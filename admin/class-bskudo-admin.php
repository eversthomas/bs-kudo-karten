<?php
/**
 * Admin-Oberfläche mit Tab-Navigation für BS Kudo Karten.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Einstellungsseite „Kudo Karten“ mit drei Tab-Platzhaltern.
 */
class BSKudo_Admin {

	/**
	 * Slug der Einstellungsseite.
	 */
	const PAGE_SLUG = 'bskudo-settings';

	/**
	 * Optionen-Gruppe (für spätere Phasen vorbereitet).
	 */
	const OPTION_GROUP = 'bskudo_options';

	/**
	 * Tab-Labels (erst bei Bedarf übersetzen, nicht im Konstruktor).
	 *
	 * @return array<string, string>
	 */
	private function get_tabs() {
		return array(
			'general'  => __( 'Allgemein', 'bs-kudo-karten' ),
			'branding' => __( 'Branding', 'bs-kudo-karten' ),
			'security' => __( 'Sicherheit', 'bs-kudo-karten' ),
		);
	}

	/**
	 * Admin-Menüpunkt registrieren.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Kudo Karten', 'bs-kudo-karten' ),
			__( 'Kudo Karten', 'bs-kudo-karten' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-email-alt',
			27
		);
	}

	/**
	 * Einstellungen registrieren (Platzhalter für spätere Phasen).
	 */
	public function register_settings() {
		register_setting( self::OPTION_GROUP, 'bskudo_settings' );
	}

	/**
	 * Aktiven Tab aus der URL ermitteln.
	 *
	 * @return string Tab-Schlüssel.
	 */
	private function get_active_tab() {
		$tabs = $this->get_tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

		if ( ! array_key_exists( $tab, $tabs ) ) {
			$tab = 'general';
		}

		return $tab;
	}

	/**
	 * Einstellungsseite ausgeben.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();
		$tabs       = $this->get_tabs();
		$page_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Einstellungs-Tabs', 'bs-kudo-karten' ); ?>">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $page_url ) ); ?>"
						class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="bskudo-admin-tab-content" style="margin-top: 1.5em;">
				<?php $this->render_tab_content( $active_tab ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Inhalt des aktiven Tabs ausgeben (Platzhalter).
	 *
	 * @param string $tab Tab-Schlüssel.
	 */
	private function render_tab_content( $tab ) {
		switch ( $tab ) {
			case 'branding':
				$this->render_tab_branding();
				break;
			case 'security':
				$this->render_tab_security();
				break;
			case 'general':
			default:
				$this->render_tab_general();
				break;
		}
	}

	/**
	 * Tab: Allgemein (Platzhalter).
	 */
	private function render_tab_general() {
		?>
		<h2><?php esc_html_e( 'Allgemein', 'bs-kudo-karten' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Einstellungen für Absender, Betreff und Kopie folgen in einer späteren Phase.', 'bs-kudo-karten' ); ?>
		</p>
		<?php
	}

	/**
	 * Tab: Branding (Platzhalter).
	 */
	private function render_tab_branding() {
		?>
		<h2><?php esc_html_e( 'Branding', 'bs-kudo-karten' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Einstellungen für Logo, Primärfarbe und Mail-Footer folgen in einer späteren Phase.', 'bs-kudo-karten' ); ?>
		</p>
		<?php
	}

	/**
	 * Tab: Sicherheit (Platzhalter).
	 */
	private function render_tab_security() {
		?>
		<h2><?php esc_html_e( 'Sicherheit', 'bs-kudo-karten' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Einstellungen für Rate Limit, Zeichenlimit und Datenschutzhinweis folgen in einer späteren Phase.', 'bs-kudo-karten' ); ?>
		</p>
		<?php
	}
}
