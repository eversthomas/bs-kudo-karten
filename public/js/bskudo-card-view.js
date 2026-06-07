/**
 * BS Kudo Karten – Webansicht (Token-Seite)
 */
(function () {
	'use strict';

	var config = window.bskudoCardView || { i18n: {} };

	function fitMessages(scope) {
		if (window.bskudo && typeof window.bskudo.scheduleFitMessages === 'function') {
			window.bskudo.scheduleFitMessages(scope || document);
		}
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

		if (printBtn) {
			printBtn.addEventListener('click', function () {
				fitMessages(document.querySelector('.bskudo-cardview__media'));
				window.print();
			});
		}
	}

	function initMessageFit() {
		var media = document.querySelector('.bskudo-cardview__media');
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
				fitMessages(media);
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
