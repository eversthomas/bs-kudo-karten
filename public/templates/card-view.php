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
$branding   = esc_html( (string) ( $card['back_branding'] ?? '' ) );
$icon_pos   = isset( $card['icon_position'] ) ? (string) $card['icon_position'] : 'center';
$msg_align  = in_array( $icon_pos, array( 'left', 'right' ), true ) ? $icon_pos : 'center';
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
			display: inline-block;
			max-width: 100%;
			line-height: 0;
		}
		.bskudo-cardview__media img {
			width: 100%;
			max-width: 800px;
			height: auto;
			border-radius: 12px;
			display: block;
			margin: 0 auto;
			box-shadow: 0 8px 32px rgba(51, 92, 112, 0.15);
		}
		.bskudo-cardview__message {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			width: 68%;
			max-height: 38%;
			overflow: hidden;
			margin: 0;
			padding: 0;
			color: #2a2a2a;
			font-family: Georgia, "Times New Roman", serif;
			font-size: clamp(14px, 2.2vw, 24px);
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
		.bskudo-cardview__toggle {
			background: #FF664D;
			color: #ffffff;
			border-radius: 100px;
			padding: 12px 28px;
			font-size: 15px;
			font-weight: 700;
			border: none;
			cursor: pointer;
			margin-top: 1.5rem;
			transition: background 0.2s ease;
			font-family: inherit;
		}
		.bskudo-cardview__toggle:hover {
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
				<div class="bskudo-cardview__media">
					<?php if ( $image_url ) : ?>
						<img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: card title */ __( 'Kudo-Karte Vorderseite: %s', 'bs-kudo-karten' ), (string) ( $card['title'] ?? '' ) ) ); ?>">
					<?php else : ?>
						<div class="bskudo-cardview__back-panel" style="background:#f8f8f8;color:#333;">
							<?php echo $card_title; ?>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $message && $image_url ) : ?>
						<p class="bskudo-cardview__message bskudo-cardview__message--<?php echo esc_attr( $msg_align ); ?>"><?php echo esc_html( $message ); ?></p>
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
					<?php echo $branding; ?>
				</div>
			</div>

			<button type="button" class="bskudo-cardview__toggle" id="bskudo-toggle">
				<?php esc_html_e( 'Rückseite ansehen', 'bs-kudo-karten' ); ?>
			</button>
		</div>

		<?php if ( BSKudo_Settings::get( 'branding', 'footer_powered', true ) ) : ?>
			<footer class="bskudo-card-view-page__footer">
				<p><?php esc_html_e( 'Powered by BS Kudo Karten · bezugssysteme.de', 'bs-kudo-karten' ); ?></p>
			</footer>
		<?php endif; ?>
	</div>

	<script>
		var toggle = document.getElementById('bskudo-toggle');
		var front = document.querySelector('.bskudo-cardview__side--front');
		var back = document.querySelector('.bskudo-cardview__side--back');
		var showingFront = true;
		var labelShowBack = <?php echo wp_json_encode( __( 'Rückseite ansehen', 'bs-kudo-karten' ) ); ?>;
		var labelShowFront = <?php echo wp_json_encode( __( 'Vorderseite ansehen', 'bs-kudo-karten' ) ); ?>;

		if (toggle && front && back) {
			toggle.addEventListener('click', function () {
				if (showingFront) {
					front.classList.remove('bskudo-cardview__side--active');
					back.classList.add('bskudo-cardview__side--active');
					toggle.textContent = labelShowFront;
				} else {
					back.classList.remove('bskudo-cardview__side--active');
					front.classList.add('bskudo-cardview__side--active');
					toggle.textContent = labelShowBack;
				}
				showingFront = !showingFront;
			});
		}
	</script>
</body>
</html>