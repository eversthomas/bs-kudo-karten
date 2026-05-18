<?php
/**
 * HTML-Mail-Template (table-basiert, inline data-URIs).
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
 * @var string $card_img_src
 * @var string $logo_src
 * @var string $mail_footer_text
 * @var string $qr_data_uri
 * @var string $token_ttl_days
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recipient_name   = isset( $recipient_name ) ? $recipient_name : '';
$sender_name      = isset( $sender_name ) ? $sender_name : '';
$sender_display   = isset( $sender_display ) ? $sender_display : '';
$site_name        = isset( $site_name ) ? $site_name : '';
$view_url         = isset( $view_url ) ? $view_url : '';
$card_img_src     = isset( $card_img_src ) ? $card_img_src : '';
$logo_src         = isset( $logo_src ) ? $logo_src : '';
$mail_footer_text = isset( $mail_footer_text ) ? $mail_footer_text : '';
$qr_data_uri      = isset( $qr_data_uri ) ? $qr_data_uri : '';
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
<body style="margin:0;padding:0;background:#f0f0f0;font-family:Arial,Helvetica,sans-serif;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f0f0f0;padding:24px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
					<!-- Kopfzeile -->
					<tr>
						<td style="background:#335C70;padding:24px;text-align:center;">
							<?php if ( '' !== $logo_src ) : ?>
								<img src="<?php echo esc_attr( $logo_src ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" width="160" style="max-width:160px;height:auto;display:inline-block;border:0;">
							<?php else : ?>
								<p style="margin:0;font-size:20px;font-weight:bold;color:#ffffff;line-height:1.3;">
									<?php echo esc_html( $header_label ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<!-- Hauptbereich -->
					<tr>
						<td style="padding:32px 40px;font-family:Arial,Helvetica,sans-serif;">
							<p style="margin:0 0 16px;font-size:18px;line-height:1.4;color:#212121;font-weight:600;">
								<?php
								printf(
									/* translators: %s: recipient name */
									esc_html__( 'Hallo %s,', 'bs-kudo-karten' ),
									esc_html( $recipient_name )
								);
								?>
							</p>
							<p style="margin:0 0 8px;font-size:15px;line-height:1.5;color:rgba(0,0,0,0.65);">
								<?php
								printf(
									/* translators: %s: sender name */
									esc_html__( '%s hat dir eine Kudo-Karte geschickt:', 'bs-kudo-karten' ),
									esc_html( $sender_name )
								);
								?>
							</p>
							<?php if ( '' !== $card_img_src ) : ?>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
								<tr>
									<td align="center">
										<img
											src="<?php echo esc_attr( $card_img_src ); ?>"
											alt="<?php esc_attr_e( 'Deine Kudo-Karte', 'bs-kudo-karten' ); ?>"
											width="480"
											style="width:100%;max-width:480px;height:auto;border-radius:12px;display:block;margin:0 auto;border:0;"
										>
									</td>
								</tr>
							</table>
							<?php endif; ?>
							<?php if ( $view_url ) : ?>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 0;">
								<tr>
									<td align="center">
										<a
											href="<?php echo esc_url( $view_url ); ?>"
											style="display:block;max-width:240px;margin:0 auto;padding:14px 32px;background:#FF664D;color:#ffffff;font-size:16px;font-weight:bold;line-height:1.2;text-align:center;text-decoration:none;border-radius:100px;"
										>
											<?php esc_html_e( 'Karte im Browser ansehen', 'bs-kudo-karten' ); ?>
										</a>
									</td>
								</tr>
								<tr>
									<td align="center" style="padding-top:12px;">
										<p style="margin:0;font-size:12px;line-height:1.5;color:rgba(0,0,0,0.45);">
											<?php
											printf(
												/* translators: %d: number of days */
												esc_html__( 'Die Karte ist %d Tage aufrufbar.', 'bs-kudo-karten' ),
												(int) $token_ttl_days
											);
											?>
										</p>
									</td>
								</tr>
								<?php if ( '' !== $qr_data_uri ) : ?>
								<tr>
									<td align="center" style="padding-top:24px;">
										<img
											src="<?php echo esc_attr( $qr_data_uri ); ?>"
											alt="<?php esc_attr_e( 'QR-Code zur Kudo-Karte', 'bs-kudo-karten' ); ?>"
											width="120"
											height="120"
											style="display:block;max-width:120px;height:auto;margin:0 auto;border:0;border-radius:8px;"
										>
										<p style="margin:10px 0 0;font-size:12px;line-height:1.5;color:rgba(0,0,0,0.45);">
											<?php esc_html_e( 'Oder QR-Code scannen', 'bs-kudo-karten' ); ?>
										</p>
									</td>
								</tr>
								<?php endif; ?>
							</table>
							<?php endif; ?>
							<?php if ( '' !== trim( $mail_footer_text ) ) : ?>
							<p style="margin:24px 0 0;font-size:13px;line-height:1.5;color:rgba(0,0,0,0.55);text-align:center;">
								<?php echo esc_html( $mail_footer_text ); ?>
							</p>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( $show_powered ) : ?>
					<!-- Fußzeile -->
					<tr>
						<td style="background:#f5f5f5;padding:20px;text-align:center;font-family:Arial,Helvetica,sans-serif;">
							<p style="margin:0;font-size:12px;line-height:1.5;color:#888888;">
								<a href="https://bezugssysteme.de" style="color:#888888;text-decoration:underline;">
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
