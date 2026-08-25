<?php
/**
 * Admin-Tab: Branding.
 *
 * @package BSKudo
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

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

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Logo & Farbe', 'bs-kudo-karten' ); ?></h2>
			<p><?php esc_html_e( 'Logo, Farben und Texte für E-Mail und Karten-Rückseite. Karten werden in E-Mails als fertige JPGs (Vorder- und Rückseite) versendet.', 'bs-kudo-karten' ); ?></p>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<span class="flabel"><?php esc_html_e( 'Logo', 'bs-kudo-karten' ); ?></span>
					<input type="hidden" id="bskudo_logo_id" name="bskudo_settings[branding][logo_id]" value="<?php echo esc_attr( (string) $logo_id ); ?>">
					<div
						id="bskudo-logo-preview"
						class="logo-preview"
						data-empty-label="<?php esc_attr_e( 'Noch kein Logo ausgewählt', 'bs-kudo-karten' ); ?>"
					>
						<?php if ( $logo_url ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
						<?php endif; ?>
					</div>
					<div class="btn-row">
						<button type="button" class="btn ghost sm" id="bskudo-logo-select"><?php esc_html_e( 'Logo wählen', 'bs-kudo-karten' ); ?></button>
						<button type="button" class="btn ghost sm" id="bskudo-logo-remove" <?php echo $logo_id ? '' : 'hidden'; ?>>
							<?php esc_html_e( 'Entfernen', 'bs-kudo-karten' ); ?>
						</button>
					</div>
					<p class="fhint"><?php esc_html_e( 'Erscheint in E-Mails (Kopf) und auf der Karten-Rückseite.', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_primary_color"><?php esc_html_e( 'Primärfarbe', 'bs-kudo-karten' ); ?></label>
					<input type="color" id="bskudo_primary_color" name="bskudo_settings[branding][primary_color]" value="<?php echo esc_attr( (string) $branding['primary_color'] ); ?>" class="input color">
					<p class="fhint"><?php esc_html_e( 'Buttons in E-Mails und Hintergrund der Rückseite (wenn die Karte keine eigene Akzentfarbe hat).', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Rückseiten-Texte', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_branding_text_col1"><?php esc_html_e( 'Standard Rückseite: Spalte 1 (links – neben QR-Code)', 'bs-kudo-karten' ); ?></label>
					<?php
					$editor_settings = array(
						'textarea_name' => 'bskudo_settings[branding][branding_text_col1]',
						'media_buttons' => false,
						'textarea_rows' => 4,
						'teeny'         => true,
						'quicktags'     => true,
					);
					wp_editor( $branding['branding_text_col1'] ? $branding['branding_text_col1'] : '', 'bskudo_branding_text_col1', $editor_settings );
					?>
					<p class="fhint"><?php esc_html_e( 'Optionaler Begleittext für die linke Spalte (neben dem QR-Code). Leer = nur QR-Code.', 'bs-kudo-karten' ); ?></p>
				</div>

				<div class="field">
					<label class="flabel" for="bskudo_branding_text_col2"><?php esc_html_e( 'Standard Rückseite: Spalte 2 (rechts – Branding)', 'bs-kudo-karten' ); ?></label>
					<?php
					$editor_settings = array(
						'textarea_name' => 'bskudo_settings[branding][branding_text_col2]',
						'media_buttons' => false,
						'textarea_rows' => 4,
						'teeny'         => true,
						'quicktags'     => true,
					);
					$col2_val = $branding['branding_text_col2'] ? $branding['branding_text_col2'] : $branding['branding_text'];
					wp_editor( $col2_val, 'bskudo_branding_text_col2', $editor_settings );
					?>
					<p class="fhint"><?php esc_html_e( 'Das Haupt-Branding in der rechten Spalte. Kann pro Karte überschrieben werden.', 'bs-kudo-karten' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'Globales Branding', 'bs-kudo-karten' ); ?></h2>
			<p><?php esc_html_e( 'Erscheint oben in der Benachrichtigungs-E-Mail und unten auf der Online-Kudo-Karte.', 'bs-kudo-karten' ); ?></p>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_global_branding_text"><?php esc_html_e( 'Branding-Text', 'bs-kudo-karten' ); ?></label>
					<textarea id="bskudo_global_branding_text" name="bskudo_settings[branding][global_branding_text]" rows="3" class="textarea"><?php echo esc_textarea( $branding['global_branding_text'] ?? '' ); ?></textarea>
					<p class="fhint">
						<?php esc_html_e( 'Benannter Link:', 'bs-kudo-karten' ); ?>
						<code>[<?php esc_html_e( 'Linktext', 'bs-kudo-karten' ); ?>](https://beispiel.de)</code>.
						<?php esc_html_e( 'Internetadressen ohne Klammern werden automatisch verlinkt.', 'bs-kudo-karten' ); ?>
					</p>
					<p class="fhint">
						<?php esc_html_e( 'Beispiel:', 'bs-kudo-karten' ); ?>
						<code><?php echo esc_html( 'Ein Projekt von [Thomas Evers](https://bezugssysteme.de)' ); ?></code>
					</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">
			<h2><?php esc_html_e( 'E-Mail-Footer', 'bs-kudo-karten' ); ?></h2>
		</div>
		<div class="card-body">
			<div class="fields">
				<div class="field">
					<label class="flabel" for="bskudo_mail_footer_text"><?php esc_html_e( 'Zusatz im E-Mail-Footer', 'bs-kudo-karten' ); ?></label>
					<textarea id="bskudo_mail_footer_text" name="bskudo_settings[branding][mail_footer_text]" rows="3" class="textarea"><?php echo esc_textarea( $branding['mail_footer_text'] ); ?></textarea>
					<p class="fhint">
						<?php esc_html_e( 'Optionaler Text unter der Karte in der Benachrichtigungs-E-Mail. Benannter Link:', 'bs-kudo-karten' ); ?>
						<code>[<?php esc_html_e( 'Linktext', 'bs-kudo-karten' ); ?>](https://beispiel.de)</code>.
						<?php esc_html_e( 'Internetadressen ohne Klammern werden automatisch verlinkt.', 'bs-kudo-karten' ); ?>
					</p>
					<p class="fhint">
						<?php esc_html_e( 'Beispiel:', 'bs-kudo-karten' ); ?>
						<code><?php echo esc_html( 'Ein Projekt von [Thomas Evers](https://bezugssysteme.de) · https://bezugssysteme.de' ); ?></code>
					</p>
				</div>

				<div class="field">
					<label class="check-row">
						<input type="checkbox" name="bskudo_settings[branding][footer_powered]" value="1" <?php checked( ! empty( $branding['footer_powered'] ) ); ?>>
						<span><?php esc_html_e( '„Powered by BS Kudo Karten · bezugssysteme.de“ in der E-Mail anzeigen', 'bs-kudo-karten' ); ?></span>
					</label>
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions">
		<button type="submit" class="btn primary"><?php esc_html_e( 'Einstellungen speichern', 'bs-kudo-karten' ); ?></button>
	</div>
</form>
