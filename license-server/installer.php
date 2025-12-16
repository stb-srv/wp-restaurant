<?php
/**
 * License Server - One-Click Installer mit Datenbank-Setup
 */

define('LICENSE_SERVER', true);

// Prüfe ob bereits installiert
if (file_exists(__DIR__ . '/.installed')) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success_step1 = false;
$current_step = 1;

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $post_step = (int)$_POST['step'];
    
    if ($post_step === 1) {
        // ============================================
        // SCHRITT 1: DATENBANK VERBINDUNG
        // ============================================
        $db_host = trim($_POST['db_host'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = $_POST['db_pass'] ?? '';
        
        // Validierung
        if (empty($db_host)) {
            $errors[] = 'Datenbank-Host ist erforderlich';
        }
        if (empty($db_name)) {
            $errors[] = 'Datenbank-Name ist erforderlich';
        }
        if (empty($db_user)) {
            $errors[] = 'Datenbank-User ist erforderlich';
        }
        
        if (empty($errors)) {
            // DB-Config Datei erstellen
            $config_content = "<?php\n";
            $config_content .= "if (!defined('LICENSE_SERVER')) {\n";
            $config_content .= "    die('Direct access not allowed');\n";
            $config_content .= "}\n\n";
            $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
            $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
            
            if (!file_put_contents(__DIR__ . '/db-config.php', $config_content)) {
                $errors[] = 'Konnte db-config.php nicht erstellen. Prüfe Schreibrechte!';
            } else {
                // Verbindung testen
                try {
                    require_once __DIR__ . '/includes/database.php';
                    $db = LicenseDB::getInstance();
                    
                    if ($db->getConnection()) {
                        // Tabellen erstellen
                        if ($db->createTables()) {
                            $success_step1 = true;
                            $current_step = 2;
                        } else {
                            $errors[] = 'Konnte Tabellen nicht erstellen. Prüfe Datenbank-Rechte!';
                            @unlink(__DIR__ . '/db-config.php');
                        }
                    } else {
                        $errors[] = 'Verbindung zur Datenbank fehlgeschlagen!';
                        @unlink(__DIR__ . '/db-config.php');
                    }
                } catch (Exception $e) {
                    $errors[] = 'Datenbankfehler: ' . $e->getMessage();
                    @unlink(__DIR__ . '/db-config.php');
                }
            }
        }
        
    } elseif ($post_step === 2) {
        // ============================================
        // SCHRITT 2: ADMIN ACCOUNT
        // ============================================
        $admin_username = trim($_POST['admin_username'] ?? '');
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_email = trim($_POST['admin_email'] ?? '');
        
        // Validierung
        if (strlen($admin_username) < 3) {
            $errors[] = 'Admin-Username muss mindestens 3 Zeichen lang sein';
        }
        if (strlen($admin_password) < 8) {
            $errors[] = 'Admin-Passwort muss mindestens 8 Zeichen lang sein';
        }
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige E-Mail-Adresse';
        }
        
        if (empty($errors)) {
            try {
                require_once __DIR__ . '/includes/database.php';
                $db = LicenseDB::getInstance();
                
                // Admin-Config in DB speichern
                $admin_config = array(
                    'username' => $admin_username,
                    'password' => password_hash($admin_password, PASSWORD_BCRYPT),
                    'email' => $admin_email,
                );
                
                $db->setConfig('admin', $admin_config);
                
                // API Key generieren
                $api_key = bin2hex(random_bytes(32));
                $db->setConfig('api_key', $api_key);
                
                // Weitere Einstellungen
                $db->setConfig('timezone', 'Europe/Berlin');
                $db->setConfig('currency', '€');
                
                // Standard-Preise in DB
                $pricing = [
                    'free' => ['price' => 0, 'currency' => '€', 'label' => 'FREE'],
                    'pro' => ['price' => 29, 'currency' => '€', 'label' => 'PRO'],
                    'pro_plus' => ['price' => 49, 'currency' => '€', 'label' => 'PRO+'],
                ];
                $db->savePricing($pricing);
                
                // Installation abgeschlossen
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
                
                // Weiterleitung
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Fehler beim Speichern: ' . $e->getMessage();
            }
        }
        
        // Bei Fehlern zurück zu Schritt 2
        $current_step = 2;
    }
}

// Wenn Schritt 1 erfolgreich war, zeige Schritt 2
if ($success_step1) {
    $current_step = 2;
}

