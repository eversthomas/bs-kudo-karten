# BS Kudo Karten – Plugin Architecture

## Projektkontext
WordPress-Plugin für digitale Kudo-Karten.
Entwickelt von Tom Evers (bezugssysteme.de) für Marcus Rosik (Systemische Beratung).
Ziel: Nutzer wählen eine Kudo-Karte, schreiben einen kurzen Text (max. 160 Zeichen, 2 Zeilen),
geben Absender und Empfänger an und verschicken die Karte per E-Mail.

## Prinzipien
- Kein Framework, kein jQuery (außer WP-Standard) – Vanilla JS
- Kein unnötiger Datenbankschreibvorgang (keine personenbezogenen Daten speichern)
- Jede Klasse hat genau eine Verantwortung
- CSS-Prefix: .bskudo- (keine Konflikte mit Divi oder anderen Themes)
- PHP-Prefix: bskudo_ / BSKUDO_ / BSKudo_
- Deutsche Kommentare im Code
- Testbare Phasen: jede Phase ist einzeln aktivierbar und prüfbar

## Plugin-Prefix
Slug:       bs-kudo-karten
PHP:        bskudo_
Konstanten: BSKUDO_
CSS:        .bskudo-
JS:         window.bskudo

## Dateistruktur
bs-kudo-karten/
├── bs-kudo-karten.php          # Hauptdatei, nur Konstanten + Loader-Aufruf
├── ARCHITECTURE.md
├── includes/
│   ├── class-bskudo-loader.php      # Alle add_action / add_filter zentral
│   ├── class-bskudo-cpt.php         # CPT: kudo_card, kudo_set, kudo_textbaustein
│   ├── class-bskudo-shortcode.php   # [kudo_karten] Shortcode
│   ├── class-bskudo-mailer.php      # Versand via wp_mail()
│   ├── class-bskudo-security.php    # Honeypot, Rate Limiting, Sanitizing
│   └── class-bskudo-token.php       # Temporäre URLs für Kartenansicht
├── admin/
│   ├── class-bskudo-admin.php       # Admin-Seite mit Tabs
│   ├── settings-general.php         # Tab: Allgemein (Absender, Betreff)
│   ├── settings-branding.php        # Tab: Branding (Logo, Farbe, Mail-Template)
│   └── settings-security.php        # Tab: Sicherheit (Rate Limit, Zeichenlimit)
├── public/
│   ├── css/bskudo-wizard.css
│   ├── js/bskudo-wizard.js
│   └── templates/
│       ├── wizard.php               # 3-Schritt-Wizard HTML
│       └── card-view.php            # Webansicht für Empfänger (Token-basiert)
├── mail/
│   └── template-mail.php            # HTML-Mail mit Karte + Branding
└── assets/
    └── cards/                       # Karten als WebP (Web) + JPG (Mail)

## Custom Post Types
kudo_card
  - Felder: Titel, Bild (Medienmanager), Impuls-Text, Akzentfarbe, Icon-Position
  - Gehört zu: kudo_set (Taxonomie)

kudo_set
  - Taxonomie für kudo_card
  - Felder: Titel, Beschreibung, Preis (für spätere Vermarktung)

kudo_textbaustein
  - Felder: Text (max. 160Z), zugeordnete kudo_card oder kudo_set

## Wizard – 3 Schritte
Schritt 1: Kartenauswahl (Grid, CSS-Flip bei Auswahl)
Schritt 2: Text (Textbausteine als Impulse + Freitext, Live-Vorschau auf Karte, max. 160 Zeichen)
Schritt 3: Absender/Empfänger + Datenschutzhinweis + Senden

## Sicherheit
- Honeypot-Feld (immer aktiv, kein Toggle)
- Rate Limiting: X Versendungen pro IP/Stunde (konfigurierbar, Default: 5)
  Implementierung: WP Transients, key = 'bskudo_limit_' . md5(IP)
- Textlänge: max. 160 Zeichen, serverseitig validiert
- Nonce: wp_nonce_field() für AJAX-Formular
- Keine Speicherung personenbezogener Daten

## Datenschutz
- Kein Logging von Namen oder E-Mail-Adressen
- Hinweistext im Formular (konfigurierbar im Backend)
- Token für Webansicht: nur Karten-ID + Text + Ablaufdatum, gespeichert als WP Transient

## Mail-Versand
- wp_mail() – kein eigenes SMTP
- Empfehlung an Admin: WP Mail SMTP Plugin nutzen
- HTML-Template: mail/template-mail.php
- Karte als inline base64-Bild (JPG)
- Optionale Kopie an Absender
- "Powered by BS Kudo Karten · bezugssysteme.de" im Footer (abschaltbar)

## Admin-Backend (Tabs)
Tab 1 – Allgemein: Absender-Name, Absender-Mail, Betreff-Template, Kopie an Absender
Tab 2 – Branding: Logo (Medienmanager), Primärfarbe, Footer-Text Mail
Tab 3 – Sicherheit: Rate-Limit-Zahl, Zeichenlimit, Datenschutzhinweis-Text

## Phasen
Phase 1: Grundgerüst – Plugin aktivierbar, CPTs, Admin-Menü
Phase 2: Kartenanzeige – Shortcode, Grid, Karte wählbar
Phase 3: Wizard – alle 3 Schritte, Live-Vorschau
Phase 4: Versand – wp_mail(), Sicherheit
Phase 5: Webansicht – Token, card-view.php
Phase 6: Branding – HTML-Mail, Logo, Farbe
Phase 7: Polish – QR-Code, verzögerter Versand, "An mich selbst"

## Branding
Entwickelt von: Tom Evers – bezugssysteme.de
Urheber Karten: Marcus Rosik – Systemische Beratung
Copyright-Hinweis im Admin sichtbar, Karten-Bilder nicht direkt verlinkbar