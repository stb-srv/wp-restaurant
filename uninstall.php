<?php
/**
 * Plugin-Deinstallation
 *
 * Diese Datei wird ausgeführt, wenn das Plugin über WordPress deinstalliert wird.
 * Sie löscht alle Plugin-Daten aus der Datenbank.
 *
 * @package WP_Restaurant_Menu
 */

// Sicherheitscheck: Nur ausführen wenn WordPress die Deinstallation aufruft
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Lösche alle Menüpunkte
 */
function wpr_delete_all_menu_items() {
    $args = array(
        'post_type' => 'restaurant_menu_item',
        'posts_per_page' => -1,
        'post_status' => 'any',
    );

    $posts = get_posts($args);

    foreach ($posts as $post) {
        // Lösche alle Meta-Daten
        delete_post_meta($post->ID, '_menu_item_price');
        delete_post_meta($post->ID, '_menu_item_allergens');
        delete_post_meta($post->ID, '_menu_item_spicy_level');
        delete_post_meta($post->ID, '_menu_item_vegetarian');
        delete_post_meta($post->ID, '_menu_item_vegan');

        // Lösche den Post endgültig (force delete)
        wp_delete_post($post->ID, true);
    }
}

/**
 * Lösche alle Taxonomie-Begriffe
 */
function wpr_delete_all_terms() {
    $taxonomies = array('menu_category', 'menu_tag', 'menu_list');

    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
}

/**
 * Lösche Plugin-Optionen
 */
function wpr_delete_options() {
    delete_option('wp_restaurant_menu_version');
    delete_option('wp_restaurant_menu_settings');
}

// Nur löschen, wenn der Benutzer explizit deinstalliert
// (nicht bei einfacher Deaktivierung)
if (current_user_can('activate_plugins')) {
    wpr_delete_all_menu_items();
    wpr_delete_all_terms();
    wpr_delete_options();

    // Flush Rewrite Rules
    flush_rewrite_rules();
}
