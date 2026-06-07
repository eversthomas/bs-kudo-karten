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
	 * Kurzbeschreibungen pro Tab.
	 *
	 * @return array<string, string>
	 */
	private function get_tab_ledes() {
		return array(
			'general'  => __( 'E-Mail-Absender, Versandoptionen und Wizard-Einstellungen.', 'bs-kudo-karten' ),
			'branding' => __( 'Logo, Farben und Texte für E-Mail und Karten-Rückseite.', 'bs-kudo-karten' ),
			'security' => __( 'Rate Limits, Zeichenbegrenzung und Datenschutzhinweis.', 'bs-kudo-karten' ),
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
		$tab_ledes  = $this->get_tab_ledes();
		$page_url   = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$tab_lede   = $tab_ledes[ $active_tab ] ?? '';
		?>
		<div class="wrap bskudo-wrap">
			<div class="bskudo-app-shell">
				<div class="bskudo-app" data-accent="blue" data-density="regular">
					<main class="main">
						<?php BSKudo_Admin_UI::render_topbar(); ?>
						<div class="content">
							<div class="page">
							<div class="page-head">
								<div>
									<h1><?php esc_html_e( 'Einstellungen', 'bs-kudo-karten' ); ?></h1>
									<?php if ( '' !== $tab_lede ) : ?>
										<p class="lede"><?php echo esc_html( $tab_lede ); ?></p>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
								<div class="alert ok" role="status">
									<p class="alert-title"><?php esc_html_e( 'Gespeichert', 'bs-kudo-karten' ); ?></p>
									<p><?php esc_html_e( 'Einstellungen wurden erfolgreich gespeichert.', 'bs-kudo-karten' ); ?></p>
								</div>
							<?php endif; ?>

							<?php $this->maybe_render_mail_debug_panel(); ?>

							<div class="settings-layout">
								<nav class="settings-tabs" aria-label="<?php esc_attr_e( 'Einstellungs-Tabs', 'bs-kudo-karten' ); ?>">
									<?php foreach ( $tabs as $tab_key => $label ) : ?>
										<a
											href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $page_url ) ); ?>"
											class="stab<?php echo $active_tab === $tab_key ? ' active' : ''; ?>"
										>
											<?php echo esc_html( $label ); ?>
										</a>
									<?php endforeach; ?>
								</nav>

								<div class="settings-panel">
									<?php $this->render_tab_content( $active_tab ); ?>
								</div>
							</div>

							<?php BSKudo_Admin_UI::render_branding_footer(); ?>
							</div>
						</div>
					</main>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hinweis zum Frontend-Shortcode.
	 */
	public static function render_shortcode_panel() {
		?>
		<div class="alert info">
			<p class="alert-title"><?php esc_html_e( 'Kudo-Karten im Frontend anzeigen', 'bs-kudo-karten' ); ?></p>
			<p><?php esc_html_e( 'Füge auf einer Seite oder in einem Beitrag den Shortcode ein, damit Besucher Kudo-Karten versenden können:', 'bs-kudo-karten' ); ?></p>
			<p><code class="key">[<?php echo esc_html( BSKudo_Shortcode::TAG ); ?>]</code></p>
			<p><?php esc_html_e( 'Optional kannst du nur Karten aus einem bestimmten Kudo-Set anzeigen (Slug des Sets):', 'bs-kudo-karten' ); ?></p>
			<p><code class="key">[<?php echo esc_html( BSKudo_Shortcode::TAG ); ?> set="dein-set-slug"]</code></p>
			<p class="fhint"><?php esc_html_e( 'Im Block-Editor: Shortcode-Block hinzufügen und den Code einfügen. Bei Divi oder Page Buildern entsprechend ein Shortcode- oder Code-Modul verwenden.', 'bs-kudo-karten' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Mail-Debug-Panel (nur wenn Logging aktiv), eingeklappt per Accordion.
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
		<details class="accordion">
			<summary class="accordion-sum">
				<span class="accordion-sum-label"><?php esc_html_e( 'Mail-Debug & Protokoll', 'bs-kudo-karten' ); ?></span>
				<span class="badge warn dot"><?php esc_html_e( 'Aktiv', 'bs-kudo-karten' ); ?></span>
			</summary>
			<div class="accordion-body alert warn">
				<p><?php esc_html_e( 'Jeder Versand wird protokolliert. Auf lokalen Systemen kommt oft keine E-Mail im Postfach an – das Log zeigt, ob WordPress den Versand dennoch als erfolgreich meldet.', 'bs-kudo-karten' ); ?></p>
				<ul>
					<li><code class="key"><?php echo esc_html( $log_path ); ?></code></li>
					<li><code class="key"><?php echo esc_html( $html_path ); ?></code> <?php esc_html_e( '(zuletzt erzeugte HTML-Mail)', 'bs-kudo-karten' ); ?></li>
				</ul>
				<?php if ( ! empty( $env['local_note'] ) ) : ?>
					<p><em><?php echo esc_html( (string) $env['local_note'] ); ?></em></p>
				<?php endif; ?>
				<?php if ( '' !== $tail ) : ?>
					<p><strong><?php esc_html_e( 'Letzte Log-Einträge:', 'bs-kudo-karten' ); ?></strong></p>
					<pre class="log-pre"><?php echo esc_html( $tail ); ?></pre>
				<?php else : ?>
					<p><?php esc_html_e( 'Noch keine Einträge – bitte einmal eine Kudo-Karte im Frontend senden.', 'bs-kudo-karten' ); ?></p>
				<?php endif; ?>
			</div>
		</details>
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
