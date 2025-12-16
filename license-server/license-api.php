<?php
/**
 * WP Restaurant Menu - License Server API
 * 
 * Dieser API-Endpoint handhabt:
 * - Lizenzprüfung (?action=check_license&key=XXX&domain=YYY)
 * - Preis-Abfrage (?action=get_pricing)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$DATA_FILE = __DIR__ . '/pricing.json';
$LICENSES_FILE = __DIR__ . '/licenses.json';

// Action bestimmen
$action = isset($_GET['action']) ? $_GET['action'] : '';

// PRICING ABRUFEN
if ($action === 'get_pricing') {
    if (file_exists($DATA_FILE)) {
        $pricing = json_decode(file_get_contents($DATA_FILE), true);
    } else {
        // Fallback Preise
        $pricing = array(
            'free' => array('price' => 0, 'currency' => '€', 'label' => 'FREE'),
            'pro' => array('price' => 29, 'currency' => '€', 'label' => 'PRO'),
            'pro_plus' => array('price' => 49, 'currency' => '€', 'label' => 'PRO+'),
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'pricing' => $pricing,
    ));
    exit;
}

// LIZENZ PRÜFEN
if ($action === 'check_license') {
    $key = isset($_GET['key']) ? strtoupper(trim($_GET['key'])) : '';
    $domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';
    
    if (empty($key) || empty($domain)) {
        echo json_encode(array(
            'success' => false,
            'valid' => false,
            'message' => 'Key and domain required',
        ));
        exit;
    }
    
    // Lizenzen laden
    if (file_exists($LICENSES_FILE)) {
        $licenses = json_decode(file_get_contents($LICENSES_FILE), true);
    } else {
        $licenses = array();
    }
    
    // Lizenz suchen
    if (isset($licenses[$key])) {
        $license = $licenses[$key];
        
        // Domain prüfen
        if ($license['domain'] !== '*' && $license['domain'] !== $domain) {
            echo json_encode(array(
                'success' => false,
                'valid' => false,
                'message' => 'Domain mismatch',
            ));
            exit;
        }
        
        // Ablaufdatum prüfen
        if ($license['expires'] !== 'lifetime' && strtotime($license['expires']) < time()) {
            echo json_encode(array(
                'success' => false,
                'valid' => false,
                'message' => 'License expired',
            ));
            exit;
        }
        
        // Lizenz ist gültig
        echo json_encode(array(
            'success' => true,
            'valid' => true,
            'type' => $license['type'],
            'max_items' => $license['max_items'],
            'expires' => $license['expires'],
            'features' => $license['features'],
        ));
        exit;
    }
    
    // Lizenz nicht gefunden
    echo json_encode(array(
        'success' => false,
        'valid' => false,
        'message' => 'License not found',
    ));
    exit;
}

// Ungültige Action
echo json_encode(array(
    'success' => false,
    'message' => 'Invalid action',
));
