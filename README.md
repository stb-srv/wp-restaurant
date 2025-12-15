# WP Restaurant Menu - WordPress Plugin

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-brightgreen.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-orange.svg)

## 📋 Beschreibung

WP Restaurant Menu ist ein modernes, benutzerfreundliches WordPress-Plugin zur vollumfänglichen Verwaltung von Restaurant-Speisekarten. Mit einer intuitiven Benutzeroberfläche können Sie Menüpunkte erstellen, bearbeiten, löschen und kategorisieren.

## ✨ Features

- **Vollständige CRUD-Funktionalität**: Erstellen, Lesen, Aktualisieren und Löschen von Menüpunkten
- **Kategorien-Verwaltung**: Organisieren Sie Ihre Speisen in Kategorien (Vorspeisen, Hauptgerichte, Desserts, etc.)
- **Moderne Admin-Oberfläche**: Benutzerfreundliches Dashboard im WordPress-Backend
- **Responsive Design**: Funktioniert perfekt auf Desktop, Tablet und Mobile
- **Preise & Allergene**: Verwalten Sie Preise, Beschreibungen und Allergen-Informationen
- **Bilder-Upload**: Fügen Sie ansprechende Bilder zu jedem Menüpunkt hinzu
- **Shortcode-Integration**: Einfache Einbindung der Speisekarte auf jeder Seite
- **Mehrsprachig vorbereitet**: Translation-ready mit .pot Datei

## 🚀 Installation

### Manuelle Installation

1. Laden Sie das Plugin herunter
2. Entpacken Sie die Dateien in `/wp-content/plugins/wp-restaurant-menu/`
3. Aktivieren Sie das Plugin über das WordPress-Admin-Panel unter "Plugins"
4. Konfigurieren Sie das Plugin unter "Restaurant Menu" im Admin-Menü

### Via Git

```bash
cd wp-content/plugins/
git clone https://github.com/stb-srv/wp-restaurant.git wp-restaurant-menu
```

## 📖 Verwendung

### Menüpunkte erstellen

1. Navigieren Sie zu **Restaurant Menu** > **Neues Gericht hinzufügen**
2. Füllen Sie die erforderlichen Felder aus:
   - Name des Gerichts
   - Beschreibung
   - Preis
   - Kategorie
   - Bild (optional)
   - Allergene (optional)
3. Klicken Sie auf **Veröffentlichen**

### Speisekarte anzeigen

Verwenden Sie den Shortcode auf jeder Seite oder in jedem Beitrag:

```
[restaurant_menu]
```

#### Shortcode-Parameter

```
[restaurant_menu category="hauptgerichte" columns="2"]
```

- `category`: Zeigt nur eine bestimmte Kategorie an
- `columns`: Anzahl der Spalten (1-4)
- `limit`: Maximale Anzahl der angezeigten Gerichte

## 🏗️ Projekt-Struktur

```
wp-restaurant-menu/
│
├── admin/                          # Admin-spezifische Dateien
│   ├── css/                        # Admin-Stylesheets
│   ├── js/                         # Admin-JavaScript
│   └── class-wp-restaurant-menu-admin.php
│
├── public/                         # Frontend-Dateien
│   ├── css/                        # Public-Stylesheets
│   ├── js/                         # Public-JavaScript
│   └── class-wp-restaurant-menu-public.php
│
├── includes/                       # Kern-Funktionalität
│   ├── class-wp-restaurant-menu.php              # Haupt-Plugin-Klasse
│   ├── class-wp-restaurant-menu-activator.php    # Aktivierungs-Logik
│   ├── class-wp-restaurant-menu-deactivator.php  # Deaktivierungs-Logik
│   └── class-wp-restaurant-menu-loader.php       # Hook-Loader
│
├── languages/                      # Übersetzungsdateien
│   └── wp-restaurant-menu.pot
│
├── wp-restaurant-menu.php          # Haupt-Plugin-Datei
├── README.md                       # Diese Datei
├── uninstall.php                   # Deinstallations-Logik
└── LICENSE                         # GPL-2.0+ Lizenz
```

## 🔧 Systemanforderungen

- **WordPress**: Version 5.0 oder höher
- **PHP**: Version 7.4 oder höher
- **MySQL**: Version 5.6 oder höher

## 🛠️ Entwicklung

### Custom Post Type

Das Plugin registriert einen Custom Post Type `restaurant_menu_item` mit folgenden Taxonomien:

- `menu_category`: Kategorien für Menüpunkte
- `menu_tag`: Tags für zusätzliche Filterung

### Meta-Felder

- `_menu_item_price`: Preis des Gerichts
- `_menu_item_allergenes`: Allergen-Informationen
- `_menu_item_spicy_level`: Schärfegrad (optional)
- `_menu_item_vegetarian`: Vegetarisch (Ja/Nein)
- `_menu_item_vegan`: Vegan (Ja/Nein)

### Hooks & Filter

```php
// Filter für Preis-Formatierung
apply_filters('wp_restaurant_menu_price_format', $price);

// Action nach dem Speichern eines Menüpunkts
do_action('wp_restaurant_menu_after_save', $post_id);
```

## 🤝 Beitragen

Beiträge sind willkommen! Bitte erstellen Sie einen Fork des Repositories und reichen Sie Pull Requests ein.

1. Fork das Projekt
2. Erstellen Sie einen Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Committen Sie Ihre Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Pushen Sie zum Branch (`git push origin feature/AmazingFeature`)
5. Öffnen Sie einen Pull Request

## 📝 Changelog

### Version 1.0.0 (2025-12-15)
- Initial Release
- Custom Post Type für Menüpunkte
- Admin-Interface für CRUD-Operationen
- Frontend-Shortcode
- Kategorien-Verwaltung

## 📄 Lizenz

Dieses Projekt ist unter der GPL-2.0+ Lizenz lizenziert - siehe [LICENSE](LICENSE) für Details.

## 👤 Autor

**STB-SRV**

- GitHub: [@stb-srv](https://github.com/stb-srv)
- Repository: [wp-restaurant](https://github.com/stb-srv/wp-restaurant)

## 🙏 Danksagungen

- WordPress Community
- Alle Mitwirkenden an diesem Projekt

---

**Gefällt Ihnen dieses Plugin?** ⭐ Geben Sie uns einen Stern auf GitHub!
