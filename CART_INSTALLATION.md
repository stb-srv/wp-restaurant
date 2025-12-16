# Warenkorb-Feature Integration - Installations-Anleitung

## 🚨 Problem
Der Warenkorb ist im Code vorhanden, aber noch nicht vollständig in die Hauptdatei integriert.

## ✅ Lösung

### Schritt 1: Hauptdatei aktualisieren

Fügen Sie **oben in der `wp-restaurant-menu.php`** (nach Zeile 18) folgende Zeile hinzu:

```php
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-cart.php';
```

Die Zeilen 16-18 sollten dann so aussehen:
```php
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-import-export.php';
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-license.php';
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-cart.php';
```

### Schritt 2: Einstellungen erweitern

Fügen Sie in der `wpr_activate()` Funktion (ca. Zeile 32) folgende Zeilen hinzu:

```php
'cart_enabled' => 'yes',
'cart_button_text' => '🛒 In den Warenkorb',
```

Die komplette $settings-Array sollte so aussehen:
```php
add_option('wpr_settings', array(
    'currency_symbol' => '€',
    'currency_position' => 'after',
    'show_images' => 'yes',
    'image_position' => 'left',
    'show_search' => 'yes',
    'group_by_category' => 'yes',
    'grid_columns' => '2',
    'dark_mode_enabled' => 'no',
    'dark_mode_method' => 'manual',
    'dark_mode_position' => 'bottom-right',
    'cart_enabled' => 'yes',
    'cart_button_text' => '🛒 In den Warenkorb',
));
```

### Schritt 3: Cart-Button in Gerichten anzeigen

Ersetzen Sie die Funktion `wpr_render_single_item()` (ca. Zeile 820) mit dieser erweiterten Version:

```php
function wpr_render_single_item($item, $show_images, $image_position) {
    $dish_number = get_post_meta($item->ID, '_wpr_dish_number', true);
    $price = get_post_meta($item->ID, '_wpr_price', true);
    $allergen_slugs = get_post_meta($item->ID, '_wpr_allergens', true);
    $vegan = get_post_meta($item->ID, '_wpr_vegan', true);
    $vegetarian = get_post_meta($item->ID, '_wpr_vegetarian', true);
    $has_image = has_post_thumbnail($item->ID);
    $item_categories = wp_get_post_terms($item->ID, 'wpr_category', array('fields' => 'slugs'));
    $cat_classes = is_array($item_categories) ? implode(' ', array_map(function($c) { return 'wpr-cat-' . $c; }, $item_categories)) : '';
    
    // Cart-Einstellungen
    $settings = get_option('wpr_settings');
    $cart_enabled = isset($settings['cart_enabled']) && $settings['cart_enabled'] === 'yes';
    $cart_button_text = isset($settings['cart_button_text']) ? $settings['cart_button_text'] : '🛒 In den Warenkorb';
    
    $license = WPR_License::get_license_info();
    $has_cart = $license['valid'] && in_array('cart', $license['features'], true);
    
    $all_allergens = wpr_get_allergens();
    $allergens = array();
    if (is_array($allergen_slugs)) {
        foreach ($allergen_slugs as $slug) {
            if (isset($all_allergens[$slug])) {
                $allergens[] = array(
                    'slug' => $slug,
                    'name' => $all_allergens[$slug]['name'],
                    'icon' => $all_allergens[$slug]['icon'],
                );
            }
        }
    }
    
    ob_start();
    ?>
    <div class="wpr-menu-item <?php echo ($image_position === 'left' && $has_image) ? 'wpr-has-image-left' : ''; ?> <?php echo ($image_position === 'top' && $has_image) ? 'wpr-has-image-top' : ''; ?> <?php echo esc_attr($cat_classes); ?>" 
         data-title="<?php echo esc_attr(strtolower($item->post_title)); ?>" 
         data-description="<?php echo esc_attr(strtolower(wp_strip_all_tags($item->post_content))); ?>" 
         data-number="<?php echo esc_attr($dish_number); ?>">
        
        <?php if ($show_images && $has_image) : ?>
            <div class="wpr-menu-item-image">
                <?php if ($dish_number) : ?>
                    <div class="wpr-dish-number-badge"><?php echo esc_html($dish_number); ?></div>
                <?php endif; ?>
                <?php echo get_the_post_thumbnail($item->ID, 'medium'); ?>
            </div>
        <?php endif; ?>
        
        <div class="wpr-menu-item-content">
            <div class="wpr-menu-item-header">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <?php if ($dish_number && (!$show_images || !$has_image)) : ?>
                        <span class="wpr-dish-number-inline"><?php echo esc_html($dish_number); ?></span>
                    <?php endif; ?>
                    <h3 class="wpr-menu-item-title"><?php echo esc_html($item->post_title); ?></h3>
                </div>
                <?php if ($price) : ?>
                    <span class="wpr-menu-item-price"><?php echo esc_html(wpr_format_price($price)); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($item->post_content)) : ?>
                <div class="wpr-menu-item-description">
                    <?php echo wpautop($item->post_content); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($vegan || $vegetarian || !empty($allergens)) : ?>
                <div class="wpr-menu-item-meta">
                    <div class="wpr-meta-badges">
                        <?php if ($vegan) : ?>
                            <span class="wpr-badge wpr-badge-vegan">🌿 Vegan</span>
                        <?php elseif ($vegetarian) : ?>
                            <span class="wpr-badge wpr-badge-vegetarian">🌱 Vegetarisch</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($allergens)) : ?>
                            <?php foreach ($allergens as $allergen) : ?>
                                <span class="wpr-badge wpr-badge-allergen" data-tooltip="<?php echo esc_attr($allergen['name']); ?>">
                                    <?php echo esc_html($allergen['icon']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($cart_enabled && $has_cart && !empty($price)) : ?>
                <div class="wpr-cart-actions">
                    <button class="wpr-add-to-cart" type="button" data-id="<?php echo esc_attr($item->ID); ?>">
                        <?php echo esc_html($cart_button_text); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

### Schritt 4: Cart-Settings in Admin-Einstellungen

Fügen Sie in der `wpr_render_settings_page()` Funktion (nach den Dark Mode Einstellungen, ca. Zeile 250) folgenden Abschnitt hinzu:

```php
<!-- Warenkorb Einstellungen -->
<h2 style="display: flex; align-items: center; gap: 10px;">
    🛒 Warenkorb
    <?php 
    $license = WPR_License::get_license_info();
    $has_cart = $license['valid'] && in_array('cart', $license['features'], true);
    ?>
    <?php if ($has_cart) : ?>
        <span style="background: #1f2937; color: #fbbf24; padding: 4px 12px; border-radius: 4px; font-size: 0.8em; font-weight: normal;">PRO+</span>
    <?php else : ?>
        <span style="background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 4px; font-size: 0.8em; font-weight: normal;">🔒 Lizenz erforderlich</span>
    <?php endif; ?>
