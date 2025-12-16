<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Plugin URI: https://github.com/stb-srv/wp-restaurant
 * Description: Modernes WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten
 * Version: 1.5.3
 * Author: STB-SRV
 * License: GPL-2.0+
 * Text Domain: wp-restaurant-menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

define('WP_RESTAURANT_MENU_VERSION', '1.5.3');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-import-export.php';
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-license.php';

function wpr_activate() {
    wpr_register_post_type();
    wpr_register_taxonomies();
    wpr_create_default_allergens();
    flush_rewrite_rules();
    
    if (!get_option('wpr_settings')) {
        add_option('wpr_settings', array(
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'show_images' => 'yes',
            'image_position' => 'left',
            'show_search' => 'yes',
            'group_by_category' => 'yes',
            'grid_columns' => '2',
        ));
    } else {
        $settings = get_option('wpr_settings');
        if (!isset($settings['group_by_category'])) {
            $settings['group_by_category'] = 'yes';
        }
        if (!isset($settings['grid_columns'])) {
            $settings['grid_columns'] = '2';
        }
        update_option('wpr_settings', $settings);
    }
    
    add_option('wpr_version', WP_RESTAURANT_MENU_VERSION);
}
register_activation_hook(__FILE__, 'wpr_activate');

function wpr_check_settings() {
    $settings = get_option('wpr_settings');
    if ($settings) {
        $updated = false;
        if (!isset($settings['group_by_category'])) {
            $settings['group_by_category'] = 'yes';
            $updated = true;
        }
        if (!isset($settings['grid_columns'])) {
            $settings['grid_columns'] = '2';
            $updated = true;
        }
        if ($updated) {
            update_option('wpr_settings', $settings);
        }
    }
}
add_action('plugins_loaded', 'wpr_check_settings');

// Einmalige Bereinigung fehlerhafter Allergene
function wpr_clean_allergens_once() {
    if (get_option('wpr_allergens_cleaned')) return;
    
    // Alle Allergene holen
    $all_allergens = get_terms(array(
        'taxonomy' => 'wpr_allergen',
        'hide_empty' => false,
    ));
    
    $valid_names = array(
        'A - Glutenhaltiges Getreide',
        'B - Krebstiere',
        'C - Eier',
        'D - Fisch',
        'E - Erdnüsse',
        'F - Soja',
        'G - Milch/Laktose',
        'H - Schalenfrüchte',
        'L - Sellerie',
        'M - Senf',
        'N - Sesamsamen',
        'O - Schwefeldioxid',
        'P - Lupinen',
        'R - Weichtiere',
    );
    
    // Lösche alle Terms, die nur Zahlen sind oder nicht in valid_names
    if (!empty($all_allergens) && !is_wp_error($all_allergens)) {
        foreach ($all_allergens as $term) {
            // Wenn Name nur aus Zahlen besteht oder nicht in der Liste
            if (is_numeric($term->name) || !in_array($term->name, $valid_names)) {
                wp_delete_term($term->term_id, 'wpr_allergen');
            }
        }
    }
    
    // Markiere als bereinigt
    update_option('wpr_allergens_cleaned', true);
}
add_action('admin_init', 'wpr_clean_allergens_once');

function wpr_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wpr_deactivate');

