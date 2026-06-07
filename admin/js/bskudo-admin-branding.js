/**
 * Logo-Auswahl im Branding-Tab (Medienbibliothek).
 */
(function ($) {
	'use strict';

	var frame;

	$('#bskudo-logo-select').on('click', function (e) {
		e.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: 'Logo wählen',
			button: { text: 'Logo verwenden' },
			multiple: false,
			library: { type: 'image' },
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#bskudo_logo_id').val(attachment.id);
			var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			$('#bskudo-logo-preview').html('<img src="' + url + '" alt="">');
			$('#bskudo-logo-remove').prop('hidden', false);
		});

		frame.open();
	});

	$('#bskudo-logo-remove').on('click', function (e) {
		e.preventDefault();
		$('#bskudo_logo_id').val('0');
		$('#bskudo-logo-preview').empty();
		$(this).prop('hidden', true);
	});
})(jQuery);
