<?php
/**
 * WP Restaurant Menu - License Management
 * Automatische Registrierung beim License-Server!
 * Version 2.0 - Ohne Master Keys, nur Server-basiert
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_License {
    
    // Lizenz-Server URL
    private static function get_server_url() {
        return 'https://license-server.stb-srv.de/license-server/api.php';
    }
    
    // Fallback Preise (falls Server nicht erreichbar)
    private static $fallback_pricing = array(
        'free' => array(
            'price' => 0,
            'currency' => '€',
            'label' => 'FREE',
        ),
        'pro' => array(
            'price' => 29,
            'currency' => '€',
            'label' => 'PRO',
        ),
        'pro_plus' => array(
            'price' => 49,
            'currency' => '€',
            'label' => 'PRO+',
        ),
    );
    
    /**
     * Preise vom Server holen (mit Caching)
     */
    public static function get_pricing() {
        $cached = get_transient('wpr_pricing_data');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $server_url = self::get_server_url();
        
        if (empty($server_url)) {
            return self::$fallback_pricing;
        }
        
        $url = add_query_arg(array(
            'action' => 'get_pricing',
        ), $server_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return self::$fallback_pricing;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['pricing'])) {
            return self::$fallback_pricing;
        }
        
        set_transient('wpr_pricing_data', $data['pricing'], 86400);
        
        return $data['pricing'];
    }
    
    /**
     * Lizenz-Info abrufen (lokal gecached)
     */
    public static function get_license_info() {
        $key = get_option('wpr_license_key', '');
        
        // Wenn kein Key -> Free Version
        if (empty($key)) {
            return array(
                'valid' => false,
                'type' => 'free',
                'max_items' => 20,
                'expires' => '',
                'features' => array(),
            );
        }
        
        // Gecachte Lizenz-Daten prüfen
        $cached = get_option('wpr_license_data');
        $last_check = get_option('wpr_license_last_check', 0);
        
        // Alle 24h neu prüfen
        if ($cached && is_array($cached) && (time() - $last_check) < 86400) {
            return $cached;
        }
        
        // Server-Check durchführen
        $server_url = self::get_server_url();
        if (!empty($server_url)) {
            $result = self::check_license_remote($key);
            
            if ($result && isset($result['valid']) && $result['valid'] === true) {
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
    
    /**
     * Prüfe ob Dark Mode verfügbar
     */
    public static function has_dark_mode() {
        $license = self::get_license_info();
        return isset($license['features']) && in_array('dark_mode', $license['features']);
    }
    
    /**
     * Helper: Lizenz-Label für Anzeige formatieren
     */
    private static function format_license_label($type) {
        $labels = array(
            'free' => 'FREE',
            'pro' => 'PRO',
            'pro_plus' => 'PRO+',
        );
        
        if (isset($labels[$type])) {
            return $labels[$type];
        }
        
        // Fallback: Underscores durch + ersetzen und uppercase
        return strtoupper(str_replace('_', '+', $type));
    }
    
    /**
     * Remote Server-Check
     */
    private static function check_license_remote($key) {
        $server_url = self::get_server_url();
        if (empty($server_url)) return false;
        
        $domain = $_SERVER['HTTP_HOST'];
        $url = add_query_arg(array(
            'action' => 'check_license',
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
        
        if (!$data) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Lizenz aktivieren
     */
    public static function activate_license($key) {
        $key = strtoupper(trim($key));
        
        if (empty($key)) {
            return array(
                'success' => false,
                'message' => 'Bitte geben Sie einen Lizenzschlüssel ein.',
            );
        }
        
        // Format prüfen (WPR-XXXXX-XXXXX-XXXXX)
        if (!preg_match('/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return array(
                'success' => false,
                'message' => 'Ungültiges Format. Erwartet: WPR-XXXXX-XXXXX-XXXXX',
            );
        }
        
        // Lizenz speichern und Server-Check
        $server_url = self::get_server_url();
        
        if (empty($server_url)) {
            return array(
                'success' => false,
                'message' => '⚠️ Lizenz-Server nicht konfiguriert.',
            );
        }
        
        update_option('wpr_license_key', $key);
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        $info = self::check_license_remote($key);
        
        if ($info && isset($info['valid']) && $info['valid'] === true) {
            update_option('wpr_license_data', $info);
            update_option('wpr_license_last_check', time());
            
            // Lizenztyp bestimmen
            $license_type = isset($info['type']) ? $info['type'] : 'unknown';
            $type_label = self::format_license_label($license_type);
            
            // Max Items für Nachricht
            $max_items = isset($info['max_items']) ? $info['max_items'] : 0;
            
            // Features für spezielle Nachricht
            $has_dark_mode = isset($info['features']) && in_array('dark_mode', $info['features']);
            
            // Erfolgs-Nachricht je nach Lizenztyp
            $message = "✅ {$type_label} Lizenz erfolgreich aktiviert!";
            
            if ($license_type === 'free') {
                $message .= " Bis zu {$max_items} Gerichte verfügbar.";
            } elseif ($license_type === 'pro') {
                $message .= " Bis zu {$max_items} Gerichte freigeschaltet.";
            } elseif ($license_type === 'pro_plus') {
                $message .= " Bis zu {$max_items} Gerichte";
                if ($has_dark_mode) {
                    $message .= " + 🌙 Dark Mode freigeschaltet.";
                } else {
                    $message .= " freigeschaltet.";
                }
            } else {
                // Unbekannter Typ
                $message .= " Bis zu {$max_items} Gerichte verfügbar.";
            }
            
            return array(
                'success' => true,
                'message' => $message,
                'data' => $info,
            );
        } else {
            delete_option('wpr_license_key');
            
            $error_msg = '❌ Ungültiger Lizenzschlüssel';
            if (isset($info['message'])) {
                $error_msg .= ': ' . $info['message'];
            }
            
            return array(
                'success' => false,
                'message' => $error_msg,
            );
        }
    }
    
    /**
     * Lizenz deaktivieren
     */
    public static function deactivate_license() {
        delete_option('wpr_license_key');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        return array(
            'success' => true,
            'message' => 'Lizenz wurde deaktiviert.',
        );
    }
    
    /**
     * Admin-Seite rendern
     */
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
        
        // Cache manuell löschen
        if (isset($_POST['wpr_refresh_pricing']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            delete_transient('wpr_pricing_data');
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            echo '<div class="notice notice-success"><p>🔄 Cache gelöscht! Daten werden neu geladen.</p></div>';
        }
        
        $license_info = self::get_license_info();
        $current_key = get_option('wpr_license_key', '');
        $server_url = self::get_server_url();
        $pricing = self::get_pricing();
        $current_domain = $_SERVER['HTTP_HOST'];
        
        $count = wp_count_posts('wpr_menu_item');
        $total_items = $count->publish + $count->draft + $count->pending;
        
        $max_items = $license_info['max_items'];
        $is_over_limit = $total_items > $max_items;
        
        ?>
        <div class="wrap">
            <h1>🔑 Lizenz-Verwaltung</h1>
            
            <!-- Aktueller Status -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">📊 Aktueller Status</h2>
                
                <?php if ($license_info['valid']) : ?>
                    <?php if ($is_over_limit) : ?>
                        <div style="padding: 15px; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 4px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 10px 0; color: #991b1b;">⚠️ Lizenz-Limit überschritten!</h3>
                            <p style="margin: 5px 0;"><strong>Typ:</strong> <?php echo esc_html(self::format_license_label($license_info['type'])); ?></p>
                            <p style="margin: 5px 0;"><strong>Gerichte:</strong> <span style="color: #991b1b; font-weight: bold;"><?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></span> (Überschreitung: <?php echo esc_html($total_items - $max_items); ?>)</p>
                            <?php if (!empty($license_info['expires']) && $license_info['expires'] !== '2099-12-31') : ?>
                                <p style="margin: 5px 0;"><strong>Gültig bis:</strong> <?php echo esc_html(date('d.m.Y', strtotime($license_info['expires']))); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div style="padding: 15px; background: #d1fae5; border-left: 4px solid #10b981; border-radius: 4px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 10px 0; color: #047857;">✅ <?php echo esc_html(self::format_license_label($license_info['type'])); ?> Lizenz aktiv</h3>
                            <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></p>
                            <?php if (self::has_dark_mode()) : ?>
                                <p style="margin: 5px 0;"><strong>Features:</strong> <span style="background: #1f2937; color: #fbbf24; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">🌙 Dark Mode</span></p>
                            <?php endif; ?>
                            <?php if (!empty($license_info['expires']) && $license_info['expires'] !== '2099-12-31') : ?>
                                <p style="margin: 5px 0;"><strong>Gültig bis:</strong> <?php echo esc_html(date('d.m.Y', strtotime($license_info['expires']))); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div style="padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #92400e;">⚠️ FREE Version</h3>
                        <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></p>
                        <p style="margin: 10px 0 0 0;"><a href="#lizenz-aktivieren" class="button button-primary">🔑 Jetzt upgraden</a></p>
                    </div>
                <?php endif; ?>
                
                <div style="padding: 12px; background: #f0f9ff; border-radius: 4px; margin-top: 15px;">
                    <p style="margin: 0; color: #0369a1; font-size: 0.9em;">
                        🌐 <strong>Lizenz-Server:</strong> <code><?php echo esc_html($server_url); ?></code>
                    </p>
                    <p style="margin: 5px 0 0 0; color: #0369a1; font-size: 0.9em;">
                        📍 <strong>Diese Installation:</strong> <code><?php echo esc_html($current_domain); ?></code>
                    </p>
                </div>
            </div>
            
            <!-- Lizenz-Pakete -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">🎯 Verfügbare Pakete</h2>
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                        <button type="submit" name="wpr_refresh_pricing" class="button" style="font-size: 0.9em;">
                            🔄 Daten aktualisieren
                        </button>
                    </form>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div style="padding: 20px; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <h3 style="margin: 0 0 10px 0;"><?php echo esc_html($pricing['free']['label']); ?></h3>
                        <p style="font-size: 2em; font-weight: bold; margin: 10px 0;">
                            <?php echo esc_html($pricing['free']['price']); ?><?php echo esc_html($pricing['free']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 15px 0;">
                            <li style="margin: 8px 0;">✅ Bis zu 20 Gerichte</li>
                            <li style="margin: 8px 0;">✅ Alle Basis-Features</li>
                            <li style="margin: 8px 0;">❌ Kein Dark Mode</li>
                        </ul>
                    </div>
                    
                    <div style="padding: 20px; border: 2px solid #d97706; border-radius: 8px; background: #fef3c7;">
                        <h3 style="margin: 0 0 10px 0; color: #92400e;"><?php echo esc_html($pricing['pro']['label']); ?></h3>
                        <p style="font-size: 2em; font-weight: bold; margin: 10px 0; color: #92400e;">
                            <?php echo esc_html($pricing['pro']['price']); ?><?php echo esc_html($pricing['pro']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 15px 0;">
                            <li style="margin: 8px 0;">✅ Bis zu 200 Gerichte</li>
                            <li style="margin: 8px 0;">✅ Individuell anpassbar</li>
                            <li style="margin: 8px 0;">❌ Kein Dark Mode</li>
                        </ul>
                    </div>
                    
                    <div style="padding: 20px; border: 2px solid #1f2937; border-radius: 8px; background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: #fff;">
                        <h3 style="margin: 0 0 10px 0; color: #fbbf24;"><?php echo esc_html($pricing['pro_plus']['label']); ?> 🌟</h3>
                        <p style="font-size: 2em; font-weight: bold; margin: 10px 0; color: #fbbf24;">
                            <?php echo esc_html($pricing['pro_plus']['price']); ?><?php echo esc_html($pricing['pro_plus']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 15px 0;">
                            <li style="margin: 8px 0;">✅ Bis zu 200 Gerichte</li>
                            <li style="margin: 8px 0;">✅ Individuell anpassbar</li>
                            <li style="margin: 8px 0; color: #fbbf24; font-weight: bold;">🌙 Dark Mode</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Lizenz aktivieren -->
            <div id="lizenz-aktivieren" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
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
                                    Geben Sie Ihren Lizenzschlüssel ein. Format: WPR-XXXXX-XXXXX-XXXXX
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
        </div>
        <?php
    }
}
