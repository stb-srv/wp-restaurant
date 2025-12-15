<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Plugin URI: https://github.com/stb-srv/wp-restaurant
 * Description: Modernes WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten
 * Version: 1.0.2
 * Author: STB-SRV
 * License: GPL-2.0+
 * Text Domain: wp-restaurant-menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

define('WP_RESTAURANT_MENU_VERSION', '1.0.2');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));

function wpr_activate() {
    wpr_register_post_type();
    wpr_register_taxonomies();
    flush_rewrite_rules();
    
    if (!get_option('wpr_settings')) {
        add_option('wpr_settings', array(
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'show_images' => 'yes',
            'image_position' => 'left',
            'show_search' => 'yes',
        ));
    }
    
    add_option('wpr_version', WP_RESTAURANT_MENU_VERSION);
}
register_activation_hook(__FILE__, 'wpr_activate');

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
}
add_action('init', 'wpr_register_taxonomies');

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

function wpr_render_settings_page() {
    if (isset($_POST['wpr_save_settings']) && check_admin_referer('wpr_settings_save', 'wpr_settings_nonce')) {
        $settings = array(
            'currency_symbol' => sanitize_text_field($_POST['currency_symbol']),
            'currency_position' => sanitize_text_field($_POST['currency_position']),
            'show_images' => sanitize_text_field($_POST['show_images']),
            'image_position' => sanitize_text_field($_POST['image_position']),
            'show_search' => sanitize_text_field($_POST['show_search']),
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
                                <option value="top" <?php selected($settings['image_position'], 'top'); ?>>Oben (über dem Text)</option>
                                <option value="left" <?php selected($settings['image_position'], 'left'); ?>>Links (neben dem Text)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2>Such-Einstellungen</h2>
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

function wpr_format_price($price) {
    if (empty($price)) return '';
    $settings = get_option('wpr_settings', array('currency_symbol' => '€', 'currency_position' => 'after'));
    return $settings['currency_position'] === 'before' ? $settings['currency_symbol'] . ' ' . $price : $price . ' ' . $settings['currency_symbol'];
}

function wpr_add_meta_boxes() {
    add_meta_box('wpr_details', 'Gericht-Details', 'wpr_render_meta_box', 'wpr_menu_item', 'normal', 'high');
}
add_action('add_meta_boxes', 'wpr_add_meta_boxes');

function wpr_render_meta_box($post) {
    wp_nonce_field('wpr_save_meta', 'wpr_meta_nonce');
    
    $dish_number = get_post_meta($post->ID, '_wpr_dish_number', true);
    $price = get_post_meta($post->ID, '_wpr_price', true);
    $allergens = get_post_meta($post->ID, '_wpr_allergens', true);
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
                    Optional: Eindeutige Nummer für dieses Gericht (z.B. 1, 12, A5, G1)
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
        
        <p>
            <label><strong>Allergene:</strong></label><br>
            <input type="text" name="wpr_allergens" value="<?php echo esc_attr($allergens); ?>" style="width: 100%; max-width: 500px;" placeholder="z.B. A, C, G, L">
            <span style="color: #666; font-size: 0.9em;">
                Durch Komma getrennt
            </span>
        </p>
        
        <div style="display: flex; gap: 15px; margin-top: 15px;">
            <label style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f9f9f9; border-radius: 6px; cursor: pointer;">
                <input type="checkbox" name="wpr_vegetarian" value="1" <?php checked($vegetarian, '1'); ?>>
                <span>🌱 Vegetarisch</span>
            </label>
            
            <label style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f9f9f9; border-radius: 6px; cursor: pointer;">
                <input type="checkbox" name="wpr_vegan" value="1" <?php checked($vegan, '1'); ?>>
                <span>🌿 Vegan</span>
            </label>
        </div>
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
    if (isset($_POST['wpr_allergens'])) {
        update_post_meta($post_id, '_wpr_allergens', sanitize_text_field($_POST['wpr_allergens']));
    }
    update_post_meta($post_id, '_wpr_vegetarian', isset($_POST['wpr_vegetarian']) ? '1' : '0');
    update_post_meta($post_id, '_wpr_vegan', isset($_POST['wpr_vegan']) ? '1' : '0');
}
add_action('save_post', 'wpr_save_meta');

function wpr_menu_shortcode($atts) {
    $atts = shortcode_atts(array(
        'menu' => '',
        'category' => '',
        'columns' => '1',
    ), $atts);
    
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
    if (!$query->have_posts()) return '<p class="wpr-no-items">Keine Gerichte gefunden.</p>';
    
    $settings = get_option('wpr_settings', array(
        'show_images' => 'yes',
        'image_position' => 'left',
        'show_search' => 'yes',
    ));
    
    $show_images = $settings['show_images'] === 'yes';
    $image_position = $settings['image_position'];
    $show_search = $settings['show_search'] === 'yes';
    $columns = max(1, min(3, intval($atts['columns'])));
    
    $categories = array();
    if (!empty($atts['menu'])) {
        $categories = get_terms(array(
            'taxonomy' => 'wpr_category',
            'hide_empty' => true,
        ));
    }
    
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
                        
                        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                            <div class="wpr-category-filter">
                                <button class="wpr-filter-btn active" data-category="all">Alle</button>
                                <?php foreach ($categories as $cat) : ?>
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
        
        <div class="wpr-menu-grid wpr-columns-<?php echo esc_attr($columns); ?>">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                    $dish_number = get_post_meta(get_the_ID(), '_wpr_dish_number', true);
                    $price = get_post_meta(get_the_ID(), '_wpr_price', true);
                    $allergens = get_post_meta(get_the_ID(), '_wpr_allergens', true);
                    $vegan = get_post_meta(get_the_ID(), '_wpr_vegan', true);
                    $vegetarian = get_post_meta(get_the_ID(), '_wpr_vegetarian', true);
                    $has_image = has_post_thumbnail();
                    $item_categories = wp_get_post_terms(get_the_ID(), 'wpr_category', array('fields' => 'slugs'));
                    $cat_classes = is_array($item_categories) ? implode(' ', array_map(function($c) { return 'wpr-cat-' . $c; }, $item_categories)) : '';
                ?>
                <div class="wpr-menu-item <?php echo $image_position === 'left' && $has_image ? 'wpr-has-image-left' : ''; ?> <?php echo $image_position === 'top' && $has_image ? 'wpr-has-image-top' : ''; ?> <?php echo esc_attr($cat_classes); ?>" 
                     data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>" 
                     data-description="<?php echo esc_attr(strtolower(wp_strip_all_tags(get_the_content()))); ?>" 
                     data-number="<?php echo esc_attr($dish_number); ?>">
                    
                    <?php if ($show_images && $has_image) : ?>
                        <div class="wpr-menu-item-image">
                            <?php if ($dish_number) : ?>
                                <div class="wpr-dish-number-badge"><?php echo esc_html($dish_number); ?></div>
                            <?php endif; ?>
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="wpr-menu-item-content">
                        <div class="wpr-menu-item-header">
                            <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                <?php if ($dish_number && (!$show_images || !$has_image)) : ?>
                                    <span class="wpr-dish-number-inline"><?php echo esc_html($dish_number); ?></span>
                                <?php endif; ?>
                                <h3 class="wpr-menu-item-title"><?php the_title(); ?></h3>
                            </div>
                            <?php if ($price) : ?>
                                <span class="wpr-menu-item-price"><?php echo esc_html(wpr_format_price($price)); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (get_the_content()) : ?>
                            <div class="wpr-menu-item-description">
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($vegan || $vegetarian || $allergens) : ?>
                            <div class="wpr-menu-item-meta">
                                <?php if ($vegan) : ?>
                                    <span class="wpr-badge wpr-badge-vegan">🌿 Vegan</span>
                                <?php elseif ($vegetarian) : ?>
                                    <span class="wpr-badge wpr-badge-vegetarian">🌱 Vegetarisch</span>
                                <?php endif; ?>
                                
                                <?php if ($allergens) : ?>
                                    <span class="wpr-allergens">Allergene: <strong><?php echo esc_html($allergens); ?></strong></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('restaurant_menu', 'wpr_menu_shortcode');
