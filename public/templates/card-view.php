<?php
/**
 * Webansicht für Empfänger (tokenbasiert) – Vorder-/Rückseite per Toggle.
 *
 * @package BSKudo
 *
 * @var array<string, mixed> $card         Karten-Daten.
 * @var string               $message      Nutzer-Text.
 * @var string               $sender_name  Name des Versenders.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card         = isset( $card ) && is_array( $card ) ? $card : array();
$message      = isset( $message ) ? (string) $message : '';
$sender_name  = isset( $sender_name ) ? (string) $sender_name : '';
$accent_color = sanitize_hex_color( (string) ( $card['accent_color'] ?? '#335C70' ) );
if ( ! $accent_color ) {
	$accent_color = '#335C70';
}
$image_url  = esc_url( (string) ( $card['image_url'] ?? '' ) );
$image_alt  = '' !== (string) ( $card['image_alt'] ?? '' )
	? esc_attr( (string) $card['image_alt'] )
	: esc_attr( (string) ( $card['title'] ?? __( 'Kudo-Karte', 'bs-kudo-karten' ) ) );
$card_title = esc_html( (string) ( $card['title'] ?? '' ) );
$branding_col1 = (string) ( $card['back_branding_col1'] ?? '' );
$branding_col2 = (string) ( $card['back_branding_col2'] ?? '' );
$icon_pos   = isset( $card['icon_position'] ) ? (string) $card['icon_position'] : 'center';
$msg_align  = in_array( $icon_pos, array( 'left', 'right' ), true ) ? $icon_pos : 'center';
$msg_zone_class = 'bskudo-card--msg-' . $msg_align;

$logo_id  = (int) BSKudo_Settings::get( 'branding', 'logo_id', 0 );
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

$token = get_query_var( BSKudo_Token::QUERY_VAR );
$current_url = BSKudo_Token::get_url( $token );
$qr_code_data_uri = BSKudo_QR::get_data_uri( $current_url, 150 );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( sprintf( /* translators: %s: site name */ __( 'Deine Kudo-Karte · %s', 'bs-kudo-karten' ), get_bloginfo( 'name' ) ) ); ?></title>
	<style>
		body.bskudo-card-view-page {
			margin: 0;
			min-height: 100vh;
			background: #f0f0f0;
			font-family: "Brandon Grotesque", "Nunito", Arial, Helvetica, sans-serif;
			color: #212121;
		}
		.bskudo-card-view-page__wrap {
			max-width: 800px;
			margin: 0 auto;
			padding: 2rem 1.25rem 3rem;
			text-align: center;
		}
		.bskudo-card-view-page__header {
			margin-bottom: 1.5rem;
		}
		.bskudo-card-view-page__eyebrow {
			margin: 0 0 0.35rem;
			font-size: 0.875rem;
			color: rgba(0, 0, 0, 0.45);
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}
		.bskudo-card-view-page__title {
			margin: 0;
			font-size: 1.35rem;
			font-weight: 700;
			color: #335C70;
		}
		.bskudo-cardview__side {
			display: none;
			opacity: 0;
			transition: opacity 0.4s ease;
		}
		.bskudo-cardview__side--active {
			display: block;
			opacity: 1;
		}
		.bskudo-cardview__label {
			font-size: 11px;
			font-weight: 500;
			letter-spacing: 0.08em;
			text-transform: uppercase;
			color: #335C70;
			margin: 0 0 8px;
		}
		.bskudo-cardview__media {
			position: relative;
			display: grid;
			max-width: 100%;
			line-height: 0;
		}
		.bskudo-cardview__media > img {
			grid-area: 1 / 1;
			width: 100%;
			max-width: 800px;
			height: auto;
			border-radius: 12px;
			display: block;
			margin: 0 auto;
			box-shadow: 0 8px 32px rgba(51, 92, 112, 0.15);
		}
		.bskudo-cardview__media > .bskudo-card__message-zone {
			grid-area: 1 / 1;
			position: relative;
			top: auto;
			left: auto;
			transform: none;
			width: 60%;
			height: 40%;
			max-height: 40%;
			place-self: center;
		}
		.bskudo-cardview__media > .bskudo-card__message-zone > .bskudo-cardview__message {
			width: 100%;
			max-width: 100%;
			max-height: 100%;
		}
		.bskudo-cardview__message {
			position: static;
			width: 100%;
			max-width: 100%;
			max-height: 100%;
			min-height: 0;
			flex: 0 1 auto;
			align-self: center;
			overflow: hidden;
			overflow-wrap: anywhere;
			word-break: break-word;
			margin: 0;
			padding: 0;
			color: #2a2a2a;
			font-family: Georgia, "Times New Roman", serif;
			font-style: italic;
			font-weight: 600;
			line-height: 1.25;
			text-align: center;
			word-break: break-word;
			text-shadow: 0 0 10px rgba(255, 255, 255, 0.95), 0 0 4px rgba(255, 255, 255, 0.85);
		}
		.bskudo-cardview__message--left { text-align: left; }
		.bskudo-cardview__message--right { text-align: right; }
		.bskudo-cardview__back-panel {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			max-width: 800px;
			min-height: 460px;
			margin: 0 auto;
			padding: 2rem 1.5rem;
			box-sizing: border-box;
			border-radius: 12px;
			background: <?php echo esc_attr( $accent_color ); ?>;
			color: #ffffff;
			font-size: 1rem;
			font-weight: 600;
			line-height: 1.45;
			box-shadow: 0 8px 32px rgba(51, 92, 112, 0.15);
		}
		.bskudo-cardview__toggle,
		.bskudo-cardview__print-btn {
			background: #FF664D;
			color: #ffffff;
			border-radius: 100px;
			padding: 12px 28px;
			font-size: 15px;
			font-weight: 700;
			border: none;
			cursor: pointer;
			transition: background 0.2s ease;
			font-family: inherit;
		}
		.bskudo-cardview__toggle:hover,
		.bskudo-cardview__print-btn:hover {
			background: #335C70;
		}
		.bskudo-card-view-page__footer {
			margin-top: 2rem;
			font-size: 0.75rem;
			color: #888888;
		}
		.bskudo-card-view-page__footer p {
			margin: 0;
		}

		/* Two columns for card back side */
		.bskudo-cardview__back-cols {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 2rem;
			width: 100%;
			text-align: left;
			align-items: center;
		}
		.bskudo-cardview__back-col {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			text-align: center;
			min-width: 0;
		}
		.bskudo-cardview__qr-wrap {
			background: #ffffff;
			padding: 8px;
			border-radius: 8px;
			display: inline-block;
			margin-bottom: 10px;
			line-height: 0;
		}
		.bskudo-cardview__qr-image {
			width: 120px !important;
			height: 120px !important;
			display: block !important;
			box-shadow: none !important;
			border-radius: 0 !important;
		}
		.bskudo-cardview__col-content {
			font-size: 0.95rem;
			line-height: 1.45;
			color: #ffffff;
			width: 100%;
		}
		.bskudo-cardview__col-content p {
			margin: 0 0 8px;
		}
		.bskudo-cardview__col-content p:last-child {
			margin-bottom: 0;
		}
		.bskudo-cardview__col-content a {
			color: #ffffff !important;
			text-decoration: underline !important;
		}

		@media (max-width: 600px) {
			.bskudo-cardview__back-cols {
				grid-template-columns: 1fr;
				gap: 1.5rem;
			}
		}

		/* Print Layout */
		.bskudo-print-marks {
			display: none;
		}
		
		@media print {
			@page {
				size: A4 portrait;
				margin: 0;
			}
			html, body {
				width: 210mm !important;
				height: 297mm !important;
				background: #ffffff !important;
				color: #000000 !important;
				padding: 0 !important;
				margin: 0 !important;
			}
			body.bskudo-card-view-page {
				background: #ffffff !important;
				color: #000000 !important;
				padding: 0 !important;
				margin: 0 !important;
			}
			.bskudo-card-view-page__wrap {
				max-width: 100% !important;
				width: 100% !important;
				padding: 0 !important;
				margin: 0 !important;
				position: relative !important;
				overflow: visible !important;
			}
			.bskudo-card-view-page__header,
			.bskudo-cardview__label,
			.bskudo-cardview__actions,
			.bskudo-card-view-page__footer {
				display: none !important;
			}
			
			/* Place cards exactly stacked vertically on the top half of page */
			.bskudo-cardview {
				display: block !important;
				position: absolute !important;
				top: 1.0cm !important;
				left: 50% !important;
				transform: translateX(-50%) !important;
				width: 14.8cm !important;
				height: 22.2cm !important;
				box-shadow: none !important;
				border: none !important;
				margin: 0 !important;
				padding: 0 !important;
				overflow: visible !important;
			}
			
			.bskudo-cardview__side {
				display: block !important;
				opacity: 1 !important;
				width: 14.8cm !important;
				height: 11.1cm !important;
				position: absolute !important;
				left: 0 !important;
				box-sizing: border-box !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			
			/* Back is at top (rotated 180°), Front is at bottom (upright) */
			.bskudo-cardview__side--back {
				top: 0 !important;
				transform: rotate(180deg) !important;
			}
			.bskudo-cardview__side--front {
				top: 11.1cm !important;
			}

			.bskudo-cardview__media img,
			.bskudo-cardview__back-panel {
				width: 14.8cm !important;
				height: 11.1cm !important;
				border-radius: 0 !important;
				box-shadow: none !important;
				border: 1px solid #ddd !important; /* light guidelines for cutting */
				box-sizing: border-box !important;
			}

			.bskudo-cardview__media {
				display: grid !important;
				position: relative !important;
				width: 14.8cm !important;
				height: 11.1cm !important;
				max-width: 14.8cm !important;
				line-height: 0 !important;
			}

			.bskudo-cardview__media > img {
				grid-area: 1 / 1 !important;
				width: 14.8cm !important;
				height: 11.1cm !important;
				max-width: none !important;
				margin: 0 !important;
				object-fit: contain !important;
				object-position: center !important;
			}

			.bskudo-cardview__media > .bskudo-card__message-zone {
				grid-area: 1 / 1 !important;
				position: relative !important;
				top: auto !important;
				left: auto !important;
				transform: none !important;
				width: 60% !important;
				height: 40% !important;
				max-height: 40% !important;
				place-self: center !important;
				padding: 0 !important;
				overflow: hidden !important;
			}

			.bskudo-cardview__media > .bskudo-card__message-zone > .bskudo-cardview__message {
				width: 100% !important;
				max-width: 100% !important;
				max-height: 100% !important;
				overflow: hidden !important;
				overflow-wrap: anywhere !important;
				word-break: break-word !important;
			}
			
			.bskudo-cardview__message {
				text-shadow: none !important;
				color: #000000 !important;
			}
			
			.bskudo-cardview__back-panel {
				background: <?php echo esc_attr( $accent_color ); ?> !important;
				color: #ffffff !important;
				min-height: 11.1cm !important;
				max-height: 11.1cm !important;
				height: 11.1cm !important;
				padding: 1.0cm !important;
				-webkit-print-color-adjust: exact !important;
				print-color-adjust: exact !important;
			}
			.bskudo-cardview__back-cols {
				gap: 1.0cm !important;
				grid-template-columns: 1fr 1fr !important;
			}
			
			.bskudo-cardview__qr-wrap {
				border: none !important;
				padding: 4px !important;
			}
			.bskudo-cardview__qr-image {
				width: 75px !important;
				height: 75px !important;
			}
			.bskudo-cardview__col-content {
				font-size: 10pt !important;
				color: #ffffff !important;
			}
			.bskudo-cardview__col-content a {
				color: #ffffff !important;
				text-decoration: underline !important;
			}
			
			/* Crop Marks & Fold Mark Container (relativ zum Kartenstapel) */
			.bskudo-print-marks {
				display: block !important;
				position: absolute !important;
				top: 0 !important;
				left: 0 !important;
				width: 100% !important;
				height: 100% !important;
				transform: none !important;
				pointer-events: none !important;
				z-index: 99 !important;
				overflow: visible !important;
			}
			.bskudo-print-mark {
				position: absolute !important;
				box-sizing: border-box !important;
				border: 0 solid #666666 !important;
			}
			/* Eck-Schnittmarken: 3 mm außerhalb, L-Form je 3 mm */
			.bskudo-print-mark--tl {
				top: -3mm !important;
				left: -3mm !important;
				width: 3mm !important;
				height: 3mm !important;
				border-right-width: 0.2mm !important;
				border-bottom-width: 0.2mm !important;
			}
			.bskudo-print-mark--tr {
				top: -3mm !important;
				right: -3mm !important;
				width: 3mm !important;
				height: 3mm !important;
				border-left-width: 0.2mm !important;
				border-bottom-width: 0.2mm !important;
			}
			.bskudo-print-mark--bl {
				bottom: -3mm !important;
				left: -3mm !important;
				width: 3mm !important;
				height: 3mm !important;
				border-right-width: 0.2mm !important;
				border-top-width: 0.2mm !important;
			}
			.bskudo-print-mark--br {
				bottom: -3mm !important;
				right: -3mm !important;
				width: 3mm !important;
				height: 3mm !important;
				border-left-width: 0.2mm !important;
				border-top-width: 0.2mm !important;
			}
			/* Faltpfalz-Hilfsstriche an der Mitte (11,1 cm) */
			.bskudo-print-mark--ml {
				top: 11.1cm !important;
				left: -5mm !important;
				width: 5mm !important;
				height: 0 !important;
				border-top: 0.2mm dashed #666666 !important;
			}
			.bskudo-print-mark--mr {
				top: 11.1cm !important;
				right: -5mm !important;
				width: 5mm !important;
				height: 0 !important;
				border-top: 0.2mm dashed #666666 !important;
			}
		}
	</style>
</head>
<body class="bskudo-card-view-page">
	<div class="bskudo-card-view-page__wrap">
		<header class="bskudo-card-view-page__header">
			<?php if ( '' !== $sender_name ) : ?>
				<h1 class="bskudo-card-view-page__title">
					<?php
					printf(
						/* translators: %s: sender name */
						esc_html__( '%s hat dir etwas Besonderes geschickt – eine persönliche Kudo-Karte', 'bs-kudo-karten' ),
						esc_html( $sender_name )
					);
					?>
				</h1>
			<?php else : ?>
				<h1 class="bskudo-card-view-page__title">
					<?php esc_html_e( 'Du hast eine persönliche Kudo-Karte erhalten', 'bs-kudo-karten' ); ?>
				</h1>
			<?php endif; ?>
		</header>

		<div class="bskudo-cardview">
			<!-- Vorderseite (initial sichtbar) -->
			<div class="bskudo-cardview__side bskudo-cardview__side--front bskudo-cardview__side--active">
				<p class="bskudo-cardview__label"><?php esc_html_e( 'Vorderseite', 'bs-kudo-karten' ); ?></p>
				<div class="bskudo-cardview__media <?php echo esc_attr( $msg_zone_class ); ?>">
					<?php if ( $image_url ) : ?>
						<img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: card title */ __( 'Kudo-Karte Vorderseite: %s', 'bs-kudo-karten' ), (string) ( $card['title'] ?? '' ) ) ); ?>">
					<?php else : ?>
						<div class="bskudo-cardview__back-panel" style="background:#f8f8f8;color:#333;">
							<?php echo $card_title; ?>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $message && $image_url ) : ?>
						<div class="bskudo-card__message-zone">
							<p class="bskudo-cardview__message bskudo-cardview__message--<?php echo esc_attr( $msg_align ); ?>"><?php echo esc_html( $message ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Rückseite (Branding) -->
			<div class="bskudo-cardview__side bskudo-cardview__side--back">
				<p class="bskudo-cardview__label"><?php esc_html_e( 'Rückseite', 'bs-kudo-karten' ); ?></p>
				<div
					class="bskudo-cardview__back-panel"
					role="img"
					aria-label="<?php esc_attr_e( 'Kudo-Karte Rückseite', 'bs-kudo-karten' ); ?>"
				>
					<div class="bskudo-cardview__back-cols">
						<div class="bskudo-cardview__back-col bskudo-cardview__back-col--left">
							<?php if ( $qr_code_data_uri ) : ?>
								<div class="bskudo-cardview__qr-wrap">
									<img class="bskudo-cardview__qr-image" src="<?php echo esc_url( $qr_code_data_uri ); ?>" alt="<?php esc_attr_e( 'Kudo-Karte QR-Code', 'bs-kudo-karten' ); ?>">
								</div>
							<?php endif; ?>
							<div class="bskudo-cardview__col-content">
								<?php if ( '' !== $branding_col1 ) : ?>
									<?php echo wp_kses_post( $branding_col1 ); ?>
								<?php else : ?>
									<p style="font-size: 11px; margin-top: 8px; opacity: 0.75;"><?php esc_html_e( 'Scanne diesen Code, um die digitale Kudo-Karte online aufzurufen.', 'bs-kudo-karten' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="bskudo-cardview__back-col bskudo-cardview__back-col--right">
							<?php if ( $logo_url ) : ?>
								<div class="bskudo-cardview__logo-wrap" style="margin-bottom: 15px; text-align: center;">
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width: 140px; height: auto; display: inline-block;">
								</div>
							<?php endif; ?>
							<div class="bskudo-cardview__col-content">
								<?php echo wp_kses_post( $branding_col2 ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="bskudo-cardview__actions" style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 10px;">
				<button type="button" class="bskudo-cardview__toggle" id="bskudo-toggle">
					<?php esc_html_e( 'Rückseite ansehen', 'bs-kudo-karten' ); ?>
				</button>
				<button type="button" class="bskudo-cardview__print-btn">
					<?php esc_html_e( 'Karte drucken', 'bs-kudo-karten' ); ?>
				</button>
			</div>

			<!-- Schnittmarken für den Druck -->
			<div class="bskudo-print-marks" aria-hidden="true">
				<div class="bskudo-print-mark bskudo-print-mark--tl"></div>
				<div class="bskudo-print-mark bskudo-print-mark--tr"></div>
				<div class="bskudo-print-mark bskudo-print-mark--bl"></div>
				<div class="bskudo-print-mark bskudo-print-mark--br"></div>
				<div class="bskudo-print-mark bskudo-print-mark--ml"></div>
				<div class="bskudo-print-mark bskudo-print-mark--mr"></div>
			</div>
		</div>

		<?php if ( BSKudo_Settings::get( 'branding', 'footer_powered', true ) ) : ?>
			<footer class="bskudo-card-view-page__footer">
				<p><?php esc_html_e( 'Powered by BS Kudo Karten · bezugssysteme.de', 'bs-kudo-karten' ); ?></p>
			</footer>
		<?php endif; ?>
	</div>
</body>
</html>