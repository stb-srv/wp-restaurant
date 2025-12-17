<?php
/**
 * License Server - Public API Endpoint
 * Version 2.0 - Improved Error Handling
 */

define('LICENSE_SERVER', true);

// Fehler-Logging (nur für kritische Fehler)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Helper für JSON Response
function api_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Datenbank-Config prüfen
if (!file_exists(__DIR__ . '/db-config.php')) {
    api_response([
        'success' => false,
        'error' => 'Database not configured. Please run installer.',
    ], 500);
}

// Includes laden
try {
    if (!file_exists(__DIR__ . '/includes/database.php')) {
        throw new Exception('Database class not found');
    }
    require_once __DIR__ . '/includes/database.php';
    
    if (!file_exists(__DIR__ . '/includes/config.php')) {
        throw new Exception('Config file not found');
    }
    require_once __DIR__ . '/includes/config.php';
    
    if (!file_exists(__DIR__ . '/includes/functions.php')) {
        throw new Exception('Functions file not found');
    }
    require_once __DIR__ . '/includes/functions.php';
    
} catch (Exception $e) {
    api_response([
        'success' => false,
        'error' => 'Server configuration error: ' . $e->getMessage(),
    ], 500);
}

// Datenbank-Verbindung prüfen
try {
    $db = LicenseDB::getInstance();
    
    if (!$db || !$db->getConnection()) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    api_response([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
    ], 500);
}

// Action bestimmen
$action = $_GET['action'] ?? '';

if (empty($action)) {
    api_response([
        'success' => false,
        'error' => 'No action specified. Available: get_pricing, check_license',
    ], 400);
}

// PRICING ABRUFEN
if ($action === 'get_pricing') {
    try {
        $pricing = $db->getPricing();
        
        if (empty($pricing)) {
            throw new Exception('No pricing data available');
        }
        
        api_response([
            'success' => true,
            'pricing' => $pricing,
        ]);
    } catch (Exception $e) {
        api_response([
            'success' => false,
            'error' => 'Failed to load pricing: ' . $e->getMessage(),
        ], 500);
    }
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
    
    try {
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
        
    } catch (Exception $e) {
        api_response([
            'success' => false,
            'valid' => false,
            'error' => 'License check failed: ' . $e->getMessage(),
        ], 500);
    }
}

// DEBUG: Server-Status (ohne Secret)
if ($action === 'status') {
    api_response([
        'success' => true,
        'status' => 'online',
        'version' => '2.0',
        'timestamp' => time(),
    ]);
}

// DEBUG: Alle Lizenzen anzeigen (nur mit Secret!)
if ($action === 'debug_licenses' && isset($_GET['secret']) && $_GET['secret'] === 'debug123') {
    try {
        $licenses = $db->getAllLicenses();
        
        api_response([
            'success' => true,
            'count' => count($licenses),
            'licenses' => $licenses,
        ]);
    } catch (Exception $e) {
        api_response([
            'success' => false,
            'error' => 'Debug failed: ' . $e->getMessage(),
        ], 500);
    }
}

// Ungültige Action
api_response([
    'success' => false,
    'error' => 'Invalid action: ' . $action . '. Available: get_pricing, check_license, status',
], 400);
