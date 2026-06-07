# BS WordPress Plugin Design System
**Universelle KI-Referenzdatei · Tom Evers · bezugssysteme.de**

> Diese Datei liegt im Plugin-Root und wird bei jedem neuen Plugin-Gespräch als Kontext mitgegeben.
> Sie definiert das vollständige Design-System für alle WordPress-Plugins von Tom Evers / BezugsSysteme.de.

---

## 0. Anwendungsregeln für die KI

### 0.1 Zwei Modi – wann was gilt

**Modus A – Greenfield (neues Plugin)**
Die KI entwickelt zuerst die **Funktionslogik** ohne Design. Dabei gilt:
- Alle PHP/HTML-Klassen sofort mit dem Plugin-Präfix versehen (z. B. `myplugin-wrap`)
- Dateistruktur `assets/tokens.css` und `assets/styles.css` von Anfang an anlegen (darf leer sein)
- Kein Styling, kein Inline-CSS, kein `<style>`-Block
- Wenn die Funktionslogik steht und der Entwickler "Design umsetzen" sagt → Modus B

**Modus B – Retrofit (Design auf bestehendes Plugin aufsetzen)**
- Bestehendes HTML analysieren, Struktur auf Blueprint-Klassen mappen
- Sidebar-Shell und App-Container um bestehende Seiten wickeln
- `tokens.css` + `styles.css` einhängen
- WordPress-Admin-Bridge CSS ergänzen
- Branding-Footer auf jeder Hauptseite einfügen

### 0.2 Präfix-Prinzip

**Der Plugin-Präfix wird am Gesprächsanfang festgelegt** (z. B. `bskudo`, `bseasy`, `bsform`).
- Alle CSS-Klassen: `.[prefix]-app`, `.[prefix]-wrap` usw.
- Alle PHP-Funktionen: `[prefix]_render_page()` usw.
- Alle JS-Variablen/Handles: `[prefix]-app`, `[prefix]Data` usw.
- **Niemals** einen hardkodierten Präfix aus dieser Datei übernehmen
- **Niemals** WordPress-Core-Klassen nutzen: kein `.button`, `.card`, `.notice`, `.wrap` ohne Präfix

### 0.3 Kapselungsregel

```css
/* RICHTIG – alles unter dem App-Container */
.[prefix]-app .card { ... }
.[prefix]-app .btn.primary { ... }

/* FALSCH – globale Selektoren */
.card { ... }
.btn { ... }
```

Einzige Ausnahme: der WordPress-Admin-Bridge-Block (Abschnitt F), der gezielt WP-Core-Klassen überschreibt.

### 0.4 Absolute Verbote

- Keine Hex-Farben, RGB-Werte oder direkten Pixelwerte im CSS – ausschließlich Token-Variablen
- Kein globales CSS ohne `.[prefix]-app`-Scope
- Kein statischer Präfix aus Beispielen übernehmen
- Branding-Footer nie weglassen
- `tweaks-panel` (Prototyp-Tooling) nicht in Produktion übernehmen
- Keine CDN-React/Babel-Script-Tags in Produktionscode
- Keine WordPress-Core-Klassennamen

---

## A. Design-Token-Referenz

### A.1 Google Fonts Import

```css
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Hanken+Grotesk:ital,wght@0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500;600&display=swap');
```

### A.2 Alle Token – kopierbereit

```css
.[prefix]-app {

  /* ─── Typografie ─────────────────────────────────── */
  --font-brand: 'Space Grotesk', system-ui, sans-serif;     /* Titel, Zahlen, Logo */
  --font-ui:    'Hanken Grotesk', system-ui, sans-serif;    /* Fließtext, UI, Formulare */
  --font-mono:  'JetBrains Mono', ui-monospace, monospace;  /* Keys, IDs, API-Pfade, Code */

  /* ─── Neutrale Flächen ───────────────────────────── */
  --bg:              oklch(0.975 0.004 255);  /* App-Hintergrund, kühles Hellgrau */
  --surface:         oklch(1 0 0);            /* Cards, Modals, Inputs – Weiß */
  --surface-2:       oklch(0.985 0.004 255);  /* Tabellen-Header, Ghost-Hover */
  --surface-sunken:  oklch(0.96 0.005 255);   /* Eingesunkene Bereiche, Search, Badges */
  --border:          oklch(0.92 0.006 255);   /* Standard-Rahmen */
  --border-strong:   oklch(0.86 0.008 255);   /* Betonte Rahmen, Inputs, Ghost-Buttons */

  /* ─── Textfarben (Tinten-Skala) ──────────────────── */
  --ink:       oklch(0.24 0.012 260);  /* Primärer Text, Überschriften */
  --ink-soft:  oklch(0.42 0.012 260);  /* Sekundärer Text, Tabellenwerte */
  --muted:     oklch(0.58 0.012 260);  /* Hilfstext, Labels, Breadcrumbs */
  --faint:     oklch(0.72 0.01 260);   /* Platzhalter, Trennelemente */

  /* ─── Akzentfarbe (Default: Blau) ───────────────── */
  --accent:         oklch(0.55 0.142 252);  /* Primary-Button, aktive Nav-Icons, Focus-Ring */
  --accent-press:   oklch(0.48 0.142 252);  /* Button-Hover/Active */
  --accent-soft:    oklch(0.95 0.03 252);   /* Badge-Hintergrund, Focus-Box-Shadow */
  --accent-soft-bd: oklch(0.88 0.06 252);   /* Border in Accent-Soft-Kontexten */
  --accent-ink:     oklch(0.40 0.14 252);   /* Text auf --accent-soft */

  /* ─── Sekundär-Akzent „Thread" (Teal) ───────────── */
  --thread:      oklch(0.62 0.11 175);  /* Avatar-BG, sparsamer zweiter Akzent */
  --thread-soft: oklch(0.95 0.035 175); /* Badge-Hintergrund thread */
  --thread-ink:  oklch(0.42 0.10 175);  /* Text auf --thread-soft */

  /* ─── Statusfarben ───────────────────────────────── */
  --ok:         oklch(0.60 0.12 155);  /* Grün: Veröffentlicht, Erfolg */
  --ok-soft:    oklch(0.95 0.04 155);  /* Badge-Hintergrund ok */
  --warn:       oklch(0.70 0.13 70);   /* Amber: In Prüfung, Warnung */
  --warn-soft:  oklch(0.96 0.05 75);   /* Badge-Hintergrund warn */
  --warn-ink:   oklch(0.50 0.12 60);   /* Text auf --warn-soft */
  --danger:     oklch(0.57 0.17 25);   /* Rot: Fehler, Löschen, Pflichtfeld */
  --danger-soft:oklch(0.95 0.04 25);   /* Badge-Hintergrund danger */

  /* ─── Sidebar (Dark – Default) ───────────────────── */
  --side-bg:     oklch(0.24 0.02 265);  /* Sidebar-Hintergrund */
  --side-bg-2:   oklch(0.21 0.02 265);  /* Noch dunklerer Sidebar-Bereich */
  --side-ink:    oklch(0.95 0.01 265);  /* Primärer Text Sidebar */
  --side-muted:  oklch(0.68 0.015 265); /* Sekundärer Text, inaktive Nav-Items */
  --side-border: oklch(0.32 0.02 265);  /* Trennlinien Sidebar */
  --side-active: oklch(0.30 0.04 265);  /* Hover/Aktiv-BG Nav-Items */

  /* ─── Geometrie: Radien ──────────────────────────── */
  --r-sm: 6px;   /* Checkbox, Key-Pill, kleine Elemente */
  --r:    9px;   /* Standard: Buttons, Inputs, Nav-Items */
  --r-lg: 13px;  /* Cards */
  --r-xl: 18px;  /* Brand-Mark, größere Overlays */

  /* ─── Schatten ───────────────────────────────────── */
  --shadow-sm: 0 1px 2px oklch(0.2 0.02 260 / 0.06),
               0 1px 3px oklch(0.2 0.02 260 / 0.05);   /* Cards, Buttons, Switch-Knopf */
  --shadow:    0 2px 6px oklch(0.2 0.02 260 / 0.07),
               0 8px 24px oklch(0.2 0.02 260 / 0.06);  /* Sheet, Dropdown */
  --shadow-lg: 0 12px 40px oklch(0.2 0.02 260 / 0.16); /* Sheet-Panel, Toast */

  /* ─── Layout & Dichte (Regular – Default) ────────── */
  --row-h: 44px;  /* Tabellen-Zeilenhöhe */
  --pad:   22px;  /* Standard-Innenabstand */
  --gap:   16px;  /* Standard-Abstand zwischen Elementen */
}
```

