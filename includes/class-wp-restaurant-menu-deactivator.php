<?php
/**
 * Plugin-Deaktivierung
 *
 * Diese Klasse definiert alle Code-Aktionen während der Plugin-Deaktivierung
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Deactivator {

    /**
     * Code, der bei Plugin-Deaktivierung ausgeführt wird
     *
     * Führt folgende Aufgaben aus:
     * - Flush Rewrite Rules
     * - Aufräumen temporärer Daten (falls vorhanden)
     */
    public static function deactivate() {
        // Flush Rewrite Rules beim Deaktivieren
        flush_rewrite_rules();

        // Optionale Aufräumarbeiten
        // Hinweis: Daten werden NICHT gelöscht, nur bei Deinstallation
        // Menüpunkte und Einstellungen bleiben erhalten
    }
}
