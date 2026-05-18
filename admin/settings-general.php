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
	<h2><?php esc_html_e( 'Allgemein', 'bs-kudo-karten' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="bskudo_sender_name"><?php esc_html_e( 'Absender-Name (E-Mail)', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="text" id="bskudo_sender_name" name="bskudo_settings[general][sender_name]" value="<?php echo esc_attr( $general['sender_name'] ); ?>" class="regular-text">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_sender_email"><?php esc_html_e( 'Absender-E-Mail', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="email" id="bskudo_sender_email" name="bskudo_settings[general][sender_email]" value="<?php echo esc_attr( $general['sender_email'] ); ?>" class="regular-text">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_subject_template"><?php esc_html_e( 'Betreff-Vorlage', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="text" id="bskudo_subject_template" name="bskudo_settings[general][subject_template]" value="<?php echo esc_attr( $general['subject_template'] ); ?>" class="large-text">
				<p class="description"><?php esc_html_e( 'Platzhalter: {sender}, {recipient}, {card}', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Kopie an Absender', 'bs-kudo-karten' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="bskudo_settings[general][copy_to_sender]" value="1" <?php checked( ! empty( $general['copy_to_sender'] ) ); ?>>
					<?php esc_html_e( 'Absender erhält eine Kopie der Kudo-Karte', 'bs-kudo-karten' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'An mich selbst', 'bs-kudo-karten' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="bskudo_settings[general][enable_send_to_self]" value="1" <?php checked( ! empty( $general['enable_send_to_self'] ) ); ?>>
					<?php esc_html_e( 'Option „Karte an mich selbst senden“ im Wizard anzeigen', 'bs-kudo-karten' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Verzögerter Versand', 'bs-kudo-karten' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="bskudo_settings[general][enable_delayed_send]" value="1" <?php checked( ! empty( $general['enable_delayed_send'] ) ); ?>>
					<?php esc_html_e( 'Versand zu einem späteren Zeitpunkt erlauben', 'bs-kudo-karten' ); ?>
				</label>
				<p class="description">
					<label for="bskudo_schedule_max_days"><?php esc_html_e( 'Maximaler Vorlauf (Tage):', 'bs-kudo-karten' ); ?></label>
					<input type="number" id="bskudo_schedule_max_days" name="bskudo_settings[general][schedule_max_days]" value="<?php echo esc_attr( (string) $general['schedule_max_days'] ); ?>" min="1" max="90" class="small-text">
				</p>
				<p class="description"><?php esc_html_e( 'Geplante Aufträge werden nur bis zum Versandzeitpunkt zwischengespeichert (keine dauerhafte Speicherung).', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'QR-Code in E-Mails', 'bs-kudo-karten' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="bskudo_settings[general][show_qr_in_mail]" value="1" <?php checked( ! empty( $general['show_qr_in_mail'] ) ); ?>>
					<?php esc_html_e( 'QR-Code zur Webansicht in der E-Mail anzeigen', 'bs-kudo-karten' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
