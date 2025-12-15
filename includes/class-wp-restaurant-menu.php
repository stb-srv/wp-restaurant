<?php
/**
 * Die Haupt-Plugin-Klasse
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = defined('WP_RESTAURANT_MENU_VERSION') ? WP_RESTAURANT_MENU_VERSION : '1.0.0';
        $this->plugin_name = 'wp-restaurant-menu';

        $this->load_dependencies();
        $this->set_locale();
        $this->register_custom_post_type();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_pdf_export();
    }

    private function load_dependencies() {
        // Basis-Klassen
        $this->require_file('includes/class-wp-restaurant-menu-loader.php');
        $this->require_file('includes/class-wp-restaurant-menu-i18n.php');
        
        // Admin & Public
        $this->require_file('admin/class-wp-restaurant-menu-admin.php');
        $this->require_file('public/class-wp-restaurant-menu-public.php');

        // Menü-Verwaltung
        $this->require_file('includes/class-wp-restaurant-menu-menus.php');

        // PDF Export
        $this->require_file('includes/class-wp-restaurant-menu-pdf.php');
        $this->require_file('includes/class-wp-restaurant-menu-pdf-admin.php');

        $this->loader = new WP_Restaurant_Menu_Loader();
    }

    private function require_file($file) {
        $path = WP_RESTAURANT_MENU_PLUGIN_DIR . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }

    private function set_locale() {
        if (class_exists('WP_Restaurant_Menu_i18n')) {
            $plugin_i18n = new WP_Restaurant_Menu_i18n();
            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }
    }

    private function register_custom_post_type() {
        $this->loader->add_action('init', $this, 'register_menu_item_post_type');
        $this->loader->add_action('init', $this, 'register_menu_taxonomies');
        
        if (class_exists('WP_Restaurant_Menu_Menus')) {
            $this->loader->add_action('init', array('WP_Restaurant_Menu_Menus', 'register'));
        }
    }

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
            'all_items'          => __('Alle Gerichte', 'wp-restaurant-menu'),
        );

        register_post_type('restaurant_menu_item', array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-food',
            'supports'            => array('title', 'editor', 'thumbnail'),
            'show_in_rest'        => true,
        ));
    }

    public function register_menu_taxonomies() {
        register_taxonomy('menu_category', 'restaurant_menu_item', array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => _x('Kategorien', 'taxonomy general name', 'wp-restaurant-menu'),
                'singular_name'     => _x('Kategorie', 'taxonomy singular name', 'wp-restaurant-menu'),
                'menu_name'         => __('Kategorien', 'wp-restaurant-menu'),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'public'            => false,
        ));

        register_taxonomy('menu_tag', 'restaurant_menu_item', array(
            'hierarchical'      => false,
            'labels'            => array(
                'name'              => _x('Tags', 'taxonomy general name', 'wp-restaurant-menu'),
                'singular_name'     => _x('Tag', 'taxonomy singular name', 'wp-restaurant-menu'),
                'menu_name'         => __('Tags', 'wp-restaurant-menu'),
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'public'            => false,
        ));
    }

    private function define_admin_hooks() {
        if (!class_exists('WP_Restaurant_Menu_Admin')) {
            return;
        }

        $plugin_admin = new WP_Restaurant_Menu_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_menu_item_meta_boxes');
        $this->loader->add_action('save_post', $plugin_admin, 'save_menu_item_meta');
    }

    private function define_public_hooks() {
        if (!class_exists('WP_Restaurant_Menu_Public')) {
            return;
        }

        $plugin_public = new WP_Restaurant_Menu_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('restaurant_menu', $plugin_public, 'restaurant_menu_shortcode');
    }

    private function init_pdf_export() {
        if (class_exists('WP_Restaurant_Menu_PDF')) {
            WP_Restaurant_Menu_PDF::init();
        }
        if (class_exists('WP_Restaurant_Menu_PDF_Admin')) {
            WP_Restaurant_Menu_PDF_Admin::init();
        }
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
