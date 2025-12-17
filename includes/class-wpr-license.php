<?php
/**
 * WP Restaurant Menu - License Management
 * Alle Lizenzen werden über den License-Server verwaltet!
 * Version: 2.0 - Sauber strukturiert mit 5 Preismodellen
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_License {
    
    // Konstanten für Lizenz-Typen
    const TYPE_FREE = 'free';
    const TYPE_FREE_PLUS = 'free_plus';
    const TYPE_PRO = 'pro';
    const TYPE_PRO_PLUS = 'pro_plus';
    const TYPE_ULTIMATE = 'ultimate';
    
    // Lizenz-Server URL
    private static function get_server_url() {
        return 'https://license-server.stb-srv.de/license-server/api.php';
    }
    
    /**
     * Fallback-Konfiguration für Pricing
     * Wird verwendet, wenn Server nicht erreichbar ist
     */
    private static function get_fallback_pricing() {
        return array(
            self::TYPE_FREE => array(
                'price' => 0,
                'currency' => '€',
                'label' => 'FREE',
                'max_items' => 20,
                'features' => array(),
            ),
            self::TYPE_FREE_PLUS => array(
                'price' => 15,
                'currency' => '€',
                'label' => 'FREE+',
                'max_items' => 60,
                'features' => array(),
            ),
            self::TYPE_PRO => array(
                'price' => 29,
                'currency' => '€',
                'label' => 'PRO',
                'max_items' => 200,
                'features' => array(),
            ),
            self::TYPE_PRO_PLUS => array(
                'price' => 49,
                'currency' => '€',
                'label' => 'PRO+',
                'max_items' => 200,
                'features' => array('dark_mode', 'cart'),
            ),
            self::TYPE_ULTIMATE => array(
                'price' => 79,
                'currency' => '€',
                'label' => 'ULTIMATE',
                'max_items' => 900,
                'features' => array('dark_mode', 'cart', 'unlimited_items'),
            ),
        );
    }
    
    /**
     * Pricing vom Server abrufen (mit Cache)
     * @return array Pricing-Daten für alle Pakete
     */
    public static function get_pricing() {
        // Cache prüfen (24h)
        $cached = get_transient('wpr_pricing_data');
        if ($cached !== false && is_array($cached) && count($cached) === 5) {
            return $cached;
        }
        
        // Server anfragen
        $server_url = self::get_server_url();
        $url = add_query_arg(array('action' => 'get_pricing'), $server_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'sslverify' => true,
        ));
        
        // Bei Fehler Fallback verwenden
        if (is_wp_error($response)) {
            return self::get_fallback_pricing();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Validierung
        if (!$data || !isset($data['success']) || !$data['success'] || !isset($data['pricing'])) {
            return self::get_fallback_pricing();
        }
        
        // Stelle sicher, dass alle 5 Pakete vorhanden sind
        $pricing = array_merge(self::get_fallback_pricing(), $data['pricing']);
        
        // Cache für 24h
        set_transient('wpr_pricing_data', $pricing, 86400);
        
        return $pricing;
    }
    
    /**
     * Lizenz-Info vom Server abrufen
     * @return array Lizenz-Informationen
     */
    public static function get_license_info() {
        $key = get_option('wpr_license_key', '');
        
        // Cache prüfen (24h)
        $cached = get_option('wpr_license_data');
        $last_check = get_option('wpr_license_last_check', 0);
        
        if ($cached && is_array($cached) && (time() - $last_check) < 86400) {
            return $cached;
        }
        
        // Wenn kein Key, direkt Free zurückgeben
        if (empty($key)) {
            return self::get_free_license();
        }
        
        // Server-Check durchführen
        $result = self::check_license_remote($key);
        
        if ($result && isset($result['valid']) && $result['valid']) {
            // Erfolgreicher Check - Cache aktualisieren
            update_option('wpr_license_data', $result);
            update_option('wpr_license_last_check', time());
            return $result;
        }
        
        // Bei Fehler: Fallback auf Free
        return self::get_free_license();
    }
    
    /**
     * Free-Lizenz Struktur
     * @return array
     */
    private static function get_free_license() {
        return array(
            'valid' => false,
            'type' => self::TYPE_FREE,
            'max_items' => 20,
            'expires' => '',
            'features' => array(),
        );
    }
    
    /**
     * Remote Lizenz-Check beim Server
     * @param string $key Lizenzschlüssel
     * @return array|false
     */
    private static function check_license_remote($key) {
        $server_url = self::get_server_url();
        $domain = $_SERVER['HTTP_HOST'];
        
        $url = add_query_arg(array(
            'action' => 'check_license',
            'key' => $key,
            'domain' => $domain,
        ), $server_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success'])) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Lizenz aktivieren
     * @param string $key Lizenzschlüssel
     * @return array Ergebnis mit success und message
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
        
        // Lizenz speichern
        update_option('wpr_license_key', $key);
        
        // Cache löschen
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        // Server-Check
        $info = self::check_license_remote($key);
        
        if ($info && isset($info['valid']) && $info['valid'] === true) {
            // Erfolg - Daten cachen
            update_option('wpr_license_data', $info);
            update_option('wpr_license_last_check', time());
            
            $type_label = self::get_license_label($info['type']);
            
            return array(
                'success' => true,
                'message' => "✅ {$type_label} Lizenz erfolgreich aktiviert!",
                'data' => $info,
            );
        } else {
            // Fehler - Key wieder entfernen
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
     * @return array
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
     * Helper: Lizenz-Label für Anzeige
     * @param string $type
     * @return string
     */
    private static function get_license_label($type) {
        $labels = array(
            self::TYPE_FREE => 'FREE',
            self::TYPE_FREE_PLUS => 'FREE+',
            self::TYPE_PRO => 'PRO',
            self::TYPE_PRO_PLUS => 'PRO+',
            self::TYPE_ULTIMATE => 'ULTIMATE',
        );
        
        return isset($labels[$type]) ? $labels[$type] : strtoupper($type);
    }
    
    /**
     * Prüfe ob Dark Mode verfügbar ist
     * @return bool
     */
    public static function has_dark_mode() {
        $license = self::get_license_info();
        return isset($license['features']) && in_array('dark_mode', $license['features']);
    }
    
    /**
     * Prüfe ob Warenkorb verfügbar ist
     * @return bool
     */
    public static function has_cart() {
        $license = self::get_license_info();
        return isset($license['features']) && in_array('cart', $license['features']);
    }
    
    /**
     * Cache manuell löschen
     */
    public static function clear_cache() {
        delete_transient('wpr_pricing_data');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
    }
    
    /**
     * Admin-Seite rendern
     */
    public static function render_page() {
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
        if (isset($_POST['wpr_clear_cache']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
            self::clear_cache();
            echo '<div class="notice notice-success"><p>🔄 Cache gelöscht! Daten werden neu geladen.</p></div>';
        }
        
        // Daten laden
        $license_info = self::get_license_info();
        $current_key = get_option('wpr_license_key', '');
        $pricing = self::get_pricing();
        $current_domain = $_SERVER['HTTP_HOST'];
        
        // Gerichte zählen
        $count = wp_count_posts('wpr_menu_item');
        $total_items = $count->publish + $count->draft + $count->pending;
        
        $max_items = isset($license_info['max_items']) ? $license_info['max_items'] : 20;
        $is_valid = isset($license_info['valid']) && $license_info['valid'];
        $license_label = self::get_license_label($license_info['type']);
        
        include __DIR__ . '/../admin/views/license-page.php';
    }
}
