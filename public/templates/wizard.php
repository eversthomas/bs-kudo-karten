<?php
/**
 * Wizard-Template – Sektions-basierter Aufbau (Reveal-Scroll).
 *
 * @package BSKudo
 *
 * @var array<int, array<string, mixed>> $cards         Karten-Daten.
 * @var int                              $char_limit    Zeichenlimit.
 * @var string                           $privacy_text  Datenschutzhinweis.
 * @var string                           $branding_back Standard-Branding Rückseite.
 * @var bool                             $enable_send_to_self
 * @var bool                             $enable_delayed_send
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards               = isset( $cards ) && is_array( $cards ) ? $cards : array();
$char_limit          = isset( $char_limit ) ? (int) $char_limit : BSKUDO_CHAR_LIMIT;
$privacy_text        = isset( $privacy_text ) ? (string) $privacy_text : '';
$branding_back       = isset( $branding_back ) ? (string) $branding_back : '';
$has_cards           = ! empty( $cards );
$enable_send_to_self = ! empty( $enable_send_to_self );
$enable_delayed_send = ! empty( $enable_delayed_send );
?>
<div class="bskudo-wizard" data-section="1" data-max-chars="<?php echo esc_attr( (string) $char_limit ); ?>">
	<form class="bskudo-wizard__form" novalidate>
		<?php wp_nonce_field( BSKudo_Security::NONCE_ACTION, 'bskudo_nonce' ); ?>

		<input type="hidden" name="bskudo_card_id" class="bskudo-wizard__card-id" value="">
		<input type="hidden" name="bskudo_message" class="bskudo-wizard__message-hidden" value="">

		<ol class="bskudo-wizard__steps" aria-label="<?php esc_attr_e( 'Fortschritt', 'bs-kudo-karten' ); ?>">
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label bskudo-wizard__step-label--active" data-step="1" aria-current="step">
					<span class="bskudo-wizard__step-num" aria-hidden="true">1</span>
					<span class="bskudo-wizard__step-text"><?php esc_html_e( 'Karte wählen', 'bs-kudo-karten' ); ?></span>
				</button>
			</li>
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label" data-step="2" disabled>
					<span class="bskudo-wizard__step-num" aria-hidden="true">2</span>
					<span class="bskudo-wizard__step-text"><?php esc_html_e( 'Text', 'bs-kudo-karten' ); ?></span>
				</button>
			</li>
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label" data-step="3" disabled>
					<span class="bskudo-wizard__step-num" aria-hidden="true">3</span>
					<span class="bskudo-wizard__step-text"><?php esc_html_e( 'Versenden', 'bs-kudo-karten' ); ?></span>
				</button>
			</li>
		</ol>

		<!-- Sektion 1: Kartenauswahl -->
		<section
			class="bskudo-section bskudo-section--active"
			data-section="1"
			aria-labelledby="bskudo-section-1-heading"
		>
			<h2 id="bskudo-section-1-heading" class="bskudo-section__heading">
				<?php esc_html_e( 'Wähle deine Kudo-Karte', 'bs-kudo-karten' ); ?>
			</h2>
			<?php if ( ! $has_cards ) : ?>
				<p class="bskudo-empty">
					<?php esc_html_e( 'Aktuell sind keine Karten verfügbar. Bitte lege im Backend Kudo-Karten an und veröffentliche sie.', 'bs-kudo-karten' ); ?>
				</p>
			<?php else : ?>
				<p class="bskudo-section__hint">
					<?php esc_html_e( 'Tippe eine Karte an – im nächsten Schritt schreibst du direkt auf der Karte.', 'bs-kudo-karten' ); ?>
				</p>
				<div class="bskudo-grid bskudo-grid--thumbs" role="list">
					<?php foreach ( $cards as $card ) : ?>
						<?php
						$card_branding = '' !== (string) ( $card['back_branding'] ?? '' )
							? (string) $card['back_branding']
							: $branding_back;
						?>
						<div class="bskudo-thumb" role="listitem">
							<button
								type="button"
								class="bskudo-card bskudo-card--selectable <?php echo esc_attr( 'bskudo-card--msg-' . (string) $card['icon_position'] ); ?>"
								data-card-id="<?php echo esc_attr( (string) (int) $card['id'] ); ?>"
								data-card-title="<?php echo esc_attr( (string) $card['title'] ); ?>"
								data-accent-color="<?php echo esc_attr( (string) $card['accent_color'] ); ?>"
								data-icon-position="<?php echo esc_attr( (string) $card['icon_position'] ); ?>"
								data-image-url="<?php echo esc_url( (string) $card['image_url'] ); ?>"
								data-image-alt="<?php echo esc_attr( '' !== (string) $card['image_alt'] ? (string) $card['image_alt'] : (string) $card['title'] ); ?>"
								data-back-branding="<?php echo esc_attr( $card_branding ); ?>"
								data-image-width="<?php echo esc_attr( (string) (int) ( $card['image_width'] ?? 0 ) ); ?>"
								data-image-height="<?php echo esc_attr( (string) (int) ( $card['image_height'] ?? 0 ) ); ?>"
								aria-pressed="false"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: card title */ __( 'Karte: %s', 'bs-kudo-karten' ), (string) $card['title'] ) ); ?>"
								style="--bskudo-accent: <?php echo esc_attr( (string) $card['accent_color'] ); ?>;"
							>
								<span class="bskudo-card__inner">
									<span class="bskudo-card__face bskudo-card__face--front">
										<?php if ( (string) $card['image_url'] ) : ?>
											<img
												class="bskudo-card__image"
												src="<?php echo esc_url( (string) $card['image_url'] ); ?>"
												alt=""
												loading="lazy"
												decoding="async"
											>
										<?php else : ?>
											<span class="bskudo-card__placeholder">
												<?php echo esc_html( (string) $card['title'] ); ?>
											</span>
										<?php endif; ?>
									</span>
									<span class="bskudo-card__face bskudo-card__face--back">
										<span class="bskudo-card__branding"><?php echo esc_html( $card_branding ); ?></span>
									</span>
								</span>
							</button>
							<span class="bskudo-card__caption"><?php echo esc_html( (string) $card['title'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $has_cards ) : ?>
			<!-- Sektion 2: Text direkt auf der Karte -->
			<section
				class="bskudo-section bskudo-section--pending"
				data-section="2"
				aria-labelledby="bskudo-section-2-heading"
				hidden
			>
				<h2 id="bskudo-section-2-heading" class="bskudo-section__heading">
					<?php esc_html_e( 'Schreibe deinen Kudo-Text', 'bs-kudo-karten' ); ?>
				</h2>

				<div class="bskudo-card-write-wrap">
					<div class="bskudo-card-write bskudo-card--msg-center">
						<img
							class="bskudo-card-write__image"
							src=""
							alt=""
							decoding="async"
						>
						<div class="bskudo-card__message-zone bskudo-card__message-zone--interactive">
							<div
								class="bskudo-card-overlay"
								contenteditable="true"
								role="textbox"
								spellcheck="true"
								aria-multiline="false"
								aria-label="<?php esc_attr_e( 'Deine Botschaft auf der Karte', 'bs-kudo-karten' ); ?>"
								data-placeholder="<?php esc_attr_e( 'Schreib hier deine Botschaft …', 'bs-kudo-karten' ); ?>"
							></div>
						</div>
					</div>
				</div>

				<p class="bskudo-char-count" aria-live="polite">
					<span class="bskudo-char-count__current">0</span>
					/
					<span class="bskudo-char-count__max"><?php echo esc_html( (string) $char_limit ); ?></span>
				</p>

				<div class="bskudo-impulses">
					<p class="bskudo-impulses__label"><?php esc_html_e( 'Textimpulse', 'bs-kudo-karten' ); ?></p>
					<div class="bskudo-impulses__list" role="list"></div>
					<p class="bskudo-impulses__empty" hidden></p>
				</div>

				<div class="bskudo-wizard__nav bskudo-wizard__nav--split">
					<button type="button" class="bskudo-btn bskudo-btn--back" data-goto-section="1">
						<?php esc_html_e( 'Zurück', 'bs-kudo-karten' ); ?>
					</button>
					<button type="button" class="bskudo-btn bskudo-btn--primary bskudo-btn--next" data-goto-section="3" disabled>
						<?php esc_html_e( 'Weiter', 'bs-kudo-karten' ); ?>
					</button>
				</div>
			</section>

			<!-- Sektion 3: Versenden (Formular unverändert) -->
			<section
				class="bskudo-section bskudo-section--pending"
				data-section="3"
				aria-labelledby="bskudo-section-3-heading"
				hidden
			>
				<h2 id="bskudo-section-3-heading" class="bskudo-section__heading">
					<?php esc_html_e( 'Versenden', 'bs-kudo-karten' ); ?>
				</h2>

				<div class="bskudo-fields">
					<p class="bskudo-field">
						<label for="bskudo-sender-name"><?php esc_html_e( 'Dein Name', 'bs-kudo-karten' ); ?></label>
						<input type="text" id="bskudo-sender-name" name="bskudo_sender_name" class="bskudo-input" required autocomplete="name">
					</p>
					<p class="bskudo-field">
						<label for="bskudo-sender-email"><?php esc_html_e( 'Deine E-Mail', 'bs-kudo-karten' ); ?></label>
						<input type="email" id="bskudo-sender-email" name="bskudo_sender_email" class="bskudo-input" required autocomplete="email">
					</p>
					<p class="bskudo-field">
						<label for="bskudo-recipient-name"><?php esc_html_e( 'Name der Empfängerin / des Empfängers', 'bs-kudo-karten' ); ?></label>
						<input type="text" id="bskudo-recipient-name" name="bskudo_recipient_name" class="bskudo-input" required autocomplete="off">
					</p>
					<p class="bskudo-field">
						<label for="bskudo-recipient-email"><?php esc_html_e( 'E-Mail der Empfängerin / des Empfängers', 'bs-kudo-karten' ); ?></label>
						<input type="email" id="bskudo-recipient-email" name="bskudo_recipient_email" class="bskudo-input" required autocomplete="off">
					</p>
				</div>

				<?php if ( $enable_send_to_self ) : ?>
					<p class="bskudo-field bskudo-field--toggle">
						<label class="bskudo-toggle" for="bskudo-send-to-self">
							<input type="checkbox" id="bskudo-send-to-self" name="bskudo_send_to_self" value="1" class="bskudo-send-to-self">
							<span class="bskudo-toggle__track" aria-hidden="true">
								<span class="bskudo-toggle__thumb"></span>
							</span>
							<span class="bskudo-toggle__label"><?php esc_html_e( 'Karte an mich selbst senden', 'bs-kudo-karten' ); ?></span>
						</label>
						<span class="bskudo-field__hint"><?php esc_html_e( 'Die Karte geht an deine eigene E-Mail-Adresse.', 'bs-kudo-karten' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( $enable_delayed_send ) : ?>
					<fieldset class="bskudo-send-timing">
						<legend class="bskudo-send-timing__legend"><?php esc_html_e( 'Wann soll versendet werden?', 'bs-kudo-karten' ); ?></legend>
						<div class="bskudo-send-cards">
							<p class="bskudo-field bskudo-field--radio-card">
								<label class="bskudo-radio-card">
									<input type="radio" name="bskudo_send_mode" value="now" class="bskudo-send-mode" checked>
									<span class="bskudo-radio-card__body">
										<span class="bskudo-radio-card__title"><?php esc_html_e( 'Sofort', 'bs-kudo-karten' ); ?></span>
										<span class="bskudo-radio-card__desc"><?php esc_html_e( 'Die Karte geht direkt auf die Reise.', 'bs-kudo-karten' ); ?></span>
									</span>
								</label>
							</p>
							<p class="bskudo-field bskudo-field--radio-card">
								<label class="bskudo-radio-card">
									<input type="radio" name="bskudo_send_mode" value="later" class="bskudo-send-mode">
									<span class="bskudo-radio-card__body">
										<span class="bskudo-radio-card__title"><?php esc_html_e( 'Später senden', 'bs-kudo-karten' ); ?></span>
										<span class="bskudo-radio-card__desc"><?php esc_html_e( 'Zu einem gewählten Zeitpunkt.', 'bs-kudo-karten' ); ?></span>
									</span>
								</label>
							</p>
						</div>
						<p class="bskudo-field bskudo-schedule-field" hidden>
							<label for="bskudo-send-at"><?php esc_html_e( 'Datum und Uhrzeit', 'bs-kudo-karten' ); ?></label>
							<input type="datetime-local" id="bskudo-send-at" name="bskudo_send_at" class="bskudo-input bskudo-input--datetime">
						</p>
					</fieldset>
				<?php else : ?>
					<input type="hidden" name="bskudo_send_mode" value="now">
				<?php endif; ?>

				<p class="bskudo-privacy">
					<span class="bskudo-privacy__icon" aria-hidden="true">ℹ️</span>
					<span class="bskudo-privacy__text"><?php echo esc_html( $privacy_text ); ?></span>
				</p>

				<?php // Honeypot – für Bots unsichtbar. ?>
				<input
					type="text"
					name="bskudo_hp"
					class="bskudo-honeypot"
					value=""
					tabindex="-1"
					autocomplete="off"
					aria-hidden="true"
				>
				<input type="hidden" name="bskudo_form_ts" class="bskudo-wizard__form-ts" value="">

				<div class="bskudo-feedback" hidden aria-live="polite"></div>

				<div class="bskudo-wizard__nav">
					<button type="button" class="bskudo-btn bskudo-btn--back" data-goto-section="2">
						<?php esc_html_e( 'Zurück', 'bs-kudo-karten' ); ?>
					</button>
					<button type="submit" class="bskudo-btn bskudo-btn--primary bskudo-btn--submit" disabled>
						<?php esc_html_e( 'Kudo-Karte senden', 'bs-kudo-karten' ); ?>
					</button>
				</div>
			</section>
		<?php endif; ?>

		<dialog class="bskudo-lightbox" aria-labelledby="bskudo-lightbox-title">
			<div class="bskudo-lightbox__panel">
				<header class="bskudo-lightbox__header">
					<h3 id="bskudo-lightbox-title" class="bskudo-lightbox__title">
						<?php esc_html_e( 'Große Vorschau deiner Kudo-Karte', 'bs-kudo-karten' ); ?>
					</h3>
					<button type="button" class="bskudo-lightbox__close">
						<?php esc_html_e( 'Schließen', 'bs-kudo-karten' ); ?>
					</button>
				</header>
				<div class="bskudo-lightbox__body">
					<div class="bskudo-preview-duo bskudo-preview-duo--large" aria-live="polite">
						<div class="bskudo-preview-side">
							<p class="bskudo-preview-side__label"><?php esc_html_e( 'Vorderseite', 'bs-kudo-karten' ); ?></p>
							<div class="bskudo-lightbox__card bskudo-card bskudo-card--natural bskudo-card--msg-center">
								<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
									<span class="bskudo-card__face bskudo-card__face--front">
										<span class="bskudo-card__canvas">
											<img class="bskudo-lightbox__image bskudo-card__image" src="" alt="" hidden>
											<span class="bskudo-lightbox__placeholder bskudo-card__placeholder" hidden></span>
											<span class="bskudo-card__message-zone">
												<span class="bskudo-card__message bskudo-lightbox__message"></span>
											</span>
										</span>
									</span>
								</span>
							</div>
						</div>
						<div class="bskudo-preview-side">
							<p class="bskudo-preview-side__label"><?php esc_html_e( 'Rückseite (Branding)', 'bs-kudo-karten' ); ?></p>
							<div class="bskudo-lightbox__card bskudo-card bskudo-card--natural bskudo-card--back-only">
								<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
									<span class="bskudo-card__face bskudo-card__face--back">
										<span class="bskudo-card__branding bskudo-lightbox__branding"></span>
									</span>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</dialog>
	</form>
</div>
