<?php
/**
 * Automatische Plugin-Updates über GitHub (release.json).
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * release.json Format – im Repo-Root auf GitHub ablegen:
 *
 * {
 *   "version": "0.1.0",
 *   "download_url": "https://github.com/USER/REPO/archive/refs/heads/main.zip",
 *   "details_url": "https://github.com/USER/REPO"
 * }
 */
class BSKudo_Updater {

	/**
	 * Cache-Dauer (12 Stunden).
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Plugin-Basename (z. B. bs-kudo-karten/bs-kudo-karten.php).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Absoluter Pfad zur Hauptdatei des Plugins.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * GitHub-Benutzername.
	 *
	 * @var string
	 */
	private $github_user;

	/**
	 * GitHub-Repository-Name.
	 *
	 * @var string
	 */
	private $github_repo;

	/**
	 * URL zur release.json auf raw.githubusercontent.com.
	 *
	 * @var string
	 */
	private $github_url;

	/**
	 * Aktuell installierte Plugin-Version.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * @param string $plugin_file      Pfad zur Hauptdatei (__FILE__).
	 * @param string $github_user      GitHub-Username.
	 * @param string $github_repo      Repository-Name.
	 * @param string $current_version  Installierte Version.
	 */
	public function __construct( $plugin_file, $github_user, $github_repo, $current_version ) {
		$this->plugin_file      = $plugin_file;
		$this->plugin_slug      = plugin_basename( $plugin_file );
		$this->github_user      = sanitize_text_field( $github_user );
		$this->github_repo      = sanitize_text_field( $github_repo );
		$this->current_version  = (string) $current_version;
		$this->github_url       = sprintf(
			'https://raw.githubusercontent.com/%s/%s/main/release.json',
			$this->github_user,
			$this->github_repo
		);
	}

	/**
	 * Update-Hooks registrieren.
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'on_upgrader_complete' ), 10, 2 );
	}

	/**
	 * Versionsinformationen von GitHub laden (mit Transient-Cache).
	 *
	 * @return array{version: string, download_url: string, details_url: string}|false
	 */
	private function get_remote_version() {
		$cached = get_transient( BSKUDO_UPDATE_CACHE_KEY );

		if ( is_array( $cached ) && isset( $cached['version'] ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			$this->github_url,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( '' === $body ) {
			return false;
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			return false;
		}

		$release = array(
			'version'       => sanitize_text_field( (string) $data['version'] ),
			'download_url'  => isset( $data['download_url'] ) ? esc_url_raw( (string) $data['download_url'] ) : '',
			'details_url'   => isset( $data['details_url'] ) ? esc_url_raw( (string) $data['details_url'] ) : '',
		);

		set_transient( BSKUDO_UPDATE_CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Prüfen, ob eine neuere Version verfügbar ist.
	 *
	 * @param object|false $transient Update-Transient von WordPress.
	 * @return object|false
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$remote = $this->get_remote_version();

		if ( false === $remote || '' === $remote['version'] ) {
			return $transient;
		}

		if ( version_compare( $this->current_version, $remote['version'], '<' ) ) {
			$plugin_folder = dirname( $this->plugin_slug );

			$transient->response[ $this->plugin_slug ] = (object) array(
				'slug'        => $plugin_folder,
				'plugin'      => $this->plugin_slug,
				'new_version' => $remote['version'],
				'url'         => $remote['details_url'],
				'package'     => $remote['download_url'],
			);
		}

		return $transient;
	}

	/**
	 * Plugin-Details für den „Details anzeigen“-Dialog im Backend.
	 *
	 * @param false|object|array $result  Bisheriges Ergebnis.
	 * @param string             $action  plugins_api-Aktion.
	 * @param object             $args    Anfrage-Argumente.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! is_object( $args ) || empty( $args->slug ) ) {
			return $result;
		}

		$plugin_folder = dirname( $this->plugin_slug );

		if ( $args->slug !== $plugin_folder ) {
			return $result;
		}

		$remote = $this->get_remote_version();

		if ( false === $remote ) {
			return $result;
		}

		$info               = new stdClass();
		$info->name         = 'BS Kudo Karten';
		$info->slug         = $plugin_folder;
		$info->version      = $remote['version'];
		$info->author       = '<a href="https://bezugssysteme.de">Tom Evers – bezugssysteme.de</a>';
		$info->homepage     = $remote['details_url'];
		$info->download_link = $remote['download_url'];
		$info->requires     = '6.0';
		$info->tested       = get_bloginfo( 'version' );
		$info->sections     = array(
			'description' => __(
				'Digitale Kudo-Karten – Wizard, E-Mail-Versand und Token-Webansicht.',
				'bs-kudo-karten'
			),
		);

		return $info;
	}

	/**
	 * Cache nach Plugin-Update leeren.
	 */
	private function clear_cache() {
		delete_transient( BSKUDO_UPDATE_CACHE_KEY );
	}

	/**
	 * Cache leeren, wenn dieses Plugin aktualisiert wurde.
	 *
	 * @param WP_Upgrader $upgrader Upgrader-Instanz.
	 * @param array       $options  Upgrade-Optionen.
	 */
	public function on_upgrader_complete( $upgrader, $options ) {
		if ( ! is_array( $options ) || empty( $options['action'] ) || 'update' !== $options['action'] ) {
			return;
		}

		if ( empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
			return;
		}

		if ( empty( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		if ( in_array( $this->plugin_slug, $options['plugins'], true ) ) {
			$this->clear_cache();
		}
	}
}
