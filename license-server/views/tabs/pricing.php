<?php
// Preise speichern
if (isset($_POST['save_pricing']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $pricing = [
        'free' => [
            'price' => intval($_POST['free_price']),
            'currency' => sanitize_input($_POST['free_currency']),
            'label' => sanitize_input($_POST['free_label']),
        ],
        'pro' => [
            'price' => intval($_POST['pro_price']),
            'currency' => sanitize_input($_POST['pro_currency']),
            'label' => sanitize_input($_POST['pro_label']),
        ],
        'pro_plus' => [
            'price' => intval($_POST['pro_plus_price']),
            'currency' => sanitize_input($_POST['pro_plus_currency']),
            'label' => sanitize_input($_POST['pro_plus_label']),
        ],
    ];
    
    save_pricing($pricing);
    $success = 'Preise erfolgreich gespeichert!';
}

$pricing = get_pricing();
?>

<div class="pricing-page">
    <?php if (isset($success)) : ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h2>💰 Preisverwaltung</h2>
        <p style="margin-bottom: 20px; color: #6b7280;">Diese Preise werden automatisch an die WordPress-Plugins übertragen.</p>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="pricing-grid">
                <!-- FREE -->
                <div class="pricing-card">
                    <h3>FREE Paket</h3>
                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="free_label" value="<?php echo htmlspecialchars($pricing['free']['label']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis</label>
                        <input type="number" name="free_price" value="<?php echo htmlspecialchars($pricing['free']['price']); ?>" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Währung</label>
                        <input type="text" name="free_currency" value="<?php echo htmlspecialchars($pricing['free']['currency']); ?>" required maxlength="3">
                    </div>
                </div>
                
                <!-- PRO -->
                <div class="pricing-card pro">
                    <h3>PRO Paket</h3>
                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="pro_label" value="<?php echo htmlspecialchars($pricing['pro']['label']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis</label>
                        <input type="number" name="pro_price" value="<?php echo htmlspecialchars($pricing['pro']['price']); ?>" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Währung</label>
                        <input type="text" name="pro_currency" value="<?php echo htmlspecialchars($pricing['pro']['currency']); ?>" required maxlength="3">
                    </div>
                </div>
                
                <!-- PRO+ -->
                <div class="pricing-card pro-plus">
                    <h3>PRO+ Paket 🌟</h3>
                    <div class="form-group">
                        <label>Label</label>
                        <input type="text" name="pro_plus_label" value="<?php echo htmlspecialchars($pricing['pro_plus']['label']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preis</label>
                        <input type="number" name="pro_plus_price" value="<?php echo htmlspecialchars($pricing['pro_plus']['price']); ?>" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Währung</label>
                        <input type="text" name="pro_plus_currency" value="<?php echo htmlspecialchars($pricing['pro_plus']['currency']); ?>" required maxlength="3">
                    </div>
                </div>
            </div>
            
            <button type="submit" name="save_pricing" class="btn-primary">💾 Preise speichern</button>
        </form>
    </div>
</div>
