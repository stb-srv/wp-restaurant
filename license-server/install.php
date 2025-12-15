<?php
/**
 * License Server Installation
 * 
 * Aufruf: https://wp-stb-srv.infinityfree.me/license-server/install.php
 * 
 * WICHTIG: Nach erfolgreicher Installation diese Datei LÖSCHEN!
 */

define('LICENSE_SERVER', true);
require_once __DIR__ . '/config.php';

// Sicherheitscheck
if (file_exists(__DIR__ . '/.installed')) {
    die('<h1>❌ Installation bereits durchgeführt!</h1><p>Bitte löschen Sie install.php aus Sicherheitsgründen.</p>');
}

$db = get_db_connection();
if (!$db) {
    die('<h1>❌ Datenbankverbindung fehlgeschlagen!</h1><p>Bitte prüfen Sie die Zugangsdaten in config.php</p>');
}

$errors = [];
$success = [];

try {
    // 1. Licenses Tabelle
    $sql = "CREATE TABLE IF NOT EXISTS licenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        license_key VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100),
        customer_name VARCHAR(100),
        domains TEXT,
        max_items INT DEFAULT 999,
        active BOOLEAN DEFAULT TRUE,
        expires_at DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_checked TIMESTAMP NULL,
        check_count INT DEFAULT 0,
        INDEX idx_license_key (license_key),
        INDEX idx_active (active),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    $success[] = '✅ Tabelle "licenses" erstellt';
    
    // 2. Rate Limits Tabelle
    $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier (identifier),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    $success[] = '✅ Tabelle "rate_limits" erstellt';
    
    // 3. Access Logs Tabelle
    $sql = "CREATE TABLE IF NOT EXISTS access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        license_key VARCHAR(50),
        domain VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_license (license_key),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    $success[] = '✅ Tabelle "access_logs" erstellt';
    
    // 4. Test-Lizenz erstellen
    $test_key = 'WPR-TEST-' . strtoupper(substr(md5(time()), 0, 12));
    $stmt = $db->prepare("
        INSERT INTO licenses (license_key, email, customer_name, domains, max_items, active, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $test_key,
        'test@example.com',
        'Test Customer',
        'localhost,127.0.0.1',
        999,
        1,
        date('Y-m-d', strtotime('+1 year'))
    ]);
    $success[] = '✅ Test-Lizenz erstellt: <code>' . $test_key . '</code>';
    
    // 5. .installed Marker erstellen
    file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
    $success[] = '✅ Installation abgeschlossen';
    
} catch (PDOException $e) {
    $errors[] = '❌ Fehler: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Server Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .success {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #166534;
        }
        .error {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .info {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }
        ul {
            list-style: none;
            margin: 0;
        }
        li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        li:last-child {
            border-bottom: none;
        }
        code {
            background: rgba(0,0,0,0.05);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.2s;
        }
        .button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 License Server Installation</h1>
        <p class="subtitle">WP Restaurant Menu - License Server</p>
        
        <?php if (!empty($success)) : ?>
            <div class="box success">
                <ul>
                    <?php foreach ($success as $msg) : ?>
                        <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)) : ?>
            <div class="box error">
                <ul>
                    <?php foreach ($errors as $msg) : ?>
                        <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($errors)) : ?>
            <div class="box info">
                <strong>📋 Nächste Schritte:</strong>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>✅ Datenbank erfolgreich eingerichtet</li>
                    <li>🔑 Test-Lizenz erstellt und bereit</li>
                    <li>⚠️ <strong>WICHTIG:</strong> Löschen Sie <code>install.php</code> aus Sicherheitsgründen!</li>
                </ol>
            </div>
            
            <div class="box warning">
                <strong>⚠️ Sicherheitshinweise:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Löschen Sie <code>install.php</code></li>
                    <li>Ändern Sie das Admin-Passwort in <code>config.php</code></li>
                    <li>Schützen Sie den <code>/license-server</code> Ordner mit .htaccess</li>
                </ul>
            </div>
            
            <a href="admin.php" class="button">→ Zum Admin-Panel</a>
            <a href="test.php" class="button" style="background: #10b981;">🧪 API Testen</a>
        <?php else : ?>
            <div class="box warning">
                <strong>Bitte beheben Sie die Fehler und laden Sie die Seite neu.</strong>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
