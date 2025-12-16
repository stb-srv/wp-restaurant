<?php
/**
 * WP Restaurant Menu - License Server v2.0
 * Professional License Management System
 */

session_start();
define('LICENSE_SERVER', true);

// Auto-Installer bei erstem Aufruf
if (!file_exists(__DIR__ . '/.installed')) {
    require_once __DIR__ . '/installer.php';
    exit;
}

// Config laden
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

// Login prüfen
if (!is_logged_in() && !isset($_POST['login'])) {
    require_once __DIR__ . '/views/login.php';
    exit;
}

// Login verarbeiten
if (isset($_POST['login'])) {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (authenticate($username, $password)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Ungültige Zugangsdaten!';
        require_once __DIR__ . '/views/login.php';
        exit;
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Tab-System
$current_tab = $_GET['tab'] ?? 'dashboard';

// Admin-Panel laden
require_once __DIR__ . '/views/admin.php';
