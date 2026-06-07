# BS Kudo Karten

Digitale Kudo-Karten für WordPress – entwickelt von [bezugssysteme.de](https://bezugssysteme.de) für Marcus Rosik, Systemische Beratung.

**Version:** 0.4.1

---

## Was ist das?

Ein WordPress-Plugin, das es Besuchern ermöglicht, digitale Wertschätzungskarten zu versenden. Der Absender wählt eine Karte, schreibt direkt auf die Sprechblase, gibt Absender und Empfänger an – und schickt die Karte per E-Mail.

---

## Features

- **3-Schritt-Wizard** mit Scroll-Dramaturgie
- **Direkt auf die Karte schreiben** – Text-Overlay über dem Kartenbild (contenteditable)
- **Live-Zeichenzähler** – Standard max. 240 Zeichen (im Backend konfigurierbar)
- **Textimpulse** als klickbare Chips pro Karte konfigurierbar
- **„An mich selbst senden“** – Selbstwertschätzung als explizites Feature
- **Verzögerter Versand** – Datum und Uhrzeit wählbar
- **Token-basierte Webansicht** für Empfänger (temporäre URL, Drucklayout)
- **Honeypot, Formular-Zeitstempel und Rate Limiting** gegen Missbrauch
- **HTML-Mail** als Teaser mit Link zur Webansicht (+ optional QR-Code, lokal generiert)
- **Branding-Tab** im Backend – Logo, Farbe, Mail-Template konfigurierbar
- **Shortcode** `[kudo_karten]` – auf jeder Seite einsetzbar, Divi-kompatibel

---

## Shortcode

```
[kudo_karten]
[kudo_karten set="dankbarkeit"]
```

---

## Voraussetzungen

- WordPress 6.3+
- PHP 8.0+ mit **GD-Erweiterung** (für QR-Codes)
- Empfohlen: [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) für zuverlässigen Mailversand
- Für geplanten Versand: **System-Cron** (WP-Cron alle 5–15 Minuten auslösen)

---

## Installation

1. Plugin-Verzeichnis `bs-kudo-karten` in `/wp-content/plugins/` kopieren (inkl. `vendor/`)
2. Falls `vendor/` fehlt: im Plugin-Verzeichnis `composer install --no-dev` ausführen
3. Plugin im WordPress-Backend aktivieren
4. Unter **Kudo Karten → Karten verwalten** Kartenbilder hochladen und Karten anlegen
5. Unter **Kudo Karten → Einstellungen** Absender-Mail, Branding und Datenschutzhinweis konfigurieren
6. Shortcode `[kudo_karten]` auf einer Seite einfügen
7. Permalinks einmal speichern (Einstellungen → Permalinks), damit `/kudo-karte/{token}/` funktioniert

---

## Kartenbilder

Die Karten (Design: Marcus Rosik – Systemische Beratung) werden als **WebP** oder **JPG/PNG** über den WordPress-Medienmanager hinterlegt. Export aus den Original-PDFs mit mind. 1400px Breite empfohlen.

Urheberrecht: © Marcus Rosik – marcusrosik.de

---

## Backend-Struktur

```
Kudo Karten
├── Karten verwalten     – CPT kudo_card (Bild, Rückseiten-Branding, Farbe)
├── Textbausteine        – CPT kudo_textbaustein (Karten/Sets zugeordnet)
└── Einstellungen
    ├── Allgemein        – Absender, Betreff, Kopie, geplanter Versand, QR
    ├── Branding         – Logo, Primärfarbe, Mail-Footer
    └── Sicherheit       – Rate Limit, Zeichenlimit, Datenschutzhinweis
```

---

## Datenschutz (Kurzüberblick)

- **Sofortversand:** Name, E-Mail und Nachricht werden nur für den Versand verarbeitet, nicht dauerhaft gespeichert.
- **Geplanter Versand:** Daten werden bis zum Versandzeitpunkt als WP-Transient zwischengespeichert.
- **Webansicht:** Token enthält Karten-ID, Nachricht und Absendername (TTL konfigurierbar).
- **Rate Limit:** IP nur als Hash im Transient-Key, kein Klartext-Logging in Produktion.
- **Mail-Debug:** Nur bei `BSKUDO_MAIL_DEBUG` oder lokaler Entwicklungs-URL aktiv.

Den konfigurierbaren Hinweistext im Wizard bitte an die Datenschutzerklärung der Website anpassen.

---

## Mail-Debug (Entwicklung)

Optional in `wp-config.php`:

```php
define( 'BSKUDO_MAIL_DEBUG', true );
```

Logs landen in `debug/mail.log`. Der Ordner ist per `.htaccess` gegen direkten Zugriff geschützt (Apache).

**nginx-Beispiel** (im Server-Block):

```nginx
location ~ ^/wp-content/plugins/bs-kudo-karten/debug/ {
    deny all;
    return 404;
}
```

---

## Technische Entscheidungen

- **Vanilla JS** – kein jQuery im Frontend
- **Minimale Datenspeicherung** – Transients für Token, Rate Limit und geplante Jobs
- **CSS-Namespace** `.bskudo-` – keine Konflikte mit Divi oder anderen Themes
- **WP Transients** für Token und Rate Limiting
- **wp_mail()** für Versand – SMTP über externes Plugin empfohlen
- **QR-Codes** lokal via `chillerlan/php-qrcode` (kein externer Dienst)

---

## Prefix-Übersicht

| Kontext | Prefix |
|---------|--------|
| PHP-Funktionen | `bskudo_` |
| PHP-Konstanten | `BSKUDO_` |
| CSS-Klassen | `.bskudo-` |
| JavaScript | `window.bskudo` |
| Plugin-Slug | `bs-kudo-karten` |

---

## Entwicklung

**Entwickler:** Tom Evers – [bezugssysteme.de](https://bezugssysteme.de)  
**Auftraggeber:** Marcus Rosik – [marcusrosik.de](https://marcusrosik.de)  
**Lizenz:** GPL-2.0+

Composer-Abhängigkeiten aktualisieren:

```bash
cd wp-content/plugins/bs-kudo-karten
composer update --no-dev
```
