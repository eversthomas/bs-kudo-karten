<?php
/**
 * Webansicht für Empfänger (Phase 5, tokenbasiert).
 *
 * @package BSKudo
 *
 * @var array<string, mixed> $card               Karten-Daten.
 * @var string               $message            Nutzer-Text.
 * @var string               $message_position   CSS-Klasse Textposition.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card             = isset( $card ) && is_array( $card ) ? $card : array();
$message          = isset( $message ) ? (string) $message : '';
$message_position = isset( $message_position ) ? (string) $message_position : 'bskudo-card--msg-center';
$accent_color     = esc_attr( (string) ( $card['accent_color'] ?? '#c45c3e' ) );
$image_url        = esc_url( (string) ( $card['image_url'] ?? '' ) );
$image_alt        = '' !== (string) ( $card['image_alt'] ?? '' )
	? esc_attr( (string) $card['image_alt'] )
	: esc_attr( (string) ( $card['title'] ?? '' ) );
$card_title       = esc_html( (string) ( $card['title'] ?? '' ) );
$branding         = esc_html( (string) ( $card['back_branding'] ?? '' ) );
$aspect_w         = (int) ( $card['image_width'] ?? 0 );
$aspect_h         = (int) ( $card['image_height'] ?? 0 );
$aspect_style     = ( $aspect_w > 0 && $aspect_h > 0 )
	? ' style="--bskudo-aspect-ratio: ' . esc_attr( (string) $aspect_w ) . ' / ' . esc_attr( (string) $aspect_h ) . ';"'
	: '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( sprintf( /* translators: %s: site name */ __( 'Deine Kudo-Karte · %s', 'bs-kudo-karten' ), get_bloginfo( 'name' ) ) ); ?></title>
</head>
<body class="bskudo-card-view-page">
	<main class="bskudo-card-view" style="--bskudo-accent: <?php echo $accent_color; ?>;">
		<header class="bskudo-card-view__header">
			<p class="bskudo-card-view__eyebrow"><?php esc_html_e( 'Kudo-Karte für dich', 'bs-kudo-karten' ); ?></p>
			<?php if ( $card_title ) : ?>
				<h1 class="bskudo-card-view__title"><?php echo $card_title; ?></h1>
			<?php endif; ?>
		</header>

		<div class="bskudo-card-view__layout">
			<div class="bskudo-card-view__mode bskudo-card-view__mode--duo" data-view-mode="duo">
				<p class="bskudo-card-view__hint"><?php esc_html_e( 'Vorder- und Rückseite deiner Kudo-Karte', 'bs-kudo-karten' ); ?></p>
				<div class="bskudo-preview-duo bskudo-preview-duo--view">
					<div class="bskudo-preview-side">
						<p class="bskudo-preview-side__label"><?php esc_html_e( 'Vorderseite', 'bs-kudo-karten' ); ?></p>
						<div class="bskudo-card bskudo-card--preview bskudo-card--natural <?php echo esc_attr( $message_position ); ?>"<?php echo $aspect_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
								<span class="bskudo-card__face bskudo-card__face--front">
									<span class="bskudo-card__canvas">
										<?php if ( $image_url ) : ?>
											<img
												class="bskudo-card__image bskudo-card-view__image"
												src="<?php echo $image_url; ?>"
												alt="<?php echo $image_alt; ?>"
												data-width="<?php echo esc_attr( (string) $aspect_w ); ?>"
												data-height="<?php echo esc_attr( (string) $aspect_h ); ?>"
											>
										<?php else : ?>
											<span class="bskudo-card__placeholder"><?php echo $card_title; ?></span>
										<?php endif; ?>
										<span class="bskudo-card__message-zone">
											<span class="bskudo-card__message bskudo-card-view__message"><?php echo esc_html( $message ); ?></span>
										</span>
									</span>
								</span>
							</span>
						</div>
					</div>
					<div class="bskudo-preview-side">
						<p class="bskudo-preview-side__label"><?php esc_html_e( 'Rückseite (Branding)', 'bs-kudo-karten' ); ?></p>
						<div class="bskudo-card bskudo-card--preview bskudo-card--natural bskudo-card--back-only"<?php echo $aspect_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
								<span class="bskudo-card__face bskudo-card__face--back">
									<span class="bskudo-card__branding"><?php echo $branding; ?></span>
								</span>
							</span>
						</div>
					</div>
				</div>
			</div>

			<div class="bskudo-card-view__mode bskudo-card-view__mode--flip" data-view-mode="flip" hidden>
				<p class="bskudo-card-view__hint"><?php esc_html_e( 'Tippe die Karte an oder nutze den Button zum Umdrehen.', 'bs-kudo-karten' ); ?></p>
				<div class="bskudo-card-view__flip-wrap">
					<div class="bskudo-card bskudo-card--hero bskudo-card--natural <?php echo esc_attr( $message_position ); ?> bskudo-card-view__flip-card"<?php echo $aspect_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<span class="bskudo-card__inner">
							<span class="bskudo-card__face bskudo-card__face--front">
								<span class="bskudo-card__canvas">
									<?php if ( $image_url ) : ?>
										<img
											class="bskudo-card__image bskudo-card-view__flip-image"
											src="<?php echo $image_url; ?>"
											alt="<?php echo $image_alt; ?>"
											data-width="<?php echo esc_attr( (string) $aspect_w ); ?>"
											data-height="<?php echo esc_attr( (string) $aspect_h ); ?>"
										>
									<?php else : ?>
										<span class="bskudo-card__placeholder"><?php echo $card_title; ?></span>
									<?php endif; ?>
									<span class="bskudo-card__message-zone">
										<span class="bskudo-card__message bskudo-card-view__flip-message"><?php echo esc_html( $message ); ?></span>
									</span>
								</span>
							</span>
							<span class="bskudo-card__face bskudo-card__face--back">
								<span class="bskudo-card__branding"><?php echo $branding; ?></span>
							</span>
						</span>
					</div>
					<button type="button" class="bskudo-btn bskudo-btn--secondary bskudo-card-view__flip-btn">
						<?php esc_html_e( 'Rückseite anzeigen', 'bs-kudo-karten' ); ?>
					</button>
				</div>
			</div>
		</div>

		<nav class="bskudo-card-view__tabs" aria-label="<?php esc_attr_e( 'Ansicht wechseln', 'bs-kudo-karten' ); ?>">
			<button type="button" class="bskudo-card-view__tab bskudo-card-view__tab--active" data-view-target="duo">
				<?php esc_html_e( 'Beide Seiten', 'bs-kudo-karten' ); ?>
			</button>
			<button type="button" class="bskudo-card-view__tab" data-view-target="flip">
				<?php esc_html_e( 'Karte umdrehen', 'bs-kudo-karten' ); ?>
			</button>
		</nav>

		<?php if ( BSKudo_Settings::get( 'branding', 'footer_powered', true ) ) : ?>
			<footer class="bskudo-card-view__footer">
				<p>Powered by BS Kudo Karten · bezugssysteme.de</p>
			</footer>
		<?php endif; ?>
	</main>
</body>
</html>
