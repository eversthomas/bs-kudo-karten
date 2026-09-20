<?php
/**
 * Ergebnis nach POST-Bestätigung oder bei Fehler auf der Landing.
 *
 * @package BSKudo
 *
 * @var bool   $success Erfolg?
 * @var string $message Meldung.
 * @var string $title   Seitentitel.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success = ! empty( $success );
$message = isset( $message ) ? (string) $message : '';
$title   = isset( $title ) ? (string) $title : '';

$accent = sanitize_hex_color( (string) BSKudo_Settings::get( 'branding', 'primary_color', '#335C70' ) );
if ( ! $accent ) {
	$accent = '#335C70';
}

$box_class = $success ? 'bskudo-confirm-page__box--success' : 'bskudo-confirm-page__box--error';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $title ); ?></title>
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
		.bskudo-confirm-page__title {
			margin: 0 0 1rem;
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
		.bskudo-confirm-page__box--error { border-left: 4px solid #c62828; }
		.bskudo-confirm-page__box--success { border-left: 4px solid #2e7d32; }
		.bskudo-confirm-page__home {
			display: block;
			margin-top: 1.5rem;
			text-align: center;
			color: <?php echo esc_attr( $accent ); ?>;
		}
	</style>
</head>
<body class="bskudo-confirm-page">
	<div class="bskudo-confirm-page__wrap">
		<div class="bskudo-confirm-page__box <?php echo esc_attr( $box_class ); ?>">
			<h1 class="bskudo-confirm-page__title"><?php echo esc_html( $title ); ?></h1>
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<a class="bskudo-confirm-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Zur Startseite', 'bs-kudo-karten' ); ?></a>
	</div>
</body>
</html>
