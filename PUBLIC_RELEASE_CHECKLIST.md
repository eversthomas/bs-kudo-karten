# BS Kudo Karten – Checkliste für eine öffentliche Veröffentlichung
**Leitfaden für Datenschutz (DSGVO/UWG), Sicherheit und WordPress.org-Standards**

> **Kontext:**  
> Dieses Dokument dient als verbindliche Arbeits- und Prüfliste für den Fall, dass das Plugin **BS Kudo Karten** (aktuell für den rein internen/proprietären Einsatz konzipiert) öffentlich freigegeben, als Open-Source bereitgestellt oder im offiziellen [WordPress.org Plugin Directory](https://wordpress.org/plugins/) eingereicht werden soll.

---

## Inhaltsverzeichnis
1. [Datenschutz & Rechtskonformität (DSGVO, TTDSG, UWG)](#1-datenschutz--rechtskonformität)
2. [Sicherheit & Härtung (Security)](#2-sicherheit--härtung)
3. [WordPress.org Repository & Coding Standards](#3-wordpressorg-standards)
4. [Kompakte Release-Checkliste (Tabelle)](#4-release-checkliste)

---

## 1. Datenschutz & Rechtskonformität

### 1.1 Lokales Schriften-Hosting (Keine Google Fonts via CDN)
* **Status Quo:**  
  In [`admin/assets/tokens.css`](admin/assets/tokens.css) werden Google Fonts per `@import url('https://fonts.googleapis.com/...');` von US-Servern geladen. Dies betrifft zwar ausschließlich das WordPress-Backend, verstößt aber bei öffentlicher Verbreitung gegen die DSGVO (Urteil LG München I, Az. 3 O 17493/20).
* **Notwendige Maßnahmen:**
  1. Den `@import`-Befehl aus `admin/assets/tokens.css` vollständig entfernen.
  2. **Option A (Empfohlen):** Moderne System-Fonts nutzen:
     ```css
     --font-brand: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
     --font-ui: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
     --font-mono: ui-monospace, "Cascadia Code", "Source Code Pro", Menlo, Consolas, monospace;
     ```
  3. **Option B:** Benötigte Schriftdateien (`.woff2`) lokal im Plugin unter `admin/assets/fonts/` ablegen und per `@font-face` mit relativen URLs einbinden.

---

### 1.2 Die E-Card-Problematik (§ 7 UWG / BGH "Tell-a-Friend")
* **Status Quo:**  
  Nach ständiger BGH-Rechtsprechung (BGH, 12.09.2013, Az. I ZR 208/12) haftet der Seitenbetreiber für unaufgefordert versendete E-Cards an Dritte als Störer/Täter wegen unzumutbarer Belästigung (§ 7 Abs. 2 Nr. 3 UWG).
* **Aktuelle Schutzmaßnahme im Plugin:**  
  Die Absender-Bestätigung ([`BSKudo_Confirm`](includes/class-bskudo-confirm.php)) verhindert anonymen E-Mail-Missbrauch wirksam per Double-Opt-In an den Absender.
* **Notwendige Maßnahmen für Release:**
  1. **Option nicht leichtfertig deaktivierbar machen:**  
     In [`admin/settings-security.php`](admin/settings-security.php) sollte die Option `require_sender_confirmation` standardmäßig fest verdrahtet oder mit einem deutlichen Warnhinweis (rotes Warnbanner über rechtliche Abmahnrisiken bei Deaktivierung) versehen werden.
  2. **Abuse-Hinweis standardisieren:**  
     Den Hinweis zur Meldung unangebrachter Karten ([`mail/template-mail.php`](mail/template-mail.php)) standardmäßig aktivieren, falls eine Absender-/Admin-E-Mail hinterlegt ist.

---

### 1.3 WordPress Core Privacy Integration (Art. 13, 15, 17 DSGVO)
Öffentliche Plugins müssen sich nahtlos in die Datenschutz-Tools des WordPress-Kerns einfügen:
* **Datenschutzerklärung-Leitfaden (`wp_add_privacy_policy_content`):**
  * Hook in `includes/class-bskudo-loader.php` registrieren.
  * Bereitstellung eines vorgefertigten Textbausteins für Seitenbetreiber:
    * Welche Daten werden erhoben (Absender-/Empfänger-Name, E-Mails, Nachrichtentext, IP-Hash).
    * Zweck (Zustellung der digitalen Wertschätzungskarte).
    * Speicherdauer (Verarbeitung im flüchtigen Speicher / Transients mit konfigurierbarer TTL von max. X Tagen; keine dauerhafte Tabellenspeicherung bei Sofortversand).
* **DSGVO-Datenexport & -Löschung (`wp_privacy_personal_data_exporters` / `wp_privacy_personal_data_erasers`):**
  * Handler implementieren, die bei Eingabe einer E-Mail-Adresse prüfen, ob noch geplante Versendungen (`bskudo_job_*`) oder Bestätigungs-Transients (`bskudo_confirm_*`) für diese Adresse vorliegen, und diese exportieren bzw. auf Wunsch löschen.

---

### 1.4 Frontend-Verlinkung der Datenschutzerklärung
* **Status Quo:**  
  Im Formular-Wizard ([`public/templates/wizard.php`](public/templates/wizard.php)) wird ein statischer Hinweistext angezeigt.
* **Notwendige Maßnahme:**
  * Wenn im WordPress-Core eine Datenschutzerklärung definiert ist (`get_privacy_policy_url()`), sollte der Text im Formular automatisch einen anklickbaren Link zur Datenschutzerklärung enthalten.

---

### 1.5 Schutz vor verwaisten Transients (Data Minimization / Garbage Collection)
* **Status Quo:**  
  Geplante Versendungen (`bskudo_job_*`) werden als Transients in `wp_options` gespeichert. Fällt der WP-Cron aus oder wird der geplante Zeitpunkt verpasst, bleiben Transients theoretisch liegen.
* **Notwendige Maßnahme:**
  * Einen täglichen Wartungs-Cron (`bskudo_daily_cleanup`) einrichten, der abgelaufene `bskudo_`-Transients und verwaiste Lock-Optionen automatisch aus der Datenbank bereinigt.

---

## 2. Sicherheit & Härtung (Security)

### 2.1 Schutz von Debug-Logs & Vermeidung von Pfadoffengebung
* **Status Quo:**  
  1. In [`BSKudo_Send::send_success_response()`](includes/class-bskudo-send.php) werden bei aktivem Debug absolute Serverpfade (`log_file`, `html_file`) an den Client zurückgegeben.
  2. [`BSKudo_Debug::ensure_dir()`](includes/class-bskudo-debug.php) schützt Verzeichnisse nur per Apache-`.htaccess`. Auf Nginx-, Caddy- oder LiteSpeed-Servern greift dieser Schutz nicht.
* **Notwendige Maßnahmen:**
  1. **Keine Serverpfade im JSON:**  
     Pfade aus der AJAX-Response entfernen oder nur auf Admins mit `manage_options`-Capability beschränken.
  2. **Logdateien web-unzugänglich machen:**  
     * Logdatei nicht als `.log` speichern, sondern als `.php` mit integriertem Exit-Header:
       ```php
       <?php exit; ?> // Log-Einträge folgen hier...
       ```
     * Alternativ: Dateinamen mit einem kryptografischen Zufallshash versehen, z. B. `mail-debug-[wp_salt-hash].log`.
  3. **Lokale Testdateien aus dem Repository entfernen:**  
     Die Dateien `debug/mail.log` und `debug/last-mail.html` im Root dürfen nicht in Release-Pakete gepackt werden.

---

### 2.2 Cloudflare Header Spoofing absichern
* **Status Quo:**  
  In [`BSKudo_Security::get_client_ip()`](includes/class-bskudo-security.php) wird `$_SERVER['HTTP_CF_CONNECTING_IP']` ungeprüft übernommen, wenn die Option `behind_cloudflare` aktiv ist.
* **Notwendige Maßnahme:**
  * Der Header `HTTP_CF_CONNECTING_IP` darf nur ausgewertet werden, wenn `$_SERVER['REMOTE_ADDR']` nachweislich aus den offiziellen IP-Netzen von Cloudflare stammt (oder wenn ein Reverse-Proxy auf Serverebene dies sicherstellt). Andernfalls kann das IP-Rate-Limiting über manipulierte HTTP-Header umgangen werden.

---

## 3. WordPress.org Repository & Coding Standards

### 3.1 Saubere Deinstallation (`uninstall.php`)
* **Status Quo:**  
  Es existiert kein Deinstallations-Skript.
* **Notwendige Maßnahme:**  
  Eine Datei [`uninstall.php`](uninstall.php) im Plugin-Root anlegen:
  ```php
  <?php
  if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
      exit;
  }

  // 1. Einstellungen löschen
  delete_option( 'bskudo_settings' );

  // 2. Custom Post Types & Meta löschen (optional oder per Option steuerbar)
  // 3. Temporäre Upload-Verzeichnisse leeren (uploads/bskudo-mail-qr und bskudo-debug-private)
  // 4. Verbliebene Transients bereinigen
  ```

---

### 3.2 Vollständige Lokalisierung (i18n)
* **Status Quo:**  
  Alle Strings nutzen `__()` und `esc_html__()`, aber der Aufruf von `load_plugin_textdomain()` fehlt.
* **Notwendige Maßnahme:**
  * Im Loader registrieren:
    ```php
    add_action( 'init', function() {
        load_plugin_textdomain(
            'bs-kudo-karten',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    } );
    ```
  * Eine `.pot`-Sprachvorlage unter `languages/bs-kudo-karten.pot` generieren.

---

### 3.3 Detail-Korrekturen & Code Smells
* **Textbaustein-Zeichenlimit:**  
  In [`admin/class-bskudo-textbaustein-meta.php`](admin/class-bskudo-textbaustein-meta.php#L160) die harte Konstante `BSKUDO_CHAR_LIMIT` durch `BSKudo_Settings::get_char_limit()` ersetzen.
* **Dateileichen bereinigen:**  
  Das leere Verzeichnis [`admin/css/`](admin/css/) entfernen.

---

## 4. Release-Checkliste

| Priorität | Bereich | Maßnahme | Betroffene Datei(en) |
| :---: | :--- | :--- | :--- |
| 🔴 **P1** | **Datenschutz** | Google Fonts `@import` entfernen; System-Fonts oder lokale WOFF2 nutzen | `admin/assets/tokens.css` |
| 🔴 **P1** | **Sicherheit** | Absolute Serverpfade aus AJAX-JSON-Antwort entfernen | `includes/class-bskudo-send.php` |
| 🔴 **P1** | **Sicherheit** | Logdateien gegen direkten Webzugriff (Nginx/Caddy) via PHP-Exit-Header absichern | `includes/class-bskudo-debug.php` |
| 🔴 **P1** | **UWG/Recht** | Absender-Bestätigung (Double-Opt-In) standardmäßig forcieren & Warnhinweis bei Deaktivierung | `admin/settings-security.php` |
| 🟡 **P2** | **WP-Standard** | `uninstall.php` für saubere Bereinigung beim Löschen erstellen | `uninstall.php` *(neu)* |
| 🟡 **P2** | **WP-Standard** | `load_plugin_textdomain()` im Loader registrieren | `includes/class-bskudo-loader.php` |
| 🟡 **P2** | **Datenschutz** | Datenschutz-Mustertext via `wp_add_privacy_policy_content()` registrieren | `includes/class-bskudo-loader.php` |
| 🟡 **P2** | **Sicherheit** | Cloudflare-IP-Header-Validierung gegen Spoofing absichern | `includes/class-bskudo-security.php` |
| 🟢 **P3** | **Funktional** | Zeichenlimit bei Textbausteinen an dynamische Option anbinden | `admin/class-bskudo-textbaustein-meta.php` |
| 🟢 **P3** | **Bereinigung** | Leeres Verzeichnis `admin/css/` und lokale Logs entfernen | `admin/css/`, `debug/mail.log` |
| 🟢 **P3** | **Datenschutz** | Dynamischen Link zur Website-Datenschutzerklärung im Wizard einbinden | `public/templates/wizard.php` |
