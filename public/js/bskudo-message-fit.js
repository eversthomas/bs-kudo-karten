/**
 * BS Kudo Karten – gemeinsame Schriftgrößen-Anpassung für Karten-Texte.
 */
(function (global) {
	'use strict';

	global.bskudo = global.bskudo || {};

	var CONFIG = {
		minFontSize: 10,
		maxFontSize: 72,
		lineHeight: 1.25,
		zoneWidthRatio: 0.6,
		zoneHeightRatio: 0.4,
		zonePaddingRatio: 0.94,
	};

	/**
	 * Schreibzone um ein Textelement finden.
	 *
	 * @param {HTMLElement} messageEl Text-Element.
	 * @return {HTMLElement|null}
	 */
	function getZone(messageEl) {
		if (!messageEl) {
			return null;
		}

		return messageEl.closest('.bskudo-card__message-zone');
	}

	/**
	 * Verfügbare Breite/Höhe der Schreibzone ermitteln.
	 *
	 * @param {HTMLElement} zone Schreibzone.
	 * @return {{width: number, height: number}}
	 */
	function getZoneSize(zone) {
		var rect = zone.getBoundingClientRect();

		if (rect.width >= 8 && rect.height >= 8) {
			return {
				width: rect.width * CONFIG.zonePaddingRatio,
				height: rect.height * CONFIG.zonePaddingRatio,
			};
		}

		var root = zone.closest('.bskudo-card-write, .bskudo-cardview__media, .bskudo-card__canvas');

		if (root) {
			var rootRect = root.getBoundingClientRect();

			if (rootRect.width >= 8 && rootRect.height >= 8) {
				return {
					width: rootRect.width * CONFIG.zoneWidthRatio * CONFIG.zonePaddingRatio,
					height: rootRect.height * CONFIG.zoneHeightRatio * CONFIG.zonePaddingRatio,
				};
			}
		}

		return { width: rect.width, height: rect.height };
	}

	/**
	 * Textbox fest auf Schreibzone begrenzen.
	 *
	 * @param {HTMLElement} messageEl Text- oder Overlay-Element.
	 * @param {number} zoneW Breite in px.
	 * @param {number} zoneH Höhe in px.
	 */
	function applyBoxConstraints(messageEl, zoneW, zoneH) {
		messageEl.style.boxSizing = 'border-box';
		messageEl.style.display = 'block';
		messageEl.style.width = zoneW + 'px';
		messageEl.style.maxWidth = zoneW + 'px';
		messageEl.style.maxHeight = zoneH + 'px';
		messageEl.style.minWidth = '0';
		messageEl.style.overflow = 'hidden';
		messageEl.style.overflowWrap = 'anywhere';
		messageEl.style.wordBreak = 'break-word';
		messageEl.style.lineHeight = String(CONFIG.lineHeight);
	}

	/**
	 * Inline-Größen zurücksetzen.
	 *
	 * @param {HTMLElement} messageEl Text- oder Overlay-Element.
	 */
	function clearBoxConstraints(messageEl) {
		messageEl.style.boxSizing = '';
		messageEl.style.display = '';
		messageEl.style.width = '';
		messageEl.style.maxWidth = '';
		messageEl.style.maxHeight = '';
		messageEl.style.minWidth = '';
		messageEl.style.overflow = '';
		messageEl.style.overflowWrap = '';
		messageEl.style.wordBreak = '';
		messageEl.style.lineHeight = '';
		messageEl.style.fontSize = '';
	}

	/**
	 * Schriftgröße per Binary Search an die Schreibzone anpassen.
	 *
	 * @param {HTMLElement} messageEl Text- oder Overlay-Element.
	 */
	function fitCardMessage(messageEl) {
		if (!messageEl) {
			return;
		}

		var zone = getZone(messageEl);
		if (!zone) {
			return;
		}

		var text = (messageEl.textContent || '').replace(/\s+/g, ' ').trim();
		var placeholder = (messageEl.getAttribute('data-placeholder') || '').trim();

		if (!text || (placeholder && text === placeholder)) {
			clearBoxConstraints(messageEl);
			return;
		}

		var zoneSize = getZoneSize(zone);
		var zoneW = Math.floor(zoneSize.width);
		var zoneH = Math.floor(zoneSize.height);

		if (zoneW < 8 || zoneH < 8) {
			return;
		}

		applyBoxConstraints(messageEl, zoneW, zoneH);

		var lineHeight = CONFIG.lineHeight;
		var minSize = CONFIG.minFontSize;
		var maxSize = Math.min(
			CONFIG.maxFontSize,
			Math.floor(((zoneH / lineHeight) * 0.98) * 2) / 2,
			Math.floor((zoneW * 0.55) * 2) / 2
		);
		maxSize = Math.max(minSize, maxSize);

		var fits = function (fontSize) {
			messageEl.style.fontSize = fontSize + 'px';
			void messageEl.offsetHeight;

			return messageEl.scrollHeight <= zoneH + 1 && messageEl.scrollWidth <= zoneW + 1;
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

		messageEl.style.fontSize = best + 'px';
	}

	/**
	 * Mehrere Textelemente nach Layout-Änderungen anpassen.
	 *
	 * @param {Document|Element} scope Wurzel für die Suche.
	 */
	function scheduleFitMessages(scope) {
		var container = scope || document;
		var selector =
			'.bskudo-card__message, .bskudo-card-overlay, .bskudo-cardview__message';

		var run = function () {
			container.querySelectorAll(selector).forEach(fitCardMessage);
		};

		requestAnimationFrame(function () {
			requestAnimationFrame(run);
		});
		setTimeout(run, 80);
		setTimeout(run, 250);
	}

	global.bskudo.fitCardMessage = fitCardMessage;
	global.bskudo.scheduleFitMessages = scheduleFitMessages;
})(typeof window !== 'undefined' ? window : this);
