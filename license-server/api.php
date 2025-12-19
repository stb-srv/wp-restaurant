<?php
/**
 * License Server - Public API Endpoint
 * Version 2.1 - Enhanced Security & Input Validation
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
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Input Sanitization Helper
function sanitize_license_key($key) {
    $key = strtoupper(trim($key));
    // Nur erlaubte Zeichen: A-Z, 0-9, Bindestriche
    $key = preg_replace('/[^A-Z0-9-]/', '', $key);
    return $key;
}

function sanitize_domain($domain) {
    $domain = strtolower(trim($domain));
    // Entferne Protokoll falls vorhanden
    $domain = preg_replace('#^https?://#', '', $domain);
    // Entferne trailing slash
    $domain = rtrim($domain, '/');
    // Validiere Domain-Format
    if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
        return false;
    }
    return $domain;
}

// Datenbank-Config prüfen
if (!file_exists(__DIR__ . '/db-config.php')) {
    api_response([
        'success' => false,
        'error' => 'Database not configured. Please run installer.',
    ], 500);
}

// Includes laden in korrekter Reihenfolge
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

// Action bestimmen und validieren
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

if (empty($action)) {
    api_response([
        'success' => false,
        'error' => 'No action specified. Available: get_pricing, check_license, status',
    ], 400);
}

// Whitelist erlaubter Actions
$allowed_actions = ['get_pricing', 'check_license', 'status', 'debug_licenses'];
if (!in_array($action, $allowed_actions)) {
    api_response([
        'success' => false,
        'error' => 'Invalid action: ' . htmlspecialchars($action),
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
    // Input validieren und sanitizen
    $key = isset($_GET['key']) ? sanitize_license_key($_GET['key']) : '';
    $domain = isset($_GET['domain']) ? sanitize_domain($_GET['domain']) : '';
    
    if (empty($key)) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'License key required',
        ], 400);
    }
    
    if ($domain === false || empty($domain)) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'Valid domain required',
        ], 400);
    }
    
    // Lizenzschlüssel-Format validieren
    if (!preg_match('/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}(-[A-Z0-9]{5})?$/', $key)) {
        api_response([
            'success' => false,
            'valid' => false,
            'message' => 'Invalid license key format',
        ], 400);
    }
    
    try {
        // Lizenz aus DB laden
        $license = $db->getLicense($key);
        
        if (!$license) {
            $db->log('check_license_failed', "Not found: $key for $domain");
            api_response([
                'success' => false,
                'valid' => false,
                'message' => 'License not found',
            ]);
        }
        
        // Domain prüfen (wenn gesetzt)
        if (!empty($license['domain']) && $license['domain'] !== '*' && $license['domain'] !== $domain) {
            $db->log('check_license_failed', "Domain mismatch: $key for $domain (expected: {$license['domain']})");
            api_response([
                'success' => false,
                'valid' => false,
                'message' => 'Domain mismatch. License is for: ' . htmlspecialchars($license['domain']),
            ]);
        }
        
        // Ablaufdatum prüfen
        if (!empty($license['expires']) && $license['expires'] !== 'lifetime') {
            if (strtotime($license['expires']) < time()) {
                $db->log('check_license_failed', "Expired: $key on {$license['expires']}");
                api_response([
                    'success' => false,
                    'valid' => false,
                    'message' => 'License expired on ' . htmlspecialchars($license['expires']),
                ]);
            }
        }
        
        // Lizenz ist gültig!
        $db->log('check_license_success', "Valid: $key for $domain");
        
        api_response([
            'success' => true,
            'valid' => true,
            'type' => $license['type'],
            'max_items' => (int)$license['max_items'],
            'expires' => $license['expires'],
            'features' => $license['features'],
        ]);
        
    } catch (Exception $e) {
        $db->log('check_license_error', "Error: " . $e->getMessage());
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
        'version' => '2.1',
        'timestamp' => time(),
        'php_version' => PHP_VERSION,
    ]);
}

// DEBUG: Alle Lizenzen anzeigen (nur mit Secret!)
if ($action === 'debug_licenses') {
    $secret = isset($_GET['secret']) ? $_GET['secret'] : '';
    
    // Secret validieren (sollte aus Umgebungsvariable kommen)
    $valid_secret = getenv('DEBUG_SECRET') ?: 'debug123';
    
    if ($secret !== $valid_secret) {
        api_response([
            'success' => false,
            'error' => 'Unauthorized',
        ], 403);
    }
    
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

// Sollte nie erreicht werden (wegen Whitelist)
api_response([
    'success' => false,
    'error' => 'Invalid action',
], 400);
