<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Plugin URI: https://github.com/stb-srv/wp-restaurant
 * Description: Ein modernes und benutzerfreundliches WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten
 * Version: 1.0.0
 * Author: STB-SRV
 * Author URI: https://github.com/stb-srv
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-restaurant-menu
 * Domain Path: /languages
 */

// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('WP_RESTAURANT_MENU_VERSION', '1.0.0');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Code, der bei Plugin-Aktivierung ausgeführt wird
 */
function activate_wp_restaurant_menu() {
    require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wp-restaurant-menu-activator.php';
    WP_Restaurant_Menu_Activator::activate();
}

/**
 * Code, der bei Plugin-Deaktivierung ausgeführt wird
 */
function deactivate_wp_restaurant_menu() {
    require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wp-restaurant-menu-deactivator.php';
    WP_Restaurant_Menu_Deactivator::deactivate();
}

// Aktivierungs- und Deaktivierungs-Hooks registrieren
register_activation_hook(__FILE__, 'activate_wp_restaurant_menu');
register_deactivation_hook(__FILE__, 'deactivate_wp_restaurant_menu');

/**
 * Die Haupt-Plugin-Klasse laden
 */
require WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wp-restaurant-menu.php';

/**
 * Plugin-Ausführung starten
 */
function run_wp_restaurant_menu() {
    $plugin = new WP_Restaurant_Menu();
    $plugin->run();
}
run_wp_restaurant_menu();
