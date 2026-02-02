/**
 * Admin - List Table.
 *
 * @package Simple_Page_Access_Restriction.
 */

;( function ( $, window, document, undefined ) {
	'use strict';

	// Set the events.
	var events = {
		onDocumentReady: function onDocumentReady() {
			// Move the elements.
			functions.moveElements();
		},
	};

	// Set the functions.
	var functions = {
		init: function init() {
			// Bind the ready event.
			$( events.onDocumentReady );
		},
		moveElements: function moveElements() {
			// Move the bulk edit fields.
			$( '#ps-simple-par-bulk-edit' ).insertAfter( $( '#bulk-edit .inline-edit-wrapper fieldset' ).last() );
		},
	};

	// Set the object.
	var pssparAdminListTable = {
		events: events,
		functions: functions,
	};

	// Init.
	pssparAdminListTable.functions.init();

} )( jQuery, window, document );