</h2>

<?php if (!$has_cart) : ?>
    <div style="padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; margin-bottom: 20px;">
        <p style="margin: 0;"><strong>🔒 Warenkorb ist ein PRO+ Feature</strong></p>
        <p style="margin: 10px 0 0 0;">Mit dem Warenkorb können Gäste ihre Auswahl vorab zusammenstellen.</p>
        <a href="<?php echo admin_url('edit.php?post_type=wpr_menu_item&page=wpr-license'); ?>" class="button button-primary" style="margin-top: 10px;">🔑 Jetzt upgraden</a>
    </div>
<?php endif; ?>

<table class="form-table">
    <tr>
        <th scope="row"><label for="cart_enabled">Warenkorb aktivieren</label></th>
        <td>
            <select name="cart_enabled" id="cart_enabled" style="min-width: 200px;" <?php echo $has_cart ? '' : 'disabled'; ?>>
                <option value="yes" <?php selected($settings['cart_enabled'], 'yes'); ?>>Ja, Warenkorb aktivieren</option>
                <option value="no" <?php selected($settings['cart_enabled'], 'no'); ?>>Nein, deaktiviert</option>
            </select>
            <p class="description">Ermöglicht Gästen, Gerichte in einen Warenkorb zu legen.</p>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="cart_button_text">Button-Text</label></th>
        <td>
            <input type="text" name="cart_button_text" id="cart_button_text" value="<?php echo esc_attr($settings['cart_button_text'] ?? '🛒 In den Warenkorb'); ?>" style="min-width: 300px;" <?php echo $has_cart ? '' : 'disabled'; ?>>
            <p class="description">Text des "In den Warenkorb"-Buttons.</p>
        </td>
    </tr>
</table>
```

### Schritt 5: Einstellungen speichern

Fügen Sie in der `wpr_render_settings_page()` Funktion beim Speichern (ca. Zeile 180) folgende Zeilen hinzu:

```php
'cart_enabled' => sanitize_text_field($_POST['cart_enabled']),
'cart_button_text' => sanitize_text_field($_POST['cart_button_text']),
```

### Schritt 6: Cart-Badge global anzeigen

Fügen Sie in der `wpr_menu_shortcode()` Funktion (am Ende, vor `</div>`) folgenden Code hinzu:

```php
<?php 
$license = WPR_License::get_license_info();
$has_cart = $license['valid'] && in_array('cart', $license['features'], true);
if ($has_cart) : 
?>
    <div class="wpr-cart-counter-badge">
        🛒 <span class="wpr-cart-counter">0</span>
    </div>
<?php endif; ?>
```

### Schritt 7: CSS für Cart-Button

Fügen Sie in `assets/cart.css` folgende Styles hinzu:

```css
.wpr-cart-actions {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f3f4f6;
}

.wpr-add-to-cart {
  background: #111827;
  color: #ffffff;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.wpr-add-to-cart:hover {
  background: #1f2937;
  transform: translateY(-1px);
}

.wpr-add-to-cart.wpr-added {
  background: #10b981;
}
```

## 🎯 Verwendung

### Warenkorb auf Seite einbinden

Fügen Sie den Shortcode auf einer Seite ein:

```
[restaurant_cart]
```

### Menü mit Warenkorb-Buttons

Das normale Menü zeigt automatisch die Buttons an, wenn:
- PRO+ Lizenz aktiv ist
- "cart" im Feature-Array enthalten ist
- Warenkorb in den Einstellungen aktiviert ist
- Das Gericht einen Preis hat

```
[restaurant_menu]
```

## 🔐 Lizenz-Feature aktivieren

In der `class-wpr-license.php` müssen Sie das Feature `'cart'` in das `features`-Array der PRO+ Lizenz hinzufügen:

```php
'features' => ['dark_mode', 'unlimited_items', 'cart']
```

## ✅ Fertig!

Nach diesen Schritten sollte der Warenkorb vollständig funktionieren.