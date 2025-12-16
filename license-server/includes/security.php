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
    
    if (!isset($config['admin'])) {
        return false;
    }
    
    $admin = $config['admin'];
    
    if ($username === $admin['username'] && password_verify($password, $admin['password'])) {
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

// Rate Limiting (einfach, dateibasiert)
function check_rate_limit($identifier, $max_requests = 60, $time_window = 3600) {
    $file = LOGS_DIR . '/rate_' . md5($identifier) . '.txt';
    
    $requests = [];
    if (file_exists($file)) {
        $requests = json_decode(file_get_contents($file), true) ?? [];
    }
    
    // Alte Einträge entfernen
    $now = time();
    $requests = array_filter($requests, function($timestamp) use ($now, $time_window) {
        return $timestamp > ($now - $time_window);
    });
    
    // Limit prüfen
    if (count($requests) >= $max_requests) {
        return false;
    }
    
    // Neuen Request hinzufügen
    $requests[] = $now;
    file_put_contents($file, json_encode($requests));
    
    return true;
}