---

## B. Akzent-Themes & Varianten

Alle Varianten werden via `data-*`-Attribute auf dem `.[prefix]-app`-Wurzelelement gesteuert.

### B.1 Akzent-Themes (`data-accent`)

```css
.[prefix]-app[data-accent="indigo"] {
  --accent:         oklch(0.52 0.18 274);
  --accent-press:   oklch(0.45 0.18 274);
  --accent-soft:    oklch(0.95 0.04 274);
  --accent-soft-bd: oklch(0.88 0.07 274);
  --accent-ink:     oklch(0.40 0.17 274);
}
.[prefix]-app[data-accent="teal"] {
  --accent:         oklch(0.55 0.11 192);
  --accent-press:   oklch(0.47 0.11 192);
  --accent-soft:    oklch(0.95 0.04 192);
  --accent-soft-bd: oklch(0.88 0.06 192);
  --accent-ink:     oklch(0.40 0.10 192);
}
.[prefix]-app[data-accent="plum"] {
  --accent:         oklch(0.52 0.16 330);
  --accent-press:   oklch(0.45 0.16 330);
  --accent-soft:    oklch(0.95 0.035 330);
  --accent-soft-bd: oklch(0.88 0.065 330);
  --accent-ink:     oklch(0.41 0.15 330);
}
```

### B.2 Helle Sidebar (`data-side="light"`)

```css
.[prefix]-app[data-side="light"] {
  --side-bg:     oklch(1 0 0);
  --side-bg-2:   oklch(0.985 0.004 255);
  --side-ink:    var(--ink);
  --side-muted:  oklch(0.55 0.012 260);
  --side-border: var(--border);
  --side-active: var(--accent-soft);
}
```

### B.3 Dichte-Varianten (`data-density`)

```css
.[prefix]-app[data-density="compact"] {
  --row-h: 38px;
  --pad:   16px;
  --gap:   12px;
}
.[prefix]-app[data-density="comfy"] {
  --row-h: 52px;
  --pad:   28px;
  --gap:   20px;
}
```

---

## C. Typografie-Skala (Referenz)

| Element | Größe | Gewicht | Font | Besonderheit |
|---|---|---|---|---|
| Seitentitel `h1` | 25px | 600 | brand | letter-spacing -0.02em |
| Sheet-Titel `h2` | 17px | 600 | brand | letter-spacing -0.01em |
| Card-Titel `h3` | 14px | 600 | brand | letter-spacing -0.01em |
| Sidebar Branding | 17px | 600 | brand | letter-spacing -0.01em |
| Body / UI | 14.5px | 400 | ui | line-height 1.5 |
| Formular-Label `.flabel` | 13px | 600 | ui | – |
| Hilfetext `.fhint` | 12px | 400 | ui | color: --muted |
| Tabellen-Header `th` | 11px | 600 | ui | uppercase, letter-spacing 0.04em |
| Badge / Chip | 11.5px | 600 | ui | – |
| Mono-Badge `.badge.mono` | 11–13px | 500 | mono | – |
| Nav-Label (Gruppenüberschrift) | 10px | – | mono | uppercase, letter-spacing 0.12em |
| Sidebar-Sub (Version) | 10.5px | – | mono | letter-spacing 0.02em |
| Mono-Key `.key` | 12px | – | mono | color: --muted |

---

## D. Animationen & Timing

```css
/* Sheet / Slide-Over */
@keyframes slidein {
  from { transform: translateX(28px); opacity: 0.4; }
  to   { transform: translateX(0);    opacity: 1;   }
}

/* Toast */
@keyframes toastin {
  from { transform: translateY(10px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* Screen-Wechsel */
@keyframes routein {
  from { transform: translateY(5px); opacity: 0; }
  to   { transform: translateY(0);   opacity: 1; }
}
```

**Timing-Regeln:**

| Kontext | Dauer |
|---|---|
| Nav-Hover, Button-Hover | 0.13s |
| Switch, Checkbox | 0.16s |
| Toast, Route-Wechsel | 0.24s |
| Sheet Slide-In | 0.22s cubic-bezier(0.22, 1, 0.36, 1) |

**Kritische Regel:** Opacity-Endzustand ist immer `1`. Sichtbarkeit niemals dauerhaft an eine Animation koppeln – nach der Animation muss das Element voll sichtbar sein, auch wenn JS die Animation-Class entfernt.

---

## E. Komponenten-Blueprints (HTML)

Alle Blueprints verwenden `[prefix]` als Platzhalter – vor Verwendung durch den Plugin-Präfix ersetzen.

### E.1 App-Shell (Grundstruktur jeder Plugin-Seite)

