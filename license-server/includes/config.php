<?php
/**
 * License Server - Configuration Loader
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Pfade
define('DATA_DIR', __DIR__ . '/../data');
define('LOGS_DIR', __DIR__ . '/../logs');

// Config laden
function get_config() {
    static $config = null;
    
    if ($config === null) {
        $file = DATA_DIR . '/config.json';
        if (file_exists($file)) {
            $config = json_decode(file_get_contents($file), true);
        } else {
            $config = [];
        }
    }
    
    return $config;
}

// API Key abrufen
function get_api_key() {
    $config = get_config();
    return $config['api_key'] ?? '';
}

// Timezone setzen
$config = get_config();
date_default_timezone_set($config['timezone'] ?? 'Europe/Berlin');
