<?php
/**
 * Plugin Name:       Kudo Cards – Send Digital Appreciation
 * Plugin URI:        https://github.com/eversthomas/bs-kudo-karten
 * Description:       Send digital appreciation cards from WordPress. Choose a card, write a message, and deliver it by email with a secure token web view.
 * Version:           0.5.0
 * Requires at least: 6.3
 * Tested up to:      6.8
 * Requires PHP:       8.0
 * Author:            Tom Evers
 * Author URI:        https://bezugssysteme.de
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bs-kudo-karten
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BSKUDO_VERSION', '0.5.0' );
define( 'BSKUDO_CHAR_LIMIT', 240 );
define( 'BSKUDO_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSKUDO_URL', plugin_dir_url( __FILE__ ) );
define( 'BSKUDO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Optional in wp-config.php: define( 'BSKUDO_MAIL_DEBUG', true );
 * Aktiv nur bei expliziter Konstante oder lokaler Entwicklungs-URL (.local, .test, localhost).
 */

if ( file_exists( BSKUDO_PATH . 'vendor/autoload.php' ) ) {
	require_once BSKUDO_PATH . 'vendor/autoload.php';
}

require_once BSKUDO_PATH . 'includes/class-bskudo-loader.php';

/**
 * Rewrite-Regeln für /kudo-karte/{token}/ registrieren.
 */
function bskudo_activate() {
	require_once BSKUDO_PATH . 'includes/class-bskudo-token.php';
	require_once BSKUDO_PATH . 'includes/class-bskudo-card-view.php';

	$card_view = new BSKudo_Card_View();
	$card_view->register_rewrite();
	flush_rewrite_rules();
}

/**
 * Rewrite-Regeln beim Deaktivieren leeren.
 */
function bskudo_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'bskudo_activate' );
register_deactivation_hook( __FILE__, 'bskudo_deactivate' );

bskudo_run();
