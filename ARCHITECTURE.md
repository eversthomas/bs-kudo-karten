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
│   ├── class-bskudo-card-meta.php   # Karten: Akzentfarbe, optionales Rückseiten-Branding
│   ├── class-bskudo-textbaustein-meta.php
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

## Karten-Layout (Vorder- und Rückseite)

**Vorderseite:** Kartenbild in Originalproportionen (`object-fit: contain`) + Nutzertext zentriert
auf dem vorgesehenen Notizfeld (max. 160 Zeichen, 2 Zeilen, Schrift skaliert automatisch).
**Große Vorschau:** Dialog in Schritt 2 vor dem Versand – Karte in voller Größe mit komplettem Text.

**Rückseite:** Branding (Logo, Text, Farbe) – konfigurierbar im Backend-Tab „Branding“.
Optional pro Karte überschreibbar (Meta-Feld „Rückseiten-Branding“ am CPT `kudo_card`).

Kein CSS-Flip in E-Mails – Flip nur im Web-Wizard als Interaktion (Schritt 1: Branding ansehen).

## Custom Post Types
kudo_card
  - Felder: Titel, Bild (Medienmanager), Akzentfarbe, Icon-Position (Textausrichtung Vorderseite)
  - Optional: Rückseiten-Branding (überschreibt globalen Standard)
  - Gehört zu: kudo_set (Taxonomie)

kudo_set
  - Taxonomie für kudo_card
  - Felder: Titel, Beschreibung, Preis (für spätere Vermarktung)

kudo_textbaustein
  - Felder: Text (max. 160Z), zugeordnete kudo_card oder kudo_set

## Wizard – 3 Schritte

**Navigation:** Die Schritt-Labels („Karte wählen“, „Text“, „Versenden“) sind klickbar.
Bereits besuchte Schritte können jederzeit angesprungen werden (z. B. Text oder Karte vor dem Versand ändern).
Vorwärts nur mit gültigen Eingaben (Karte gewählt, Text nicht leer).

**Schritt 1 – Kartenauswahl:** Split-Layout – große sticky Vorschau (links/oben) mit „Weiter“-Button,
kompaktes scrollbares Karten-Grid zur Auswahl (rechts/unten). Flip nur in der großen Vorschau (Branding).

**Schritt 2 – Text:** Textbausteine als Impulse + Freitext, Live-Vorschau mit **Vorder- und Rückseite nebeneinander**
(wie später in der E-Mail). Text erscheint auf der Vorderseite.

**Schritt 3 – Versenden:** Absender/Empfänger + Datenschutzhinweis + Senden.

## E-Mail-Darstellung (kein Flip)

E-Mail-Clients unterstützen kein zuverlässiges 3D-Flip. Strategie:

1. **HTML-Mail:** Zwei statische Bilder nebeneinander (Desktop) bzw. untereinander (Mobile):
   - Links/oben: Vorderseite als **fertiges JPG** (Bild + Nutzertext bereits eingebrannt)
   - Rechts/unten: Rückseite als **JPG** (Branding)
2. **Plain-Text-Alternative** (`multipart/alternative`): Nachrichtentext + kurzer Hinweis ohne Layout –
   für Clients ohne HTML (z. B. ältere Outlook-Konfigurationen).
3. **Webansicht (Token, Phase 5):** Optional Flip oder ebenfalls Duo-Ansicht – konsistent zur Mail.

Bilder werden serverseitig als JPG gerendert (Phase 6), nicht als HTML/CSS-Flip versendet.

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
- Karte als inline base64-Bild (JPG) – Vorder- und Rückseite getrennt
- Optionale Kopie an Absender
- "Powered by BS Kudo Karten · bezugssysteme.de" im Footer (abschaltbar)

## Admin-Backend (Tabs)
Tab 1 – Allgemein: Absender-Name, Absender-Mail, Betreff-Template, Kopie an Absender
Tab 2 – Branding: Logo (Medienmanager), Primärfarbe, Rückseiten-Text, Footer-Text Mail
Tab 3 – Sicherheit: Rate-Limit-Zahl, Zeichenlimit, Datenschutzhinweis-Text

## Phasen
Phase 1: Grundgerüst – Plugin aktivierbar, CPTs, Admin-Menü
Phase 2: Kartenanzeige – Shortcode, Grid, Karte wählbar
Phase 3: Wizard – alle 3 Schritte, Live-Vorschau (Vorderseite Text, Rückseite Branding), klickbare Schritte
Phase 4: Versand – wp_mail(), Sicherheit
Phase 5: Webansicht – Token, card-view.php
Phase 6: Branding – HTML-Mail, Logo, Farbe, JPG-Rendering Vorder-/Rückseite
Phase 7: Polish – QR-Code, verzögerter Versand, "An mich selbst"

## Branding
Entwickelt von: Tom Evers – bezugssysteme.de
Urheber Karten: Marcus Rosik – Systemische Beratung
Copyright-Hinweis im Admin sichtbar, Karten-Bilder nicht direkt verlinkbar
