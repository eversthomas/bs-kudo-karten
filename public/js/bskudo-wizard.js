/**
 * BS Kudo Karten – Wizard (Phase 3)
 */
(function () {
	'use strict';

	var config = window.bskudoWizard || {
		maxChars: 160,
		cards: [],
		textbausteine: [],
		i18n: {},
	};

	window.bskudo = window.bskudo || {};

	/**
	 * Zeichenanzahl (Unicode-sicher wenn möglich).
	 *
	 * @param {string} text Text.
	 * @return {number}
	 */
	function countChars(text) {
		if (typeof text.length === 'undefined') {
			return 0;
		}
		return text.length;
	}

	/**
	 * Text auf Zeichenlimit kürzen.
	 *
	 * @param {string} text Text.
	 * @param {number} max Maximale Länge.
	 * @return {string}
	 */
	function truncate(text, max) {
		if (countChars(text) <= max) {
			return text;
		}
		return text.slice(0, max);
	}

	/**
	 * E-Mail grob prüfen.
	 *
	 * @param {string} email Adresse.
	 * @return {boolean}
	 */
	function isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	/**
	 * Wizard-Instanz initialisieren.
	 *
	 * @param {HTMLElement} root Root-Element.
	 */
	function initWizard(root) {
		var maxChars = parseInt(root.getAttribute('data-max-chars'), 10) || config.maxChars || 160;
		var form = root.querySelector('.bskudo-wizard__form');
		var steps = root.querySelectorAll('.bskudo-step');
		var stepLabels = root.querySelectorAll('.bskudo-wizard__step-label');
		var cardIdInput = root.querySelector('.bskudo-wizard__card-id');
		var messageHidden = root.querySelector('.bskudo-wizard__message-hidden');
		var statusEl = root.querySelector('.bskudo-selection-status');
		var cards = root.querySelectorAll('.bskudo-card:not(.bskudo-card--preview)');
		var btnNextStep1 = root.querySelector('.bskudo-step--1 .bskudo-btn--next');
		var messageField = root.querySelector('.bskudo-message');
		var charCurrent = root.querySelector('.bskudo-char-count__current');
		var btnNextStep2 = root.querySelector('.bskudo-step--2 .bskudo-btn--next');
		var impulsesList = root.querySelector('.bskudo-impulses__list');
		var impulsesEmpty = root.querySelector('.bskudo-impulses__empty');
		var previewCard = root.querySelector('.bskudo-preview__card');
		var previewImage = root.querySelector('.bskudo-preview__image');
		var previewPlaceholder = root.querySelector('.bskudo-preview__placeholder');
		var previewMessage = root.querySelector('.bskudo-preview__message');
		var submitBtn = root.querySelector('.bskudo-btn--submit');

		var state = {
			step: 1,
			cardId: 0,
			cardData: null,
			message: '',
		};

		/**
		 * Schritt wechseln.
		 *
		 * @param {number} step Ziel-Schritt.
		 */
		function goToStep(step) {
			state.step = step;
			root.setAttribute('data-step', String(step));

			steps.forEach(function (section) {
				var sectionStep = parseInt(section.getAttribute('data-step'), 10);
				var isActive = sectionStep === step;
				section.classList.toggle('bskudo-step--active', isActive);
				section.classList.toggle('bskudo-step--hidden', !isActive);
				section.hidden = !isActive;
			});

			stepLabels.forEach(function (label) {
				var labelStep = parseInt(label.getAttribute('data-step'), 10);
				label.classList.toggle('bskudo-wizard__step-label--active', labelStep === step);
				label.classList.toggle('bskudo-wizard__step-label--disabled', labelStep > step);
			});

			if (step === 2) {
				updatePreview();
				renderImpulses();
			}
		}

		/**
		 * Karten-Daten aus Konfiguration holen.
		 *
		 * @param {number} cardId ID.
		 * @return {object|null}
		 */
		function getCardById(cardId) {
			var found = null;
			(config.cards || []).forEach(function (card) {
				if (card.id === cardId) {
					found = card;
				}
			});
			return found;
		}

		/**
		 * Passende Textbausteine für die gewählte Karte.
		 *
		 * @return {Array}
		 */
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

		/**
		 * Textimpulse als Buttons rendern.
		 */
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

		/**
		 * Live-Vorschau aktualisieren.
		 */
		function updatePreview() {
			if (!previewCard || !state.cardData) {
				return;
			}

			var data = state.cardData;
			var iconClass = 'bskudo-card--icon-' + (data.iconPosition || 'center');

			previewCard.className = 'bskudo-preview__card bskudo-card bskudo-card--preview is-flipped ' + iconClass;
			previewCard.style.setProperty('--bskudo-accent', data.accentColor || '#c45c3e');

			if (data.imageUrl && previewImage) {
				previewImage.src = data.imageUrl;
				previewImage.alt = data.imageAlt || data.title || '';
				previewImage.hidden = false;
				if (previewPlaceholder) {
					previewPlaceholder.hidden = true;
				}
			} else if (previewPlaceholder) {
				previewPlaceholder.textContent = data.title || '';
				previewPlaceholder.hidden = false;
				if (previewImage) {
					previewImage.hidden = true;
				}
			}

			if (previewMessage) {
				var displayText = state.message || config.i18n.enterMessage || '…';
				previewMessage.textContent = displayText;
			}
		}

		/**
		 * Nachricht setzen und UI synchronisieren.
		 *
		 * @param {string} text Text.
		 */
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

		/**
		 * Zeichenzähler aktualisieren.
		 */
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

		/**
		 * Weiter-Button Schritt 2 aktivieren/deaktivieren.
		 */
		function updateStep2NextButton() {
			if (btnNextStep2) {
				btnNextStep2.disabled = countChars(state.message.trim()) === 0;
			}
		}

		/**
		 * Karte auswählen.
		 *
		 * @param {HTMLButtonElement} selectedButton Button.
		 */
		function selectCard(selectedButton) {
			var cardId = parseInt(selectedButton.getAttribute('data-card-id'), 10);
			state.cardId = cardId;
			state.cardData = getCardById(cardId);

			if (!state.cardData) {
				state.cardData = {
					id: cardId,
					title: selectedButton.getAttribute('data-card-title') || '',
					imageUrl: selectedButton.getAttribute('data-image-url') || '',
					imageAlt: selectedButton.getAttribute('data-image-alt') || '',
					accentColor: selectedButton.getAttribute('data-accent-color') || '#c45c3e',
					iconPosition: selectedButton.getAttribute('data-icon-position') || 'center',
					setIds: [],
				};
			}

			cards.forEach(function (card) {
				var isSelected = card === selectedButton;
				card.classList.toggle('bskudo-card--selected', isSelected);
				card.classList.toggle('is-flipped', isSelected);
				card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
			});

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

		/* Schritt 1: Kartenauswahl */
		cards.forEach(function (card) {
			card.addEventListener('click', function () {
				if (!card.classList.contains('bskudo-card--selected')) {
					selectCard(card);
				}
			});

			card.addEventListener('keydown', function (event) {
				if (event.key !== 'Enter' && event.key !== ' ') {
					return;
				}
				event.preventDefault();
				selectCard(card);
			});
		});

		/* Navigation */
		root.querySelectorAll('[data-goto]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = parseInt(btn.getAttribute('data-goto'), 10);

				if (target === 2 && !state.cardId) {
					if (statusEl) {
						statusEl.textContent = config.i18n.selectCard || '';
					}
					return;
				}

				if (target === 3 && countChars(state.message.trim()) === 0) {
					return;
				}

				goToStep(target);
			});
		});

		/* Schritt 2: Text */
		if (messageField) {
			messageField.addEventListener('input', function () {
				setMessage(messageField.value);
			});
		}

		/* Schritt 3: Formular (Versand folgt Phase 4) */
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
				var valid = true;

				requiredFields.forEach(function (input) {
					if (!input.value.trim()) {
						valid = false;
					}
				});

				var senderEmail = root.querySelector('#bskudo-sender-email');
				var recipientEmail = root.querySelector('#bskudo-recipient-email');

				if (senderEmail && !isValidEmail(senderEmail.value.trim())) {
					valid = false;
				}
				if (recipientEmail && !isValidEmail(recipientEmail.value.trim())) {
					valid = false;
				}

				/* Submit bleibt bis Phase 4 deaktiviert. */
				if (submitBtn) {
					submitBtn.disabled = true;
				}
			});
		});
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
