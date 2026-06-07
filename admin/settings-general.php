<?php
/**
 * Admin-Tab: Allgemein.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = BSKudo_Settings::get_all();
$general  = $settings['general'];
?>
<form method="post" action="options.php">
	<?php settings_fields( BSKudo_Admin::OPTION_GROUP ); ?>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'E-Mail & Versand', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_sender_name"><?php esc_html_e( 'Absender-Name (E-Mail)', 'bs-kudo-karten' ); ?></label>
					<input type="text" id="bskudo_sender_name" name="bskudo_settings[general][sender_name]" value="<?php echo esc_attr( $general['sender_name'] ); ?>" class="input">
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_sender_email"><?php esc_html_e( 'Absender-E-Mail', 'bs-kudo-karten' ); ?></label>
					<input type="email" id="bskudo_sender_email" name="bskudo_settings[general][sender_email]" value="<?php echo esc_attr( $general['sender_email'] ); ?>" class="input">
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_subject_template"><?php esc_html_e( 'Betreff-Vorlage', 'bs-kudo-karten' ); ?></label>
					<input type="text" id="bskudo_subject_template" name="bskudo_settings[general][subject_template]" value="<?php echo esc_attr( $general['subject_template'] ); ?>" class="input">
					<p class="fhint"><?php esc_html_e( 'Platzhalter: {sender}, {recipient}, {card}', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Wizard-Optionen', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[general][copy_to_sender]" value="1" <?php checked( ! empty( $general['copy_to_sender'] ) ); ?>>
						<span><?php esc_html_e( 'Absender erhält eine Kopie der Kudo-Karte', 'bs-kudo-karten' ); ?></span>
					</label>
				</div>

				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[general][enable_send_to_self]" value="1" <?php checked( ! empty( $general['enable_send_to_self'] ) ); ?>>
						<span><?php esc_html_e( 'Option „Karte an mich selbst senden“ im Wizard anzeigen', 'bs-kudo-karten' ); ?></span>
					</label>
				</div>

				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[general][enable_delayed_send]" value="1" <?php checked( ! empty( $general['enable_delayed_send'] ) ); ?>>
						<span><?php esc_html_e( 'Versand zu einem späteren Zeitpunkt erlauben', 'bs-kudo-karten' ); ?></span>
					</label>
					<div class="field-row">
						<label class="flabel" for="bskudo_schedule_max_days"><?php esc_html_e( 'Maximaler Vorlauf (Tage):', 'bs-kudo-karten' ); ?></label>
						<input type="number" id="bskudo_schedule_max_days" name="bskudo_settings[general][schedule_max_days]" value="<?php echo esc_attr( (string) $general['schedule_max_days'] ); ?>" min="1" max="90" class="input sm">
					</div>
					<p class="fhint"><?php esc_html_e( 'Geplante Aufträge werden nur bis zum Versandzeitpunkt zwischengespeichert (keine dauerhafte Speicherung). Für zuverlässigen verzögerten Versand sollte ein echter System-Cron eingerichtet werden (WP-Cron alle 5–15 Minuten auslösen), da WordPress-Cron sonst erst bei Seitenaufrufen startet.', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[general][show_qr_in_mail]" value="1" <?php checked( ! empty( $general['show_qr_in_mail'] ) ); ?>>
						<span><?php esc_html_e( 'QR-Code zur Webansicht in der E-Mail anzeigen', 'bs-kudo-karten' ); ?></span>
					</label>
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions">
		<button type="submit" class="btn primary"><?php esc_html_e( 'Einstellungen speichern', 'bs-kudo-karten' ); ?></button>
	</div>
</form>
