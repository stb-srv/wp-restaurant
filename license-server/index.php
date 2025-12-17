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

// Fehler-Logging aktivieren (nur für Debug)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Datenbank ZUERST laden (vor functions.php!)
if (file_exists(__DIR__ . '/includes/database.php')) {
    require_once __DIR__ . '/includes/database.php';
}

// Config laden
if (file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
}

// Functions laden (benötigt database.php!)
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

// Security laden
if (file_exists(__DIR__ . '/includes/security.php')) {
    require_once __DIR__ . '/includes/security.php';
}

// Prüfen ob alle Dateien geladen wurden
if (!function_exists('is_logged_in')) {
    die('Error: Security functions not loaded. Please check includes/security.php');
}

if (!class_exists('LicenseDB')) {
    die('Error: Database class not loaded. Please check includes/database.php');
}

// Login prüfen
if (!is_logged_in() && !isset($_POST['login'])) {
    if (file_exists(__DIR__ . '/views/login.php')) {
        require_once __DIR__ . '/views/login.php';
    } else {
        die('Error: Login view not found.');
    }
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
if (file_exists(__DIR__ . '/views/admin.php')) {
    require_once __DIR__ . '/views/admin.php';
} else {
    die('Error: Admin view not found.');
}
