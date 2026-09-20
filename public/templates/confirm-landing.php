<?php
/**
 * Bestätigungs-Landing (GET aus E-Mail) – nur Anzeige, Versand per POST-Button.
 *
 * @package BSKudo
 *
 * @var string               $token           Bereinigter Token.
 * @var array<string, mixed>   $data            Aufgelöste Versanddaten.
 * @var string               $form_action     POST-Ziel (admin-ajax).
 * @var string               $nonce           Nonce-Feld-Wert.
 * @var string               $schedule_notice Optionaler Hinweis zum geplanten Versand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token           = isset( $token ) ? (string) $token : '';
$data            = isset( $data ) && is_array( $data ) ? $data : array();
$form_action     = isset( $form_action ) ? (string) $form_action : '';
$nonce           = isset( $nonce ) ? (string) $nonce : '';
$schedule_notice = isset( $schedule_notice ) ? (string) $schedule_notice : '';

$recipient_name = isset( $data['recipient_name'] ) ? sanitize_text_field( (string) $data['recipient_name'] ) : '';
$product        = BSKudo_Settings::product_name_singular();
$accent         = sanitize_hex_color( (string) BSKudo_Settings::get( 'branding', 'primary_color', '#335C70' ) );
if ( ! $accent ) {
	$accent = '#335C70';
}

$logo_id  = (int) BSKudo_Settings::get( 'branding', 'logo_id', 0 );
$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( sprintf( /* translators: %s: product name singular */ __( 'Versand bestätigen · %s', 'bs-kudo-karten' ), $product ) ); ?></title>
	<style>
		body.bskudo-confirm-page {
			margin: 0;
			min-height: 100vh;
			background: #f0f0f0;
			font-family: "Brandon Grotesque", "Nunito", Arial, Helvetica, sans-serif;
			color: #212121;
		}
		.bskudo-confirm-page__wrap {
			max-width: 32rem;
			margin: 0 auto;
			padding: 2rem 1.25rem 3rem;
		}
		.bskudo-confirm-page__header {
			text-align: center;
			margin-bottom: 1.5rem;
		}
		.bskudo-confirm-page__logo {
			max-height: 48px;
			width: auto;
			margin-bottom: 1rem;
		}
		.bskudo-confirm-page__eyebrow {
			margin: 0 0 0.35rem;
			font-size: 0.875rem;
			color: rgba(0, 0, 0, 0.45);
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}
		.bskudo-confirm-page__title {
			margin: 0;
			font-size: 1.35rem;
			font-weight: 700;
			color: <?php echo esc_attr( $accent ); ?>;
		}
		.bskudo-confirm-page__box {
			background: #fff;
			border-radius: 12px;
			padding: 1.75rem 1.5rem;
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
		}
		.bskudo-confirm-page__lead {
			margin: 0 0 1rem;
			font-size: 1.05rem;
			line-height: 1.5;
		}
		.bskudo-confirm-page__meta {
			margin: 0 0 1.25rem;
			font-size: 0.95rem;
			color: rgba(0, 0, 0, 0.65);
		}
		.bskudo-confirm-page__submit {
			display: block;
			width: 100%;
			border: 0;
			border-radius: 8px;
			padding: 0.85rem 1rem;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			background: <?php echo esc_attr( $accent ); ?>;
			color: #fff;
		}
		.bskudo-confirm-page__submit:hover {
			filter: brightness(0.95);
		}
		.bskudo-confirm-page__hint {
			margin: 1rem 0 0;
			font-size: 0.8125rem;
			color: rgba(0, 0, 0, 0.5);
			text-align: center;
			line-height: 1.45;
		}
		.bskudo-confirm-page__home {
			display: block;
			margin-top: 1.5rem;
			text-align: center;
			color: <?php echo esc_attr( $accent ); ?>;
			text-decoration: none;
			font-size: 0.9375rem;
		}
		.bskudo-confirm-page__box--error {
			border-left: 4px solid #c62828;
		}
		.bskudo-confirm-page__box--success {
			border-left: 4px solid #2e7d32;
		}
	</style>
</head>
<body class="bskudo-confirm-page">
	<div class="bskudo-confirm-page__wrap">
		<header class="bskudo-confirm-page__header">
			<?php if ( $logo_url ) : ?>
				<img class="bskudo-confirm-page__logo" src="<?php echo esc_url( $logo_url ); ?>" alt="">
			<?php endif; ?>
			<p class="bskudo-confirm-page__eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 class="bskudo-confirm-page__title"><?php esc_html_e( 'Versand bestätigen', 'bs-kudo-karten' ); ?></h1>
		</header>

		<div class="bskudo-confirm-page__box">
			<p class="bskudo-confirm-page__lead">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: product name singular, 2: recipient name */
						__( '%1$s an %2$s jetzt versenden?', 'bs-kudo-karten' ),
						$product,
						$recipient_name
					)
				);
				?>
			</p>
			<?php if ( '' !== $schedule_notice ) : ?>
				<p class="bskudo-confirm-page__meta"><?php echo esc_html( $schedule_notice ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( $form_action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( BSKudo_Confirm::AJAX_ACTION ); ?>">
				<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
				<input type="hidden" name="bskudo_confirm_nonce" value="<?php echo esc_attr( $nonce ); ?>">
				<button type="submit" class="bskudo-confirm-page__submit">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: recipient name */
							__( 'Ja, Karte an %s senden', 'bs-kudo-karten' ),
							$recipient_name
						)
					);
					?>
				</button>
			</form>
			<p class="bskudo-confirm-page__hint">
				<?php esc_html_e( 'Nur dieser Button startet den Versand. Das Öffnen des Links aus der E-Mail allein reicht nicht.', 'bs-kudo-karten' ); ?>
			</p>
		</div>
		<a class="bskudo-confirm-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Zur Startseite', 'bs-kudo-karten' ); ?></a>
	</div>
</body>
</html>
