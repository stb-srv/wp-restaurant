<?php
/**
 * License Server - Helper Functions
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

// Preise laden
function get_pricing() {
    $file = DATA_DIR . '/pricing.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [
        'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
        'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
        'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
    ];
}

// Preise speichern
function save_pricing($pricing) {
    $file = DATA_DIR . '/pricing.json';
    return file_put_contents($file, json_encode($pricing, JSON_PRETTY_PRINT));
}

// Lizenzen laden
function get_licenses() {
    $file = DATA_DIR . '/licenses.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return [];
}

// Lizenzen speichern
function save_licenses($licenses) {
    $file = DATA_DIR . '/licenses.json';
    return file_put_contents($file, json_encode($licenses, JSON_PRETTY_PRINT));
}

// Einzelne Lizenz laden
function get_license($key) {
    $licenses = get_licenses();
    return $licenses[strtoupper($key)] ?? null;
}

// Lizenz hinzufügen/aktualisieren
function save_license($key, $data) {
    $licenses = get_licenses();
    $licenses[strtoupper($key)] = $data;
    return save_licenses($licenses);
}

// Lizenz löschen
function delete_license($key) {
    $licenses = get_licenses();
    unset($licenses[strtoupper($key)]);
    return save_licenses($licenses);
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

// Logging
function log_message($message, $type = 'info') {
    $file = LOGS_DIR . '/server.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = "[$timestamp] [$type] [$ip] $message\n";
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

// Statistiken
function get_stats() {
    $licenses = get_licenses();
    
    $stats = [
        'total' => count($licenses),
        'active' => 0,
        'expired' => 0,
        'by_type' => [
            'free' => 0,
            'pro' => 0,
            'pro_plus' => 0,
        ],
    ];
    
    foreach ($licenses as $license) {
        // Type zählen
        $type = $license['type'] ?? 'free';
        if (isset($stats['by_type'][$type])) {
            $stats['by_type'][$type]++;
        }
        
        // Aktiv/Expired
        if (isset($license['expires'])) {
            if ($license['expires'] === 'lifetime' || strtotime($license['expires']) > time()) {
                $stats['active']++;
            } else {
                $stats['expired']++;
            }
        } else {
            $stats['active']++;
        }
    }
    
    return $stats;
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
