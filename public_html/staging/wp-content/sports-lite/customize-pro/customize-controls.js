( function( api ) {
	// Extends our custom "sports-lite" section.
	api.sectionConstructor['sports-lite'] = api.Section.extend( {
		// No events for this type of section.
		attachEvents: function () {},
		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );
} )( wp.customize );