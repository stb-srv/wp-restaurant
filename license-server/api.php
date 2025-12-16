<?php
/**
 * License Server - Public API Endpoint
 */

define('LICENSE_SERVER', true);

// Datenbank-Config laden
if (!file_exists(__DIR__ . '/db-config.php')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database not configured. Please run installer.',
    ]);
    exit;
}

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Helper für JSON Response
function api_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Datenbank-Verbindung prüfen
$db = LicenseDB::getInstance();
if (!$db->getConnection()) {
    api_response([
        'success' => false,
        'error' => 'Database connection failed',
    ], 500);
}

// Action bestimmen
$action = $_GET['action'] ?? '';

// PRICING ABRUFEN
if ($action === 'get_pricing') {
    $pricing = $db->getPricing();
    
    api_response([
        'success' => true,
        'pricing' => $pricing,
    ]);
}

// LIZENZ PRÜFEN
if ($action === 'check_license') {
    $key = isset($_GET['key']) ? strtoupper(trim($_GET['key'])) : '';
    $domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';
    
    if (empty($key)) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'License key required',
        ], 400);
    }
    
    if (empty($domain)) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'Domain required',
        ], 400);
    }
    
    // Lizenz aus DB laden
    $license = $db->getLicense($key);
    
    if (!$license) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'License not found',
        ]);
    }
    
    // Domain prüfen (wenn gesetzt)
    if (!empty($license['domain']) && $license['domain'] !== '*' && $license['domain'] !== $domain) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'Domain mismatch. License is for: ' . $license['domain'],
        ]);
    }
    
    // Ablaufdatum prüfen
    if (!empty($license['expires']) && $license['expires'] !== 'lifetime') {
        if (strtotime($license['expires']) < time()) {
            api_response([
                'success' => false,
                'valid' => false,
                'message' => 'License expired on ' . $license['expires'],
            ]);
        }
    }
    
    // Lizenz ist gültig!
    $db->log('check_license', "Valid: $key for $domain");
    
    api_response([
        'success' => true,
        'valid' => true,
        'type' => $license['type'],
        'max_items' => (int)$license['max_items'],
        'expires' => $license['expires'],
        'features' => $license['features'],
    ]);
}

// DEBUG: Alle Lizenzen anzeigen (nur für Tests!)
if ($action === 'debug_licenses' && isset($_GET['secret']) && $_GET['secret'] === 'debug123') {
    $licenses = $db->getAllLicenses();
    
    api_response([
        'success' => true,
        'count' => count($licenses),
        'licenses' => $licenses,
    ]);
}

// Ungültige Action
api_response([
    'success' => false,
    'error' => 'Invalid action. Available: get_pricing, check_license',
], 400);
