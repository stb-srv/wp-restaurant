# 🍽️ WP Restaurant Menu

![Version](https://img.shields.io/badge/version-1.6.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-brightgreen.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-orange.svg)

Modernes WordPress-Plugin zur professionellen Verwaltung von Restaurant-Speisekarten mit umfangreichen Funktionen für Gastronomiebetriebe.

## ✨ Features

### Kernfunktionen
- 📋 **Menü-Verwaltung** - Erstellen und verwalten Sie unbegrenzt viele Gerichte
- 🏷️ **Kategorisierung** - Organisieren Sie Gerichte in Kategorien und Menükarten
- 💰 **Flexible Preisgestaltung** - Individuelle Währungen (€, $, £, CHF, etc.) und Positionierung
- 🖼️ **Bild-Upload** - Hochwertige Produktbilder mit flexibler Positionierung (links/oben)
- 🔍 **Live-Suche** - Echtzeit-Suchfunktion mit Overlay und Kategorie-Filter
- 📊 **Responsive Design** - Optimiert für Desktop, Tablet und Mobile

### Allergene & Ernährung
- 🥜 **14 EU-Allergene** - Vollständige Kennzeichnung nach EU-Richtlinien
- 🌱 **Vegetarisch/Vegan** - Spezielle Badges für vegetarische und vegane Gerichte
- 🍯 **Icon-Darstellung** - Visuelle Allergenkennzeichnung mit Emojis

### Layout-Optionen
- 📱 **Accordion-Ansicht** - Aufklappbare Kategorien für übersichtliche Darstellung
- 🎨 **Grid-Layout** - 1-3 Spalten-Layout (automatisch responsive)
- 🌙 **Dark Mode** - Automatischer oder manueller Dark Mode (PRO+ Feature)
- 🎯 **Gericht-Nummerierung** - Optionale Nummerierung (z.B. "12" oder "A5")

### Import/Export
- 📤 **JSON Export** - Vollständige Datensicherung aller Gerichte
- 📥 **JSON Import** - Einfacher Import von Gerichten aus Backups
- 📊 **CSV Export** - Export für Excel/Tabellenkalkulation
- 🖼️ **Bild-Export** - Optional mit allen Produktbildern als ZIP

### Pro Features
- 🔓 **Unlimited Items** - Unbegrenzte Anzahl an Gerichten (mit Lizenz)
- 🌙 **Dark Mode** - Vollständiger Dark Mode Support
- 📄 **PDF Export** - Menükarten als PDF exportieren (in Entwicklung)
- 🔧 **Premium Support** - Vorrangiger Support

## 📦 Installation

### Automatische Installation
1. WordPress Admin → Plugins → Neu hinzufügen
2. "WP Restaurant Menu" suchen
3. Installieren und aktivieren

### Manuelle Installation
1. Plugin-Dateien in `/wp-content/plugins/wp-restaurant-menu/` hochladen
2. WordPress Admin → Plugins → WP Restaurant Menu aktivieren
3. Menü über "Restaurant Menu" im Admin-Bereich verwalten

## 🚀 Verwendung

### Shortcode
Fügen Sie das Menü auf jeder Seite oder jedem Beitrag ein:

```php
[restaurant_menu]
```

#### Shortcode-Parameter

**Nach Menükarte filtern:**
```php
[restaurant_menu menu="mittagskarte"]
```

**Nach Kategorie filtern:**
```php
[restaurant_menu category="vorspeisen"]
```

**Spalten-Anzahl anpassen:**
```php
[restaurant_menu columns="3"]
```

**Kombiniert:**
```php
[restaurant_menu menu="abendkarte" category="hauptgerichte" columns="2"]
```

### Gericht erstellen

1. **Restaurant Menu** → **Neues Gericht**
2. Titel und Beschreibung eingeben
3. Preis und optionale Gericht-Nummer hinzufügen
4. Produktbild hochladen (optional)
5. Kategorien und Menükarten zuweisen
6. Allergene und Ernährungsweise markieren
7. Veröffentlichen

## ⚙️ Einstellungen

### Währung
- **Symbol**: €, EUR, EURO, $, £, CHF
- **Position**: Vor oder nach dem Preis

### Bilder
- **Anzeige**: Ein/Aus
- **Position**: Oben oder Links

### Layout
- **Suche**: Ein/Aus
- **Gruppierung**: Accordion oder Grid
- **Spalten**: 1-3 Spalten (Desktop)

### Dark Mode (PRO+)
- **Aktivierung**: Manual oder Automatisch
- **Position**: Unten Rechts/Links
- **System-Integration**: Folgt Geräte-Einstellung

## 🏗️ Struktur

```
wp-restaurant-menu/
├── admin/              # Admin-spezifische Funktionen
├── assets/            # CSS, JavaScript, Bilder
│   ├── menu-styles.css
│   ├── menu-search.js
│   ├── menu-accordion.js
│   └── dark-mode.css
├── blocks/            # Gutenberg Blocks (in Entwicklung)
├── includes/          # Kern-Klassen
│   ├── class-wpr-import-export.php
│   ├── class-wpr-license.php
│   └── ...
├── license-server/    # Lizenz-Validierung
├── public/           # Frontend-Funktionen
└── wp-restaurant-menu.php  # Haupt-Plugin-Datei
```

## 🔧 Entwicklung

### Voraussetzungen
- PHP 7.4+
- WordPress 5.0+
- MySQL 5.6+

### Custom Post Type
Das Plugin registriert den Custom Post Type `wpr_menu_item` mit folgenden Taxonomien:
- `wpr_category` - Kategorien (hierarchisch)
- `wpr_menu_list` - Menükarten (nicht-hierarchisch)

### Hooks & Filter

**Action Hooks:**
```php
do_action('wpr_before_menu_render', $atts);
do_action('wpr_after_menu_render', $atts);
```

**Filter Hooks:**
```php
apply_filters('wpr_menu_args', $args);
apply_filters('wpr_item_html', $html, $item);
```

## 📝 Allergene

Das Plugin unterstützt alle 14 EU-Pflicht-Allergene:

| Code | Allergen | Icon |
|------|----------|------|
| A | Glutenhaltiges Getreide | 🌾 |
| B | Krebstiere | 🦀 |
| C | Eier | 🥚 |
| D | Fisch | 🐟 |
| E | Erdnüsse | 🥜 |
| F | Soja | 🌱 |
| G | Milch/Laktose | 🥛 |
| H | Schalenfrüchte | 🌰 |
| L | Sellerie | 🥬 |
| M | Senf | 🍯 |
| N | Sesamsamen | 🌾 |
| O | Schwefeldioxid | 🧪 |
| P | Lupinen | 🌺 |
| R | Weichtiere | 🦐 |

## 🔐 Lizenzierung

Das Plugin bietet verschiedene Lizenz-Stufen:

- **Free** - Bis zu 20 Gerichte
- **Basic** - Bis zu 50 Gerichte
- **Pro** - Bis zu 100 Gerichte
- **Pro+** - Unbegrenzte Gerichte + Dark Mode

Lizenz-Management über: **Restaurant Menu** → **🔑 Lizenz**

## 🐛 Fehlerbehebung

### Shortcode funktioniert nicht
- Stellen Sie sicher, dass das Plugin aktiviert ist
- Prüfen Sie, ob Gerichte veröffentlicht sind
- Cache leeren (falls Caching-Plugin aktiv)

### Bilder werden nicht angezeigt
- Prüfen Sie die Einstellungen unter "Bild-Einstellungen"
- Stellen Sie sicher, dass Beitragsbilder aktiviert sind
- Überprüfen Sie Dateirechte im Upload-Verzeichnis

### Dark Mode nicht verfügbar
- Dark Mode benötigt eine PRO+ Lizenz
- Aktivieren Sie die Lizenz unter **🔑 Lizenz**

## 📄 Changelog

### Version 1.6.0
- ✨ Dark Mode Support (PRO+)
- 🔍 Verbesserte Suchfunktion
- 📱 Optimiertes Mobile Design
- 🐛 Diverse Bugfixes

## 👥 Support

Bei Fragen oder Problemen:
- **GitHub Issues**: [github.com/stb-srv/wp-restaurant/issues](https://github.com/stb-srv/wp-restaurant/issues)
- **Dokumentation**: [Wiki](https://github.com/stb-srv/wp-restaurant/wiki)

## 📜 Lizenz

Dieses Plugin ist unter der GPL-2.0+ Lizenz lizenziert.

## 🙏 Credits

Entwickelt von **STB-SRV**

---

Made with ❤️ for the restaurant industry
