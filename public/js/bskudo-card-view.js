/**
 * BS Kudo Karten – Webansicht (Phase 5)
 */
(function () {
	'use strict';

	var config = window.bskudoCardView || { i18n: {} };

	/**
	 * Schriftgröße an die Nachrichtenzone anpassen (max. 2 Zeilen).
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
		if (!text) {
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

	function scheduleFitMessages(scope) {
		var container = scope || document;
		var run = function () {
			container.querySelectorAll('.bskudo-card__message').forEach(fitCardMessage);
		};
		requestAnimationFrame(function () {
			requestAnimationFrame(run);
		});
		setTimeout(run, 80);
		setTimeout(run, 250);
	}

	function applyImageAspect(img, cardRoot) {
		if (!img || !cardRoot) {
			return;
		}

		var w = parseInt(img.getAttribute('data-width'), 10) || img.naturalWidth || 0;
		var h = parseInt(img.getAttribute('data-height'), 10) || img.naturalHeight || 0;

		if (w > 0 && h > 0) {
			cardRoot.style.setProperty('--bskudo-aspect-ratio', w + ' / ' + h);
		}

		var syncBack = function () {
			var duo = cardRoot.closest('.bskudo-preview-duo--view');
			if (!duo) {
				return;
			}
			var ratio = cardRoot.style.getPropertyValue('--bskudo-aspect-ratio');
			var back = duo.querySelector('.bskudo-card--back-only');
			if (back && ratio) {
				back.style.setProperty('--bskudo-aspect-ratio', ratio);
			}
		};

		if (img.complete && img.naturalWidth) {
			syncBack();
		} else {
			img.addEventListener(
				'load',
				function () {
					var nw = img.naturalWidth || w;
					var nh = img.naturalHeight || h;
					if (nw > 0 && nh > 0) {
						cardRoot.style.setProperty('--bskudo-aspect-ratio', nw + ' / ' + nh);
					}
					syncBack();
					scheduleFitMessages(cardRoot.closest('.bskudo-card-view') || document);
				},
				{ once: true }
			);
		}
	}

	function initTabs(root) {
		var tabs = root.querySelectorAll('.bskudo-card-view__tab');
		var modes = root.querySelectorAll('[data-view-mode]');

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var target = tab.getAttribute('data-view-target');
				tabs.forEach(function (t) {
					t.classList.toggle('bskudo-card-view__tab--active', t === tab);
				});
				modes.forEach(function (mode) {
					var isActive = mode.getAttribute('data-view-mode') === target;
					mode.hidden = !isActive;
				});
				if (target === 'flip') {
					scheduleFitMessages(root.querySelector('.bskudo-card-view__mode--flip'));
				} else {
					scheduleFitMessages(root.querySelector('.bskudo-card-view__mode--duo'));
				}
			});
		});
	}

	function initFlip(root) {
		var flipCard = root.querySelector('.bskudo-card-view__flip-card');
		var flipBtn = root.querySelector('.bskudo-card-view__flip-btn');

		if (!flipCard) {
			return;
		}

		var toggle = function () {
			var flipped = flipCard.classList.toggle('is-flipped');
			if (flipBtn) {
				flipBtn.textContent = flipped
					? config.i18n.showFront || 'Vorderseite anzeigen'
					: config.i18n.showBack || 'Rückseite anzeigen';
			}
		};

		flipCard.addEventListener('click', toggle);
		if (flipBtn) {
			flipBtn.addEventListener('click', toggle);
		}
	}

	function init() {
		var root = document.querySelector('.bskudo-card-view');
		if (!root) {
			return;
		}

		root.querySelectorAll('.bskudo-card-view__image, .bskudo-card-view__flip-image').forEach(function (img) {
			var card = img.closest('.bskudo-card');
			if (card) {
				applyImageAspect(img, card);
			}
		});

		initTabs(root);
		initFlip(root);
		scheduleFitMessages(root);

		if (typeof ResizeObserver !== 'undefined') {
			var observer = new ResizeObserver(function () {
				scheduleFitMessages(root);
			});
			observer.observe(root);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
