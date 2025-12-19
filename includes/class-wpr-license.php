<?php
/**
 * WP Restaurant Menu - License Management
 * Version 2.3.1 - Fixed critical WordPress error
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_License {
    
    // Lizenz-Server URL
    private static function get_server_url() {
        return 'https://license-server.stb-srv.de/license-server/api.php';
    }
    
    // Fallback Preise mit Beschreibungen
    private static $fallback_pricing = array(
        'free' => array(
            'price' => 0,
            'currency' => '€',
            'label' => 'FREE',
            'description' => 'Perfekt zum Testen und für kleine Restaurants',
            'max_items' => 20,
            'features' => array(),
        ),
        'free_plus' => array(
            'price' => 15,
            'currency' => '€',
            'label' => 'FREE+',
            'description' => 'Erweiterte Kapazität für mittelgroße Menüs',
            'max_items' => 60,
            'features' => array(),
        ),
        'pro' => array(
            'price' => 29,
            'currency' => '€',
            'label' => 'PRO',
            'description' => 'Professionelle Lösung für umfangreiche Speisekarten',
            'max_items' => 200,
            'features' => array(),
        ),
        'pro_plus' => array(
            'price' => 49,
            'currency' => '€',
            'label' => 'PRO+',
            'description' => 'PRO + Dark Mode + Warenkorb-System',
            'max_items' => 200,
            'features' => array('dark_mode', 'cart'),
        ),
        'ultimate' => array(
            'price' => 79,
            'currency' => '€',
            'label' => 'ULTIMATE',
            'description' => 'Alle Features + unbegrenzte Gerichte',
            'max_items' => 900,
            'features' => array('dark_mode', 'cart', 'unlimited_items'),
        ),
    );
    
    /**
     * Preise vom Server holen
     */
    public static function get_pricing() {
        $cached = get_transient('wpr_pricing_data');
        if ($cached !== false && is_array($cached)) {
            return $cached;
        }
        
        $server_url = self::get_server_url();
        $url = add_query_arg(array('action' => 'get_pricing'), $server_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return self::$fallback_pricing;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['pricing']) || !is_array($data['pricing'])) {
            return self::$fallback_pricing;
        }
        
        set_transient('wpr_pricing_data', $data['pricing'], 86400);
        return $data['pricing'];
    }
    
    /**
     * Lizenz-Info abrufen
     */
    public static function get_license_info() {
        $key = get_option('wpr_license_key', '');
        
        if (empty($key)) {
            return self::get_free_license();
        }
        
        // Cache prüfen
        $cached = get_option('wpr_license_data');
        $last_check = get_option('wpr_license_last_check', 0);
        
        if ($cached && is_array($cached) && (time() - $last_check) < 86400) {
            return $cached;
        }
        
        // Server-Check
        $result = self::check_license_remote($key);
        
        if ($result && isset($result['valid']) && $result['valid'] === true) {
            update_option('wpr_license_data', $result);
            update_option('wpr_license_last_check', time());
            return $result;
        }
        
        // Fallback auf gecachte Daten bei Server-Fehler
        if ($cached && is_array($cached)) {
            return $cached;
        }
        
        return self::get_free_license();
    }
    
    /**
     * Free Lizenz
     */
    private static function get_free_license() {
        return array(
            'valid' => false,
            'type' => 'free',
            'max_items' => 20,
            'expires' => '',
            'features' => array(),
        );
    }
    
    /**
     * Feature-Check (generisch)
     */
    public static function check_feature($feature_name) {
        $license = self::get_license_info();
        return isset($license['features']) && is_array($license['features']) && in_array($feature_name, $license['features']);
    }
    
    /**
     * Dark Mode Check
     */
    public static function has_dark_mode() {
        return self::check_feature('dark_mode');
    }
    
    /**
     * Cart Feature Check
     */
    public static function has_cart() {
        return self::check_feature('cart');
    }
    
    /**
     * Unlimited Items Check
     */
    public static function has_unlimited_items() {
        return self::check_feature('unlimited_items');
    }
    
    /**
     * Lizenz-Label formatieren
     */
    private static function format_license_label($type) {
        $labels = array(
            'free' => 'FREE',
            'free_plus' => 'FREE+',
            'pro' => 'PRO',
            'pro_plus' => 'PRO+',
            'ultimate' => 'ULTIMATE',
        );
        
        return isset($labels[$type]) ? $labels[$type] : strtoupper(str_replace('_', '+', $type));
    }
    
    /**
     * Lizenz-Format validieren
     */
    private static function validate_license_format($key) {
        // 3 Segmente
        if (preg_match('/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return true;
        }
        
        // 4 Segmente
        if (preg_match('/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Remote Server-Check
     */
    private static function check_license_remote($key) {
        $server_url = self::get_server_url();
        if (empty($server_url)) return false;
        
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown';
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
        
        return $data ? $data : false;
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
        
        if (!self::validate_license_format($key)) {
            return array(
                'success' => false,
                'message' => 'Ungültiges Format. Erwartet: WPR-XXXXX-XXXXX-XXXXX oder WPR-XXXXX-XXXXX-XXXXX-XXXXX',
            );
        }
        
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
            
            $license_type = isset($info['type']) ? $info['type'] : 'unknown';
            $type_label = self::format_license_label($license_type);
            $max_items = isset($info['max_items']) ? $info['max_items'] : 0;
            $features = isset($info['features']) && is_array($info['features']) ? $info['features'] : array();
            
            $message = "✅ {$type_label} Lizenz erfolgreich aktiviert!";
            $message .= " Bis zu {$max_items} Gerichte";
            
            $feature_list = array();
            if (in_array('dark_mode', $features)) $feature_list[] = '🌙 Dark Mode';
            if (in_array('cart', $features)) $feature_list[] = '🛒 Warenkorb';
            if (in_array('unlimited_items', $features)) $feature_list[] = '♾️ Unbegrenzt';
            
            if (!empty($feature_list)) {
                $message .= ' + ' . implode(', ', $feature_list);
            }
            
            $message .= ' freigeschaltet.';
            
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
        try {
            // Lizenz aktivieren
            if (isset($_POST['wpr_activate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
                $key = sanitize_text_field($_POST['license_key']);
                $result = self::activate_license($key);
                
                $notice_class = $result['success'] ? 'notice-success' : 'notice-error';
                echo '<div class="notice ' . esc_attr($notice_class) . '"><p>' . esc_html($result['message']) . '</p></div>';
            }
            
            // Lizenz deaktivieren
            if (isset($_POST['wpr_deactivate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
                $result = self::deactivate_license();
                echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            }
            
            // Cache löschen
            if (isset($_POST['wpr_refresh_pricing']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
                delete_transient('wpr_pricing_data');
                delete_option('wpr_license_data');
                delete_option('wpr_license_last_check');
                echo '<div class="notice notice-success"><p>🔄 Cache gelöscht!</p></div>';
            }
            
            $license_info = self::get_license_info();
            $current_key = get_option('wpr_license_key', '');
            $server_url = self::get_server_url();
            $pricing = self::get_pricing();
            $current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            
            $count = wp_count_posts('wpr_menu_item');
            $total_items = 0;
            if ($count && is_object($count)) {
                $total_items = (isset($count->publish) ? $count->publish : 0) + 
                               (isset($count->draft) ? $count->draft : 0) + 
                               (isset($count->pending) ? $count->pending : 0);
            }
            
            $max_items = isset($license_info['max_items']) ? $license_info['max_items'] : 20;
            $current_type = isset($license_info['type']) ? $license_info['type'] : 'free';
            
            // Test-Button für Server-Verbindung
            $server_test = null;
            if (isset($_POST['wpr_test_server']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
                $test_url = add_query_arg(array('action' => 'status'), $server_url);
                $test_response = wp_remote_get($test_url, array('timeout' => 5, 'sslverify' => false));
                
                if (is_wp_error($test_response)) {
                    $server_test = array('success' => false, 'message' => $test_response->get_error_message());
                } else {
                    $test_body = wp_remote_retrieve_body($test_response);
                    $test_data = json_decode($test_body, true);
                    $server_test = array(
                        'success' => isset($test_data['status']) && $test_data['status'] === 'online',
                        'message' => isset($test_data['status']) ? 'Server online (v' . (isset($test_data['version']) ? $test_data['version'] : '?') . ')' : 'Server offline',
                    );
                }
            }
            
            ?>
            <div class="wrap">
                <h1>🔑 Lizenz-Verwaltung</h1>
                
                <?php if ($server_test) : ?>
                    <div class="notice <?php echo $server_test['success'] ? 'notice-success' : 'notice-error'; ?>">
                        <p><?php echo $server_test['success'] ? '✅' : '❌'; ?> Server-Status: <?php echo esc_html($server_test['message']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Aktueller Status -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">📊 Aktueller Status</h2>
                    
                    <?php if (isset($license_info['valid']) && $license_info['valid']) : ?>
                        <div style="padding: 15px; background: #d1fae5; border-left: 4px solid #10b981; border-radius: 4px;">
                            <h3 style="margin: 0 0 10px 0; color: #047857;">✅ <?php echo esc_html(self::format_license_label($license_info['type'])); ?> aktiv</h3>
                            <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></p>
                            <?php 
                            $features = isset($license_info['features']) && is_array($license_info['features']) ? $license_info['features'] : array();
                            if (!empty($features)) :
                            ?>
                                <p style="margin: 5px 0;"><strong>Features:</strong> 
                                <?php 
                                $feature_names = array();
                                if (in_array('dark_mode', $features)) $feature_names[] = '🌙 Dark Mode';
                                if (in_array('cart', $features)) $feature_names[] = '🛒 Warenkorb';
                                if (in_array('unlimited_items', $features)) $feature_names[] = '♾️ Unbegrenzt';
                                echo implode(', ', $feature_names);
                                ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div style="padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                            <h3 style="margin: 0 0 10px 0; color: #92400e;">⚠️ FREE Version</h3>
                            <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="padding: 12px; background: #f0f9ff; border-radius: 4px; margin-top: 15px;">
                        <p style="margin: 0; font-size: 0.9em;">
                            🌐 <strong>Server:</strong> <code><?php echo esc_html($server_url); ?></code>
                        </p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em;">
                            📍 <strong>Domain:</strong> <code><?php echo esc_html($current_domain); ?></code>
                        </p>
                        <form method="post" style="margin: 10px 0 0 0;">
                            <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                            <button type="submit" name="wpr_test_server" class="button button-small">🔍 Server testen</button>
                            <button type="submit" name="wpr_refresh_pricing" class="button button-small">🔄 Preise aktualisieren</button>
                        </form>
                    </div>
                </div>
                
                <!-- Verfügbare Lizenzen -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">💳 Verfügbare Lizenzen</h2>
                    <p style="margin-bottom: 20px; color: #6b7280;">Wählen Sie das passende Modell für Ihr Restaurant:</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <?php 
                        if (is_array($pricing)) :
                            foreach ($pricing as $type => $package) : 
                                if (!is_array($package)) continue;
                                
                                $is_current = ($type === $current_type);
                                $border_color = $is_current ? '#10b981' : '#e5e7eb';
                                $bg_color = $is_current ? '#f0fdf4' : '#ffffff';
                                
                                $price = isset($package['price']) ? $package['price'] : 0;
                                $currency = isset($package['currency']) ? $package['currency'] : '€';
                                $label = isset($package['label']) ? $package['label'] : $type;
                                $description = isset($package['description']) ? $package['description'] : '';
                                $items = isset($package['max_items']) ? $package['max_items'] : 0;
                                $features = isset($package['features']) && is_array($package['features']) ? $package['features'] : array();
                        ?>
                            <div style="border: 2px solid <?php echo $border_color; ?>; border-radius: 8px; padding: 20px; background: <?php echo $bg_color; ?>; position: relative;">
                                <?php if ($is_current) : ?>
                                    <div style="position: absolute; top: 10px; right: 10px; background: #10b981; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.75em; font-weight: bold;">
                                        AKTIV
                                    </div>
                                <?php endif; ?>
                                
                                <h3 style="margin: 0 0 10px 0; font-size: 1.5em; color: #111827;">
                                    <?php echo esc_html($label); ?>
                                </h3>
                                
                                <div style="margin: 15px 0;">
                                    <span style="font-size: 2.5em; font-weight: bold; color: #111827;">
                                        <?php 
                                        if ($price == 0) {
                                            echo 'Kostenlos';
                                        } else {
                                            echo esc_html($price . ' ' . $currency);
                                        }
                                        ?>
                                    </span>
                                    <?php if ($price > 0) : ?>
                                        <span style="font-size: 0.9em; color: #6b7280;"> / einmalig</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p style="color: #6b7280; font-size: 0.95em; min-height: 40px; margin: 10px 0;">
                                    <?php echo esc_html($description); ?>
                                </p>
                                
                                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 15px 0;">
                                
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <li style="padding: 8px 0; display: flex; align-items: center; gap: 8px;">
                                        <span style="color: #10b981; font-size: 1.2em;">✓</span>
                                        <span><strong><?php echo esc_html($items); ?> Gerichte</strong></span>
                                    </li>
                                    
                                    <?php if (in_array('dark_mode', $features)) : ?>
                                        <li style="padding: 8px 0; display: flex; align-items: center; gap: 8px;">
                                            <span style="color: #10b981; font-size: 1.2em;">✓</span>
                                            <span>🌙 Dark Mode</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('cart', $features)) : ?>
                                        <li style="padding: 8px 0; display: flex; align-items: center; gap: 8px;">
                                            <span style="color: #10b981; font-size: 1.2em;">✓</span>
                                            <span>🛒 Warenkorb-System</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('unlimited_items', $features)) : ?>
                                        <li style="padding: 8px 0; display: flex; align-items: center; gap: 8px;">
                                            <span style="color: #10b981; font-size: 1.2em;">✓</span>
                                            <span>♾️ Unbegrenzte Gerichte</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                
                <!-- Lizenz aktivieren -->
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">🔐 Lizenz aktivieren</h2>
                    
                    <form method="post">
                        <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="license_key">Lizenzschlüssel</label></th>
                                <td>
                                    <input 
                                        type="text" 
                                        name="license_key" 
                                        id="license_key" 
                                        value="<?php echo esc_attr($current_key); ?>" 
                                        class="regular-text"
                                        placeholder="WPR-XXXXX-XXXXX-XXXXX"
                                        style="font-family: monospace; font-size: 1.1em;"
                                    />
                                    <p class="description">Format: WPR-XXXXX-XXXXX-XXXXX (oder mit 4 Segmenten)</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="wpr_activate_license" class="button button-primary button-large">
                                🔑 Lizenz aktivieren
                            </button>
                            
                            <?php if (!empty($current_key)) : ?>
                                <button type="submit" name="wpr_deactivate_license" class="button button-secondary">
                                    Lizenz deaktivieren
                                </button>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>
            
            <style>
            .wrap h3 {
                font-weight: 600;
            }
            @media (max-width: 768px) {
                .wrap > div[style*="grid"] {
                    grid-template-columns: 1fr !important;
                }
            }
            </style>
            <?php
        } catch (Exception $e) {
            echo '<div class="wrap"><h1>🔑 Lizenz-Verwaltung</h1>';
            echo '<div class="notice notice-error"><p>Fehler beim Laden der Lizenz-Seite: ' . esc_html($e->getMessage()) . '</p></div>';
            echo '<p><a href="' . admin_url('edit.php?post_type=wpr_menu_item') . '" class="button">← Zurück zum Menü</a></p>';
            echo '</div>';
        }
    }
}
