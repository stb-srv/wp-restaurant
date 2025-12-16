<?php
/**
 * License Server - Security Functions (MySQL)
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Login prüfen
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Authentifizierung (MySQL)
function authenticate($username, $password) {
    $config = get_config();
    
    if (!isset($config['admin_username']) || !isset($config['admin_password'])) {
        return false;
    }
    
    if ($username === $config['admin_username'] && password_verify($password, $config['admin_password'])) {
        log_message("Successful login: $username", 'security');
        return true;
    }
    
    log_message("Failed login attempt: $username", 'security');
    return false;
}

// CSRF Token generieren
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token prüfen
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate Limiting (MySQL)
function check_rate_limit($identifier, $max_requests = 60, $time_window = 3600) {
    $db = get_db();
    
    try {
        // Alte Einträge löschen
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$time_window]);
        
        // Zählen
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$identifier, $time_window]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $max_requests) {
            return false;
        }
        
        // Neuen Request hinzufügen
        $stmt = $db->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
        $stmt->execute([$identifier]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return true; // Bei Fehler durchlassen
    }
}

// Passwort ändern
function change_password($new_password) {
    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
    set_config('admin_password', $hashed);
    log_message('Password changed', 'security');
    return true;
}
