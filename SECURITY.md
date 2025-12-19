# Security Policy

## 🛡️ Sicherheitsrichtlinien für WP Restaurant Menu

### Unterstützte Versionen

| Version | Unterstützt          |
| ------- | -------------------- |
| 1.7.x   | :white_check_mark:   |
| 1.6.x   | :x:                  |
| < 1.6   | :x:                  |

### Sicherheitsfunktionen

#### API Security
- ✅ Vollständige Input-Validierung und Sanitization
- ✅ Prepared Statements für alle Datenbank-Queries
- ✅ XSS-Schutz durch htmlspecialchars
- ✅ Action-Whitelist für API-Endpunkte
- ✅ Security Headers (X-Content-Type-Options, X-Frame-Options)

#### WordPress Security
- ✅ Nonce-Verifikation für alle Admin-Formulare
- ✅ Capability-Checks (`manage_options`)
- ✅ ABSPATH-Check in allen PHP-Dateien
- ✅ Sanitization für alle User-Inputs

#### Lizenz-Server Security
- ✅ PDO mit Prepared Statements
- ✅ Database connection error handling
- ✅ Rate limiting (empfohlen auf Server-Ebene)
- ✅ Debug-Endpoint nur mit Secret-Key

### Schwachstellen melden

Wenn Sie eine Sicherheitslücke finden, melden Sie diese bitte **NICHT** über GitHub Issues!

#### Reporting-Prozess

1. **E-Mail senden** an: s.behncke@icloud.com
2. **Betreff**: `[SECURITY] WP Restaurant Menu - [Kurzbeschreibung]`
3. **Inhalt**:
   - Beschreibung der Schwachstelle
   - Schritte zur Reproduktion
   - Potentielle Auswirkungen
   - Vorgeschlagene Lösung (optional)

#### Response Time
- **Initial Response**: Innerhalb von 48 Stunden
- **Status Update**: Innerhalb von 7 Tagen
- **Fix Timeline**: Abhängig von Severity (siehe unten)

### Severity Levels

| Level    | Response Time | Beispiele                                    |
|----------|--------------|----------------------------------------------|
| Critical | 24-48h       | SQL Injection, Remote Code Execution         |
| High     | 3-7 Tage     | XSS, Authentication Bypass                   |
| Medium   | 7-14 Tage    | CSRF, Information Disclosure                 |
| Low      | 14-30 Tage   | Minor issues ohne direkte Sicherheitsrisiken |

### Best Practices für Benutzer

#### Plugin-Installation
```bash
# Sichere Installation
1. Download nur von offiziellen Quellen
2. Verify checksums (wenn verfügbar)
3. Teste zuerst in Staging-Umgebung
4. Backup vor Installation
```

#### Lizenz-Server Setup
```bash
# Sichere Konfiguration
1. HTTPS verwenden (Let's Encrypt empfohlen)
2. db-config.php außerhalb des Webroots
3. Debug-Secret als Umgebungsvariable setzen
4. Regelmäßige Backups der Datenbank
```

#### Umgebungsvariablen
```bash
# .env Datei (NICHT committen!)
DEBUG_SECRET=your-secure-random-string-here
DB_HOST=localhost
DB_NAME=license_db
DB_USER=license_user
DB_PASS=secure-password-here
```

#### Server-Hardening
```apache
# .htaccess für License Server
<Files "db-config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>

# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
```

### Sicherheits-Checkliste

#### Vor Produktiv-Deployment
- [ ] HTTPS aktiviert
- [ ] Debug-Mode deaktiviert
- [ ] Starke Datenbank-Passwörter
- [ ] File Permissions korrekt (644 für Dateien, 755 für Ordner)
- [ ] Backup-System eingerichtet
- [ ] Monitoring/Logging aktiviert
- [ ] WordPress & PHP auf aktueller Version
- [ ] Rate Limiting konfiguriert

#### Regelmäßige Wartung
- [ ] WordPress Core Updates
- [ ] Plugin Updates
- [ ] PHP Version aktuell (min 7.4, empfohlen 8.1+)
- [ ] Datenbank-Backups
- [ ] Log-Review
- [ ] Security Scans

### Bekannte Einschränkungen

1. **Rate Limiting**: Muss auf Server-Ebene implementiert werden
2. **HTTPS**: Muss vom Hosting-Provider konfiguriert sein
3. **Brute Force Protection**: WordPress-Plugin empfohlen (z.B. Wordfence)

### Sicherheits-Updates

Security Patches werden priorisiert und schnellstmöglich veröffentlicht.

#### Update-Benachrichtigungen
- GitHub Releases
- Security Advisory (bei kritischen Fixes)
- E-Mail an registrierte Lizenz-Inhaber (geplant)

### Compliance

#### DSGVO
- ✅ Minimale Datenerhebung
- ✅ Keine Cookies ohne Einwilligung
- ✅ IP-Adressen in Logs (kann deaktiviert werden)
- ✅ Recht auf Vergessenwerden (manuelle Löschung möglich)

#### WordPress.org Richtlinien
- ✅ Keine verschlüsselte/obfuskierte Code
- ✅ GPL-kompatible Lizenz
- ✅ Secure Coding Standards

### Weitere Ressourcen

- [WordPress Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

### Hall of Fame

Danke an alle, die Sicherheitsprobleme verantwortungsvoll gemeldet haben:

- *Keine Einträge bisher*

---

**Letzte Aktualisierung**: 19. Dezember 2024  
**Version**: 1.7.1
