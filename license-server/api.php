<?php
/**
 * License Server - Public API Endpoint
 */

define('LICENSE_SERVER', true);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

// Rate Limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($ip, 100, 3600)) {
    json_response([
        'success' => false,
        'error' => 'Rate limit exceeded. Try again later.',
    ], 429);
}

// Action bestimmen
$action = $_GET['action'] ?? '';

// PRICING ABRUFEN
if ($action === 'get_pricing') {
    log_message("Pricing request from $ip", 'api');
    
    json_response([
        'success' => true,
        'pricing' => get_pricing(),
    ]);
}

// LIZENZ PRÜFEN
if ($action === 'check_license') {
    $key = isset($_GET['key']) ? strtoupper(trim($_GET['key'])) : '';
    $domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';
    
    log_message("License check: key=$key, domain=$domain", 'api');
    
    if (empty($key) || empty($domain)) {
        json_response([
            'success' => false,
            'valid' => false,
            'message' => 'Key and domain required',
        ], 400);
    }
    
    // Lizenz laden
    $license = get_license($key);
    
    if (!$license) {
        log_message("License not found: $key", 'warning');
        json_response([
            'success' => false,
            'valid' => false,
            'message' => 'License not found',
        ]);
    }
    
    // Domain prüfen
    if ($license['domain'] !== '*' && $license['domain'] !== $domain) {
        log_message("Domain mismatch: $domain != {$license['domain']}", 'warning');
        json_response([
            'success' => false,
            'valid' => false,
            'message' => 'Domain mismatch',
        ]);
    }
    
    // Ablaufdatum prüfen
    if (isset($license['expires']) && $license['expires'] !== 'lifetime' && strtotime($license['expires']) < time()) {
        log_message("License expired: $key", 'warning');
        json_response([
            'success' => false,
            'valid' => false,
            'message' => 'License expired',
        ]);
    }
    
    // Lizenz ist gültig
    log_message("Valid license: $key for $domain", 'success');
    
    json_response([
        'success' => true,
        'valid' => true,
        'type' => $license['type'],
        'max_items' => $license['max_items'],
        'expires' => $license['expires'],
        'features' => $license['features'],
    ]);
}

// Ungültige Action
log_message("Invalid API action: $action", 'warning');
json_response([
    'success' => false,
    'error' => 'Invalid action',
], 400);
