/**
 * Debloat & Harden settings screen: search filter, "only enabled" filter and
 * confirmations. No dependencies.
 */
( function () {
	'use strict';

	var l10n = window.wpDebloat || {};

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var search = document.getElementById( 'wp-debloat-search' );
		var onlyEnabled = document.getElementById( 'wp-debloat-only-enabled' );
		var count = document.querySelector( '.wp-debloat-count' );
		var empty = document.querySelector( '.wp-debloat-empty' );
		var groups = document.querySelectorAll( '.wp-debloat-group' );

		function filter() {
			var terms = ( search ? search.value : '' ).toLowerCase().trim().split( /\s+/ ).filter( Boolean );
			var enabledOnly = onlyEnabled && onlyEnabled.checked;
			var shown = 0;

			groups.forEach( function ( group ) {
				var visibleInGroup = 0;
				group.querySelectorAll( '.wp-debloat-tweak' ).forEach( function ( row ) {
					var text = row.getAttribute( 'data-search' ) || '';
					var toggle = row.querySelector( '.wp-debloat-switch' );
					var match = terms.every( function ( term ) {
						return text.indexOf( term ) !== -1;
					} );
					if ( enabledOnly && toggle && ! toggle.checked ) {
						match = false;
					}
					row.hidden = ! match;
					if ( match ) {
						visibleInGroup++;
					}
				} );
				group.hidden = visibleInGroup === 0;
				shown += visibleInGroup;
			} );

			if ( empty ) {
				empty.hidden = shown !== 0;
			}
			if ( count && l10n.matches ) {
				count.textContent = l10n.matches.replace( '%d', String( shown ) );
			}
		}

		if ( search ) {
			search.addEventListener( 'input', filter );
		}
		if ( onlyEnabled ) {
			onlyEnabled.addEventListener( 'change', filter );
		}

		document.querySelectorAll( '.wp-debloat-switch' ).forEach( function ( toggle ) {
			toggle.addEventListener( 'change', function () {
				var row = toggle.closest( '.wp-debloat-tweak' );
				if ( toggle.checked && row && row.getAttribute( 'data-risk' ) === 'high' && l10n.confirmHigh ) {
					// eslint-disable-next-line no-alert
					if ( ! window.confirm( l10n.confirmHigh ) ) {
						toggle.checked = false;
					}
				}
				if ( row ) {
					row.classList.toggle( 'is-on', toggle.checked );
				}
			} );
		} );

		document.querySelectorAll( '[data-debloat-confirm="preset"]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function ( event ) {
				// eslint-disable-next-line no-alert
				if ( l10n.confirmPreset && ! window.confirm( l10n.confirmPreset ) ) {
					event.preventDefault();
				}
			} );
		} );

		filter();
	} );
}() );
