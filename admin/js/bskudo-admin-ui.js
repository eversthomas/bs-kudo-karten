/**
 * BS Kudo Karten – Admin UI (native WP-Screens in App-Shell).
 */
(function () {
	'use strict';

	var host = document.querySelector('.bskudo-wp-host');
	if (!host) {
		return;
	}

	var wpBody = document.getElementById('wpbody-content');
	if (!wpBody) {
		return;
	}

	// Admin-Notices oberhalb von wpbody in den Seiteninhalt verschieben.
	var notices = document.querySelectorAll('#wpbody > .notice, #wpbody > .updated, #wpbody > .error');
	notices.forEach(function (notice) {
		host.insertBefore(notice, host.firstChild);
	});

	// Screen-Meta und WP-Inhalt sicher im Host halten (Fallback bei abweichendem Markup).
	var screenMeta = document.getElementById('screen-meta');
	if (screenMeta && !host.contains(screenMeta)) {
		host.appendChild(screenMeta);
	}

	var screenMetaLinks = document.getElementById('screen-meta-links');
	if (screenMetaLinks && !host.contains(screenMetaLinks)) {
		host.appendChild(screenMetaLinks);
	}

	wpBody.querySelectorAll(':scope > .wrap').forEach(function (wrap) {
		if (!host.contains(wrap)) {
			host.appendChild(wrap);
		}
	});
})();
