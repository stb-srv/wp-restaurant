<?php
/**
 * Plugin-Aktivierung
 *
 * Diese Klasse definiert alle Code-Aktionen während der Plugin-Aktivierung
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Activator {

    /**
     * Code, der bei Plugin-Aktivierung ausgeführt wird
     *
     * Führt folgende Aufgaben aus:
     * - Erstellt Standard-Kategorien
     * - Setzt Standardoptionen
     * - Flush Rewrite Rules für Custom Post Type
     */
    public static function activate() {
        // Custom Post Type temporär registrieren für Rewrite Rules
        self::register_temp_post_type();

        // Flush Rewrite Rules, um Permalinks zu aktualisieren
        flush_rewrite_rules();

        // Standard-Kategorien erstellen
        self::create_default_categories();

        // Plugin-Version in Datenbank speichern
        add_option('wp_restaurant_menu_version', WP_RESTAURANT_MENU_VERSION);

        // Standard-Einstellungen setzen
        $default_settings = array(
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'number_of_decimals' => 2,
        );
        add_option('wp_restaurant_menu_settings', $default_settings);
    }

    /**
     * Registriere den Custom Post Type temporär für die Aktivierung
     */
    private static function register_temp_post_type() {
        register_post_type('restaurant_menu_item', array(
            'public'  => true,
            'rewrite' => array('slug' => 'menu-item'),
        ));
        
        register_taxonomy('menu_category', 'restaurant_menu_item', array(
            'public'  => true,
            'rewrite' => array('slug' => 'menu-kategorie'),
        ));
    }

    /**
     * Erstelle Standard-Kategorien bei Aktivierung
     */
    private static function create_default_categories() {
        // Überprüfe ob bereits Kategorien existieren
        $existing_cats = get_terms(array(
            'taxonomy' => 'menu_category',
            'hide_empty' => false,
        ));

        // Nur erstellen, wenn keine Kategorien existieren
        if (empty($existing_cats) || is_wp_error($existing_cats)) {
            $default_categories = array(
                'Vorspeisen' => 'Köstliche Appetit-Anreger',
                'Suppen' => 'Wärmende Suppen und Eintöpfe',
                'Salate' => 'Frische Salat-Kreationen',
                'Hauptgerichte' => 'Unsere Hauptspeisen',
                'Vegetarisch' => 'Vegetarische Köstlichkeiten',
                'Fisch & Meeresfrüchte' => 'Frisch aus dem Meer',
                'Fleischgerichte' => 'Herzhafte Fleischspezialitäten',
                'Beilagen' => 'Leckere Beilagen',
                'Desserts' => 'Süße Vergnügungen',
                'Getränke' => 'Erfrischende Getränke',
            );

            foreach ($default_categories as $name => $description) {
                if (!term_exists($name, 'menu_category')) {
                    wp_insert_term(
                        $name,
                        'menu_category',
                        array('description' => $description)
                    );
                }
            }
        }
    }
}
