<?php
// Einstellungen speichern
if (isset($_POST['save_settings']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $config = get_config();
    $config['timezone'] = sanitize_input($_POST['timezone']);
    $config['currency'] = sanitize_input($_POST['currency']);
    
    file_put_contents(DATA_DIR . '/config.json', json_encode($config, JSON_PRETTY_PRINT));
    $success = 'Einstellungen gespeichert!';
}

// Passwort ändern
if (isset($_POST['change_password']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 8) {
        $error = 'Passwort muss mindestens 8 Zeichen lang sein!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwörter stimmen nicht überein!';
    } else {
        $config = get_config();
        $config['admin']['password'] = password_hash($new_password, PASSWORD_BCRYPT);
        file_put_contents(DATA_DIR . '/config.json', json_encode($config, JSON_PRETTY_PRINT));
        $success = 'Passwort erfolgreich geändert!';
    }
}

$config = get_config();
?>

<div class="settings-page">
    <?php if (isset($success)) : ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <div class="alert error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Allgemeine Einstellungen -->
    <div class="card">
        <h2>⚙️ Allgemeine Einstellungen</h2>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Zeitzone</label>
                <select name="timezone">
                    <option value="Europe/Berlin" <?php echo ($config['timezone'] ?? 'Europe/Berlin') === 'Europe/Berlin' ? 'selected' : ''; ?>>Europe/Berlin</option>
                    <option value="Europe/Vienna" <?php echo ($config['timezone'] ?? '') === 'Europe/Vienna' ? 'selected' : ''; ?>>Europe/Vienna</option>
                    <option value="Europe/Zurich" <?php echo ($config['timezone'] ?? '') === 'Europe/Zurich' ? 'selected' : ''; ?>>Europe/Zurich</option>
                    <option value="UTC" <?php echo ($config['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Standard-Währung</label>
                <input type="text" name="currency" value="<?php echo htmlspecialchars($config['currency'] ?? '€'); ?>" maxlength="3">
            </div>
            
            <button type="submit" name="save_settings" class="btn-primary">💾 Speichern</button>
        </form>
    </div>
    
    <!-- Passwort ändern -->
    <div class="card">
        <h2>🔒 Passwort ändern</h2>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Neues Passwort</label>
                <input type="password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label>Passwort bestätigen</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            
            <button type="submit" name="change_password" class="btn-primary">🔒 Passwort ändern</button>
        </form>
    </div>
    
    <!-- System Info -->
    <div class="card info">
        <h3>📊 System Information</h3>
        <table class="info-table">
            <tr>
                <td><strong>PHP Version:</strong></td>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <td><strong>Server:</strong></td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt'; ?></td>
            </tr>
            <tr>
                <td><strong>API Key:</strong></td>
                <td><code><?php echo substr($config['api_key'] ?? '', 0, 16); ?>...</code></td>
            </tr>
            <tr>
                <td><strong>Admin:</strong></td>
                <td><?php echo htmlspecialchars($config['admin']['username'] ?? 'Unbekannt'); ?></td>
            </tr>
        </table>
    </div>
</div>
