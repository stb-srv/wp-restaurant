<?php
/**
 * License Server - Security Functions
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Login prüfen
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Authentifizierung
function authenticate($username, $password) {
    $config = get_config();
    
    if (empty($config) || !isset($config['admin'])) {
        return false;
    }
    
    $admin = $config['admin'];
    
    if ($admin['username'] !== $username) {
        return false;
    }
    
    return password_verify($password, $admin['password']);
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
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Rate Limiting (einfach)
function check_rate_limit($action = 'default', $limit = 100) {
    $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    $count = $_SESSION[$key] ?? 0;
    $time = $_SESSION[$key . '_time'] ?? time();
    
    // Reset nach 1 Stunde
    if (time() - $time > 3600) {
        $_SESSION[$key] = 0;
        $_SESSION[$key . '_time'] = time();
        return true;
    }
    
    if ($count >= $limit) {
        return false;
    }
    
    $_SESSION[$key] = $count + 1;
    return true;
}
