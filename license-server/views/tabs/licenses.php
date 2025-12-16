<?php
// Lizenz hinzufügen/bearbeiten
if ((isset($_POST['add_license']) || isset($_POST['edit_license'])) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $key = strtoupper(sanitize_input($_POST['license_key']));
    $type = sanitize_input($_POST['type']);
    $domain = sanitize_input($_POST['domain']);
    $expires_option = sanitize_input($_POST['expires_option']);
    $max_items = intval($_POST['max_items']);
    
    // Ablaufdatum berechnen
    $expires = 'lifetime';
    if ($expires_option !== 'lifetime') {
        $now = new DateTime();
        
        switch ($expires_option) {
            case '7d':
                $now->modify('+7 days');
                break;
            case '31d':
                $now->modify('+31 days');
                break;
            case '6m':
                $now->modify('+6 months');
                break;
            case '12m':
                $now->modify('+12 months');
                break;
            case '24m':
                $now->modify('+24 months');
                break;
            case '36m':
                $now->modify('+36 months');
                break;
        }
        
        $expires = $now->format('Y-m-d');
    }
    
    // Features basierend auf Typ
    $features = [];
    if ($type === 'pro' || $type === 'pro_plus') {
        $features[] = 'unlimited_items';
    }
    if ($type === 'pro_plus') {
        $features[] = 'dark_mode';
    }
    
    $license_data = [
        'type' => $type,
        'domain' => $domain,
        'expires' => $expires,
        'max_items' => $max_items,
        'features' => $features,
    ];
    
    // Bestehendes Update oder neu
    $existing = get_license($key);
    if ($existing && isset($_POST['edit_license'])) {
        // Behalten: created_at
        $license_data['created_at'] = $existing['created_at'] ?? date('Y-m-d H:i:s');
        $license_data['updated_at'] = date('Y-m-d H:i:s');
        $success = 'Lizenz erfolgreich aktualisiert!';
    } else {
        $license_data['created_at'] = date('Y-m-d H:i:s');
        $success = 'Lizenz erfolgreich hinzugefügt!';
    }
    
    save_license($key, $license_data);
}

