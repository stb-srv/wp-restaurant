<?php
// Frontend Cart Handling for WP Restaurant Menu

if (!defined('ABSPATH')) {
    exit;
}

class WPR_Cart {
    const SESSION_KEY = 'wpr_cart_items';

    public static function init() {
        add_action('init', [__CLASS__, 'start_session'], 1);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_wpr_add_to_cart', [__CLASS__, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_wpr_add_to_cart', [__CLASS__, 'ajax_add_to_cart']);
        add_action('wp_ajax_wpr_remove_from_cart', [__CLASS__, 'ajax_remove_from_cart']);
        add_action('wp_ajax_nopriv_wpr_remove_from_cart', [__CLASS__, 'ajax_remove_from_cart']);
        add_shortcode('restaurant_cart', [__CLASS__, 'render_cart_shortcode']);
    }

    public static function start_session() {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }

    public static function enqueue_scripts() {
        wp_enqueue_script(
            'wpr-cart',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/cart.js',
            ['jquery'],
            WP_RESTAURANT_MENU_VERSION,
            true
        );

        wp_localize_script('wpr-cart', 'wprCart', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wpr_cart_nonce'),
        ]);

        wp_enqueue_style(
            'wpr-cart',
            WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/cart.css',
            [],
            WP_RESTAURANT_MENU_VERSION
        );
    }

    public static function ajax_add_to_cart() {
        check_ajax_referer('wpr_cart_nonce', 'nonce');

        $item_id  = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

        if (!$item_id || get_post_type($item_id) !== 'wpr_menu_item') {
            wp_send_json_error(['message' => 'Ungültiger Menüpunkt.']);
        }

        $cart = $_SESSION[self::SESSION_KEY] ?? [];

        if (isset($cart[$item_id])) {
            $cart[$item_id]['quantity'] += $quantity;
        } else {
            $price = get_post_meta($item_id, '_wpr_price', true);
            $cart[$item_id] = [
                'id'       => $item_id,
                'title'    => get_the_title($item_id),
                'price'    => $price,
                'quantity' => $quantity,
            ];
        }

        $_SESSION[self::SESSION_KEY] = $cart;

        wp_send_json_success([
            'cart'  => array_values($cart),
            'count' => self::get_cart_count(),
        ]);
    }

    public static function ajax_remove_from_cart() {
        check_ajax_referer('wpr_cart_nonce', 'nonce');

        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        $cart = $_SESSION[self::SESSION_KEY] ?? [];

        if (isset($cart[$item_id])) {
            unset($cart[$item_id]);
        }

        $_SESSION[self::SESSION_KEY] = $cart;

        wp_send_json_success([
            'cart'  => array_values($cart),
            'count' => self::get_cart_count(),
        ]);
    }

    public static function get_cart_items() {
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    public static function get_cart_count() {
        $cart = self::get_cart_items();
        $count = 0;
        foreach ($cart as $item) {
            $count += (int) $item['quantity'];
        }
        return $count;
    }

    public static function get_cart_total() {
        $cart  = self::get_cart_items();
        $total = 0;
        foreach ($cart as $item) {
            $total += (float) str_replace(',', '.', $item['price']) * (int) $item['quantity'];
        }
        return $total;
    }

    public static function render_cart_shortcode() {
        $license = WPR_License::get_license_info();
        $has_cart = $license['valid'] && in_array('cart', $license['features'], true);

        if (!$has_cart) {
            ob_start();
            ?>
            <div class="wpr-cart-locked">
                <p>🛒 Der Warenkorb ist ein <strong>PRO+</strong> Feature.</p>
                <p>Bitte aktivieren Sie eine PRO+ Lizenz, um den Warenkorb zu nutzen.</p>
            </div>
            <?php
            return ob_get_clean();
        }

        $cart  = self::get_cart_items();
        $total = self::get_cart_total();

        ob_start();
        ?>
        <div class="wpr-cart-wrapper">
            <div class="wpr-cart-header">
                <h3>🛒 Ihre Auswahl</h3>
                <span class="wpr-cart-count"><?php echo esc_html(self::get_cart_count()); ?> Artikel</span>
            </div>

            <?php if (empty($cart)) : ?>
                <p class="wpr-cart-empty">Ihr Warenkorb ist leer.</p>
            <?php else : ?>
                <ul class="wpr-cart-items">
                    <?php foreach ($cart as $item) : ?>
                        <li class="wpr-cart-item" data-id="<?php echo esc_attr($item['id']); ?>">
                            <div class="wpr-cart-item-main">
                                <span class="wpr-cart-item-title"><?php echo esc_html($item['title']); ?></span>
                                <span class="wpr-cart-item-quantity">x <?php echo esc_html($item['quantity']); ?></span>
                            </div>
                            <div class="wpr-cart-item-meta">
                                <span class="wpr-cart-item-price"><?php echo esc_html(wpr_format_price($item['price'])); ?></span>
                                <button class="wpr-cart-remove" type="button">Entfernen</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="wpr-cart-footer">
                    <div class="wpr-cart-total">
                        <span>Zwischensumme:</span>
                        <strong><?php echo esc_html(wpr_format_price(number_format($total, 2, ',', ''))); ?></strong>
                    </div>
                    <p class="wpr-cart-note">
                        Diese Auswahl ist unverbindlich. Sie können Ihre Bestellung im Restaurant final bestätigen und bezahlen.
                    </p>
                    <button class="wpr-cart-print" type="button">Auswahl speichern / zeigen</button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

WPR_Cart::init();
