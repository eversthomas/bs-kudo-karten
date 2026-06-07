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
 * Einstellungsseite „Kudo Karten“.
 */
class BSKudo_Admin {

	const PAGE_SLUG = 'bskudo-settings';

	const OPTION_GROUP = 'bskudo_options';

	const PARENT_SLUG = 'edit.php?post_type=kudo_card';

	/**
	 * Tab-Labels.
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
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Einstellungen', 'bs-kudo-karten' ),
			__( 'Einstellungen', 'bs-kudo-karten' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Einstellungen registrieren.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			BSKudo_Settings::OPTION,
			array(
				'sanitize_callback' => array( 'BSKudo_Settings', 'sanitize' ),
			)
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Scripts für Einstellungsseite (z. B. Logo-Auswahl).
	 *
	 * @param string $hook Aktuelle Admin-Seite.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'kudo_card_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

		if ( 'branding' !== $tab ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'bskudo-admin-branding',
			BSKUDO_URL . 'admin/js/bskudo-admin-branding.js',
			array( 'jquery' ),
			BSKUDO_VERSION,
			true
		);
	}

	/**
	 * Aktiven Tab aus der URL ermitteln.
	 *
	 * @return string
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
			<p class="description">
				<?php esc_html_e( 'Entwickelt von Tom Evers – bezugssysteme.de · Karten: Marcus Rosik, Systemische Beratung', 'bs-kudo-karten' ); ?>
			</p>

			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Einstellungen gespeichert.', 'bs-kudo-karten' ); ?></p></div>
			<?php endif; ?>

			<?php $this->render_shortcode_panel(); ?>

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

			<?php $this->maybe_render_mail_debug_panel(); ?>

			<div class="bskudo-admin-tab-content" style="margin-top: 1.5em;">
				<?php $this->render_tab_content( $active_tab ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Hinweis zum Frontend-Shortcode.
	 */
	private function render_shortcode_panel() {
		?>
		<div class="notice notice-info" style="margin-top:1em;">
			<p><strong><?php esc_html_e( 'Kudo-Karten im Frontend anzeigen', 'bs-kudo-karten' ); ?></strong></p>
			<p><?php esc_html_e( 'Füge auf einer Seite oder in einem Beitrag den Shortcode ein, damit Besucher Kudo-Karten versenden können:', 'bs-kudo-karten' ); ?></p>
			<p>
				<code>[<?php echo esc_html( BSKudo_Shortcode::TAG ); ?>]</code>
			</p>
			<p><?php esc_html_e( 'Optional kannst du nur Karten aus einem bestimmten Kudo-Set anzeigen (Slug des Sets):', 'bs-kudo-karten' ); ?></p>
			<p>
				<code>[<?php echo esc_html( BSKudo_Shortcode::TAG ); ?> set="dein-set-slug"]</code>
			</p>
			<p class="description">
				<?php esc_html_e( 'Im Block-Editor: Shortcode-Block hinzufügen und den Code einfügen. Bei Divi oder Page Buildern entsprechend ein Shortcode- oder Code-Modul verwenden.', 'bs-kudo-karten' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Mail-Debug-Panel (nur wenn Logging aktiv).
	 */
	private function maybe_render_mail_debug_panel() {
		if ( ! class_exists( 'BSKudo_Debug', false ) || ! BSKudo_Debug::is_enabled() ) {
			return;
		}

		$log_path  = BSKudo_Debug::get_log_path();
		$html_path = BSKudo_Debug::get_html_path();
		$tail      = BSKudo_Debug::tail_log( 8 );
		$env       = BSKudo_Debug::get_environment_snapshot();
		?>
		<div class="notice notice-info" style="margin-top:1em;">
			<p><strong><?php esc_html_e( 'Mail-Debug aktiv', 'bs-kudo-karten' ); ?></strong></p>
			<p><?php esc_html_e( 'Jeder Versand wird protokolliert. Auf lokalen Systemen kommt oft keine E-Mail im Postfach an – das Log zeigt, ob WordPress den Versand dennoch als erfolgreich meldet.', 'bs-kudo-karten' ); ?></p>
			<ul style="list-style:disc;margin-left:1.5em;">
				<li><code><?php echo esc_html( $log_path ); ?></code></li>
				<li><code><?php echo esc_html( $html_path ); ?></code> <?php esc_html_e( '(zuletzt erzeugte HTML-Mail)', 'bs-kudo-karten' ); ?></li>
			</ul>
			<?php if ( ! empty( $env['local_note'] ) ) : ?>
				<p><em><?php echo esc_html( (string) $env['local_note'] ); ?></em></p>
			<?php endif; ?>
			<?php if ( '' !== $tail ) : ?>
				<p><strong><?php esc_html_e( 'Letzte Log-Einträge:', 'bs-kudo-karten' ); ?></strong></p>
				<pre style="background:#fff;padding:12px;overflow:auto;max-height:220px;font-size:12px;"><?php echo esc_html( $tail ); ?></pre>
			<?php else : ?>
				<p><?php esc_html_e( 'Noch keine Einträge – bitte einmal eine Kudo-Karte im Frontend senden.', 'bs-kudo-karten' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Tab-Inhalt einbinden.
	 *
	 * @param string $tab Tab-Schlüssel.
	 */
	private function render_tab_content( $tab ) {
		$file = BSKUDO_PATH . 'admin/settings-' . $tab . '.php';

		if ( file_exists( $file ) ) {
			include $file;
			return;
		}

		echo '<p>' . esc_html__( 'Tab nicht gefunden.', 'bs-kudo-karten' ) . '</p>';
	}
}
