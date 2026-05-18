/**
 * BS Kudo Karten – Wizard
 */
(function () {
	'use strict';

	var config = window.bskudoWizard || {
		maxChars: 160,
		brandingBack: '',
		cards: [],
		textbausteine: [],
		i18n: {},
	};

	window.bskudo = window.bskudo || {};

	function countChars(text) {
		return text ? text.length : 0;
	}

	function truncate(text, max) {
		if (countChars(text) <= max) {
			return text;
		}
		return text.slice(0, max);
	}

	function isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	/**
	 * Branding-Text für die gewählte Karte (optional pro Karte überschreibbar).
	 *
	 * @param {object|null} cardData Karten-Daten.
	 * @return {string}
	 */
	function getBrandingText(cardData) {
		if (cardData && cardData.backBranding) {
			return cardData.backBranding;
		}
		return config.brandingBack || '';
	}

	/**
	 * Seitenverhältnis der Karte setzen (aus Bildmetadaten).
	 *
	 * @param {HTMLElement|null} cardRoot Karten-Root.
	 * @param {number} width  Bildbreite.
	 * @param {number} height Bildhöhe.
	 */
	function applyCardAspect(cardRoot, width, height) {
		var w = parseInt(width, 10);
		var h = parseInt(height, 10);
		if (!cardRoot || w <= 0 || h <= 0) {
			return;
		}
		cardRoot.style.setProperty('--bskudo-aspect-ratio', w + ' / ' + h);
	}

	/**
	 * Bild laden und Seitenverhältnis übernehmen.
	 *
	 * @param {HTMLImageElement|null} img       Bild-Element.
	 * @param {HTMLElement|null}      cardRoot  Karten-Root.
	 * @param {object|null}           cardData  Karten-Daten.
	 */
	function bindImageAspect(img, cardRoot, cardData) {
		if (!img || !cardRoot) {
			return;
		}

		var apply = function () {
			var w = img.naturalWidth || (cardData && cardData.imageWidth) || 0;
			var h = img.naturalHeight || (cardData && cardData.imageHeight) || 0;
			applyCardAspect(cardRoot, w, h);
		};

		if (cardData && cardData.imageWidth && cardData.imageHeight) {
			applyCardAspect(cardRoot, cardData.imageWidth, cardData.imageHeight);
		}

		if (img.complete && img.naturalWidth) {
			apply();
		} else {
			img.addEventListener('load', apply, { once: true });
		}

		var fitScope = cardRoot && cardRoot.closest('.bskudo-wizard');
		if (fitScope) {
			img.addEventListener(
				'load',
				function () {
					scheduleFitMessages(fitScope);
				},
				{ once: true }
			);
		}
	}

	/**
	 * Rückseiten-Vorschau an Vorderseiten-Maße koppeln.
	 *
	 * @param {HTMLElement} root Wizard-Root.
	 */
	function syncBackPreviewAspect(root) {
		var frontCard = root.querySelector(
			'.bskudo-step--2 .bskudo-preview-duo:not(.bskudo-preview-duo--large) .bskudo-card--natural:not(.bskudo-card--back-only)'
		);
		var backCard = root.querySelector(
			'.bskudo-step--2 .bskudo-preview-duo:not(.bskudo-preview-duo--large) .bskudo-card--back-only'
		);
		if (!frontCard || !backCard) {
			return;
		}
		var ratio = frontCard.style.getPropertyValue('--bskudo-aspect-ratio');
		if (ratio) {
			backCard.style.setProperty('--bskudo-aspect-ratio', ratio);
		}
	}

	/**
	 * Schriftgröße anpassen, damit der gesamte Text (max. 2 Zeilen) in die Zone passt.
	 *
	 * @param {HTMLElement} messageEl Text-Element.
	 */
	function fitCardMessage(messageEl) {
		if (!messageEl) {
			return;
		}

		var zone = messageEl.closest('.bskudo-card__message-zone');
		if (!zone) {
			return;
		}

		var text = messageEl.textContent.replace(/\s+/g, ' ').trim();
		var placeholder = (config.i18n.enterMessage || '…').trim();

		if (!text || text === placeholder) {
			messageEl.style.fontSize = '';
			return;
		}

		var zoneW = zone.clientWidth;
		var zoneH = zone.clientHeight;
		if (zoneW < 8 || zoneH < 8) {
			return;
		}

		var lineHeight = 1.25;
		var maxLines = 2;
		var minSize = 6;
		var maxByHeight = (zoneH / (lineHeight * maxLines)) * 0.9;
		var maxSize = Math.max(minSize, Math.min(maxByHeight, zoneW * 0.18, 80));

		messageEl.style.lineHeight = String(lineHeight);
		messageEl.style.display = 'block';
		messageEl.style.overflow = 'hidden';
		messageEl.style.fontSize = minSize + 'px';

		var fits = function (fontSize) {
			messageEl.style.fontSize = fontSize + 'px';
			var maxHeight = fontSize * lineHeight * maxLines + 2;
			return messageEl.scrollHeight <= maxHeight + 1 && messageEl.scrollWidth <= zoneW + 1;
		};

		// Größte passende Schrift suchen (skaliert mit Zonengröße – auch in der Lightbox).
		var low = minSize;
		var high = maxSize;
		var best = minSize;

		while (low <= high) {
			var mid = Math.round(((low + high) / 2) * 2) / 2;

			if (fits(mid)) {
				best = mid;
				low = mid + 0.5;
			} else {
				high = mid - 0.5;
			}
		}

		messageEl.style.fontSize = best + 'px';
	}

	/**
	 * Nach Layout-Update alle Kartentexte skalieren.
	 *
	 * @param {HTMLElement} scope Container.
	 * @param {boolean}   retry Nach kurzer Verzögerung erneut messen (z. B. Lightbox).
	 */
	function scheduleFitMessages(scope, retry) {
		var container = scope || document;

		var run = function () {
			container.querySelectorAll('.bskudo-card__message').forEach(fitCardMessage);
		};

		requestAnimationFrame(function () {
			requestAnimationFrame(run);
		});

		if (retry) {
			setTimeout(run, 80);
			setTimeout(run, 250);
		}
	}

	function initWizard(root) {
		var maxChars = parseInt(root.getAttribute('data-max-chars'), 10) || config.maxChars || 160;
		var form = root.querySelector('.bskudo-wizard__form');
		var steps = root.querySelectorAll('.bskudo-step');
		var stepButtons = root.querySelectorAll('.bskudo-wizard__step-btn');
		var cardIdInput = root.querySelector('.bskudo-wizard__card-id');
		var messageHidden = root.querySelector('.bskudo-wizard__message-hidden');
		var statusEl = root.querySelector('.bskudo-step--1 .bskudo-selection-status');
		var cards = root.querySelectorAll('.bskudo-card--selectable');
		var btnNextStep1 = root.querySelector('.bskudo-step--1 .bskudo-btn--next');
		var step1PreviewEmpty = root.querySelector('.bskudo-step1-preview__empty');
		var step1PreviewActive = root.querySelector('.bskudo-step1-preview__active');
		var step1HeroCard = root.querySelector('.bskudo-card--hero');
		var step1HeroImage = root.querySelector('.bskudo-step1-preview__image');
		var step1HeroPlaceholder = root.querySelector('.bskudo-step1-preview__placeholder');
		var step1HeroBranding = root.querySelector('.bskudo-step1-preview__branding');
		var step1PreviewTitle = root.querySelector('.bskudo-step1-preview__title');
		var step1FlipBtn = root.querySelector('.bskudo-step1-preview__flip');
		var messageField = root.querySelector('.bskudo-message');
		var charCurrent = root.querySelector('.bskudo-char-count__current');
		var btnNextStep2 = root.querySelector('.bskudo-step--2 .bskudo-btn--next');
		var impulsesList = root.querySelector('.bskudo-impulses__list');
		var impulsesEmpty = root.querySelector('.bskudo-impulses__empty');
		var previewCardFront = root.querySelector(
			'.bskudo-step--2 .bskudo-preview-duo:not(.bskudo-preview-duo--large) .bskudo-card--natural:not(.bskudo-card--back-only)'
		);
		var previewCardBack = root.querySelector(
			'.bskudo-step--2 .bskudo-preview-duo:not(.bskudo-preview-duo--large) .bskudo-card--back-only'
		);
		var previewImage = root.querySelector('.bskudo-step--2 .bskudo-preview__image');
		var previewPlaceholder = root.querySelector('.bskudo-step--2 .bskudo-preview__placeholder');
		var previewMessage = root.querySelector('.bskudo-step--2 .bskudo-preview__message');
		var previewBranding = root.querySelector('.bskudo-step--2 .bskudo-preview__branding');
		var previewLargeBtns = root.querySelectorAll('.bskudo-btn--preview-large');
		var lightbox = root.querySelector('.bskudo-lightbox');
		var lbImage = root.querySelector('.bskudo-lightbox__image');
		var lbPlaceholder = root.querySelector('.bskudo-lightbox__placeholder');
		var lbMessage = root.querySelector('.bskudo-lightbox__message');
		var lbBranding = root.querySelector('.bskudo-lightbox__branding');
		var lbFrontCard = root.querySelector('.bskudo-lightbox__card:not(.bskudo-card--back-only)');
		var lbBackCard = root.querySelector('.bskudo-lightbox .bskudo-card--back-only');
		var submitBtn = root.querySelector('.bskudo-btn--submit');

		var state = {
			step: 1,
			maxStep: 1,
			cardId: 0,
			cardData: null,
			message: '',
		};

		/**
		 * Schritt-Navigation aktualisieren (klickbare, bereits besuchte Schritte).
		 */
		function updateStepNav() {
			stepButtons.forEach(function (btn) {
				var btnStep = parseInt(btn.getAttribute('data-step'), 10);
				var reachable = btnStep <= state.maxStep;
				var isActive = btnStep === state.step;

				btn.disabled = !reachable;
				btn.classList.toggle('bskudo-wizard__step-label--active', isActive);
				btn.classList.toggle('bskudo-wizard__step-label--reachable', reachable && !isActive);
				btn.setAttribute('aria-current', isActive ? 'step' : 'false');
			});
		}

		/**
		 * Prüfen, ob ein Vorwärtssprung erlaubt ist.
		 *
		 * @param {number} target Ziel-Schritt.
		 * @return {boolean}
		 */
		function canAdvanceTo(target) {
			if (target <= state.step) {
				return true;
			}
			if (target >= 2 && !state.cardId) {
				if (statusEl) {
					statusEl.textContent = config.i18n.selectCard || '';
				}
				return false;
			}
			if (target >= 3 && countChars(state.message.trim()) === 0) {
				return false;
			}
			return true;
		}

		/**
		 * Schritt wechseln.
		 *
		 * @param {number} step Ziel-Schritt.
		 * @return {boolean}
		 */
		function goToStep(step) {
			if (step < 1 || step > 3) {
				return false;
			}

			if (step > state.step && !canAdvanceTo(step)) {
				return false;
			}

			state.step = step;
			state.maxStep = Math.max(state.maxStep, step);
			root.setAttribute('data-step', String(step));

			steps.forEach(function (section) {
				var sectionStep = parseInt(section.getAttribute('data-step'), 10);
				var isActive = sectionStep === step;
				section.classList.toggle('bskudo-step--active', isActive);
				section.classList.toggle('bskudo-step--hidden', !isActive);
				section.hidden = !isActive;
			});

			updateStepNav();

			if (step === 2) {
				updatePreview();
				renderImpulses();
			}

			return true;
		}

		function getCardById(cardId) {
			var found = null;
			(config.cards || []).forEach(function (card) {
				if (card.id === cardId) {
					found = card;
				}
			});
			return found;
		}

		function getImpulsesForCard() {
			if (!state.cardData) {
				return [];
			}

			var cardId = state.cardData.id;
			var setIds = state.cardData.setIds || [];

			return (config.textbausteine || []).filter(function (item) {
				if (item.cardId && item.cardId === cardId) {
					return true;
				}
				if (item.setIds && item.setIds.length) {
					return item.setIds.some(function (setId) {
						return setIds.indexOf(setId) !== -1;
					});
				}
				if (!item.cardId && (!item.setIds || !item.setIds.length)) {
					return true;
				}
				return false;
			});
		}

		function renderImpulses() {
			if (!impulsesList) {
				return;
			}

			impulsesList.innerHTML = '';
			var impulses = getImpulsesForCard();

			if (!impulses.length) {
				if (impulsesEmpty) {
					impulsesEmpty.textContent = config.i18n.noTextbausteine || '';
					impulsesEmpty.hidden = false;
				}
				return;
			}

			if (impulsesEmpty) {
				impulsesEmpty.hidden = true;
			}

			impulses.forEach(function (item) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'bskudo-impulse';
				btn.setAttribute('role', 'listitem');
				btn.textContent = item.text;
				btn.addEventListener('click', function () {
					setMessage(item.text);
				});
				impulsesList.appendChild(btn);
			});
		}

		function updatePreview() {
			if (!state.cardData) {
				return;
			}

			var data = state.cardData;
			var iconClass = 'bskudo-card--msg-' + (data.iconPosition || 'center');
			var brandingText = getBrandingText(data);

			if (previewCardFront) {
				previewCardFront.className =
					'bskudo-preview__card bskudo-card bskudo-card--preview bskudo-card--natural ' + iconClass;
				previewCardFront.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');
			}

			if (previewCardBack) {
				previewCardBack.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');
			}

			if (data.imageUrl && previewImage) {
				previewImage.src = data.imageUrl;
				previewImage.alt = data.imageAlt || data.title || '';
				previewImage.hidden = false;
				if (previewPlaceholder) {
					previewPlaceholder.hidden = true;
				}
				bindImageAspect(previewImage, previewCardFront, data);
			} else if (previewPlaceholder) {
				previewPlaceholder.textContent = data.title || '';
				previewPlaceholder.hidden = false;
				if (previewImage) {
					previewImage.hidden = true;
				}
			}

			if (previewMessage) {
				previewMessage.textContent = state.message.trim() || (config.i18n.enterMessage || '…');
			}

			if (previewBranding) {
				previewBranding.textContent = brandingText;
			}

			syncBackPreviewAspect(root);
			updateLightboxPreview();
			scheduleFitMessages(root);
		}

		/**
		 * Große Vorschau (Lightbox) mit aktuellen Daten füllen.
		 */
		function updateLightboxPreview() {
			if (!state.cardData) {
				return;
			}

			var data = state.cardData;
			var iconClass = 'bskudo-card--msg-' + (data.iconPosition || 'center');
			var brandingText = getBrandingText(data);
			var displayText = state.message.trim() || (config.i18n.enterMessage || '…');

			if (lbFrontCard) {
				lbFrontCard.className = 'bskudo-lightbox__card bskudo-card bskudo-card--natural ' + iconClass;
				lbFrontCard.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');
			}

			if (lbBackCard) {
				lbBackCard.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');
			}

			if (data.imageUrl && lbImage) {
				lbImage.src = data.imageUrl;
				lbImage.alt = data.imageAlt || data.title || '';
				lbImage.hidden = false;
				if (lbPlaceholder) {
					lbPlaceholder.hidden = true;
				}
				bindImageAspect(lbImage, lbFrontCard, data);
			} else if (lbPlaceholder) {
				lbPlaceholder.textContent = data.title || '';
				lbPlaceholder.hidden = false;
				if (lbImage) {
					lbImage.hidden = true;
				}
			}

			if (lbMessage) {
				lbMessage.textContent = displayText;
			}

			if (lbBranding) {
				lbBranding.textContent = brandingText;
			}

			if (lbFrontCard && previewCardFront) {
				var ratio = previewCardFront.style.getPropertyValue('--bskudo-aspect-ratio');
				if (ratio) {
					lbFrontCard.style.setProperty('--bskudo-aspect-ratio', ratio);
					if (lbBackCard) {
						lbBackCard.style.setProperty('--bskudo-aspect-ratio', ratio);
					}
				}
			}
		}

		function openLightbox() {
			if (!state.cardData || countChars(state.message.trim()) === 0) {
				return;
			}
			updateLightboxPreview();
			if (lightbox && typeof lightbox.showModal === 'function') {
				lightbox.showModal();
				scheduleFitMessages(lightbox, true);
			}
		}

		function setMessage(text) {
			var value = truncate(text, maxChars);
			state.message = value;

			if (messageField) {
				messageField.value = value;
			}
			if (messageHidden) {
				messageHidden.value = value;
			}
			updateCharCount();
			updatePreview();
			updateStep2NextButton();
		}

		function updateCharCount() {
			if (!charCurrent) {
				return;
			}
			var len = countChars(state.message);
			charCurrent.textContent = String(len);

			var countWrap = root.querySelector('.bskudo-char-count');
			if (countWrap) {
				countWrap.classList.toggle('bskudo-char-count--limit', len >= maxChars);
			}
		}

		function updateStep2NextButton() {
			var hasText = countChars(state.message.trim()) > 0;

			if (btnNextStep2) {
				btnNextStep2.disabled = !hasText;
			}

			previewLargeBtns.forEach(function (btn) {
				btn.disabled = !hasText;
			});
		}

		/**
		 * Große Schritt-1-Vorschau aktualisieren.
		 */
		function updateStep1Preview() {
			if (!state.cardData) {
				if (step1PreviewEmpty) {
					step1PreviewEmpty.hidden = false;
				}
				if (step1PreviewActive) {
					step1PreviewActive.hidden = true;
				}
				if (step1PreviewTitle) {
					step1PreviewTitle.textContent = '';
				}
				return;
			}

			var data = state.cardData;
			var brandingText = getBrandingText(data);

			if (step1PreviewEmpty) {
				step1PreviewEmpty.hidden = true;
			}
			if (step1PreviewActive) {
				step1PreviewActive.hidden = false;
			}

			if (step1HeroCard) {
				step1HeroCard.classList.remove('is-flipped');
				step1HeroCard.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');
			}

			if (step1FlipBtn) {
				step1FlipBtn.textContent = config.i18n.showCardBack || 'Rückseite anzeigen';
			}

			if (data.imageUrl && step1HeroImage) {
				step1HeroImage.src = data.imageUrl;
				step1HeroImage.alt = data.imageAlt || data.title || '';
				step1HeroImage.hidden = false;
				if (step1HeroPlaceholder) {
					step1HeroPlaceholder.hidden = true;
				}
			} else if (step1HeroPlaceholder) {
				step1HeroPlaceholder.textContent = data.title || '';
				step1HeroPlaceholder.hidden = false;
				if (step1HeroImage) {
					step1HeroImage.hidden = true;
				}
			}

			if (step1HeroBranding) {
				step1HeroBranding.textContent = brandingText;
			}

			if (step1PreviewTitle) {
				step1PreviewTitle.textContent = data.title || '';
			}

			if (step1HeroImage && step1HeroCard) {
				bindImageAspect(step1HeroImage, step1HeroCard, data);
			}
		}

		function selectCard(selectedButton) {
			var cardId = parseInt(selectedButton.getAttribute('data-card-id'), 10);
			state.cardId = cardId;
			state.cardData = getCardById(cardId);

			if (!state.cardData) {
				state.cardData = {
					id: cardId,
					title: selectedButton.getAttribute('data-card-title') || '',
					imageUrl: selectedButton.getAttribute('data-image-url') || '',
					imageWidth: parseInt(selectedButton.getAttribute('data-image-width'), 10) || 0,
					imageHeight: parseInt(selectedButton.getAttribute('data-image-height'), 10) || 0,
					imageAlt: selectedButton.getAttribute('data-image-alt') || '',
					accentColor: selectedButton.getAttribute('data-accent-color') || '#c45c3e',
					iconPosition: selectedButton.getAttribute('data-icon-position') || 'center',
					backBranding: selectedButton.getAttribute('data-back-branding') || '',
					setIds: [],
				};
			}

			cards.forEach(function (card) {
				var isSelected = card === selectedButton;
				card.classList.toggle('bskudo-card--selected', isSelected);
				card.classList.remove('is-flipped');
				card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
			});

			updateStep1Preview();

			if (cardIdInput) {
				cardIdInput.value = String(cardId);
			}

			if (statusEl) {
				var title = selectedButton.getAttribute('data-card-title') || '';
				statusEl.textContent = (config.i18n.cardSelected || '') + (title ? ': ' + title : '');
			}

			if (btnNextStep1) {
				btnNextStep1.disabled = false;
			}

			root.style.setProperty('--bskudo-accent', state.cardData.accentColor || '#c45c3e');
		}

		cards.forEach(function (card) {
			var thumbW = parseInt(card.getAttribute('data-image-width'), 10);
			var thumbH = parseInt(card.getAttribute('data-image-height'), 10);
			if (thumbW > 0 && thumbH > 0) {
				applyCardAspect(card, thumbW, thumbH);
			}

			card.addEventListener('click', function () {
				selectCard(card);
			});

			card.addEventListener('keydown', function (event) {
				if (event.key !== 'Enter' && event.key !== ' ') {
					return;
				}
				event.preventDefault();
				selectCard(card);
			});
		});

		if (step1FlipBtn && step1HeroCard) {
			step1FlipBtn.addEventListener('click', function () {
				var flipped = step1HeroCard.classList.toggle('is-flipped');
				step1FlipBtn.textContent = flipped
					? (config.i18n.showCardFront || 'Vorderseite anzeigen')
					: (config.i18n.showCardBack || 'Rückseite anzeigen');
			});

			step1HeroCard.addEventListener('click', function () {
				if (!state.cardId) {
					return;
				}
				var flipped = step1HeroCard.classList.toggle('is-flipped');
				if (step1FlipBtn) {
					step1FlipBtn.textContent = flipped
						? (config.i18n.showCardFront || 'Vorderseite anzeigen')
						: (config.i18n.showCardBack || 'Rückseite anzeigen');
				}
			});
		}

		stepButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = parseInt(btn.getAttribute('data-step'), 10);
				goToStep(target);
			});
		});

		root.querySelectorAll('[data-goto]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				goToStep(parseInt(btn.getAttribute('data-goto'), 10));
			});
		});

		if (messageField) {
			messageField.addEventListener('input', function () {
				setMessage(messageField.value);
			});
		}

		previewLargeBtns.forEach(function (btn) {
			btn.addEventListener('click', openLightbox);
		});

		if (lightbox) {
			var closeBtn = lightbox.querySelector('.bskudo-lightbox__close');
			if (closeBtn) {
				closeBtn.addEventListener('click', function () {
					lightbox.close();
				});
			}
			lightbox.addEventListener('click', function (event) {
				if (event.target === lightbox) {
					lightbox.close();
				}
			});
		}

		var previewWrap = root.querySelector('.bskudo-step--2 .bskudo-preview-wrap');
		if (previewWrap && typeof ResizeObserver !== 'undefined') {
			var resizeTimer;
			var observer = new ResizeObserver(function () {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function () {
					scheduleFitMessages(root);
				}, 80);
			});
			observer.observe(previewWrap);
			if (lightbox) {
				observer.observe(lightbox);
			}
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				if (submitBtn && !submitBtn.disabled) {
					window.alert(config.i18n.sendSoon || '');
				}
			});
		}

		var requiredFields = root.querySelectorAll(
			'#bskudo-sender-name, #bskudo-sender-email, #bskudo-recipient-name, #bskudo-recipient-email'
		);

		requiredFields.forEach(function (field) {
			field.addEventListener('input', function () {
				if (submitBtn) {
					submitBtn.disabled = true;
				}
			});
		});

		updateStepNav();
	}

	function initAll() {
		document.querySelectorAll('.bskudo-wizard').forEach(initWizard);
	}

	window.bskudo.initWizard = initWizard;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
