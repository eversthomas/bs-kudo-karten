# BS Kudo Karten

Digitale Kudo-Karten für WordPress – entwickelt von [bezugssysteme.de](https://bezugssysteme.de) für Marcus Rosik, Systemische Beratung.

---

## Was ist das?

Ein WordPress-Plugin das es Besuchern ermöglicht, digitale Wertschätzungskarten zu versenden. Der Absender wählt eine Karte, schreibt direkt auf die Sprechblase, gibt Absender und Empfänger an – und schickt die Karte per E-Mail.

---

## Features

- **3-Schritt-Wizard** mit Scroll-Dramaturgie (keine harten Screen-Wechsel)
- **Direkt auf die Karte schreiben** – Text-Overlay über dem Kartenbild (contenteditable)
- **Live-Zeichenzähler** – max. 160 Zeichen, 2 Zeilen
- **Textimpulse** als klickbare Chips pro Karte konfigurierbar
- **"An mich selbst" Funktion** – Selbstwertschätzung als explizites Feature
- **Verzögerter Versand** – Datum und Uhrzeit wählbar
- **Token-basierte Webansicht** für Empfänger (temporäre URL)
- **Honeypot + Rate Limiting** gegen Missbrauch
- **Kein Datenspeichern** – personenbezogene Daten werden nicht persistiert
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
- PHP 8.0+
- Empfohlen: [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) für zuverlässigen Mailversand

---

## Installation

1. Plugin-Verzeichnis `bs-kudo-karten` in `/wp-content/plugins/` kopieren
2. Plugin im WordPress-Backend aktivieren
3. Unter **Kudo Karten → Karten verwalten** die Kartenbilder hochladen und Karten anlegen
4. Unter **Kudo Karten → Einstellungen** Absender-Mail und Branding konfigurieren
5. Shortcode `[kudo_karten]` auf einer Seite einfügen

---

## Kartenbilder

Die Karten (Design: Marcus Rosik – Systemische Beratung) werden als **WebP** für die Web-Ansicht und als **JPG** für den Mail-Versand benötigt. Export aus den Original-PDFs mit mind. 1400px Breite.

Urheberrecht: © Marcus Rosik – marcusrosik.de

---

## Backend-Struktur

```
Kudo Karten
├── Karten verwalten     – CPT kudo_card (Bild, Impuls-Text, Farbe)
├── Textbausteine        – CPT kudo_textbaustein (Karten zugeordnet)
└── Einstellungen
    ├── Allgemein        – Absender, Betreff-Template, Kopie an Absender
    ├── Branding         – Logo, Primärfarbe, Mail-Footer
    └── Sicherheit       – Rate Limit, Zeichenlimit, Datenschutzhinweis
```

---

## Entwicklungs-Phasen

| Phase | Status | Inhalt |
|-------|--------|--------|
| 1 | ✅ | Grundgerüst – Plugin aktivierbar, CPTs, Admin-Menü |
| 2 | ✅ | Kartenauswahl – Shortcode, Grid, Karte wählbar |
| 3 | ✅ | Wizard – Scroll-Dramaturgie, contenteditable, Live-Vorschau |
| 4 | 🔄 | Versand – wp_mail(), Honeypot, Rate Limiting |
| 5 | ⏳ | Webansicht – Token-URL für Empfänger |
| 6 | ⏳ | Branding – HTML-Mail, Logo, Farbe, Powered-by |
| 7 | ⏳ | Polish – QR-Code, verzögerter Versand |

---

## Technische Entscheidungen

- **Vanilla JS** – kein jQuery, kein Framework
- **Kein Datenspeichern** – keine personenbezogenen Daten in der DB
- **CSS-Namespace** `.bskudo-` – keine Konflikte mit Divi oder anderen Themes
- **WordPress Medienmanager** für Kartenbilder
- **WP Transients** für Token und Rate Limiting
- **wp_mail()** für Versand – SMTP über externes Plugin empfohlen

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
**Workflow:** Cursor AI (Entwicklung) · Claude Code (Code-Review) · LocalWP (Testing)