```html
<div class="[prefix]-app" data-accent="blue" data-side="dark" data-density="regular">

  <!-- Sidebar -->
  <aside class="side">
    <!-- Inhalt: siehe E.2 -->
  </aside>

  <!-- Hauptbereich -->
  <main class="main">
    <div class="topbar">
      <!-- Inhalt: siehe E.3 -->
    </div>
    <div class="content">
      <div class="page">
        <!-- Seiteninhalt -->
      </div>
    </div>
  </main>

</div>
```

**CSS-Grundgerüst:**

```css
.[prefix]-app {
  display: grid;
  grid-template-columns: 248px 1fr;
  height: 100vh;
  overflow: hidden;
  background: var(--bg);
  color: var(--ink);
  font-family: var(--font-ui);
  font-size: 14.5px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
.[prefix]-app[data-density="compact"] { grid-template-columns: 224px 1fr; }

.[prefix]-app .main {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}
.[prefix]-app .content {
  flex: 1;
  overflow-y: auto;
}
.[prefix]-app .page {
  max-width: 1180px;
  margin: 0 auto;
  padding: 26px 26px 80px;
}
```

### E.2 Sidebar

```html
<aside class="side">

  <!-- Branding -->
  <div class="side-brand">
    <div class="brand-mark"></div>
    <div>
      <div class="brand-name">bs<b>·</b>[Plugin-Name]</div>
      <div class="brand-sub">v1.0.0 · [Kurzbeschreibung]</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="side-nav">

    <div class="nav-label">Hauptmenü</div>

    <a class="nav-item active" href="#" data-screen="dash">
      <span class="nav-ic"><!-- SVG Icon 16×16 --></span>
      Dashboard
    </a>
    <a class="nav-item" href="#" data-screen="list">
      <span class="nav-ic"><!-- SVG Icon 16×16 --></span>
      Einträge
      <span class="nav-count">12</span>
    </a>
    <a class="nav-item" href="#" data-screen="settings">
      <span class="nav-ic"><!-- SVG Icon 16×16 --></span>
      Einstellungen
    </a>

    <div class="nav-label" style="margin-top: 16px;">Werkzeuge</div>

    <a class="nav-item" href="#" data-screen="export">
      <span class="nav-ic"><!-- SVG Icon 16×16 --></span>
      Export
    </a>

  </nav>

  <!-- Nutzer-Footer -->
  <div class="side-foot">
    <div class="avatar">TE</div>
    <div>
      <div style="font-size:13px; font-weight:600; color:var(--side-ink);">Tom Evers</div>
      <div style="font-size:11px; color:var(--side-muted);">Administrator</div>
    </div>
  </div>

</aside>
```

**CSS:**

```css
.[prefix]-app .side {
  background: var(--side-bg);
  border-right: 1px solid var(--side-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.[prefix]-app .side-brand {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 18px 16px 16px;
  border-bottom: 1px solid var(--side-border);
}
.[prefix]-app .brand-mark {
  width: 34px; height: 34px;
  border-radius: var(--r-xl);
  background: var(--accent);
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}
.[prefix]-app .brand-mark::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(0deg, transparent, transparent 4px, rgba(255,255,255,0.12) 4px, rgba(255,255,255,0.12) 5px);
}
.[prefix]-app .brand-mark::after {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(90deg, transparent, transparent 4px, rgba(0,0,0,0.12) 4px, rgba(0,0,0,0.12) 5px);
}
.[prefix]-app .brand-name {
  font-family: var(--font-brand);
  font-size: 17px; font-weight: 600;
  letter-spacing: -0.01em;
  color: var(--side-ink);
}
.[prefix]-app .brand-name b { color: var(--accent); }
.[prefix]-app .brand-sub {
  font-family: var(--font-mono);
  font-size: 10.5px;
  color: var(--side-muted);
  letter-spacing: 0.02em;
  margin-top: 1px;
}
.[prefix]-app .side-nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px 10px;
}
.[prefix]-app .nav-label {
  font-family: var(--font-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--side-muted);
  padding: 6px 8px 4px;
}
.[prefix]-app .nav-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 10px;
  height: 36px;
  border-radius: var(--r);
  color: var(--side-muted);
  font-size: 13.5px;
  text-decoration: none;
  transition: background 0.13s, color 0.13s;
  position: relative;
}
.[prefix]-app .nav-item:hover {
  background: var(--side-active);
  color: var(--side-ink);
}
.[prefix]-app .nav-item.active {
  background: var(--side-active);
  color: var(--side-ink);
}
.[prefix]-app .nav-item.active .nav-ic { color: var(--accent); }
.[prefix]-app .nav-ic {
  width: 16px; height: 16px;
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.[prefix]-app .nav-count {
  margin-left: auto;
  font-family: var(--font-mono);
  font-size: 11px;
  background: var(--side-bg-2);
  color: var(--side-muted);
  padding: 1px 6px;
  border-radius: 20px;
}
.[prefix]-app .side-foot {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-top: 1px solid var(--side-border);
}
.[prefix]-app .avatar {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: var(--thread);
  color: #fff;
  font-family: var(--font-brand);
  font-size: 13px; font-weight: 600;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
```

### E.3 Topbar & Breadcrumbs

```html
<div class="topbar">
  <nav class="crumbs">
    <a href="#" data-screen="dash">Dashboard</a>
    <span class="sep">›</span>
    <a href="#" data-screen="list">Einträge</a>
    <span class="sep">›</span>
    <span class="here">Neuer Eintrag</span>
  </nav>
  <div class="spacer"></div>
  <div class="search">
    <input type="search" placeholder="Suchen… ⌘K" />
  </div>
</div>
```

**CSS:**

```css
.[prefix]-app .topbar {
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 var(--pad);
  gap: 12px;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
  flex-shrink: 0;
}
.[prefix]-app .crumbs {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}
.[prefix]-app .crumbs a {
  color: var(--muted);
  text-decoration: none;
}
.[prefix]-app .crumbs a:hover { color: var(--ink); }
.[prefix]-app .crumbs .sep { color: var(--faint); }
.[prefix]-app .crumbs .here { color: var(--ink); font-weight: 600; }
.[prefix]-app .spacer { flex: 1; }
.[prefix]-app .search input {
  width: 260px;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 6px 12px;
  font-size: 13px;
  font-family: var(--font-ui);
  color: var(--ink);
}
.[prefix]-app .search input::placeholder { color: var(--faint); }
.[prefix]-app .search input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}
```

### E.4 Page-Header

```html
<div class="page-head">
  <div>
    <h1>Seitentitel</h1>
    <p class="lede">Optionale Beschreibungszeile unter dem Titel</p>
  </div>
  <div class="ha">
    <button class="btn ghost">Sekundäre Aktion</button>
    <button class="btn primary">Primäre Aktion</button>
  </div>
</div>
```

