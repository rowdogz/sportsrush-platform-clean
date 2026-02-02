jQuery(document).ready(function ($) {
    // Attach click event to the dismiss button
    $(document).on('click', '.notice[data-notice="get-start"] button.notice-dismiss', function () {
        // Dismiss the notice via AJAX
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'soccer_club_dismissed_notice',
            },
            success: function () {
                // Remove the notice on success
                $('.notice[data-notice="example"]').remove();
            }
        });
    });
});

// WordClever – AI Content Writer plugin activation
document.addEventListener('DOMContentLoaded', function () {
    const soccer_club_button = document.getElementById('install-activate-button');

    if (!soccer_club_button) return;

    soccer_club_button.addEventListener('click', function (e) {
        e.preventDefault();

        const soccer_club_redirectUrl = soccer_club_button.getAttribute('data-redirect');

        // Step 1: Check if plugin is already active
        const soccer_club_checkData = new FormData();
        soccer_club_checkData.append('action', 'check_wordclever_activation');

        fetch(installWordcleverData.ajaxurl, {
            method: 'POST',
            body: soccer_club_checkData,
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data.active) {
                // Plugin is already active → just redirect
                window.location.href = soccer_club_redirectUrl;
            } else {
                // Not active → proceed with install + activate
                soccer_club_button.textContent = 'Installing & Activating...';

                const soccer_club_installData = new FormData();
                soccer_club_installData.append('action', 'install_and_activate_wordclever_plugin');
                soccer_club_installData.append('_ajax_nonce', installWordcleverData.nonce);

                fetch(installWordcleverData.ajaxurl, {
                    method: 'POST',
                    body: soccer_club_installData,
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        window.location.href = soccer_club_redirectUrl;
                    } else {
                        alert('Activation error: ' + (res.data?.message || 'Unknown error'));
                        soccer_club_button.textContent = 'Try Again';
                    }
                })
                .catch(error => {
                    alert('Request failed: ' + error.message);
                    soccer_club_button.textContent = 'Try Again';
                });
            }
        })
        .catch(error => {
            alert('Check request failed: ' + error.message);
        });
    });
});