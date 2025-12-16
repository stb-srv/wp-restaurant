# 🚀 License Server v2.0 - MySQL Edition

## Features

✅ **Datenbank-basiert** - Alle Daten sicher in MySQL  
✅ **Kein Datenverlust** - Bei Re-Upload bleiben alle Lizenzen & Einstellungen erhalten  
✅ **Professional** - Modernes Admin-Dashboard  
✅ **Sicher** - Password-Hashing, CSRF-Schutz, Rate Limiting  
✅ **Einfach** - 2-Schritt Installation

---

## 📋 Was wird in der Datenbank gespeichert?

### Tabellen:

1. **`config`** - Einstellungen
   - Admin-Username & Passwort
   - API-Key
   - Timezone, Währung

2. **`licenses`** - Alle Lizenzen
   - Lizenzschlüssel
   - Typ (FREE/PRO/PRO+)
   - Domain, Max Items
   - Ablaufdatum, Features

3. **`pricing`** - Preispakete
   - FREE, PRO, PRO+ Preise
   - Währung, Labels

4. **`logs`** - System-Logs
   - API-Zugriffe
   - Fehler, Warnungen

---

## 🛠️ Installation

### Schritt 1: Datenbank erstellen

1. **Gehe zu deinem Hosting-Panel** (cPanel, Plesk, etc.)
2. **Erstelle eine neue MySQL-Datenbank**
   - Name: z.B. `license_server`
3. **Notiere:**
   - Datenbank-Host (meist `localhost`)
   - Datenbank-Name
   - Datenbank-User
   - Datenbank-Passwort

### Schritt 2: Dateien hochladen

1. **Lade den kompletten `/license-server/` Ordner hoch**
2. **Browser öffnen:**
   ```
   https://deine-domain.com/license-server/
   ```

### Schritt 3: Installer ausführen

#### **Screen 1: Datenbank**
```
📡 Datenbank-Host: localhost
💾 Datenbank-Name: license_server
👤 Datenbank-User: dein_user
🔒 Datenbank-Passwort: ********
```
➡️ Klick: **"Weiter zu Schritt 2"**

#### **Screen 2: Admin-Account**
```
👤 Admin Username: admin
🔒 Admin Passwort: ********
✉️ E-Mail: admin@deine-domain.com
```
➡️ Klick: **"Installation abschließen"**

### Schritt 4: Fertig! 🎉

```
🎉 Installation erfolgreich!

Login: https://deine-domain.com/license-server/
Username: admin
Passwort: dein_passwort
```

---

## 🔄 Re-Upload / Update

### Alte Methode (JSON):
```
❌ Dateien löschen
❌ Neue Dateien hochladen
❌ ALLE Lizenzen weg! 😱
❌ Admin-Login vergessen
❌ Preise zurückgesetzt
```

### Neue Methode (MySQL):
```
✅ Dateien löschen
✅ Neue Dateien hochladen
✅ KEINE Datei: db-config.php löschen!
✅ Alle Lizenzen bleiben! 🎉
✅ Login funktioniert weiter
✅ Preise bleiben erhalten
```

### **Wichtig beim Re-Upload:**

1. **NIEMALS löschen:**
   - `db-config.php` (Datenbank-Verbindung)
   - `.installed` (Installations-Marker)

2. **Löschen OK:**
   - Alle anderen `.php` Dateien
   - `assets/` Ordner
   - `views/` Ordner
   - `includes/` Ordner (außer `db-config.php`!)

3. **Neue Dateien hochladen**

4. **Fertig!** Alles funktioniert wie vorher! ✅

---

## 📁 Datei-Struktur