**CSS:**

```css
.[prefix]-app .page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}
.[prefix]-app .page-head h1 {
  font-family: var(--font-brand);
  font-size: 25px; font-weight: 600;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin: 0;
}
.[prefix]-app .page-head .lede {
  font-size: 14px;
  color: var(--muted);
  margin: 4px 0 0;
}
.[prefix]-app .page-head .ha {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
```

### E.5 Buttons

```html
<!-- Varianten -->
<button class="btn primary">Speichern</button>
<button class="btn ghost">Abbrechen</button>
<button class="btn subtle">Weitere Optionen</button>
<button class="btn danger">Löschen</button>
<button class="btn primary sm">Klein Primary</button>
<button class="btn ghost sm">Klein Ghost</button>
<button class="btn icon ghost" aria-label="Bearbeiten"><!-- SVG --></button>
<button class="btn icon sm ghost" aria-label="Löschen"><!-- SVG --></button>
<button class="btn primary" disabled>Deaktiviert</button>
```

**CSS:**

```css
.[prefix]-app .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  height: 38px;
  padding: 0 16px;
  border-radius: var(--r);
  font-family: var(--font-ui);
  font-size: 13.5px; font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.13s, border-color 0.13s, box-shadow 0.13s, transform 0.1s;
}
.[prefix]-app .btn:active { transform: translateY(0.5px); }
.[prefix]-app .btn:disabled { opacity: 0.5; pointer-events: none; }

.[prefix]-app .btn.primary {
  background: var(--accent);
  color: #fff;
  box-shadow: var(--shadow-sm);
}
.[prefix]-app .btn.primary:hover { background: var(--accent-press); }

.[prefix]-app .btn.ghost {
  background: var(--surface);
  color: var(--ink-soft);
  border-color: var(--border-strong);
  box-shadow: var(--shadow-sm);
}
.[prefix]-app .btn.ghost:hover {
  background: var(--surface-2);
  border-color: oklch(0.78 0.01 260);
}

.[prefix]-app .btn.subtle {
  background: transparent;
  color: var(--ink-soft);
  border-color: transparent;
}
.[prefix]-app .btn.subtle:hover { background: var(--surface-sunken); }

.[prefix]-app .btn.danger {
  background: var(--surface);
  color: var(--danger);
  border-color: var(--border-strong);
}
.[prefix]-app .btn.danger:hover {
  background: var(--danger-soft);
  border-color: var(--danger);
}

.[prefix]-app .btn.sm { height: 32px; padding: 0 12px; font-size: 12.5px; }

.[prefix]-app .btn.icon { width: 38px; padding: 0; }
.[prefix]-app .btn.icon.sm { width: 32px; padding: 0; }
```

### E.6 Card

```html
<div class="card">
  <div class="card-head">
    <h3>Card-Titel</h3>
    <div class="ca">
      <button class="btn ghost sm">Aktion</button>
    </div>
  </div>
  <div class="card-body">
    <!-- Inhalt -->
  </div>
</div>
```

**CSS:**

```css
.[prefix]-app .card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm);
}
.[prefix]-app .card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--pad);
  border-bottom: 1px solid var(--border);
}
.[prefix]-app .card-head h3 {
  font-family: var(--font-brand);
  font-size: 14px; font-weight: 600;
  letter-spacing: -0.01em;
  margin: 0;
}
.[prefix]-app .card-head .ca {
  display: flex; gap: 6px;
}
.[prefix]-app .card-body {
  padding: var(--pad);
}
```

### E.7 Stat-Kacheln (Dashboard)

```html
<div class="stat-grid">
  <div class="card">
    <div class="card-body">
      <div style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:var(--muted); margin-bottom:8px;">Gesamt</div>
      <div style="font-family:var(--font-brand); font-size:32px; font-weight:600; letter-spacing:-0.02em; color:var(--ink);">247</div>
      <div style="font-size:12px; color:var(--muted); margin-top:4px;">+12 diese Woche</div>
    </div>
  </div>
  <!-- weitere Kacheln -->
</div>
```

**CSS:**

```css
.[prefix]-app .stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--gap);
  margin-bottom: var(--gap);
}
```

### E.8 Tabelle

```html
<div class="card">
  <table class="tbl">
    <thead>
      <tr>
        <th class="checkbox-cell"><div class="checkbox" id="select-all"></div></th>
        <th class="sortable">Name <span class="sar">↑</span></th>
        <th>Status</th>
        <th>Typ</th>
        <th class="num">Datum</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <tr class="row-click">
        <td class="checkbox-cell"><div class="checkbox on"></div></td>
        <td><span class="cell-strong">Beispiel-Eintrag</span></td>
        <td><span class="badge ok dot">Veröffentlicht</span></td>
        <td><span class="ftype"><!-- Icon --> text</span></td>
        <td class="num cell-mute">12.06.2026</td>
        <td>
          <button class="btn icon sm ghost"><!-- Edit Icon --></button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

**CSS:**

```css
.[prefix]-app .tbl {
  width: 100%;
  border-collapse: collapse;
}
.[prefix]-app .tbl th {
  font-size: 11px; font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--muted);
  background: var(--surface-2);
  padding: 0 var(--pad);
  height: 38px;
  text-align: left;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
.[prefix]-app .tbl th.sortable { cursor: pointer; }
.[prefix]-app .tbl th.sortable:hover { color: var(--ink); }
.[prefix]-app .tbl .sar { color: var(--accent); margin-left: 4px; }
.[prefix]-app .tbl td {
  padding: 0 var(--pad);
  height: var(--row-h);
  font-size: 13.5px;
  color: var(--ink-soft);
  vertical-align: middle;
  border-bottom: 1px solid var(--border);
}
.[prefix]-app .tbl tbody tr:last-child td { border-bottom: none; }
.[prefix]-app .tbl tbody tr:hover td { background: var(--surface-2); transition: background 0.1s; }
.[prefix]-app .tbl .row-click { cursor: pointer; }
.[prefix]-app .tbl .num { font-family: var(--font-mono); font-size: 12px; font-variant-numeric: tabular-nums; }
.[prefix]-app .cell-strong { color: var(--ink); font-weight: 600; }
.[prefix]-app .cell-mute { color: var(--muted); }
.[prefix]-app .checkbox-cell { width: 44px; padding: 0 12px; }
```

### E.9 Checkboxen, Switch, Segmented Control

```html
<!-- Checkbox -->
<div class="checkbox"></div>         <!-- leer -->
<div class="checkbox on"></div>      <!-- aktiv -->

<!-- Switch -->
<div class="switch"></div>           <!-- aus -->
<div class="switch on"></div>        <!-- ein -->

