<?php
/**
 * Gemeinsame Admin-UI: Assets, App-Shell.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Design-System für alle Plugin-Backend-Seiten.
 */
class BSKudo_Admin_UI {

	/**
	 * Ob die Shell für die aktuelle Seite bereits geöffnet wurde.
	 *
	 * @var bool
	 */
	private static $shell_open = false;

	/**
	 * Hooks registrieren.
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_styles' ) );
		add_action( 'admin_menu', array( $this, 'decorate_admin_menu' ), 999 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'in_admin_header', array( $this, 'maybe_open_shell' ), 1 );
		add_action( 'admin_footer', array( $this, 'maybe_close_shell' ), 999 );
	}

	/**
	 * Dashicon-Styling für WP-Admin-Menü (global im Backend).
	 */
	public function enqueue_menu_styles() {
		wp_enqueue_style(
			'bskudo-admin-menu',
			BSKUDO_URL . 'admin/assets/admin-menu.css',
			array(),
			BSKUDO_VERSION
		);
	}

	/**
	 * Untermenüpunkte mit Dashicons versehen.
	 */
	public function decorate_admin_menu() {
		global $submenu;

		$parent = 'edit.php?post_type=kudo_card';

		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			return;
		}

		foreach ( $submenu[ $parent ] as $key => $item ) {
			$icon = self::dashicon_for_menu_slug( $item[2] );

			if ( ! $icon || false !== strpos( $item[0], 'bskudo-menu-icon' ) ) {
				continue;
			}

			$submenu[ $parent ][ $key ][0] = self::format_menu_icon( $icon ) . $item[0];
		}
	}

	/**
	 * Dashicon-Klasse für einen Menü-Slug.
	 *
	 * @param string $slug Menü-Slug.
	 * @return string|null
	 */
	private static function dashicon_for_menu_slug( $slug ) {
		if ( 'edit.php?post_type=kudo_card' === $slug ) {
			return 'dashicons-grid-view';
		}

		if ( 'post-new.php?post_type=kudo_card' === $slug ) {
			return 'dashicons-plus-alt2';
		}

		if ( false !== strpos( $slug, 'kudo_textbaustein' ) ) {
			return false !== strpos( $slug, 'post-new' ) ? 'dashicons-plus-alt2' : 'dashicons-editor-alignleft';
		}

		if ( false !== strpos( $slug, 'kudo_set' ) ) {
			return 'dashicons-category';
		}

		if ( BSKudo_Admin::PAGE_SLUG === $slug ) {
			return 'dashicons-admin-generic';
		}

		return null;
	}

	/**
	 * @param string $dashicon Dashicon-Klasse (ohne Präfix dashicons-).
	 * @return string
	 */
	private static function format_menu_icon( $dashicon ) {
		return '<span class="bskudo-menu-icon dashicons ' . esc_attr( $dashicon ) . '" aria-hidden="true"></span> ';
	}

	/**
	 * Prüfen, ob die aktuelle Seite zum Plugin gehört.
	 *
	 * @return bool
	 */
	public static function is_plugin_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return false;
		}

		if ( 'kudo_card_page_' . BSKudo_Admin::PAGE_SLUG === $screen->id ) {
			return true;
		}

		if ( in_array( $screen->post_type, array( 'kudo_card', 'kudo_textbaustein' ), true ) ) {
			return true;
		}

		if ( 'kudo_set' === $screen->taxonomy ) {
			return true;
		}

		return in_array( $screen->id, array( 'edit-kudo_card', 'edit-kudo_textbaustein', 'edit-kudo_set' ), true );
	}

	/**
	 * Einstellungsseite (eigene Shell in render_page).
	 *
	 * @return bool
	 */
	public static function is_settings_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && 'kudo_card_page_' . BSKudo_Admin::PAGE_SLUG === $screen->id;
	}

	/**
	 * Body-Klasse für Admin-Bridge.
	 *
	 * @param string $classes Bestehende Klassen.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( self::is_plugin_screen() ) {
			$classes .= ' bskudo-admin-screen';
		}

		return $classes;
	}

	/**
	 * Styles und Scripts auf allen Plugin-Seiten laden.
	 *
	 * @param string $hook Admin-Hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! self::is_plugin_screen() ) {
			return;
		}

		wp_enqueue_style(
			'bskudo-admin-tokens',
			BSKUDO_URL . 'admin/assets/tokens.css',
			array(),
			BSKUDO_VERSION
		);

		wp_enqueue_style(
			'bskudo-admin-styles',
			BSKUDO_URL . 'admin/assets/styles.css',
			array( 'bskudo-admin-tokens' ),
			BSKUDO_VERSION
		);

		wp_enqueue_style(
			'bskudo-admin-bridge',
			BSKUDO_URL . 'admin/assets/admin-bridge.css',
			array( 'bskudo-admin-styles' ),
			BSKUDO_VERSION
		);

		if ( self::is_settings_screen() ) {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

			if ( 'branding' === $tab ) {
				wp_enqueue_media();
				wp_enqueue_script(
					'bskudo-admin-branding',
					BSKUDO_URL . 'admin/js/bskudo-admin-branding.js',
					array( 'jquery' ),
					BSKUDO_VERSION,
					true
				);
			}

			return;
		}

		wp_enqueue_script(
			'bskudo-admin-ui',
			BSKUDO_URL . 'admin/js/bskudo-admin-ui.js',
			array(),
			BSKUDO_VERSION,
			true
		);
	}

	/**
	 * App-Shell für native WP-Seiten öffnen.
	 */
	public function maybe_open_shell() {
		if ( ! self::is_plugin_screen() || self::is_settings_screen() || self::$shell_open ) {
			return;
		}

		self::$shell_open = true;

		echo '<div class="bskudo-app-shell">';
		echo '<div class="bskudo-app" data-accent="blue" data-density="regular">';
		echo '<main class="main">';
		self::render_topbar();
		echo '<div class="content"><div class="page bskudo-wp-host">';
	}

	/**
	 * App-Shell schließen.
	 */
	public function maybe_close_shell() {
		if ( ! self::$shell_open ) {
			return;
		}

		self::render_branding_footer();
		echo '</div></div></main></div></div>';
		self::$shell_open = false;
	}

	/**
	 * Topbar mit Breadcrumbs.
	 */
	public static function render_topbar() {
		$crumbs = self::get_breadcrumbs();
		?>
		<div class="topbar">
			<nav class="crumbs" aria-label="<?php esc_attr_e( 'Brotkrumen', 'bs-kudo-karten' ); ?>">
				<?php foreach ( $crumbs as $index => $crumb ) : ?>
					<?php if ( $index > 0 ) : ?>
						<span class="sep" aria-hidden="true">›</span>
					<?php endif; ?>
					<?php if ( ! empty( $crumb['url'] ) ) : ?>
						<a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
					<?php else : ?>
						<span class="here"><?php echo esc_html( $crumb['label'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
			<div class="spacer"></div>
		</div>
		<?php
	}

	/**
	 * Branding-Footer.
	 */
	public static function render_branding_footer() {
		?>
		<footer class="bskudo-branding-footer">
			<span>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: link to bezugssysteme.de */
						__( 'Tom Evers · %s · Karten: Marcus Rosik, Systemische Beratung', 'bs-kudo-karten' ),
						'<a href="https://bezugssysteme.de" target="_blank" rel="noopener noreferrer">bezugssysteme.de</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				);
				?>
			</span>
			<span>v<?php echo esc_html( BSKUDO_VERSION ); ?></span>
		</footer>
		<?php
	}

	/**
	 * Breadcrumbs für die aktuelle Seite.
	 *
	 * @return array<int, array{label: string, url?: string}>
	 */
	public static function get_breadcrumbs() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$root   = array(
			array(
				'label' => __( 'Kudo Karten', 'bs-kudo-karten' ),
				'url'   => admin_url( 'edit.php?post_type=kudo_card' ),
			),
		);

		if ( ! $screen ) {
			return $root;
		}

		if ( self::is_settings_screen() ) {
			$tabs    = array(
				'general'  => __( 'Allgemein', 'bs-kudo-karten' ),
				'branding' => __( 'Branding', 'bs-kudo-karten' ),
				'security' => __( 'Sicherheit', 'bs-kudo-karten' ),
			);
			$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
			$tab_lbl = $tabs[ $tab ] ?? __( 'Einstellungen', 'bs-kudo-karten' );

			return array_merge(
				$root,
				array(
					array(
						'label' => __( 'Einstellungen', 'bs-kudo-karten' ),
						'url'   => admin_url( 'admin.php?page=' . BSKudo_Admin::PAGE_SLUG ),
					),
					array( 'label' => $tab_lbl ),
				)
			);
		}

		if ( 'edit-kudo_card' === $screen->id ) {
			return array_merge( $root, array( array( 'label' => __( 'Alle Karten', 'bs-kudo-karten' ) ) ) );
		}

		if ( 'kudo_card' === $screen->id ) {
			return array_merge(
				$root,
				array(
					array(
						'label' => __( 'Alle Karten', 'bs-kudo-karten' ),
						'url'   => admin_url( 'edit.php?post_type=kudo_card' ),
					),
					array(
						'label' => self::is_new_post_screen()
							? __( 'Neue Karte', 'bs-kudo-karten' )
							: __( 'Karte bearbeiten', 'bs-kudo-karten' ),
					),
				)
			);
		}

		if ( 'edit-kudo_textbaustein' === $screen->id ) {
			return array_merge( $root, array( array( 'label' => __( 'Textbausteine', 'bs-kudo-karten' ) ) ) );
		}

		if ( 'kudo_textbaustein' === $screen->id ) {
			return array_merge(
				$root,
				array(
					array(
						'label' => __( 'Textbausteine', 'bs-kudo-karten' ),
						'url'   => admin_url( 'edit.php?post_type=kudo_textbaustein' ),
					),
					array(
						'label' => self::is_new_post_screen()
							? __( 'Neuer Textbaustein', 'bs-kudo-karten' )
							: __( 'Textbaustein bearbeiten', 'bs-kudo-karten' ),
					),
				)
			);
		}

		if ( 'edit-kudo_set' === $screen->id || 'kudo_set' === $screen->id ) {
			return array_merge( $root, array( array( 'label' => __( 'Kudo-Sets', 'bs-kudo-karten' ) ) ) );
		}

		return $root;
	}

	/**
	 * @return bool
	 */
	private static function is_new_post_screen() {
		global $pagenow;

		return 'post-new.php' === $pagenow;
	}
}
