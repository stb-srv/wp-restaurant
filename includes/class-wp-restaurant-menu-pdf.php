<?php
/**
 * PDF Export für Restaurant-Speisekarten
 *
 * Diese Klasse generiert professionelle, druckbare PDFs aus den Menüpunkten
 * Nutzt TCPDF für hochwertige PDF-Generierung
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_PDF {

    /**
     * Initialisiere den PDF-Export
     */
    public static function init() {
        // Admin-Handler für PDF-Export
        add_action('init', array(__CLASS__, 'handle_pdf_export'));
    }

    /**
     * Handle PDF-Export Request
     */
    public static function handle_pdf_export() {
        // Überprüfe ob PDF-Export angefordert wurde
        if (isset($_GET['wpr_pdf_export']) && isset($_GET['_wpnonce'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'], 'wpr_pdf_export_nonce')) {
                wp_die('Sicherheitscheck fehlgeschlagen');
            }
            
            if (!current_user_can('manage_options')) {
                wp_die('Keine Berechtigung');
            }
            
            $menu_list = isset($_GET['menu_list']) ? sanitize_text_field($_GET['menu_list']) : '';
            $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
            
            self::generate_pdf($menu_list, $category);
            exit;
        }
    }

    /**
     * Generiere das PDF (nutzt TCPDF wenn verfügbar, sonst HTML2PDF)
     */
    private static function generate_pdf($menu_list = '', $category = '') {
        // HTML generieren
        $html = self::generate_html($menu_list, $category);
        
        // Versuche TCPDF zu laden
        if (self::generate_with_tcpdf($html)) {
            return;
        }
        
        // Fallback: Einfaches HTML-PDF
        self::generate_simple_pdf($html, $menu_list, $category);
    }

    /**
     * Generiere HTML für PDF
     */
    private static function generate_html($menu_list = '', $category = '') {
        $menu_items = self::get_menu_items($menu_list, $category);
        
        if (empty($menu_items)) {
            return '<p>' . __('Keine Menüpunkte gefunden.', 'wp-restaurant-menu') . '</p>';
        }

        $grouped = self::group_by_category($menu_items);
        
        $html = self::get_html_header();
        
        foreach ($grouped as $category_name => $items) {
            $html .= self::get_category_html($category_name, $items);
        }
        
        $html .= self::get_html_footer();
        
        return $html;
    }

    /**
     * HTML Header
     */
    private static function get_html_header() {
        $blog_name = get_bloginfo('name');
        $blog_desc = get_bloginfo('description');
        $date = date_i18n('j. F Y', current_time('timestamp'));
        
        return '<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: white; }
.header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #333; padding-bottom: 20px; }
.header h1 { margin: 0; font-size: 32px; color: #333; }
.header .desc { color: #666; font-size: 14px; margin: 5px 0; }
.header .date { color: #999; font-size: 12px; margin-top: 10px; }
.category { margin: 30px 0; page-break-inside: avoid; }
.category h2 { margin: 0 0 15px 0; font-size: 18px; color: #333; background: #f5f5f5; padding: 10px; border-left: 4px solid #666; }
.item { margin-bottom: 20px; page-break-inside: avoid; }
.item-header { display: flex; justify-content: space-between; align-items: baseline; }
.item-title { font-weight: bold; font-size: 14px; }
.item-price { font-weight: bold; color: #d97706; font-size: 14px; }
.item-desc { font-size: 12px; color: #666; margin: 5px 0; line-height: 1.4; }
.item-meta { font-size: 11px; color: #999; margin-top: 5px; font-style: italic; }
.footer { margin-top: 50px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
@media print { body { margin: 0; padding: 20px; } }
</style>
</head>
<body>
<div class="header">
    <h1>' . esc_html($blog_name) . '</h1>
    ' . ($blog_desc ? '<div class="desc">' . esc_html($blog_desc) . '</div>' : '') . '
    <div class="date">' . esc_html($date) . '</div>
</div>';
    }

    /**
     * Kategorie-HTML
     */
    private static function get_category_html($category_name, $items) {
        $html = '<div class="category"><h2>' . esc_html($category_name) . '</h2>';
        
        foreach ($items as $item) {
            $html .= self::get_item_html($item);
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Gericht-HTML
     */
    private static function get_item_html($item) {
        $title = $item->post_title;
        $description = wp_strip_all_tags($item->post_content);
        $price = get_post_meta($item->ID, '_menu_item_price', true);
        $allergens = get_post_meta($item->ID, '_menu_item_allergens', true);
        $vegetarian = get_post_meta($item->ID, '_menu_item_vegetarian', true);
        $vegan = get_post_meta($item->ID, '_menu_item_vegan', true);
        $spicy = get_post_meta($item->ID, '_menu_item_spicy_level', true);
        
        $meta = array();
        if ($vegan) {
            $meta[] = __('🌿 Vegan', 'wp-restaurant-menu');
        } elseif ($vegetarian) {
            $meta[] = __('🌱 Vegetarisch', 'wp-restaurant-menu');
        }
        if ($spicy > 0) {
            $meta[] = str_repeat('🌶', $spicy);
        }
        if ($allergens) {
            $meta[] = __('Allergene', 'wp-restaurant-menu') . ': ' . $allergens;
        }
        
        $html = '<div class="item">';
        $html .= '<div class="item-header">';
        $html .= '<span class="item-title">' . esc_html($title) . '</span>';
        if ($price) {
            $html .= '<span class="item-price">' . esc_html(self::format_price($price)) . '</span>';
        }
        $html .= '</div>';
        
        if ($description) {
            $html .= '<div class="item-desc">' . esc_html(substr($description, 0, 200)) . '</div>';
        }
        
        if (!empty($meta)) {
            $html .= '<div class="item-meta">' . esc_html(implode(' | ', $meta)) . '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * HTML Footer
     */
    private static function get_html_footer() {
        return '<div class="footer">' . __('Generiert mit WP Restaurant Menu', 'wp-restaurant-menu') . '</div></body></html>';
    }

    /**
     * Versuche PDF mit TCPDF zu generieren
     */
    private static function generate_with_tcpdf($html) {
        $tcpdf_path = WP_RESTAURANT_MENU_PLUGIN_DIR . 'vendor/autoload.php';
        
        if (!file_exists($tcpdf_path)) {
            return false;
        }
        
        try {
            require_once $tcpdf_path;
            
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('WP Restaurant Menu');
            $pdf->SetAuthor(get_bloginfo('name'));
            $pdf->SetTitle(__('Speisekarte', 'wp-restaurant-menu'));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 20, 15);
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 10);
            $pdf->WriteHTML($html);
            
            $filename = sanitize_file_name(get_bloginfo('name') . '-speisekarte.pdf');
            $pdf->Output($filename, 'D');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Einfache HTML-zu-PDF Fallback
     */
    private static function generate_simple_pdf($html, $menu_list = '', $category = '') {
        // Setze Header für PDF-Download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name(get_bloginfo('name') . '-speisekarte.pdf') . '"');
        
        // Gebe HTML aus mit CSS für Browser-Print
        echo '<!DOCTYPE html>';
        echo $html;
    }

    /**
     * Holen von Menüpunkten
     */
    private static function get_menu_items($menu_list = '', $category = '') {
        $args = array(
            'post_type' => 'restaurant_menu_item',
            'posts_per_page' => -1,
            'orderby' => array('menu_order' => 'ASC', 'post_title' => 'ASC'),
            'post_status' => 'publish',
        );

        $tax_query = array();

        if (!empty($menu_list)) {
            $tax_query[] = array(
                'taxonomy' => 'menu_list',
                'field' => 'slug',
                'terms' => $menu_list,
            );
        }

        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'menu_category',
                'field' => 'slug',
                'terms' => $category,
            );
        }

        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }

        return get_posts($args);
    }

    /**
     * Gruppiere Menüpunkte nach Kategorie
     */
    private static function group_by_category($items) {
        $grouped = array();

        foreach ($items as $item) {
            $categories = wp_get_post_terms($item->ID, 'menu_category', array('fields' => 'all'));

            if (empty($categories)) {
                $cat_name = __('Sonstige', 'wp-restaurant-menu');
            } else {
                $cat_name = $categories[0]->name;
            }

            if (!isset($grouped[$cat_name])) {
                $grouped[$cat_name] = array();
            }
            $grouped[$cat_name][] = $item;
        }

        return $grouped;
    }

    /**
     * Formatiere Preis
     */
    private static function format_price($price) {
        $settings = get_option('wp_restaurant_menu_settings', array(
            'currency_symbol' => '€',
            'currency_position' => 'after',
        ));

        $symbol = $settings['currency_symbol'];
        $position = $settings['currency_position'];

        if ($position === 'before') {
            return $symbol . ' ' . $price;
        }
        
        return $price . ' ' . $symbol;
    }
}
