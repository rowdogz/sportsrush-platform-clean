/**
 * Rent Wallet Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Auto-select landlord when property is selected
        $('#property_id').on('change', function() {
            var landlordId = $(this).find(':selected').data('landlord');
            if (landlordId) {
                $('#landlord_user_id').val(landlordId);
            }
        });

        // Confirm dangerous actions
        $('form[data-confirm]').on('submit', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

})(jQuery);
