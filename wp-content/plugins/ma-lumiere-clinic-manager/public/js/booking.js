/**
 * Public six-step booking widget.
 * Steps: treatment -> date -> time -> details -> review -> confirmation.
 * Talks only to the clinic REST endpoints. Requires MLBooking config
 * (rendered by the [ml_booking] shortcode).
 */
(function () {
	'use strict';

	var cfg = window.MLBooking;
	if (!cfg || !cfg.restUrl) {
		return;
	}

	var S = cfg.strings || {};
	var root = null;
	var body = null;
	var steps = null;
	var state = {
		step: -1,
		treatment: 0,
		treatmentData: null,
		date: '',
		time: '',
		end: '',
		patient: {
			first_name: '',
			last_name: '',
			patient_email: '',
			phone: '',
			date_of_birth: '',
			gender: '',
			patient_message: '',
			notes: ''
		},
		idempotency: Math.random().toString(36).slice(2) + Date.now().toString(36),
		submitting: false
	};

	var esc = function (value) {
		if (value === null || value === undefined) {
			return '';
		}
		return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
	};

	var escAttr = function (value) {
		return esc(value);
	};

	var base = function (path) {
		return cfg.restUrl.replace(/\/$/, '') + '/' + path;
	};

	var findTreatment = function (id) {
		id = parseInt(id, 10);
		var list = cfg.treatments || [];
		for (var i = 0; i < list.length; i++) {
			if (parseInt(list[i].id, 10) === id) {
				return list[i];
			}
		}
		return null;
	};

	var nowLocal = function () {
		// Site "now" (server resolved) as {Y, M, D, h, m}.
		var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(cfg.now || '');
		if (!m) {
			return null;
		}
		return { y: +m[1], mo: +m[2], d: +m[3], h: +m[4], min: +m[5] };
	};

	var isPastTick = function (date, time) {
		var now = nowLocal();
		if (!now) {
			return false;
		}
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(date || '');
		var t = /^(\d{2}):(\d{2})$/.exec(time || '');
		if (!m || !t) {
			return false;
		}
		var d = { y: +m[1], mo: +m[2], d: +m[3], h: +t[1], min: +t[2] };
		if (d.y !== now.y || d.mo !== now.mo || d.d !== now.d) {
			return false;
		}
		if (d.h > now.h) {
			return false;
		}
		if (d.h === now.h && d.min > now.min) {
			return false;
		}
		return true;
	};

	/* ------------------------- step rendering ------------------------- */

	var stepper = function () {
		steps = root.querySelectorAll('.ml-booking__step');
		for (var i = 0; i < steps.length; i++) {
			steps[i].classList.remove('ml-booking__step--active', 'ml-booking__step--done');
			if (i === state.step) {
				steps[i].classList.add('ml-booking__step--active');
			} else if (i < state.step) {
				steps[i].classList.add('ml-booking__step--done');
			}
		}
	};

	var nav = function (options) {
		options = options || {};
		var out = '<div class="ml-booking__nav">';
		if (options.back) {
			out += '<button type="button" class="ml-booking__btn-back" data-action="back">' + esc(S.back || 'Back') + '</button>';
		} else {
			out += '<span></span>';
		}
		if (options.next) {
			out += '<button type="button" class="ml-booking__btn-primary" data-action="next">' + esc(S.next || 'Continue') + '</button>';
		}
		out += '</div>';
		return out;
	};

	var apiError = function (json) {
		json = json || {};
		var d = json.data || {};
		if (d && d.errors) {
			// First field error, most helpful.
			for (var key in d.errors) {
				if (Object.prototype.hasOwnProperty.call(d.errors, key)) {
					return String(d.errors[key]);
				}
			}
		}
		return d.message || S.error || 'Something went wrong.';
	};

	/* ------------------------------ steps ------------------------------ */

	var renderTreatments = function () {
		state.step = 0;
		stepper();
		var list = cfg.treatments || [];
		var html = '<h3>' + esc(S.treatment || 'Treatment') + '</h3>';
		html += '<p class="ml-booking__intro">' + esc(S.stepIntro || 'Choose how you wish to book.') + '</p>';
		html += '<div class="ml-booking__treatments">';
		for (var i = 0; i < list.length; i++) {
			var t = list[i];
			var mins = parseInt(t.duration, 10);
			var selected = parseInt(t.id, 10) === state.treatment;
			html += '<button type="button" class="ml-booking__treatment' + (selected ? ' ml-booking__treatment--selected' : '') + '" data-treatment="' + escAttr(t.id) + '" role="radio" aria-checked="' + (selected ? 'true' : 'false') + '">';
			html += '<img src="' + escAttr(t.image || '') + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">';
			html += '<span class="ml-booking__treatment-body">';
			html += '<span class="ml-booking__treatment-duration">' + (mins > 0 ? esc(mins + ' ' + (S.minutes || 'min')) : '') + '</span>';
			html += '<span class="ml-booking__treatment-name">' + esc(t.name) + '</span>';
			if (t.short_desc) {
				html += '<span class="ml-booking__treatment-desc">' + esc(t.short_desc) + '</span>';
			}
			html += '<span class="ml-booking__treatment-cta">' + esc(S.select || 'Select') + ' →</span>';
			html += '</span></button>';
		}
		html += '</div>';
		body.innerHTML = html;
		body.setAttribute('aria-busy', 'false');
	};

	var loadDates = function () {
		body.setAttribute('aria-busy', 'true');
		body.innerHTML = '<p class="ml-booking__loading">' + esc(S.loading || 'Loading…') + '</p>';
		fetch(base('appointments/availability'), {
			headers: { 'Accept': 'application/json' }
		}).then(function (resp) {
			return resp.json().then(function (json) {
				if (!resp.ok) {
					throw json;
				}
				return json;
			});
		}).then(function (json) {
			renderDates(json.data && json.data.dates ? json.data.dates : []);
		}).catch(function () {
			body.innerHTML = '<p class="ml-booking__empty">' + esc(S.error || 'Something went wrong.') + '</p>';
			body.setAttribute('aria-busy', 'false');
		});
	};

	var renderDates = function (dates) {
		state.step = 1;
		stepper();
		if (!dates.length) {
			body.innerHTML = '<p class="ml-booking__empty">' + esc(S.noDates || 'No appointment slots are available in the coming days.') + '</p>';
			body.setAttribute('aria-busy', 'false');
			return;
		}
		var html = '<h3>' + esc(S.date || 'Date') + '</h3>';
		html += '<p class="ml-booking__hint">' + esc(state.treatmentData ? state.treatmentData.name : '') + '</p>';
		html += '<div class="ml-booking__dates">';
		var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
		for (var i = 0; i < dates.length; i++) {
			var d = dates[i].date;
			var parts = d.split('-');
			var dt = new Date(+parts[0], +parts[1] - 1, +parts[2]);
			var selected = d === state.date;
			html += '<button type="button" class="ml-booking__choice' + (selected ? ' ml-booking__choice--selected' : '') + '" data-date="' + escAttr(d) + '">';
			html += '<span class="ml-booking__choice-value">' + esc(dayNames[dt.getDay()]) + ' ' + +parts[2] + '</span>';
			html += '<span class="ml-booking__choice-meta">' + esc(monthNames[dt.getMonth()]) + ' · ' + esc(dates[i].open) + '–' + esc(dates[i].close) + '</span>';
			html += '</button>';
		}
		html += '</div>';
		html += nav({ back: true });
		body.innerHTML = html;
		body.setAttribute('aria-busy', 'false');
	};

	var loadSlots = function () {
		body.setAttribute('aria-busy', 'true');
		body.innerHTML = '<p class="ml-booking__loading">' + esc(S.loading || 'Loading…') + '</p>';
		var url = base('appointments/availability') + '?date=' + encodeURIComponent(state.date) + '&treatment_id=' + encodeURIComponent(state.treatment);
		fetch(url, {
			headers: { 'Accept': 'application/json' }
		}).then(function (resp) {
			return resp.json().then(function (json) {
				if (!resp.ok) {
					throw json;
				}
				return json;
			});
		}).then(function (json) {
			renderSlots(json.data && json.data.slots ? json.data.slots : []);
		}).catch(function () {
			body.innerHTML = '<p class="ml-booking__empty">' + esc(S.error || 'Something went wrong.') + '</p>';
			body.setAttribute('aria-busy', 'false');
		});
	};

	var renderSlots = function (slots) {
		state.step = 2;
		stepper();
		var html = '<h3>' + esc(S.time || 'Time') + '</h3>';
		html += '<p class="ml-booking__hint">' + esc(state.treatmentData ? state.treatmentData.name : '') + ' · ' + esc(state.date) + '</p>';
		if (!slots.length) {
			html += '<p class="ml-booking__empty">' + esc(S.noSlots || 'No free slots on this day.') + '</p>';
			html += nav({ back: true });
			body.innerHTML = html;
			body.setAttribute('aria-busy', 'false');
			return;
		}
		html += '<div class="ml-booking__times">';
		for (var i = 0; i < slots.length; i++) {
			var past = isPastTick(state.date, slots[i].time);
			html += '<button type="button" class="ml-booking__choice' + (slots[i].time === state.time ? ' ml-booking__choice--selected' : '') + (past ? ' ml-booking__choice--disabled' : '') + '" data-time="' + escAttr(slots[i].time) + '"' + (past ? ' disabled' : '') + '>';
			html += '<span class="ml-booking__choice-value">' + esc(slots[i].time) + '</span>';
			html += '<span class="ml-booking__choice-meta">' + esc(slots[i].end || '') + '</span>';
			html += '</button>';
		}
		html += '</div>';
		html += nav({ back: true });
		body.innerHTML = html;
		body.setAttribute('aria-busy', 'false');
	};

	var renderDetails = function () {
		state.step = 3;
		stepper();
		var p = state.patient;
		var html = '<h3>' + esc(S.details || 'Your details') + '</h3>';
		html += '<p class="ml-booking__hint">' + esc((state.treatmentData ? state.treatmentData.name : '') + ' · ' + state.date + (state.time ? ' · ' + state.time : '')) + '</p>';
		html += '<form class="ml-booking__form" data-form="details" novalidate>';
		html += '<input class="ml-booking__hp" type="text" name="' + escAttr('ml_website') + '" tabindex="-1" autocomplete="off" aria-hidden="true">';
		html += field('first_name', S.firstName || 'First name', p.first_name, true);
		html += field('last_name', S.lastName || 'Last name', p.last_name, true);
		html += field('patient_email', S.email || 'Email', p.patient_email, true, 'email');
		html += field('phone', S.phone || 'Phone', p.phone, true, 'tel');
		html += field('date_of_birth', S.dob || 'Date of birth (optional)', p.date_of_birth, false, 'date');
		html += '<div class="ml-booking__field"><label for="ml-field-gender">' + esc(S.gender || 'Gender (optional)') + '</label>';
		html += '<select id="ml-field-gender" name="gender"><option value="">—</option><option value="female"' + (p.gender === 'female' ? ' selected' : '') + '>Female</option><option value="male"' + (p.gender === 'male' ? ' selected' : '') + '>Male</option><option value="other"' + (p.gender === 'other' ? ' selected' : '') + '>Other</option><option value="prefer_not_to_say"' + (p.gender === 'prefer_not_to_say' ? ' selected' : '') + '>Prefer not to say</option></select>';
		html += '<span class="ml-booking__field-error"></span></div>';
		html += '<div class="ml-booking__field ml-booking__field--full"><label for="ml-field-message">' + esc(S.message || 'Anything we should know? (optional)') + '</label>';
		html += '<textarea id="ml-field-message" name="patient_message" rows="3">' + esc(p.patient_message) + '</textarea>';
		html += '<span class="ml-booking__field-error"></span></div>';
		html += '<div class="ml-booking__field ml-booking__field--full"><label class="ml-booking__consent"><input type="checkbox" name="consent" value="1"> <span>' + esc(S.consent || 'I agree to be contacted about my appointment.') + '</span></label>';
		html += '<span class="ml-booking__field-error"></span></div>';
		html += '</form>';
		html += nav({ back: true, next: true });
		body.innerHTML = html;
		body.setAttribute('aria-busy', 'false');
	};

	var field = function (name, label, value, required, type) {
		type = type || 'text';
		return '<div class="ml-booking__field"><label for="ml-field-' + escAttr(name) + '">' + esc(label) + (required ? ' *' : '') + '</label>' +
			'<input id="ml-field-' + escAttr(name) + '" type="' + escAttr(type) + '" name="' + escAttr(name) + '" value="' + escAttr(value) + '"' + (required ? ' required' : '') + '>' +
			'<span class="ml-booking__field-error"></span></div>';
	};

	var validateDetails = function () {
		var form = body.querySelector('[data-form="details"]');
		var errors = {};
		var ok = true;
		var check = function (name, message, fn) {
			var el = form.querySelector('input[name="' + name + '"]');
			var raw = el ? el.value : '';
			var err = el ? el.parentNode.querySelector('.ml-booking__field-error') : null;
			var stateField = fn(raw);
			if (stateField.error) {
				ok = false;
				errors[name] = stateField.message;
				if (err) { err.textContent = stateField.message; }
			} else {
				state.patient[name] = stateField.value;
				if (err) { err.textContent = ''; }
			}
		};
		// eslint-disable-next-line no-shadow
		var clean = function (raw) {
			return { error: !raw.trim(), message: S.required || 'This field is required.', value: raw.trim() };
		};
		check('first_name', '', clean);
		check('last_name', '', clean);
		check('phone', '', clean);
		check('patient_email', '', function (raw) {
			var trimmed = raw.trim();
			if (!trimmed) {
				return { error: true, message: S.required || 'This field is required.', value: '' };
			}
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
				return { error: true, message: S.emailInvalid || 'Please enter a valid email address.', value: trimmed };
			}
			return { error: false, message: '', value: trimmed };
		});
		state.patient.gender = form.querySelector('select[name="gender"]').value;
		state.patient.patient_message = form.querySelector('textarea[name="patient_message"]').value;
		state.patient.date_of_birth = form.querySelector('input[name="date_of_birth"]').value;
		var consent = form.querySelector('input[name="consent"]');
		if (!consent.checked) {
			ok = false;
			var cerr = consent.parentNode.parentNode.querySelector('.ml-booking__field-error');
			if (cerr) { cerr.textContent = S.consentRequired || 'Please confirm you agree to be contacted.'; }
		} else {
			var cok = consent.parentNode.parentNode.querySelector('.ml-booking__field-error');
			if (cok) { cok.textContent = ''; }
		}
		return { ok: ok, errors: errors };
	};

	var renderReview = function () {
		state.step = 4;
		stepper();
		var t = state.treatmentData ? state.treatmentData.name : 'Consultation';
		var mins = state.treatmentData ? parseInt(state.treatmentData.duration, 10) : 0;
		var p = state.patient;
		var rows = [
			[S.treatment || 'Treatment', t, 0],
			[S.date || 'Date', state.date, 1],
			[S.time || 'Time', state.time + (state.end && state.end !== state.time ? '–' + state.end : ''), 2],
			[S.firstName || 'First name', p.first_name, 3],
			[S.lastName || 'Last name', p.last_name, 3],
			[S.email || 'Email', p.patient_email, 3],
			[S.phone || 'Phone', p.phone, 3],
			[S.dob || 'Date of birth', p.date_of_birth || '—', 3]
		];
		var html = '<h3>' + esc(S.review || 'Review & confirm') + '</h3>';
		html += '<div class="ml-booking__summary">';
		for (var i = 0; i < rows.length; i++) {
			if (!rows[i][1]) {
				continue;
			}
			html += '<div class="ml-booking__summary-row"><span class="ml-booking__summary-key">' + esc(rows[i][0]) + '</span>';
			html += '<span class="ml-booking__summary--right"><span class="ml-booking__summary-value">' + esc(rows[i][1]) + '</span> ';
			html += '<button type="button" class="ml-booking__summary-change" data-goto="' + rows[i][2] + '">' + esc(S.change || 'Change') + '</button></span>';
			html += '</div>';
		}
		html += '</div>';
		html += '<button type="button" class="ml-booking__submit" data-action="submit">' + esc(S.confirmBtn || 'Confirm my booking') + '</button>';
		html += nav({ back: true });
		body.innerHTML = html;
		body.setAttribute('aria-busy', 'false');
	};

	var renderSuccess = function (appt) {
		state.step = 5;
		stepper();
		body.innerHTML = '<div class="ml-booking__success">' +
			'<span class="ml-booking__check">✓</span>' +
			'<h3>' + esc(S.confirmed || 'Booking confirmed') + '</h3>' +
			'<p>' + esc(S.with || 'Consultation') + ': ' + esc(state.treatmentData ? state.treatmentData.name : '') + '</p>' +
			'<p>' + esc(state.date) + ' at ' + esc(state.time) + '</p>' +
			'<p class="ml-booking__success-ref">' + esc(S.ref || 'Reference') + ': ' + esc(appt.booking_reference || '') + '</p>' +
			'<div class="ml-booking__success-box">' +
				summaryLine(S.date || 'Date', state.date) +
				summaryLine(S.time || 'Time', state.time + (state.end ? '–' + state.end : '')) +
				summaryLine(S.treatment || 'Treatment', state.treatmentData ? state.treatmentData.name : 'Consultation') +
				summaryLine('Reference', appt.booking_reference || '—') +
			'</div>' +
			'<div class="ml-booking__calendar">' + calendarLinks(appt) + '</div>' +
			'<div class="ml-booking__actions">' +
				'<a href="/" class="ml-booking__btn ml-booking__btn--ghost">' + esc(S.home || 'Return to homepage') + '</a>' +
				'<button type="button" class="ml-booking__btn ml-booking__btn--primary" data-action="again">' + esc(S.bookAnother || 'Book another appointment') + '</button>' +
			'</div>' +
			'</div>';
		body.setAttribute('aria-busy', 'false');
	};

	var summaryLine = function (key, value) {
		return '<div class="ml-booking__summary-row"><span class="ml-booking__summary-key">' + esc(key) + '</span><span class="ml-booking__summary-value">' + esc(value) + '</span></div>';
	};

	var calendarLinks = function (appt) {
		var parts = state.date.split('-');
		var timeParts = state.time.split(':');
		var mins = state.treatmentData ? parseInt(state.treatmentData.duration, 10) : 60;
		var start = toUtc(+parts[0], +parts[1], +parts[2], +timeParts[0], +timeParts[1]);
		var end = new Date(start.getTime() + (mins || 60) * 60000);
		var fmt = function (d) {
			return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
		};
		var summary = 'Consultation – ' + (state.treatmentData ? state.treatmentData.name : '');
		var details = 'Appointment ' + (appt.booking_reference || '') + ' with the clinic.';
		var gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' + encodeURIComponent(summary) +
			'&dates=' + fmt(start) + '/' + fmt(end) +
			'&details=' + encodeURIComponent(details);
		var href = gcal;
		return '<a href="' + escAttr(href) + '" target="_blank" rel="noopener noreferrer">' + esc(S.googleCal || 'Google Calendar') + '</a>';
	};

	var toUtc = function (y, m, d, h, min) {
		var offset = (parseInt(cfg.gmtOffsetSeconds, 10) || 0);
		return new Date(Date.UTC(y, m - 1, d, h, min) - offset * 1000);
	};

	var icsLink = function (appt, summary, details, start, end) {
		var escIcs = function (s) {
			return String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,\s*/g, ', ').replace(/\n/g, '\\n');
		};
		var fmt = function (d) {
			return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
		};
		var ics = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Ma Lumière Clinic//Booking//EN',
			'BEGIN:VEVENT',
			'UID:' + (appt.booking_reference || 'ml-book') + '@malumere.clinic',
			'DTSTAMP:' + fmt(new Date()),
			'DTSTART:' + fmt(start),
			'DTEND:' + fmt(end),
			'SUMMARY:' + escIcs(summary),
			'DESCRIPTION:' + escIcs(details),
			'END:VEVENT',
			'END:VCALENDAR'
		].join('\r\n');
		return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(ics);
	};

	/* --------------------------- submission --------------------------- */

	var submit = function () {
		if (state.submitting) {
			return;
		}
		state.submitting = true;
		var btn = body.querySelector('.ml-booking__submit');
		if (btn) {
			btn.disabled = true;
			btn.textContent = S.loading || 'Loading…';
		}
		var payload = {
			_nonce: cfg.bookingNonce,
			first_name: state.patient.first_name,
			last_name: state.patient.last_name,
			patient_email: state.patient.patient_email,
			phone: state.patient.phone,
			date_of_birth: state.patient.date_of_birth,
			gender: state.patient.gender,
			patient_message: state.patient.patient_message,
			notes: '',
			treatment_id: state.treatment,
			doctor_id: cfg.doctorId || 0,
			date: state.date,
			time: state.time,
			appointment_type: 'new_consultation',
			ml_website: '',
			idempotency_key: state.idempotency
		};
		fetch(base('appointments/book'), {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.restNonce || ''
			},
			body: JSON.stringify(payload)
		}).then(function (resp) {
			return resp.json().then(function (json) {
				return { ok: resp.ok, status: resp.status, json: json };
			});
		}).then(function (res) {
			state.submitting = false;
			if (res.ok && res.status === 201) {
				renderSuccess(res.json.data || {});
				return;
			}
			var msg;
			if (res.status === 429 || (res.json && res.json.data && res.json.data.code === 'ml_booking_limited')) {
				msg = (res.json && res.json.data && res.json.data.message) ? res.json.data.message : (S.errorLimit || 'Too many requests. Please wait a moment and try again.');
			} else if (res.status === 403) {
				msg = S.errorSession || 'Your session could not be verified. Please refresh the page and try again.';
			} else {
				msg = apiError(res.json);
			}
			body.innerHTML = '<div class="ml-booking__alert ml-booking__alert--error">' + esc(msg) + '</div>';
			renderDetails();
		}).catch(function () {
			state.submitting = false;
			body.innerHTML = '<div class="ml-booking__alert ml-booking__alert--error">' + esc(S.error || 'Something went wrong.') + '</div>';
			renderDetails();
		});
	};

	/* ------------------------------ events ------------------------------ */

	var goTo = function (step) {
		if (step === 0) {
			state.step = -1;
			renderTreatments();
		} else if (step === 1) {
			loadDates();
		} else if (step === 2) {
			loadSlots();
		} else if (step === 3) {
			renderDetails();
		} else if (step === 4) {
			renderReview();
		}
	};

	var onClick = function (e) {
		var target = e.target;
		// pick action
		var actionEl = target.closest('[data-action]');
		var treatmentEl = target.closest('[data-treatment]');
		var dateEl = target.closest('[data-date]');
		var timeEl = target.closest('[data-time]');
		var gotoEl = target.closest('[data-goto]');

		if (actionEl) {
			var action = actionEl.getAttribute('data-action');
			if (action === 'next') {
				if (state.step === 3) {
					var v = validateDetails();
					if (v.ok) {
						renderReview();
					}
				}
				return;
			}
			if (action === 'back') {
				goTo(state.step - 1);
				return;
			}
			if (action === 'submit') {
				submit();
				return;
			}
			if (action === 'again') {
				state.treatment = 0;
				state.treatmentData = null;
				state.date = '';
				state.time = '';
				state.end = '';
				state.patient = { first_name: '', last_name: '', patient_email: '', phone: '', date_of_birth: '', gender: '', patient_message: '', notes: '' };
				state.idempotency = Math.random().toString(36).slice(2) + Date.now().toString(36);
				renderTreatments();
				return;
			}
			return;
		}
		if (treatmentEl) {
			state.treatment = parseInt(treatmentEl.getAttribute('data-treatment'), 10);
			state.treatmentData = findTreatment(state.treatment);
			state.date = '';
			state.time = '';
			body.setAttribute('aria-busy', 'true');
			loadDates();
			return;
		}
		if (dateEl) {
			state.date = dateEl.getAttribute('data-date');
			state.time = '';
			loadSlots();
			return;
		}
		if (timeEl) {
			if (timeEl.disabled) {
				return;
			}
			state.time = timeEl.getAttribute('data-time');
			state.end = '';
			if (state.treatmentData) {
				var mins = parseInt(state.treatmentData.duration, 10);
				if (mins > 0) {
					var tp = state.time.split(':');
					var d = new Date(2000, 0, 1, +tp[0], +tp[1] + mins);
					state.end = (d.getHours() < 10 ? '0' : '') + d.getHours() + ':' + (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
				}
			}
			renderDetails();
			return;
		}
		if (gotoEl) {
			goTo(parseInt(gotoEl.getAttribute('data-goto'), 10));
			return;
		}
	};

	/* ---------------------------- bootstrap ---------------------------- */

	var boot = function () {
		root = document.getElementById('ml-booking');
		if (!root) {
			return;
		}
		body = root.querySelector('.ml-booking__body');
		// Preselect from ?treatment= when the shortcode asked for it.
		state.treatmentData = findTreatment(cfg.preselectTreatment);
		if (state.treatmentData) {
			state.treatment = parseInt(state.treatmentData.id, 10);
		}
		root.addEventListener('click', onClick);
		if (state.treatmentData) {
			goTo(1);
		} else {
			renderTreatments();
		}
	};

	if (document.readyState !== 'loading') {
		boot();
	} else {
		document.addEventListener('DOMContentLoaded', boot);
	}
})();