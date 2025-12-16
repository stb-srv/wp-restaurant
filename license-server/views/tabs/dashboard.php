<?php
$pricing = get_pricing();
$licenses = get_licenses();
$config = get_config();
?>

<div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎫</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Gesamt Lizenzen</div>
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Aktive Lizenzen</div>
            </div>
        </div>
        
        <div class="stat-card red">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['expired']; ?></div>
                <div class="stat-label">Abgelaufene Lizenzen</div>
            </div>
        </div>
        
        <div class="stat-card blue">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($pricing); ?></div>
                <div class="stat-label">Preispakete</div>
            </div>
        </div>
    </div>
    
    <!-- Lizenz-Verteilung -->
    <div class="card">
        <h2>📊 Lizenz-Verteilung</h2>
        <div class="license-distribution">
            <div class="dist-item">
                <div class="dist-label">FREE</div>
                <div class="dist-bar">
                    <div class="dist-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['by_type']['free'] / $stats['total'] * 100) : 0; ?>%; background: #9ca3af;"></div>
                </div>
                <div class="dist-value"><?php echo $stats['by_type']['free']; ?></div>
            </div>
            
            <div class="dist-item">
                <div class="dist-label">PRO</div>
                <div class="dist-bar">
                    <div class="dist-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['by_type']['pro'] / $stats['total'] * 100) : 0; ?>%; background: #f59e0b;"></div>
                </div>
                <div class="dist-value"><?php echo $stats['by_type']['pro']; ?></div>
            </div>
            
            <div class="dist-item">
                <div class="dist-label">PRO+</div>
                <div class="dist-bar">
                    <div class="dist-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['by_type']['pro_plus'] / $stats['total'] * 100) : 0; ?>%; background: #fbbf24;"></div>
                </div>
                <div class="dist-value"><?php echo $stats['by_type']['pro_plus']; ?></div>
            </div>
        </div>
    </div>
    
    <!-- Aktuelle Preise -->
    <div class="card">
        <h2>💵 Aktuelle Preise</h2>
        <div class="pricing-preview">
            <?php foreach ($pricing as $type => $data) : ?>
                <div class="price-item">
                    <div class="price-label"><?php echo htmlspecialchars($data['label']); ?></div>
                    <div class="price-value"><?php echo htmlspecialchars($data['price']); ?><?php echo htmlspecialchars($data['currency']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quick Info -->
    <div class="card info">
        <h3>💡 Quick Info</h3>
        <p><strong>API Endpoint:</strong></p>
        <code><?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php'); ?></code>
        <p style="margin-top: 15px;"><strong>Installiert am:</strong> <?php echo file_exists(__DIR__ . '/../../.installed') ? file_get_contents(__DIR__ . '/../../.installed') : 'Unbekannt'; ?></p>
    </div>
</div>
