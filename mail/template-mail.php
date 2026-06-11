<?php
/**
 * HTML-Mail-Template – Türöffner zur Webansicht.
 * Kein Kartenbild in der Mail – der Wow-Effekt liegt in der Webansicht.
 *
 * @package BSKudo
 *
 * @var string $recipient_name
 * @var string $sender_name
 * @var string $sender_display
 * @var string $site_name
 * @var string $show_powered
 * @var string $powered_text
 * @var string $view_url
 * @var string $logo_src
 * @var string $mail_footer_text
 * @var string $qr_src
 * @var string $token_ttl_days
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recipient_name   = isset( $recipient_name ) ? $recipient_name : '';
$sender_name      = isset( $sender_name ) ? $sender_name : '';
$sender_display   = isset( $sender_display ) ? $sender_display : '';
$site_name        = isset( $site_name ) ? $site_name : '';
$view_url         = isset( $view_url ) ? $view_url : '';
$logo_src         = isset( $logo_src ) ? $logo_src : '';
$mail_footer_text = isset( $mail_footer_text ) ? $mail_footer_text : '';
$qr_src           = isset( $qr_src ) ? $qr_src : '';
$show_powered     = isset( $show_powered ) ? $show_powered : '';
$powered_text     = isset( $powered_text ) ? $powered_text : '';
$token_ttl_days   = isset( $token_ttl_days ) ? max( 1, (int) $token_ttl_days ) : 30;

$header_label = '' !== trim( $sender_display ) ? $sender_display : $site_name;
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?></title>
</head>
<body style="margin:0;padding:0;background:#EFF8FB;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#EFF8FB;padding:32px 16px;">
	<tr>
		<td align="center">
			<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(51,92,112,0.10);">

				<!-- Kopfzeile -->
				<tr>
					<td style="background:#335C70;padding:28px 40px;text-align:center;">
						<?php if ( '' !== $logo_src ) : ?>
							<img
								src="<?php echo esc_url( $logo_src ); ?>"
								alt="<?php echo esc_attr( $site_name ); ?>"
								width="160"
								style="max-width:160px;height:auto;display:inline-block;border:0;"
							>
						<?php else : ?>
							<p style="margin:0;font-size:18px;font-weight:bold;color:#ffffff;letter-spacing:0.02em;">
								<?php echo esc_html( $header_label ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>

				<!-- Hauptbereich -->
				<tr>
					<td style="padding:40px 40px 32px;font-family:Arial,Helvetica,sans-serif;">

						<!-- Begrüßung -->
						<p style="margin:0 0 8px;font-size:22px;line-height:1.3;color:#212121;font-weight:700;">
							<?php
							printf(
								/* translators: %s: recipient name */
								esc_html__( 'Hallo %s,', 'bs-kudo-karten' ),
								esc_html( $recipient_name )
							);
							?>
						</p>

						<!-- Einleitung -->
						<p style="margin:0 0 32px;font-size:16px;line-height:1.6;color:rgba(0,0,0,0.60);">
							<?php
							printf(
								/* translators: %s: sender name */
								esc_html__( '%s hat dir etwas Besonderes geschickt – eine persönliche Kudo-Karte.', 'bs-kudo-karten' ),
								'<strong style="color:#335C70;">' . esc_html( $sender_name ) . '</strong>'
							);
							?>
						</p>

						<!-- Trennlinie -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 32px;">
							<tr>
								<td style="height:1px;background:#EFF8FB;font-size:0;line-height:0;">&nbsp;</td>
							</tr>
						</table>

						<!-- Neugier-Teaser -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
							<tr>
								<td style="background:#EFF8FB;border-radius:12px;padding:24px;text-align:center;">
									<p style="margin:0 0 6px;font-size:32px;line-height:1;">💌</p>
									<p style="margin:0;font-size:15px;line-height:1.5;color:#335C70;font-style:italic;">
										<?php esc_html_e( 'Deine Karte wartet auf dich …', 'bs-kudo-karten' ); ?>
									</p>
								</td>
							</tr>
						</table>

						<?php if ( $view_url ) : ?>

						<!-- CTA-Button -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 16px;">
							<tr>
								<td align="center">
									<a
										href="<?php echo esc_url( $view_url ); ?>"
										style="display:inline-block;padding:16px 40px;background:#FF664D;color:#ffffff;font-size:17px;font-weight:bold;line-height:1.2;text-align:center;text-decoration:none;border-radius:100px;letter-spacing:0.01em;"
									>
										<?php esc_html_e( 'Kudo-Karte ansehen →', 'bs-kudo-karten' ); ?>
									</a>
								</td>
							</tr>
						</table>

						<!-- TTL-Hinweis -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 32px;">
							<tr>
								<td align="center">
									<p style="margin:0;font-size:12px;line-height:1.5;color:rgba(0,0,0,0.38);">
										<?php
										printf(
											/* translators: %d: number of days */
											esc_html__( 'Der Link ist %d Tage gültig.', 'bs-kudo-karten' ),
											(int) $token_ttl_days
										);
										?>
									</p>
								</td>
							</tr>
						</table>

						<?php endif; ?>

						<?php if ( $qr_src ) : ?>

						<!-- Trennlinie -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
							<tr>
								<td style="height:1px;background:#EFF8FB;font-size:0;line-height:0;">&nbsp;</td>
							</tr>
						</table>

						<!-- QR-Code -->
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px;">
							<tr>
								<td align="center">
									<img
										src="<?php echo esc_url( $qr_src ); ?>"
										alt="<?php esc_attr_e( 'QR-Code zur Kudo-Karte', 'bs-kudo-karten' ); ?>"
										width="100"
										height="100"
										style="display:block;width:100px;height:100px;margin:0 auto;border:0;border-radius:8px;"
									>
									<p style="margin:8px 0 0;font-size:11px;line-height:1.5;color:rgba(0,0,0,0.35);text-transform:uppercase;letter-spacing:0.06em;">
										<?php esc_html_e( 'Oder QR-Code scannen', 'bs-kudo-karten' ); ?>
									</p>
								</td>
							</tr>
						</table>

						<?php endif; ?>

						<?php if ( '' !== trim( $mail_footer_text ) ) : ?>
						<p style="margin:24px 0 0;font-size:13px;line-height:1.5;color:rgba(0,0,0,0.45);text-align:center;font-style:italic;">
							<?php echo esc_html( $mail_footer_text ); ?>
						</p>
						<?php endif; ?>

					</td>
				</tr>

				<?php if ( $show_powered ) : ?>
				<!-- Fußzeile -->
				<tr>
					<td style="background:#f7f7f7;padding:16px 40px;text-align:center;border-top:1px solid #eeeeee;">
						<p style="margin:0;font-size:11px;line-height:1.5;color:#aaaaaa;">
							<a href="https://bezugssysteme.de" style="color:#aaaaaa;text-decoration:none;">
								<?php echo esc_html( $powered_text ); ?>
							</a>
						</p>
					</td>
				</tr>
				<?php endif; ?>

			</table>
		</td>
	</tr>
</table>
</body>
</html>