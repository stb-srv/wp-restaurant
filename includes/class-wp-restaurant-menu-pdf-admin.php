<?php
/**
 * Admin-Interface für PDF-Export
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_PDF_Admin {

    /**
     * Initialisiere Admin-Seite
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_export_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Füge Export-Seite hinzu
     */
    public static function add_export_page() {
        add_submenu_page(
            'edit.php?post_type=restaurant_menu_item',
            __('PDF Export', 'wp-restaurant-menu'),
            __('📄 PDF Export', 'wp-restaurant-menu'),
            'manage_options',
            'wpr-pdf-export',
            array(__CLASS__, 'render_export_page')
        );
    }

    /**
     * Render Export-Seite
     */
    public static function render_export_page() {
        $menu_lists = get_terms(array(
            'taxonomy' => 'menu_list',
            'hide_empty' => false,
        ));

        $categories = get_terms(array(
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('📄 PDF Speisekarte exportieren', 'wp-restaurant-menu'); ?></h1>
            <div style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h2><?php _e('PDF generieren', 'wp-restaurant-menu'); ?></h2>
                <form method="get" style="max-width: 500px;">
                    <input type="hidden" name="wpr_pdf_export" value="1">
                    <?php wp_nonce_field('wpr_pdf_export_nonce', '_wpnonce'); ?>

                    <div style="margin-bottom: 15px;">
                        <label for="menu_list" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            <?php _e('Menükarte wählen:', 'wp-restaurant-menu'); ?>
                        </label>
                        <select name="menu_list" id="menu_list" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value=""><?php _e('Alle Karten', 'wp-restaurant-menu'); ?></option>
                            <?php if (!is_wp_error($menu_lists) && !empty($menu_lists)) : ?>
                                <?php foreach ($menu_lists as $menu) : ?>
                                    <option value="<?php echo esc_attr($menu->slug); ?>"><?php echo esc_html($menu->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="category" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            <?php _e('Kategorie (optional):', 'wp-restaurant-menu'); ?>
                        </label>
                        <select name="category" id="category" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value=""><?php _e('Alle Kategorien', 'wp-restaurant-menu'); ?></option>
                            <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
                                <?php foreach ($categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="submit" class="button button-primary button-large">
                        <?php _e('📄 PDF exportieren', 'wp-restaurant-menu'); ?>
                    </button>
                </form>
            </div>

            <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 20px; margin-top: 20px; border-radius: 4px;">
                <h3><?php _e('💡 Tipps für den PDF-Export', 'wp-restaurant-menu'); ?></h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><?php _e('Das PDF ist optimiert für den Druck auf A4-Papier', 'wp-restaurant-menu'); ?></li>
                    <li><?php _e('Berücksichtigt automatisch Vegetarisch, Vegan und Allergene', 'wp-restaurant-menu'); ?></li>
                    <li><?php _e('Restaurant-Name und Datum werden automatisch eingefügt', 'wp-restaurant-menu'); ?></li>
                    <li><?php _e('Gerichte werden nach Kategorie sortiert angezeigt', 'wp-restaurant-menu'); ?></li>
                    <li><?php _e('Menükarten können gefiltert und kombiniert exportiert werden', 'wp-restaurant-menu'); ?></li>
                    <li><?php _e('Im Browser können Sie die Seite drucken (Strg+P oder Cmd+P)', 'wp-restaurant-menu'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue Scripts
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'wpr-pdf-admin',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'admin/js/pdf-export.js',
            array('jquery'),
            WP_RESTAURANT_MENU_VERSION
        );
    }
}
