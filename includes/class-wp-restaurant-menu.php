<?php
/**
 * Die Haupt-Plugin-Klasse
 *
 * Diese Klasse definiert alle Hooks und lädt die erforderlichen Dependencies
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu {

    /**
     * Der Loader für alle Hooks des Plugins
     *
     * @var WP_Restaurant_Menu_Loader
     */
    protected $loader;

    /**
     * Der eindeutige Identifier für dieses Plugin
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * Die aktuelle Version des Plugins
     *
     * @var string
     */
    protected $version;

    /**
     * Initialisiere die Klasse und setze ihre Eigenschaften
     */
    public function __construct() {
        $this->version = WP_RESTAURANT_MENU_VERSION;
        $this->plugin_name = 'wp-restaurant-menu';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_custom_post_type();
    }

    /**
     * Lade die erforderlichen Dependencies für dieses Plugin
     */
    private function load_dependencies() {
        // Loader-Klasse zum Registrieren aller Hooks
        require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wp-restaurant-menu-loader.php';
        
        // Klasse für Internationalisierung
        require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wp-restaurant-menu-i18n.php';
        
        // Admin-spezifische Hooks
        require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/class-wp-restaurant-menu-admin.php';
        
        // Public-spezifische Hooks
        require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'public/class-wp-restaurant-menu-public.php';

        $this->loader = new WP_Restaurant_Menu_Loader();
    }

    /**
     * Definiere die Locale für Internationalisierung
     */
    private function set_locale() {
        $plugin_i18n = new WP_Restaurant_Menu_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Registriere Custom Post Type und Taxonomien
     */
    private function register_custom_post_type() {
        $this->loader->add_action('init', $this, 'register_menu_item_post_type');
        $this->loader->add_action('init', $this, 'register_menu_taxonomies');
    }

    /**
     * Registriere den Custom Post Type für Menüpunkte
     */
    public function register_menu_item_post_type() {
        $labels = array(
            'name'               => _x('Menüpunkte', 'post type general name', 'wp-restaurant-menu'),
            'singular_name'      => _x('Menüpunkt', 'post type singular name', 'wp-restaurant-menu'),
            'menu_name'          => _x('Restaurant Menu', 'admin menu', 'wp-restaurant-menu'),
            'add_new'            => _x('Neu hinzufügen', 'menu item', 'wp-restaurant-menu'),
            'add_new_item'       => __('Neues Gericht hinzufügen', 'wp-restaurant-menu'),
            'edit_item'          => __('Gericht bearbeiten', 'wp-restaurant-menu'),
            'new_item'           => __('Neues Gericht', 'wp-restaurant-menu'),
            'view_item'          => __('Gericht ansehen', 'wp-restaurant-menu'),
            'search_items'       => __('Gerichte durchsuchen', 'wp-restaurant-menu'),
            'not_found'          => __('Keine Gerichte gefunden', 'wp-restaurant-menu'),
            'not_found_in_trash' => __('Keine Gerichte im Papierkorb', 'wp-restaurant-menu'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'menu-item'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-food',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'        => true, // Gutenberg-Support
        );

        register_post_type('restaurant_menu_item', $args);
    }

    /**
     * Registriere Taxonomien für den Custom Post Type
     */
    public function register_menu_taxonomies() {
        // Kategorien-Taxonomie
        $category_labels = array(
            'name'              => _x('Kategorien', 'taxonomy general name', 'wp-restaurant-menu'),
            'singular_name'     => _x('Kategorie', 'taxonomy singular name', 'wp-restaurant-menu'),
            'search_items'      => __('Kategorien durchsuchen', 'wp-restaurant-menu'),
            'all_items'         => __('Alle Kategorien', 'wp-restaurant-menu'),
            'edit_item'         => __('Kategorie bearbeiten', 'wp-restaurant-menu'),
            'update_item'       => __('Kategorie aktualisieren', 'wp-restaurant-menu'),
            'add_new_item'      => __('Neue Kategorie hinzufügen', 'wp-restaurant-menu'),
            'new_item_name'     => __('Neuer Kategoriename', 'wp-restaurant-menu'),
            'menu_name'         => __('Kategorien', 'wp-restaurant-menu'),
        );

        register_taxonomy('menu_category', 'restaurant_menu_item', array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'menu-kategorie'),
            'show_in_rest'      => true,
        ));

        // Tags-Taxonomie
        $tag_labels = array(
            'name'              => _x('Tags', 'taxonomy general name', 'wp-restaurant-menu'),
            'singular_name'     => _x('Tag', 'taxonomy singular name', 'wp-restaurant-menu'),
            'search_items'      => __('Tags durchsuchen', 'wp-restaurant-menu'),
            'all_items'         => __('Alle Tags', 'wp-restaurant-menu'),
            'edit_item'         => __('Tag bearbeiten', 'wp-restaurant-menu'),
            'update_item'       => __('Tag aktualisieren', 'wp-restaurant-menu'),
            'add_new_item'      => __('Neues Tag hinzufügen', 'wp-restaurant-menu'),
            'new_item_name'     => __('Neuer Tag-Name', 'wp-restaurant-menu'),
            'menu_name'         => __('Tags', 'wp-restaurant-menu'),
        );

        register_taxonomy('menu_tag', 'restaurant_menu_item', array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'menu-tag'),
            'show_in_rest'      => true,
        ));
    }

    /**
     * Registriere alle Admin-bezogenen Hooks
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Restaurant_Menu_Admin($this->get_plugin_name(), $this->get_version());

        // Enqueue Admin-Styles und Scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Meta-Boxen hinzufügen
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_menu_item_meta_boxes');
        $this->loader->add_action('save_post', $plugin_admin, 'save_menu_item_meta');
    }

    /**
     * Registriere alle Public-bezogenen Hooks
     */
    private function define_public_hooks() {
        $plugin_public = new WP_Restaurant_Menu_Public($this->get_plugin_name(), $this->get_version());

        // Enqueue Public-Styles und Scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Shortcode registrieren
        $this->loader->add_shortcode('restaurant_menu', $plugin_public, 'restaurant_menu_shortcode');
    }

    /**
     * Führe den Loader aus, um alle Hooks zu registrieren
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Getter für Plugin-Namen
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Getter für Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Getter für Version
     */
    public function get_version() {
        return $this->version;
    }
}
