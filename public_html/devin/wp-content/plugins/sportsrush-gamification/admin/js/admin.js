/**
 * SportsRush Gamification - Admin JavaScript
 */

(function($) {
    'use strict';

    var SRAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Run cron job now
            $(document).on('click', '.sr-run-cron-now', this.runCronJob);
        },

        runCronJob: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var job = $btn.data('job');
            var originalText = $btn.text();

            $btn.addClass('running').text(srAdmin.strings.running);

            $.ajax({
                url: srAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sr_run_cron_now',
                    nonce: srAdmin.nonce,
                    job: job
                },
                success: function(response) {
                    if (response.success) {
                        $btn.text(srAdmin.strings.success);
                        setTimeout(function() {
                            $btn.removeClass('running').text(originalText);
                        }, 2000);
                        
                        // Update last run time if available
                        if (response.data.log) {
                            // Could update the table here
                        }
                    } else {
                        alert(response.data.message || srAdmin.strings.error);
                        $btn.removeClass('running').text(originalText);
                    }
                },
                error: function() {
                    alert(srAdmin.strings.error);
                    $btn.removeClass('running').text(originalText);
                }
            });
        }
    };

    $(document).ready(function() {
        SRAdmin.init();
    });

})(jQuery);
