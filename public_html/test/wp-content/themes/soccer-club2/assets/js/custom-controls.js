(function(api) {

    api.sectionConstructor['soccer-club-upsell'] = api.Section.extend({
        attachEvents: function() {},
        isContextuallyActive: function() {
            return true;
        }
    });

    const soccer_club_section_lists = ['banner', 'service'];
    soccer_club_section_lists.forEach(soccer_club_homepage_scroll);

    function soccer_club_homepage_scroll(item, index) {
        item = item.replace(/-/g, '_');
        wp.customize.section('soccer_club_' + item + '_section', function(section) {
            section.expanded.bind(function(isExpanding) {
                wp.customize.previewer.send(item, { expanded: isExpanding });
            });
        });
    }
})(wp.customize);