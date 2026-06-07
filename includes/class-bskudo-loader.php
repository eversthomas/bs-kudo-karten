<?php
/**
 * Zentraler Hook-Loader für BS Kudo Karten.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lädt Abhängigkeiten und registriert alle Plugin-Hooks.
 */
class BSKudo_Loader {

	/**
	 * CPT-Handler.
	 *
	 * @var BSKudo_CPT
	 */
	private $cpt;

	/**
	 * Admin-Handler (nur im Backend).
	 *
	 * @var BSKudo_Admin|null
	 */
	private $admin;

	/**
	 * Shortcode-Handler.
	 *
	 * @var BSKudo_Shortcode
	 */
	private $shortcode;

	/**
	 * AJAX-Versand.
	 *
	 * @var BSKudo_Send
	 */
	private $send;

	/**
	 * Token-Webansicht.
	 *
	 * @var BSKudo_Card_View
	 */
	private $card_view;

	/**
	 * Meta-Boxen für Kudo-Karten.
	 *
	 * @var BSKudo_Card_Meta|null
	 */
	private $card_meta;

	/**
	 * Meta-Boxen für Textbausteine.
	 *
	 * @var BSKudo_Textbaustein_Meta|null
	 */
	private $textbaustein_meta;

	/**
	 * Konstruktor: Abhängigkeiten laden und Hooks definieren.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Klassendateien einbinden.
	 */
	private function load_dependencies() {
		require_once BSKUDO_PATH . 'includes/class-bskudo-cpt.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-debug.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-settings.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-security.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-token.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-mailer.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-qr.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-scheduler.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-send.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-card-view.php';
		require_once BSKUDO_PATH . 'includes/class-bskudo-shortcode.php';

		$this->cpt       = new BSKudo_CPT();
		$this->shortcode = new BSKudo_Shortcode();
		$this->send      = new BSKudo_Send();
		$this->card_view = new BSKudo_Card_View();

		if ( is_admin() ) {
			require_once BSKUDO_PATH . 'admin/class-bskudo-admin.php';
			require_once BSKUDO_PATH . 'admin/class-bskudo-card-meta.php';
			require_once BSKUDO_PATH . 'admin/class-bskudo-textbaustein-meta.php';
			$this->admin             = new BSKudo_Admin();
			$this->card_meta         = new BSKudo_Card_Meta();
			$this->textbaustein_meta = new BSKudo_Textbaustein_Meta();
		}
	}

	/**
	 * WordPress-Hooks registrieren.
	 */
	private function define_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this->cpt, 'register_post_types' ) );
		add_action( 'init', array( $this->cpt, 'register_taxonomies' ) );
		add_action( 'init', array( $this->shortcode, 'register' ) );
		$this->send->register();
		$this->card_view->register();

		if ( $this->admin ) {
			add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
			add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		}

		if ( $this->card_meta ) {
			$this->card_meta->register();
		}

		if ( $this->textbaustein_meta ) {
			$this->textbaustein_meta->register();
		}
	}

	/**
	 * Textdomain laden (vor allen übersetzten Strings, ab init).
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'bs-kudo-karten',
			false,
			dirname( BSKUDO_BASENAME ) . '/languages'
		);
	}
}

/**
 * Plugin starten.
 */
function bskudo_run() {
	new BSKudo_Loader();
}
