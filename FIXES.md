# 🔧 Fehlerbehebungen & Troubleshooting

## ✅ Behobene Probleme in diesem Update

### 1. HTTP 500 Error im Admin-Panel

**Symptom**: Nach Login auf `admin-panel.php` erschien HTTP 500 Error

**Ursache**: 
- Fehlende Datenbank-Integration
- Nicht behandelte Exceptions beim Laden der Pricing-Daten

**Lösung**:
```php
// Korrekte DB-Integration
$db = LicenseDB::getInstance();
$pricing = $db->getPricing();
```

**Datei**: `license-server/admin-panel.php`

**Status**: ✅ **FIXED**

---

### 2. "Keine Menüpunkte gefunden" im Shortcode

**Symptom**: Shortcode `[restaurant_menu]` zeigt "Keine Menüpunkte gefunden"

**Mögliche Ursachen**:

#### A) Keine Gerichte vorhanden
➡️ **Lösung**: Mindestens 1 Gericht erstellen und **veröffentlichen**

```
WordPress Admin → Restaurant Menü → Neues Gericht hinzufügen
```

#### B) Gerichte sind nur als Entwurf gespeichert
➡️ **Lösung**: Status auf "Veröffentlicht" setzen

```
Gericht bearbeiten → Status: Veröffentlicht → Aktualisieren
```

#### C) Falsche Kategorie im Shortcode
➡️ **Lösung**: Kategorie-Slug prüfen

```php
// Falsch (wenn Kategorie nicht existiert)
[restaurant_menu category="vorspeisen"]

// Richtig (alle anzeigen)
[restaurant_menu]
```

#### D) Plugin nicht aktiviert
➡️ **Lösung**: Plugin aktivieren

```
Plugins → WP Restaurant Menu → Aktivieren
```

**Status**: ✅ **Shortcode funktioniert korrekt** (Code ist OK)

---

### 3. Lizenzmodelle nicht sichtbar

**Symptom**: Auf der Lizenz-Seite wurden nicht alle 5 Modelle angezeigt

**Ursache**: Alte Version ohne Pricing-Übersicht

**Lösung**: 
- Neue `class-wpr-license.php` Version
- Pricing-Karten für alle 5 Modelle
- Dynamische Server-Daten

**Datei**: `includes/class-wpr-license.php`

**Status**: ✅ **FIXED** - Alle 5 Modelle sichtbar

---

## 🛠️ Troubleshooting

### Problem: Preise werden nicht aktualisiert

**Lösung 1**: Cache löschen im Plugin
```
WordPress Admin → Restaurant Menü → Lizenz → 🔄 Preise aktualisieren
```

**Lösung 2**: WordPress-Cache löschen
```
Plugins → Caching-Plugin → Cache leeren
```

**Lösung 3**: Browser-Cache löschen
```
Strg + Shift + R (Windows)
Cmd + Shift + R (Mac)
```

---

### Problem: Admin-Panel zeigt alte Beschreibungen

**Ursache**: Datenbank-Spalte `description` fehlt

**Lösung**: Automatische Migration
```
1. Beliebige API-Anfrage machen
2. Spalte wird automatisch erstellt
3. Standard-Beschreibungen werden eingefügt
```

**Manuell**:
```sql
ALTER TABLE pricing ADD COLUMN description TEXT AFTER label;
```

---

### Problem: Server-Verbindung fehlgeschlagen

**Symptome**:
- Plugin zeigt FREE Version obwohl Lizenz vorhanden
- "Server-Test" schlägt fehl

**Lösung 1**: Server-URL prüfen
```php
// In class-wpr-license.php
private static function get_server_url() {
    return 'https://license-server.stb-srv.de/license-server/api.php';
}
```

**Lösung 2**: SSL-Zertifikat prüfen
```
https://license-server.stb-srv.de/license-server/api.php?action=status

Erwartete Antwort:
{"status":"online","version":"2.1"}
```

**Lösung 3**: Firewall-Regeln prüfen
```
Server muss ausgehende HTTPS-Requests erlauben
```

