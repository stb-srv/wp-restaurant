<?php
$api_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php';
$config = get_config();
$api_key = $config['api_key'] ?? 'NICHT_KONFIGURIERT';
?>

<div class="api-page">
    <div class="card">
        <h2>🔌 API Dokumentation</h2>
        
        <div class="api-section">
            <h3>Endpoint URL</h3>
            <code class="code-block"><?php echo htmlspecialchars($api_url); ?></code>
        </div>
        
        <div class="api-section">
            <h3>1. Preise abrufen</h3>
            <p><strong>Endpoint:</strong></p>
            <code class="code-block">GET <?php echo htmlspecialchars($api_url); ?>?action=get_pricing</code>
            
            <p><strong>Response:</strong></p>
            <pre class="code-block">{
  "success": true,
  "pricing": {
    "free": {
      "price": 0,
      "currency": "€",
      "label": "FREE"
    },
    "pro": {
      "price": 29,
      "currency": "€",
      "label": "PRO"
    },
    "pro_plus": {
      "price": 49,
      "currency": "€",
      "label": "PRO+"
    }
  }
}</pre>
        </div>
        
        <div class="api-section">
            <h3>2. Lizenz prüfen</h3>
            <p><strong>Endpoint:</strong></p>
            <code class="code-block">GET <?php echo htmlspecialchars($api_url); ?>?action=check_license&key=WPR-XXX&domain=beispiel.de</code>
            
            <p><strong>Parameter:</strong></p>
            <ul>
                <li><code>key</code> - Lizenzschlüssel</li>
                <li><code>domain</code> - Domain des Kunden</li>
            </ul>
            
            <p><strong>Response (Gültig):</strong></p>
            <pre class="code-block">{
  "success": true,
  "valid": true,
  "type": "pro_plus",
  "max_items": 999999,
  "expires": "lifetime",
  "features": ["unlimited_items", "dark_mode"]
}</pre>
            
            <p><strong>Response (Ungültig):</strong></p>
            <pre class="code-block">{
  "success": false,
  "valid": false,
  "message": "License not found"
}</pre>
        </div>
        
        <div class="api-section">
            <h3>3. WordPress Plugin Konfiguration</h3>
            <p>Trage diese URL im WordPress-Plugin unter "Lizenz" → "Lizenz-Server URL" ein:</p>
            <code class="code-block"><?php echo htmlspecialchars($api_url); ?></code>
        </div>
    </div>
    
    <!-- API Tester -->
    <div class="card">
        <h2>🧪 API Tester</h2>
        <p>Teste die API-Endpunkte direkt hier:</p>
        
        <div style="margin-top: 20px;">
            <button onclick="testPricing()" class="btn-primary">Preise testen</button>
            <button onclick="testLicense()" class="btn-secondary" style="margin-left: 10px;">Lizenz testen</button>
        </div>
        
        <div id="test-result" style="margin-top: 20px;"></div>
    </div>
</div>

<script>
const apiUrl = <?php echo json_encode($api_url); ?>;

async function testPricing() {
    const result = document.getElementById('test-result');
    result.innerHTML = '<p>Lade...</p>';
    
    try {
        const response = await fetch(apiUrl + '?action=get_pricing');
        const data = await response.json();
        result.innerHTML = '<pre class="code-block">' + JSON.stringify(data, null, 2) + '</pre>';
    } catch (error) {
        result.innerHTML = '<div class="alert error">Fehler: ' + error.message + '</div>';
    }
}

async function testLicense() {
    const key = prompt('Lizenzschlüssel eingeben:', 'WPR-TEST-TEST-TEST');
    if (!key) return;
    
    const domain = prompt('Domain eingeben:', 'test.de');
    if (!domain) return;
    
    const result = document.getElementById('test-result');
    result.innerHTML = '<p>Lade...</p>';
    
    try {
        const response = await fetch(apiUrl + '?action=check_license&key=' + encodeURIComponent(key) + '&domain=' + encodeURIComponent(domain));
        const data = await response.json();
        result.innerHTML = '<pre class="code-block">' + JSON.stringify(data, null, 2) + '</pre>';
    } catch (error) {
        result.innerHTML = '<div class="alert error">Fehler: ' + error.message + '</div>';
    }
}
</script>
