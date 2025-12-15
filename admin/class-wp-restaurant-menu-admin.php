<?php
/**
 * Admin-spezifische Funktionalität
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Admin Styles laden
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_RESTAURANT_MENU_PLUGIN_URL . 'admin/css/wp-restaurant-menu-admin.css',
            array(),
            $this->version
        );
    }

    /**
     * Admin Scripts laden
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WP_RESTAURANT_MENU_PLUGIN_URL . 'admin/js/wp-restaurant-menu-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Meta-Boxen für Menüpunkte hinzufügen
     */
    public function add_menu_item_meta_boxes() {
        add_meta_box(
            'menu_item_details',
            __('Gericht-Details', 'wp-restaurant-menu'),
            array($this, 'render_menu_item_meta_box'),
            'restaurant_menu_item',
            'normal',
            'high'
        );
    }

    /**
     * Render Meta-Box für Menüpunkt-Details
     */
    public function render_menu_item_meta_box($post) {
        wp_nonce_field('menu_item_meta_box', 'menu_item_meta_box_nonce');

        $price = get_post_meta($post->ID, '_menu_item_price', true);
        $allergens = get_post_meta($post->ID, '_menu_item_allergens', true);
        $spicy_level = get_post_meta($post->ID, '_menu_item_spicy_level', true);
        $vegetarian = get_post_meta($post->ID, '_menu_item_vegetarian', true);
        $vegan = get_post_meta($post->ID, '_menu_item_vegan', true);
        ?>
        <div class="wpr-meta-box">
            <div class="wpr-meta-row">
                <label for="menu_item_price">
                    <strong><?php _e('Preis', 'wp-restaurant-menu'); ?></strong>
                </label>
                <input 
                    type="text" 
                    id="menu_item_price" 
                    name="menu_item_price" 
                    value="<?php echo esc_attr($price); ?>" 
                    placeholder="z.B. 12,50"
                    class="wpr-input"
                />
                <p class="description"><?php _e('Preis des Gerichts (ohne Währungssymbol)', 'wp-restaurant-menu'); ?></p>
            </div>

            <div class="wpr-meta-row">
                <label for="menu_item_allergens">
                    <strong><?php _e('Allergene', 'wp-restaurant-menu'); ?></strong>
                </label>
                <input 
                    type="text" 
                    id="menu_item_allergens" 
                    name="menu_item_allergens" 
                    value="<?php echo esc_attr($allergens); ?>" 
                    placeholder="z.B. A, C, G, L"
                    class="wpr-input"
                />
                <p class="description"><?php _e('Allergen-Kennzeichnungen (durch Komma getrennt)', 'wp-restaurant-menu'); ?></p>
            </div>

            <div class="wpr-meta-row">
                <label for="menu_item_spicy_level">
                    <strong><?php _e('Schärfegrad', 'wp-restaurant-menu'); ?></strong>
                </label>
                <select id="menu_item_spicy_level" name="menu_item_spicy_level" class="wpr-select">
                    <option value="0" <?php selected($spicy_level, '0'); ?>><?php _e('Nicht scharf', 'wp-restaurant-menu'); ?></option>
                    <option value="1" <?php selected($spicy_level, '1'); ?>>🌶 <?php _e('Mild', 'wp-restaurant-menu'); ?></option>
                    <option value="2" <?php selected($spicy_level, '2'); ?>>🌶🌶 <?php _e('Mittel', 'wp-restaurant-menu'); ?></option>
                    <option value="3" <?php selected($spicy_level, '3'); ?>>🌶🌶🌶 <?php _e('Scharf', 'wp-restaurant-menu'); ?></option>
                </select>
            </div>

            <div class="wpr-meta-row wpr-meta-checkboxes">
                <label>
                    <input 
                        type="checkbox" 
                        name="menu_item_vegetarian" 
                        value="1" 
                        <?php checked($vegetarian, '1'); ?>
                    />
                    <span><?php _e('🌱 Vegetarisch', 'wp-restaurant-menu'); ?></span>
                </label>

                <label>
                    <input 
                        type="checkbox" 
                        name="menu_item_vegan" 
                        value="1" 
                        <?php checked($vegan, '1'); ?>
                    />
                    <span><?php _e('🌿 Vegan', 'wp-restaurant-menu'); ?></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Speichere Meta-Daten
     */
    public function save_menu_item_meta($post_id) {
        if (!isset($_POST['menu_item_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['menu_item_meta_box_nonce'], 'menu_item_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Preis speichern
        if (isset($_POST['menu_item_price'])) {
            update_post_meta($post_id, '_menu_item_price', sanitize_text_field($_POST['menu_item_price']));
        }

        // Allergene speichern
        if (isset($_POST['menu_item_allergens'])) {
            update_post_meta($post_id, '_menu_item_allergens', sanitize_text_field($_POST['menu_item_allergens']));
        }

        // Schärfegrad speichern
        if (isset($_POST['menu_item_spicy_level'])) {
            update_post_meta($post_id, '_menu_item_spicy_level', sanitize_text_field($_POST['menu_item_spicy_level']));
        }

        // Vegetarisch speichern
        $vegetarian = isset($_POST['menu_item_vegetarian']) ? '1' : '0';
        update_post_meta($post_id, '_menu_item_vegetarian', $vegetarian);

        // Vegan speichern
        $vegan = isset($_POST['menu_item_vegan']) ? '1' : '0';
        update_post_meta($post_id, '_menu_item_vegan', $vegan);
    }
}
