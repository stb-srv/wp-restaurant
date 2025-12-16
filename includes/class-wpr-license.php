<?php
/**
 * WP Restaurant Menu - License Management
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_License {
    
    // 10 Master Keys - funktionieren IMMER ohne Server
    private static $master_keys = array(
        'WPR-MASTER-2025-KEY1-ALPHA',
        'WPR-MASTER-2025-KEY2-BETA',
        'WPR-MASTER-2025-KEY3-GAMMA',
        'WPR-MASTER-2025-KEY4-DELTA',
        'WPR-MASTER-2025-KEY5-EPSILON',
        'WPR-MASTER-2025-KEY6-ZETA',
        'WPR-MASTER-2025-KEY7-ETA',
        'WPR-MASTER-2025-KEY8-THETA',
        'WPR-MASTER-2025-KEY9-IOTA',
        'WPR-MASTER-2025-KEY10-KAPPA',
    );
    
    // Lizenz-Server URL (später einstellbar)
    private static function get_server_url() {
        return get_option('wpr_license_server', '');
    }
    
    // Prüfe ob Master Key
    private static function is_master_key($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys);
    }
    
    // Lizenz-Info abrufen (lokal gecached)
    public static function get_license_info() {
        $key = get_option('wpr_license_key', '');
        
        // Master Key? -> Immer gültig!
        if (self::is_master_key($key)) {
            return array(
                'valid' => true,
                'type' => 'master',
                'max_items' => 999999,
                'expires' => '2099-12-31',
                'features' => array('unlimited_items', 'all_features'),
            );
        }
        
        // Gecachte Lizenz-Daten
        $cached = get_option('wpr_license_data');
        $last_check = get_option('wpr_license_last_check', 0);
        
        // Alle 24h neu prüfen
        if ($cached && (time() - $last_check) < 86400) {
            return $cached;
        }
        
        // Server-Check (wenn URL gesetzt)
        $server_url = self::get_server_url();
        if (!empty($key) && !empty($server_url)) {
            $result = self::check_license_remote($key);
            if ($result) {
                update_option('wpr_license_data', $result);
                update_option('wpr_license_last_check', time());
                return $result;
            }
        }
        
        // Fallback: Free Version
        return array(
            'valid' => false,
            'type' => 'free',
            'max_items' => 20,
            'expires' => '',
            'features' => array(),
        );
    }
    
    // Remote Server-Check
    private static function check_license_remote($key) {
        $server_url = self::get_server_url();
        if (empty($server_url)) return false;
        
        $domain = $_SERVER['HTTP_HOST'];
        $url = add_query_arg(array(
            'key' => $key,
            'domain' => $domain,
        ), $server_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['valid'])) {
            return false;
        }
        
        return $data;
    }
    
    // Lizenz aktivieren
    public static function activate_license($key) {
        $key = strtoupper(trim($key));
        
        // Leerer Key
        if (empty($key)) {
            return array(
                'success' => false,
                'message' => 'Bitte geben Sie einen Lizenzschlüssel ein.',
            );
        }
        
        // Master Key?
        if (self::is_master_key($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '🎉 Master-Lizenz erfolgreich aktiviert! Alle Features freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // Server-Check (wenn verfügbar)
        $server_url = self::get_server_url();
        if (!empty($server_url)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            $info = self::get_license_info();
            
            if ($info['valid']) {
                return array(
                    'success' => true,
                    'message' => '✅ Lizenz erfolgreich aktiviert!',
                    'data' => $info,
                );
            } else {
                delete_option('wpr_license_key');
                return array(
                    'success' => false,
                    'message' => '❌ Ungültiger Lizenzschlüssel oder Lizenz abgelaufen.',
                );
            }
        }
        
        // Kein Server = Ungültig
        return array(
            'success' => false,
            'message' => '⚠️ Lizenz-Server nicht konfiguriert. Master-Keys funktionieren weiterhin.',
        );
    }
    
    // Lizenz deaktivieren
    public static function deactivate_license() {
        delete_option('wpr_license_key');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        return array(
            'success' => true,
            'message' => 'Lizenz wurde deaktiviert.',
        );
    }
    
    // Admin-Seite rendern
    public static function render_page() {
        // Lizenz aktivieren
        if (isset($_POST['wpr_activate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            $key = sanitize_text_field($_POST['license_key']);
            $result = self::activate_license($key);
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
        
        // Lizenz deaktivieren
        if (isset($_POST['wpr_deactivate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            $result = self::deactivate_license();
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        }
        
        // Server-URL speichern
        if (isset($_POST['wpr_save_server']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            $server_url = esc_url_raw($_POST['license_server']);
            update_option('wpr_license_server', $server_url);
            echo '<div class="notice notice-success"><p>Server-URL gespeichert!</p></div>';
        }
        
        $license_info = self::get_license_info();
        $current_key = get_option('wpr_license_key', '');
        $server_url = self::get_server_url();
        
        $count = wp_count_posts('wpr_menu_item');
        $total_items = $count->publish + $count->draft + $count->pending;
        
        // Prüfe ob unbegrenzt
        $is_unlimited = $license_info['valid'] && (in_array('unlimited_items', $license_info['features']) || $license_info['max_items'] >= 999);
        
        ?>
        <div class="wrap">
            <h1>🔑 Lizenz-Verwaltung</h1>
            
            <!-- Aktueller Status -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">📊 Aktueller Status</h2>
                
                <?php if ($license_info['valid']) : ?>
                    <div style="padding: 15px; background: #d1fae5; border-left: 4px solid #10b981; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #047857;">✅ Pro-Lizenz aktiv</h3>
                        <p style="margin: 5px 0;"><strong>Typ:</strong> <?php echo esc_html(ucfirst($license_info['type'])); ?></p>
                        <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo $is_unlimited ? '∞ Unbegrenzt' : esc_html($license_info['max_items']); ?></p>
                        <?php if (!empty($license_info['expires']) && $license_info['expires'] !== '2099-12-31') : ?>
                            <p style="margin: 5px 0;"><strong>Gültig bis:</strong> <?php echo esc_html(date('d.m.Y', strtotime($license_info['expires']))); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div style="padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #92400e;">⚠️ Free Version</h3>
                        <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($license_info['max_items']); ?> (Limit erreicht bei <?php echo esc_html($license_info['max_items']); ?>)</p>
                        <p style="margin: 10px 0 0 0;">Aktivieren Sie eine Pro-Lizenz für unbegrenzte Gerichte!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Lizenz aktivieren -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">🔐 Lizenz aktivieren</h2>
                
                <form method="post">
                    <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="license_key">Lizenzschlüssel</label></th>
                            <td>
                                <input 
                                    type="text" 
                                    name="license_key" 
                                    id="license_key" 
                                    value="<?php echo esc_attr($current_key); ?>" 
                                    class="regular-text"
                                    placeholder="WPR-XXXXX-XXXXX-XXXXX"
                                />
                                <p class="description">
                                    Geben Sie Ihren Lizenzschlüssel ein. Master-Keys funktionieren ohne Server.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="wpr_activate_license" class="button button-primary button-large">
                            🔑 Lizenz aktivieren
                        </button>
                        
                        <?php if (!empty($current_key)) : ?>
                            <button type="submit" name="wpr_deactivate_license" class="button button-secondary" style="margin-left: 10px;">
                                Lizenz deaktivieren
                            </button>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Server-Konfiguration -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">🌐 Lizenz-Server (Optional)</h2>
                
                <form method="post">
                    <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="license_server">Server-URL</label></th>
                            <td>
                                <input 
                                    type="url" 
                                    name="license_server" 
                                    id="license_server" 
                                    value="<?php echo esc_url($server_url); ?>" 
                                    class="regular-text"
                                    placeholder="https://ihre-domain.com/lizenz-api.php"
                                />
                                <p class="description">
                                    URL zu Ihrem Lizenz-Server API-Endpoint. Leer lassen wenn Sie nur Master-Keys nutzen.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="wpr_save_server" class="button button-primary">
                            💾 Server-URL speichern
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Master Keys Info -->
            <div style="background: #f0f9ff; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #0ea5e9;">
                <h2 style="margin-top: 0; color: #0369a1;">ℹ️ Master-Keys Information</h2>
                <p>Die folgenden 10 Master-Keys funktionieren <strong>immer offline</strong> ohne Server-Verbindung:</p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach (self::$master_keys as $key) : ?>
                        <li><code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace;"><?php echo esc_html($key); ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Hinweis:</strong> Diese Keys sind für Entwicklung und Tests gedacht. Für Produktiv-Umgebungen richten Sie einen Lizenz-Server ein.</p>
            </div>
        </div>
        <?php
    }
}
