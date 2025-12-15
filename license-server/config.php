<?php
/**
 * License Server Configuration
 * 
 * WICHTIG: Dateiname nach Upload ändern zu .htaccess schützen!
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Datenbank-Verbindung
define('DB_HOST', 'sql113.infinityfree.com');
define('DB_NAME', 'if0_40687657_wp_stb_srv_keys');
define('DB_USER', 'if0_40687657');
define('DB_PASS', 'OKvw8YtZqtH');
define('DB_CHARSET', 'utf8mb4');

// Admin-Panel Passwort (SHA256 Hash)
// Standard-Passwort: "admin2025" - BITTE ÄNDERN!
define('ADMIN_PASSWORD_HASH', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');

// Sicherheit
define('API_KEY', 'WPR_2025_SECRET_' . md5(DB_NAME)); // Automatisch generiert
define('ALLOW_LOCAL_REQUESTS', true); // Für Tests

// Logging
define('ENABLE_LOGGING', true);
define('LOG_FILE', __DIR__ . '/logs/access.log');

// Rate Limiting
define('MAX_REQUESTS_PER_HOUR', 100);

// Timezone
date_default_timezone_set('Europe/Berlin');

// Datenbank-Verbindung herstellen
function get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
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
            return false;
        }
    }
    
    return $conn;
}

// Helper: JSON Response
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Helper: Logging
function log_access($message) {
    if (!ENABLE_LOGGING) return;
    
    $log_dir = dirname(LOG_FILE);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = "[$timestamp] [$ip] $message\n";
    
    @file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

// Helper: Rate Limiting Check
function check_rate_limit($identifier) {
    $db = get_db_connection();
    if (!$db) return true; // Bei DB-Fehler durchlassen
    
    try {
        // Alte Einträge löschen (älter als 1h)
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        
        // Zählen
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$identifier]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= MAX_REQUESTS_PER_HOUR) {
            return false;
        }
        
        // Eintrag hinzufügen
        $stmt = $db->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
        $stmt->execute([$identifier]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return true; // Bei Fehler durchlassen
    }
}
