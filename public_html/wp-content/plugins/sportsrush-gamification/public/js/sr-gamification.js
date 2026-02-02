/**
 * SportsRush Gamification - Frontend JavaScript
 */

(function($) {
    'use strict';

    var SR = {
        init: function() {
            this.hideAdminBarSearch();
            this.bindEvents();
            this.initNotifications();
            this.initMiniLeaderboards();
            this.initDailyPick();
            this.initCountdowns();
        },

        // Hide the admin bar search box that blocks the theme toggle
        hideAdminBarSearch: function() {
            var searchEl = document.getElementById('wp-admin-bar-search');
            if (searchEl) {
                searchEl.style.display = 'none';
            }
        },

        bindEvents: function() {
            // Notification bell toggle
            $(document).on('click', '.sr-bell-button', this.toggleNotifications);
            $(document).on('click', '.sr-mark-all-read', this.markAllRead);
            $(document).on('click', '.sr-notification-item.sr-unread', this.markNotificationRead);
            
            // Close notifications when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sr-notifications-bell').length) {
                    $('#sr-notifications-dropdown').hide();
                }
            });

            // Mini leaderboard tabs
            $(document).on('click', '.sr-mini-tab', this.switchTab);

            // Daily pick options
            $(document).on('click', '.sr-pick-option', this.selectPickOption);
            $(document).on('click', '.sr-submit-pick', this.submitDailyPick);
        },

        // Notifications
        initNotifications: function() {
            if (!srGamification.isLoggedIn) return;
            
            // Load notifications on first bell click
            var loaded = false;
            $(document).on('click', '.sr-bell-button', function() {
                if (!loaded) {
                    SR.loadNotifications();
                    loaded = true;
                }
            });
        },

        toggleNotifications: function(e) {
            e.stopPropagation();
            $('#sr-notifications-dropdown').toggle();
        },

        loadNotifications: function() {
            var $list = $('#sr-notifications-list');
            $list.html('<div class="sr-notifications-loading">' + srGamification.strings.loading + '</div>');

            $.ajax({
                url: srGamification.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sr_get_notifications',
                    nonce: srGamification.nonce
                },
                success: function(response) {
                    if (response.success && response.data.notifications.length > 0) {
                        var html = '';
                        response.data.notifications.forEach(function(notification) {
                            var readClass = notification.is_read === '1' ? 'sr-read' : 'sr-unread';
                            html += '<div class="sr-notification-item ' + readClass + '" data-notification-id="' + notification.id + '">';
                            html += '<span class="sr-notification-icon">' + SR.getNotificationIcon(notification.notification_type) + '</span>';
                            html += '<div class="sr-notification-content">';
                            html += '<p class="sr-notification-message">' + SR.escapeHtml(notification.payload.message || 'New notification') + '</p>';
                            html += '<span class="sr-notification-time">' + SR.timeAgo(notification.created_at) + '</span>';
                            html += '</div></div>';
                        });
                        $list.html(html);
                    } else {
                        $list.html('<div class="sr-no-notifications">' + srGamification.strings.noNotifications + '</div>');
                    }
                },
                error: function() {
                    $list.html('<div class="sr-no-notifications">' + srGamification.strings.error + '</div>');
                }
            });
        },

        markNotificationRead: function() {
            var $item = $(this);
            var notificationId = $item.data('notification-id');

            $.ajax({
                url: srGamification.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sr_mark_notification_read',
                    nonce: srGamification.nonce,
                    notification_id: notificationId
                },
                success: function(response) {
                    if (response.success) {
                        $item.removeClass('sr-unread').addClass('sr-read');
                        SR.updateBadgeCount(response.data.unread_count);
                    }
                }
            });
        },

        markAllRead: function(e) {
            e.preventDefault();
            e.stopPropagation();

            $.ajax({
                url: srGamification.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sr_mark_all_notifications_read',
                    nonce: srGamification.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.sr-notification-item').removeClass('sr-unread').addClass('sr-read');
                        SR.updateBadgeCount(0);
                    }
                }
            });
        },

        updateBadgeCount: function(count) {
            var $badge = $('.sr-bell-badge');
            if (count > 0) {
                if ($badge.length) {
                    $badge.text(count > 99 ? '99+' : count);
                } else {
                    $('.sr-bell-button').append('<span class="sr-bell-badge">' + (count > 99 ? '99+' : count) + '</span>');
                }
            } else {
                $badge.remove();
            }
        },

        getNotificationIcon: function(type) {
            var icons = {
                'rival_overtook': '&#128104;&#8205;&#128104;&#8205;&#128102;',
                'deadline_soon': '&#9200;',
                'rank_change': '&#128200;',
                'banter_ready': '&#128172;',
                'daily_pick_ready': '&#127919;',
                'achievement_earned': '&#127942;'
            };
            return icons[type] || '&#128276;';
        },

        // Mini Leaderboards
        initMiniLeaderboards: function() {
            // Show first tab by default
            $('.sr-mini-leaderboards-widget').each(function() {
                var $widget = $(this);
                $widget.find('.sr-mini-tab').first().addClass('active');
                $widget.find('.sr-mini-tab-content').first().addClass('active');
            });
        },

        switchTab: function(e) {
            e.preventDefault();
            var $tab = $(this);
            var $widget = $tab.closest('.sr-mini-leaderboards-widget');
            var target = $tab.data('tab');

            $widget.find('.sr-mini-tab').removeClass('active');
            $widget.find('.sr-mini-tab-content').removeClass('active');

            $tab.addClass('active');
            $widget.find('.sr-mini-tab-content[data-tab="' + target + '"]').addClass('active');
        },

        // Daily Pick
        initDailyPick: function() {
            // Inject dynamic CSS to override theme button styles
            SR.injectPickStyles();
            // Apply visual styles to pre-selected buttons on page load
            SR.applySelectedStyles();
        },

        // Inject a style tag with high-specificity CSS to override theme
        injectPickStyles: function() {
            if ($('#sr-pick-dynamic-styles').length) return; // Already injected
            
            var css = '' +
                '/* SR Daily Pick - Override theme button styles */' +
                'button.sr-pick-option,' +
                'button.sr-pick-option:not(:hover),' +
                '.sr-pick-options button.sr-pick-option,' +
                '.sr-daily-pick-form button.sr-pick-option {' +
                '  background: #ffffff !important;' +
                '  background-image: none !important;' +
                '  border: 3px solid #e5e7eb !important;' +
                '  color: #374151 !important;' +
                '}' +
                'button.sr-pick-option.selected,' +
                'button.sr-pick-option.selected:not(:hover),' +
                '.sr-pick-options button.sr-pick-option.selected,' +
                '.sr-daily-pick-form button.sr-pick-option.selected {' +
                '  background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;' +
                '  background-image: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;' +
                '  border-color: #10b981 !important;' +
                '  color: #ffffff !important;' +
                '  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4) !important;' +
                '  transform: translateY(-2px) !important;' +
                '}' +
                'button.sr-pick-option.sr-pick-locked.selected,' +
                'button.sr-pick-option.sr-pick-locked.selected:not(:hover) {' +
                '  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;' +
                '  background-image: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;' +
                '  border-color: #6b7280 !important;' +
                '  color: #ffffff !important;' +
                '  opacity: 0.8 !important;' +
                '}' +
                'button.sr-pick-option.sr-pick-locked:not(.selected),' +
                'button.sr-pick-option.sr-pick-locked:not(.selected):not(:hover) {' +
                '  background: #f3f4f6 !important;' +
                '  background-image: none !important;' +
                '  border-color: #d1d5db !important;' +
                '  color: #9ca3af !important;' +
                '  opacity: 0.6 !important;' +
                '}' +
                '/* Hide theme pseudo-elements */' +
                'button.sr-pick-option::before,' +
                'button.sr-pick-option::after {' +
                '  display: none !important;' +
                '  content: none !important;' +
                '}';
            
            $('<style id="sr-pick-dynamic-styles">' + css + '</style>').appendTo('head');
        },

        // Apply inline styles to selected buttons to override theme
        applySelectedStyles: function() {
            // The dynamic CSS handles the styling now
            // This function just ensures classes are correct
        },

        selectPickOption: function() {
            var $option = $(this);
            var $form = $option.closest('.sr-daily-pick-form');
            var choice = $option.data('value');
            var pickId = $form.find('input[name="sr_daily_pick_id"]').val();
            var $status = $form.find('.sr-pick-status');

            // Don't do anything if already saving or same choice
            if ($form.hasClass('sr-saving')) {
                return;
            }

            // Update visual selection immediately
            $form.find('.sr-pick-option').removeClass('selected');
            $option.addClass('selected');
            $form.find('input[name="sr_pick_choice"]').val(choice);
            
            // Apply inline styles to ensure visual feedback
            SR.applySelectedStyles();

            // Show saving status
            $form.addClass('sr-saving');
            $status.text('Saving...').removeClass('sr-status-success sr-status-error').addClass('sr-status-saving');

            // Auto-submit via AJAX
            $.ajax({
                url: srGamification.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sr_submit_daily_pick',
                    nonce: srGamification.nonce,
                    daily_pick_id: pickId,
                    choice: choice
                },
                success: function(response) {
                    $form.removeClass('sr-saving');
                    if (response.success) {
                        $status.text(response.data.message).removeClass('sr-status-saving sr-status-error').addClass('sr-status-success');
                        // Clear status after 2 seconds
                        setTimeout(function() {
                            $status.text('');
                        }, 2000);
                    } else {
                        $status.text(response.data.message || 'Error saving pick').removeClass('sr-status-saving sr-status-success').addClass('sr-status-error');
                        // Revert selection on error
                        $option.removeClass('selected');
                    }
                },
                error: function() {
                    $form.removeClass('sr-saving');
                    $status.text('Error saving pick').removeClass('sr-status-saving sr-status-success').addClass('sr-status-error');
                    // Revert selection on error
                    $option.removeClass('selected');
                }
            });
        },

        // Legacy submit handler (no longer used but kept for compatibility)
        submitDailyPick: function(e) {
            e.preventDefault();
        },

        // Countdowns
        initCountdowns: function() {
            $('.sr-countdown').each(function() {
                var $el = $(this);
                var target = new Date($el.data('target')).getTime();

                setInterval(function() {
                    var now = new Date().getTime();
                    var diff = target - now;

                    if (diff <= 0) {
                        $el.text('Locked');
                        return;
                    }

                    var hours = Math.floor(diff / (1000 * 60 * 60));
                    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                    $el.text(SR.pad(hours) + ':' + SR.pad(minutes) + ':' + SR.pad(seconds));
                }, 1000);
            });
        },

        // Utilities
        pad: function(num) {
            return num < 10 ? '0' + num : num;
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        },

        timeAgo: function(dateString) {
            var date = new Date(dateString);
            var now = new Date();
            var diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return date.toLocaleDateString();
        }
    };

    $(document).ready(function() {
        SR.init();
    });

})(jQuery);
