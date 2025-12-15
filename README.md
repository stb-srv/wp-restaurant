# WP Restaurant Menu

![Version](https://img.shields.io/badge/version-2.0.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

## Beschreibung
Ein einfaches WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten.

## Installation
1. Plugin in `wp-content/plugins/wp-restaurant/` hochladen
2. Plugin im WordPress-Admin aktivieren
3. Unter "Restaurant Menu" Gerichte anlegen

## Verwendung

### Menüpunkte erstellen
1. Gehe zu **Restaurant Menu** > **Neu hinzufügen**
2. Fülle Name, Beschreibung, Preis aus
3. Wähle Kategorie und Menükarte
4. Veröffentlichen

### Shortcode
Zeige die Speisekarte auf einer Seite an:

```
[restaurant_menu]
```

Mit Filtern:
```
[restaurant_menu menu="hauptspeisekarte"]
[restaurant_menu category="vorspeisen"]
```

## Features
- Custom Post Type für Menüpunkte
- Kategorien (Vorspeisen, Hauptgerichte, etc.)
- Menükarten (Hauptkarte, Getränkekarte)
- Preis, Allergene, Vegan/Vegetarisch
- Shortcode zur Anzeige

## Lizenz
GPL-2.0+
