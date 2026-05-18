<?php
/**
 * Admin-Tab: Branding.
 *
 * @package BSKudo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = BSKudo_Settings::get_all();
$branding = $settings['branding'];
$logo_id  = (int) $branding['logo_id'];
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
?>
<form method="post" action="options.php">
	<?php settings_fields( BSKudo_Admin::OPTION_GROUP ); ?>
	<h2><?php esc_html_e( 'Branding', 'bs-kudo-karten' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Logo, Farben und Texte für E-Mail und Karten-Rückseite. Karten werden in E-Mails als fertige JPGs (Vorder- und Rückseite) versendet.', 'bs-kudo-karten' ); ?></p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Logo', 'bs-kudo-karten' ); ?></th>
			<td>
				<input type="hidden" id="bskudo_logo_id" name="bskudo_settings[branding][logo_id]" value="<?php echo esc_attr( (string) $logo_id ); ?>">
				<div id="bskudo-logo-preview" style="margin-bottom:10px;">
					<?php if ( $logo_url ) : ?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:200px;height:auto;">
					<?php endif; ?>
				</div>
				<button type="button" class="button" id="bskudo-logo-select"><?php esc_html_e( 'Logo wählen', 'bs-kudo-karten' ); ?></button>
				<button type="button" class="button" id="bskudo-logo-remove" <?php echo $logo_id ? '' : 'style="display:none;"'; ?>>
					<?php esc_html_e( 'Entfernen', 'bs-kudo-karten' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Erscheint in E-Mails (Kopf) und auf der Karten-Rückseite.', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_primary_color"><?php esc_html_e( 'Primärfarbe', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<input type="color" id="bskudo_primary_color" name="bskudo_settings[branding][primary_color]" value="<?php echo esc_attr( (string) $branding['primary_color'] ); ?>">
				<p class="description"><?php esc_html_e( 'Buttons in E-Mails und Hintergrund der Rückseite (wenn die Karte keine eigene Akzentfarbe hat).', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_branding_text"><?php esc_html_e( 'Standard Rückseiten-Branding', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<textarea id="bskudo_branding_text" name="bskudo_settings[branding][branding_text]" rows="3" class="large-text"><?php echo esc_textarea( $branding['branding_text'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Kann pro Karte im Editor überschrieben werden.', 'bs-kudo-karten' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bskudo_mail_footer_text"><?php esc_html_e( 'Zusatz im E-Mail-Footer', 'bs-kudo-karten' ); ?></label></th>
			<td>
				<textarea id="bskudo_mail_footer_text" name="bskudo_settings[branding][mail_footer_text]" rows="2" class="large-text"><?php echo esc_textarea( $branding['mail_footer_text'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Footer in E-Mails', 'bs-kudo-karten' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="bskudo_settings[branding][footer_powered]" value="1" <?php checked( ! empty( $branding['footer_powered'] ) ); ?>>
					<?php esc_html_e( '„Powered by BS Kudo Karten · bezugssysteme.de“ anzeigen', 'bs-kudo-karten' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>