function wpr_register_post_type() {
    register_post_type('wpr_menu_item', array(
        'labels' => array(
            'name' => 'Menüpunkte',
            'singular_name' => 'Menüpunkt',
            'add_new_item' => 'Neues Gericht hinzufügen',
            'edit_item' => 'Gericht bearbeiten',
            'menu_name' => 'Restaurant Menu',
            'all_items' => 'Alle Gerichte',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-food',
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => false,
        'rewrite' => false,
    ));
}
add_action('init', 'wpr_register_post_type');

function wpr_register_taxonomies() {
    register_taxonomy('wpr_category', 'wpr_menu_item', array(
        'labels' => array(
            'name' => 'Kategorien',
            'singular_name' => 'Kategorie',
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'public' => false,
        'rewrite' => false,
    ));
    
    register_taxonomy('wpr_menu_list', 'wpr_menu_item', array(
        'labels' => array(
            'name' => 'Menükarten',
            'singular_name' => 'Menükarte',
        ),
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'public' => false,
        'rewrite' => false,
    ));
    
    register_taxonomy('wpr_allergen', 'wpr_menu_item', array(
        'labels' => array(
            'name' => 'Allergene',
            'singular_name' => 'Allergen',
            'add_new_item' => 'Neues Allergen hinzufügen',
            'edit_item' => 'Allergen bearbeiten',
            'menu_name' => 'Allergene',
        ),
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => false,
        'public' => false,
        'rewrite' => false,
        'meta_box_cb' => false,
    ));
}
add_action('init', 'wpr_register_taxonomies');

function wpr_create_default_allergens() {
    $allergens = array(
        'a' => array('name' => 'A - Glutenhaltiges Getreide', 'icon' => '🌾'),
        'b' => array('name' => 'B - Krebstiere', 'icon' => '🦀'),
        'c' => array('name' => 'C - Eier', 'icon' => '🥚'),
        'd' => array('name' => 'D - Fisch', 'icon' => '🐟'),
        'e' => array('name' => 'E - Erdnüsse', 'icon' => '🥜'),
        'f' => array('name' => 'F - Soja', 'icon' => '🌱'),
        'g' => array('name' => 'G - Milch/Laktose', 'icon' => '🥛'),
        'h' => array('name' => 'H - Schalenfrüchte', 'icon' => '🌰'),
        'l' => array('name' => 'L - Sellerie', 'icon' => '🥬'),
        'm' => array('name' => 'M - Senf', 'icon' => '🍯'),
        'n' => array('name' => 'N - Sesamsamen', 'icon' => '🌾'),
        'o' => array('name' => 'O - Schwefeldioxid', 'icon' => '🧪'),
        'p' => array('name' => 'P - Lupinen', 'icon' => '🌺'),
        'r' => array('name' => 'R - Weichtiere', 'icon' => '🦐'),
    );
    
    foreach ($allergens as $slug => $data) {
        // Prüfe zuerst ob Term mit diesem Namen existiert
        $existing = get_term_by('name', $data['name'], 'wpr_allergen');
        
        if (!$existing) {
            // Erstelle nur wenn nicht vorhanden
            $term = wp_insert_term($data['name'], 'wpr_allergen', array(
                'slug' => $slug,
            ));
            
            if (!is_wp_error($term)) {
                add_term_meta($term['term_id'], 'icon', $data['icon'], true);
            }
        } else {
            // Update Icon falls fehlend
            $icon = get_term_meta($existing->term_id, 'icon', true);
            if (empty($icon)) {
                update_term_meta($existing->term_id, 'icon', $data['icon']);
            }
        }
    }
}

function wpr_enqueue_styles() {
    if (is_singular() || is_page()) {
        wp_enqueue_style(
            'wpr-menu-styles',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-styles.css',
            array(),
            WP_RESTAURANT_MENU_VERSION
        );
        
        wp_enqueue_script(
            'wpr-menu-search',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-search.js',
            array(),
            WP_RESTAURANT_MENU_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wpr-menu-accordion',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-accordion.js',
            array(),
            WP_RESTAURANT_MENU_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'wpr_enqueue_styles');

function wpr_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=wpr_menu_item',
        'Einstellungen',
        '⚙️ Einstellungen',
        'manage_options',
        'wpr-settings',
        'wpr_render_settings_page'
    );
}
add_action('admin_menu', 'wpr_add_settings_page');

function wpr_add_license_page() {
    add_submenu_page(
        'edit.php?post_type=wpr_menu_item',
        'Lizenz',
        '🔑 Lizenz',
        'manage_options',
        'wpr-license',
        array('WPR_License', 'render_page')
    );
}
add_action('admin_menu', 'wpr_add_license_page');

function wpr_add_import_export_page() {
    add_submenu_page(
        'edit.php?post_type=wpr_menu_item',
        'Import / Export',
        '📊 Import / Export',
        'manage_options',
        'wpr-import-export',
        'wpr_render_import_export_page'
    );
}
add_action('admin_menu', 'wpr_add_import_export_page');

function wpr_render_settings_page() {
    if (isset($_POST['wpr_save_settings']) && check_admin_referer('wpr_settings_save', 'wpr_settings_nonce')) {
        $settings = array(
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'currency_position' => sanitize_text_field($_POST['currency_position']),
            'show_images' => sanitize_text_field($_POST['show_images']),
            'image_position' => sanitize_text_field($_POST['image_position']),
            'show_search' => sanitize_text_field($_POST['show_search']),
            'group_by_category' => sanitize_text_field($_POST['group_by_category']),
            'grid_columns' => sanitize_text_field($_POST['grid_columns']),
        );
        update_option('wpr_settings', $settings);
        echo '<div class="notice notice-success"><p><strong>Einstellungen gespeichert!</strong></p></div>';
    }
    
    $settings = get_option('wpr_settings', array(
        'currency_symbol' => '€',
        'currency_position' => 'after',
        'show_images' => 'yes',
        'image_position' => 'left',
        'show_search' => 'yes',
        'group_by_category' => 'yes',
        'grid_columns' => '2',
    ));
    ?>
    <div class="wrap">
        <h1>⚙️ Restaurant Menü Einstellungen</h1>
        
        <div style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
            <form method="post">
                <?php wp_nonce_field('wpr_settings_save', 'wpr_settings_nonce'); ?>
                
                <h2 style="margin-top: 0;">Währungseinstellungen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="currency_symbol">Währungssymbol</label></th>
                        <td>
                            <select name="currency_symbol" id="currency_symbol" style="min-width: 200px;">
                                <option value="€" <?php selected($settings['currency_symbol'], '€'); ?>>€ (Euro-Symbol)</option>
                                <option value="EUR" <?php selected($settings['currency_symbol'], 'EUR'); ?>>EUR</option>
                                <option value="EURO" <?php selected($settings['currency_symbol'], 'EURO'); ?>>EURO</option>
                                <option value="$" <?php selected($settings['currency_symbol'], '$'); ?>>$ (Dollar)</option>
                                <option value="£" <?php selected($settings['currency_symbol'], '£'); ?>>£ (Pfund)</option>
                                <option value="CHF" <?php selected($settings['currency_symbol'], 'CHF'); ?>>CHF</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="currency_position">Position des Symbols</label></th>
                        <td>
                            <select name="currency_position" id="currency_position" style="min-width: 200px;">
                                <option value="after" <?php selected($settings['currency_position'], 'after'); ?>>Nach dem Preis (12,50 €)</option>
                                <option value="before" <?php selected($settings['currency_position'], 'before'); ?>>Vor dem Preis (€ 12,50)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2>Bild-Einstellungen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="show_images">Produktbilder anzeigen</label></th>
                        <td>
                            <select name="show_images" id="show_images" style="min-width: 200px;">
                                <option value="yes" <?php selected($settings['show_images'], 'yes'); ?>>Ja, Bilder anzeigen</option>
                                <option value="no" <?php selected($settings['show_images'], 'no'); ?>>Nein, keine Bilder</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="image_position">Bild-Position</label></th>
                        <td>
                            <select name="image_position" id="image_position" style="min-width: 200px;">
                                <option value="top" <?php selected($settings['image_position'], 'top'); ?>>Über dem Text</option>
                                <option value="left" <?php selected($settings['image_position'], 'left'); ?>>Links (neben dem Text)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2>Layout-Einstellungen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="show_search">Suchfunktion anzeigen</label></th>
                        <td>
                            <select name="show_search" id="show_search" style="min-width: 200px;">
                                <option value="yes" <?php selected($settings['show_search'], 'yes'); ?>>Ja, Suche aktivieren</option>
                                <option value="no" <?php selected($settings['show_search'], 'no'); ?>>Nein, keine Suche</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="group_by_category">Nach Kategorien gruppieren</label></th>
                        <td>
                            <select name="group_by_category" id="group_by_category" style="min-width: 200px;">
                                <option value="yes" <?php selected($settings['group_by_category'], 'yes'); ?>>Ja, Accordion-Ansicht</option>
                                <option value="no" <?php selected($settings['group_by_category'], 'no'); ?>>Nein, Grid-Ansicht</option>
                            </select>
                            <p class="description">Aktiviert aufklappbare Kategorien für eine übersichtliche Menüdarstellung.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="grid_columns">Spalten-Layout</label></th>
                        <td>
                            <select name="grid_columns" id="grid_columns" style="min-width: 200px;">
                                <option value="1" <?php selected($settings['grid_columns'], '1'); ?>>1 Spalte (untereinander)</option>
                                <option value="2" <?php selected($settings['grid_columns'], '2'); ?>>2 Spalten (Desktop)</option>
                                <option value="3" <?php selected($settings['grid_columns'], '3'); ?>>3 Spalten (breit)</option>
                            </select>
                            <p class="description">Anzahl der Spalten auf Desktop-Geräten. Smartphones zeigen automatisch immer 1 Spalte.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="wpr_save_settings" class="button button-primary button-large">
                        💾 Einstellungen speichern
                    </button>
                </p>
            </form>
        </div>
    </div>
    <?php
}

function wpr_render_import_export_page() {
    WPR_Import_Export::render_page();
}

function wpr_format_price($price) {
    if (empty($price)) return '';
    $settings = get_option('wpr_settings', array('currency_symbol' => '€', 'currency_position' => 'after'));
    return $settings['currency_position'] === 'before' ? $settings['currency_symbol'] . ' ' . $price : $price . ' ' . $settings['currency_symbol'];
}

function wpr_add_meta_boxes() {
    add_meta_box('wpr_details', 'Gericht-Details', 'wpr_render_meta_box', 'wpr_menu_item', 'normal', 'high');
    add_meta_box('wpr_allergens', 'Allergene', 'wpr_allergen_meta_box', 'wpr_menu_item', 'side', 'default');
}
add_action('add_meta_boxes', 'wpr_add_meta_boxes');

function wpr_render_meta_box($post) {
    wp_nonce_field('wpr_save_meta', 'wpr_meta_nonce');
    
    $dish_number = get_post_meta($post->ID, '_wpr_dish_number', true);
    $price = get_post_meta($post->ID, '_wpr_price', true);
    $vegan = get_post_meta($post->ID, '_wpr_vegan', true);
    $vegetarian = get_post_meta($post->ID, '_wpr_vegetarian', true);
    $settings = get_option('wpr_settings', array('currency_symbol' => '€'));
    ?>
    <div style="padding: 10px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
            <div>
                <label><strong>Gericht-Nummer:</strong></label><br>
                <input type="text" name="wpr_dish_number" value="<?php echo esc_attr($dish_number); ?>" style="width: 100%;" placeholder="z.B. 12 oder A5">
                <span style="color: #666; font-size: 0.9em; display: block; margin-top: 5px;">
                    Optional: Eindeutige Nummer für dieses Gericht
                </span>
            </div>
            
            <div>
                <label><strong>Preis:</strong></label><br>
                <input type="text" name="wpr_price" value="<?php echo esc_attr($price); ?>" style="width: 100%;" placeholder="z.B. 12,50">
                <span style="color: #666; font-size: 0.9em; display: block; margin-top: 5px;">
                    Nur die Zahl. Währung (<?php echo esc_html($settings['currency_symbol']); ?>) wird automatisch hinzugefügt.
                </span>
            </div>
        </div>
        
        <div style="padding: 15px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
            <label style="font-weight: 600; margin-bottom: 10px; display: block; color: #374151;">Ernährungsweise:</label>
            <div style="display: flex; gap: 15px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: #fff; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" name="wpr_vegetarian" value="1" <?php checked($vegetarian, '1'); ?>>
                    <span>🌱 Vegetarisch</span>
                </label>
                
                <label style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: #fff; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" name="wpr_vegan" value="1" <?php checked($vegan, '1'); ?>>
                    <span>🌿 Vegan</span>
                </label>
            </div>
        </div>
    </div>
    <?php
}

function wpr_allergen_meta_box($post) {
    $allergens = get_terms(array(
        'taxonomy' => 'wpr_allergen',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    
    $post_allergens = wp_get_post_terms($post->ID, 'wpr_allergen', array('fields' => 'ids'));
    if (is_wp_error($post_allergens)) {
        $post_allergens = array();
    }
    ?>
    <div style="padding: 10px;">
        <?php if (empty($allergens) || is_wp_error($allergens)) : ?>
            <p style="color: #666; font-size: 13px;">Keine Allergene gefunden.</p>
        <?php else : ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allergens as $allergen) : 
                    $icon = get_term_meta($allergen->term_id, 'icon', true);
                ?>
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px; margin-bottom: 5px; background: #f9fafb; border-radius: 4px; cursor: pointer; transition: all 0.2s;"
                           onmouseover="this.style.background='#f3f4f6';" 
                           onmouseout="this.style.background='#f9fafb';">
                        <input 
                            type="checkbox" 
                            name="tax_input[wpr_allergen][]" 
                            value="<?php echo esc_attr($allergen->term_id); ?>"
                            <?php checked(in_array($allergen->term_id, $post_allergens)); ?>
                        />
                        <?php if ($icon) : ?>
                            <span style="font-size: 16px;"><?php echo esc_html($icon); ?></span>
                        <?php endif; ?>
                        <span style="font-size: 13px;"><?php echo esc_html($allergen->name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function wpr_save_meta($post_id) {
    if (!isset($_POST['wpr_meta_nonce']) || !wp_verify_nonce($_POST['wpr_meta_nonce'], 'wpr_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['wpr_dish_number'])) {
        update_post_meta($post_id, '_wpr_dish_number', sanitize_text_field($_POST['wpr_dish_number']));
    }
    if (isset($_POST['wpr_price'])) {
        update_post_meta($post_id, '_wpr_price', sanitize_text_field($_POST['wpr_price']));
    }
    update_post_meta($post_id, '_wpr_vegetarian', isset($_POST['wpr_vegetarian']) ? '1' : '0');
    update_post_meta($post_id, '_wpr_vegan', isset($_POST['wpr_vegan']) ? '1' : '0');
}
add_action('save_post', 'wpr_save_meta');

function wpr_check_item_limit_before_save($post_id) {
    // Nur für Menüpunkte
    if (get_post_type($post_id) !== 'wpr_menu_item') return;
    
    // Nur bei neuen Gerichten prüfen (nicht beim Bearbeiten)
    $post_status = get_post_status($post_id);
    if ($post_status !== 'auto-draft' && $post_status !== 'draft') {
        // Wenn es bereits veröffentlicht ist, nicht blockieren
        $old_status = get_post_meta($post_id, '_wpr_old_status', true);
        if ($old_status === 'publish') {
            return;
        }
    }
    
    // Lizenz-Infos holen
    $license = WPR_License::get_license_info();
    
    // Prüfe ob unbegrenzt (unlimited_items Feature ODER max_items >= 999)
    $is_unlimited = $license['valid'] && (
        in_array('unlimited_items', $license['features']) || 
        $license['max_items'] >= 999
    );
    
    // Wenn unbegrenzt, kein Limit
    if ($is_unlimited) return;
    
    // Aktuelle Anzahl zählen
    $count = wp_count_posts('wpr_menu_item');
    $total = $count->publish + $count->draft + $count->pending;
    
    // Limit erreicht?
    if ($total >= $license['max_items']) {
        $message = $license['valid'] 
            ? '<h1>🔒 Lizenz-Limit erreicht</h1>' .
              '<p>Ihre Lizenz erlaubt maximal <strong>' . $license['max_items'] . ' Gerichte</strong>.</p>' .
              '<p>Sie haben bereits <strong>' . $total . ' Gerichte</strong> angelegt.</p>' .
              '<p><a href="' . admin_url('edit.php?post_type=wpr_menu_item&page=wpr-license') . '" class="button button-primary">🔑 Lizenz upgraden</a></p>'
            : '<h1>🔒 Limit erreicht</h1>' .
              '<p>Sie haben das Maximum von <strong>' . $license['max_items'] . ' Gerichten</strong> erreicht.</p>' .
              '<p><a href="' . admin_url('edit.php?post_type=wpr_menu_item&page=wpr-license') . '" class="button button-primary">🔑 Jetzt Pro-Lizenz aktivieren</a></p>';
        
        wp_die(
            $message,
            'Limit erreicht',
            array('back_link' => true)
        );
    }
}
add_action('save_post', 'wpr_check_item_limit_before_save', 1);

// Speichere alten Status vor Update
function wpr_remember_post_status($post_id) {
    if (get_post_type($post_id) === 'wpr_menu_item') {
        $status = get_post_status($post_id);
        update_post_meta($post_id, '_wpr_old_status', $status);
    }
}
add_action('pre_post_update', 'wpr_remember_post_status');

function wpr_admin_notices() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'wpr_menu_item') return;
    
    $license = WPR_License::get_license_info();
    
    // Prüfe ob unbegrenzt
    $is_unlimited = $license['valid'] && (
        in_array('unlimited_items', $license['features']) || 
        $license['max_items'] >= 999
    );
    
    if ($is_unlimited) return;
    
    $count = wp_count_posts('wpr_menu_item');
    $total = $count->publish + $count->draft + $count->pending;
    $remaining = $license['max_items'] - $total;
    
    if ($remaining <= 5 && $remaining > 0) {
        $upgrade_text = $license['valid'] 
            ? 'Lizenz upgraden'
            : 'Jetzt upgraden';
        
        echo '<div class="notice notice-warning">';
        echo '<p><strong>⚠️ Achtung:</strong> Sie haben noch <strong>' . $remaining . ' von ' . $license['max_items'] . ' Gerichten</strong> verfügbar. ';
        echo '<a href="' . admin_url('edit.php?post_type=wpr_menu_item&page=wpr-license') . '">' . $upgrade_text . '</a> für mehr Gerichte.</p>';
        echo '</div>';
    } elseif ($remaining <= 0) {
        $limit_text = $license['valid']
            ? '<strong>🔒 Lizenz-Limit erreicht:</strong> Sie haben das Maximum Ihrer Lizenz von <strong>' . $license['max_items'] . ' Gerichten</strong> erreicht.'
            : '<strong>🔒 Limit erreicht:</strong> Sie haben das Maximum von <strong>' . $license['max_items'] . ' Gerichten</strong> erreicht.';
        
        $button_text = $license['valid'] 
            ? '🔑 Lizenz upgraden'
            : '🔑 Pro-Lizenz aktivieren';
        
        echo '<div class="notice notice-error">';
        echo '<p>' . $limit_text . ' ';
        echo '<a href="' . admin_url('edit.php?post_type=wpr_menu_item&page=wpr-license') . '" class="button button-primary" style="margin-left: 10px;">' . $button_text . '</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'wpr_admin_notices');

function wpr_menu_shortcode($atts) {
    $atts = shortcode_atts(array(
        'menu' => '',
        'category' => '',
        'columns' => '',
    ), $atts);
    
    $settings = get_option('wpr_settings', array(
        'show_images' => 'yes',
        'image_position' => 'left',
        'show_search' => 'yes',
        'group_by_category' => 'yes',
        'grid_columns' => '2',
    ));
    
    $show_images = $settings['show_images'] === 'yes';
    $image_position = $settings['image_position'];
    $show_search = $settings['show_search'] === 'yes';
    $group_by_category = isset($settings['group_by_category']) ? $settings['group_by_category'] === 'yes' : true;
    
    $columns = !empty($atts['columns']) ? max(1, min(3, intval($atts['columns']))) : intval($settings['grid_columns']);
    
    $all_categories = get_terms(array(
        'taxonomy' => 'wpr_category',
        'hide_empty' => true,
    ));
    
    ob_start();
    ?>
    <div class="wpr-menu-wrapper">
        <?php if ($show_search) : ?>
            <div class="wpr-search-bar">
                <div class="wpr-search-input-wrapper">
                    <input 
                        type="text" 
                        class="wpr-search-input" 
                        placeholder="🔍 Suche nach Gerichten..." 
                        readonly
                    />
                </div>
            </div>
            
            <div class="wpr-search-overlay">
                <div class="wpr-search-overlay-content">
                    <div class="wpr-overlay-search-wrapper">
                        <div class="wpr-overlay-search-header">
                            <input 
                                type="text" 
                                class="wpr-overlay-search-input" 
                                placeholder="Suche nach Gerichten..." 
                            />
                            <button class="wpr-search-close">✕</button>
                        </div>
                        
                        <?php if (!empty($all_categories) && !is_wp_error($all_categories)) : ?>
                            <div class="wpr-category-filter">
                                <button class="wpr-filter-btn active" data-category="all">Alle</button>
                                <?php foreach ($all_categories as $cat) : ?>
                                    <button class="wpr-filter-btn" data-category="<?php echo esc_attr($cat->slug); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <span class="wpr-results-count"></span>
                    </div>
                    
                    <div class="wpr-search-results-grid"></div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($group_by_category) : ?>
            <?php echo wpr_render_accordion_menu($atts, $show_images, $image_position, $columns); ?>
        <?php else : ?>
            <?php echo wpr_render_grid_menu($atts, $show_images, $image_position, $columns); ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function wpr_render_accordion_menu($atts, $show_images, $image_position, $columns) {
    $args = array(
        'post_type' => 'wpr_menu_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    $tax_query = array();
    if (!empty($atts['menu'])) {
        $tax_query[] = array('taxonomy' => 'wpr_menu_list', 'field' => 'slug', 'terms' => $atts['menu']);
    }
    if (!empty($atts['category'])) {
        $tax_query[] = array('taxonomy' => 'wpr_category', 'field' => 'slug', 'terms' => $atts['category']);
    }
    if (!empty($tax_query)) $args['tax_query'] = $tax_query;
    
    $all_items = get_posts($args);
    
    if (empty($all_items)) {
        return '<p class="wpr-no-items">Keine Gerichte gefunden.</p>';
    }
    
    $categories = get_terms(array(
        'taxonomy' => 'wpr_category',
        'hide_empty' => true,
    ));
    
    if (empty($categories) || is_wp_error($categories)) {
        return wpr_render_grid_menu($atts, $show_images, $image_position, $columns);
    }
    
    $items_by_category = array();
    $uncategorized = array();
    
    foreach ($all_items as $item) {
        $item_cats = wp_get_post_terms($item->ID, 'wpr_category');
        if (empty($item_cats) || is_wp_error($item_cats)) {
            $uncategorized[] = $item;
        } else {
            foreach ($item_cats as $cat) {
                if (!isset($items_by_category[$cat->term_id])) {
                    $items_by_category[$cat->term_id] = array(
                        'term' => $cat,
                        'items' => array()
                    );
                }
                $items_by_category[$cat->term_id]['items'][] = $item;
            }
        }
    }
    
    ob_start();
    ?>
    <div class="wpr-accordion-menu">
        <?php foreach ($items_by_category as $cat_id => $data) : ?>
            <div class="wpr-accordion-section">
                <button class="wpr-accordion-header" data-category-id="<?php echo esc_attr($cat_id); ?>">
                    <span class="wpr-accordion-title"><?php echo esc_html($data['term']->name); ?></span>
                    <span class="wpr-accordion-count"><?php echo count($data['items']); ?> Gerichte</span>
                    <span class="wpr-accordion-icon">▼</span>
                </button>
                
                <div class="wpr-accordion-content">
                    <div class="wpr-accordion-grid wpr-grid-columns-<?php echo esc_attr($columns); ?>">
                        <?php foreach ($data['items'] as $item) : ?>
                            <?php echo wpr_render_single_item($item, $show_images, $image_position); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (!empty($uncategorized)) : ?>
            <div class="wpr-accordion-section">
                <button class="wpr-accordion-header" data-category-id="uncategorized">
                    <span class="wpr-accordion-title">Weitere Gerichte</span>
                    <span class="wpr-accordion-count"><?php echo count($uncategorized); ?> Gerichte</span>
                    <span class="wpr-accordion-icon">▼</span>
                </button>
                
                <div class="wpr-accordion-content">
                    <div class="wpr-accordion-grid wpr-grid-columns-<?php echo esc_attr($columns); ?>">
                        <?php foreach ($uncategorized as $item) : ?>
                            <?php echo wpr_render_single_item($item, $show_images, $image_position); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function wpr_render_grid_menu($atts, $show_images, $image_position, $columns) {
    $args = array(
        'post_type' => 'wpr_menu_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    $tax_query = array();
    if (!empty($atts['menu'])) {
        $tax_query[] = array('taxonomy' => 'wpr_menu_list', 'field' => 'slug', 'terms' => $atts['menu']);
    }
    if (!empty($atts['category'])) {
        $tax_query[] = array('taxonomy' => 'wpr_category', 'field' => 'slug', 'terms' => $atts['category']);
    }
    if (!empty($tax_query)) $args['tax_query'] = $tax_query;
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p class="wpr-no-items">Keine Gerichte gefunden.</p>';
    }
    
    ob_start();
    ?>
    <div class="wpr-menu-grid wpr-columns-<?php echo esc_attr($columns); ?>">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <?php echo wpr_render_single_item(get_post(), $show_images, $image_position); ?>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

function wpr_render_single_item($item, $show_images, $image_position) {
    $dish_number = get_post_meta($item->ID, '_wpr_dish_number', true);
    $price = get_post_meta($item->ID, '_wpr_price', true);
    $allergens = wp_get_post_terms($item->ID, 'wpr_allergen');
    $vegan = get_post_meta($item->ID, '_wpr_vegan', true);
    $vegetarian = get_post_meta($item->ID, '_wpr_vegetarian', true);
    $has_image = has_post_thumbnail($item->ID);
    $item_categories = wp_get_post_terms($item->ID, 'wpr_category', array('fields' => 'slugs'));
    $cat_classes = is_array($item_categories) ? implode(' ', array_map(function($c) { return 'wpr-cat-' . $c; }, $item_categories)) : '';
    
    ob_start();
    ?>
    <div class="wpr-menu-item <?php echo ($image_position === 'left' && $has_image) ? 'wpr-has-image-left' : ''; ?> <?php echo ($image_position === 'top' && $has_image) ? 'wpr-has-image-top' : ''; ?> <?php echo esc_attr($cat_classes); ?>" 
         data-title="<?php echo esc_attr(strtolower($item->post_title)); ?>" 
         data-description="<?php echo esc_attr(strtolower(wp_strip_all_tags($item->post_content))); ?>" 
         data-number="<?php echo esc_attr($dish_number); ?>">
        
        <?php if ($show_images && $has_image) : ?>
            <div class="wpr-menu-item-image">
                <?php if ($dish_number) : ?>
                    <div class="wpr-dish-number-badge"><?php echo esc_html($dish_number); ?></div>
                <?php endif; ?>
                <?php echo get_the_post_thumbnail($item->ID, 'medium'); ?>
            </div>
        <?php endif; ?>
        
        <div class="wpr-menu-item-content">
            <div class="wpr-menu-item-header">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <?php if ($dish_number && (!$show_images || !$has_image)) : ?>
                        <span class="wpr-dish-number-inline"><?php echo esc_html($dish_number); ?></span>
                    <?php endif; ?>
                    <h3 class="wpr-menu-item-title"><?php echo esc_html($item->post_title); ?></h3>
                </div>
                <?php if ($price) : ?>
                    <span class="wpr-menu-item-price"><?php echo esc_html(wpr_format_price($price)); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($item->post_content)) : ?>
                <div class="wpr-menu-item-description">
                    <?php echo wpautop($item->post_content); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($vegan || $vegetarian || !empty($allergens)) : ?>
                <div class="wpr-menu-item-meta">
                    <div class="wpr-meta-badges">
                        <?php if ($vegan) : ?>
                            <span class="wpr-badge wpr-badge-vegan">🌿 Vegan</span>
                        <?php elseif ($vegetarian) : ?>
                            <span class="wpr-badge wpr-badge-vegetarian">🌱 Vegetarisch</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($allergens) && !is_wp_error($allergens)) : ?>
                            <?php foreach ($allergens as $allergen) : 
                                $icon = get_term_meta($allergen->term_id, 'icon', true);
                            ?>
                                <span class="wpr-badge wpr-badge-allergen" title="<?php echo esc_attr($allergen->name); ?>">
                                    <?php if ($icon) : ?>
                                        <?php echo esc_html($icon); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('restaurant_menu', 'wpr_menu_shortcode');
