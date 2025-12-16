<?php
/**
 * WP Restaurant - License API Endpoint
 * Erlaubt License-Server die Server-URL zu setzen!
 */

require_once __DIR__ . '/wp-load.php';

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

require_once plugin_dir_path(__FILE__) . 'wp-restaurant-menu/includes/class-wpr-license.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ));
    exit;
}

// Action prüfen
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'set_server_url') {
    // ============================================
    // SERVER-URL SETZEN (vom License-Server)
    // ============================================
    
    $api_key = $_POST['api_key'] ?? '';
    $server_url = $_POST['server_url'] ?? '';
    $domain = $_POST['domain'] ?? '';
    
    // Domain prüfen (Basic Security)
    $current_domain = $_SERVER['HTTP_HOST'];
    if ($domain !== $current_domain) {
        http_response_code(403);
        echo json_encode(array(
            'success' => false,
            'message' => 'Domain mismatch. Expected: ' . $current_domain,
        ));
        exit;
    }
    
    // API-Key prüfen (Basic Auth)
    $stored_api_key = get_option('wpr_api_key', '');
    
    // Wenn noch kein API-Key gesetzt, automatisch generieren
    if (empty($stored_api_key)) {
        $stored_api_key = bin2hex(random_bytes(32));
        update_option('wpr_api_key', $stored_api_key);
    }
    
    if ($api_key !== $stored_api_key) {
        http_response_code(401);
        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid API key',
        ));
        exit;
    }
    
    // Server-URL setzen
    if (WPR_License::set_server_url($server_url)) {
        // Cache löschen
        delete_transient('wpr_pricing_data');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Server-URL erfolgreich gesetzt',
            'server_url' => $server_url,
        ));
    } else {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'message' => 'Fehler beim Setzen der Server-URL',
        ));
    }
    
} elseif ($action === 'get_api_key') {
    // ============================================
    // API-KEY ABRUFEN (für Setup)
    // ============================================
    
    // Nur für Admins
    if (!current_user_can('manage_options')) {
        http_response_code(403);
        echo json_encode(array(
            'success' => false,
            'message' => 'Unauthorized. Admin access required.',
        ));
        exit;
    }
    
    $api_key = get_option('wpr_api_key', '');
    
    // Wenn noch kein Key, generieren
    if (empty($api_key)) {
        $api_key = bin2hex(random_bytes(32));
        update_option('wpr_api_key', $api_key);
    }
    
    echo json_encode(array(
        'success' => true,
        'api_key' => $api_key,
        'domain' => $_SERVER['HTTP_HOST'],
    ));
    
} else {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Unknown action. Available: set_server_url, get_api_key',
    ));
}
