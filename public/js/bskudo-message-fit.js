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
		printMinFontSize: 8,
	};

	var PRINT = {
		cardWidthCm: 14.8,
		cardHeightCm: 11.1,
	};

	var cmPxCache = {};

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
	 * Zentimeter in Pixel umrechnen (browserabhängige Druckauflösung).
	 *
	 * @param {number} cm Länge in cm.
	 * @return {number}
	 */
	function cmToPx(cm) {
		var key = String(cm);

		if (cmPxCache[key]) {
			return cmPxCache[key];
		}

		var probe = document.createElement('div');
		probe.style.cssText =
			'position:fixed;left:-9999px;top:0;width:' +
			cm +
			'cm;height:1px;pointer-events:none;visibility:hidden;';
		document.documentElement.appendChild(probe);
		var px = probe.getBoundingClientRect().width;
		document.documentElement.removeChild(probe);
		cmPxCache[key] = px;

		return px;
	}

	/**
	 * Schreibzone für den Drucklayout (A4, 14,8 × 11,1 cm Karte).
	 *
	 * @return {{width: number, height: number}}
	 */
	function getPrintZoneSize() {
		var cardW = cmToPx(PRINT.cardWidthCm);
		var cardH = cmToPx(PRINT.cardHeightCm);

		return {
			width: cardW * CONFIG.zoneWidthRatio * CONFIG.zonePaddingRatio,
			height: cardH * CONFIG.zoneHeightRatio * CONFIG.zonePaddingRatio,
		};
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
	 * CSS-Längeneinheit für den Modus.
	 *
	 * @param {boolean} isPrint Druckmodus.
	 * @return {string}
	 */
	function getUnit(isPrint) {
		return isPrint ? 'pt' : 'px';
	}

	/**
	 * Pixel in Druck-Einheit umrechnen.
	 *
	 * @param {number} px Wert in px.
	 * @param {boolean} isPrint Druckmodus.
	 * @return {number}
	 */
	function toLength(px, isPrint) {
		if (!isPrint) {
			return px;
		}

		return Math.round(px * 0.75 * 10) / 10;
	}

	/**
	 * Textbox fest auf Schreibzone begrenzen.
	 *
	 * @param {HTMLElement} messageEl Text- oder Overlay-Element.
	 * @param {number} zoneW Breite in px.
	 * @param {number} zoneH Höhe in px.
	 * @param {boolean} isPrint Druckmodus.
	 */
	function applyBoxConstraints(messageEl, zoneW, zoneH, isPrint) {
		var unit = getUnit(isPrint);

		messageEl.style.boxSizing = 'border-box';
		messageEl.style.display = 'block';
		messageEl.style.width = toLength(zoneW, isPrint) + unit;
		messageEl.style.maxWidth = toLength(zoneW, isPrint) + unit;
		messageEl.style.maxHeight = toLength(zoneH, isPrint) + unit;
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
	 * @param {{print?: boolean}|undefined} options Optionen.
	 */
	function fitCardMessage(messageEl, options) {
		if (!messageEl) {
			return;
		}

		var isPrint = !!(options && options.print);
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

		var zoneSize = isPrint ? getPrintZoneSize() : getZoneSize(zone);
		var zoneW = Math.floor(zoneSize.width);
		var zoneH = Math.floor(zoneSize.height);

		if (zoneW < 8 || zoneH < 8) {
			return;
		}

		applyBoxConstraints(messageEl, zoneW, zoneH, isPrint);

		var lineHeight = CONFIG.lineHeight;
		var minSize = isPrint ? CONFIG.printMinFontSize : CONFIG.minFontSize;
		var maxSizePx = Math.min(
			CONFIG.maxFontSize,
			Math.floor(((zoneH / lineHeight) * 0.98) * 2) / 2,
			Math.floor((zoneW * 0.55) * 2) / 2
		);
		var maxSize = isPrint ? toLength(maxSizePx, true) : maxSizePx;
		maxSize = Math.max(minSize, maxSize);

		var unit = getUnit(isPrint);

		var fits = function (fontSize) {
			messageEl.style.fontSize = fontSize + unit;
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

		messageEl.style.fontSize = best + unit;
		messageEl.setAttribute('data-bskudo-fit-mode', isPrint ? 'print' : 'screen');
	}

	var MESSAGE_SELECTOR =
		'.bskudo-card__message, .bskudo-card-overlay, .bskudo-cardview__message';

	/**
	 * Mehrere Textelemente sofort anpassen (z. B. vor dem Druck).
	 *
	 * @param {Document|Element} scope Wurzel für die Suche.
	 * @param {{print?: boolean}|undefined} options Optionen.
	 */
	function fitCardMessagesNow(scope, options) {
		var container = scope || document;
		container.querySelectorAll(MESSAGE_SELECTOR).forEach(function (el) {
			fitCardMessage(el, options);
		});
	}

	/**
	 * Mehrere Textelemente nach Layout-Änderungen anpassen.
	 *
	 * @param {Document|Element} scope Wurzel für die Suche.
	 * @param {{print?: boolean}|undefined} options Optionen.
	 */
	function scheduleFitMessages(scope, options) {
		var container = scope || document;
		var fitOptions = options || {};

		var run = function () {
			container.querySelectorAll(MESSAGE_SELECTOR).forEach(function (el) {
				fitCardMessage(el, fitOptions);
			});
		};

		requestAnimationFrame(function () {
			requestAnimationFrame(run);
		});
		setTimeout(run, 80);
		setTimeout(run, 250);
	}

	global.bskudo.fitCardMessage = fitCardMessage;
	global.bskudo.fitCardMessagesNow = fitCardMessagesNow;
	global.bskudo.scheduleFitMessages = scheduleFitMessages;
})(typeof window !== 'undefined' ? window : this);
