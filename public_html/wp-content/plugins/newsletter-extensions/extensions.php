<?php

/*
  Plugin Name: Newsletter - Addons Manager and Support
  Plugin URI: https://www.thenewsletterplugin.com/documentation/extensions-extension
  Description: Manages all premium and free Newsletter addons directly from your blog
  Version: 1.3.7
  Requires at least: 5.6
  Requires PHP: 7.0
  Author: The Newsletter Team
  Author URI: https://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 */

add_action('newsletter_loaded', function ($version) {
    if (version_compare($version, '8.7.3', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>Newsletter plugin upgrade required by <strong>Newsletter - Addons Manager and Support</strong>.</p></div>';
        });
    } else {
        include_once __DIR__ . '/plugin.php';
        new NewsletterExtensions('1.3.7');
    }
});
