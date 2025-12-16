<?php
/**
 * License Server - Configuration Loader (DB-basiert!)
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Datenbank-Manager laden
require_once __DIR__ . '/database.php';

// Config laden (aus DB!)
function get_config() {
    $db = LicenseDB::getInstance();
    
    // Admin-Config
    $admin = $db->getConfig('admin', null);
    
    if (!$admin) {
        return [];
    }
    
    return array(
        'admin' => $admin,
        'api_key' => $db->getConfig('api_key', ''),
        'timezone' => $db->getConfig('timezone', 'Europe/Berlin'),
        'currency' => $db->getConfig('currency', '€'),
    );
}

// API Key abrufen
function get_api_key() {
    $db = LicenseDB::getInstance();
    return $db->getConfig('api_key', '');
}

// Timezone setzen
$db = LicenseDB::getInstance();
$timezone = $db->getConfig('timezone', 'Europe/Berlin');
date_default_timezone_set($timezone);
