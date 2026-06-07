<?php
/**
 * Admin-Tab: Sicherheit.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = BSKudo_Settings::get_all();
$security = $settings['security'];
?>
<form method="post" action="options.php">
	<?php settings_fields( BSKudo_Admin::OPTION_GROUP ); ?>
	<h2><?php esc_html_e( 'Sicherheit', 'bs-kudo-karten' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="bskudo_rate_limit"><?php esc_html_e( 'Rate Limit', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="number" id="bskudo_rate_limit" name="bskudo_settings[security][rate_limit]" value="<?php echo esc_attr( (string) $security['rate_limit'] ); ?>" min="1" max="100" class="small-text">
				<p class="description"><?php esc_html_e( 'Maximale Versendungen pro IP und Stunde.', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_char_limit"><?php esc_html_e( 'Zeichenlimit', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="number" id="bskudo_char_limit" name="bskudo_settings[security][char_limit]" value="<?php echo esc_attr( (string) $security['char_limit'] ); ?>" min="1" max="500" class="small-text">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_token_ttl"><?php esc_html_e( 'Webansicht gültig (Tage)', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="number" id="bskudo_token_ttl" name="bskudo_settings[security][token_ttl_days]" value="<?php echo esc_attr( (string) $security['token_ttl_days'] ); ?>" min="1" max="365" class="small-text">
				<p class="description"><?php esc_html_e( 'Wie lange der Link „Karte im Browser ansehen“ in der E-Mail funktioniert (Transient, ohne personenbezogene Daten).', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_privacy_text"><?php esc_html_e( 'Datenschutzhinweis', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<textarea id="bskudo_privacy_text" name="bskudo_settings[security][privacy_text]" rows="4" class="large-text"><?php echo esc_textarea( $security['privacy_text'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Wird im Wizard bei Schritt 3 angezeigt. Bereits gespeicherte Installationen behalten ihren bisherigen Text – bitte bei Bedarf manuell anpassen.', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
