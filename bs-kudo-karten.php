<?php
/**
 * Plugin Name:  BS Kudo Karten
 * Plugin URI:   https://github.com/eversthomas/bs-kudo-karten
 * Description:  Digitale Kudo-Karten – entwickelt für Marcus Rosik, Systemische Beratung.
 * Version:      0.1.0
 * Author:       Tom Evers – bezugssysteme.de
 * Author URI:   https://bezugssysteme.de
 * Text Domain:  bs-kudo-karten
 * License:      GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BSKUDO_VERSION', '0.4.0' );
define( 'BSKUDO_CHAR_LIMIT', 160 );
define( 'BSKUDO_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSKUDO_URL', plugin_dir_url( __FILE__ ) );
define( 'BSKUDO_BASENAME', plugin_basename( __FILE__ ) );
define( 'BSKUDO_UPDATE_CACHE_KEY', 'bskudo_github_version' );

/**
 * Optional in wp-config.php: define( 'BSKUDO_MAIL_DEBUG', true );
 * Ohne Konstante: Debug bei WP_DEBUG oder lokaler URL (.local, .test, localhost).
 */

require_once BSKUDO_PATH . 'includes/class-bskudo-loader.php';

if ( is_admin() ) {
	require_once BSKUDO_PATH . 'includes/class-bskudo-updater.php';

	$bskudo_updater = new BSKudo_Updater(
		__FILE__,
		'DEIN-GITHUB-USERNAME',
		'bs-kudo-karten',
		BSKUDO_VERSION
	);
	$bskudo_updater->init();
}

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
