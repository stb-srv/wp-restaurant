# WP Restaurant Menu - Complete Plugin Specification

**Version**: 1.7.2  
**Author**: STB-SRV  
**License**: GPL-2.0+  
**Purpose**: Modernes WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten mit Lizenz-Server

---

## 🎯 Überblick

Ein vollständiges Restaurant-Menü-Plugin für WordPress mit:
- **5 Lizenz-Modellen** (FREE, FREE+, PRO, PRO+, ULTIMATE)
- **Eigenständigem Lizenz-Server** mit MySQL-Datenbank
- **Dynamischer Preisverwaltung** über Admin-Panel
- **Dark Mode**, **Warenkorb-System**, **Allergenkennzeichnung**
- **Import/Export**, **Suchfunktion**, **Kategorien**

---

## 📚 Inhaltsverzeichnis

1. [Plugin-Struktur](#plugin-struktur)
2. [Lizenz-System](#lizenz-system)
3. [Features pro Lizenz](#features-pro-lizenz)
4. [Lizenz-Server](#lizenz-server)
5. [WordPress-Plugin](#wordpress-plugin)
6. [Frontend-Features](#frontend-features)
7. [Admin-Backend](#admin-backend)
8. [Datenbank-Schema](#datenbank-schema)
9. [API-Endpunkte](#api-endpunkte)
10. [Sicherheit](#sicherheit)

---

## 📁 Plugin-Struktur

```
wp-restaurant-menu/
├── wp-restaurant-menu.php          # Hauptdatei
├── wpr-license-api.php              # Legacy API (deprecated)
├── uninstall.php                    # Deinstallations-Script
├── README.md
├── CHANGELOG.md
├── SECURITY.md
├── FIXES.md
├── COMPLETE-SPECIFICATION.md        # Diese Datei
├── includes/
│   ├── class-wpr-license.php        # Lizenz-Management (v2.3.1)
│   └── class-wpr-import-export.php  # Import/Export
├── admin/
│   ├── settings-page.php            # Einstellungen UI
│   └── license-page.php             # Lizenz UI
├── assets/
│   ├── menu-styles.css              # Basis-Styles
│   ├── dark-mode.css                # Dark Mode Styles
│   ├── menu-search.js               # Suchfunktion
│   ├── menu-accordion.js            # Kategorie-Accordion
│   └── dark-mode.js                 # Dark Mode Toggle
├── blocks/
│   └── menu-block/                  # Gutenberg Block
├── public/
│   ├── shortcode-menu.php           # Shortcode Handler
│   └── templates/                   # Frontend Templates
└── license-server/                  # Eigenständiger Lizenz-Server
    ├── api.php                      # API Endpunkte (v2.1)
    ├── admin-panel.php              # Admin UI (v2.1)
    ├── setup.php                    # Datenbank Setup
    ├── db-config.php                # DB Konfiguration (nicht im Repo)
    └── includes/
        └── database.php              # Database Class (v2.2)
```

---

## 🔑 Lizenz-System

### 5 Lizenz-Modelle

| Modell | Preis | Gerichte | Features | Beschreibung |
|--------|-------|----------|----------|-------------|
| **FREE** | Kostenlos | 20 | Basis | Perfekt zum Testen und für kleine Restaurants |
| **FREE+** | 15€ einmalig | 60 | Basis | Erweiterte Kapazität für mittelgroße Menüs |
| **PRO** | 29€ einmalig | 200 | Basis | Professionelle Lösung für umfangreiche Speisekarten |
| **PRO+** | 49€ einmalig | 200 | Dark Mode, Cart | PRO + Dark Mode + Warenkorb-System |
| **ULTIMATE** | 79€ einmalig | 900 | Alle + Unlimited | Alle Features + unbegrenzte Gerichte |

### Lizenzschlüssel-Format

```
WPR-XXXXX-XXXXX-XXXXX          # 3 Segmente (Standard)
WPR-XXXXX-XXXXX-XXXXX-XXXXX    # 4 Segmente (erweitert)

Beispiel: WPR-ABC12-DEF34-GHI56
```

**Validierung**:
- Präfix: `WPR-`
- Segmente: 3 oder 4
- Zeichen: A-Z, 0-9 (nur Großbuchstaben)
- Länge pro Segment: 5 Zeichen

---

## ✨ Features pro Lizenz

### Basis-Features (alle Lizenzen)

✅ **Menü-Verwaltung**
- Custom Post Type `wpr_menu_item`
- Title, Description, Price, Dish Number
- Featured Image Support
- Menu Order / Sortierung

✅ **Taxonomien**
- Kategorien (hierarchisch): `wpr_category`
- Menükarten (nicht hierarchisch): `wpr_menu_list`

✅ **Allergene & Ernährung**
- 14 EU-Allergene (A-R)
- Vegetarisch / Vegan Kennzeichnung
- Icon-Darstellung

✅ **Frontend-Darstellung**
- Shortcode `[restaurant_menu]`
- Grid-Layout (1-3 Spalten)
- Accordion nach Kategorien
- Responsive Design
- Suchfunktion

✅ **Einstellungen**
- Währung (€, $, CHF, etc.)
- Währungsposition (vor/nach Preis)
- Bild-Position (oben/links)
- Grid-Spalten (1-3)
- Suche aktivieren/deaktivieren

✅ **Import/Export**
- JSON Export aller Menüpunkte
- JSON Import mit Überschreiben-Option
- Kategorien & Taxonomien inklusive

### Premium-Features

#### 🌙 Dark Mode (PRO+, ULTIMATE)
```php
if (WPR_License::has_dark_mode()) {
    // Dark Mode aktiv
}
```

**Features**:
- Globaler Dark Mode (ganze Website)
- Lokaler Dark Mode (nur Menü-Bereich)
- Automatisch (System-Einstellung)
- Manuell (Toggle Button)
- Position: Unten rechts/links
- Persistent (localStorage)

**CSS-Variablen**:
```css
:root[data-theme="dark"] {
    --bg-color: #1f2937;
    --text-color: #f3f4f6;
    --card-bg: #374151;
    --border-color: #4b5563;
}
```

#### 🛒 Warenkorb-System (PRO+, ULTIMATE)
```php
if (WPR_License::has_cart()) {
    // Warenkorb aktiv
}
```

**Features**:
- Add to Cart Button
- Cart Sidebar
- Menge anpassen
- Gesamtpreis berechnen
- LocalStorage Persistenz
- "Bestellung senden" Funktion

#### ♾️ Unlimited Items (ULTIMATE)
```php
if (WPR_License::has_unlimited_items()) {
    // Unbegrenzte Gerichte
}
```

**Features**:
- Keine Gerichte-Limits
- Maximale Grenze: 900+ Gerichte

---

## 🖥️ Lizenz-Server

### Architektur

```
Lizenz-Server (Eigenständige PHP-Anwendung)
├── MySQL Datenbank
├── REST API (api.php)
├── Admin-Panel (admin-panel.php)
└── Setup-Script (setup.php)
```

### API-Endpunkte

#### 1. Status Check
```
GET /api.php?action=status

Response:
{
    "status": "online",
    "version": "2.1"
}
```

#### 2. Lizenz prüfen
```
GET /api.php?action=check_license&key=WPR-XXXXX-XXXXX-XXXXX&domain=example.com

Response:
{
    "valid": true,
    "type": "pro_plus",
    "max_items": 200,
    "expires": "lifetime",
    "features": ["dark_mode", "cart"]
}
```

#### 3. Pricing abrufen
```
GET /api.php?action=get_pricing

Response:
{
    "pricing": {
        "free": {
            "price": 0,
            "currency": "€",
            "label": "FREE",
            "description": "Perfekt zum Testen...",
            "max_items": 20,
            "features": []
        },
        "free_plus": { ... },
        "pro": { ... },
        "pro_plus": { ... },
        "ultimate": { ... }
    }
}
```

#### 4. Debug (nur mit SECRET)
```
GET /api.php?action=debug&secret=XXX

Response: Alle Lizenzen und Statistiken
```

### Datenbank-Schema

#### Tabelle: `pricing`
```sql
CREATE TABLE pricing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_type VARCHAR(50) UNIQUE NOT NULL,
    price INT DEFAULT 0,
    currency VARCHAR(10) DEFAULT '€',
    label VARCHAR(100),
    description TEXT,
    max_items INT DEFAULT 20,
    features TEXT,  -- JSON Array
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_package_type (package_type),
    INDEX idx_price (price)
);
```

#### Tabelle: `licenses`
```sql
CREATE TABLE licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(100) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    domain VARCHAR(255),
    max_items INT DEFAULT 20,
    expires VARCHAR(50),
    features TEXT,  -- JSON Array
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (license_key),
    INDEX idx_type (type),
    INDEX idx_domain (domain),
    INDEX idx_expires (expires)
);
```

#### Tabelle: `logs`
```sql
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_type VARCHAR(50),
    message TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (log_type),
    INDEX idx_created (created_at),
    INDEX idx_ip (ip_address)
);
```

#### Tabelle: `config`
```sql
CREATE TABLE config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Admin-Panel Features

**Login**: `admin123` (änderbar in admin-panel.php Zeile 10)

**Funktionen**:
1. **Preisverwaltung**
   - Alle 5 Modelle editierbar
   - Preis, Währung, Label, Beschreibung
   - Sofortige Synchronisation

2. **Lizenzverwaltung** (geplant)
   - Neue Lizenzen erstellen
   - Bestehende bearbeiten
   - Domain zuweisen
   - Ablaufdatum setzen

3. **Statistiken** (geplant)
   - Aktive Lizenzen
   - Verteilung nach Typ
   - API-Aufrufe

### Setup-Script

**URL**: `/license-server/setup.php`  
**Passwort**: `setup123` (änderbar)

**Schritte**:
1. DB-Config prüfen
2. Verbindung testen
3. Tabellen erstellen
4. Standard-Preise einfügen
5. API testen

**Nach Setup**: Datei löschen!

---

## 👨‍💻 WordPress-Plugin

### Installation

1. **Plugin installieren**
   ```
   WordPress Admin → Plugins → Installieren → Hochladen
   wp-restaurant-menu.zip auswählen
   ```

2. **Plugin aktivieren**
   ```
   Aktivieren klicken
   ```

3. **Lizenz eingeben**
   ```
   Restaurant Menü → Lizenz → Schlüssel eingeben
   ```

### Custom Post Type: `wpr_menu_item`

**Registrierung**:
```php
function wpr_register_post_type() {
    register_post_type('wpr_menu_item', array(
        'labels' => array(
            'name' => 'Menüpunkte',
            'singular_name' => 'Menüpunkt',
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-food',
        'supports' => array('title', 'editor', 'thumbnail'),
    ));
}
```

**Meta Fields**:
- `_wpr_dish_number` (string) - Gericht-Nummer
- `_wpr_price` (string) - Preis
- `_wpr_vegan` (boolean) - Vegan
- `_wpr_vegetarian` (boolean) - Vegetarisch
- `_wpr_allergens` (array) - Allergene-Slugs

### Taxonomien

#### 1. Kategorien (`wpr_category`)
```php
register_taxonomy('wpr_category', 'wpr_menu_item', array(
    'hierarchical' => true,
    'labels' => array('name' => 'Kategorien'),
));
```

**Beispiele**:
- Vorspeisen
- Hauptgerichte
- Desserts
- Getränke

#### 2. Menükarten (`wpr_menu_list`)
```php
register_taxonomy('wpr_menu_list', 'wpr_menu_item', array(
    'hierarchical' => false,
    'labels' => array('name' => 'Menükarten'),
));
```

**Beispiele**:
- Mittagsmenü
- Abendkarte
- Saisonkarte
- Weihnachtsmenü

### Shortcode: `[restaurant_menu]`

**Verwendung**:
```php
// Alle Gerichte
[restaurant_menu]

// Bestimmte Kategorie
[restaurant_menu category="hauptgerichte"]

// Bestimmte Menükarte
[restaurant_menu menu="sommer"]

// Mit Optionen
[restaurant_menu columns="3" show_search="yes" group_by_category="no"]
```

**Parameter**:
| Parameter | Werte | Standard | Beschreibung |
|-----------|-------|----------|-------------|
| `category` | slug | - | Kategorie-Filter |
| `menu` | slug | - | Menükarten-Filter |
| `columns` | 1-3 | 2 | Anzahl Spalten |
| `show_search` | yes/no | yes | Suchfeld anzeigen |
| `show_images` | yes/no | yes | Bilder anzeigen |
| `image_position` | top/left | left | Bild-Position |
| `group_by_category` | yes/no | yes | Accordion-Gruppierung |

**Output-Struktur**:
```html
<div class="wpr-menu-container">
    <div class="wpr-search-container">
        <input type="text" class="wpr-menu-search" />
    </div>
    
    <div class="wpr-category-section">
        <button class="wpr-category-header">Kategorie-Name</button>
        <div class="wpr-category-items">
            <div class="wpr-menu-grid wpr-columns-2">
                <article class="wpr-menu-item">
                    <div class="wpr-menu-item-image">...</div>
                    <div class="wpr-menu-item-content">
                        <div class="wpr-menu-item-header">
                            <h3>Gericht-Titel</h3>
                            <span class="wpr-menu-item-price">12,50 €</span>
                        </div>
                        <div class="wpr-menu-item-description">...</div>
                        <div class="wpr-menu-item-meta">
                            <span class="wpr-badge wpr-badge-vegan">🌿</span>
                            <span class="wpr-allergens">A, C, G</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</div>
```

### Allergene-System

**14 EU-Allergene**:
```php
function wpr_get_allergens() {
    return array(
        'a' => array('name' => 'A - Glutenhaltiges Getreide', 'icon' => '🌾'),
        'b' => array('name' => 'B - Krebstiere', 'icon' => '🦀'),
        'c' => array('name' => 'C - Eier', 'icon' => '🥚'),
        'd' => array('name' => 'D - Fisch', 'icon' => '🐟'),
        'e' => array('name' => 'E - Erdnüsse', 'icon' => '🥜'),
        'f' => array('name' => 'F - Soja', 'icon' => '🌱'),
        'g' => array('name' => 'G - Milch/Laktose', 'icon' => '🥛'),
        'h' => array('name' => 'H - Schalenfrüchte', 'icon' => '🌰'),
        'l' => array('name' => 'L - Sellerie', 'icon' => '🥬'),
        'm' => array('name' => 'M - Senf', 'icon' => '🍯'),
        'n' => array('name' => 'N - Sesamsamen', 'icon' => '🌾'),
        'o' => array('name' => 'O - Schwefeldioxid', 'icon' => '🧪'),
        'p' => array('name' => 'P - Lupinen', 'icon' => '🏺'),
        'r' => array('name' => 'R - Weichtiere', 'icon' => '🦐'),
    );
}
```

**Frontend-Darstellung**:
```html
<span class="wpr-allergens">
    <span class="wpr-allergen-icon" title="A - Glutenhaltiges Getreide">A</span>
    <span class="wpr-allergen-icon" title="C - Eier">C</span>
    <span class="wpr-allergen-icon" title="G - Milch/Laktose">G</span>
</span>
```

---

## 🎨 Frontend-Features

### Responsive Design

**Breakpoints**:
```css
/* Desktop (Standard) */
.wpr-menu-grid.wpr-columns-2 {
    grid-template-columns: repeat(2, 1fr);
}

/* Tablet */
@media (max-width: 768px) {
    .wpr-menu-grid {
        grid-template-columns: 1fr !important;
    }
}

/* Mobile */
@media (max-width: 480px) {
    .wpr-menu-item {
        padding: 15px;
    }
}
```

### Suchfunktion

**JavaScript**:
```javascript
document.querySelector('.wpr-menu-search').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    
    document.querySelectorAll('.wpr-menu-item').forEach(item => {
        const title = item.dataset.searchTitle;
        const content = item.dataset.searchContent;
        
        if (title.includes(query) || content.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});
```

### Kategorie-Accordion

**JavaScript**:
```javascript
document.querySelectorAll('.wpr-category-header').forEach(header => {
    header.addEventListener('click', function() {
        const items = this.nextElementSibling;
        const isOpen = items.style.display !== 'none';
        
        items.style.display = isOpen ? 'none' : 'block';
        this.setAttribute('aria-expanded', !isOpen);
    });
});
```

### Dark Mode

**Toggle**:
```javascript
function toggleDarkMode() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('wpr-theme', newTheme);
}
```

**Auto-Detection**:
```javascript
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-theme', 'dark');
}
```

---

## ⚙️ Admin-Backend

### Menü-Struktur

```
Restaurant Menü (🍴 dashicons-food)
├── Alle Gerichte
├── Neues Gericht hinzufügen
├── Kategorien
├── Menükarten
├── ⚙️ Einstellungen
├── 🔑 Lizenz
└── 📊 Import / Export
```

### Einstellungen-Seite

**Kategorien**:

1. **Währungseinstellungen**
   - Währungssymbol (€, EUR, EURO, $, £, CHF)
   - Position (vor/nach Preis)

2. **Bild-Einstellungen**
   - Bilder anzeigen (ja/nein)
   - Bild-Position (oben/links)

3. **Layout-Einstellungen**
   - Suchfunktion (ja/nein)
   - Nach Kategorien gruppieren (ja/nein)
   - Spalten-Layout (1-3)

4. **Dark Mode** (🔒 PRO+ Feature)
   - Dark Mode aktivieren (ja/nein)
   - Bereich (global/nur Menü)
   - Umschalt-Methode (manuell/automatisch)
   - Toggle Position (unten rechts/links)

### Lizenz-Seite

**Bereiche**:

1. **Aktueller Status**
   - Lizenz-Typ (FREE/PRO+/etc.)
   - Gerichte (X / Max)
   - Features (Dark Mode, Cart, etc.)
   - Server-URL
   - Domain

2. **Verfügbare Lizenzen**
   - Grid mit 5 Karten
   - Preis, Beschreibung, Features
   - "AKTIV" Badge

3. **Lizenz aktivieren**
   - Input-Feld für Schlüssel
   - Aktivieren-Button
   - Deaktivieren-Button

4. **Aktionen**
   - 🔍 Server testen
   - 🔄 Preise aktualisieren

### Import/Export-Seite

**Export**:
```json
{
    "version": "1.7.2",
    "exported_at": "2024-12-19T10:00:00Z",
    "items": [
        {
            "title": "Pizza Margherita",
            "content": "Tomaten, Mozzarella, Basilikum",
            "price": "8.50",
            "dish_number": "12",
            "vegan": false,
            "vegetarian": true,
            "allergens": ["a", "g"],
            "categories": ["hauptgerichte"],
            "menu_lists": ["mittag"]
        }
    ]
}
```

**Import**:
- JSON-Datei hochladen
- Option: Bestehende überschreiben
- Vorschau vor Import
- Erfolgsmeldung mit Statistik

---

## 🔒 Sicherheit

### WordPress Plugin

**1. Nonce-Validierung**
```php
wp_nonce_field('wpr_license_action', 'wpr_license_nonce');

if (!check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
    wp_die('Security check failed');
}
```

**2. Input Sanitization**
```php
$key = sanitize_text_field($_POST['license_key']);
$price = sanitize_text_field($_POST['wpr_price']);
$allergens = array_map('sanitize_text_field', $_POST['wpr_allergens']);
```

**3. Capability Checks**
```php
if (!current_user_can('edit_post', $post_id)) {
    return;
}
```

**4. SQL Injection Prevention**
```php
// WordPress nutzt prepared statements
$wpdb->prepare("SELECT * FROM table WHERE id = %d", $id);
```

### Lizenz-Server

**1. Input Validation**
```php
function validate_license_key($key) {
    if (!preg_match('/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $key)) {
        return false;
    }
    return true;
}

function validate_domain($domain) {
    if (!filter_var('https://' . $domain, FILTER_VALIDATE_URL)) {
        return false;
    }
    return true;
}
```

**2. SQL Injection Prevention**
```php
$stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
$stmt->execute([strtoupper($key)]);
```

**3. XSS Prevention**
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

**4. Rate Limiting** (geplant)
```php
// IP-basiertes Rate Limiting
// Max 100 Requests pro Stunde
```

**5. Debug-Endpoint Protection**
```php
if ($action === 'debug') {
    $secret = getenv('DEBUG_SECRET') ?: 'change-me';
    if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
}
```

**6. Security Headers**
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

---

## 📦 Deployment

### WordPress Plugin

**1. Production Build**
```bash
# Dateien die ins Plugin gehören
wp-restaurant-menu/
├── wp-restaurant-menu.php
├── uninstall.php
├── README.md
├── includes/
├── admin/
├── assets/
├── blocks/
└── public/

# NICHT ins Plugin:
- license-server/  (separat deployen)
- .git/
- node_modules/
- *.md (außer README.md)
```

**2. ZIP erstellen**
```bash
zip -r wp-restaurant-menu.zip wp-restaurant-menu/ \
    -x "*.git*" \
    -x "*license-server*" \
    -x "*node_modules*"
```

**3. WordPress Installation**
```
1. wp-restaurant-menu.zip hochladen
2. Plugin aktivieren
3. Restaurant Menü → Lizenz → Schlüssel eingeben
```

### Lizenz-Server

**1. Dateien hochladen**
```bash
# Via FTP/SFTP
license-server/
├── api.php
├── admin-panel.php
├── setup.php
└── includes/
    └── database.php
```

**2. DB-Config erstellen**
```php
// license-server/db-config.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_licenses');
define('DB_USER', 'db_user');
define('DB_PASS', 'secure_password');
```

**3. Setup ausführen**
```
https://deine-domain.com/license-server/setup.php
Passwort: setup123
```

**4. Setup-Script löschen**
```bash
rm setup.php
```

**5. Passwörter ändern**
```php
// admin-panel.php Zeile 10
$ADMIN_PASSWORD = 'dein_sicheres_passwort';
```

---

## 📝 Code-Beispiele

### Plugin erstellen - Minimal

```php
<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Version: 1.7.2
 */

if (!defined('ABSPATH')) die();

define('WP_RESTAURANT_MENU_VERSION', '1.7.2');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Includes
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-license.php';

// Post Type
function wpr_register_post_type() {
    register_post_type('wpr_menu_item', [
        'labels' => ['name' => 'Menüpunkte'],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-food',
        'supports' => ['title', 'editor', 'thumbnail'],
    ]);
}
add_action('init', 'wpr_register_post_type');

// Shortcode
function wpr_menu_shortcode($atts) {
    $query = new WP_Query(['post_type' => 'wpr_menu_item']);
    
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="wpr-menu-container">';
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <article class="wpr-menu-item">
                <h3><?php the_title(); ?></h3>
                <?php the_content(); ?>
            </article>
            <?php
        }
        echo '</div>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('restaurant_menu', 'wpr_menu_shortcode');
```

### Lizenz-Server - Minimal

```php
<?php
// api.php
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'status') {
    echo json_encode(['status' => 'online', 'version' => '2.1']);
    exit;
}

if ($action === 'get_pricing') {
    $pricing = [
        'free' => [
            'price' => 0,
            'currency' => '€',
            'label' => 'FREE',
            'max_items' => 20,
            'features' => []
        ],
        // ... weitere Pakete
    ];
    
    echo json_encode(['pricing' => $pricing]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
```

---

## 🛣️ Roadmap / Geplante Features

### Version 1.8.0
- ☐ Vollständige Warenkorb-Implementation
- ☐ QR-Code Generator für Menüs
- ☐ PDF-Export
- ☐ Multi-Language Support (WPML/Polylang)

### Version 1.9.0
- ☐ Online-Bestellungen
- ☐ Tisch-Reservierungen
- ☐ Zahlungs-Integration (Stripe/PayPal)

### Version 2.0.0
- ☐ REST API für Mobile Apps
- ☐ Custom Themes
- ☐ Advanced Analytics

---

## 🐞 Bekannte Probleme & Lösungen

Siehe `FIXES.md` für vollständige Liste.

**Häufigste Probleme**:
1. "Keine Menüpunkte gefunden" → Gerichte veröffentlichen
2. HTTP 500 Admin-Panel → DB-Config prüfen
3. Preise nicht sichtbar → Setup-Script ausführen

---

## 📚 Ressourcen

- **GitHub**: https://github.com/stb-srv/wp-restaurant
- **Lizenz-Server**: https://license-server.stb-srv.de
- **Support**: s.behncke@icloud.com
- **Dokumentation**: README.md

---

## ✏️ Zusammenfassung für Prompt

**Diese Spezifikation enthält alle Informationen um das Plugin von Grund auf neu zu erstellen:**

1. ✅ Vollständige Dateistruktur
2. ✅ 5 Lizenz-Modelle mit Features
3. ✅ Eigenständiger Lizenz-Server
4. ✅ Datenbank-Schema
5. ✅ API-Endpunkte
6. ✅ WordPress Integration
7. ✅ Frontend & Backend UI
8. ✅ Sicherheits-Maßnahmen
9. ✅ Code-Beispiele
10. ✅ Deployment-Anleitung

**Verwende diese Datei als Prompt-Basis für AI-Tools wie:**
- ChatGPT/Claude: "Erstelle mir basierend auf COMPLETE-SPECIFICATION.md..."
- GitHub Copilot: "Generate code according to specification in COMPLETE-SPECIFICATION.md"
- Cursor AI: "Implement features from COMPLETE-SPECIFICATION.md"

---

**Version**: 1.0  
**Letzte Aktualisierung**: 19. Dezember 2024  
**Autor**: STB-SRV  
**Status**: ✅ Vollständig & Production-Ready