```
/license-server/
├── index.php                  ← Haupt-Entry
├── installer.php             ← 2-Schritt Installer
├── api.php                   ← Public API
├── db-config.php             ← DB-Credentials (WICHTIG!)
├── .installed                ← Installations-Marker
├── .gitignore                ← Schützt Secrets
├── README.md                 ← Diese Datei
├── includes/
│   ├── database.php          ← MySQL PDO Wrapper
│   ├── config.php            ← Config aus DB laden
│   ├── functions.php         ← Helper Functions
│   └── security.php          ← Auth & Security
├── views/
│   ├── login.php             ← Login-Seite
│   ├── admin.php             ← Admin-Layout
│   └── tabs/
│       ├── dashboard.php     ← 📊 Dashboard
│       ├── licenses.php      ← 🎫 Lizenzen
│       ├── pricing.php       ← 💰 Preise
│       ├── api.php           ← 🔌 API Docs
│       └── settings.php      ← ⚙️ Einstellungen
└── assets/
    └── admin.css             ← Modern UI
```

---

## 🔐 Sicherheit

### Was ist geschützt?

1. **db-config.php**
   - Wird NICHT in Git commitet
   - Enthält nur DB-Zugangsdaten
   - Alle Daten sind in MySQL

2. **Admin-Passwort**
   - BCrypt-Hash in DB
   - Niemals im Klartext

3. **API-Key**
   - 64-stelliger Random-Key
   - In DB gespeichert

4. **CSRF-Schutz**
   - Alle Forms mit Token

5. **Rate Limiting**
   - Max 100 Requests/Stunde

---

## 🎯 WordPress Plugin konfigurieren

### Im WordPress Admin:

1. **Gehe zu:** Lizenz-Verwaltung
2. **Server-URL eintragen:**
   ```
   https://deine-domain.com/license-server/api.php
   ```
3. **Speichern**
4. **Lizenzschlüssel eingeben** (vom License-Server)
5. **Aktivieren** ✅

---

## 🔧 Troubleshooting

### Problem: "Database connection failed"

**Lösung:**
1. Prüfe `db-config.php`:
   ```php
   define('DB_HOST', 'localhost');    // Richtig?
   define('DB_NAME', 'license_server'); // Existiert?
   define('DB_USER', 'dein_user');      // Korrekt?
   define('DB_PASS', 'dein_passwort');  // Richtig?
   ```

2. Test DB-Verbindung in phpMyAdmin

### Problem: "Tabellen nicht gefunden"

**Lösung:**
```php
// In includes/database.php
$db = LicenseDB::getInstance();
$db->createTables(); // Manuell ausführen
```

### Problem: "Installation Loop"

**Lösung:**
1. Lösche `.installed` Datei
2. Starte Installer neu

---

## 📊 Datenbank-Schema

### Tabelle: `config`
```sql
CREATE TABLE config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE,
    config_value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabelle: `licenses`
```sql
CREATE TABLE licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(100) UNIQUE,
    type VARCHAR(50),
    domain VARCHAR(255),
    max_items INT,
    expires VARCHAR(50),
    features TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabelle: `pricing`
```sql
CREATE TABLE pricing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_type VARCHAR(50) UNIQUE,
    price INT,
    currency VARCHAR(10),
    label VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabelle: `logs`
```sql
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_type VARCHAR(50),
    message TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP
);
```

---

## 🎉 Vorteile

| Feature | JSON (Alt) | MySQL (Neu) |
|---------|------------|-------------|
| **Datenverlust bei Re-Upload** | ❌ Ja | ✅ Nein |
| **Skalierbar** | ❌ Nein | ✅ Ja |
| **Backup einfach** | ❌ Nein | ✅ Ja |
| **Performance** | ⚠️ Langsam | ✅ Schnell |
| **Concurrent Access** | ❌ Nein | ✅ Ja |
| **Suche/Filter** | ❌ Nein | ✅ Ja |

---

## 📞 Support

Bei Problemen:
1. Prüfe diese README
2. Logs in DB prüfen: `SELECT * FROM logs`
3. PHP Error Log prüfen

---

## 🚀 Migration von JSON zu MySQL

Falls du noch alte JSON-Dateien hast:

```php
// Einmalig ausführen:
$old_licenses = json_decode(file_get_contents('data/licenses.json'), true);
$db = LicenseDB::getInstance();

foreach ($old_licenses as $key => $data) {
    $db->saveLicense($key, $data);
}

echo "Migration abgeschlossen!";
```

---

**Version:** 2.0  
**Datum:** Dezember 2025  
**Status:** Production Ready 🚀
