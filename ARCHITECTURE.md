# BS Kudo Karten – Plugin Architecture

## Projektkontext
WordPress-Plugin für digitale Kudo-Karten.
Entwickelt von Tom Evers (bezugssysteme.de) für Marcus Rosik (Systemische Beratung).
Ziel: Nutzer wählen eine Kudo-Karte, schreiben einen kurzen Text (Standard max. 240 Zeichen, konfigurierbar),
geben Absender und Empfänger an und verschicken die Karte per E-Mail.

## Prinzipien
- Kein Framework, kein jQuery im Frontend – Vanilla JS
- Minimale Datenspeicherung – Transients statt dauerhafter personenbezogener Datensätze
- Jede Klasse hat genau eine Verantwortung
- CSS-Prefix: .bskudo- (keine Konflikte mit Divi oder anderen Themes)
- PHP-Prefix: bskudo_ / BSKUDO_ / BSKudo_
- Deutsche Kommentare im Code

## Plugin-Prefix
Slug:       bs-kudo-karten
PHP:        bskudo_
Konstanten: BSKUDO_
CSS:        .bskudo-
JS:         window.bskudo

## Dateistruktur
bs-kudo-karten/
├── bs-kudo-karten.php          # Konstanten, Composer-Autoload, Loader
├── composer.json               # chillerlan/php-qrcode (QR lokal)
├── vendor/                     # Composer-Abhängigkeiten (mit deployen)
├── languages/                  # Übersetzungen (Textdomain bs-kudo-karten)
├── includes/
│   ├── class-bskudo-loader.php
│   ├── class-bskudo-cpt.php
│   ├── class-bskudo-shortcode.php
│   ├── class-bskudo-send.php          # AJAX-Versand
│   ├── class-bskudo-mailer.php
│   ├── class-bskudo-security.php      # Nonce, Honeypot, Zeitstempel, Rate Limit
│   ├── class-bskudo-token.php
│   ├── class-bskudo-card-view.php
│   ├── class-bskudo-scheduler.php     # WP-Cron für geplanten Versand
│   ├── class-bskudo-qr.php            # Lokale QR-Generierung
│   ├── class-bskudo-settings.php
│   └── class-bskudo-debug.php
├── admin/
│   ├── class-bskudo-admin.php
│   ├── class-bskudo-card-meta.php
│   ├── class-bskudo-textbaustein-meta.php
│   ├── settings-general.php
│   ├── settings-branding.php
│   └── settings-security.php
├── public/
│   ├── css/
│   ├── js/
│   └── templates/
│       ├── wizard.php
│       └── card-view.php
├── mail/
│   └── template-mail.php
└── debug/                      # Mail-Debug (nur lokal / BSKUDO_MAIL_DEBUG)

## E-Mail-Strategie

Die HTML-Mail ist ein **Türöffner**: Begrüßung, Teaser, CTA-Button zur tokenbasierten Webansicht.
Optional QR-Code (lokal generiert, PNG in Uploads-Ordner für Mail-Kompatibilität).
Kein Kartenbild direkt in der Mail – der Wow-Effekt liegt in der Webansicht.

Plain-Text-Alternative via PHPMailer `AltBody`.

## Webansicht (Token)

URL: `/kudo-karte/{token}/` (Pretty Permalinks) oder `?bskudo_kudo={token}`

Transient-Payload: `card_id`, `message`, `sender_name`, `created`
Keine Empfänger-E-Mail im Token.

## Sicherheit
- Nonce für AJAX
- Honeypot-Feld (immer aktiv)
- Formular-Zeitstempel (min. 3 Sekunden zwischen Laden und Absenden)
- Rate Limiting: X Versendungen pro IP/Stunde (WP Transient, Key = `bskudo_limit_` + md5(IP))
- Zeichenlimit serverseitig validiert
- Karten-ID muss veröffentlichte `kudo_card` sein

## Datenschutz
- Sofortversand: keine dauerhafte Speicherung personenbezogener Daten
- Geplanter Versand: Versanddaten als Transient bis zum Versandzeitpunkt
- Token: Nachricht + Absendername temporär (TTL konfigurierbar)
- Debug-Logging nur bei `BSKUDO_MAIL_DEBUG` oder lokaler URL – nicht automatisch bei `WP_DEBUG`
- QR-Codes werden lokal erzeugt (kein externer API-Dienst)

## Mail-Versand
- wp_mail() – kein eigenes SMTP
- Empfehlung: WP Mail SMTP Plugin
- HTML-Template: mail/template-mail.php
- Reply-To: Absender aus Formular
- From: konfigurierbar im Backend
- Optionale Kopie an Absender

## Phasen (Stand 0.4.1)
Phase 1–3: ✅ Grundgerüst, Shortcode, Wizard
Phase 4: ✅ Versand, Sicherheit
Phase 5: ✅ Token-Webansicht
Phase 6: ✅ HTML-Mail, Branding
Phase 7: ✅ QR-Code (lokal), verzögerter Versand, „An mich selbst“

## Branding
Entwickelt von: Tom Evers – bezugssysteme.de
Urheber Karten: Marcus Rosik – Systemische Beratung
