<?php
/**
 * WP Restaurant Menu - License Management
 * Automatische Registrierung beim License-Server!
 * 
 * Neue Lizenzmodelle:
 * - Free: 20 Speisen, kostenlos
 * - Standard: 60 Speisen
 * - Pro: 200 Speisen
 * - Pro+: 200 Speisen + Dark Mode
 * - Ultimate: 900 Speisen + alle Features
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_License {
    
    // 10 Master Keys für Free (Test/Demo)
    private static $master_keys_free = array(
        'WPR-FREE-2025-KEY1-ALPHA',
        'WPR-FREE-2025-KEY2-BETA',
        'WPR-FREE-2025-KEY3-GAMMA',
    );
    
    // 10 Master Keys für Standard (60 Items)
    private static $master_keys_standard = array(
        'WPR-STANDARD-2025-KEY1-ALPHA',
        'WPR-STANDARD-2025-KEY2-BETA',
        'WPR-STANDARD-2025-KEY3-GAMMA',
    );
    
    // Master Keys für Pro (200 Items)
    private static $master_keys_pro = array(
        'WPR-PRO-2025-KEY1-DELTA',
        'WPR-PRO-2025-KEY2-EPSILON',
        'WPR-PRO-2025-KEY3-ZETA',
    );
    
    // PRO+ Master Keys (200 Items + Dark Mode)
    private static $master_keys_pro_plus = array(
        'WPR-PROPLUS-2025-KEY1-OMEGA',
        'WPR-PROPLUS-2025-KEY2-SIGMA',
        'WPR-PROPLUS-2025-KEY3-PHI',
    );
    
    // Ultimate Master Keys (900 Items + all Features)
    private static $master_keys_ultimate = array(
        'WPR-ULTIMATE-2025-KEY1-PSILONALPHA',
        'WPR-ULTIMATE-2025-KEY2-ARCHIALPHA',
        'WPR-ULTIMATE-2025-KEY3-TAUONOALPHA',
    );
    
    // FESTE Server-URL (hier eintragen!)
    private static function get_server_url() {
        // WICHTIG: /license-server/ nicht vergessen!
        return 'https://license-server.stb-srv.de/license-server/api.php';
    }
    
    // Fallback Preise (falls Server nicht erreichbar)
    private static $fallback_pricing = array(
        'free' => array(
            'price' => 0,
            'currency' => '€',
            'label' => 'FREE',
        ),
        'standard' => array(
            'price' => 19,
            'currency' => '€',
            'label' => 'STANDARD',
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
        'ultimate' => array(
            'price' => 79,
            'currency' => '€',
            'label' => 'ULTIMATE',
        ),
    );
    
    // Prüfe ob Free Master Key
    private static function is_master_key_free($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys_free);
    }
    
    // Prüfe ob Standard Master Key
    private static function is_master_key_standard($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys_standard);
    }
    
    // Prüfe ob Pro Master Key
    private static function is_master_key_pro($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys_pro);
    }
    
    // Prüfe ob PRO+ Master Key
    private static function is_master_key_pro_plus($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys_pro_plus);
    }
    
    // Prüfe ob Ultimate Master Key
    private static function is_master_key_ultimate($key) {
        return in_array(strtoupper(trim($key)), self::$master_keys_ultimate);
    }
    
    // Preise vom Server holen (mit Caching)
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
    
    // Lizenz-Info abrufen (lokal gecached)
    public static function get_license_info() {
        $key = get_option('wpr_license_key', '');
        
        // Ultimate Master Key? -> 900 Items + Dark Mode + alle Features
        if (self::is_master_key_ultimate($key)) {
            return array(
                'valid' => true,
                'type' => 'ultimate',
                'display_name' => 'ULTIMATE',
                'max_items' => 900,
                'expires' => '2099-12-31',
                'features' => array('dark_mode', 'unlimited_items'),
            );
        }
        
        // PRO+ Master Key? -> 200 Items + Dark Mode
        if (self::is_master_key_pro_plus($key)) {
            return array(
                'valid' => true,
                'type' => 'pro_plus',
                'display_name' => 'PRO+',
                'max_items' => 200,
                'expires' => '2099-12-31',
                'features' => array('dark_mode'),
            );
        }
        
        // Pro Master Key? -> 200 Items
        if (self::is_master_key_pro($key)) {
            return array(
                'valid' => true,
                'type' => 'pro',
                'display_name' => 'PRO',
                'max_items' => 200,
                'expires' => '2099-12-31',
                'features' => array(),
            );
        }
        
        // Standard Master Key? -> 60 Items
        if (self::is_master_key_standard($key)) {
            return array(
                'valid' => true,
                'type' => 'standard',
                'display_name' => 'STANDARD',
                'max_items' => 60,
                'expires' => '2099-12-31',
                'features' => array(),
            );
        }
        
        // Free Master Key? -> 20 Items
        if (self::is_master_key_free($key)) {
            return array(
                'valid' => true,
                'type' => 'free',
                'display_name' => 'FREE LIZENZ AKTIVIERT',
                'max_items' => 20,
                'expires' => '2099-12-31',
                'features' => array(),
            );
        }
        
        // Gecachte Lizenz-Daten
        $cached = get_option('wpr_license_data');
        $last_check = get_option('wpr_license_last_check', 0);
        
        // Alle 24h neu prüfen
        if ($cached && (time() - $last_check) < 86400) {
            return $cached;
        }
        
        // Server-Check
        $server_url = self::get_server_url();
        if (!empty($key) && !empty($server_url)) {
            $result = self::check_license_remote($key);
            if ($result && isset($result['valid'])) {
                update_option('wpr_license_data', $result);
                update_option('wpr_license_last_check', time());
                return $result;
            }
        }
        
        // Fallback: Free Version (20 Items)
        return array(
            'valid' => false,
            'type' => 'free',
            'display_name' => 'KOSTENLOS',
            'max_items' => 20,
            'expires' => '',
            'features' => array(),
        );
    }
    
    // Prüfe ob Dark Mode verfügbar
    public static function has_dark_mode() {
        $license = self::get_license_info();
        return in_array('dark_mode', $license['features']);
    }
    
    // Remote Server-Check
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
    
    // Lizenz aktivieren
    public static function activate_license($key) {
        $key = strtoupper(trim($key));
        
        if (empty($key)) {
            return array(
                'success' => false,
                'message' => 'Bitte geben Sie einen Lizenzschlüssel ein.',
            );
        }
        
        // Ultimate Master Key?
        if (self::is_master_key_ultimate($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '🎉 ULTIMATE Master-Lizenz aktiviert! 900 Gerichte + alle Features freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // PRO+ Master Key?
        if (self::is_master_key_pro_plus($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '🎉 PRO+ Master-Lizenz aktiviert! 200 Gerichte + Dark Mode freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // Pro Master Key?
        if (self::is_master_key_pro($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '🎉 PRO Master-Lizenz aktiviert! 200 Gerichte freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // Standard Master Key?
        if (self::is_master_key_standard($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '🎉 STANDARD Master-Lizenz aktiviert! 60 Gerichte freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // Free Master Key?
        if (self::is_master_key_free($key)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            return array(
                'success' => true,
                'message' => '✅ FREE Master-Lizenz aktiviert! 20 Gerichte freigeschaltet.',
                'data' => self::get_license_info(),
            );
        }
        
        // Server-Check
        $server_url = self::get_server_url();
        if (!empty($server_url)) {
            update_option('wpr_license_key', $key);
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            
            $info = self::check_license_remote($key);
            
            if ($info && isset($info['valid']) && $info['valid'] === true) {
                update_option('wpr_license_data', $info);
                update_option('wpr_license_last_check', time());
                
                return array(
                    'success' => true,
                    'message' => '✅ Lizenz erfolgreich aktiviert!',
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
        
        return array(
            'success' => false,
            'message' => '⚠️ Lizenz-Server nicht konfiguriert.',
        );
    }
    
    // Lizenz deaktivieren
    public static function deactivate_license() {
        delete_option('wpr_license_key');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        return array(
            'success' => true,
            'message' => 'Lizenz wurde deaktiviert. Sie nutzen jetzt die kostenlose Version (20 Gerichte).',
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
        
        // Cache manuell löschen
        if (isset($_POST['wpr_refresh_pricing']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            delete_transient('wpr_pricing_data');
            delete_option('wpr_license_data');
            delete_option('wpr_license_last_check');
            echo '<div class="notice notice-success"><p>Cache gelöscht! Daten werden neu geladen.</p></div>';
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
        
        // Status-Farbe basierend auf Lizenztyp
        $status_bg = '#fef3c7';
        $status_border = '#f59e0b';
        $status_text = '#92400e';
        
        if ($license_info['valid'] && $license_info['type'] === 'ultimate') {
            $status_bg = '#dbeafe';
            $status_border = '#3b82f6';
            $status_text = '#1e40af';
        } elseif ($license_info['valid'] && $license_info['type'] === 'pro_plus') {
            $status_bg = '#e0e7ff';
            $status_border = '#6366f1';
            $status_text = '#312e81';
        } elseif ($license_info['valid']) {
            $status_bg = '#dcfce7';
            $status_border = '#22c55e';
            $status_text = '#166534';
        }
        
        ?>
        <div class="wrap">
            <h1>🔑 Lizenz-Verwaltung</h1>
            
            <!-- Aktueller Status -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">📊 Aktueller Status</h2>
                
                <?php if ($is_over_limit) : ?>
                    <div style="padding: 15px; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #991b1b;">⚠️ Lizenz-Limit überschritten!</h3>
                        <p style="margin: 5px 0;"><strong>Typ:</strong> <?php echo esc_html(strtoupper($license_info['display_name'])); ?></p>
                        <p style="margin: 5px 0;"><strong>Gerichte:</strong> <span style="color: #991b1b; font-weight: bold;"><?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></span> (Überschreitung: <?php echo esc_html($total_items - $max_items); ?>)</p>
                        <?php if (!empty($license_info['expires']) && $license_info['expires'] !== '2099-12-31') : ?>
                            <p style="margin: 5px 0;"><strong>Gültig bis:</strong> <?php echo esc_html(date('d.m.Y', strtotime($license_info['expires']))); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div style="padding: 15px; background: <?php echo esc_attr($status_bg); ?>; border-left: 4px solid <?php echo esc_attr($status_border); ?>; border-radius: 4px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: <?php echo esc_attr($status_text); ?>;">
                            <?php if ($license_info['valid']) : ?>
                                ✅
                            <?php else : ?>
                                ⓘ
                            <?php endif; ?>
                            <?php echo esc_html($license_info['display_name']); ?>
                        </h3>
                        <p style="margin: 5px 0;"><strong>Typ:</strong> <?php echo esc_html(strtoupper($license_info['type'])); ?></p>
                        <p style="margin: 5px 0;"><strong>Gerichte:</strong> <?php echo esc_html($total_items); ?> / <?php echo esc_html($max_items); ?></p>
                        <?php if (self::has_dark_mode()) : ?>
                            <p style="margin: 5px 0;"><strong>Features:</strong> <span style="background: #1f2937; color: #fbbf24; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">🌙 Dark Mode</span></p>
                        <?php endif; ?>
                        <?php if (!empty($license_info['expires']) && $license_info['expires'] !== '2099-12-31') : ?>
                            <p style="margin: 5px 0;"><strong>Gültig bis:</strong> <?php echo esc_html(date('d.m.Y', strtotime($license_info['expires']))); ?></p>
                        <?php endif; ?>
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
                
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
                    <!-- FREE -->
                    <div style="padding: 15px; border: 2px solid #d1d5db; border-radius: 8px;">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.1em;"><?php echo esc_html($pricing['free']['label']); ?></h3>
                        <p style="font-size: 1.8em; font-weight: bold; margin: 8px 0;">
                            <?php echo esc_html($pricing['free']['price']); ?><?php echo esc_html($pricing['free']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 12px 0; font-size: 0.9em;">
                            <li style="margin: 5px 0;">✅ 20 Gerichte</li>
                            <li style="margin: 5px 0;">✅ Basis-Features</li>
                            <li style="margin: 5px 0;">❌ Kein Dark Mode</li>
                        </ul>
                    </div>
                    
                    <!-- STANDARD -->
                    <div style="padding: 15px; border: 2px solid #6ee7b7; border-radius: 8px; background: #ecfdf5;">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.1em; color: #065f46;"><?php echo esc_html($pricing['standard']['label']); ?></h3>
                        <p style="font-size: 1.8em; font-weight: bold; margin: 8px 0; color: #065f46;">
                            <?php echo esc_html($pricing['standard']['price']); ?><?php echo esc_html($pricing['standard']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 12px 0; font-size: 0.9em;">
                            <li style="margin: 5px 0;">✅ 60 Gerichte</li>
                            <li style="margin: 5px 0;">✅ Alle Features</li>
                            <li style="margin: 5px 0;">❌ Kein Dark Mode</li>
                        </ul>
                    </div>
                    
                    <!-- PRO -->
                    <div style="padding: 15px; border: 2px solid #d97706; border-radius: 8px; background: #fef3c7;">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.1em; color: #92400e;"><?php echo esc_html($pricing['pro']['label']); ?></h3>
                        <p style="font-size: 1.8em; font-weight: bold; margin: 8px 0; color: #92400e;">
                            <?php echo esc_html($pricing['pro']['price']); ?><?php echo esc_html($pricing['pro']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 12px 0; font-size: 0.9em;">
                            <li style="margin: 5px 0;">✅ 200 Gerichte</li>
                            <li style="margin: 5px 0;">✅ Alle Features</li>
                            <li style="margin: 5px 0;">❌ Kein Dark Mode</li>
                        </ul>
                    </div>
                    
                    <!-- PRO+ -->
                    <div style="padding: 15px; border: 2px solid #6366f1; border-radius: 8px; background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.1em; color: #312e81;">🌟 <?php echo esc_html($pricing['pro_plus']['label']); ?></h3>
                        <p style="font-size: 1.8em; font-weight: bold; margin: 8px 0; color: #312e81;">
                            <?php echo esc_html($pricing['pro_plus']['price']); ?><?php echo esc_html($pricing['pro_plus']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 12px 0; font-size: 0.9em;">
                            <li style="margin: 5px 0;">✅ 200 Gerichte</li>
                            <li style="margin: 5px 0;">✅ Alle Features</li>
                            <li style="margin: 5px 0; color: #6366f1; font-weight: bold;">🌙 Dark Mode</li>
                        </ul>
                    </div>
                    
                    <!-- ULTIMATE -->
                    <div style="padding: 15px; border: 2px solid #0284c7; border-radius: 8px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                        <h3 style="margin: 0 0 8px 0; font-size: 1.1em; color: #1e40af;">👑 <?php echo esc_html($pricing['ultimate']['label']); ?></h3>
                        <p style="font-size: 1.8em; font-weight: bold; margin: 8px 0; color: #1e40af;">
                            <?php echo esc_html($pricing['ultimate']['price']); ?><?php echo esc_html($pricing['ultimate']['currency']); ?>
                        </p>
                        <ul style="list-style: none; padding: 0; margin: 12px 0; font-size: 0.9em;">
                            <li style="margin: 5px 0;">✅ 900 Gerichte</li>
                            <li style="margin: 5px 0;">✅ Alle Features</li>
                            <li style="margin: 5px 0; color: #1e40af; font-weight: bold;">🌙 Dark Mode</li>
                        </ul>
                    </div>
                </div>
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
                                    Geben Sie Ihren Lizenzschlüssel ein. Verfügbare Master Keys zur Demo:
                                    <br>
                                    <strong>Free:</strong> WPR-FREE-2025-KEY1-ALPHA
                                    <br>
                                    <strong>Standard:</strong> WPR-STANDARD-2025-KEY1-ALPHA
                                    <br>
                                    <strong>Pro:</strong> WPR-PRO-2025-KEY1-DELTA
                                    <br>
                                    <strong>Pro+:</strong> WPR-PROPLUS-2025-KEY1-OMEGA
                                    <br>
                                    <strong>Ultimate:</strong> WPR-ULTIMATE-2025-KEY1-PSILONALPHA
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
