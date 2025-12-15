<?php
/**
 * License Server API Endpoint
 * 
 * Aufruf: https://wp-stb-srv.infinityfree.me/license-server/api.php?key=LIZENZ&domain=example.com
 */

define('LICENSE_SERVER', true);
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json');

// Rate Limiting
$client_id = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!check_rate_limit($client_id)) {
    json_response([
        'valid' => false,
        'error' => 'Rate limit exceeded. Try again later.',
    ], 429);
}

// Parameter auslesen
$license_key = isset($_GET['key']) ? trim($_GET['key']) : '';
$domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';

// Validierung
if (empty($license_key)) {
    log_access('Missing license key');
    json_response([
        'valid' => false,
        'error' => 'License key required',
        'max_items' => 20,
    ]);
}

if (empty($domain)) {
    log_access('Missing domain');
    json_response([
        'valid' => false,
        'error' => 'Domain required',
        'max_items' => 20,
    ]);
}

// Domain normalisieren
$domain = strtolower($domain);
$domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
$domain = explode('/', $domain)[0]; // Nur Hauptdomain

// Datenbank
$db = get_db_connection();
if (!$db) {
    log_access('Database connection failed');
    json_response([
        'valid' => false,
        'error' => 'Service temporarily unavailable',
        'max_items' => 20,
    ], 503);
}

try {
    // Lizenz suchen
    $stmt = $db->prepare("
        SELECT * FROM licenses 
        WHERE license_key = ? 
        LIMIT 1
    ");
    $stmt->execute([$license_key]);
    $license = $stmt->fetch();
    
    // Access Log
    $log_stmt = $db->prepare("
        INSERT INTO access_logs (license_key, domain, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // Lizenz nicht gefunden
    if (!$license) {
        log_access("Invalid license key: $license_key");
        $log_stmt->execute([$license_key, $domain, $client_id, $_SERVER['HTTP_USER_AGENT'] ?? '', 'invalid']);
        
        json_response([
            'valid' => false,
            'error' => 'Invalid license key',
            'max_items' => 20,
        ]);
    }
    
    // Lizenz inaktiv
    if (!$license['active']) {
        log_access("Inactive license: $license_key");
        $log_stmt->execute([$license_key, $domain, $client_id, $_SERVER['HTTP_USER_AGENT'] ?? '', 'inactive']);
        
        json_response([
            'valid' => false,
            'error' => 'License deactivated',
            'max_items' => 20,
        ]);
    }
    
    // Lizenz abgelaufen
    if (!empty($license['expires_at'])) {
        $expires = strtotime($license['expires_at']);
        if ($expires < time()) {
            log_access("Expired license: $license_key");
            $log_stmt->execute([$license_key, $domain, $client_id, $_SERVER['HTTP_USER_AGENT'] ?? '', 'expired']);
            
            json_response([
                'valid' => false,
                'error' => 'License expired',
                'expires' => $license['expires_at'],
                'max_items' => 20,
            ]);
        }
    }
    
    // Domain-Check
    $allowed_domains = !empty($license['domains']) ? explode(',', $license['domains']) : [];
    $allowed_domains = array_map('trim', $allowed_domains);
    $allowed_domains = array_map('strtolower', $allowed_domains);
    
    $domain_valid = false;
    if (empty($allowed_domains)) {
        // Keine Domain-Beschränkung
        $domain_valid = true;
    } else {
        // Prüfe ob Domain erlaubt ist
        foreach ($allowed_domains as $allowed) {
            if ($allowed === '*' || $domain === $allowed || strpos($domain, $allowed) !== false) {
                $domain_valid = true;
                break;
            }
        }
    }
    
    if (!$domain_valid) {
        log_access("Domain not authorized: $domain for license $license_key");
        $log_stmt->execute([$license_key, $domain, $client_id, $_SERVER['HTTP_USER_AGENT'] ?? '', 'domain_mismatch']);
        
        json_response([
            'valid' => false,
            'error' => 'Domain not authorized',
            'max_items' => 20,
        ]);
    }
    
    // ✅ Alles OK - Lizenz gültig!
    log_access("Valid license: $license_key for domain $domain");
    $log_stmt->execute([$license_key, $domain, $client_id, $_SERVER['HTTP_USER_AGENT'] ?? '', 'success']);
    
    // Zähler erhöhen
    $update_stmt = $db->prepare("
        UPDATE licenses 
        SET last_checked = NOW(), check_count = check_count + 1 
        WHERE id = ?
    ");
    $update_stmt->execute([$license['id']]);
    
    // Response
    json_response([
        'valid' => true,
        'license_key' => $license['license_key'],
        'max_items' => (int)$license['max_items'],
        'expires' => $license['expires_at'],
        'customer' => $license['customer_name'],
        'features' => ['unlimited_items', 'priority_support'],
    ]);
    
} catch (PDOException $e) {
    error_log('License check error: ' . $e->getMessage());
    log_access('Database error: ' . $e->getMessage());
    
    json_response([
        'valid' => false,
        'error' => 'Service error',
        'max_items' => 20,
    ], 500);
}
