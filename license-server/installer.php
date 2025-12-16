<?php
/**
 * License Server - One-Click Installer mit MySQL Datenbank
 */

define('LICENSE_SERVER', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // Daten sammeln
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];
    $admin_email = trim($_POST['admin_email']);
    
    $errors = [];
    
    // Validierung
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $errors[] = 'Alle Datenbank-Felder sind erforderlich';
    }
    if (strlen($admin_username) < 3) {
        $errors[] = 'Username muss mindestens 3 Zeichen lang sein';
    }
    if (strlen($admin_password) < 8) {
        $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein';
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ungültige E-Mail-Adresse';
    }
    
    if (empty($errors)) {
        // Datenbank-Verbindung testen
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;charset=utf8mb4",
                $db_user,
                $db_pass,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            
            // Datenbank erstellen falls nicht existiert
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Tabellen erstellen
            
            // 1. Config-Tabelle
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `config` (
                    `key` VARCHAR(100) PRIMARY KEY,
                    `value` TEXT NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // 2. Lizenzen-Tabelle
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `licenses` (
                    `key` VARCHAR(50) PRIMARY KEY,
                    `type` VARCHAR(20) NOT NULL,
                    `domain` VARCHAR(255) NOT NULL,
                    `max_items` INT NOT NULL DEFAULT 20,
                    `expires` VARCHAR(20) NOT NULL DEFAULT 'lifetime',
                    `features` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_domain` (`domain`),
                    INDEX `idx_type` (`type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // 3. Preise-Tabelle
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `pricing` (
                    `type` VARCHAR(20) PRIMARY KEY,
                    `price` DECIMAL(10,2) NOT NULL,
                    `currency` VARCHAR(10) NOT NULL,
                    `label` VARCHAR(50) NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // 4. Logs-Tabelle
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `type` VARCHAR(20) NOT NULL,
                    `message` TEXT NOT NULL,
                    `ip` VARCHAR(50),
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_type` (`type`),
                    INDEX `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // 5. Rate Limiting Tabelle
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `rate_limits` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `identifier` VARCHAR(100) NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_identifier` (`identifier`),
                    INDEX `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // API Key generieren
            $api_key = bin2hex(random_bytes(32));
            
            // Config-Daten einfügen
            $stmt = $pdo->prepare("INSERT INTO config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['admin_username', $admin_username]);
            $stmt->execute(['admin_password', password_hash($admin_password, PASSWORD_BCRYPT)]);
            $stmt->execute(['admin_email', $admin_email]);
            $stmt->execute(['api_key', $api_key]);
            $stmt->execute(['timezone', 'Europe/Berlin']);
            $stmt->execute(['currency', '€']);
            
            // Standard-Preise einfügen
            $stmt = $pdo->prepare("INSERT INTO pricing (type, price, currency, label) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), currency = VALUES(currency), label = VALUES(label)");
            $stmt->execute(['free', 0, '€', 'FREE']);
            $stmt->execute(['pro', 29, '€', 'PRO']);
            $stmt->execute(['pro_plus', 49, '€', 'PRO+']);
            
            // DB-Config-Datei erstellen
            $db_config = "<?php\n";
            $db_config .= "// Database Configuration - Auto-generated\n";
            $db_config .= "define('DB_HOST', " . var_export($db_host, true) . ");\n";
            $db_config .= "define('DB_NAME', " . var_export($db_name, true) . ");\n";
            $db_config .= "define('DB_USER', " . var_export($db_user, true) . ");\n";
            $db_config .= "define('DB_PASS', " . var_export($db_pass, true) . ");\n";
            $db_config .= "define('DB_CHARSET', 'utf8mb4');\n";
            
            @mkdir(__DIR__ . '/data', 0755, true);
            file_put_contents(__DIR__ . '/data/db-config.php', $db_config);
            
            // Verzeichnisse erstellen
            @mkdir(__DIR__ . '/logs', 0755, true);
            
            // .htaccess für Sicherheit
            $htaccess = "# Security\n";
            $htaccess .= "<FilesMatch \"\\.(php|json)$\">\n";
            $htaccess .= "    Order allow,deny\n";
            $htaccess .= "    Deny from all\n";
            $htaccess .= "</FilesMatch>\n";
            
            file_put_contents(__DIR__ . '/data/.htaccess', $htaccess);
            
            // Installation abgeschlossen
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
            
            // Weiterleitung
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Datenbankfehler: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Server - Installation</title>
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
        .installer {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .section:last-child { border-bottom: none; }
        .section h2 {
            font-size: 18px;
            color: #374151;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #f0f9ff;
            color: #0369a1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0ea5e9;
            font-size: 14px;
        }
        .info strong { color: #075985; }
        small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <h1>🚀 License Server v2.0</h1>
        <p class="subtitle">MySQL-Datenbank Installation</p>
        
        <div class="info">
            <strong>📊 MySQL-Vorteile:</strong><br>
            • Keine Datenverluste bei Updates<br>
            • Schneller & zuverlässiger<br>
            • Automatische Backups möglich<br>
            • Professionelle Lösung
        </div>
        
        <?php if (!empty($errors)) : ?>
            <div class="error">
                <?php foreach ($errors as $error) : ?>
                    ⚠️ <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <!-- Datenbank-Konfiguration -->
            <div class="section">
                <h2>📦 1. MySQL Datenbank</h2>
                
                <div class="form-group">
                    <label>Datenbank-Host</label>
                    <input type="text" name="db_host" required placeholder="localhost oder sql113.infinityfree.com" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>">
                    <small>Meist: localhost (bei lokalem Server) oder vom Hoster bereitgestellt</small>
                </div>
                
                <div class="form-group">
                    <label>Datenbank-Name</label>
                    <input type="text" name="db_name" required placeholder="license_server" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>">
                    <small>Name der Datenbank (wird erstellt falls nicht existiert)</small>
                </div>
                
                <div class="form-group">
                    <label>Datenbank-Benutzer</label>
                    <input type="text" name="db_user" required placeholder="root" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Datenbank-Passwort</label>
                    <input type="password" name="db_pass" placeholder="(optional bei localhost)">
                </div>
            </div>
            
            <!-- Admin-Account -->
            <div class="section">
                <h2>👤 2. Admin-Account</h2>
                
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="admin_username" required minlength="3" placeholder="admin" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Admin Passwort</label>
                    <input type="password" name="admin_password" required minlength="8" placeholder="Mindestens 8 Zeichen">
                </div>
                
                <div class="form-group">
                    <label>E-Mail</label>
                    <input type="email" name="admin_email" required placeholder="admin@ihre-domain.com" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" name="install">✨ Jetzt installieren</button>
        </form>
    </div>
</body>
</html>
