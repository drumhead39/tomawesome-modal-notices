(function () {
	'use strict';

	var openModal = null;
	var previousFocus = null;

	function storageKey(settings) {
		var pageSuffix = settings.scope === 'page' ? '_p' + window.IMFW_CONFIG.pageId : '';
		return window.IMFW_CONFIG.storagePrefix + settings.id + pageSuffix;
	}

	function canShow(settings) {
		var store;
		var value;

		if (settings.frequency === 'always') {
			return true;
		}

		try {
			store = settings.frequency === 'session' ? window.sessionStorage : window.localStorage;
			value = parseInt(store.getItem(storageKey(settings)) || '0', 10);

			if (!value) {
				return true;
			}

			if (settings.frequency === 'days') {
				return Date.now() - value >= settings.repeatDays * 86400000;
			}

			return false;
		} catch (error) {
			return true;
		}
	}

	function remember(settings) {
		var store;

		if (settings.frequency === 'always') {
			return;
		}

		try {
			store = settings.frequency === 'session' ? window.sessionStorage : window.localStorage;
			store.setItem(storageKey(settings), String(Date.now()));
		} catch (error) {
			// Browser storage can be unavailable in private or restricted contexts.
		}
	}

	function focusableElements(modal) {
		return modal.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), ' +
			'textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
	}

	function show(modal, settings) {
		var firstFocus;

		if (!canShow(settings) || openModal) {
			return false;
		}

		previousFocus = document.activeElement;
		openModal = modal;
		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.documentElement.classList.add('imfw-open');

		window.requestAnimationFrame(function () {
			modal.classList.add('is-visible');
		});

		remember(settings);
		firstFocus = focusableElements(modal)[0] || modal.querySelector('.imfw-dialog');
		firstFocus.focus();
		modal.dispatchEvent(new CustomEvent('imfw:opened', { detail: settings }));
		return true;
	}

	function close(modal) {
		if (!modal) {
			return;
		}

		modal.classList.remove('is-visible');
		window.setTimeout(function () {
			modal.hidden = true;
			modal.setAttribute('aria-hidden', 'true');
		}, 250);

		document.documentElement.classList.remove('imfw-open');
		openModal = null;

		if (previousFocus && previousFocus.focus) {
			previousFocus.focus();
		}

		modal.dispatchEvent(new CustomEvent('imfw:closed'));
	}

	function bindModal(modal) {
		var settings;

		try {
			settings = JSON.parse(modal.getAttribute('data-imfw'));
		} catch (error) {
			return;
		}

		modal.addEventListener('click', function (event) {
			var confirmButton;

			if (event.target.closest('[data-imfw-close]')) {
				close(modal);
				return;
			}

			confirmButton = event.target.closest('[data-imfw-confirm]');
			if (!confirmButton) {
				return;
			}

			remember(settings);
			if (settings.action === 'url' && settings.url) {
				window.location.href = settings.url;
			} else if (settings.action === 'url_new' && settings.url) {
				window.open(settings.url, '_blank', 'noopener');
			}
			close(modal);
		});

		if (settings.trigger === 'load') {
			show(modal, settings);
		} else if (settings.trigger === 'delay') {
			window.setTimeout(function () {
				show(modal, settings);
			}, Math.max(0, settings.delay));
		} else if (settings.trigger === 'scroll') {
			bindScrollTrigger(modal, settings);
		} else if (settings.trigger === 'exit') {
			bindExitTrigger(modal, settings);
		} else if (settings.trigger === 'click' && settings.selector) {
			bindClickTrigger(modal, settings);
		}
	}

	function bindScrollTrigger(modal, settings) {
		var onScroll = function () {
			var scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
			var percentage = scrollableHeight <= 0 ? 100 : (window.scrollY / scrollableHeight) * 100;

			if (percentage >= settings.scroll) {
				window.removeEventListener('scroll', onScroll);
				show(modal, settings);
			}
		};

		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	function bindExitTrigger(modal, settings) {
		var onExit = function (event) {
			if (event.clientY <= 0) {
				document.removeEventListener('mouseout', onExit);
				show(modal, settings);
			}
		};

		document.addEventListener('mouseout', onExit);
	}

	function bindClickTrigger(modal, settings) {
		document.addEventListener('click', function (event) {
			var trigger;

			try {
				trigger = event.target.closest(settings.selector);
			} catch (error) {
				return;
			}

			if (trigger && show(modal, settings)) {
				event.preventDefault();
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		var focusable;
		var first;
		var last;

		if (!openModal) {
			return;
		}

		if (event.key === 'Escape') {
			close(openModal);
			return;
		}

		if (event.key !== 'Tab') {
			return;
		}

		focusable = focusableElements(openModal);
		if (!focusable.length) {
			return;
		}

		first = focusable[0];
		last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-imfw]').forEach(bindModal);
	});
})();
