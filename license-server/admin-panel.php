<?php
/**
 * WP Restaurant Menu - License Server Admin Panel
 * Version 2.1 - Robust error handling
 */

// Error reporting für Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// KONFIGURATION
$ADMIN_PASSWORD = 'admin123'; // ÄNDERE DIES!

// Database laden mit Error Handling
try {
    define('LICENSE_SERVER', true);
    
    if (!file_exists(__DIR__ . '/includes/database.php')) {
        throw new Exception('Database class not found');
    }
    
    require_once __DIR__ . '/includes/database.php';
} catch (Exception $e) {
    die('<h1>Fehler</h1><p>Konnte Datenbank-Klasse nicht laden: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Login prüfen
if (isset($_POST['login'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = 'Falsches Passwort!';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Nicht eingeloggt? Login-Formular zeigen
if (!isset($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>License Server - Login</title>
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
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            h1 {
                margin: 0 0 30px 0;
                text-align: center;
                color: #1f2937;
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
            .error {
                background: #fee2e2;
                color: #991b1b;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔑 License Server</h1>
            <?php if (isset($error)) : ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Admin-Passwort" required autofocus>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Database-Instanz mit Error Handling
try {
    $db = LicenseDB::getInstance();
    
    if (!$db || !$db->getConnection()) {
        throw new Exception('Konnte keine Verbindung zur Datenbank herstellen');
    }
    
    // Tabellen erstellen falls nicht vorhanden
    $db->createTables();
    
} catch (Exception $e) {
    die('<h1>Datenbankfehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="?logout">Logout</a></p>');
}

// Preise speichern
if (isset($_POST['save_pricing'])) {
    try {
        $pricing = array(
            'free' => array(
                'price' => intval($_POST['free_price'] ?? 0),
                'currency' => sanitize_text($_POST['free_currency'] ?? '€'),
                'label' => sanitize_text($_POST['free_label'] ?? 'FREE'),
                'description' => sanitize_text($_POST['free_description'] ?? ''),
                'max_items' => 20,
                'features' => array(),
            ),
            'free_plus' => array(
                'price' => intval($_POST['free_plus_price'] ?? 15),
                'currency' => sanitize_text($_POST['free_plus_currency'] ?? '€'),
                'label' => sanitize_text($_POST['free_plus_label'] ?? 'FREE+'),
                'description' => sanitize_text($_POST['free_plus_description'] ?? ''),
                'max_items' => 60,
                'features' => array(),
            ),
            'pro' => array(
                'price' => intval($_POST['pro_price'] ?? 29),
                'currency' => sanitize_text($_POST['pro_currency'] ?? '€'),
                'label' => sanitize_text($_POST['pro_label'] ?? 'PRO'),
                'description' => sanitize_text($_POST['pro_description'] ?? ''),
                'max_items' => 200,
                'features' => array(),
            ),
            'pro_plus' => array(
                'price' => intval($_POST['pro_plus_price'] ?? 49),
                'currency' => sanitize_text($_POST['pro_plus_currency'] ?? '€'),
                'label' => sanitize_text($_POST['pro_plus_label'] ?? 'PRO+'),
                'description' => sanitize_text($_POST['pro_plus_description'] ?? ''),
                'max_items' => 200,
                'features' => array('dark_mode', 'cart'),
            ),
            'ultimate' => array(
                'price' => intval($_POST['ultimate_price'] ?? 79),
                'currency' => sanitize_text($_POST['ultimate_currency'] ?? '€'),
                'label' => sanitize_text($_POST['ultimate_label'] ?? 'ULTIMATE'),
                'description' => sanitize_text($_POST['ultimate_description'] ?? ''),
                'max_items' => 900,
                'features' => array('dark_mode', 'cart', 'unlimited_items'),
            ),
        );
        
        if ($db->savePricing($pricing)) {
            $success = 'Preise und Beschreibungen erfolgreich gespeichert!';
        } else {
            $error_save = 'Fehler beim Speichern!';
        }
    } catch (Exception $e) {
        $error_save = 'Fehler: ' . $e->getMessage();
    }
}

// Aktuelle Preise laden
try {
    $pricing = $db->getPricing();
    if (empty($pricing)) {
        // Fallback mit Standard-Werten
        $pricing = array(
            'free' => array('price' => 0, 'currency' => '€', 'label' => 'FREE', 'description' => '', 'max_items' => 20, 'features' => array()),
            'free_plus' => array('price' => 15, 'currency' => '€', 'label' => 'FREE+', 'description' => '', 'max_items' => 60, 'features' => array()),
            'pro' => array('price' => 29, 'currency' => '€', 'label' => 'PRO', 'description' => '', 'max_items' => 200, 'features' => array()),
            'pro_plus' => array('price' => 49, 'currency' => '€', 'label' => 'PRO+', 'description' => '', 'max_items' => 200, 'features' => array('dark_mode', 'cart')),
            'ultimate' => array('price' => 79, 'currency' => '€', 'label' => 'ULTIMATE', 'description' => '', 'max_items' => 900, 'features' => array('dark_mode', 'cart', 'unlimited_items')),
        );
    }
} catch (Exception $e) {
    $error_load = 'Fehler beim Laden: ' . $e->getMessage();
    $pricing = array();
}

function sanitize_text($text) {
    return htmlspecialchars(strip_tags(trim($text)), ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>License Server - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
        }
        .logout {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .logout:hover {
            background: #dc2626;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .package {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: #fafafa;
        }
        .package.new {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .package h3 {
            margin-bottom: 15px;
            color: #374151;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .package.new h3 {
            color: #047857;
        }
        .form-group {
            margin-bottom: 12px;
        }
        label {
            display: block;
            margin-bottom: 4px;
            color: #374151;
            font-weight: 500;
            font-size: 13px;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
        }
        textarea {
            min-height: 60px;
            resize: vertical;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover {
            opacity: 0.9;
        }
        .success {
            background: #d1fae5;
            color: #047857;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        .info-box {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #0ea5e9;
            margin-top: 20px;
        }
        .info-box h3 {
            color: #0369a1;
            margin-bottom: 10px;
        }
        code {
            background: #1f2937;
            color: #fbbf24;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 License Server - Admin Panel</h1>
            <a href="?logout" class="logout">Logout</a>
        </div>
        
        <?php if (isset($success)) : ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_save)) : ?>
            <div class="error">❌ <?php echo htmlspecialchars($error_save); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_load)) : ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error_load); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>💰 Preis- & Beschreibungsverwaltung</h2>
            <p style="color: #6b7280; margin-bottom: 20px;">Bearbeiten Sie Preise und Beschreibungen für alle Lizenzmodelle. Änderungen werden sofort an alle Plugins übertragen.</p>
            
            <?php if (empty($pricing)) : ?>
                <div class="error">Keine Preis-Daten gefunden. Bitte prüfen Sie die Datenbank-Konfiguration.</div>
            <?php else : ?>
                <form method="post">
                    <div class="grid">
                        <!-- FREE -->
                        <div class="package">
                            <h3>FREE Paket</h3>
                            <div class="form-group">
                                <label>Label</label>
                                <input type="text" name="free_label" value="<?php echo htmlspecialchars($pricing['free']['label'] ?? 'FREE'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preis</label>
                                <input type="number" name="free_price" value="<?php echo htmlspecialchars($pricing['free']['price'] ?? 0); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Währung</label>
                                <input type="text" name="free_currency" value="<?php echo htmlspecialchars($pricing['free']['currency'] ?? '€'); ?>" required maxlength="3">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung</label>
                                <textarea name="free_description" rows="3"><?php echo htmlspecialchars($pricing['free']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- FREE+ -->
                        <div class="package new">
                            <h3>FREE+ Paket <span class="badge">NEU</span></h3>
                            <div class="form-group">
                                <label>Label</label>
                                <input type="text" name="free_plus_label" value="<?php echo htmlspecialchars($pricing['free_plus']['label'] ?? 'FREE+'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preis</label>
                                <input type="number" name="free_plus_price" value="<?php echo htmlspecialchars($pricing['free_plus']['price'] ?? 15); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Währung</label>
                                <input type="text" name="free_plus_currency" value="<?php echo htmlspecialchars($pricing['free_plus']['currency'] ?? '€'); ?>" required maxlength="3">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung</label>
                                <textarea name="free_plus_description" rows="3"><?php echo htmlspecialchars($pricing['free_plus']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- PRO -->
                        <div class="package">
                            <h3>PRO Paket</h3>
                            <div class="form-group">
                                <label>Label</label>
                                <input type="text" name="pro_label" value="<?php echo htmlspecialchars($pricing['pro']['label'] ?? 'PRO'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preis</label>
                                <input type="number" name="pro_price" value="<?php echo htmlspecialchars($pricing['pro']['price'] ?? 29); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Währung</label>
                                <input type="text" name="pro_currency" value="<?php echo htmlspecialchars($pricing['pro']['currency'] ?? '€'); ?>" required maxlength="3">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung</label>
                                <textarea name="pro_description" rows="3"><?php echo htmlspecialchars($pricing['pro']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- PRO+ -->
                        <div class="package">
                            <h3>PRO+ Paket</h3>
                            <div class="form-group">
                                <label>Label</label>
                                <input type="text" name="pro_plus_label" value="<?php echo htmlspecialchars($pricing['pro_plus']['label'] ?? 'PRO+'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preis</label>
                                <input type="number" name="pro_plus_price" value="<?php echo htmlspecialchars($pricing['pro_plus']['price'] ?? 49); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Währung</label>
                                <input type="text" name="pro_plus_currency" value="<?php echo htmlspecialchars($pricing['pro_plus']['currency'] ?? '€'); ?>" required maxlength="3">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung</label>
                                <textarea name="pro_plus_description" rows="3"><?php echo htmlspecialchars($pricing['pro_plus']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- ULTIMATE -->
                        <div class="package new">
                            <h3>ULTIMATE Paket <span class="badge">NEU</span></h3>
                            <div class="form-group">
                                <label>Label</label>
                                <input type="text" name="ultimate_label" value="<?php echo htmlspecialchars($pricing['ultimate']['label'] ?? 'ULTIMATE'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preis</label>
                                <input type="number" name="ultimate_price" value="<?php echo htmlspecialchars($pricing['ultimate']['price'] ?? 79); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Währung</label>
                                <input type="text" name="ultimate_currency" value="<?php echo htmlspecialchars($pricing['ultimate']['currency'] ?? '€'); ?>" required maxlength="3">
                            </div>
                            <div class="form-group">
                                <label>Beschreibung</label>
                                <textarea name="ultimate_description" rows="3"><?php echo htmlspecialchars($pricing['ultimate']['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="save_pricing">💾 Speichern & Synchronisieren</button>
                </form>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>💡 API Endpoint</h3>
                <p>Die Preise werden automatisch über die API bereitgestellt:</p>
                <p style="margin-top: 10px;"><code><?php echo htmlspecialchars('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['PHP_SELF']) . '/api.php?action=get_pricing'); ?></code></p>
                <p style="margin-top: 15px; color: #374151;">Diese URL muss im WordPress-Plugin als "Lizenz-Server URL" eingetragen werden.</p>
            </div>
        </div>
    </div>
</body>
</html>