<!-- Segmented Control -->
<div class="seg">
  <button class="on">Tabelle</button>
  <button>Karten</button>
</div>
```

**CSS:**

```css
/* Checkbox */
.[prefix]-app .checkbox {
  width: 17px; height: 17px;
  border-radius: var(--r-sm);
  border: 1.5px solid var(--border-strong);
  background: var(--surface);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.12s, border-color 0.12s;
}
.[prefix]-app .checkbox.on {
  background: var(--accent);
  border-color: var(--accent);
}
.[prefix]-app .checkbox.on::after {
  content: '';
  width: 11px; height: 11px;
  background: url("data:image/svg+xml,%3Csvg viewBox='0 0 12 12' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M2 6l3 3 5-5' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;
}

/* Switch */
.[prefix]-app .switch {
  width: 38px; height: 22px;
  border-radius: 20px;
  background: var(--border-strong);
  position: relative;
  cursor: pointer;
  transition: background 0.16s;
  flex-shrink: 0;
}
.[prefix]-app .switch::after {
  content: '';
  position: absolute;
  left: 2px; top: 2px;
  width: 18px; height: 18px;
  border-radius: 50%;
  background: #fff;
  box-shadow: var(--shadow-sm);
  transition: left 0.16s;
}
.[prefix]-app .switch.on { background: var(--accent); }
.[prefix]-app .switch.on::after { left: 18px; }

/* Segmented Control */
.[prefix]-app .seg {
  display: inline-flex;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 3px;
  gap: 2px;
}
.[prefix]-app .seg button {
  background: transparent;
  border: none;
  border-radius: calc(var(--r) - 2px);
  padding: 5px 13px;
  font-size: 13px; font-weight: 500;
  color: var(--muted);
  cursor: pointer;
  transition: background 0.13s, color 0.13s, box-shadow 0.13s;
}
.[prefix]-app .seg button.on {
  background: var(--surface);
  color: var(--ink);
  box-shadow: var(--shadow-sm);
}
```

### E.10 Formulare

```html
<div class="field">
  <label class="flabel">
    Bezeichnung
    <span class="req">*</span>
    <span class="opt">(optional)</span>
  </label>
  <input type="text" class="input" placeholder="Placeholder…" />
  <p class="fhint">Hilfstext oder Erläuterung zum Feld.</p>
</div>

<div class="field">
  <label class="flabel">Beschreibung</label>
  <textarea class="textarea" rows="4" placeholder="Längerer Text…"></textarea>
</div>

<div class="field">
  <label class="flabel">Kategorie</label>
  <div class="select-wrap">
    <select class="selectbox">
      <option>Option A</option>
      <option>Option B</option>
    </select>
    <span>▾</span>
  </div>
</div>

<!-- Monospace-Input (für Keys, IDs, Code) -->
<input type="text" class="input mono" value="mein_feld_key" />
```

**CSS:**

```css
.[prefix]-app .field { margin-bottom: 18px; }
.[prefix]-app .field:last-child { margin-bottom: 0; }

.[prefix]-app .flabel {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 13px; font-weight: 600;
  color: var(--ink);
  margin-bottom: 6px;
}
.[prefix]-app .flabel .req { color: var(--danger); font-weight: 700; }
.[prefix]-app .flabel .opt { color: var(--faint); font-weight: 400; font-size: 12px; }

.[prefix]-app .fhint {
  font-size: 12px;
  color: var(--muted);
  margin: 6px 0 0;
  line-height: 1.4;
}

.[prefix]-app .input,
.[prefix]-app .textarea,
.[prefix]-app .selectbox {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--r);
  padding: 9px 12px;
  font-size: 14px;
  font-family: var(--font-ui);
  color: var(--ink);
  transition: border-color 0.13s, box-shadow 0.13s;
  box-sizing: border-box;
}
.[prefix]-app .input:focus,
.[prefix]-app .textarea:focus,
.[prefix]-app .selectbox:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}
.[prefix]-app .input::placeholder,
.[prefix]-app .textarea::placeholder { color: var(--faint); }
.[prefix]-app .textarea { min-height: 84px; resize: vertical; }
.[prefix]-app .input.mono { font-family: var(--font-mono); font-size: 13px; }

.[prefix]-app .select-wrap {
  position: relative;
  display: block;
}
.[prefix]-app .select-wrap .selectbox { appearance: none; padding-right: 32px; }
.[prefix]-app .select-wrap > span {
  position: absolute;
  right: 12px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  pointer-events: none;
  font-size: 12px;
}
```

### E.11 Sheet / Slide-Over

```html
<!-- Scrim (Hintergrund-Overlay) -->
<div class="scrim" id="sheet-scrim"></div>

<!-- Sheet Panel -->
<div class="sheet" id="my-sheet">
  <div class="sheet-head">
    <h2>Titel des Sheets</h2>
    <button class="btn icon ghost sm" id="sheet-close" aria-label="Schließen">✕</button>
  </div>
  <div class="sheet-body">
    <!-- Formular oder Inhalte -->
  </div>
  <div class="sheet-foot">
    <button class="btn ghost" id="sheet-cancel">Abbrechen</button>
    <div style="flex:1"></div>
    <button class="btn primary" id="sheet-save">Speichern</button>
  </div>
</div>
```

**CSS:**

```css
.[prefix]-app .scrim {
  position: fixed; inset: 0;
  background: oklch(0.2 0.02 260 / 0.34);
  z-index: 40;
  animation: routein 0.18s ease;
}
.[prefix]-app .sheet {
  position: fixed;
  right: 0; top: 0; bottom: 0;
  width: 480px; max-width: 94vw;
  background: var(--surface);
  border-left: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
  z-index: 41;
  display: flex;
  flex-direction: column;
  animation: slidein 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}
.[prefix]-app .sheet-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.[prefix]-app .sheet-head h2 {
  font-family: var(--font-brand);
  font-size: 17px; font-weight: 600;
  letter-spacing: -0.01em;
  margin: 0;
}
.[prefix]-app .sheet-body {
  flex: 1;
  overflow-y: auto;
  padding: 22px;
}
.[prefix]-app .sheet-foot {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 22px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
  flex-shrink: 0;
}
```

### E.12 Toast-Benachrichtigungen

```html
<div class="toast-wrap" id="toast-wrap">
  <!-- Toasts werden per JS eingefügt -->
