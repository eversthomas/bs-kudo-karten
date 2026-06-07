/**
 * BS Kudo Karten – Webansicht (Token-Seite)
 */
(function () {
	'use strict';

	var config = window.bskudoCardView || { i18n: {} };

	function initToggle() {
		var toggle = document.getElementById('bskudo-toggle');
		var front = document.querySelector('.bskudo-cardview__side--front');
		var back = document.querySelector('.bskudo-cardview__side--back');

		if (!toggle || !front || !back) {
			return;
		}

		var showingFront = true;
		var labelShowBack = config.i18n.showBack || 'Rückseite ansehen';
		var labelShowFront = config.i18n.showFront || 'Vorderseite ansehen';

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
				window.print();
			});
		}
	}

	function init() {
		initToggle();
		initPrint();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
