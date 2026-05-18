/**
 * BS Kudo Karten – Wizard (Sektions-Reveal, contenteditable-Overlay)
 */
(function () {
	'use strict';

	var config = window.bskudoWizard || {
		ajaxUrl: '',
		ajaxAction: 'bskudo_send_kudo',
		nonce: '',
		maxChars: 160,
		brandingBack: '',
		cards: [],
		textbausteine: [],
		i18n: {},
	};

	window.bskudo = window.bskudo || {};

	/* ─── Teil 1: Utilities ───────────────────────────────────────── */

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
	 * @param {number}         width     Bildbreite.
	 * @param {number}         height    Bildhöhe.
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
	}

	/**
	 * Schriftgröße im Overlay anpassen (Binary Search, max. 2 Zeilen).
	 *
	 * @param {HTMLElement} overlay contenteditable-Overlay.
	 */
	function fitCardMessage(overlay) {
		if (!overlay) {
			return;
		}

		var write = overlay.closest('.bskudo-card-write');
		if (!write) {
			return;
		}

		var text = overlay.textContent.replace(/\s+/g, ' ').trim();
		var placeholder = (overlay.getAttribute('data-placeholder') || '').trim();

		if (!text || text === placeholder) {
			overlay.style.fontSize = '';
			return;
		}

		var writeW = write.clientWidth;
		var writeH = write.clientHeight;
		if (writeW < 8 || writeH < 8) {
			return;
		}

		var zoneW = writeW * 0.68;
		var zoneH = writeH * 0.38;
		var lineHeight = 1.25;
		var maxLines = 2;
		var minSize = 6;
		var maxByHeight = (zoneH / (lineHeight * maxLines)) * 0.9;
		var maxSize = Math.max(minSize, Math.min(maxByHeight, zoneW * 0.18, 80));

		overlay.style.lineHeight = String(lineHeight);
		overlay.style.display = 'block';
		overlay.style.overflow = 'hidden';
		overlay.style.fontSize = minSize + 'px';

		var fits = function (fontSize) {
			overlay.style.fontSize = fontSize + 'px';
			var maxHeight = fontSize * lineHeight * maxLines + 2;
			return overlay.scrollHeight <= maxHeight + 1 && overlay.scrollWidth <= zoneW + 1;
		};

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

		overlay.style.fontSize = best + 'px';
	}

	function initWizard(root) {
		var maxChars = parseInt(root.getAttribute('data-max-chars'), 10) || config.maxChars || 160;
		var form = root.querySelector('.bskudo-wizard__form');
		var sections = root.querySelectorAll('.bskudo-section');
		var stepButtons = root.querySelectorAll('.bskudo-wizard__step-btn');
		var cardIdInput = root.querySelector('.bskudo-wizard__card-id');
		var messageHidden = root.querySelector('.bskudo-wizard__message-hidden');
		var cardButtons = root.querySelectorAll('.bskudo-card--selectable');
		var writeImage = root.querySelector('.bskudo-card-write__image');
		var overlay = root.querySelector('.bskudo-card-overlay');
		var charCurrent = root.querySelector('.bskudo-char-count__current');
		var charCountMax = root.querySelector('.bskudo-char-count__max');
		var impulsesList = root.querySelector('.bskudo-impulses__list');
		var impulsesEmpty = root.querySelector('.bskudo-impulses__empty');
		var sec2NextBtn = root.querySelector('.bskudo-section[data-section="2"] .bskudo-btn--next');
		var lightbox = root.querySelector('.bskudo-lightbox');
		var lbImage = root.querySelector('.bskudo-lightbox__image');
		var lbPlaceholder = root.querySelector('.bskudo-lightbox__placeholder');
		var lbMessage = root.querySelector('.bskudo-lightbox__message');
		var lbBranding = root.querySelector('.bskudo-lightbox__branding');
		var lbFrontCard = root.querySelector('.bskudo-lightbox__card:not(.bskudo-card--back-only)');
		var lbBackCard = root.querySelector('.bskudo-lightbox .bskudo-card--back-only');
		var submitBtn = root.querySelector('.bskudo-btn--submit');
		var feedbackEl = root.querySelector('.bskudo-feedback');
		var senderNameField = root.querySelector('#bskudo-sender-name');
		var senderEmailField = root.querySelector('#bskudo-sender-email');
		var recipientNameField = root.querySelector('#bskudo-recipient-name');
		var recipientEmailField = root.querySelector('#bskudo-recipient-email');
		var sendToSelfCheckbox = root.querySelector('#bskudo-send-to-self');
		var sendModeRadios = root.querySelectorAll('.bskudo-send-mode');
		var scheduleFieldWrap = root.querySelector('.bskudo-schedule-field');
		var sendAtField = root.querySelector('#bskudo-send-at');

		/* ─── Teil 2: State ───────────────────────────────────────── */

		var state = {
			section: 1,
			maxSection: 1,
			cardId: 0,
			cardData: null,
			message: '',
			sending: false,
			sent: false,
		};

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

		/* ─── Teil 4: updateSectionNav ────────────────────────────── */

		function updateSectionNav() {
			stepButtons.forEach(function (btn) {
				var btnSection = parseInt(btn.getAttribute('data-step'), 10);
				var reachable = btnSection <= state.maxSection;
				var isActive = btnSection === state.section;

				btn.disabled = !reachable;
				btn.classList.toggle('bskudo-wizard__step-label--active', isActive);
				btn.classList.toggle('bskudo-wizard__step-label--reachable', reachable && !isActive);
				btn.classList.toggle('bskudo-wizard__step-label--done', reachable && btnSection < state.section);
				btn.setAttribute('aria-current', isActive ? 'step' : 'false');
			});
		}

		/**
		 * Prüfen, ob ein Vorwärtssprung erlaubt ist.
		 *
		 * @param {number} target Ziel-Sektion.
		 * @return {boolean}
		 */
		function canAdvanceTo(target) {
			if (target <= state.section) {
				return true;
			}
			if (target >= 2 && !state.cardId) {
				return false;
			}
			if (target >= 3 && countChars(state.message.trim()) === 0) {
				return false;
			}
			return true;
		}

		/* ─── Teil 3: revealSection ───────────────────────────────── */

		/**
		 * Sektion einblenden (ersetzt goToStep).
		 *
		 * @param {number} n Ziel-Sektion 1–3.
		 * @return {boolean}
		 */
		function revealSection(n) {
			if (n < 1 || n > 3) {
				return false;
			}

			if (n > state.section && !canAdvanceTo(n)) {
				return false;
			}

			sections.forEach(function (section) {
				var num = parseInt(section.getAttribute('data-section'), 10);

				section.classList.remove('bskudo-section--active', 'bskudo-section--reveal', 'bskudo-section--done', 'bskudo-section--pending');

				if (num < n) {
					section.hidden = false;
					section.classList.add('bskudo-section--done');
				} else if (num === n) {
					section.hidden = false;
					section.classList.add('bskudo-section--reveal', 'bskudo-section--active');
				} else {
					section.classList.add('bskudo-section--pending');
					section.hidden = true;
				}
			});

			state.section = n;
			state.maxSection = Math.max(state.maxSection, n);
			root.setAttribute('data-section', String(n));

			var activeSection = root.querySelector('.bskudo-section[data-section="' + n + '"]');
			if (activeSection && typeof activeSection.scrollIntoView === 'function') {
				activeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}

			updateSectionNav();

			if (n === 2) {
				updateSection2NextButton();
				if (overlay) {
					fitCardMessage(overlay);
				}
			}

			if (n === 3) {
				validateForm();
			}

			return true;
		}

		function showFeedback(message, isSuccess) {
			if (!feedbackEl) {
				return;
			}

			feedbackEl.textContent = message;
			feedbackEl.hidden = false;
			feedbackEl.classList.remove('bskudo-feedback--success', 'bskudo-feedback--error');
			feedbackEl.classList.add(isSuccess ? 'bskudo-feedback--success' : 'bskudo-feedback--error');
			feedbackEl.setAttribute('role', 'alert');
		}

		function syncSendToSelf() {
			var isSelf = sendToSelfCheckbox && sendToSelfCheckbox.checked;

			if (recipientNameField) {
				recipientNameField.readOnly = isSelf;
				if (isSelf && senderNameField) {
					recipientNameField.value = senderNameField.value;
				}
			}

			if (recipientEmailField) {
				recipientEmailField.readOnly = isSelf;
				if (isSelf && senderEmailField) {
					recipientEmailField.value = senderEmailField.value;
				}
			}
		}

		function isScheduleValid() {
			if (!config.enableDelayedSend || !sendModeRadios.length) {
				return true;
			}

			var modeLater = false;
			sendModeRadios.forEach(function (radio) {
				if (radio.value === 'later' && radio.checked) {
					modeLater = true;
				}
			});

			if (!modeLater) {
				return true;
			}

			if (!sendAtField || !sendAtField.value) {
				return false;
			}

			var selected = new Date(sendAtField.value);
			var minMs = Date.now() + (config.scheduleMinMinutes || 5) * 60 * 1000;

			return selected.getTime() >= minMs;
		}

		function updateSendTimingUi() {
			if (!config.enableDelayedSend || !sendModeRadios.length) {
				return;
			}

			var modeLater = false;
			sendModeRadios.forEach(function (radio) {
				if (radio.value === 'later' && radio.checked) {
					modeLater = true;
				}
			});

			if (scheduleFieldWrap) {
				scheduleFieldWrap.hidden = !modeLater;
			}

			if (submitBtn && !state.sent) {
				submitBtn.textContent = modeLater
					? config.i18n.scheduleLater || 'Später senden'
					: config.i18n.send || 'Kudo-Karte senden';
			}
		}

		function validateForm() {
			if (state.sent) {
				if (submitBtn) {
					submitBtn.disabled = false;
				}
				return true;
			}

			var valid = !!state.cardId && countChars(state.message.trim()) > 0;
			var isSelf = sendToSelfCheckbox && sendToSelfCheckbox.checked;

			if (senderNameField && !senderNameField.value.trim()) {
				valid = false;
			}
			if (senderEmailField && !isValidEmail(senderEmailField.value.trim())) {
				valid = false;
			}

			if (!isSelf) {
				if (recipientNameField && !recipientNameField.value.trim()) {
					valid = false;
				}
				if (recipientEmailField && !isValidEmail(recipientEmailField.value.trim())) {
					valid = false;
				}
			}

			if (!isScheduleValid()) {
				valid = false;
			}

			if (submitBtn) {
				submitBtn.disabled = !valid || state.sending;
			}

			return valid;
		}

		function resetWizardAfterSend() {
			state.sent = false;
			state.sending = false;
			state.section = 1;
			state.maxSection = 1;
			state.cardId = 0;
			state.cardData = null;
			state.message = '';

			if (form) {
				form.reset();
			}

			if (cardIdInput) {
				cardIdInput.value = '';
			}
			if (messageHidden) {
				messageHidden.value = '';
			}
			if (overlay) {
				overlay.textContent = '';
			}

			cardButtons.forEach(function (card) {
				card.classList.remove('bskudo-card--selected');
				card.setAttribute('aria-pressed', 'false');
			});

			if (writeImage) {
				writeImage.removeAttribute('src');
				writeImage.alt = '';
			}

			if (impulsesList) {
				impulsesList.textContent = '';
			}
			if (impulsesEmpty) {
				impulsesEmpty.hidden = true;
			}

			updateCharCount();
			updateSection2NextButton();
			revealSection(1);

			if (feedbackEl) {
				feedbackEl.hidden = true;
				feedbackEl.textContent = '';
				feedbackEl.classList.remove('bskudo-feedback--success', 'bskudo-feedback--error');
			}

			if (submitBtn) {
				submitBtn.textContent = config.i18n.send || 'Kudo-Karte senden';
				submitBtn.disabled = true;
			}
		}

		function sendKudo() {
			if (state.sent) {
				resetWizardAfterSend();
				return;
			}

			if (!validateForm() || state.sending) {
				return;
			}

			if (!config.ajaxUrl) {
				showFeedback(config.i18n.sendError || 'Versand nicht verfügbar.', false);
				return;
			}

			if (messageHidden) {
				messageHidden.value = state.message;
			}

			state.sending = true;
			validateForm();

			if (feedbackEl) {
				feedbackEl.hidden = true;
			}

			var formData = new FormData(form);
			formData.set('action', config.ajaxAction || 'bskudo_send_kudo');

			if (config.nonce && !formData.get('bskudo_nonce')) {
				formData.set('bskudo_nonce', config.nonce);
			}

			var submitLabel = config.i18n.send || 'Kudo-Karte senden';

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = config.i18n.sending || 'Wird gesendet …';
			}

			fetch(config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then(function (response) {
					return response.json().then(function (data) {
						return { ok: response.ok, data: data };
					});
				})
				.then(function (result) {
					var data = result.data;

					if (result.ok && data && data.success) {
						state.sent = true;
						showFeedback(
							(data.data && data.data.message) ||
								'Deine Kudo-Karte ist unterwegs. ✨',
							true
						);

						if (submitBtn) {
							submitBtn.disabled = false;
							submitBtn.textContent = config.i18n.sendAnother || 'Weitere Kudo-Karte senden';
						}

						return;
					}

					showFeedback(
						(data && data.data && data.data.message) ||
							config.i18n.sendError ||
							'Beim Versand ist ein Fehler aufgetreten.',
						false
					);
				})
				.catch(function () {
					showFeedback(
						config.i18n.sendError || 'Beim Versand ist ein Fehler aufgetreten.',
						false
					);
				})
				.finally(function () {
					state.sending = false;

					if (!state.sent && submitBtn) {
						submitBtn.textContent = submitLabel;
						validateForm();
					}
				});
		}

		function renderImpulses() {
			if (!impulsesList) {
				return;
			}

			impulsesList.textContent = '';
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
				lbFrontCard.style.setProperty('--bskudo-accent', data.accentColor || '#335c70');
			}

			if (lbBackCard) {
				lbBackCard.style.setProperty('--bskudo-accent', data.accentColor || '#335c70');
			}

			var imageUrl = data.imageUrlWeb || data.imageUrl;

			if (imageUrl && lbImage) {
				lbImage.src = imageUrl;
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
		}

		/* ─── Teil 9: setMessage ──────────────────────────────────── */

		function setMessage(text) {
			var value = truncate(text, maxChars);
			state.message = value;

			if (overlay) {
				overlay.textContent = value;
			}
			if (messageHidden) {
				messageHidden.value = value;
			}

			updateCharCount();
			fitCardMessage(overlay);
			updateSection2NextButton();
			updateLightboxPreview();
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
				countWrap.classList.toggle('bskudo-char-count--warn', len > 140 && len < maxChars);
			}
		}

		/* ─── Teil 8: updateSection2NextButton ───────────────────── */

		function updateSection2NextButton() {
			var hasText = countChars(state.message.trim()) > 0;

			if (sec2NextBtn) {
				sec2NextBtn.disabled = !hasText;
			}
		}

		/**
		 * Cursor ans Ende des contenteditable setzen.
		 *
		 * @param {HTMLElement} el Overlay-Element.
		 */
		function placeCaretAtEnd(el) {
			if (!el) {
				return;
			}
			el.focus();
			var range = document.createRange();
			range.selectNodeContents(el);
			range.collapse(false);
			var sel = window.getSelection();
			if (sel) {
				sel.removeAllRanges();
				sel.addRange(range);
			}
		}

		/* ─── Teil 5: selectCard ──────────────────────────────────── */

		/**
		 * Karte wählen und zu Sektion 2 wechseln.
		 *
		 * @param {number} cardId Karten-ID.
		 */
		function selectCard(cardId) {
			var selectedButton = null;

			cardButtons.forEach(function (btn) {
				if (parseInt(btn.getAttribute('data-card-id'), 10) === cardId) {
					selectedButton = btn;
				}
			});

			state.cardId = cardId;
			state.cardData = getCardById(cardId);

			if (!state.cardData && selectedButton) {
				state.cardData = {
					id: cardId,
					title: selectedButton.getAttribute('data-card-title') || '',
					imageUrl: selectedButton.getAttribute('data-image-url') || '',
					imageUrlWeb: selectedButton.getAttribute('data-image-url') || '',
					imageWidth: parseInt(selectedButton.getAttribute('data-image-width'), 10) || 0,
					imageHeight: parseInt(selectedButton.getAttribute('data-image-height'), 10) || 0,
					imageAlt: selectedButton.getAttribute('data-image-alt') || '',
					accentColor: selectedButton.getAttribute('data-accent-color') || '#335c70',
					iconPosition: selectedButton.getAttribute('data-icon-position') || 'center',
					backBranding: selectedButton.getAttribute('data-back-branding') || '',
					setIds: [],
				};
			}

			if (state.cardData && !state.cardData.imageUrlWeb) {
				state.cardData.imageUrlWeb = state.cardData.imageUrl || '';
			}

			cardButtons.forEach(function (card) {
				var isSelected = parseInt(card.getAttribute('data-card-id'), 10) === cardId;
				card.classList.toggle('bskudo-card--selected', isSelected);
				card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
			});

			if (cardIdInput) {
				cardIdInput.value = String(cardId);
			}

			var imageUrl = state.cardData ? state.cardData.imageUrlWeb || state.cardData.imageUrl : '';

			if (writeImage && state.cardData) {
				if (imageUrl) {
					writeImage.src = imageUrl;
					writeImage.alt = state.cardData.imageAlt || state.cardData.title || '';
					writeImage.hidden = false;
				} else {
					writeImage.removeAttribute('src');
					writeImage.alt = state.cardData.title || '';
				}
			}

			if (overlay && !state.message) {
				overlay.textContent = '';
			}

			root.style.setProperty('--bskudo-accent', (state.cardData && state.cardData.accentColor) || '#335c70');

			renderImpulses();
			revealSection(2);

			if (overlay) {
				requestAnimationFrame(function () {
					fitCardMessage(overlay);
					placeCaretAtEnd(overlay);
				});
			}
		}

		/* ─── Teil 6: contenteditable-Handler ─────────────────────── */

		function handleOverlayInput() {
			if (!overlay) {
				return;
			}

			var raw = overlay.textContent || '';
			var value = truncate(raw, maxChars);

			if (raw !== value) {
				overlay.textContent = value;
				placeCaretAtEnd(overlay);
			}

			state.message = value;

			if (messageHidden) {
				messageHidden.value = value;
			}

			updateCharCount();
			updateSection2NextButton();
			fitCardMessage(overlay);
		}

		/* ─── Teil 10: initWizard – Event-Listener ─────────────────── */

		cardButtons.forEach(function (card) {
			var thumbW = parseInt(card.getAttribute('data-image-width'), 10);
			var thumbH = parseInt(card.getAttribute('data-image-height'), 10);
			if (thumbW > 0 && thumbH > 0) {
				applyCardAspect(card, thumbW, thumbH);
			}

			var onSelect = function () {
				selectCard(parseInt(card.getAttribute('data-card-id'), 10));
			};

			card.addEventListener('click', onSelect);

			card.addEventListener('keydown', function (event) {
				if (event.key !== 'Enter' && event.key !== ' ') {
					return;
				}
				event.preventDefault();
				onSelect();
			});
		});

		if (overlay) {
			overlay.addEventListener('input', handleOverlayInput);

			overlay.addEventListener('paste', function (event) {
				event.preventDefault();
				var paste = '';
				if (event.clipboardData) {
					paste = event.clipboardData.getData('text/plain');
				}
				var current = overlay.textContent || '';
				var merged = truncate(current + paste, maxChars);
				overlay.textContent = merged;
				placeCaretAtEnd(overlay);
				handleOverlayInput();
			});

			overlay.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					event.preventDefault();
				}
			});
		}

		root.querySelectorAll('[data-goto-section]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = parseInt(btn.getAttribute('data-goto-section'), 10);
				revealSection(target);
			});
		});

		stepButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var target = parseInt(btn.getAttribute('data-step'), 10);
				revealSection(target);
			});
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

		var writeWrap = root.querySelector('.bskudo-card-write-wrap');
		if (writeWrap && typeof ResizeObserver !== 'undefined') {
			var resizeTimer;
			var observer = new ResizeObserver(function () {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function () {
					if (overlay) {
						fitCardMessage(overlay);
					}
				}, 80);
			});
			observer.observe(writeWrap);
		}

		if (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				sendKudo();
			});
		}

		[senderNameField, senderEmailField, recipientNameField, recipientEmailField].forEach(function (field) {
			if (!field) {
				return;
			}
			field.addEventListener('input', function () {
				if (sendToSelfCheckbox && sendToSelfCheckbox.checked) {
					syncSendToSelf();
				}
				validateForm();
			});
			field.addEventListener('blur', validateForm);
		});

		if (sendToSelfCheckbox) {
			sendToSelfCheckbox.addEventListener('change', function () {
				syncSendToSelf();
				validateForm();
			});
		}

		sendModeRadios.forEach(function (radio) {
			radio.addEventListener('change', function () {
				updateSendTimingUi();
				validateForm();
			});
		});

		if (sendAtField) {
			sendAtField.addEventListener('input', validateForm);
			sendAtField.addEventListener('change', validateForm);
		}

		if (charCountMax) {
			charCountMax.textContent = String(maxChars);
		}

		updateSendTimingUi();
		updateSectionNav();
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