// Wenn db-config.php existiert, aber noch nicht installiert -> Schritt 2
if (file_exists(__DIR__ . '/db-config.php') && !file_exists(__DIR__ . '/.installed')) {
    $current_step = 2;
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
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6b7280;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.done {
            background: #10b981;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
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
            font-size: 14px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
            font-size: 14px;
        }
        .info {
            background: #f0f9ff;
            color: #0369a1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0ea5e9;
            font-size: 14px;
            line-height: 1.5;
        }
        .info strong { color: #075985; }
        .hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <h1>🚀 License Server</h1>
        <p class="subtitle">Installation in 2 einfachen Schritten</p>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $current_step === 1 ? 'active' : 'done'; ?>">1</div>
            <div class="step <?php echo $current_step === 2 ? 'active' : ''; ?>">2</div>
        </div>
        
        <!-- Fehler anzeigen -->
        <?php if (!empty($errors)) : ?>
            <div class="error">
                <?php foreach ($errors as $error) : ?>
                    ⚠️ <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Erfolg Schritt 1 -->
        <?php if ($success_step1) : ?>
            <div class="success">
                ✅ Datenbank erfolgreich verbunden! Tabellen wurden erstellt.
            </div>
        <?php endif; ?>
        
        <?php if ($current_step === 1) : ?>
            <!-- ============================================ -->
            <!-- SCHRITT 1: DATENBANK -->
            <!-- ============================================ -->
            <div class="info">
                <strong>📦 Schritt 1: Datenbank-Verbindung</strong><br>
                Erstelle zuerst eine MySQL-Datenbank in deinem Hosting-Panel (cPanel, Plesk, etc.).<br>
                Die Tabellen werden automatisch erstellt!
            </div>
            
            <form method="post">
                <input type="hidden" name="step" value="1">
                
                <div class="form-group">
                    <label>📡 Datenbank-Host</label>
                    <input 
                        type="text" 
                        name="db_host" 
                        required 
                        placeholder="localhost" 
                        value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>"
                    >
                    <div class="hint">Meist "localhost". Bei InfinityFree: "sql123.infinityfree.com"</div>
                </div>
                
                <div class="form-group">
                    <label>💾 Datenbank-Name</label>
                    <input 
                        type="text" 
                        name="db_name" 
                        required 
                        placeholder="license_server" 
                        value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>"
                    >
                    <div class="hint">Der Name deiner MySQL-Datenbank</div>
                </div>
                
                <div class="form-group">
                    <label>👤 Datenbank-Benutzername</label>
                    <input 
                        type="text" 
                        name="db_user" 
                        required 
                        placeholder="root" 
                        value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>"
                    >
                    <div class="hint">MySQL-User (nicht dein Admin-Name!)</div>
                </div>
                
                <div class="form-group">
                    <label>🔒 Datenbank-Passwort</label>
                    <input 
                        type="password" 
                        name="db_pass" 
                        placeholder="(optional für localhost)"
                    >
                    <div class="hint">MySQL-Passwort (kann bei localhost leer sein)</div>
                </div>
                
                <button type="submit" name="install">➡️ Verbindung testen & weiter</button>
            </form>
            
        <?php else : ?>
            <!-- ============================================ -->
            <!-- SCHRITT 2: ADMIN ACCOUNT -->
            <!-- ============================================ -->
            <div class="info">
                <strong>👤 Schritt 2: Admin-Account erstellen</strong><br>
                Jetzt erstellst du deinen Login für das License-Server Dashboard.<br>
                <strong>Wichtig:</strong> Dies ist NICHT die Datenbank-Login!
            </div>
            
            <form method="post">
                <input type="hidden" name="step" value="2">
                
                <div class="form-group">
                    <label>👤 Admin-Benutzername (fürs Dashboard)</label>
                    <input 
                        type="text" 
                        name="admin_username" 
                        required 
                        minlength="3" 
                        placeholder="admin" 
                        value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>"
                    >
                    <div class="hint">Mindestens 3 Zeichen</div>
                </div>
                
                <div class="form-group">
                    <label>🔒 Admin-Passwort (fürs Dashboard)</label>
                    <input 
                        type="password" 
                        name="admin_password" 
                        required 
                        minlength="8" 
                        placeholder="Mindestens 8 Zeichen"
                    >
                    <div class="hint">Mindestens 8 Zeichen - Wähle ein sicheres Passwort!</div>
                </div>
                
                <div class="form-group">
                    <label>✉️ E-Mail-Adresse</label>
                    <input 
                        type="email" 
                        name="admin_email" 
                        required 
                        placeholder="admin@deine-domain.com" 
                        value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>"
                    >
                    <div class="hint">Für Passwort-Wiederherstellung</div>
                </div>
                
                <button type="submit" name="install">✨ Installation abschließen</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
