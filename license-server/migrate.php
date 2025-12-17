<?php
/**
 * License Server - Datenbank Migration
 * Führt Upgrade auf 5-Lizenz-System durch
 */

define('LICENSE_SERVER', true);

require_once __DIR__ . '/includes/database.php';

echo "<h1>🔧 WP Restaurant License Server - Migration</h1>";

$db = LicenseDB::getInstance();

if (!$db->getConnection()) {
    die("<p style='color:red;'>❌ Datenbankverbindung fehlgeschlagen!</p>");
}

echo "<p style='color:green;'>✅ Datenbankverbindung erfolgreich</p>";

// Tabellen erstellen/aktualisieren
echo "<h2>1. Tabellen erstellen...</h2>";

if ($db->createTables()) {
    echo "<p style='color:green;'>✅ Tabellen erstellt/aktualisiert</p>";
} else {
    echo "<p style='color:red;'>❌ Fehler beim Erstellen der Tabellen</p>";
}

// Pricing aktualisieren
echo "<h2>2. Pricing-Daten aktualisieren...</h2>";

$pricing = array(
    'free' => array(
        'price' => 0,
        'currency' => '€',
        'label' => 'FREE',
        'max_items' => 20,
        'features' => array(),
    ),
    'free_plus' => array(
        'price' => 15,
        'currency' => '€',
        'label' => 'FREE+',
        'max_items' => 60,
        'features' => array(),
    ),
    'pro' => array(
        'price' => 29,
        'currency' => '€',
        'label' => 'PRO',
        'max_items' => 200,
        'features' => array(),
    ),
    'pro_plus' => array(
        'price' => 49,
        'currency' => '€',
        'label' => 'PRO+',
        'max_items' => 200,
        'features' => array('dark_mode', 'cart'),
    ),
    'ultimate' => array(
        'price' => 79,
        'currency' => '€',
        'label' => 'ULTIMATE',
        'max_items' => 900,
        'features' => array('dark_mode', 'cart', 'unlimited_items'),
    ),
);

if ($db->savePricing($pricing)) {
    echo "<p style='color:green;'>✅ Pricing-Daten gespeichert</p>";
} else {
    echo "<p style='color:red;'>❌ Fehler beim Speichern der Pricing-Daten</p>";
}

// Bestehende Lizenzen prüfen
echo "<h2>3. Bestehende Lizenzen prüfen...</h2>";

$licenses = $db->getAllLicenses();
echo "<p>Gefundene Lizenzen: " . count($licenses) . "</p>";

if (count($licenses) > 0) {
    echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Key</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Type</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Domain</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Max Items</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Features</th>";
    echo "</tr>";
    
    foreach ($licenses as $key => $license) {
        echo "<tr>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'><code>" . esc_html($key) . "</code></td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html($license['type']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html($license['domain']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html($license['max_items']) . "</td>";
        echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . (empty($license['features']) ? '-' : implode(', ', $license['features'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Pricing anzeigen
echo "<h2>4. Aktuelle Pricing-Daten</h2>";

$current_pricing = $db->getPricing();

echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Paket</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Preis</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Max Items</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd;'>Features</th>";
echo "</tr>";

foreach ($current_pricing as $type => $data) {
    echo "<tr>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'><strong>" . esc_html($data['label']) . "</strong></td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html($data['price']) . " " . esc_html($data['currency']) . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html($data['max_items'] ?? 'N/A') . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . (empty($data['features']) ? '-' : implode(', ', $data['features'])) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2 style='color:green;'>✅ Migration abgeschlossen!</h2>";
echo "<p><strong>Nächste Schritte:</strong></p>";
echo "<ul>";
echo "<li>Teste die API: <code>" . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/api.php?action=get_pricing</code></li>";
echo "<li>Gehe zum WordPress-Plugin und klicke auf 'Cache löschen'</li>";
echo "<li>Lösche diese Datei aus Sicherheitsgründen: <code>migrate.php</code></li>";
echo "</ul>";

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
