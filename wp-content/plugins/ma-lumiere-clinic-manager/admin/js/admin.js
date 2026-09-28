/**
 * Ma Lumière Clinic — admin foundation JS.
 * Fetches the real dashboard stats via AJAX (no console.log, no sample data).
 */
(function ($) {
	'use strict';

	$(function () {
		var statsBox = document.getElementById('ml-dashboard-stats');
		if (!statsBox) {
			return;
		}

		$.ajax({
			url: MLClinicAdmin.ajaxUrl,
			method: 'POST',
			data: {
				action: 'ml_get_dashboard_stats',
				_wpnonce: MLClinicAdmin.nonce
			},
			success: function (response) {
				if (!response || !response.success || !response.data) {
					return;
				}
				var data = response.data;
				Object.keys(data).forEach(function (key) {
					var node = statsBox.querySelector('[data-stat="' + key + '"]');
					if (node) {
						node.textContent = (typeof data[key] === 'number' && Math.abs(data[key]) > 0)
							? data[key]
							: '0';
					}
				});
				statsBox.setAttribute('data-loaded', '1');
			},
			error: function () {
				statsBox.setAttribute('data-loaded', '0');
			}
		});
	});
})(jQuery);

/**
 * Appointments page controller (list + calendar + modals).
 * All mutations go through the ml-clinic/v1 REST API with the clinic
 * nonce; the server re-validates availability and permissions.
 */