---

## 📝 Neue Features

### Pricing-Übersicht

**Was ist neu?**
- Alle 5 Lizenzmodelle als Karten angezeigt
- Dynamische Preise vom Server
- Editierbare Beschreibungen
- "AKTIV" Badge für aktuelle Lizenz

**Wo finden?**
```
WordPress Admin → Restaurant Menü → Lizenz
```

**Screenshot-Beschreibung**:
```
+------------------+  +------------------+  +------------------+
|   FREE           |  |   FREE+          |  |   PRO            |
|   Kostenlos      |  |   15€ einmalig   |  |   29€ einmalig   |
|                  |  |                  |  |                  |
|   Perfekt zum    |  |   Erweiterte     |  |   Professionelle |
|   Testen         |  |   Kapazität      |  |   Lösung        |
|                  |  |                  |  |                  |
|   ✓ 20 Gerichte  |  |   ✓ 60 Gerichte  |  |   ✓ 200 Gerichte|
+------------------+  +------------------+  +------------------+

+------------------+  +------------------+
|   PRO+    AKTIV  |  |   ULTIMATE       |
|   49€ einmalig   |  |   79€ einmalig   |
|                  |  |                  |
|   PRO + Dark     |  |   Alle Features  |
|   Mode + Cart    |  |   + unbegrenzt   |
|                  |  |                  |
|   ✓ 200 Gerichte |  |   ✓ 900 Gerichte|
|   ✓ 🌙 Dark Mode  |  |   ✓ 🌙 Dark Mode  |
|   ✓ 🛒 Warenkorb  |  |   ✓ 🛒 Warenkorb  |
|                  |  |   ✓ ♾️ Unbegrenzt|
+------------------+  +------------------+
```

---

### Admin-Panel Beschreibungen

**Was ist neu?**
- Beschreibungen editierbar im Admin-Panel
- Sofortige Synchronisation zu allen Plugins
- Keine Plugin-Updates nötig für Textänderungen

**Zugriff**:
```
https://deine-domain.com/license-server/admin-panel.php

Login: admin123 (ÄNDERN!)
```

**Features**:
- Label bearbeiten
- Preis ändern
- Währung anpassen
- **Beschreibung editieren** (NEU!)

---

## 🔍 Debug-Tipps

### WordPress Debug-Modus

**Aktivieren**:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Logs prüfen**:
```
wp-content/debug.log
```

---

### Server-Logs prüfen

**Lizenz-Server**:
```
license-server/logs/
```

**API-Anfragen testen**:
```bash
# Status prüfen
curl https://license-server.stb-srv.de/license-server/api.php?action=status

# Pricing abrufen
curl https://license-server.stb-srv.de/license-server/api.php?action=get_pricing

# Lizenz prüfen
curl "https://license-server.stb-srv.de/license-server/api.php?action=check_license&key=WPR-XXXXX-XXXXX-XXXXX&domain=example.com"
```

---

## 📞 Support

### Bei weiterhin Problemen:

1. **GitHub Issues**: [stb-srv/wp-restaurant/issues](https://github.com/stb-srv/wp-restaurant/issues)
2. **E-Mail**: s.behncke@icloud.com
3. **Debug-Informationen bereitstellen**:
   - WordPress Version
   - PHP Version
   - Plugin Version
   - Error Logs
   - Screenshots

---

## 📦 Versions-Info

**Aktuell**: v1.7.2 (Security & Stability Update)

**Änderungen**:
- ✅ Admin-Panel HTTP 500 behoben
- ✅ Pricing-Übersicht alle 5 Modelle
- ✅ Beschreibungen editierbar
- ✅ Cache-Fallback verbessert
- ✅ Sicherheits-Fixes

**Nächstes Update**: v1.8.0 (geplant)
- Warenkorb-System Verbesserungen
- Dark Mode Themes
- QR-Code Generator

---

**Letzte Aktualisierung**: 19. Dezember 2024
