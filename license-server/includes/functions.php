<?php
/**
 * License Server - Helper Functions (MySQL)
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Input bereinigen
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// JSON Response senden
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Preise laden (aus MySQL)
function get_pricing() {
    $db = get_db();
    $stmt = $db->query("SELECT type, price, currency, label FROM pricing");
    $rows = $stmt->fetchAll();
    
    $pricing = [];
    foreach ($rows as $row) {
        $pricing[$row['type']] = [
            'price' => (int)$row['price'],
            'currency' => $row['currency'],
            'label' => $row['label'],
        ];
    }
    
    return $pricing;
}

// Preise speichern
function save_pricing($pricing) {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO pricing (type, price, currency, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), currency = VALUES(currency), label = VALUES(label)");
    
    foreach ($pricing as $type => $data) {
        $stmt->execute([$type, $data['price'], $data['currency'], $data['label']]);
    }
    
    return true;
}

// Lizenzen laden
function get_licenses() {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM licenses ORDER BY created_at DESC");
    $rows = $stmt->fetchAll();
    
    $licenses = [];
    foreach ($rows as $row) {
        $licenses[$row['key']] = [
            'type' => $row['type'],
            'domain' => $row['domain'],
            'max_items' => (int)$row['max_items'],
            'expires' => $row['expires'],
            'features' => json_decode($row['features'], true) ?: [],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
    
    return $licenses;
}

// Lizenzen speichern (nicht mehr nötig - einzeln speichern)
function save_licenses($licenses) {
    // Legacy-Funktion für Kompatibilität
    return true;
}

// Einzelne Lizenz laden
function get_license($key) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM licenses WHERE `key` = ?");
    $stmt->execute([strtoupper($key)]);
    $row = $stmt->fetch();
    
    if (!$row) return null;
    
    return [
        'type' => $row['type'],
        'domain' => $row['domain'],
        'max_items' => (int)$row['max_items'],
        'expires' => $row['expires'],
        'features' => json_decode($row['features'], true) ?: [],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

// Lizenz hinzufügen/aktualisieren
function save_license($key, $data) {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO licenses (`key`, type, domain, max_items, expires, features) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            type = VALUES(type),
            domain = VALUES(domain),
            max_items = VALUES(max_items),
            expires = VALUES(expires),
            features = VALUES(features)
    ");
    
    $stmt->execute([
        strtoupper($key),
        $data['type'],
        $data['domain'],
        $data['max_items'],
        $data['expires'],
        json_encode($data['features']),
    ]);
    
    return true;
}

// Lizenz löschen
function delete_license($key) {
    $db = get_db();
    $stmt = $db->prepare("DELETE FROM licenses WHERE `key` = ?");
    $stmt->execute([strtoupper($key)]);
    return true;
}

// Lizenzschlüssel generieren
function generate_license_key($type = 'pro') {
    $prefix = 'WPR';
    $segments = [];
    
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
    
    return $prefix . '-' . implode('-', $segments);
}

// Logging (in MySQL)
function log_message($message, $type = 'info') {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO logs (type, message, ip) VALUES (?, ?, ?)");
    $stmt->execute([$type, $message, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    
    // Alte Logs löschen (>älter als 30 Tage)
    $db->exec("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
}

// Statistiken
function get_stats() {
    $db = get_db();
    
    // Total
    $stmt = $db->query("SELECT COUNT(*) as total FROM licenses");
    $total = $stmt->fetch()['total'];
    
    // Aktiv/Expired
    $stmt = $db->query("SELECT COUNT(*) as count FROM licenses WHERE expires = 'lifetime' OR expires >= CURDATE()");
    $active = $stmt->fetch()['count'];
    $expired = $total - $active;
    
    // By Type
    $stmt = $db->query("SELECT type, COUNT(*) as count FROM licenses GROUP BY type");
    $by_type = [
        'free' => 0,
        'pro' => 0,
        'pro_plus' => 0,
    ];
    while ($row = $stmt->fetch()) {
        $by_type[$row['type']] = (int)$row['count'];
    }
    
    return [
        'total' => (int)$total,
        'active' => (int)$active,
        'expired' => (int)$expired,
        'by_type' => $by_type,
    ];
}

// Ablaufdatum berechnen
function calculate_expiry_date($option) {
    if ($option === 'lifetime') {
        return 'lifetime';
    }
    
    $now = new DateTime();
    
    switch ($option) {
        case '7d':
            $now->modify('+7 days');
            break;
        case '31d':
            $now->modify('+31 days');
            break;
        case '6m':
            $now->modify('+6 months');
            break;
        case '12m':
            $now->modify('+12 months');
            break;
        case '24m':
            $now->modify('+24 months');
            break;
        case '36m':
            $now->modify('+36 months');
            break;
        default:
            return 'lifetime';
    }
    
    return $now->format('Y-m-d');
}
