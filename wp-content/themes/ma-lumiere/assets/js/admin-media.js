/**
 * Ma Lumière — admin media pickers for CPT meta boxes.
 */
(function ($) {
	'use strict';

	$(document).on('click', '.ml-media-button', function (e) {
		e.preventDefault();
		var btn = $(this);
		var wrap = btn.closest('.ml-media-field');
		var input = wrap.find('.ml-media-id');
		var preview = wrap.find('.ml-media-preview');

		var frame = wp.media({
			title: btn.data('title') || 'Select image',
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			input.val(att.id);
			var src = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
			preview.html('<img src="' + src + '" alt="" style="max-width:220px;height:auto;border-radius:8px" />');
			btn.text('Change image');
		});

		frame.open();
	});

	$(document).on('click', '.ml-media-remove', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.ml-media-field');
		wrap.find('.ml-media-id').val('');
		wrap.find('.ml-media-preview').empty();
		wrap.find('.ml-media-button').text('Select image');
	});
})(jQuery);