(function () {
	'use strict';

	var page = document.getElementById('ml-appointments-page');
	if (!page) {
		return;
	}

	var rest = page.getAttribute('data-ml-rest');
	var nonce = page.getAttribute('data-ml-nonce');
	var patients = [];
	try {
		patients = JSON.parse(page.getAttribute('data-ml-patients')) || [];
	} catch (e) { /* ignore */ }
	var treatments = [];
	try {
		treatments = JSON.parse(page.getAttribute('data-ml-treatments')) || [];
	} catch (e) { /* ignore */ }

	function escapeHtml(value) {
		var div = document.createElement('div');
		div.textContent = String(value == null ? '' : value);
		return div.innerHTML;
	}

	function api(path, options) {
		options = options || {};
		options.headers = options.headers || {};
		options.headers['X-WP-Nonce'] = (window.MLClinicAdmin && MLClinicAdmin.restNonce) ? MLClinicAdmin.restNonce : nonce;
		options.headers['Accept'] = 'application/json';
		if (options.body && typeof options.body !== 'string') {
			options.headers['Content-Type'] = 'application/json';
			options.body = JSON.stringify(options.body);
		}
		return fetch(rest + path.replace(/^\//, ''), options).then(function (resp) {
			return resp.json().then(function (json) {
				return { ok: resp.ok, status: resp.status, json: json };
			});
		});
	}

	function showError(message) {
		var box = document.getElementById('ml-error-text');
		if (box) {
			box.textContent = message;
		}
		openModal('error');
	}

	function openModal(name) {
		var el = document.getElementById('ml-modal-' + name);
		if (el) {
			el.hidden = false;
		}
	}

	function closeModal(name) {
		var el = document.getElementById('ml-modal-' + name);
		if (el) {
			el.hidden = true;
		}
	}

	function closeAllModals() {
		var list = document.querySelectorAll('.ml-modal');
		for (var i = 0; i < list.length; i++) {
			list[i].hidden = true;
		}
	}

	page.addEventListener('click', function (event) {
		var closeBtn = event.target.closest('[data-ml-close]');
		if (closeBtn) {
			event.preventDefault();
			closeAllModals();
			return;
		}
		if (event.target.classList && event.target.classList.contains('ml-modal')) {
			event.target.hidden = true;
		}
	});

	/* ---- time slot loader ---- */
	function buildTimeOptions(select, date, doctorId, callback) {
		var treatmentId = 0;
		var hidden = document.getElementById('ml-new-treatment');
		if (hidden) {
			treatmentId = parseInt(hidden.value || '0', 10);
		}
		select.disabled = true;
		select.innerHTML = '<option value="">' + 'Loading…' + '</option>';
		api('appointments/availability?date=' + encodeURIComponent(date) + '&doctor_id=' + encodeURIComponent(doctorId || 0) + '&treatment_id=' + encodeURIComponent(treatmentId || 0))
			.then(function (resp) {
				select.disabled = false;
				if (!resp.ok) {
					select.innerHTML = '<option value="">' + (resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'Unavailable') + '</option>';
					if (callback) { callback([]); }
					return;
				}
				var slots = (resp.json && resp.json.data && resp.json.data.slots) || [];
				var html = '<option value="">' + 'Choose a time' + '</option>';
				for (var i = 0; i < slots.length; i++) {
					html += '<option value="' + escapeHtml(slots[i].time) + '">' + escapeHtml(slots[i].time + ' – ' + slots[i].end) + '</option>';
				}
				select.innerHTML = html;
				if (callback) { callback(slots); }
			})
			.catch(function () {
				select.disabled = false;
				select.innerHTML = '<option value="">' + 'Could not load times' + '</option>';
			});
	}

	/* ---- patient picker ---- */
	var patientInput = document.getElementById('ml-new-patient');
	var patientIdInput = document.getElementById('ml-new-patient-id');
	var patientResults = document.querySelector('.ml-patient-results');
	if (patientInput) {
		patientInput.addEventListener('input', function () {
			var q = (patientInput.value || '').trim().toLowerCase();
			if (patientIdInput) {
				patientIdInput.value = '';
			}
			if (!patientResults) {
				return;
			}
			if (!q) {
				patientResults.hidden = true;
				return;
			}
			var matches = patients.filter(function (p) {
				return (p.name + ' ' + p.uid + ' ' + p.email).toLowerCase().indexOf(q) !== -1;
			}).slice(0, 8);
			if (!matches.length) {
				patientResults.hidden = true;
				return;
			}
			patientResults.innerHTML = '';
			matches.forEach(function (p) {
				var li = document.createElement('li');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.textContent = p.name + (p.uid ? ' (' + p.uid + ')' : '');
				btn.setAttribute('data-id', p.id);
				li.appendChild(btn);
				patientResults.appendChild(li);
			});
			patientResults.hidden = false;
		});
		patientResults.addEventListener('click', function (event) {
			var btn = event.target.closest('button[data-id]');
			if (!btn) {
				return;
			}
			patientIdInput.value = btn.getAttribute('data-id');
			patientInput.value = btn.textContent;
			patientResults.hidden = true;
		});
	}

	/* ---- modal open triggers ---- */
	var modalContext = {};
	page.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-ml-open]');
		if (!trigger) {
			return;
		}
		event.preventDefault();
		var mode = trigger.getAttribute('data-ml-open');
		var id = trigger.getAttribute('data-ml-id');
		modalContext = { id: id ? parseInt(id, 10) : 0, action: '' };

		if (mode === 'new') {
			var dateInput = document.getElementById('ml-new-date');
			var prefilled = trigger.getAttribute('data-ml-date');
			if (prefilled && dateInput) {
				dateInput.value = prefilled;
				buildTimeOptions(document.getElementById('ml-new-time'), prefilled, parseInt((document.getElementById('ml-new-doctor') || { value: 0 }).value, 10));
			}
			openModal('new');
			return;
		}

		if (mode === 'detail') {
			openModal('detail');
			loadDetail(id);
			return;
		}

		if (mode === 'reschedule') {
			openModal('reschedule');
			loadRescheduleContext(id);
			return;
		}

		if (mode === 'cancel') {
			openModal('cancel');
			loadCancelContext(id);
			return;
		}
	});

	function loadDetail(id) {
var fields = document.getElementById('ml-detail-fields');
			fields.innerHTML = '<dd>Loading…</dd>';
		api('appointments/' + id).then(function (resp) {
			if (!resp.ok) {
				showError(resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'Could not load the appointment.');
				return;
			}
			var a = resp.json.data;
			var rows = [
				['Reference', a.booking_reference],
				['Status', a.status],
				['Patient', a.patient_name],
				['Contact', (a.patient_email || '') + (a.patient_phone ? ' / ' + a.patient_phone : '')],
				['Treatment', a.treatment_name || '—'],
				['Type', a.appointment_type],
				['Doctor', a.doctor_name || '—'],
				['Date', a.appointment_date],
				['Time', (a.start_time || '').slice(0, 5) + ' – ' + (a.end_time || '').slice(0, 5)],
				['Source', a.source],
				['Notes', a.notes || ''],
				['Patient message', a.patient_message || ''],
				['Created', a.created_at || ''],
				['Cancellation reason', a.cancellation_reason || '']
			];
			var html = '';
			rows.forEach(function (row) {
				if (!String(row[1] == null ? '' : row[1])) {
					return;
				}
				html += '<dt>' + escapeHtml(row[0]) + '</dt><dd>' + escapeHtml(row[1]) + '</dd>';
			});
			fields.innerHTML = html;
		});
	}

	function loadRescheduleContext(id) {
		var current = document.getElementById('ml-resched-current');
		api('appointments/' + id).then(function (resp) {
			if (!resp.ok) {
				showError('Could not load the appointment.');
				return;
			}
			var a = resp.json.data;
			if (current) {
				current.textContent = a.booking_reference + ' · ' + a.patient_name + ' · currently ' + a.appointment_date + ' ' + (a.start_time || '').slice(0, 5);
			}
			var dateEl = document.getElementById('ml-resched-date');
			dateEl.value = a.appointment_date;
			buildTimeOptions(document.getElementById('ml-resched-time'), a.appointment_date, parseInt(a.doctor_user_id || '0', 10), null);
		});
	}

	function loadCancelContext(id) {
		var current = document.getElementById('ml-cancel-current');
		api('appointments/' + id).then(function (resp) {
			if (!resp.ok) {
				showError('Could not load the appointment.');
				return;
			}
			var a = resp.json.data;
			if (current) {
				current.textContent = a.booking_reference + ' · ' + a.patient_name + ' · ' + a.appointment_date + ' ' + (a.start_time || '').slice(0, 5);
			}
		});
	}

	/* ---- the new appointment modal ---- */
	var newDate = document.getElementById('ml-new-date');
	var newDoctor = document.getElementById('ml-new-doctor');
	if (newDate) {
		newDate.addEventListener('change', function () {
			if (newDate.value) {
				buildTimeOptions(document.getElementById('ml-new-time'), newDate.value, parseInt(newDoctor.value || '0', 10), null);
			}
		});
	}
	if (newDoctor) {
		newDoctor.addEventListener('change', function () {
			if (newDate.value) {
				buildTimeOptions(document.getElementById('ml-new-time'), newDate.value, parseInt(newDoctor.value || '0', 10), null);
			}
		});
	}
	var newSubmit = document.getElementById('ml-new-submit');
	if (newSubmit) {
		newSubmit.addEventListener('click', function () {
			var patientId = parseInt(document.getElementById('ml-new-patient-id').value || '0', 10);
			var date = document.getElementById('ml-new-date').value;
			var time = document.getElementById('ml-new-time').value;
			if (!patientId) {
				showError('Please choose a patient.');
				return;
			}
			if (!date || !time) {
				showError('Please choose a date and time.');
				return;
			}
			newSubmit.disabled = true;
			api('appointments', {
				method: 'POST',
				body: {
					patient_id: patientId,
					doctor_user_id: parseInt(newDoctor.value || '0', 10),
					treatment_id: parseInt(document.getElementById('ml-new-treatment').value || '0', 10),
					appointment_date: date,
					start_time: time,
					appointment_type: document.getElementById('ml-new-type').value,
					status: document.getElementById('ml-new-status').value,
					notes: document.getElementById('ml-new-notes').value,
					source: 'admin'
				}
			}).then(function (resp) {
				newSubmit.disabled = false;
				if (!resp.ok) {
					showError(resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'The appointment could not be created.');
					return;
				}
				window.location.reload();
			});
		});
	}

	/* ---- reschedule ---- */
	var reschedSubmit = document.getElementById('ml-resched-submit');
	var reschedDate = document.getElementById('ml-resched-date');
	var reschedTime = document.getElementById('ml-resched-time');
	if (reschedDate) {
		reschedDate.addEventListener('change', function () {
			if (reschedDate.value && modalContext.id) {
				reschedTime.disabled = true;
				buildTimeOptions(reschedTime, reschedDate.value, parseInt((document.getElementById('ml-new-doctor') || { value: 0 }).value, 10), null);
			}
		});
	}
	if (reschedSubmit) {
		reschedSubmit.addEventListener('click', function () {
			if (!modalContext.id) {
				return;
			}
			if (!reschedDate.value || !reschedTime.value) {
				showError('Please choose a new date and time.');
				return;
			}
			reschedSubmit.disabled = true;
			api('appointments/' + modalContext.id + '/reschedule', {
				method: 'POST',
				body: { date: reschedDate.value, time: reschedTime.value }
			}).then(function (resp) {
				reschedSubmit.disabled = false;
				if (!resp.ok) {
					showError(resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'Rescheduling failed.');
					return;
				}
				window.location.reload();
			});
		});
	}

	/* ---- cancel ---- */
	var cancelSubmit = document.getElementById('ml-cancel-submit');
	if (cancelSubmit) {
		cancelSubmit.addEventListener('click', function () {
			if (!modalContext.id) {
				return;
			}
			cancelSubmit.disabled = true;
			api('appointments/' + modalContext.id + '/cancel', {
				method: 'POST',
				body: { reason: document.getElementById('ml-cancel-reason').value }
			}).then(function (resp) {
				cancelSubmit.disabled = false;
				if (!resp.ok) {
					showError(resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'Cancellation failed.');
					return;
				}
				window.location.reload();
			});
		});
	}

	/* ---- quick actions with confirmation ---- */
	var confirmOk = document.getElementById('ml-confirm-ok');
	page.addEventListener('click', function (event) {
		var btn = event.target.closest('[data-ml-action]');
		if (!btn) {
			return;
		}
		event.preventDefault();
		var action = btn.getAttribute('data-ml-action');
		var id = parseInt(btn.getAttribute('data-ml-id'), 10);
		var labels = {
			checkin: 'Check in this patient?',
			complete: 'Mark this appointment as completed?',
			no_show: 'Mark this patient as a no-show?'
		};
		modalContext = { id: id, action: action };
		document.getElementById('ml-confirm-title').textContent = labels[action] || 'Confirm?';
		document.getElementById('ml-confirm-text').textContent = 'This will update the appointment record for all staff.';
		openModal('confirm');
	});

	if (confirmOk) {
		confirmOk.addEventListener('click', function () {
			var pathByAction = { checkin: 'checkin', complete: 'complete', no_show: 'no-show' };
			confirmOk.disabled = true;
			api('appointments/' + modalContext.id + '/' + pathByAction[modalContext.action], { method: 'POST', body: {} })
				.then(function (resp) {
					confirmOk.disabled = false;
					if (!resp.ok) {
						showError(resp.json && resp.json.data && resp.json.data.message ? resp.json.data.message : 'Action failed.');
						return;
					}
					window.location.reload();
				});
		});
	}

	window.MLClinicAppointments = { rest: rest };
})();