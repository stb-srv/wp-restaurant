<?php
/**
 * Verwaltung von mehreren Menüs (Karten)
 *
 * Ermöglicht das Erstellen verschiedener Menüs wie:
 * - Hauptspeisekarte
 * - Getränkekarte
 * - Mittagsmenü
 * - Saisonkarte
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Menus {

    /**
     * Registriere die Menü-Listen Taxonomie
     */
    public static function register() {
        $labels = array(
            'name'              => _x('Menü-Karten', 'taxonomy general name', 'wp-restaurant-menu'),
            'singular_name'     => _x('Menü-Karte', 'taxonomy singular name', 'wp-restaurant-menu'),
            'search_items'      => __('Karten durchsuchen', 'wp-restaurant-menu'),
            'all_items'         => __('Alle Karten', 'wp-restaurant-menu'),
            'edit_item'         => __('Karte bearbeiten', 'wp-restaurant-menu'),
            'update_item'       => __('Karte aktualisieren', 'wp-restaurant-menu'),
            'add_new_item'      => __('Neue Karte hinzufügen', 'wp-restaurant-menu'),
            'new_item_name'     => __('Neuer Kartenname', 'wp-restaurant-menu'),
            'menu_name'         => __('Menü-Karten', 'wp-restaurant-menu'),
        );

        register_taxonomy('menu_list', 'restaurant_menu_item', array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'menu-karte'),
            'show_in_rest'      => true,
            'meta_box_cb'       => array(__CLASS__, 'render_meta_box'),
        ));
    }

    /**
     * Custom Meta Box für bessere Benutzerfreundlichkeit
     */
    public static function render_meta_box($post) {
        $terms = get_terms(array(
            'taxonomy' => 'menu_list',
            'hide_empty' => false,
        ));

        $post_terms = wp_get_object_terms($post->ID, 'menu_list', array('fields' => 'ids'));
        ?>
        <div class="wpr-menu-list-box">
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Wähle aus, in welchen Menükarten dieses Gericht erscheinen soll:', 'wp-restaurant-menu'); ?>
            </p>
            
            <?php if (empty($terms)) : ?>
                <p>
                    <em><?php _e('Noch keine Menükarten vorhanden.', 'wp-restaurant-menu'); ?></em>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=menu_list&post_type=restaurant_menu_item'); ?>">
                        <?php _e('Jetzt erste Karte erstellen »', 'wp-restaurant-menu'); ?>
                    </a>
                </p>
            <?php else : ?>
                <div class="wpr-menu-list-checkboxes">
                    <?php foreach ($terms as $term) : ?>
                        <label class="wpr-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="tax_input[menu_list][]" 
                                value="<?php echo esc_attr($term->term_id); ?>"
                                <?php checked(in_array($term->term_id, $post_terms)); ?>
                            />
                            <span class="wpr-checkbox-text">
                                <strong><?php echo esc_html($term->name); ?></strong>
                                <?php if ($term->description) : ?>
                                    <small> – <?php echo esc_html($term->description); ?></small>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .wpr-menu-list-checkboxes {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .wpr-checkbox-label {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .wpr-checkbox-label:hover {
                background: #f0f0f0;
            }
            .wpr-checkbox-label input[type="checkbox"] {
                margin-top: 2px;
            }
            .wpr-checkbox-text small {
                color: #666;
                display: block;
                margin-top: 2px;
            }
        </style>
        <?php
    }

    /**
     * Erstelle Standard-Menükarten bei Aktivierung
     */
    public static function create_default_menus() {
        $default_menus = array(
            array(
                'name' => 'Hauptspeisekarte',
                'slug' => 'hauptspeisekarte',
                'description' => 'Unsere Hauptmenükarte mit allen klassischen Gerichten',
            ),
            array(
                'name' => 'Getränkekarte',
                'slug' => 'getraenkekarte',
                'description' => 'Alle Getränke, Weine und Cocktails',
            ),
            array(
                'name' => 'Mittagsmenü',
                'slug' => 'mittagsmenue',
                'description' => 'Spezielle Mittagsangebote zu günstigen Preisen',
            ),
        );

        foreach ($default_menus as $menu) {
            if (!term_exists($menu['slug'], 'menu_list')) {
                wp_insert_term(
                    $menu['name'],
                    'menu_list',
                    array(
                        'slug' => $menu['slug'],
                        'description' => $menu['description'],
                    )
                );
            }
        }
    }
}
