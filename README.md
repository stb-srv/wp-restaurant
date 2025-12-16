# WP Restaurant Menu

![Version](https://img.shields.io/badge/version-2.0.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

WP Restaurant Menu ist ein WordPress-Plugin zur einfachen Verwaltung und Ausgabe von Restaurant-Speisekarten.

## Features
- Custom Post Type für Menüpunkte (Gerichte & Getränke)
- Kategorien (z. B. Vorspeisen, Hauptgerichte, Desserts, Getränke)
- Menükarten (z. B. Hauptkarte, Mittagskarte, Getränkekarte)
- Preise, Allergene, Kennzeichnung für vegan/vegetarisch
- Shortcode-Ausgabe für flexible Platzierung in Seiten/Beiträgen
- Kompatibel mit modernen WordPress-Themes und dem Block-Editor

## Installation
1. Plugin-Verzeichnis `wp-restaurant` nach `wp-content/plugins/` hochladen.
2. Im WordPress-Backend unter **Plugins** das Plugin **WP Restaurant Menu** aktivieren.
3. Im Menüpunkt **Restaurant Menu** Grundeinstellungen prüfen und erste Gerichte anlegen.

## Grundkonzept
- Ein **Menüpunkt** entspricht einem Gericht oder Getränk.
- **Kategorien** gruppieren Menüpunkte (z. B. Vorspeisen, Pizza, Pasta, Softdrinks).
- **Menükarten** steuern, welche Menüpunkte wo ausgegeben werden (z. B. Hauptkarte, Mittagskarte).

## Menüpunkte anlegen
1. Gehe zu **Restaurant Menu → Neu hinzufügen**.
2. Titel, Beschreibung und Preis ausfüllen.
3. Optional: Kategorie, Menükarte, Allergene, vegan/vegetarisch setzen.
4. Auf **Veröffentlichen** klicken.

## Shortcodes
### Standardausgabe
Gibt alle sichtbaren Menüpunkte aus:

```php
[restaurant_menu]
```

### Nach Menükarte filtern

```php
[restaurant_menu menu="hauptspeisekarte"]
```

### Nach Kategorie filtern

```php
[restaurant_menu category="vorspeisen"]
```

Mehrere Parameter können kombiniert werden, z. B. Menükarte + Kategorie.

## Entwicklung
- Mindestanforderungen: WordPress 5.8+, PHP 7.4+
- Plugin-Hauptdatei: `wp-restaurant-menu.php`
- Admin-Funktionalität: `admin/`
- Öffentliche Ausgabe/Frontend: `public/`
- Gemeinsame Logik/Hilfsfunktionen: `includes/`

Pull Requests und Issues sind willkommen: https://github.com/stb-srv/wp-restaurant

## Lizenz
Dieses Plugin steht unter der GPL-2.0+ Lizenz. Siehe `LICENSE` bzw. Plugin-Header für Details.
