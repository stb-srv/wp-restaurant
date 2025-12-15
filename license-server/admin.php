<?php
/**
 * License Server Admin Panel
 * 
 * Aufruf: https://wp-stb-srv.infinityfree.me/license-server/admin.php
 * Standard-Passwort: admin2025 (BITTE ÄNDERN!)
 */

define('LICENSE_SERVER', true);
require_once __DIR__ . '/config.php';

session_start();

// Login-Check
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['login'])) {
        $password = $_POST['password'] ?? '';
        $hash = hash('sha256', $password);
        
        if ($hash === ADMIN_PASSWORD_HASH || $password === 'admin2025') {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $login_error = 'Falsches Passwort!';
        }
    }
    
    // Login-Formular
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            h1 { color: #1a202c; margin-bottom: 20px; }
            input {
                width: 100%;
                padding: 12px;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 16px;
                margin-bottom: 15px;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 12px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            button:hover {
                background: #5568d3;
            }
            .error {
                background: #fee2e2;
                color: #991b1b;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔐 Admin Login</h1>
            <?php if (isset($login_error)) : ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Passwort" required autofocus>
                <button type="submit" name="login">Anmelden</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$db = get_db_connection();

// Neue Lizenz erstellen
if (isset($_POST['create_license'])) {
    $key = 'WPR-' . strtoupper(substr(md5(time() . rand()), 0, 5)) . '-' . 
           strtoupper(substr(md5(time() . rand()), 0, 5)) . '-' . 
           strtoupper(substr(md5(time() . rand()), 0, 5));
    
    $stmt = $db->prepare("
        INSERT INTO licenses (license_key, email, customer_name, domains, max_items, active, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $key,
        $_POST['email'],
        $_POST['customer_name'],
        $_POST['domains'],
        (int)$_POST['max_items'],
        1,
        $_POST['expires_at']
    ]);
    
    $success = 'Lizenz erstellt: ' . $key;
}

// Lizenz löschen
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM licenses WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = 'Lizenz gelöscht!';
}

// Lizenz aktivieren/deaktivieren
if (isset($_GET['toggle'])) {
    $stmt = $db->prepare("UPDATE licenses SET active = NOT active WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    $success = 'Status geändert!';
}

// Lizenzen abrufen
$licenses = $db->query("SELECT * FROM licenses ORDER BY created_at DESC")->fetchAll();

// Statistiken
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active,
        SUM(check_count) as total_checks
    FROM licenses
")->fetch();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Server Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #1a202c; }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-label { color: #718096; margin-top: 5px; }
        .box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn:hover { opacity: 0.9; }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .active { color: #10b981; font-weight: 600; }
        .inactive { color: #ef4444; font-weight: 600; }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔑 License Server</h1>
            <a href="?logout" class="btn btn-danger">Abmelden</a>
        </header>
        
        <?php if (isset($success)) : ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Lizenzen gesamt</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Aktive Lizenzen</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo number_format($stats['total_checks']); ?></div>
                <div class="stat-label">API-Aufrufe</div>
            </div>
        </div>
        
        <div class="box">
            <h2>➕ Neue Lizenz erstellen</h2>
            <form method="post" style="margin-top: 20px;">
                <input type="email" name="email" placeholder="E-Mail" required>
                <input type="text" name="customer_name" placeholder="Kundenname" required>
                <input type="text" name="domains" placeholder="Domains (kommagetrennt, z.B. example.com,*.example.com)">
                <input type="number" name="max_items" value="999" placeholder="Max Gerichte">
                <input type="date" name="expires_at" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" placeholder="Ablaufdatum">
                <button type="submit" name="create_license" class="btn btn-success">Lizenz erstellen</button>
            </form>
        </div>
        
        <div class="box">
            <h2>📋 Alle Lizenzen</h2>
            <table>
                <thead>
                    <tr>
                        <th>Lizenzschlüssel</th>
                        <th>Kunde</th>
                        <th>Domains</th>
                        <th>Status</th>
                        <th>Ablauf</th>
                        <th>Checks</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licenses as $lic) : ?>
                        <tr>
                            <td><code><?php echo $lic['license_key']; ?></code></td>
                            <td><?php echo htmlspecialchars($lic['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($lic['domains'] ?: 'Alle'); ?></td>
                            <td class="<?php echo $lic['active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $lic['active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                            </td>
                            <td><?php echo $lic['expires_at'] ? date('d.m.Y', strtotime($lic['expires_at'])) : '∞'; ?></td>
                            <td><?php echo number_format($lic['check_count']); ?></td>
                            <td>
                                <a href="?toggle=<?php echo $lic['id']; ?>" class="btn btn-primary">Toggle</a>
                                <a href="?delete=<?php echo $lic['id']; ?>" class="btn btn-danger" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
