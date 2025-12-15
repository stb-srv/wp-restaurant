# WP Restaurant Menu - License Server

## 🚀 Installation

### 1. Dateien hochladen
Lade alle Dateien aus dem `license-server` Ordner auf deinen InfinityFree Server hoch:

```
/htdocs/license-server/
├── config.php
├── install.php
├── api.php
├── admin.php
├── test.php
└── README.md
```

### 2. Installation durchführen
Öffne im Browser:
```
https://wp-stb-srv.infinityfree.me/license-server/install.php
```

Dies erstellt automatisch:
- ✅ Datenbank-Tabellen
- ✅ Test-Lizenz
- ✅ Basis-Konfiguration

**WICHTIG:** Nach erfolgreicher Installation `install.php` LÖSCHEN!

### 3. Admin-Panel aufrufen
```
https://wp-stb-srv.infinityfree.me/license-server/admin.php
```

**Standard-Passwort:** `admin2025` (BITTE ÄNDERN!)

## 🔑 API Verwendung

### Endpoint
```
https://wp-stb-srv.infinityfree.me/license-server/api.php
```

### Parameter
- `key` - Lizenzschlüssel (required)
- `domain` - Domain des Kunden (required)

### Beispiel-Request
```bash
curl 'https://wp-stb-srv.infinityfree.me/license-server/api.php?key=WPR-TEST-12345&domain=example.com'
```

### Response (Erfolg)
```json
{
  "valid": true,
  "license_key": "WPR-TEST-12345",
  "max_items": 999,
  "expires": "2026-12-15",
  "customer": "Max Mustermann",
  "features": ["unlimited_items", "priority_support"]
}
```

### Response (Fehler)
```json
{
  "valid": false,
  "error": "Invalid license key",
  "max_items": 20
}
```

## 🔧 Plugin-Integration

### Im WordPress-Plugin einstellen:

1. Gehe zu: **Restaurant Menu → 🔑 Lizenz**
2. Trage die Server-URL ein:
   ```
   https://wp-stb-srv.infinityfree.me/license-server/api.php
   ```
3. Speichern
4. Lizenzschlüssel eingeben und aktivieren

## 🛡️ Sicherheit

### Admin-Passwort ändern
Bearbeite `config.php` und ändere:
```php
define('ADMIN_PASSWORD_HASH', 'dein_sha256_hash');
```

Generiere einen Hash:
```bash
echo -n "dein_neues_passwort" | sha256sum
```

### .htaccess Schutz (Optional)
Erstelle `.htaccess` im `license-server` Ordner:
```apache
# Nur admin.php darf aufgerufen werden
<FilesMatch "^(config|install)\.php$">
    Order deny,allow
    Deny from all
</FilesMatch>
```

## 📊 Funktionen

### Admin-Panel
- ✅ Lizenzen erstellen
- ✅ Lizenzen aktivieren/deaktivieren
- ✅ Domain-Beschränkungen
- ✅ Ablaufdatum setzen
- ✅ Statistiken einsehen

### API Features
- ✅ Domain-Validierung
- ✅ Ablaufdatum-Check
- ✅ Rate Limiting (100 Requests/Stunde)
- ✅ Access Logging
- ✅ Automatisches Caching (24h)

## 🔍 Lizenz-Format

```
WPR-XXXXX-XXXXX-XXXXX
```

### Master-Keys (immer gültig):
```
WPR-MASTER-2025-KEY1-ALPHA
WPR-MASTER-2025-KEY2-BETA
... (10 insgesamt)
```

## 📝 Logs

Logs werden gespeichert in:
```
/license-server/logs/access.log
```

Format:
```
[2025-12-15 13:30:45] [127.0.0.1] Valid license: WPR-TEST-12345 for domain example.com
```

## 🆘 Support

Bei Problemen:
1. Prüfe Datenbank-Verbindung in `config.php`
2. Teste API mit `test.php`
3. Prüfe Logs in `/logs/access.log`
4. Prüfe PHP-Fehlerlog auf dem Server

## 📦 Datenbank-Tabellen

### licenses
```sql
id, license_key, email, customer_name, domains, 
max_items, active, expires_at, created_at, 
last_checked, check_count
```

### rate_limits
```sql
id, identifier, created_at
```

### access_logs
```sql
id, license_key, domain, ip_address, user_agent, 
status, created_at
```
