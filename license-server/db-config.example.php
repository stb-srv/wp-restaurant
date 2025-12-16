<?php
/**
 * License Server - Database Configuration (EXAMPLE)
 * 
 * ANLEITUNG:
 * 1. Kopiere diese Datei zu: db-config.php
 * 2. Fülle deine Datenbank-Zugangsdaten aus
 * 3. Füge db-config.php zu .gitignore hinzu!
 * 
 * WICHTIG: db-config.php NIEMALS in Git commiten!
 */

if (!defined('LICENSE_SERVER')) {
    die('Direct access not allowed');
}

// Datenbank-Verbindung
define('DB_HOST', 'localhost');                    // Meist 'localhost'
define('DB_NAME', 'license_server');               // Deine Datenbank
define('DB_USER', 'dein_username');                // Datenbank-User
define('DB_PASS', 'dein_passwort');                // Datenbank-Passwort

// Beispiel für InfinityFree:
// define('DB_HOST', 'sql113.infinityfree.com');
// define('DB_NAME', 'if0_12345678_licenses');
// define('DB_USER', 'if0_12345678');
// define('DB_PASS', 'dein_passwort_hier');
