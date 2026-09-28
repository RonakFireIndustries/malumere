/**
 * Ma Lumière — site behaviour.
 * Vanilla JS: sticky header, mobile nav, accessible accordions, reveals.
 */
(function () {
	'use strict';

	/* ---------- Sticky header ---------- */
	var header = document.querySelector('[data-site-header]');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 24);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	/* ---------- Mobile navigation ---------- */
	var toggle = document.querySelector('[data-nav-toggle]');
	var nav = document.getElementById('site-navigation');
	if (toggle && nav) {
		var closeNav = function () {
			toggle.setAttribute('aria-expanded', 'false');
			nav.classList.remove('is-open');
		};
		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
			nav.classList.toggle('is-open', !open);
		});
		// Close on Escape.
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeNav();
			}
		});
		// Close after choosing a link, and prevent the panel from capturing focus.
		nav.addEventListener('click', function (e) {
			if (e.target.closest('a')) {
				closeNav();
			}
		});
	}

	/* ---------- FAQ / treatment accordions ---------- */
	var initAccordion = function (root) {
		var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-accordion-button]'));
		buttons.forEach(function (button) {
			if (button.dataset.init === '1') return;
			button.dataset.init = '1';

			var panel = document.getElementById(button.getAttribute('aria-controls'));
			if (!panel) return;

			var measure = function (open) {
				panel.style.maxHeight = open ? panel.scrollHeight + 'px' : '0px';
			};

			button.addEventListener('click', function () {
				var wasOpen = button.getAttribute('aria-expanded') === 'true';
				// Close siblings within the same accordion group (single-open).
				if (root.hasAttribute('data-single-open')) {
					buttons.forEach(function (b) {
						if (b !== button) {
							b.setAttribute('aria-expanded', 'false');
							var p = document.getElementById(b.getAttribute('aria-controls'));
							if (p) p.style.maxHeight = '0px';
						}
					});
				}
				button.setAttribute('aria-expanded', wasOpen ? 'false' : 'true');
				measure(!wasOpen);
			});

			// Respect initial state (e.g. if "open" class present).
			if (button.getAttribute('aria-expanded') === 'true') {
				setTimeout(function () { measure(true); }, 50);
			}
		});
	};

	var accordions = document.querySelectorAll('[data-accordion]');
	Array.prototype.forEach.call(accordions, function (root) {
		root.setAttribute('data-single-open', 'true');
		initAccordion(root);
	});

	window.addEventListener('resize', function () {
		Array.prototype.forEach.call(accordions, function (root) {
			root.querySelectorAll('[data-accordion-button]').forEach(function (button) {
				var panel = document.getElementById(button.getAttribute('aria-controls'));
				if (panel && button.getAttribute('aria-expanded') === 'true') {
					panel.style.maxHeight = panel.scrollHeight + 'px';
				}
			});
		});
	});

	/* ---------- Scroll reveals ---------- */
	var reveals = document.querySelectorAll('[data-reveal]');
	if ('IntersectionObserver' in window) {
		var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
		);
		reveals.forEach(function (el) {
			if (reduceMotion) {
				el.classList.add('is-visible');
			} else {
				observer.observe(el);
			}
		});
	} else {
		reveals.forEach(function (el) {
			el.classList.add('is-visible');
		});
	}

	/* ---------- Focus-visible hint (keyboard users only) ---------- */
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Tab') {
			document.body.classList.add('has-focus-visible');
		}
	});
	document.addEventListener('mousedown', function () {
		document.body.classList.remove('has-focus-visible');
	});
})();