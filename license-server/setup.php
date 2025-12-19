<?php
/**
 * WP Restaurant Menu - License Server Setup
 * Dieses Script erstellt alle benötigten Datenbank-Tabellen und fügt Standard-Daten ein
 */

// Sicherheit
$SETUP_PASSWORD = 'setup123'; // ÄNDERE DIES!

if (!isset($_POST['setup_password']) || $_POST['setup_password'] !== $SETUP_PASSWORD) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Database Setup</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }
            .setup-box {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 500px;
            }
            h1 {
                margin: 0 0 20px 0;
                color: #1f2937;
            }
            p {
                color: #6b7280;
                margin-bottom: 20px;
            }
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                font-size: 16px;
                box-sizing: border-box;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                margin-top: 15px;
            }
            button:hover {
                opacity: 0.9;
            }
        </style>
    </head>
    <body>
        <div class="setup-box">
            <h1>🔧 Database Setup</h1>
            <p>Dieses Script erstellt alle benötigten Tabellen und fügt Standard-Preise ein.</p>
            <form method="post">
                <input type="password" name="setup_password" placeholder="Setup-Passwort" required autofocus>
                <button type="submit">Setup starten</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Setup starten
define('LICENSE_SERVER', true);

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-top: 0;
        }
        .step {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #e5e7eb;
        }
        .step.success {
            background: #d1fae5;
            border-color: #10b981;
            color: #047857;
        }
        .step.error {
            background: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .step.info {
            background: #f0f9ff;
            border-color: #0ea5e9;
            color: #0369a1;
        }
        code {
            background: #1f2937;
            color: #fbbf24;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .button:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Setup</h1>';

// Schritt 1: Database-Klasse laden
echo '<div class="step info">📦 Lade Database-Klasse...</div>';

if (!file_exists(__DIR__ . '/includes/database.php')) {
    echo '<div class="step error">❌ FEHLER: Database-Klasse nicht gefunden!</div>';
    echo '<p>Datei <code>includes/database.php</code> existiert nicht.</p>';
    exit;
}

require_once __DIR__ . '/includes/database.php';
echo '<div class="step success">✅ Database-Klasse geladen</div>';

// Schritt 2: DB-Config prüfen
echo '<div class="step info">🔍 Prüfe DB-Konfiguration...</div>';

if (!file_exists(__DIR__ . '/db-config.php')) {
    echo '<div class="step error">❌ FEHLER: db-config.php nicht gefunden!</div>';
    echo '<p>Bitte erstelle die Datei <code>license-server/db-config.php</code> mit folgendem Inhalt:</p>';
    echo '<pre style="background: #1f2937; color: #fbbf24; padding: 15px; border-radius: 6px; overflow-x: auto;">
&lt;?php
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'dein_datenbankname\');
define(\'DB_USER\', \'dein_benutzername\');
define(\'DB_PASS\', \'dein_passwort\');
</pre>';
    exit;
}

echo '<div class="step success">✅ db-config.php gefunden</div>';

// Schritt 3: Verbindung testen
echo '<div class="step info">🔌 Teste Datenbankverbindung...</div>';

try {
    $db = LicenseDB::getInstance();
    
    if (!$db || !$db->getConnection()) {
        throw new Exception('Keine Verbindung zur Datenbank möglich');
    }
    
    echo '<div class="step success">✅ Datenbankverbindung erfolgreich</div>';
    
} catch (Exception $e) {
    echo '<div class="step error">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p>Bitte prüfe deine Datenbank-Zugangsdaten in <code>db-config.php</code></p>';
    exit;
}

// Schritt 4: Tabellen erstellen
echo '<div class="step info">🏗️ Erstelle Tabellen...</div>';

try {
    $result = $db->createTables();
    
    if ($result) {
        echo '<div class="step success">✅ Tabellen erfolgreich erstellt</div>';
        echo '<ul style="margin: 10px 0; padding-left: 20px;">
            <li><code>licenses</code> - Lizenzschlüssel</li>
            <li><code>pricing</code> - Preis-Konfiguration</li>
            <li><code>logs</code> - System-Logs</li>
            <li><code>config</code> - Server-Konfiguration</li>
        </ul>';
    } else {
        echo '<div class="step error">⚠️ Tabellen konnten nicht erstellt werden (möglicherweise existieren sie bereits)</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="step error">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Schritt 5: Pricing prüfen
echo '<div class="step info">💰 Prüfe Pricing-Daten...</div>';

try {
    $pricing = $db->getPricing();
    
    if (empty($pricing)) {
        echo '<div class="step error">⚠️ Keine Pricing-Daten vorhanden</div>';
        echo '<div class="step info">🔄 Füge Standard-Preise ein...</div>';
        
        // Standard-Preise manuell einfügen
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO pricing (package_type, price, currency, label, description, max_items, features) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                price = VALUES(price),
                label = VALUES(label),
                description = VALUES(description)
        ");
        
        $defaults = array(
            array('free', 0, '€', 'FREE', 'Perfekt zum Testen und für kleine Restaurants', 20, '[]'),
            array('free_plus', 15, '€', 'FREE+', 'Erweiterte Kapazität für mittelgroße Menüs', 60, '[]'),
            array('pro', 29, '€', 'PRO', 'Professionelle Lösung für umfangreiche Speisekarten', 200, '[]'),
            array('pro_plus', 49, '€', 'PRO+', 'PRO + Dark Mode + Warenkorb-System', 200, '["dark_mode","cart"]'),
            array('ultimate', 79, '€', 'ULTIMATE', 'Alle Features + unbegrenzte Gerichte', 900, '["dark_mode","cart","unlimited_items"]'),
        );
        
        foreach ($defaults as $data) {
            $stmt->execute($data);
        }
        
        echo '<div class="step success">✅ Standard-Preise eingefügt</div>';
        
    } else {
        echo '<div class="step success">✅ Pricing-Daten vorhanden (' . count($pricing) . ' Pakete)</div>';
        
        echo '<table style="width: 100%; margin: 10px 0; border-collapse: collapse;">
            <tr style="background: #f3f4f6; font-weight: 600;">
                <td style="padding: 8px; border: 1px solid #e5e7eb;">Paket</td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">Preis</td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">Gerichte</td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">Features</td>
            </tr>';
        
        foreach ($pricing as $type => $data) {
            echo '<tr>
                <td style="padding: 8px; border: 1px solid #e5e7eb;"><strong>' . htmlspecialchars($data['label']) . '</strong></td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($data['price']) . ' ' . htmlspecialchars($data['currency']) . '</td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">' . htmlspecialchars($data['max_items']) . '</td>
                <td style="padding: 8px; border: 1px solid #e5e7eb;">' . count($data['features']) . '</td>
            </tr>';
        }
        
        echo '</table>';
    }
    
} catch (Exception $e) {
    echo '<div class="step error">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Schritt 6: API testen
echo '<div class="step info">🌐 Teste API-Endpoint...</div>';

$api_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php?action=get_pricing';

echo '<p><strong>API URL:</strong> <code>' . htmlspecialchars($api_url) . '</code></p>';

// Test API-Call
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['pricing'])) {
        echo '<div class="step success">✅ API funktioniert! ' . count($data['pricing']) . ' Pakete verfügbar</div>';
    } else {
        echo '<div class="step error">⚠️ API antwortet, aber Daten sind fehlerhaft</div>';
        echo '<pre style="background: #1f2937; color: #fbbf24; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">' . htmlspecialchars($response) . '</pre>';
    }
} else {
    echo '<div class="step error">❌ API-Fehler (HTTP ' . $http_code . ')</div>';
}

// Abschluss
echo '<div class="step success" style="margin-top: 30px;">
    <h2 style="margin: 0 0 10px 0;">🎉 Setup abgeschlossen!</h2>
    <p style="margin: 0;">Die Datenbank wurde erfolgreich initialisiert.</p>
</div>';

echo '<div style="margin-top: 20px;">
    <a href="admin-panel.php" class="button">📊 Zum Admin-Panel</a>
    <a href="api.php?action=get_pricing" class="button" style="background: #10b981; margin-left: 10px;">🌐 API testen</a>
</div>';

echo '<div class="step info" style="margin-top: 20px;">
    <p><strong>⚠️ WICHTIG:</strong> Aus Sicherheitsgründen solltest du diese Datei nach dem Setup löschen oder umbenennen:</p>
    <pre style="background: #1f2937; color: #fbbf24; padding: 10px; border-radius: 4px; margin: 10px 0;">rm setup.php</pre>
</div>';

echo '</div></body></html>';
