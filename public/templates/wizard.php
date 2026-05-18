<?php
/**
 * Wizard-Template (Phase 3: alle Schritte).
 *
 * @package BSKudo
 *
 * @var array<int, array<string, mixed>> $cards         Karten-Daten.
 * @var int                              $char_limit    Zeichenlimit.
 * @var string                           $privacy_text  Datenschutzhinweis.
 * @var string                           $branding_back Standard-Branding Rückseite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards         = isset( $cards ) && is_array( $cards ) ? $cards : array();
$char_limit    = isset( $char_limit ) ? (int) $char_limit : 160;
$privacy_text  = isset( $privacy_text ) ? (string) $privacy_text : '';
$branding_back = isset( $branding_back ) ? (string) $branding_back : '';
$has_cards     = ! empty( $cards );
?>
<div class="bskudo-wizard" data-step="1" data-max-chars="<?php echo esc_attr( (string) $char_limit ); ?>">
	<form class="bskudo-wizard__form" novalidate>
		<?php wp_nonce_field( 'bskudo_send_kudo', 'bskudo_nonce' ); ?>

		<input type="hidden" name="bskudo_card_id" class="bskudo-wizard__card-id" value="">
		<input type="hidden" name="bskudo_message" class="bskudo-wizard__message-hidden" value="">

		<ol class="bskudo-wizard__steps" aria-label="<?php esc_attr_e( 'Fortschritt', 'bs-kudo-karten' ); ?>">
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label bskudo-wizard__step-label--active" data-step="1" aria-current="step">
					<?php esc_html_e( 'Karte wählen', 'bs-kudo-karten' ); ?>
				</button>
			</li>
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label" data-step="2" disabled>
					<?php esc_html_e( 'Text', 'bs-kudo-karten' ); ?>
				</button>
			</li>
			<li class="bskudo-wizard__step-item">
				<button type="button" class="bskudo-wizard__step-btn bskudo-wizard__step-label" data-step="3" disabled>
					<?php esc_html_e( 'Versenden', 'bs-kudo-karten' ); ?>
				</button>
			</li>
		</ol>

		<!-- Schritt 1: Kartenauswahl -->
		<section
			class="bskudo-step bskudo-step--1 bskudo-step--active"
			data-step="1"
			aria-labelledby="bskudo-step-1-heading"
		>
			<h2 id="bskudo-step-1-heading" class="bskudo-step__heading">
				<?php esc_html_e( 'Wähle deine Kudo-Karte', 'bs-kudo-karten' ); ?>
			</h2>
			<?php if ( ! $has_cards ) : ?>
				<p class="bskudo-empty">
					<?php esc_html_e( 'Aktuell sind keine Karten verfügbar. Bitte lege im Backend Kudo-Karten an und veröffentliche sie.', 'bs-kudo-karten' ); ?>
				</p>
			<?php else : ?>
				<div class="bskudo-step1-layout">
					<aside class="bskudo-step1-aside" aria-label="<?php esc_attr_e( 'Vorschau der gewählten Karte', 'bs-kudo-karten' ); ?>">
						<div class="bskudo-step1-preview">
							<div class="bskudo-step1-preview__empty">
								<span class="bskudo-step1-preview__empty-icon" aria-hidden="true">♥</span>
								<p><?php esc_html_e( 'Wähle eine Karte aus – sie erscheint hier in großer Ansicht.', 'bs-kudo-karten' ); ?></p>
							</div>
							<div class="bskudo-step1-preview__active" hidden>
								<div class="bskudo-card bskudo-card--hero bskudo-card--natural bskudo-card--msg-center">
									<span class="bskudo-card__inner">
										<span class="bskudo-card__face bskudo-card__face--front">
											<span class="bskudo-card__canvas">
												<img class="bskudo-step1-preview__image bskudo-card__image" src="" alt="" hidden>
												<span class="bskudo-step1-preview__placeholder bskudo-card__placeholder" hidden></span>
											</span>
										</span>
										<span class="bskudo-card__face bskudo-card__face--back">
											<span class="bskudo-card__branding bskudo-step1-preview__branding"></span>
										</span>
									</span>
								</div>
								<button type="button" class="bskudo-step1-preview__flip">
									<?php esc_html_e( 'Rückseite anzeigen', 'bs-kudo-karten' ); ?>
								</button>
							</div>
						</div>
						<p class="bskudo-step1-preview__title" aria-live="polite"></p>
						<p class="bskudo-selection-status" aria-live="polite"></p>
						<button type="button" class="bskudo-btn bskudo-btn--primary bskudo-btn--next bskudo-btn--block" data-goto="2" disabled>
							<?php esc_html_e( 'Weiter zum Text', 'bs-kudo-karten' ); ?>
						</button>
					</aside>

					<div class="bskudo-step1-picker">
						<p class="bskudo-step1-picker__label"><?php esc_html_e( 'Karten zur Auswahl', 'bs-kudo-karten' ); ?></p>
						<p class="bskudo-step__hint bskudo-step1-picker__hint"><?php esc_html_e( 'Tippe eine Karte an – die große Vorschau aktualisiert sich sofort.', 'bs-kudo-karten' ); ?></p>
						<div class="bskudo-grid bskudo-grid--thumbs" role="list">
					<?php foreach ( $cards as $card ) : ?>
						<?php
						$card_id         = (int) $card['id'];
						$icon_position   = esc_attr( (string) $card['icon_position'] );
						$accent_color    = esc_attr( (string) $card['accent_color'] );
						$image_url       = esc_url( (string) $card['image_url'] );
						$image_alt       = '' !== (string) $card['image_alt']
							? esc_attr( (string) $card['image_alt'] )
							: esc_attr( (string) $card['title'] );
						$card_branding   = '' !== (string) ( $card['back_branding'] ?? '' )
							? (string) $card['back_branding']
							: $branding_back;
						$message_position = 'bskudo-card--msg-' . $icon_position;
						?>
						<button
							type="button"
							class="bskudo-card bskudo-card--selectable <?php echo esc_attr( $message_position ); ?>"
							role="listitem"
							data-card-id="<?php echo esc_attr( (string) $card_id ); ?>"
							data-card-title="<?php echo esc_attr( (string) $card['title'] ); ?>"
							data-accent-color="<?php echo $accent_color; ?>"
							data-icon-position="<?php echo $icon_position; ?>"
							data-image-url="<?php echo $image_url; ?>"
							data-image-alt="<?php echo $image_alt; ?>"
							data-back-branding="<?php echo esc_attr( $card_branding ); ?>"
							data-image-width="<?php echo esc_attr( (string) (int) ( $card['image_width'] ?? 0 ) ); ?>"
							data-image-height="<?php echo esc_attr( (string) (int) ( $card['image_height'] ?? 0 ) ); ?>"
							aria-pressed="false"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %s: card title */ __( 'Karte: %s', 'bs-kudo-karten' ), (string) $card['title'] ) ); ?>"
							style="--bskudo-accent: <?php echo $accent_color; ?>;"
						>
							<span class="bskudo-card__inner">
								<span class="bskudo-card__face bskudo-card__face--front">
									<?php if ( $image_url ) : ?>
										<img
											class="bskudo-card__image"
											src="<?php echo $image_url; ?>"
											alt="<?php echo $image_alt; ?>"
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
							<span class="bskudo-card__title bskudo-sr-only"><?php echo esc_html( (string) $card['title'] ); ?></span>
						</button>
					<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $has_cards ) : ?>
			<!-- Schritt 2: Text & Live-Vorschau -->
			<section
				class="bskudo-step bskudo-step--2 bskudo-step--hidden"
				data-step="2"
				aria-labelledby="bskudo-step-2-heading"
				hidden
			>
				<h2 id="bskudo-step-2-heading" class="bskudo-step__heading">
					<?php esc_html_e( 'Schreibe deinen Kudo-Text', 'bs-kudo-karten' ); ?>
				</h2>
				<p class="bskudo-step__hint">
					<?php esc_html_e( 'Dein Text erscheint auf der Vorderseite der Karte. Die Rückseite zeigt das Branding – in der E-Mail beide Seiten nebeneinander.', 'bs-kudo-karten' ); ?>
				</p>

				<div class="bskudo-step2-layout">
					<div class="bskudo-preview-wrap">
						<p class="bskudo-preview-label"><?php esc_html_e( 'Live-Vorschau', 'bs-kudo-karten' ); ?></p>
						<div class="bskudo-preview-duo" aria-live="polite">
							<div class="bskudo-preview-side">
								<p class="bskudo-preview-side__label"><?php esc_html_e( 'Vorderseite', 'bs-kudo-karten' ); ?></p>
								<div class="bskudo-preview__card bskudo-card bskudo-card--preview bskudo-card--natural bskudo-card--msg-center">
									<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
										<span class="bskudo-card__face bskudo-card__face--front">
											<span class="bskudo-card__canvas">
												<img class="bskudo-preview__image bskudo-card__image" src="" alt="" hidden>
												<span class="bskudo-preview__placeholder bskudo-card__placeholder" hidden></span>
												<span class="bskudo-card__message-zone">
													<span class="bskudo-card__message bskudo-preview__message"></span>
												</span>
											</span>
										</span>
									</span>
								</div>
							</div>
							<div class="bskudo-preview-side">
								<p class="bskudo-preview-side__label"><?php esc_html_e( 'Rückseite (Branding)', 'bs-kudo-karten' ); ?></p>
								<div class="bskudo-preview__card bskudo-card bskudo-card--preview bskudo-card--natural bskudo-card--back-only">
									<span class="bskudo-card__inner bskudo-card__inner--static bskudo-card__inner--fit">
										<span class="bskudo-card__face bskudo-card__face--back">
											<span class="bskudo-card__branding bskudo-preview__branding"><?php echo esc_html( $branding_back ); ?></span>
										</span>
									</span>
								</div>
							</div>
						</div>
						<button type="button" class="bskudo-btn bskudo-btn--secondary bskudo-btn--preview-large" disabled>
							<?php esc_html_e( 'Große Vorschau', 'bs-kudo-karten' ); ?>
						</button>
					</div>

					<div class="bskudo-text-panel">
						<div class="bskudo-impulses">
							<p class="bskudo-impulses__label"><?php esc_html_e( 'Textimpulse', 'bs-kudo-karten' ); ?></p>
							<div class="bskudo-impulses__list" role="list"></div>
							<p class="bskudo-impulses__empty" hidden></p>
						</div>

						<label for="bskudo-message" class="bskudo-field-label">
							<?php esc_html_e( 'Dein Text (Vorderseite)', 'bs-kudo-karten' ); ?>
						</label>
						<textarea
							id="bskudo-message"
							class="bskudo-message"
							name="bskudo_message_visible"
							rows="2"
							maxlength="<?php echo esc_attr( (string) $char_limit ); ?>"
							placeholder="<?php esc_attr_e( 'Schreibe hier deine Botschaft …', 'bs-kudo-karten' ); ?>"
						></textarea>
						<p class="bskudo-char-count" aria-live="polite">
							<span class="bskudo-char-count__current">0</span>
							/
							<span class="bskudo-char-count__max"><?php echo esc_html( (string) $char_limit ); ?></span>
						</p>
					</div>
				</div>

				<div class="bskudo-wizard__nav bskudo-wizard__nav--split">
					<button type="button" class="bskudo-btn bskudo-btn--back" data-goto="1">
						<?php esc_html_e( 'Zurück', 'bs-kudo-karten' ); ?>
					</button>
					<div class="bskudo-wizard__nav-group">
						<button type="button" class="bskudo-btn bskudo-btn--secondary bskudo-btn--preview-large bskudo-btn--preview-large-nav" disabled>
							<?php esc_html_e( 'Große Vorschau', 'bs-kudo-karten' ); ?>
						</button>
						<button type="button" class="bskudo-btn bskudo-btn--primary bskudo-btn--next" data-goto="3" disabled>
							<?php esc_html_e( 'Weiter', 'bs-kudo-karten' ); ?>
						</button>
					</div>
				</div>
			</section>

			<!-- Schritt 3: Absender/Empfänger (Versand in Phase 4) -->
			<section
				class="bskudo-step bskudo-step--3 bskudo-step--hidden"
				data-step="3"
				aria-labelledby="bskudo-step-3-heading"
				hidden
			>
				<h2 id="bskudo-step-3-heading" class="bskudo-step__heading">
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

				<p class="bskudo-privacy"><?php echo esc_html( $privacy_text ); ?></p>

				<?php // Honeypot – Phase 4 (immer aktiv, für Bots unsichtbar). ?>
				<input
					type="text"
					name="bskudo_hp"
					class="bskudo-honeypot"
					value=""
					tabindex="-1"
					autocomplete="off"
					aria-hidden="true"
				>

				<p class="bskudo-send-notice" role="status">
					<?php esc_html_e( 'Der E-Mail-Versand wird in der nächsten Version freigeschaltet.', 'bs-kudo-karten' ); ?>
				</p>

				<div class="bskudo-wizard__nav">
					<button type="button" class="bskudo-btn bskudo-btn--back" data-goto="2">
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
