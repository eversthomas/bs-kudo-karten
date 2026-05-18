<?php
/**
 * Plugin Name:  BS Kudo Karten
 * Plugin URI:   https://bezugssysteme.de
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

define( 'BSKUDO_VERSION', '0.1.0' );
define( 'BSKUDO_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSKUDO_URL', plugin_dir_url( __FILE__ ) );
define( 'BSKUDO_BASENAME', plugin_basename( __FILE__ ) );

require_once BSKUDO_PATH . 'includes/class-bskudo-loader.php';

bskudo_run();
