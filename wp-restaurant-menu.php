<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Plugin URI: https://github.com/stb-srv/wp-restaurant
 * Description: Modernes WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten
 * Version: 1.0.0
 * Author: STB-SRV
 * License: GPL-2.0+
 * Text Domain: wp-restaurant-menu
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Plugin-Konstanten
define('WP_RESTAURANT_MENU_VERSION', '1.0.0');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Aktivierung
 */
function wpr_activate() {
    // CPT registrieren
    wpr_register_post_type();
    wpr_register_taxonomies();
    
    // Rewrite Rules
    flush_rewrite_rules();
    
    // Optionen
    add_option('wpr_version', WP_RESTAURANT_MENU_VERSION);
}
register_activation_hook(__FILE__, 'wpr_activate');

/**
 * Deaktivierung
 */
function wpr_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wpr_deactivate');

/**
 * Custom Post Type registrieren
 */
function wpr_register_post_type() {
    register_post_type('wpr_menu_item', array(
        'labels' => array(
            'name' => 'Menüpunkte',
            'singular_name' => 'Menüpunkt',
            'add_new_item' => 'Neues Gericht hinzufügen',
            'edit_item' => 'Gericht bearbeiten',
            'menu_name' => 'Restaurant Menu',
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

/**
 * Taxonomien registrieren
 */
function wpr_register_taxonomies() {
    // Kategorien
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
    
    // Menükarten
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

/**
 * Meta-Box hinzufügen
 */
function wpr_add_meta_boxes() {
    add_meta_box(
        'wpr_details',
        'Gericht-Details',
        'wpr_render_meta_box',
        'wpr_menu_item',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wpr_add_meta_boxes');

/**
 * Meta-Box rendern
 */
function wpr_render_meta_box($post) {
    wp_nonce_field('wpr_save_meta', 'wpr_meta_nonce');
    
    $price = get_post_meta($post->ID, '_wpr_price', true);
    $allergens = get_post_meta($post->ID, '_wpr_allergens', true);
    $vegan = get_post_meta($post->ID, '_wpr_vegan', true);
    $vegetarian = get_post_meta($post->ID, '_wpr_vegetarian', true);
    ?>
    <div style="padding: 10px;">
        <p>
            <label><strong>Preis:</strong></label><br>
            <input type="text" name="wpr_price" value="<?php echo esc_attr($price); ?>" style="width: 100%; max-width: 300px;" placeholder="z.B. 12,50 €">
        </p>
        
        <p>
            <label><strong>Allergene:</strong></label><br>
            <input type="text" name="wpr_allergens" value="<?php echo esc_attr($allergens); ?>" style="width: 100%; max-width: 300px;" placeholder="z.B. A, C, G">
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="wpr_vegetarian" value="1" <?php checked($vegetarian, '1'); ?>>
                Vegetarisch
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="wpr_vegan" value="1" <?php checked($vegan, '1'); ?>>
                Vegan
            </label>
        </p>
    </div>
    <?php
}

/**
 * Meta-Daten speichern
 */
function wpr_save_meta($post_id) {
    if (!isset($_POST['wpr_meta_nonce']) || !wp_verify_nonce($_POST['wpr_meta_nonce'], 'wpr_save_meta')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
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

/**
 * Shortcode
 */
function wpr_menu_shortcode($atts) {
    $atts = shortcode_atts(array(
        'menu' => '',
        'category' => '',
    ), $atts);
    
    $args = array(
        'post_type' => 'wpr_menu_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );
    
    $tax_query = array();
    
    if (!empty($atts['menu'])) {
        $tax_query[] = array(
            'taxonomy' => 'wpr_menu_list',
            'field' => 'slug',
            'terms' => $atts['menu'],
        );
    }
    
    if (!empty($atts['category'])) {
        $tax_query[] = array(
            'taxonomy' => 'wpr_category',
            'field' => 'slug',
            'terms' => $atts['category'],
        );
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p>Keine Gerichte gefunden.</p>';
    }
    
    ob_start();
    ?>
    <div class="wpr-menu" style="display: grid; gap: 20px; margin: 20px 0;">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <?php
                $price = get_post_meta(get_the_ID(), '_wpr_price', true);
                $allergens = get_post_meta(get_the_ID(), '_wpr_allergens', true);
                $vegan = get_post_meta(get_the_ID(), '_wpr_vegan', true);
                $vegetarian = get_post_meta(get_the_ID(), '_wpr_vegetarian', true);
            ?>
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #fff;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 1.2em;"><?php the_title(); ?></h3>
                    <?php if ($price) : ?>
                        <strong style="color: #d97706;"><?php echo esc_html($price); ?></strong>
                    <?php endif; ?>
                </div>
                
                <?php if (get_the_content()) : ?>
                    <div style="color: #666; margin-bottom: 10px;">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
                
                <div style="font-size: 0.9em; color: #999;">
                    <?php if ($vegan) : ?>
                        <span style="background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 3px; margin-right: 5px;">Vegan</span>
                    <?php elseif ($vegetarian) : ?>
                        <span style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 3px; margin-right: 5px;">Vegetarisch</span>
                    <?php endif; ?>
                    
                    <?php if ($allergens) : ?>
                        <span>Allergene: <?php echo esc_html($allergens); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('restaurant_menu', 'wpr_menu_shortcode');
