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

	(function initializePreview() {
		var $stage = $('[data-imfw-preview-stage]');
		var $content;
		var $confirm;
		var $close;
		var config = window.IMFW_ADMIN_PREVIEW || {};
		var replayTimer;

		if (!$stage.length) {
			return;
		}

		$content = $stage.find('[data-imfw-preview-content]');
		$confirm = $stage.find('[data-imfw-preview-confirm]');
		$close = $stage.find('[data-imfw-preview-close]');

		function setting(name) {
			return $('[name="imfw[' + name + ']"]').first();
		}

		function value(name, fallback) {
			var current = setting(name).val();

			return current === undefined || current === '' ? fallback : current;
		}

		function setChoiceClass(prefix, selected, choices) {
			$.each(choices, function (index, choice) {
				$stage.removeClass(prefix + choice);
			});

			if ($.inArray(selected, choices) === -1) {
				selected = choices[0];
			}

			$stage.addClass(prefix + selected);
		}

		function editorContent() {
			var editor;
			var text;

			if (window.tinymce) {
				editor = window.tinymce.get('content');
				if (editor && !editor.isHidden()) {
					return editor.getContent();
				}
			}

			text = $('#content').val() || '';
			if (text.indexOf('<') === -1) {
				return $('<div>').text(text).html().replace(/\r?\n/g, '<br>');
			}

			return text;
		}

		function safePreviewContent(html) {
			var container = document.createElement('div');

			container.innerHTML = html;
			$(container).find('script, style, iframe, object, embed, link, meta, base').remove();
			$(container).find('*').each(function () {
				var element = this;

				$.each(Array.prototype.slice.call(element.attributes), function (index, attribute) {
					var name = attribute.name.toLowerCase();
					var normalizedValue = attribute.value.replace(/[\u0000-\u0020]/g, '').toLowerCase();

					if (
						name.indexOf('on') === 0 ||
						name === 'srcdoc' ||
						($.inArray(name, ['href', 'src', 'xlink:href']) !== -1 && normalizedValue.indexOf('javascript:') === 0)
					) {
						element.removeAttribute(attribute.name);
					}
				});
			});

			return $(container).contents();
		}

		function updateContent() {
			var html = editorContent();

			if (html) {
				$content.empty().append(safePreviewContent(html));
				return;
			}

			$content.empty().append(
				$('<p>', {
					'class': 'imfw-preview__empty',
					text: config.emptyContent || 'Your modal content will appear here.'
				})
			);
		}

		function updatePreview() {
			var properties = {
				width: ['--imfw-width', '90%'],
				max_width: ['--imfw-max-width', '600px'],
				padding: ['--imfw-padding', '28px'],
				radius: ['--imfw-radius', '8px'],
				font_size: ['--imfw-font-size', '16px'],
				font_family: ['--imfw-font-family', 'inherit'],
				bg: ['--imfw-background', '#ffffff'],
				text_color: ['--imfw-text', '#222222'],
				overlay: ['--imfw-overlay', 'rgba(0,0,0,.55)'],
				button_bg: ['--imfw-button-bg', '#2271b1'],
				button_text: ['--imfw-button-text', '#ffffff'],
				button_border: ['--imfw-button-border', '#2271b1'],
				button_hover_bg: ['--imfw-button-hover-bg', '#135e96'],
				button_hover_text: ['--imfw-button-hover-text', '#ffffff']
			};
			var confirmText = setting('confirm_text').val() || '';

			$.each(properties, function (name, property) {
				$stage[0].style.setProperty(property[0], value(name, property[1]));
			});

			setChoiceClass(
				'imfw-preview-place-',
				value('placement', 'center'),
				['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right']
			);
			setChoiceClass(
				'imfw-preview-anim-',
				value('animation', 'fade'),
				['none', 'fade', 'scale', 'slide-up', 'slide-down', 'slide-left', 'slide-right']
			);

			$close.toggle(setting('close_x').is(':checked'));
			$confirm.text(confirmText).toggle(confirmText.length > 0);
			updateContent();
		}

		function replayAnimation() {
			window.clearTimeout(replayTimer);
			$stage.removeClass('is-replaying');
			$stage[0].offsetWidth; // Force a reflow so the same animation can replay. // eslint-disable-line no-unused-expressions
			$stage.addClass('is-replaying');
			replayTimer = window.setTimeout(function () {
				$stage.removeClass('is-replaying');
			}, 400);
		}

		function bindEditor(editor) {
			if (!editor || editor.id !== 'content' || editor.imfwPreviewBound) {
				return;
			}

			editor.imfwPreviewBound = true;
			editor.on('change input keyup Undo Redo SetContent', updateContent);
		}

		$(document).on('input change', '[name^="imfw["]', function () {
			updatePreview();
			if ($(this).attr('name') === 'imfw[animation]' || $(this).attr('name') === 'imfw[placement]') {
				replayAnimation();
			}
		});
		$('#content').on('input change', updateContent);
		$('#content-tmce, #content-html').on('click', function () {
			window.setTimeout(updateContent, 50);
		});
		$(document).on('tinymce-editor-init', function (event, editor) {
			bindEditor(editor);
			updateContent();
		});
		$('[data-imfw-preview-replay]').on('click', replayAnimation);

		if (window.tinymce) {
			bindEditor(window.tinymce.get('content'));
		}

		updatePreview();
		window.setTimeout(replayAnimation, 100);
	})();
});

