jQuery(function ($) {
	'use strict';

	$('.imfw-color').wpColorPicker();

	$(document).on('click', '.imfw-tooltip__trigger', function (event) {
		var tooltip = $(this).closest('.imfw-tooltip');

		event.preventDefault();
		event.stopPropagation();
		$('.imfw-tooltip').not(tooltip).removeClass('is-open');
		tooltip.addClass('is-open');
	});

	$(document).on('click', function () {
		$('.imfw-tooltip').removeClass('is-open');
	});

	$(document).on('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}

		$('.imfw-tooltip').removeClass('is-open');
		$('.imfw-tooltip__trigger:focus').trigger('blur');
	});
});