</div>
```

**CSS:**

```css
.[prefix]-app .toast-wrap {
  position: fixed;
  bottom: 22px;
  left: 50%; transform: translateX(-50%);
  z-index: 60;
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
}
.[prefix]-app .toast {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--ink);
  color: #fff;
  font-size: 13.5px; font-weight: 500;
  padding: 11px 16px;
  border-radius: var(--r);
  box-shadow: var(--shadow-lg);
  animation: toastin 0.24s ease;
  white-space: nowrap;
}
```

**JS-Muster (Vanilla):**

```js
function showToast(msg, duration = 2600) {
  const wrap = document.getElementById('[prefix]-toast-wrap');
  const toast = document.createElement('div');
  toast.className = '[prefix]-app .toast'; // Anpassen
  toast.textContent = msg;
  wrap.appendChild(toast);
  setTimeout(() => toast.remove(), duration);
}
```

### E.13 Badges

```html
<span class="badge">Standard</span>
<span class="badge ok dot">Veröffentlicht</span>
<span class="badge warn dot">In Prüfung</span>
<span class="badge accent">Neu</span>
<span class="badge thread">Archiv</span>
<span class="badge mono">field_key</span>
```

**CSS:**

```css
.[prefix]-app .badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px; font-weight: 600;
  padding: 2.5px 9px;
  border-radius: 20px;
  background: var(--surface-sunken);
  color: var(--ink-soft);
  border: 1px solid var(--border);
  line-height: 1;
  white-space: nowrap;
}
.[prefix]-app .badge.dot::before {
  content: '';
  width: 6px; height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}
.[prefix]-app .badge.ok   { background: var(--ok-soft);   color: var(--ok);       border-color: transparent; }
.[prefix]-app .badge.warn { background: var(--warn-soft);  color: var(--warn-ink); border-color: transparent; }
.[prefix]-app .badge.accent { background: var(--accent-soft); color: var(--accent-ink); border-color: var(--accent-soft-bd); }
.[prefix]-app .badge.thread { background: var(--thread-soft); color: var(--thread-ink); border-color: transparent; }
.[prefix]-app .badge.mono { font-family: var(--font-mono); font-size: 11px; font-weight: 500; }
```

### E.14 Typ-Chip, Mono-Key, Empty-State, Image-Placeholder

```html
<!-- Typ-Chip (Feldtyp-Anzeige) -->
<span class="ftype">
  <svg width="13" height="13"><!-- Icon --></svg>
  text
</span>

<!-- Mono-Key (API-Schlüssel, IDs) -->
<span class="key">mein_feld_key</span>

<!-- Empty State -->
<div class="empty">
  <div class="ei">
    <svg width="46" height="46"><!-- Icon --></svg>
  </div>
  <h4>Noch nichts hier</h4>
  <p>Erstelle den ersten Eintrag, um loszulegen.</p>
  <button class="btn primary" style="margin-top:16px;">Jetzt erstellen</button>
</div>

<!-- Image-Placeholder -->
<div class="imgph">Kein Bild ausgewählt</div>
<div class="imgph up">Bild hochladen ↑</div>
```

**CSS:**

```css
.[prefix]-app .ftype {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-mono);
  font-size: 11px;
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: 2px 6px;
  color: var(--muted);
}
.[prefix]-app .ftype svg { color: var(--accent); }

.[prefix]-app .key {
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--muted);
  background: var(--surface-sunken);
  border: 1px solid var(--border);
  border-radius: 5px;
  padding: 1px 6px;
}

.[prefix]-app .empty {
  text-align: center;
  padding: 48px 20px;
}
.[prefix]-app .empty .ei {
  display: flex; justify-content: center;
  margin-bottom: 16px;
  color: var(--faint);
}
.[prefix]-app .empty h4 {
  font-family: var(--font-brand);
  font-size: 16px; font-weight: 600;
  margin: 0 0 8px;
  color: var(--ink);
}
.[prefix]-app .empty p { color: var(--muted); font-size: 14px; margin: 0; }

.[prefix]-app .imgph {
  border: 1.5px dashed var(--border-strong);
  border-radius: var(--r);
  padding: 24px;
  text-align: center;
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--muted);
  background: repeating-linear-gradient(
    -45deg,
    var(--surface-sunken), var(--surface-sunken) 4px,
    var(--surface) 4px, var(--surface) 10px
  );
  cursor: default;
  transition: border-color 0.13s, color 0.13s;
}
.[prefix]-app .imgph.up { cursor: pointer; }
.[prefix]-app .imgph.up:hover {
  border-color: var(--accent);
  color: var(--accent);
}
```

### E.15 Utility-Klassen & Trennlinie

```html
<hr class="divider" />
<span class="muted">Sekundärer Text</span>
<span class="mono">code_wert</span>

<div class="row">
  <span>Links</span>
  <div class="grow"></div>
  <span>Rechts</span>
</div>

<div class="col">
  <span>Oben</span>
  <span>Unten</span>
</div>
```

**CSS:**

```css
.[prefix]-app .divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: var(--gap) 0;
}
.[prefix]-app .muted  { color: var(--muted); }
.[prefix]-app .mono   { font-family: var(--font-mono); }
.[prefix]-app .row    { display: flex; align-items: center; gap: 10px; }
.[prefix]-app .col    { display: flex; flex-direction: column; }
.[prefix]-app .grow   { flex: 1; }
```

### E.16 Branding-Footer (Pflicht auf jeder Hauptseite)

```html
<footer class="[prefix]-branding-footer">
  <span>Tom Evers · <a href="https://bezugssysteme.de" target="_blank" rel="noopener">bezugssysteme.de</a></span>
  <span>v1.0.0</span>
