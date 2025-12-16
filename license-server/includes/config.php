<?php
/**
 * License Server - Configuration Loader (MySQL)
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Pfade
define('DATA_DIR', __DIR__ . '/../data');
define('LOGS_DIR', __DIR__ . '/../logs');

// DB-Config laden
if (file_exists(DATA_DIR . '/db-config.php')) {
    require_once DATA_DIR . '/db-config.php';
} else {
    die('Database not configured. Please run installer.');
}

// PDO Connection
function get_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please check configuration.');
        }
    }
    
    return $pdo;
}

// Config aus DB laden
function get_config() {
    static $config = null;
    
    if ($config === null) {
        $db = get_db();
        $stmt = $db->query("SELECT `key`, `value` FROM config");
        $rows = $stmt->fetchAll();
        
        $config = [];
        foreach ($rows as $row) {
            $config[$row['key']] = $row['value'];
        }
    }
    
    return $config;
}

// Config-Wert setzen
function set_config($key, $value) {
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$key, $value]);
}

// API Key abrufen
function get_api_key() {
    $config = get_config();
    return $config['api_key'] ?? '';
}

// Timezone setzen
$config = get_config();
date_default_timezone_set($config['timezone'] ?? 'Europe/Berlin');
