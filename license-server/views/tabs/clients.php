<?php
/**
 * License Server - Client Management
 * Verwalte WordPress-Installationen & sende Server-URL
 */

// Client hinzufügen
if (isset($_POST['add_client']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $domain = trim($_POST['domain']);
    $api_key = trim($_POST['api_key']);
    
    if (empty($domain) || empty($api_key)) {
        $error = 'Domain und API-Key sind erforderlich!';
    } else {
        $db = LicenseDB::getInstance();
        $clients = $db->getConfig('clients', []);
        
        $clients[$domain] = array(
            'api_key' => $api_key,
            'added' => date('Y-m-d H:i:s'),
        );
        
        $db->setConfig('clients', $clients);
        $success = 'Client erfolgreich hinzugefügt!';
    }
}

// Client löschen
if (isset($_POST['delete_client']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $domain = $_POST['client_domain'];
    
    $db = LicenseDB::getInstance();
    $clients = $db->getConfig('clients', []);
    
    if (isset($clients[$domain])) {
        unset($clients[$domain]);
        $db->setConfig('clients', $clients);
        $success = 'Client gelöscht!';
    }
}

// Server-URL zu Client pushen
if (isset($_POST['push_url']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $domain = $_POST['client_domain'];
    
    $db = LicenseDB::getInstance();
    $clients = $db->getConfig('clients', []);
    
    if (!isset($clients[$domain])) {
        $error = 'Client nicht gefunden!';
    } else {
        $client = $clients[$domain];
        $api_key = $client['api_key'];
        
        // Aktuelle Server-URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $server_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php';
        
        // API-Endpoint des Clients
        $client_api = 'https://' . $domain . '/wpr-license-api.php';
        
        // POST-Daten
        $post_data = array(
            'action' => 'set_server_url',
            'api_key' => $api_key,
            'server_url' => $server_url,
            'domain' => $domain,
        );
        
        // Check if cURL is available
        if (function_exists('curl_init')) {
            // Use cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $client_api);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            // Fallback to file_get_contents
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($post_data),
                    'timeout' => 10,
                ),
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
            );
            
            $context = stream_context_create($options);
            $response = @file_get_contents($client_api, false, $context);
            $http_code = 200; // Assume success if no error
            
            if ($response === false) {
                $http_code = 500;
            }
        }
        
        if ($http_code === 200 && $response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $success = '✅ Server-URL erfolgreich zu ' . $domain . ' gepusht!';
                log_message('Server-URL pushed to ' . $domain, 'info');
            } else {
                $error = '❌ Fehler: ' . ($data['message'] ?? 'Unknown error');
            }
        } else {
            $error = '❌ Verbindung fehlgeschlagen (HTTP ' . $http_code . '). Prüfe ob WordPress erreichbar ist.';
        }
    }
}

$db = LicenseDB::getInstance();
$clients = $db->getConfig('clients', []);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$current_server_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api.php';
?>

<div class="clients-page">
    <?php if (isset($success)) : ?>
        <div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <div class="alert error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Server-Info -->
    <div class="card info">
        <h3>🌐 Deine Server-URL</h3>
        <p>Diese URL wird zu den WordPress-Installationen gepusht:</p>
        <code style="display: block; padding: 12px; background: #1f2937; color: #10b981; border-radius: 4px; margin: 10px 0; font-size: 14px;">
            <?php echo htmlspecialchars($current_server_url); ?>
        </code>
    </div>
    
    <!-- Client hinzufügen -->
    <div class="card">
        <h2>➕ Client hinzufügen</h2>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Domain (ohne https://)</label>
                <input type="text" name="domain" required placeholder="example.com" value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>WordPress API-Key</label>
                <input type="text" name="api_key" required placeholder="64-stelliger Key von WordPress" value="<?php echo htmlspecialchars($_POST['api_key'] ?? ''); ?>">
                <p class="hint">Zu finden in WordPress unter: Lizenz-Verwaltung (wird automatisch generiert)</p>
            </div>
            
            <button type="submit" name="add_client" class="btn-primary">➕ Client hinzufügen</button>
        </form>
    </div>
    
    <!-- Client-Liste -->
    <div class="card">
        <h2>📊 Registrierte Clients (<?php echo count($clients); ?>)</h2>
        
        <?php if (empty($clients)) : ?>
            <p style="text-align: center; padding: 40px; color: #666;">
                Noch keine Clients registriert.<br>
                Füge WordPress-Installationen hinzu, um die Server-URL automatisch zu konfigurieren.
            </p>
        <?php else : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>API-Key (letzte 8 Zeichen)</th>
                        <th>Hinzugefügt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $domain => $client) : ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($domain); ?></strong><br>
                                <small style="color: #666;">https://<?php echo htmlspecialchars($domain); ?></small>
                            </td>
                            <td>
                                <code>...<?php echo htmlspecialchars(substr($client['api_key'], -8)); ?></code>
                            </td>
                            <td><?php echo htmlspecialchars($client['added']); ?></td>
                            <td>
                                <form method="post" style="display: inline-block; margin-right: 5px;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="client_domain" value="<?php echo htmlspecialchars($domain); ?>">
                                    <button type="submit" name="push_url" class="btn-primary btn-small">
                                        🚀 URL pushen
                                    </button>
                                </form>
                                
                                <form method="post" style="display: inline-block;" onsubmit="return confirm('Client wirklich löschen?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="client_domain" value="<?php echo htmlspecialchars($domain); ?>">
                                    <button type="submit" name="delete_client" class="btn-danger btn-small">
                                        🗑️ Löschen
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Anleitung -->
    <div class="card info">
        <h3>📚 Wie funktioniert es?</h3>
        
        <ol style="line-height: 1.8;">
            <li><strong>WordPress-Installation vorbereiten:</strong>
                <ul style="margin-top: 5px;">
                    <li>WP Restaurant Plugin installiert haben</li>
                    <li>Zu Lizenz-Verwaltung gehen</li>
                    <li>API-Key wird automatisch generiert</li>
                    <li>API-Key kopieren (wird unten angezeigt)</li>
                </ul>
            </li>
            
            <li style="margin-top: 10px;"><strong>Client hier registrieren:</strong>
                <ul style="margin-top: 5px;">
                    <li>Domain eingeben (z.B. <code>meine-website.com</code>)</li>
                    <li>API-Key von WordPress eingeben</li>
                    <li>"Client hinzufügen" klicken</li>
                </ul>
            </li>
            
            <li style="margin-top: 10px;"><strong>Server-URL pushen:</strong>
                <ul style="margin-top: 5px;">
                    <li>Klick auf "🚀 URL pushen"</li>
                    <li>Server sendet automatisch seine URL an WordPress</li>
                    <li>WordPress speichert die URL intern</li>
                    <li>Nutzer kann URL nicht mehr ändern!</li>
                </ul>
            </li>
            
            <li style="margin-top: 10px;"><strong>Fertig!</strong>
                <ul style="margin-top: 5px;">
                    <li>WordPress ist jetzt mit diesem Server verbunden</li>
                    <li>Lizenzen können aktiviert werden</li>
                    <li>Preise werden vom Server geladen</li>
                </ul>
            </li>
        </ol>
    </div>
</div>

<style>
.btn-small {
    padding: 6px 12px !important;
    font-size: 13px !important;
}
</style>
