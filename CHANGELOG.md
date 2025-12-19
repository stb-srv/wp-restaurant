# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

## [Unreleased] - Security & Stability Fixes

### 🔒 Security (Sicherheit)
- **API Input Validation**: Vollständige Eingabevalidierung und Sanitization in `license-server/api.php`
  - Lizenzschlüssel werden gefiltert (nur A-Z, 0-9, Bindestriche erlaubt)
  - Domain-Validierung mit FILTER_VALIDATE_URL
  - XSS-Schutz durch htmlspecialchars bei Fehlermeldungen
  - Action-Whitelist verhindert unautorisierte Endpunkte
  - Security Headers hinzugefügt (X-Content-Type-Options, X-Frame-Options)

- **Debug-Endpoint Secured**: Debug-Endpoint benötigt jetzt Secret aus Umgebungsvariable
- **Improved Error Logging**: Detaillierte Logs für fehlgeschlagene Lizenz-Checks

### ⚡ Performance
- **Database Indices**: Neue Indizes für bessere Query-Performance
  - `idx_domain` auf `licenses.domain` für schnellere Domain-Suchen
  - `idx_expires` auf `licenses.expires` für Ablaufdatum-Checks
  - `idx_ip` auf `logs.ip_address` für Log-Analysen
  - `idx_price` auf `pricing.price` für Preis-Sortierung

- **Stats-Methode**: Neue `getStats()` Methode in LicenseDB für schnelle Übersichten

### 🐞 Bug Fixes
- **License Cache Fallback**: Bei Server-Ausfall werden gecachte Lizenzdaten verwendet
  - Verhindert Downgrade auf FREE bei temporären Server-Problemen
  - 24h Cache mit Fallback-Mechanismus
  
- **Generic Feature Checking**: Neue Methode `check_feature($name)` für einheitliche Feature-Prüfung
  - `has_dark_mode()` - Dark Mode Check
  - `has_cart()` - Warenkorb Check  
  - `has_unlimited_items()` - Unbegrenzte Gerichte Check

- **Improved Feature Display**: Aktivierungsmeldung zeigt alle freigeschalteten Features an
  - 🌙 Dark Mode
  - 🛒 Warenkorb
  - ♾️ Unbegrenzte Gerichte

### 📝 Documentation
- **Changelog**: Changelog-Datei hinzugefügt für bessere Versionshistorie
- **API Version Bump**: API Version von 2.0 auf 2.1
- **License Class Version**: License Class Version von 2.1 auf 2.2
- **Database Class Version**: Database Class Version von 2.0 auf 2.1

### 🛠️ Technical Improvements
- **Code Quality**: Konsistente Fehlerbehandlung in allen API-Endpunkten
- **Type Safety**: Explizite Type-Casts für Datenbank-Rückgabewerte
- **Prepared Statements**: Alle DB-Queries verwenden Prepared Statements (bereits umgesetzt)

---

## [1.7.1] - 2024-12-18

### Added
- Shortcode-Implementierung komplett wiederhergestellt
- 5 Lizenzmodelle: Free, Free+, Pro, Pro+, Ultimate

### Fixed
- HTTP 500 Fehler durch korrekte Ladereihenfolge von database.php

---

## [1.7.0] - 2024-12-17

### Added
- Lizenz-System komplett überarbeitet
- Flexibles Lizenzformat (3 oder 4 Segmente)
- Automatische Domain-Registrierung

---

## [1.6.0] - 2024-12-16

### Added
- Warenkorb-System (PRO+ Feature)
- Dark Mode (Global oder Menü-spezifisch)
- Import/Export-Funktionalität

### Changed
- Free Tier: 10 → 20 Gerichte

---

## Format

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

### Kategorien
- **Added** (➕): Neue Features
- **Changed** (🔄): Änderungen an bestehenden Features
- **Deprecated** (⚠️): Bald zu entfernende Features
- **Removed** (❌): Entfernte Features
- **Fixed** (🐞): Bug Fixes
- **Security** (🔒): Sicherheitsfixes
