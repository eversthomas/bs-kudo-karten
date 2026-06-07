/**
 * BS Kudo Karten – Webansicht (Token-Seite)
 */
(function () {
	'use strict';

	var config = window.bskudoCardView || { i18n: {} };
	var printPrepared = false;

	function getMedia() {
		return document.querySelector('.bskudo-cardview__media');
	}

	function fitMessages(scope, options) {
		if (window.bskudo && typeof window.bskudo.scheduleFitMessages === 'function') {
			window.bskudo.scheduleFitMessages(scope || document, options);
		}
	}

	function fitMessagesNow(scope, options) {
		if (window.bskudo && typeof window.bskudo.fitCardMessagesNow === 'function') {
			window.bskudo.fitCardMessagesNow(scope || document, options);
		}
	}

	function preparePrintFit() {
		if (printPrepared) {
			return;
		}

		printPrepared = true;
		fitMessagesNow(getMedia(), { print: true });
	}

	function restoreScreenFit() {
		printPrepared = false;
		fitMessages(getMedia(), { print: false });
	}

	function initToggle() {
		var toggle = document.getElementById('bskudo-toggle');
		var front = document.querySelector('.bskudo-cardview__side--front');
		var back = document.querySelector('.bskudo-cardview__side--back');

		if (!toggle || !front || !back) {
			return;
		}

		var showingFront = true;
		var labelShowBack = config.i18n.showBack || 'Rückseite ansehen';
		var labelShowFront = config.i18n.showFront || 'Vorderseite anzeigen';

		toggle.addEventListener('click', function () {
			if (showingFront) {
				front.classList.remove('bskudo-cardview__side--active');
				back.classList.add('bskudo-cardview__side--active');
				toggle.textContent = labelShowFront;
			} else {
				back.classList.remove('bskudo-cardview__side--active');
				front.classList.add('bskudo-cardview__side--active');
				toggle.textContent = labelShowBack;
			}
			showingFront = !showingFront;
		});
	}

	function initPrint() {
		var printBtn = document.querySelector('.bskudo-cardview__print-btn');

		window.addEventListener('beforeprint', preparePrintFit);
		window.addEventListener('afterprint', restoreScreenFit);

		if (printBtn) {
			printBtn.addEventListener('click', function () {
				preparePrintFit();
				window.print();
			});
		}
	}

	function initMessageFit() {
		var media = getMedia();
		var img = media ? media.querySelector('img') : null;

		if (img) {
			if (img.complete && img.naturalWidth) {
				fitMessages(media);
			} else {
				img.addEventListener(
					'load',
					function () {
						fitMessages(media);
					},
					{ once: true }
				);
			}
		} else {
			fitMessages(document);
		}

		if (typeof ResizeObserver !== 'undefined' && media) {
			var observer = new ResizeObserver(function () {
				if (!printPrepared) {
					fitMessages(media);
				}
			});
			observer.observe(media);
		}
	}

	function init() {
		initToggle();
		initPrint();
		initMessageFit();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
