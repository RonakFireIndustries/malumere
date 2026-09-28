/**
 * Ma Lumière — contact form AJAX.
 */
(function () {
	'use strict';

	var form = document.querySelector('[data-contact-form]');
	if (!form || !window.mlContact) return;

	var message = form.querySelector('[data-form-message]');
	var submitBtn = form.querySelector('[data-submit]');

	function showMessage(type, text) {
		if (!message) return;
		message.textContent = text;
		message.classList.remove('form-message--success', 'form-message--error', 'is-visible');
		message.classList.add(type === 'success' ? 'form-message--success' : 'form-message--error', 'is-visible');
		message.setAttribute('aria-live', 'polite');
	}

	function clearMessage() {
		if (message) {
			message.classList.remove('is-visible', 'form-message--success', 'form-message--error');
		}
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		clearMessage();

		var name = form.querySelector('[name="name"]').value.trim();
		var email = form.querySelector('[name="email"]').value.trim();
		var messageText = form.querySelector('[name="message"]').value.trim();

		// Client-side hints only; authoritative validation happens server-side.
		var errors = [];
		if (!name) errors.push('name');
		if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('email');
		if (!messageText) errors.push('message');

		if (errors.length) {
			showMessage('error', 'Please complete the required fields (' + errors.join(', ') + ').');
			var first = form.querySelector('[name="' + errors[0] + '"]');
			if (first) first.focus();
			return;
		}

		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = 'Sending…';
		}

		var payload = {
			nonce: window.mlContact.nonce,
			name: name,
			phone: form.querySelector('[name="phone"]').value.trim(),
			email: email,
			subject: form.querySelector('[name="subject"]').value.trim(),
			message: messageText,
			website: form.querySelector('[name="website"]') ? form.querySelector('[name="website"]').value : ''
		};

		fetch(window.mlContact.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		})
			.then(function (res) {
				return res.json().then(function (data) {
					return { ok: res.ok, data: data };
				});
			})
			.then(function (result) {
				if (result.ok) {
					showMessage('success', result.data.message || 'Thank you — your message has been sent.');
					form.reset();
				} else {
					showMessage('error', (result.data && result.data.message) || 'Something went wrong. Please try again.');
				}
			})
			.catch(function () {
				showMessage('error', 'Could not reach the server. Please try again shortly.');
			})
			.finally(function () {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Send message';
				}
			});
	});
})();