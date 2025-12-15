<?php
/**
 * Plugin-Aktivierung
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Activator {

    /**
     * Code, der bei Plugin-Aktivierung ausgeführt wird
     */
    public static function activate() {
        // Custom Post Type temporär registrieren
        self::register_temp_post_type();

        // Flush Rewrite Rules
        flush_rewrite_rules();

        // Standard-Kategorien erstellen
        self::create_default_categories();

        // Standard-Menükarten erstellen
        self::create_default_menus();

        // Plugin-Version speichern
        if (!get_option('wp_restaurant_menu_version')) {
            add_option('wp_restaurant_menu_version', '1.0.0');
        }

        // Standard-Einstellungen
        if (!get_option('wp_restaurant_menu_settings')) {
            $default_settings = array(
                'currency_symbol' => '€',
                'currency_position' => 'after',
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'number_of_decimals' => 2,
            );
            add_option('wp_restaurant_menu_settings', $default_settings);
        }
    }

    /**
     * Registriere CPT temporär
     */
    private static function register_temp_post_type() {
        register_post_type('restaurant_menu_item', array(
            'public'  => false,
            'show_ui' => true,
        ));
        
        register_taxonomy('menu_category', 'restaurant_menu_item', array(
            'public'  => false,
            'show_ui' => true,
        ));

        register_taxonomy('menu_list', 'restaurant_menu_item', array(
            'public'  => false,
            'show_ui' => true,
        ));
    }

    /**
     * Erstelle Standard-Kategorien
     */
    private static function create_default_categories() {
        $existing_cats = get_terms(array(
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
        ));

        if (empty($existing_cats) || is_wp_error($existing_cats)) {
            $default_categories = array(
                'Vorspeisen' => 'Köstliche Appetit-Anreger',
                'Hauptgerichte' => 'Unsere Hauptspeisen',
                'Desserts' => 'Süße Vergnügungen',
                'Getränke' => 'Erfrischende Getränke',
            );

            foreach ($default_categories as $name => $description) {
                if (!term_exists($name, 'menu_category')) {
                    wp_insert_term($name, 'menu_category', array(
                        'description' => $description
                    ));
                }
            }
        }
    }

    /**
     * Erstelle Standard-Menükarten
     */
    private static function create_default_menus() {
        $existing_menus = get_terms(array(
            'taxonomy' => 'menu_list',
            'hide_empty' => false,
        ));

        if (empty($existing_menus) || is_wp_error($existing_menus)) {
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
}