// Lizenz löschen
if (isset($_POST['delete_license']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $key = $_POST['license_key'];
    delete_license($key);
    $success = 'Lizenz gelöscht!';
}

// Edit-Mode
$edit_mode = false;
$edit_license = null;
if (isset($_GET['edit'])) {
    $edit_key = $_GET['edit'];
    $edit_license = get_license($edit_key);
    if ($edit_license) {
        $edit_mode = true;
        $edit_license['key'] = $edit_key;
    }
}

$licenses = get_licenses();
?>

<div class="licenses-page">
    <?php if (isset($success)) : ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Neue/Edit Lizenz -->
    <div class="card">
        <h2><?php echo $edit_mode ? '✏️ Lizenz bearbeiten' : '➕ Neue Lizenz erstellen'; ?></h2>
        
        <?php if ($edit_mode) : ?>
            <div class="alert info" style="background: #dbeafe; border-color: #3b82f6; color: #1e40af;">
                📝 Sie bearbeiten: <strong><?php echo htmlspecialchars($edit_license['key']); ?></strong>
                <a href="?tab=licenses" style="margin-left: 15px; color: #1e40af;">Abbrechen</a>
            </div>
        <?php endif; ?>
        
        <form method="post" class="license-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Lizenzschlüssel</label>
                    <div style="display: flex; gap: 10px;">
                        <input 
                            type="text" 
                            name="license_key" 
                            id="license_key" 
                            required 
                            placeholder="WPR-XXXXX-XXXXX-XXXXX"
                            value="<?php echo $edit_mode ? htmlspecialchars($edit_license['key']) : ''; ?>"
                            <?php echo $edit_mode ? 'readonly' : ''; ?>
                        >
                        <?php if (!$edit_mode) : ?>
                            <button type="button" onclick="generateKey()" class="btn-secondary">✨ Generieren</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Typ</label>
                    <select name="type" required id="type-select" onchange="updateMaxItems()">
                        <option value="free" <?php echo ($edit_mode && $edit_license['type'] === 'free') ? 'selected' : ''; ?>>FREE (max 20 Gerichte)</option>
                        <option value="pro" <?php echo ($edit_mode && $edit_license['type'] === 'pro') ? 'selected' : ''; ?>>PRO (Unbegrenzt)</option>
                        <option value="pro_plus" <?php echo ($edit_mode && $edit_license['type'] === 'pro_plus') ? 'selected' : ''; ?>>PRO+ (Unbegrenzt + Dark Mode)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Domain</label>
                    <input 
                        type="text" 
                        name="domain" 
                        required 
                        placeholder="beispiel.de oder * für alle"
                        value="<?php echo $edit_mode ? htmlspecialchars($edit_license['domain']) : ''; ?>"
                    >
                    <small style="color: #6b7280; font-size: 12px;">Tipp: * = Alle Domains erlaubt</small>
                </div>
                
                <div class="form-group">
                    <label>Ablaufdatum</label>
                    <select name="expires_option" required>
                        <option value="lifetime" <?php echo ($edit_mode && $edit_license['expires'] === 'lifetime') ? 'selected' : ''; ?>>∞ Lifetime (kein Ablauf)</option>
                        <option value="7d">📅 7 Tage</option>
                        <option value="31d">📅 31 Tage (1 Monat)</option>
                        <option value="6m">📅 6 Monate</option>
                        <option value="12m">📅 12 Monate (1 Jahr)</option>
                        <option value="24m">📅 24 Monate (2 Jahre)</option>
                        <option value="36m">📅 36 Monate (3 Jahre)</option>
                    </select>
                    <?php if ($edit_mode && $edit_license['expires'] !== 'lifetime') : ?>
                        <small style="color: #6b7280; font-size: 12px;">Aktuell: <?php echo htmlspecialchars($edit_license['expires']); ?></small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Max. Gerichte</label>
                    <input 
                        type="number" 
                        name="max_items" 
                        id="max-items" 
                        required 
                        min="1"
                        value="<?php echo $edit_mode ? $edit_license['max_items'] : 20; ?>"
                    >
                    <small style="color: #6b7280; font-size: 12px;">Standard: FREE=20, PRO/PRO+=999999</small>
                </div>
            </div>
            
            <?php if ($edit_mode) : ?>
                <button type="submit" name="edit_license" class="btn-primary">💾 Lizenz aktualisieren</button>
                <a href="?tab=licenses" class="btn-secondary" style="margin-left: 10px; display: inline-block; text-decoration: none; text-align: center;">❌ Abbrechen</a>
            <?php else : ?>
                <button type="submit" name="add_license" class="btn-primary">➕ Lizenz hinzufügen</button>
            <?php endif; ?>
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
                            <th>Features</th>
                            <th>Abläuft</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $key => $license) : ?>
                            <?php
                            $is_expired = isset($license['expires']) && $license['expires'] !== 'lifetime' && strtotime($license['expires']) < time();
                            $status_class = $is_expired ? 'status-expired' : 'status-active';
                            
                            // Features anzeigen
                            $features_str = '';
                            if (isset($license['features']) && !empty($license['features'])) {
                                $features_badges = [];
                                if (in_array('unlimited_items', $license['features'])) {
                                    $features_badges[] = '∞';
                                }
                                if (in_array('dark_mode', $license['features'])) {
                                    $features_badges[] = '🌙';
                                }
                                $features_str = implode(' ', $features_badges);
                            }
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                <td><span class="badge badge-<?php echo $license['type']; ?>"><?php echo strtoupper($license['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($license['domain']); ?></td>
                                <td><strong><?php echo number_format($license['max_items']); ?></strong></td>
                                <td><?php echo $features_str ?: '-'; ?></td>
                                <td>
                                    <?php if ($license['expires'] === 'lifetime') : ?>
                                        <span style="color: #10b981; font-weight: 600;">∞ Lifetime</span>
                                    <?php else : ?>
                                        <?php echo htmlspecialchars($license['expires']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $is_expired ? '❌ Abgelaufen' : '✅ Aktiv'; ?></span></td>
                                <td>
                                    <a href="?tab=licenses&edit=<?php echo urlencode($key); ?>" class="btn-edit" title="Bearbeiten">✏️</a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Lizenz wirklich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="license_key" value="<?php echo htmlspecialchars($key); ?>">
                                        <button type="submit" name="delete_license" class="btn-delete" title="Löschen">🗑️</button>
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

// Auto-update Max Items basierend auf Typ
function updateMaxItems() {
    const typeSelect = document.getElementById('type-select');
    const maxItemsInput = document.getElementById('max-items');
    const type = typeSelect.value;
    
    // Nur bei neuer Lizenz auto-setzen (nicht beim Edit)
    if (!<?php echo $edit_mode ? 'true' : 'false'; ?>) {
        if (type === 'free') {
            maxItemsInput.value = 20;
        } else if (type === 'pro' || type === 'pro_plus') {
            maxItemsInput.value = 999999;
        }
    }
}

// Initial setzen
window.addEventListener('DOMContentLoaded', function() {
    updateMaxItems();
});
</script>

<style>
.btn-edit {
    display: inline-block;
    padding: 6px 12px;
    background: #dbeafe;
    color: #1e40af;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    margin-right: 5px;
    transition: background 0.3s;
}

.btn-edit:hover {
    background: #bfdbfe;
}

.alert.info {
    background: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}
</style>
