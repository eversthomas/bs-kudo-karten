<?php
/**
 * Admin-Tab: Sicherheit.
 *
 * @package BSKudo
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = BSKudo_Settings::get_all();
$security = $settings['security'];
?>
<form method="post" action="options.php">
	<?php settings_fields( BSKudo_Admin::OPTION_GROUP ); ?>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Limits & Gültigkeit', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_rate_limit"><?php esc_html_e( 'Rate Limit (Stunde)', 'bs-kudo-karten' ); ?></label>
					<input type="number" id="bskudo_rate_limit" name="bskudo_settings[security][rate_limit]" value="<?php echo esc_attr( (string) $security['rate_limit'] ); ?>" min="1" max="100" class="input sm">
					<p class="fhint"><?php esc_html_e( 'Maximale Versendungen pro IP und Stunde.', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_rate_limit_day"><?php esc_html_e( 'Rate Limit (Tag)', 'bs-kudo-karten' ); ?></label>
					<input type="number" id="bskudo_rate_limit_day" name="bskudo_settings[security][rate_limit_day]" value="<?php echo esc_attr( (string) ( $security['rate_limit_day'] ?? 5 ) ); ?>" min="1" max="500" class="input sm">
					<p class="fhint"><?php esc_html_e( 'Zusätzliche Obergrenze pro IP und Kalendertag (Transient).', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[security][behind_cloudflare]" value="1" <?php checked( ! empty( $security['behind_cloudflare'] ) ); ?>>
						<span><?php esc_html_e( 'Website läuft hinter Cloudflare (Rate-Limit nutzt CF-Connecting-IP)', 'bs-kudo-karten' ); ?></span>
					</label>
					<p class="fhint"><?php esc_html_e( 'Nur aktivieren, wenn der Traffic tatsächlich über Cloudflare läuft – sonst kann der Header gefälscht werden.', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_char_limit"><?php esc_html_e( 'Zeichenlimit', 'bs-kudo-karten' ); ?></label>
					<input type="number" id="bskudo_char_limit" name="bskudo_settings[security][char_limit]" value="<?php echo esc_attr( (string) $security['char_limit'] ); ?>" min="1" max="500" class="input sm">
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_token_ttl"><?php esc_html_e( 'Webansicht gültig (Tage)', 'bs-kudo-karten' ); ?></label>
					<input type="number" id="bskudo_token_ttl" name="bskudo_settings[security][token_ttl_days]" value="<?php echo esc_attr( (string) $security['token_ttl_days'] ); ?>" min="1" max="365" class="input sm">
					<p class="fhint"><?php esc_html_e( 'Wie lange der Link „Karte im Browser ansehen“ in der E-Mail funktioniert (Transient, ohne personenbezogene Daten).', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Absender-Bestätigung', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[security][require_sender_confirmation]" value="1" <?php checked( ! empty( $security['require_sender_confirmation'] ) ); ?>>
						<span><?php esc_html_e( 'Versand erst nach Bestätigung per Link an die Absender-E-Mail', 'bs-kudo-karten' ); ?></span>
					</label>
					<p class="fhint"><?php esc_html_e( 'Schützt vor falscher Absender-Identität. Deaktivieren nur für vertrauenswürdige/interne Installationen.', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Cloudflare Turnstile', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_turnstile_site_key"><?php esc_html_e( 'Site-Key', 'bs-kudo-karten' ); ?></label>
					<input type="text" id="bskudo_turnstile_site_key" name="bskudo_settings[security][turnstile_site_key]" value="<?php echo esc_attr( (string) ( $security['turnstile_site_key'] ?? '' ) ); ?>" class="input" autocomplete="off">
				</div>
				<div class="field">
					<label class="flabel" for="bskudo_turnstile_secret_key"><?php esc_html_e( 'Secret-Key', 'bs-kudo-karten' ); ?></label>
					<input type="password" id="bskudo_turnstile_secret_key" name="bskudo_settings[security][turnstile_secret_key]" value="<?php echo esc_attr( (string) ( $security['turnstile_secret_key'] ?? '' ) ); ?>" class="input" autocomplete="new-password">
					<p class="fhint"><?php esc_html_e( 'Optional. Widget erscheint im Wizard nur mit Site-Key; serverseitige Prüfung nur mit Secret-Key. Beide leer = Turnstile aus (wie bisher).', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Datenschutz', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_privacy_text"><?php esc_html_e( 'Datenschutzhinweis', 'bs-kudo-karten' ); ?></label>
					<textarea id="bskudo_privacy_text" name="bskudo_settings[security][privacy_text]" rows="4" class="textarea"><?php echo esc_textarea( $security['privacy_text'] ); ?></textarea>
					<p class="fhint"><?php esc_html_e( 'Wird im Wizard bei Schritt 3 angezeigt. Bereits gespeicherte Installationen behalten ihren bisherigen Text – bitte bei Bedarf manuell anpassen.', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<?php BSKudo_Debug::render_admin_log_view(); ?>

	<div class="form-actions">
		<button type="submit" class="btn primary"><?php esc_html_e( 'Einstellungen speichern', 'bs-kudo-karten' ); ?></button>
	</div>
</form>