</footer>
```

**CSS:**

```css
.[prefix]-app .page .[prefix]-branding-footer,
.[prefix]-branding-footer {
  margin-top: 48px;
  border-top: 1px solid var(--border);
  padding-top: 16px;
  font-size: 12px;
  color: var(--muted);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.[prefix]-branding-footer a {
  color: inherit;
  text-decoration: none;
}
.[prefix]-branding-footer a:hover { color: var(--ink); }
```

---

## F. WordPress-Admin-Bridge

### F.1 Admin-Bridge CSS (Pflicht)

```css
/*
 * WordPress-Admin-Hintergrund auf Plugin-Seiten unterdrücken.
 * Body-Klasse: body.toplevel_page_[plugin-slug]
 * Bei Unterseiten: body.[plugin-slug]_page_[submenu-slug]
 */
body.toplevel_page_[plugin-slug] #wpcontent,
body.toplevel_page_[plugin-slug] #wpbody-content,
body.[plugin-slug]_page_[submenu-slug] #wpcontent,
body.[plugin-slug]_page_[submenu-slug] #wpbody-content {
  background: oklch(0.975 0.004 255) !important;
  padding: 0 !important;
}
body.toplevel_page_[plugin-slug] .wrap,
body.[plugin-slug]_page_[submenu-slug] .wrap {
  margin: 0;
  max-width: none;
  padding: 0;
}
```

### F.2 Admin-Enqueue

```php
add_action('admin_enqueue_scripts', function($hook) {
    // Nur auf eigenen Plugin-Seiten laden
    if (strpos($hook, '[plugin-slug]') === false) return;

    wp_enqueue_style(
        '[prefix]-tokens',
        plugin_dir_url(__FILE__) . 'assets/tokens.css',
        [],
        '1.0.0'
    );
    wp_enqueue_style(
        '[prefix]-styles',
        plugin_dir_url(__FILE__) . 'assets/styles.css',
        ['[prefix]-tokens'],
        '1.0.0'
    );
    wp_enqueue_script(
        '[prefix]-app',
        plugin_dir_url(__FILE__) . 'assets/app.js',
        ['wp-element'],
        '1.0.0',
        true
    );

    // Konfiguration an JS übergeben
    wp_localize_script('[prefix]-app', '[prefix]Data', [
        'nonce'  => wp_create_nonce('wp_rest'),
        'apiUrl' => rest_url('[prefix]/v1/'),
        'siteUrl'=> get_site_url(),
    ]);
});
```

### F.3 Admin-Menü-Registration

```php
add_action('admin_menu', function() {
    add_menu_page(
        '[Plugin-Name]',          // Seitentitel
        '[Plugin-Name]',          // Menübezeichnung
        'manage_options',         // Capability
        '[plugin-slug]',          // Menu-Slug
        '[prefix]_render_page',   // Callback
        'dashicons-layout',       // Icon
        30                        // Position
    );
    add_submenu_page('[plugin-slug]', 'Dashboard',      'Dashboard',      'manage_options', '[plugin-slug]',           '[prefix]_render_dashboard');
    add_submenu_page('[plugin-slug]', 'Einträge',       'Einträge',       'manage_options', '[plugin-slug]-entries',   '[prefix]_render_entries');
    add_submenu_page('[plugin-slug]', 'Einstellungen',  'Einstellungen',  'manage_options', '[plugin-slug]-settings',  '[prefix]_render_settings');
});

function [prefix]_render_page() {
    // App-Shell ausgeben
    echo '<div class="[prefix]-app" data-accent="blue" data-side="dark" data-density="regular">';
    // ... Sidebar, Main, Content
    echo '</div>';
}
```

### F.4 REST-API-Routes

```php
add_action('rest_api_init', function() {
    $namespace = '[prefix]/v1';

    register_rest_route($namespace, '/entries', [
        'methods'             => 'GET',
        'callback'            => '[prefix]_get_entries',
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ]);
    register_rest_route($namespace, '/entries', [
        'methods'             => 'POST',
        'callback'            => '[prefix]_create_entry',
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ]);
    register_rest_route($namespace, '/entries/(?P<id>\d+)', [
        'methods'             => 'PUT,DELETE',
        'callback'            => '[prefix]_update_entry',
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ]);
});
```

### F.5 Nonce-Patterns

```php
// PHP: Nonce für Settings-Formulare
wp_nonce_field('[prefix]-save-settings', '[prefix]_nonce');

// PHP: Nonce prüfen
if (!isset($_POST['[prefix]_nonce']) || 
    !wp_verify_nonce($_POST['[prefix]_nonce'], '[prefix]-save-settings')) {
    wp_die('Sicherheitsprüfung fehlgeschlagen.');
}

// PHP: Nonce für REST-API (via wp_localize_script übergeben, dann im JS-Fetch-Header):
// 'X-WP-Nonce': [prefix]Data.nonce
```

```js
// JS: Authentifizierter REST-API-Aufruf
async function apiGet(endpoint) {
  const res = await fetch([prefix]Data.apiUrl + endpoint, {
    headers: { 'X-WP-Nonce': [prefix]Data.nonce }
  });
  return res.json();
}

async function apiPost(endpoint, data) {
  const res = await fetch([prefix]Data.apiUrl + endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': [prefix]Data.nonce
    },
    body: JSON.stringify(data)
  });
  return res.json();
}
```

---

## G. JavaScript-Muster

### G.1 Sheet öffnen / schließen (Vanilla JS)

```js
function openSheet(sheetId) {
  document.getElementById(sheetId).style.display = 'flex';
  document.getElementById('sheet-scrim').style.display = 'block';

  // Escape-Taste zum Schließen
  const onKey = (e) => {
    if (e.key === 'Escape') closeSheet(sheetId, onKey);
  };
  document.addEventListener('keydown', onKey);

  // Scrim-Klick zum Schließen
  document.getElementById('sheet-scrim').onclick = () => closeSheet(sheetId, onKey);
}

function closeSheet(sheetId, onKey) {
  document.getElementById(sheetId).style.display = 'none';
  document.getElementById('sheet-scrim').style.display = 'none';
  document.removeEventListener('keydown', onKey);
}
```

### G.2 Auto-Slug (slugify mit deutschen Umlauten)

```js
function slugify(str) {
  return str
    .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue')
    .replace(/Ä/g, 'ae').replace(/Ö/g, 'oe').replace(/Ü/g, 'ue')
    .replace(/ß/g, 'ss')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '');
}

// Verwendung: Label → Key-Feld
// keyTouched = true wenn User den Key manuell bearbeitet hat → Auto-Slug deaktivieren
let keyTouched = false;

labelInput.addEventListener('input', () => {
  if (!keyTouched) keyInput.value = slugify(labelInput.value);
});
keyInput.addEventListener('input', () => { keyTouched = true; });
```

**Validierung Field-Key:** `/^[a-z0-9_]+$/` — nur Kleinbuchstaben, Ziffern, Unterstriche.

### G.3 Select-All für Tabellen

```js
const selectAll = document.getElementById('select-all');
const rowBoxes  = document.querySelectorAll('.tbl tbody .checkbox');
const bulkBar   = document.getElementById('bulk-bar');

selectAll.addEventListener('click', () => {
  const allOn = selectAll.classList.contains('on');
  selectAll.classList.toggle('on');
  rowBoxes.forEach(cb => cb.classList.toggle('on', !allOn));
  updateBulkBar();
});

rowBoxes.forEach(cb => {
  cb.addEventListener('click', () => {
    cb.classList.toggle('on');
    updateBulkBar();
  });
});

function updateBulkBar() {
  const count = document.querySelectorAll('.tbl tbody .checkbox.on').length;
  bulkBar.style.display = count > 0 ? 'flex' : 'none';
  bulkBar.querySelector('.bulk-count').textContent = count + ' ausgewählt';
}
```

### G.4 Routing (State-basiert, Vanilla JS)

```js
// Einfaches State-Router-Muster für Plugin-Seiten ohne Build-Step
const app = {
  route: 'dashboard',
  params: {},

  go(name, params = {}) {
    this.route = name;
    this.params = params;
    this.render();
    document.querySelector('.[prefix]-app .content')?.scrollTo(0, 0);
    // Breadcrumbs aktualisieren
    updateBreadcrumbs(name, params);
  },

  render() {
    const content = document.getElementById('[prefix]-content');
    content.innerHTML = '';
    content.classList.remove('route-active');
    requestAnimationFrame(() => {
      content.classList.add('route-active');
      screens[this.route]?.(content, this.params);
    });
  }
};

