<?php
/**
 * HTML-Mail-Template (Phase 6: JPG-Duo + Branding).
 *
 * @package BSKudo
 *
 * @var string $recipient_name
 * @var string $sender_name
 * @var string $message
 * @var string $card_title
 * @var string $branding_text
 * @var string $site_name
 * @var string $show_powered
 * @var string $powered_text
 * @var string $view_url
 * @var string $logo_url
 * @var string $accent_color
 * @var string $mail_footer_text
 * @var string $front_jpg_base64
 * @var string $back_jpg_base64
 * @var bool   $has_card_images
 * @var string $qr_data_uri
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recipient_name    = isset( $recipient_name ) ? $recipient_name : '';
$sender_name       = isset( $sender_name ) ? $sender_name : '';
$message           = isset( $message ) ? $message : '';
$card_title        = isset( $card_title ) ? $card_title : '';
$branding_text     = isset( $branding_text ) ? $branding_text : '';
$site_name         = isset( $site_name ) ? $site_name : '';
$view_url          = isset( $view_url ) ? $view_url : '';
$logo_url          = isset( $logo_url ) ? $logo_url : '';
$accent_color      = isset( $accent_color ) ? $accent_color : '#c45c3e';
$mail_footer_text  = isset( $mail_footer_text ) ? $mail_footer_text : '';
$front_jpg_base64  = isset( $front_jpg_base64 ) ? $front_jpg_base64 : '';
$back_jpg_base64   = isset( $back_jpg_base64 ) ? $back_jpg_base64 : '';
$has_card_images   = ! empty( $has_card_images );
$qr_data_uri       = isset( $qr_data_uri ) ? $qr_data_uri : '';
$show_powered      = isset( $show_powered ) ? $show_powered : '';
$powered_text      = isset( $powered_text ) ? $powered_text : '';

if ( ! sanitize_hex_color( $accent_color ) ) {
	$accent_color = '#c45c3e';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Helvetica,Arial,sans-serif;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f4;padding:24px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;">
					<?php if ( $logo_url ) : ?>
					<tr>
						<td style="padding:24px 24px 8px;text-align:center;">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" style="max-width:160px;height:auto;display:inline-block;">
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td style="padding:<?php echo $logo_url ? '8px' : '28px'; ?> 24px 16px;text-align:center;">
							<p style="margin:0 0 8px;font-size:14px;color:#666;line-height:1.5;">
								<?php
								printf(
									/* translators: 1: recipient name, 2: sender name */
									esc_html__( 'Hallo %1$s, %2$s hat dir eine Kudo-Karte geschickt:', 'bs-kudo-karten' ),
									esc_html( $recipient_name ),
									esc_html( $sender_name )
								);
								?>
							</p>
							<?php if ( $card_title ) : ?>
								<p style="margin:0;font-size:13px;color:#999;"><?php echo esc_html( $card_title ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( $has_card_images ) : ?>
					<tr>
						<td style="padding:0 16px 8px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td width="50%" style="padding:8px;text-align:center;vertical-align:top;">
										<p style="margin:0 0 8px;font-size:11px;color:#999;text-transform:uppercase;letter-spacing:0.05em;"><?php esc_html_e( 'Vorderseite', 'bs-kudo-karten' ); ?></p>
										<img src="data:image/jpeg;base64,<?php echo esc_attr( $front_jpg_base64 ); ?>" alt="<?php esc_attr_e( 'Vorderseite der Kudo-Karte', 'bs-kudo-karten' ); ?>" width="260" style="max-width:100%;height:auto;border-radius:8px;display:block;margin:0 auto;">
									</td>
									<td width="50%" style="padding:8px;text-align:center;vertical-align:top;">
										<p style="margin:0 0 8px;font-size:11px;color:#999;text-transform:uppercase;letter-spacing:0.05em;"><?php esc_html_e( 'Rückseite', 'bs-kudo-karten' ); ?></p>
										<img src="data:image/jpeg;base64,<?php echo esc_attr( $back_jpg_base64 ); ?>" alt="<?php esc_attr_e( 'Rückseite der Kudo-Karte', 'bs-kudo-karten' ); ?>" width="260" style="max-width:100%;height:auto;border-radius:8px;display:block;margin:0 auto;">
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php else : ?>
					<tr>
						<td style="padding:0 24px 12px;text-align:center;">
							<p style="margin:0;font-size:18px;line-height:1.45;color:#2a2a2a;font-weight:600;white-space:pre-wrap;"><?php echo esc_html( $message ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( $view_url ) : ?>
					<tr>
						<td style="padding:8px 24px 20px;text-align:center;">
							<a href="<?php echo esc_url( $view_url ); ?>" style="display:inline-block;padding:12px 28px;background:<?php echo esc_attr( $accent_color ); ?>;color:#ffffff;text-decoration:none;border-radius:999px;font-size:15px;font-weight:600;">
								<?php esc_html_e( 'Karte im Browser ansehen', 'bs-kudo-karten' ); ?>
							</a>
							<p style="margin:10px 0 0;font-size:12px;color:#999;"><?php esc_html_e( 'Interaktive Ansicht mit Vorder- und Rückseite', 'bs-kudo-karten' ); ?></p>
							<?php if ( $qr_data_uri ) : ?>
							<p style="margin:20px 0 8px;font-size:12px;color:#888;"><?php esc_html_e( 'Oder QR-Code scannen:', 'bs-kudo-karten' ); ?></p>
							<img src="<?php echo esc_attr( $qr_data_uri ); ?>" alt="<?php esc_attr_e( 'QR-Code zur Kudo-Karte', 'bs-kudo-karten' ); ?>" width="120" height="120" style="display:inline-block;border-radius:8px;">
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( $mail_footer_text ) : ?>
					<tr>
						<td style="padding:0 24px 12px;text-align:center;">
							<p style="margin:0;font-size:13px;color:#888;line-height:1.5;"><?php echo esc_html( $mail_footer_text ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( $show_powered ) : ?>
					<tr>
						<td style="padding:16px 24px 24px;text-align:center;border-top:1px solid #eee;">
							<p style="margin:0;font-size:11px;color:#aaa;"><?php echo esc_html( $powered_text ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
