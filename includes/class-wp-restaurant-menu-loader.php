<?php
/**
 * Hook-Loader für das Plugin
 *
 * Registriert alle Actions und Filters für das Plugin
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Loader {

    /**
     * Array der registrierten Actions
     *
     * @var array
     */
    protected $actions;

    /**
     * Array der registrierten Filter
     *
     * @var array
     */
    protected $filters;

    /**
     * Array der registrierten Shortcodes
     *
     * @var array
     */
    protected $shortcodes;

    /**
     * Initialisiere die Collections für Actions, Filters und Shortcodes
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    /**
     * Füge eine neue Action zum Collection-Array hinzu
     *
     * @param string $hook          Der Name der WordPress-Action
     * @param object $component     Eine Referenz zur Klasseninstanz
     * @param string $callback      Der Name der Callback-Funktion
     * @param int    $priority      Priorität der Action (Standard: 10)
     * @param int    $accepted_args Anzahl der akzeptierten Argumente (Standard: 1)
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Füge einen neuen Filter zum Collection-Array hinzu
     *
     * @param string $hook          Der Name des WordPress-Filters
     * @param object $component     Eine Referenz zur Klasseninstanz
     * @param string $callback      Der Name der Callback-Funktion
     * @param int    $priority      Priorität des Filters (Standard: 10)
     * @param int    $accepted_args Anzahl der akzeptierten Argumente (Standard: 1)
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Füge einen neuen Shortcode zum Collection-Array hinzu
     *
     * @param string $tag       Der Shortcode-Tag
     * @param object $component Eine Referenz zur Klasseninstanz
     * @param string $callback  Der Name der Callback-Funktion
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes[] = array(
            'tag'       => $tag,
            'component' => $component,
            'callback'  => $callback
        );
    }

    /**
     * Hilfsfunktion zum Hinzufügen von Hooks zum Collection-Array
     *
     * @param array  $hooks         Das bestehende Array von Hooks
     * @param string $hook          Der Hook-Name
     * @param object $component     Die Klasseninstanz
     * @param string $callback      Die Callback-Funktion
     * @param int    $priority      Die Priorität
     * @param int    $accepted_args Anzahl der Argumente
     * @return array Das aktualisierte Array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }

    /**
     * Registriere alle Actions, Filters und Shortcodes mit WordPress
     */
    public function run() {
        // Actions registrieren
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Filter registrieren
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Shortcodes registrieren
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'],
                array($shortcode['component'], $shortcode['callback'])
            );
        }
    }
}
