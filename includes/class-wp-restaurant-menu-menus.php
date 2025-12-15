<?php
/**
 * Verwaltung von mehreren Menüs (Karten)
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
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'public'            => false,
            'meta_box_cb'       => array(__CLASS__, 'render_meta_box'),
        ));
    }

    /**
     * Custom Meta Box
     */
    public static function render_meta_box($post) {
        $terms = get_terms(array(
            'taxonomy' => 'menu_list',
            'hide_empty' => false,
        ));

        $post_terms = wp_get_object_terms($post->ID, 'menu_list', array('fields' => 'ids'));
        
        if (is_wp_error($post_terms)) {
            $post_terms = array();
        }
        ?>
        <div class="wpr-menu-list-box">
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Wähle aus, in welchen Menükarten dieses Gericht erscheinen soll:', 'wp-restaurant-menu'); ?>
            </p>
            
            <?php if (empty($terms) || is_wp_error($terms)) : ?>
                <p>
                    <em><?php _e('Noch keine Menükarten vorhanden.', 'wp-restaurant-menu'); ?></em>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=menu_list&post_type=restaurant_menu_item'); ?>">
                        <?php _e('Jetzt erste Karte erstellen »', 'wp-restaurant-menu'); ?>
                    </a>
                </p>
            <?php else : ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($terms as $term) : ?>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f9f9f9; border-radius: 4px;">
                            <input 
                                type="checkbox" 
                                name="tax_input[menu_list][]" 
                                value="<?php echo esc_attr($term->term_id); ?>"
                                <?php checked(in_array($term->term_id, $post_terms)); ?>
                            />
                            <strong><?php echo esc_html($term->name); ?></strong>
                            <?php if ($term->description) : ?>
                                <small style="color: #666;"> – <?php echo esc_html($term->description); ?></small>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Erstelle Standard-Menükarten
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
