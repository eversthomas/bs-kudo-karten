=== Kudo Cards – Send Digital Appreciation ===
Contributors: tomevers
Donate link: https://bezugssysteme.de
Tags: kudo, appreciation, cards, email, recognition
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send heartfelt digital kudo cards via email. Choose a card, write directly on it, and deliver appreciation in seconds.

== Description ==

**Kudo Cards** lets your visitors send beautiful digital appreciation cards to anyone – directly from your WordPress site.

The sender picks a card, writes their message **directly on the card** (no separate text field), enters the recipient's details, and sends. The recipient gets an email with a link to a stunning web view of their card.

= Key Features =

* **3-step wizard** with smooth scroll dramaturgy – no hard screen switches
* **Write directly on the card** – text overlay on the card image (contenteditable)
* **Live character counter** – max. 160 characters, two lines
* **Text impulses** – configurable chips per card to inspire the sender
* **"Send to myself" option** – self-appreciation as an explicit feature
* **Scheduled sending** – choose a date and time for delivery
* **Token-based web view** – recipient gets a personal URL (valid for 30 days)
* **QR code** – included in the email for mobile access
* **Honeypot + rate limiting** – built-in spam protection
* **No data storage** – personal data is never persisted
* **Branding tab** – configure logo, colors, and mail template in the backend
* **Shortcode** `[kudo_karten]` – place the wizard on any page or post

= How It Works =

1. Place `[kudo_karten]` on any page
2. Visitors choose a card, write their message directly on it
3. They enter sender and recipient details
4. The recipient receives an email with a link to their personal card view

= Privacy =

Kudo Cards is designed with privacy in mind. No personal data (names, email addresses, messages) is stored in the database. Data is only used for sending the card and then discarded.

= Shortcode Options =

`[kudo_karten]` – shows all available cards

`[kudo_karten set="slug"]` – shows only cards from a specific set

= For Coaches, Teams & Organizations =

Kudo Cards is ideal for:

* Coaches and therapists who want to offer appreciation tools
* Teams that want to celebrate each other
* Organizations building a culture of recognition
* Anyone who wants to send something more personal than an email

= About the Developer =

Kudo Cards is developed by Tom Evers – [bezugssysteme.de](https://bezugssysteme.de)

== Installation ==

= Automatic Installation =

1. Go to **Plugins → Add New** in your WordPress dashboard
2. Search for **Kudo Cards**
3. Click **Install Now** and then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Activate the plugin

= After Activation =

1. Go to **Kudo Cards → Manage Cards** and add your first card
   (upload a card image via the WordPress media manager)
2. Go to **Kudo Cards → Settings** to configure sender email and branding
3. Place `[kudo_karten]` on any page

= Recommended =

For reliable email delivery, install and configure [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/).

== Frequently Asked Questions ==

= What image formats are supported for cards? =

WebP and JPG/PNG are both supported. We recommend WebP for best quality and file size. Export your card images at a minimum width of 1400px.

= Are personal data stored? =

No. Names and email addresses are only used for sending the card and are never stored in the database.

= Can I use my own card designs? =

Yes. Cards are managed as custom post types in the WordPress backend. Upload any image via the media manager and assign it to a card.

= How does the token-based web view work? =

When a card is sent, a unique token is generated and stored as a WordPress transient (not in the database as a permanent record). The token expires after 30 days (configurable). The recipient receives a personal URL that displays their card beautifully in the browser.

= Can I limit which cards are shown? =

Yes. Use the shortcode attribute `set` to show only cards from a specific set:
`[kudo_karten set="your-set-slug"]`

= Does it work with page builders like Divi or Elementor? =

Yes. The plugin uses its own CSS namespace (`.bskudo-`) to avoid conflicts with themes and page builders.

= Can visitors schedule card delivery? =

Yes, if you enable the "Delayed Send" feature in the plugin settings. Visitors can choose a specific date and time for delivery.

= What spam protection is included? =

The plugin includes a honeypot field (always active) and IP-based rate limiting (configurable, default: 5 cards per hour per IP).

= Is the plugin GDPR compliant? =

The plugin itself stores no personal data. You should mention the card sending functionality in your privacy policy. The plugin provides a configurable privacy notice text that is shown to senders before they submit.

== Screenshots ==

1. Step 1 – Choose your kudo card from the card grid
2. Step 2 – Write your message directly on the card with live preview
3. Step 3 – Enter sender and recipient details
4. The recipient's email – elegant and personal
5. The web view – the card in full beauty in the browser
6. Backend – Card management and settings

== Changelog ==

= 0.8.2 =
* Mail footer supports Markdown links [text](url) plus auto-linked bare URLs
* Backend helper text with example for the footer editor

= 0.8.1 =
* Mail QR code always links to the online card view (separate from configurable back-of-card QR)
* Backend hint clarifies mail QR vs. card-back QR

= 0.8.0 =
* Backend layout fix for the card editor (WordPress post screen)
* Optional custom QR target URL per card
* Configurable back-side layout (blocks, columns, order, visibility)
* Mail debug artifacts excluded from version control

= 0.7.0 =
* Fixed escaping in card-view template
* Fixed Tested up to: WordPress 7.0
* Nonce verification hardened
* Admin template phpcs annotations added

= 0.6.0 =
* Plugin Checker fixes for wordpress.org submission
* Removed .DS_Store and hidden files
* Added translators comments
* Template files marked with phpcs:disable for extract() variable pattern

= 0.5.0 =
* Plugin prepared for public release
* Removed client-specific references
* Default branding text now uses site name

= 0.4.0 =
* Card images now use public URLs instead of base64 in emails
* QR code saved as PNG file for reliable email display
* Mail template redesigned as elegant "door opener" to web view

= 0.3.0 =
* Token-based web view for recipients
* Front/back side toggle in web view
* Card view shows front side only on load

= 0.2.0 =
* GitHub auto-updater integrated
* Plugin URI added

= 0.1.0 =
* Initial release
* 3-step wizard with scroll dramaturgy
* contenteditable overlay on card image
* wp_mail() sending with honeypot and rate limiting
* Token system for recipient web view
* Backend with three tabs: General, Branding, Security

== Upgrade Notice ==

= 0.5.0 =
First public release. No breaking changes.