/**
 * Ma Lumière — before/after comparison slider.
 * Pointer-events based, keyboard accessible (arrow keys).
 */
(function () {
	'use strict';

	var sliders = document.querySelectorAll('[data-ba-slider]');

	function setPos(slider, x) {
		var rect = slider.getBoundingClientRect();
		if (rect.width === 0) return;
		var pct = ((x - rect.left) / rect.width) * 100;
		pct = Math.max(0, Math.min(100, pct));
		slider.style.setProperty('--pos', pct);
	}

	function movePointer(slider, clientX) {
		setPos(slider, clientX);
	}

	sliders.forEach(function (slider) {
		var dragging = false;

		slider.addEventListener('pointerdown', function (e) {
			dragging = true;
			slider.setPointerCapture(e.pointerId);
			movePointer(slider, e.clientX);
			e.preventDefault();
		});

		slider.addEventListener('pointermove', function (e) {
			if (dragging) {
				movePointer(slider, e.clientX);
			}
		});

		var stop = function (e) {
			if (dragging) {
				dragging = false;
				try { slider.releasePointerCapture(e.pointerId); } catch (err) { /* noop */ }
			}
		};
		slider.addEventListener('pointerup', stop);
		slider.addEventListener('pointercancel', stop);

		// Keyboard: left/right nudges the handle, home/end jumps.
		slider.setAttribute('tabindex', '0');
		slider.setAttribute('role', 'slider');
		slider.setAttribute('aria-label', 'Before and after comparison');
		slider.setAttribute('aria-valuemin', '0');
		slider.setAttribute('aria-valuemax', '100');

		slider.addEventListener('keydown', function (e) {
			var current = parseFloat(slider.style.getPropertyValue('--pos')) || 50;
			if (e.key === 'ArrowLeft') {
				setPos(slider, (slider.getBoundingClientRect().left + (slider.getBoundingClientRect().width * Math.max(0, current - 4)) / 100));
				e.preventDefault();
			} else if (e.key === 'ArrowRight') {
				setPos(slider, (slider.getBoundingClientRect().left + (slider.getBoundingClientRect().width * Math.min(100, current + 4)) / 100));
				e.preventDefault();
			} else if (e.key === 'Home') {
				setPos(slider, slider.getBoundingClientRect().left);
				e.preventDefault();
			} else if (e.key === 'End') {
				setPos(slider, slider.getBoundingClientRect().right);
				e.preventDefault();
			} else {
				return;
			}
			slider.setAttribute('aria-valuenow', String(Math.round(parseFloat(slider.style.getPropertyValue('--pos')) || 50)));
		});
	});
})();