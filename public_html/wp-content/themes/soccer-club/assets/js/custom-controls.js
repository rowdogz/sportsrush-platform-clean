(function(api) {

    api.sectionConstructor['soccer-club-upsell'] = api.Section.extend({
        attachEvents: function() {},
        isContextuallyActive: function() {
            return true;
        }
    });
})(wp.customize);