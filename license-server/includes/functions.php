<?php
/**
 * License Server - Functions
 * Version 2.1 - Fixed and Working
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Input bereinigen
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// JSON Response
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Preise laden
function get_pricing() {
    $db = LicenseDB::getInstance();
    if (!$db || !$db->getConnection()) {
        return array(
            'free' => array('price' => 0, 'currency' => '€', 'label' => 'FREE'),
            'pro' => array('price' => 29, 'currency' => '€', 'label' => 'PRO'),
            'pro_plus' => array('price' => 49, 'currency' => '€', 'label' => 'PRO+'),
        );
    }
    return $db->getPricing();
}

// Preise speichern
function save_pricing($pricing) {
    $db = LicenseDB::getInstance();
    return $db && $db->getConnection() ? $db->savePricing($pricing) : false;
}

// Lizenzen laden
function get_licenses() {
    $db = LicenseDB::getInstance();
    return $db && $db->getConnection() ? $db->getAllLicenses() : array();
}

// Einzelne Lizenz
function get_license($key) {
    $db = LicenseDB::getInstance();
    return $db && $db->getConnection() ? $db->getLicense($key) : null;
}

// Lizenz speichern
function save_license($key, $data) {
    $db = LicenseDB::getInstance();
    return $db && $db->getConnection() ? $db->saveLicense($key, $data) : false;
}

// Lizenz löschen
function delete_license($key) {
    $db = LicenseDB::getInstance();
    return $db && $db->getConnection() ? $db->deleteLicense($key) : false;
}

// Lizenzschlüssel generieren (3 oder 4 Segmente)
function generate_license_key($segments = 3) {
    $prefix = 'WPR';
    $parts = array();
    
    // 3 oder 4 Segmente nach WPR-
    $count = in_array($segments, array(3, 4)) ? $segments : 3;
    
    for ($i = 0; $i < $count; $i++) {
        $parts[] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
    
    return $prefix . '-' . implode('-', $parts);
}

// Logging
function log_message($message, $type = 'info') {
    $db = LicenseDB::getInstance();
    if ($db && $db->getConnection()) {
        $db->log($type, $message);
    }
}

// Statistiken
function get_stats() {
    $licenses = get_licenses();
    
    $stats = array(
        'total' => count($licenses),
        'active' => 0,
        'expired' => 0,
        'by_type' => array(
            'free' => 0,
            'pro' => 0,
            'pro_plus' => 0,
        ),
    );
    
    foreach ($licenses as $license) {
        $type = $license['type'] ?? 'free';
        if (isset($stats['by_type'][$type])) {
            $stats['by_type'][$type]++;
        }
        
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
