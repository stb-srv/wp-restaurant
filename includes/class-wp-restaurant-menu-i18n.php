<?php
/**
 * Internationalisierung für das Plugin
 *
 * Lädt und definiert die Internationalisierungsdateien für dieses Plugin
 * damit es in mehreren Sprachen verwendet werden kann
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_i18n {

    /**
     * Lade die Plugin-Text-Domain für Übersetzungen
     *
     * Verwendet die Text-Domain 'wp-restaurant-menu'
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-restaurant-menu',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
