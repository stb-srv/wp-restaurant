<?php
/**
 * Import/Export Handler
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

class WPR_Import_Export {

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'handle_export'));
    }
    
    public static function handle_export() {
        if (!isset($_GET['wpr_export_action']) || !isset($_GET['format'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung');
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wpr_export')) {
            wp_die('Sicherheitsüberprüfung fehlgeschlagen');
        }
        
        $format = sanitize_text_field($_GET['format']);
        self::export_menu($format);
        exit;
    }

    public static function render_page() {
        // Handle Import
        if (isset($_POST['wpr_import']) && check_admin_referer('wpr_import_action', 'wpr_import_nonce')) {
            $result = self::import_menu($_FILES['import_file']);
            if ($result['success']) {
                echo '<div class="notice notice-success"><p><strong>' . esc_html($result['message']) . '</strong></p></div>';
            } else {
                echo '<div class="notice notice-error"><p><strong>Fehler:</strong> ' . esc_html($result['message']) . '</p></div>';
            }
        }
        
        self::render_ui();
    }
    
    private static function render_ui() {
        $items_count = wp_count_posts('wpr_menu_item');
        $total = $items_count->publish + $items_count->draft;
        ?>
        <div class="wrap">
            <h1>📊 Import / Export</h1>
            
            <!-- Export Section -->
            <div style="background: #fff; padding: 25px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 900px;">
                <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    📝 Export
                    <span style="background: #d97706; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: normal;">
                        <?php echo esc_html($total); ?> Gerichte
                    </span>
                </h2>
                
                <p style="color: #666; margin-bottom: 20px;">
                    Exportiere alle deine Gerichte in verschiedenen Formaten. Die Datei wird automatisch heruntergeladen.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <?php 
                    $formats = array(
                        'csv' => array(
                            'icon' => '📊',
                            'name' => 'CSV',
                            'desc' => 'Excel, Google Sheets',
                            'color' => '#10b981'
                        ),
                        'json' => array(
                            'icon' => '📦',
                            'name' => 'JSON',
                            'desc' => 'Universal, API-ready',
                            'color' => '#3b82f6'
                        ),
                        'xml' => array(
                            'icon' => '📜',
                            'name' => 'XML',
                            'desc' => 'Standard-Format',
                            'color' => '#8b5cf6'
                        ),
                    );
                    
                    foreach ($formats as $format => $info) :
                        $export_url = wp_nonce_url(
                            add_query_arg(array(
                                'wpr_export_action' => '1',
                                'format' => $format
                            ), admin_url('edit.php?post_type=wpr_menu_item&page=wpr-import-export')),
                            'wpr_export'
                        );
                    ?>
                        <a href="<?php echo esc_url($export_url); ?>" 
                           class="wpr-export-card" 
                           style="display: block; padding: 20px; border: 2px solid #e5e7eb; border-radius: 8px; text-decoration: none; transition: all 0.2s; background: #fff;">
                            <div style="font-size: 32px; margin-bottom: 10px;"><?php echo $info['icon']; ?></div>
                            <h3 style="margin: 0 0 5px 0; color: <?php echo $info['color']; ?>; font-size: 18px;">
                                <?php echo esc_html($info['name']); ?>
                            </h3>
                            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                <?php echo esc_html($info['desc']); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <style>
                    .wpr-export-card:hover {
                        border-color: #d97706 !important;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
                    }
                </style>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #0284c7; border-radius: 4px;">
                    <strong style="color: #0369a1;">💡 Exportierte Daten umfassen:</strong>
                    <ul style="margin: 10px 0 0 20px; color: #0c4a6e;">
                        <li>Gericht-Nummer, Name, Beschreibung</li>
                        <li>Preis, Allergene</li>
                        <li>Kategorien, Menükarten</li>
                        <li>Vegan/Vegetarisch Markierungen</li>
                    </ul>
                </div>
            </div>
            
            <!-- Import Section -->
            <div style="background: #fff; padding: 25px; margin-top: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 900px;">
                <h2 style="margin-top: 0;">📰 Import</h2>
                
                <p style="color: #666; margin-bottom: 20px;">
                    Importiere Gerichte aus einer CSV, JSON oder XML Datei. Bestehende Gerichte werden nicht überschrieben.
                </p>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('wpr_import_action', 'wpr_import_nonce'); ?>
                    
                    <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 30px; text-align: center; margin-bottom: 20px; background: #f9fafb;">
                        <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                        <input 
                            type="file" 
                            name="import_file" 
                            accept=".csv,.json,.xml" 
                            required 
                            style="margin-bottom: 10px;"
                        />
                        <p style="color: #6b7280; font-size: 14px; margin: 10px 0 0 0;">
                            Unterstützte Formate: CSV, JSON, XML
                        </p>
                    </div>
                    
                    <button type="submit" name="wpr_import" class="button button-primary button-large" style="display: flex; align-items: center; gap: 8px; margin: 0 auto;">
                        <span class="dashicons dashicons-upload"></span>
                        Datei importieren
                    </button>
                </form>
                
                <div style="margin-top: 25px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                    <strong style="color: #92400e;">⚠️ Wichtige Hinweise:</strong>
                    <ul style="margin: 10px 0 0 20px; color: #78350f;">
                        <li>Mache vor dem Import ein Backup (Export)</li>
                        <li>CSV-Dateien müssen UTF-8 codiert sein</li>
                        <li>Duplikate werden automatisch erkannt</li>
                    </ul>
                </div>
            </div>
            
            <!-- CSV Template -->
            <div style="background: #fff; padding: 25px; margin-top: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 900px;">
                <h2 style="margin-top: 0;">📝 CSV-Vorlage</h2>
                
                <p style="color: #666; margin-bottom: 15px;">
                    Deine CSV-Datei sollte diese Spalten enthalten (in dieser Reihenfolge):
                </p>
                
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px; font-family: monospace; overflow-x: auto;">
                    <code>dish_number,title,description,price,allergens,category,menu,vegetarian,vegan</code>
                </div>
                
                <p style="color: #666; margin: 15px 0; font-size: 14px;">
                    <strong>Beispiel-Zeile:</strong>
                </p>
                
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; overflow-x: auto;">
                    <code>12,Pizza Margherita,Klassische Pizza mit Tomaten und Mozzarella,8.50,"A,C,G",Hauptgerichte,Hauptspeisekarte,1,0</code>
                </div>
            </div>
        </div>
        <?php
    }
    
    private static function export_menu($format) {
        $items = get_posts(array(
            'post_type' => 'wpr_menu_item',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        $data = array();
        foreach ($items as $item) {
            $categories = wp_get_post_terms($item->ID, 'wpr_category', array('fields' => 'names'));
            $menus = wp_get_post_terms($item->ID, 'wpr_menu_list', array('fields' => 'names'));
            
            $data[] = array(
                'dish_number' => get_post_meta($item->ID, '_wpr_dish_number', true),
                'title' => $item->post_title,
                'description' => wp_strip_all_tags($item->post_content),
                'price' => get_post_meta($item->ID, '_wpr_price', true),
                'allergens' => get_post_meta($item->ID, '_wpr_allergens', true),
                'category' => !empty($categories) ? implode(', ', $categories) : '',
                'menu' => !empty($menus) ? implode(', ', $menus) : '',
                'vegetarian' => get_post_meta($item->ID, '_wpr_vegetarian', true),
                'vegan' => get_post_meta($item->ID, '_wpr_vegan', true),
            );
        }
        
        $filename = 'restaurant-menu-' . date('Y-m-d-His');
        
        // Clear any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        switch ($format) {
            case 'csv':
                self::export_csv($data, $filename);
                break;
            case 'json':
                self::export_json($data, $filename);
                break;
            case 'xml':
                self::export_xml($data, $filename);
                break;
        }
    }
    
    private static function export_csv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM für Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, array('dish_number', 'title', 'description', 'price', 'allergens', 'category', 'menu', 'vegetarian', 'vegan'), ';');
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
    }
    
    private static function export_json($data, $filename) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode(array(
            'exported_at' => date('Y-m-d H:i:s'),
            'total_items' => count($data),
            'items' => $data
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private static function export_xml($data, $filename) {
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><menu></menu>');
        $xml->addAttribute('exported_at', date('Y-m-d H:i:s'));
        $xml->addAttribute('total_items', count($data));
        
        foreach ($data as $item) {
            $dish = $xml->addChild('dish');
            foreach ($item as $key => $value) {
                $dish->addChild($key, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
            }
        }
        
        echo $xml->asXML();
    }
    
    private static function import_menu($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => 'Datei-Upload fehlgeschlagen.');
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'csv':
                return self::import_csv($file['tmp_name']);
            case 'json':
                return self::import_json($file['tmp_name']);
            case 'xml':
                return self::import_xml($file['tmp_name']);
            default:
                return array('success' => false, 'message' => 'Nicht unterstütztes Dateiformat.');
        }
    }
    
    private static function import_csv($filepath) {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return array('success' => false, 'message' => 'Datei konnte nicht geöffnet werden.');
        }
        
        // Detect delimiter
        $first_line = fgets($handle);
        rewind($handle);
        $delimiter = (strpos($first_line, ';') !== false) ? ';' : ',';
        
        $header = fgetcsv($handle, 0, $delimiter);
        $imported = 0;
        $skipped = 0;
        
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row[1])) continue;
            
            $result = self::create_menu_item(array(
                'dish_number' => $row[0] ?? '',
                'title' => $row[1] ?? '',
                'description' => $row[2] ?? '',
                'price' => $row[3] ?? '',
                'allergens' => $row[4] ?? '',
                'category' => $row[5] ?? '',
                'menu' => $row[6] ?? '',
                'vegetarian' => $row[7] ?? '0',
                'vegan' => $row[8] ?? '0',
            ));
            
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        fclose($handle);
        
        return array(
            'success' => true,
            'message' => "Import erfolgreich! {$imported} Gerichte importiert, {$skipped} übersprungen."
        );
    }
    
    private static function import_json($filepath) {
        $content = file_get_contents($filepath);
        $json = json_decode($content, true);
        
        if (!$json) {
            return array('success' => false, 'message' => 'Ungültige JSON-Datei.');
        }
        
        // Handle both formats: with wrapper or direct array
        $data = isset($json['items']) ? $json['items'] : $json;
        
        if (!is_array($data)) {
            return array('success' => false, 'message' => 'Ungültiges JSON-Format.');
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($data as $item) {
            if (empty($item['title'])) continue;
            
            $result = self::create_menu_item($item);
            
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        return array(
            'success' => true,
            'message' => "Import erfolgreich! {$imported} Gerichte importiert, {$skipped} übersprungen."
        );
    }
    
    private static function import_xml($filepath) {
        $xml = simplexml_load_file($filepath);
        
        if (!$xml) {
            return array('success' => false, 'message' => 'Ungültige XML-Datei.');
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($xml->dish as $dish) {
            if (empty($dish->title)) continue;
            
            $result = self::create_menu_item(array(
                'dish_number' => (string)$dish->dish_number,
                'title' => (string)$dish->title,
                'description' => (string)$dish->description,
                'price' => (string)$dish->price,
                'allergens' => (string)$dish->allergens,
                'category' => (string)$dish->category,
                'menu' => (string)$dish->menu,
                'vegetarian' => (string)$dish->vegetarian,
                'vegan' => (string)$dish->vegan,
            ));
            
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        return array(
            'success' => true,
            'message' => "Import erfolgreich! {$imported} Gerichte importiert, {$skipped} übersprungen."
        );
    }
    
    private static function create_menu_item($data) {
        $existing = get_page_by_title($data['title'], OBJECT, 'wpr_menu_item');
        if ($existing) {
            return false;
        }
        
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['description']),
            'post_type' => 'wpr_menu_item',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        update_post_meta($post_id, '_wpr_dish_number', sanitize_text_field($data['dish_number']));
        update_post_meta($post_id, '_wpr_price', sanitize_text_field($data['price']));
        update_post_meta($post_id, '_wpr_allergens', sanitize_text_field($data['allergens']));
        update_post_meta($post_id, '_wpr_vegetarian', $data['vegetarian'] === '1' ? '1' : '0');
        update_post_meta($post_id, '_wpr_vegan', $data['vegan'] === '1' ? '1' : '0');
        
        if (!empty($data['category'])) {
            $cats = array_map('trim', explode(',', $data['category']));
            wp_set_post_terms($post_id, $cats, 'wpr_category');
        }
        
        if (!empty($data['menu'])) {
            $menus = array_map('trim', explode(',', $data['menu']));
            wp_set_post_terms($post_id, $menus, 'wpr_menu_list');
        }
        
        return true;
    }
}

// Initialize
WPR_Import_Export::init();
