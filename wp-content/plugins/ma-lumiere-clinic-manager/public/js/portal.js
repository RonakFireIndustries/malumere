/**
 * Patient portal — foundation JS scaffold.
 * No external requests, no console output, no data handling yet.
 * Portal interactions arrive with the patient portal phase.
 */
(function () {
	'use strict';

	window.MLPortal = window.MLPortal || {
		ready: function (fn) {
			if (document.readyState !== 'loading') {
				fn();
			} else {
				document.addEventListener('DOMContentLoaded', fn);
			}
		}
	};
})();