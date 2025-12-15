<?php
/**
 * API Test Page
 * 
 * Teste die API direkt im Browser
 */

define('LICENSE_SERVER', true);
require_once __DIR__ . '/config.php';

$test_key = '';
$test_domain = '';
$result = null;

if (isset($_POST['test'])) {
    $test_key = $_POST['license_key'];
    $test_domain = $_POST['domain'];
    
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php';
    $url .= '?key=' . urlencode($test_key) . '&domain=' . urlencode($test_domain);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #1a202c; margin-bottom: 20px; }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 16px;
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
        button:hover { background: #5568d3; }
        pre {
            background: #1a202c;
            color: #10b981;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 14px;
        }
        .success { color: #10b981; font-weight: 600; }
        .error { color: #ef4444; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h1>🧪 API Test</h1>
            <form method="post">
                <input type="text" name="license_key" placeholder="Lizenzschlüssel" value="<?php echo htmlspecialchars($test_key); ?>" required>
                <input type="text" name="domain" placeholder="Domain (z.B. example.com)" value="<?php echo htmlspecialchars($test_domain); ?>" required>
                <button type="submit" name="test">API Testen</button>
            </form>
        </div>
        
        <?php if ($result) : ?>
            <div class="box">
                <h2>📡 API Response:</h2>
                <pre><?php echo json_encode($result, JSON_PRETTY_PRINT); ?></pre>
                
                <?php if ($result['valid']) : ?>
                    <p class="success" style="margin-top: 20px;">✅ Lizenz ist gültig!</p>
                <?php else : ?>
                    <p class="error" style="margin-top: 20px;">❌ Lizenz ungültig: <?php echo $result['error'] ?? 'Unknown error'; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="box">
            <h2>📖 API Dokumentation</h2>
            <p><strong>Endpoint:</strong></p>
            <pre>GET <?php echo 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php'; ?></pre>
            
            <p style="margin-top: 20px;"><strong>Parameter:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><code>key</code> - Lizenzschlüssel (required)</li>
                <li><code>domain</code> - Domain des Kunden (required)</li>
            </ul>
            
            <p style="margin-top: 20px;"><strong>Beispiel:</strong></p>
            <pre>curl '<?php echo 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php'; ?>?key=WPR-TEST-12345&domain=example.com'</pre>
        </div>
    </div>
</body>
</html>