// Screen-Definitionen
const screens = {
  dashboard: (el) => { el.innerHTML = `...`; },
  entries:   (el) => { el.innerHTML = `...`; },
  settings:  (el) => { el.innerHTML = `...`; },
};

// CSS für Route-Animation
// .[prefix]-app .route-active { animation: routein 0.24s ease; }
```

---

## H. Screen-Blueprints (Plugin-agnostisch)

Die folgenden Screens sind Standardmuster und können für jedes Plugin angepasst werden.

### H.1 Dashboard-Screen

**Struktur:**
```
Page-Header (Titel + optionaler Erstellen-Button)
Stat-Grid (4 Kacheln: Gesamt, Veröffentlicht, Entwürfe, [Plugin-spezifisch])
2-Spalten-Grid (1fr 1fr):
  Links: Letzte Aktivitäten (Card + Tabelle, 5 Zeilen)
  Rechts: Schnellzugriff (Card + vertikale Link-Liste)
Branding-Footer
```

### H.2 Listen-Screen

**Struktur:**
```
Page-Header (Titel + Erstellen-Button)
Filter-Zeile: Seg-Control (Ansicht), Suchfeld, Status-Filter-Dropdown
Card mit Tabelle (Checkbox | Primärspalte | Status-Badge | Typ-Chip | Datum | Aktionen)
Pagination-Leiste (Anzahl-Info links, Zurück/Weiter rechts)
Branding-Footer
```

**Bulk-Action-Bar** (erscheint über der Tabelle wenn Selektion > 0):
```html
<div class="bulk-bar" style="display:none; align-items:center; gap:8px; padding:10px var(--pad); background:var(--accent-soft); border:1px solid var(--accent-soft-bd); border-radius:var(--r); margin-bottom:12px;">
  <span class="bulk-count" style="font-size:13px; font-weight:600; color:var(--accent-ink);">0 ausgewählt</span>
  <div style="flex:1"></div>
  <button class="btn ghost sm">Veröffentlichen</button>
  <button class="btn danger sm">Löschen</button>
</div>
```

### H.3 Editor-Screen (Formular)

**Struktur:**
```
Page-Header (Zurück-Link + Titel)
2-Spalten-Layout (2fr 1fr):
  Links (Hauptinhalt):
    Card "Inhalt" – Hauptfelder (Titel, Beschreibung, Felder je nach Plugin)
    Card "Weitere Felder" – optionale Zusatzfelder
  Rechts (Sidebar):
    Card "Status" – Status-Radio (Entwurf / In Prüfung / Veröffentlicht) + Speichern-Button
    Card "Metadaten" – Erstellt-Datum, Autor, ID
Branding-Footer
```

### H.4 Einstellungen-Screen

**Struktur:**
```
Page-Header (Titel: "Einstellungen")
2-Spalten-Layout (220px | 1fr):
  Links: Vertikale Tab-Navigation (Allgemein, Rollen & Rechte, API & Export, Erweitert)
  Rechts: Tab-Content-Card (je Tab ein Formular oder eine Tabelle)
  Branding-Footer (außerhalb des Grids)
```

**Vertikale Tab-Navigation:**
```html
<nav class="settings-tabs">
  <a class="stab active" data-tab="general">Allgemein</a>
  <a class="stab" data-tab="roles">Rollen &amp; Rechte</a>
  <a class="stab" data-tab="api">API &amp; Export</a>
  <a class="stab" data-tab="advanced">Erweitert</a>
</nav>
```

```css
.[prefix]-app .settings-tabs { display: flex; flex-direction: column; gap: 2px; }
.[prefix]-app .stab {
  display: block;
  padding: 9px 14px;
  border-radius: var(--r);
  font-size: 13.5px;
  color: var(--ink-soft);
  text-decoration: none;
  cursor: pointer;
  transition: background 0.13s, color 0.13s;
}
.[prefix]-app .stab:hover  { background: var(--surface-2); color: var(--ink); }
.[prefix]-app .stab.active { background: var(--accent-soft); color: var(--accent-ink); font-weight: 600; }
```

---

## I. Anti-Pattern-Liste

Die KI darf folgendes **niemals** tun:

| Verboten | Richtig |
|---|---|
| `color: #3b82f6` | `color: var(--accent)` |
| `padding: 22px` direkt | `padding: var(--pad)` |
| `.card { ... }` global | `.[prefix]-app .card { ... }` |
| Präfix aus Beispiel übernehmen (z. B. `bsf-`) | Am Gesprächsanfang nach Präfix fragen |
| Branding-Footer weglassen | Immer auf jeder Hauptseite einbauen |
| WP-Core-Klassen: `.button`, `.notice`, `.wrap` | Eigene Klassen mit Präfix |
| `tweaks-panel` in Produktionscode | Nur im Prototyp, nicht in echtem Plugin |
| CDN-React/Babel-Tags | `@wordpress/scripts` Build-Setup |
| Animationsendstatus `opacity: 0` | `opacity: 1` als Endzustand sicherstellen |
| Sheet ohne Escape-Taste-Listener | Immer `keydown: Escape` + Cleanup beim Schließen |
| Inline-Styles für Farben und Abstände | Token-Variablen in externem CSS |

---

## J. Dateistruktur (Standard)

```
[plugin-root]/
├── [plugin-slug].php          # Plugin-Header, Hooks
├── includes/
│   ├── admin.php              # Admin-Seiten, Menüs, Enqueue
│   ├── api.php                # REST-Route-Handler
│   └── db.php                 # Datenbankoperationen
├── assets/
│   ├── tokens.css             # Nur CSS Custom Properties (diese Datei)
│   ├── styles.css             # Alle Komponenten-Klassen
│   ├── admin-bridge.css       # WP-Admin-Override-CSS
│   └── app.js                 # Plugin-JavaScript
├── templates/
│   ├── page-dashboard.php     # Dashboard-Screen
│   ├── page-list.php          # Listen-Screen
│   ├── page-editor.php        # Editor/Formular-Screen
│   └── page-settings.php      # Einstellungen-Screen
└── BS-PluginDesignSystem.md   # Diese Datei
```

---

*Tom Evers · BezugsSysteme.de · Stand: Juni 2026*
*Dieses Designsystem basiert auf dem bs·fabric Prototyp, entwickelt mit Claude Design.*