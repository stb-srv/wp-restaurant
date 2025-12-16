<?php
/**
 * License Server - Helper Functions (DB-basiert!)
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

// Preise laden (aus DB!)
function get_pricing() {
    $db = LicenseDB::getInstance();
    return $db->getPricing();
}

// Preise speichern (in DB!)
function save_pricing($pricing) {
    $db = LicenseDB::getInstance();
    return $db->savePricing($pricing);
}

// Lizenzen laden (aus DB!)
function get_licenses() {
    $db = LicenseDB::getInstance();
    return $db->getAllLicenses();
}

// Einzelne Lizenz laden (aus DB!)
function get_license($key) {
    $db = LicenseDB::getInstance();
    return $db->getLicense($key);
}

// Lizenz hinzufügen/aktualisieren (in DB!)
function save_license($key, $data) {
    $db = LicenseDB::getInstance();
    return $db->saveLicense($key, $data);
}

// Lizenz löschen (aus DB!)
function delete_license($key) {
    $db = LicenseDB::getInstance();
    return $db->deleteLicense($key);
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

// Logging (in DB!)
function log_message($message, $type = 'info') {
    $db = LicenseDB::getInstance();
    $db->log($type, $message);
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
