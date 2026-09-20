# BS Kudo Karten

Digitale Kudo-Karten für WordPress – entwickelt von [bezugssysteme.de](https://bezugssysteme.de).

**Version:** 0.10.0

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
- **Honeypot, Formular-Zeitstempel und Rate Limiting** (Stunde + Tag) gegen Missbrauch
- **Optional Cloudflare Turnstile** und **Absender-Bestätigung per E-Mail-Link** (Double-Opt-In, abschaltbar)
- **Missbrauchsmeldung** – optionale Kontakt-E-Mail in Benachrichtigung und Webansicht
- **HTML-Mail** als Teaser mit Link zur Webansicht (+ optional QR-Code zur Online-Karte, lokal generiert)
- **QR-Ziel-Link pro Karte (Rückseite)** – optional eigene URL nur für den QR auf der Online-Kartenrückseite (nicht für den Mail-QR)
- **Konfigurierbares Rückseiten-Layout** – Bausteine (QR, Text, Logo) pro Spalte, Reihenfolge und Sichtbarkeit in der Webansicht
- **Branding-Tab** im Backend – Logo, Farbe, Mail-Template konfigurierbar
- **Shortcode** `[kudo_karten]` – auf jeder Seite einsetzbar, Divi-kompatibel
- **Anpassbare Produktbezeichnung** – Singular/Plural (z. B. „Kudokarte“ / „Kudokarten“) für Wizard, E-Mail und Webansicht; technische Slugs unverändert

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

## Produktbezeichnung (z. B. „Kudokarten“)

Unter **Einstellungen → Allgemein → Bezeichnung** kann Singular und Plural frei gesetzt werden (z. B. `Kudokarte` / `Kudokarten`). Damit ändern sich sichtbare Texte in Wizard, E-Mail, Webansicht und Backend-Menü. **Shortcode**, Post-Typ-Slugs und URLs bleiben unverändert.

- **Update ohne Datenverlust:** Gespeicherte Karten, Branding-Texte, Betreff-Vorlage und Datenschutzhinweis in der Datenbank werden beim Plugin-Update **nicht** überschrieben.
- Leere Felder = bisherige Standardbezeichnung „Kudo-Karte“ / „Kudo-Karten“.
- Optional im Betreff: Platzhalter `{product}` und `{product_plural}` (bestehende Betreff-Zeile bleibt, bis du sie anpasst).

---

## Kartenbilder

Kartenbilder werden als **WebP** oder **JPG/PNG** über den WordPress-Medienmanager hinterlegt. Export mit mind. 1400px Breite empfohlen.

---

## Backend-Struktur

```
Kudo Karten
├── Karten verwalten     – CPT kudo_card (Bild, Rückseiten-Branding, QR-Ziel, Layout, Farbe)
├── Textbausteine        – CPT kudo_textbaustein (Karten/Sets zugeordnet)
└── Einstellungen
    ├── Allgemein        – Bezeichnung (Singular/Plural), Absender, Betreff, Kopie, geplanter Versand, QR
    ├── Branding         – Logo, Primärfarbe, Mail-Footer, Missbrauchskontakt
    └── Sicherheit       – Rate Limits, Absender-Bestätigung, Turnstile, Cloudflare-IP, Datenschutz- & Haftungshinweis, Mail-Debug-Log
```

---

## Datenschutz (Kurzüberblick)

Es werden **keine personenbezogenen Daten dauerhaft in der Datenbank** gespeichert (keine eigenen Tabellen für Namen, E-Mails oder Nachrichten). Daten liegen höchstens **temporär in TTL-begrenzten Transients** (WordPress-Cache), bis die Karte versendet wurde, der geplante Termin erreicht ist oder das Bestätigungsfenster abläuft.

- **Sofortversand:** Verarbeitung nur für den Versand; keine dauerhafte Speicherung.
- **Absender-Bestätigung (Standard an):** Anfrage ca. 30 Min. als Transient; Bestätigungslink per E-Mail (Landing-Seite + Button).
- **Geplanter Versand:** Transient bis zum Versandzeitpunkt (WP-Cron).
- **Webansicht:** Eigener Token-Transient (TTL konfigurierbar).
- **Rate Limit / optional Turnstile:** In der Datenschutzerklärung erwähnen, wenn aktiv.
- **Mail-Debug:** Nur bei `BSKUDO_MAIL_DEBUG` oder lokaler Entwicklungs-URL aktiv.

Datenschutz- und Haftungshinweis im Wizard bitte an die Website-DSE anpassen.

---

## Sicherheit (0.10.0)

| Einstellung (Tab **Sicherheit**) | Zweck |
|----------------------------------|--------|
| Rate Limit Stunde / Tag | Missbrauch über eine IP eindämmen |
| Website hinter Cloudflare | Echte Client-IP für Rate Limits (`CF-Connecting-IP`) |
| Absender-Bestätigung | Double-Opt-In (Landing + POST, Default **an**) |
| Turnstile Site-/Secret-Key | Bot-Schutz (serverseitig nur bei beiden Keys) |
| Haftungsausschluss | Pflicht-Checkbox vor Versand (Text konfigurierbar) |

**Branding → Kontakt für Meldungen:** E-Mail für Empfänger:innen bei unangebrachten Karten.

**Rollout:** Absender-Bestätigung und Haftungs-Checkbox sind für neue Defaults aktiv – auf Staging testen.

---

## Mail-Debug (Entwicklung)

Optional in `wp-config.php`:

```php
define( 'BSKUDO_MAIL_DEBUG', true );
```

Bei aktivem Debug (oder auf lokalen Hosts wie `.local`, `.test`, `localhost`) schreibt das Plugin nach `wp-content/uploads/bskudo-debug-private/mail.log` und optional `last-mail.html`. Beim ersten Schreibvorgang legt es den Ordner an und erzeugt `.htaccess` (`Deny from all`) sowie `index.php` automatisch. Die letzten Log-Zeilen sind im Backend unter **Einstellungen → Sicherheit** einsehbar.

Im Git-Repository liegt nur `debug/index.php` als Schutz-Stub (Legacy-Fallback, falls Uploads nicht beschreibbar sind). Log-Dateien im Plugin-Ordner bleiben in `.gitignore`.

**nginx-Beispiel** (im Server-Block):

```nginx
location ~ ^/wp-content/uploads/bskudo-debug-private/ {
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
**Lizenz:** GPL-2.0+

Composer-Abhängigkeiten aktualisieren:

```bash
cd wp-content/plugins/bs-kudo-karten
composer update --no-dev
```
