<?php
/**
 * License Server - One-Click Installer
 */

define('LICENSE_SERVER', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    // Daten sammeln
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];
    $admin_email = trim($_POST['admin_email']);
    
    $errors = [];
    
    // Validierung
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
        // Verzeichnisse erstellen
        @mkdir(__DIR__ . '/data', 0755, true);
        @mkdir(__DIR__ . '/logs', 0755, true);
        
        // API Key generieren
        $api_key = bin2hex(random_bytes(32));
        
        // Config erstellen
        $config = [
            'admin' => [
                'username' => $admin_username,
                'password' => password_hash($admin_password, PASSWORD_BCRYPT),
                'email' => $admin_email,
            ],
            'api_key' => $api_key,
            'timezone' => 'Europe/Berlin',
            'currency' => '€',
        ];
        
        file_put_contents(__DIR__ . '/data/config.json', json_encode($config, JSON_PRETTY_PRINT));
        
        // Standard-Preise erstellen
        $pricing = [
            'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
            'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
            'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
        ];
        
        file_put_contents(__DIR__ . '/data/pricing.json', json_encode($pricing, JSON_PRETTY_PRINT));
        
        // Lizenzen-Datei erstellen
        file_put_contents(__DIR__ . '/data/licenses.json', json_encode([], JSON_PRETTY_PRINT));
        
        // .htaccess für Sicherheit
        $htaccess = "# Security\n";
        $htaccess .= "<FilesMatch \"\\.(json|log)$\">\n";
        $htaccess .= "    Order allow,deny\n";
        $htaccess .= "    Deny from all\n";
        $htaccess .= "</FilesMatch>\n";
        
        file_put_contents(__DIR__ . '/data/.htaccess', $htaccess);
        file_put_contents(__DIR__ . '/logs/.htaccess', $htaccess);
        
        // Installation abgeschlossen
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        // Weiterleitung
        header('Location: index.php');
        exit;
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
            max-width: 500px;
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
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
    </style>
</head>
<body>
    <div class="installer">
        <h1>🚀 License Server</h1>
        <p class="subtitle">Willkommen! Richten Sie Ihren Lizenz-Server ein.</p>
        
        <div class="info">
            <strong>💡 Quick Setup:</strong><br>
            1. Wählen Sie einen Admin-Username<br>
            2. Sicheres Passwort (min. 8 Zeichen)<br>
            3. Ihre E-Mail-Adresse<br>
            4. Fertig! 🎉
        </div>
        
        <?php if (!empty($errors)) : ?>
            <div class="error">
                <?php foreach ($errors as $error) : ?>
                    ⚠️ <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>👤 Admin Username</label>
                <input type="text" name="admin_username" required minlength="3" placeholder="z.B. admin" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>🔒 Admin Passwort</label>
                <input type="password" name="admin_password" required minlength="8" placeholder="Mindestens 8 Zeichen">
            </div>
            
            <div class="form-group">
                <label>✉️ E-Mail</label>
                <input type="email" name="admin_email" required placeholder="admin@ihre-domain.com" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>">
            </div>
            
            <button type="submit" name="install">✨ Server installieren</button>
        </form>
    </div>
</body>
</html>
