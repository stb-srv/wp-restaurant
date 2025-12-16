<?php
// Lizenz hinzufügen
if (isset($_POST['add_license']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $key = strtoupper(sanitize_input($_POST['license_key']));
    $type = sanitize_input($_POST['type']);
    $domain = sanitize_input($_POST['domain']);
    $expires = sanitize_input($_POST['expires']);
    $max_items = intval($_POST['max_items']);
    
    $features = [];
    if ($type === 'pro' || $type === 'pro_plus') {
        $features[] = 'unlimited_items';
    }
    if ($type === 'pro_plus') {
        $features[] = 'dark_mode';
    }
    
    save_license($key, [
        'type' => $type,
        'domain' => $domain,
        'expires' => $expires,
        'max_items' => $max_items,
        'features' => $features,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    $success = 'Lizenz erfolgreich hinzugefügt!';
}

// Lizenz löschen
if (isset($_POST['delete_license']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $key = $_POST['license_key'];
    delete_license($key);
    $success = 'Lizenz gelöscht!';
}

$licenses = get_licenses();
?>

<div class="licenses-page">
    <?php if (isset($success)) : ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Neue Lizenz -->
    <div class="card">
        <h2>➕ Neue Lizenz erstellen</h2>
        <form method="post" class="license-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Lizenzschlüssel</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="license_key" id="license_key" required placeholder="WPR-XXXXX-XXXXX-XXXXX">
                        <button type="button" onclick="generateKey()" class="btn-secondary">Generieren</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Typ</label>
                    <select name="type" required>
                        <option value="free">FREE</option>
                        <option value="pro">PRO</option>
                        <option value="pro_plus">PRO+</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Domain</label>
                    <input type="text" name="domain" required placeholder="beispiel.de oder * für alle">
                </div>
                
                <div class="form-group">
                    <label>Ablaufdatum</label>
                    <input type="text" name="expires" required placeholder="lifetime oder YYYY-MM-DD" value="lifetime">
                </div>
                
                <div class="form-group">
                    <label>Max. Gerichte</label>
                    <input type="number" name="max_items" required value="999999">
                </div>
            </div>
            
            <button type="submit" name="add_license" class="btn-primary">➕ Lizenz hinzufügen</button>
        </form>
    </div>
    
    <!-- Lizenz-Liste -->
    <div class="card">
        <h2>📊 Alle Lizenzen (<?php echo count($licenses); ?>)</h2>
        
        <?php if (empty($licenses)) : ?>
            <p class="empty-state">Noch keine Lizenzen vorhanden. Erstellen Sie die erste Lizenz oben!</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="licenses-table">
                    <thead>
                        <tr>
                            <th>Schlüssel</th>
                            <th>Typ</th>
                            <th>Domain</th>
                            <th>Max. Items</th>
                            <th>Abläuft</th>
                            <th>Status</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $key => $license) : ?>
                            <?php
                            $is_expired = isset($license['expires']) && $license['expires'] !== 'lifetime' && strtotime($license['expires']) < time();
                            $status_class = $is_expired ? 'status-expired' : 'status-active';
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                <td><span class="badge badge-<?php echo $license['type']; ?>"><?php echo strtoupper($license['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($license['domain']); ?></td>
                                <td><?php echo $license['max_items']; ?></td>
                                <td><?php echo $license['expires'] === 'lifetime' ? '∞ Lifetime' : htmlspecialchars($license['expires']); ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $is_expired ? '❌ Abgelaufen' : '✅ Aktiv'; ?></span></td>
                                <td>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Lizenz wirklich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="license_key" value="<?php echo htmlspecialchars($key); ?>">
                                        <button type="submit" name="delete_license" class="btn-delete">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function generateKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let key = 'WPR';
    
    for (let i = 0; i < 4; i++) {
        key += '-';
        for (let j = 0; j < 5; j++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
    }
    
    document.getElementById('license_key').value = key;
}
</script>
