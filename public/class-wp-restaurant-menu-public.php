<?php
/**
 * Public-facing Funktionalität
 *
 * @package    WP_Restaurant_Menu
 * @subpackage WP_Restaurant_Menu/public
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Restaurant_Menu_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Public Styles laden
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_RESTAURANT_MENU_PLUGIN_URL . 'public/css/wp-restaurant-menu-public.css',
            array(),
            $this->version
        );
    }

    /**
     * Public Scripts laden
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WP_RESTAURANT_MENU_PLUGIN_URL . 'public/js/wp-restaurant-menu-public.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Shortcode für Restaurant-Menü
     * 
     * Verwendung: [restaurant_menu category="hauptgerichte" columns="2"]
     */
    public function restaurant_menu_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => '1',
            'limit' => -1,
            'show_images' => 'yes',
        ), $atts);

        $args = array(
            'post_type' => 'restaurant_menu_item',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'menu_order',
            'order' => 'ASC',
        );

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'menu_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['category']),
                ),
            );
        }

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return '<p class="wpr-no-items">' . __('Keine Menüpunkte gefunden.', 'wp-restaurant-menu') . '</p>';
        }

        $columns = max(1, min(4, intval($atts['columns'])));
        $show_images = ($atts['show_images'] === 'yes');

        ob_start();
        ?>
        <div class="wpr-menu-grid wpr-columns-<?php echo esc_attr($columns); ?>">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                    $post_id = get_the_ID();
                    $price = get_post_meta($post_id, '_menu_item_price', true);
                    $allergens = get_post_meta($post_id, '_menu_item_allergens', true);
                    $spicy_level = get_post_meta($post_id, '_menu_item_spicy_level', true);
                    $vegetarian = get_post_meta($post_id, '_menu_item_vegetarian', true);
                    $vegan = get_post_meta($post_id, '_menu_item_vegan', true);
                ?>
                <article class="wpr-menu-item">
                    <?php if ($show_images && has_post_thumbnail()) : ?>
                        <div class="wpr-menu-item-image">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="wpr-menu-item-content">
                        <div class="wpr-menu-item-header">
                            <h3 class="wpr-menu-item-title"><?php the_title(); ?></h3>
                            <?php if ($price) : ?>
                                <span class="wpr-menu-item-price"><?php echo esc_html($this->format_price($price)); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (get_the_content()) : ?>
                            <div class="wpr-menu-item-description">
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>

                        <div class="wpr-menu-item-meta">
                            <?php if ($vegan) : ?>
                                <span class="wpr-badge wpr-badge-vegan">🌿 <?php _e('Vegan', 'wp-restaurant-menu'); ?></span>
                            <?php elseif ($vegetarian) : ?>
                                <span class="wpr-badge wpr-badge-vegetarian">🌱 <?php _e('Vegetarisch', 'wp-restaurant-menu'); ?></span>
                            <?php endif; ?>

                            <?php if ($spicy_level > 0) : ?>
                                <span class="wpr-badge wpr-badge-spicy">
                                    <?php echo str_repeat('🌶', intval($spicy_level)); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($allergens) : ?>
                                <span class="wpr-allergens">
                                    <?php _e('Allergene:', 'wp-restaurant-menu'); ?>
                                    <strong><?php echo esc_html($allergens); ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Formatiere Preis mit Währungssymbol
     */
    private function format_price($price) {
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
