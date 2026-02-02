<?php
/*
Plugin Name: Football Pool Sponsor Manager
Description: Allows admins and authors to manage sponsor images and links from the WordPress admin panel.
Version: 1.5
Author: Your Name
*/

// Add menu item in the WordPress Admin Panel
function fp_sponsors_add_admin_menu() {
    add_menu_page(
        'Sponsor Settings', // Page Title
        'Sponsors', // Menu Title
        'edit_sponsors',  // Now only users with "edit_sponsors" capability can access it
        'fp_sponsor_settings', // Menu Slug
        'fp_sponsors_settings_page', // Function to Display Page
        'dashicons-admin-generic', // Icon
        25 // Position
    );
}
add_action('admin_menu', 'fp_sponsors_add_admin_menu');

// Function to create the custom "Prediction User" role
function fp_create_prediction_user_role() {
    add_role(
        'prediction_user',
        'Prediction User',
        [
            'read' => true, // Allow access to login and basic dashboard
            'edit_sponsors' => true, // Custom capability for managing sponsors
        ]
    );
}
register_activation_hook(__FILE__, 'fp_create_prediction_user_role');

// Remove the role when the plugin is deactivated
function fp_remove_prediction_user_role() {
    remove_role('prediction_user');
}
register_deactivation_hook(__FILE__, 'fp_remove_prediction_user_role');

// Function to add "edit_sponsors" capability to Admins & Prediction Users
function fp_add_sponsor_capability() {
    // Get the role for Prediction Users
    $role = get_role('prediction_user');
    if ($role) {
        $role->add_cap('edit_sponsors'); // Allow sponsor editing
    }

    // Ensure Admins also have this capability
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('edit_sponsors');
    }
}
add_action('init', 'fp_add_sponsor_capability');

function fp_add_sponsors_menu_link($wp_admin_bar) {
    // Check if the current user has the capability to edit sponsors
    if (current_user_can('edit_sponsors')) {
        $args = array(
            'id'    => 'fp_manage_sponsors',
            'title' => 'Manage Sponsors',
            'href'  => admin_url('admin.php?page=fp_sponsor_settings'),
            'meta'  => array('class' => 'fp-admin-bar-sponsors')
        );
        $wp_admin_bar->add_node($args);
    }
}
add_action('admin_bar_menu', 'fp_add_sponsors_menu_link', 100);

// Register Settings
function fp_sponsors_register_settings() {
    register_setting('fp_sponsor_settings_group', 'fp_sponsors');

    // Allow WordPress to recognize this setting
    add_filter('whitelist_options', function ($options) {
        $options['fp_sponsor_settings_group'] = ['fp_sponsors'];
        return $options;
    });
}
add_action('admin_init', 'fp_sponsors_register_settings');

// Settings Page UI
function fp_sponsors_settings_page() {
    $sponsors = get_option('fp_sponsors', []); // Get stored sponsors
    if (!is_array($sponsors)) {
        $sponsors = [];
    }
    ?>

    <div class="wrap">
        <h1>Sponsor Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fp_sponsor_settings_group'); ?>
            <?php do_settings_sections('fp_sponsor_settings_group'); ?>
            
            <table class="form-table" id="sponsor-table">
                <thead>
                    <tr>
                        <th>Image URL</th>
                        <th>Upload</th>
                        <th>Link URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sponsor-rows">
                    <?php foreach ($sponsors as $index => $sponsor) : ?>
                        <tr>
                            <td><input type="text" name="fp_sponsors[<?php echo $index; ?>][image]" value="<?php echo esc_attr($sponsor['image']); ?>" class="regular-text sponsor-image"></td>
                            <td><button type="button" class="upload-button button">Upload</button></td>
                            <td><input type="text" name="fp_sponsors[<?php echo $index; ?>][link]" value="<?php echo esc_attr($sponsor['link']); ?>" class="regular-text"></td>
                            <td><button type="button" class="button remove-sponsor">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p><button type="button" id="add-sponsor" class="button button-primary">Add Sponsor</button></p>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let sponsorRows = document.getElementById('sponsor-rows');

            // Add New Sponsor Row
            document.getElementById('add-sponsor').addEventListener('click', function () {
                let rowCount = sponsorRows.children.length;
                let newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" name="fp_sponsors[${rowCount}][image]" class="regular-text sponsor-image"></td>
                    <td><button type="button" class="upload-button button">Upload</button></td>
                    <td><input type="text" name="fp_sponsors[${rowCount}][link]" class="regular-text"></td>
                    <td><button type="button" class="button remove-sponsor">Remove</button></td>
                `;
                sponsorRows.appendChild(newRow);
            });

            // Remove Sponsor Row
            sponsorRows.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-sponsor')) {
                    e.target.closest('tr').remove();
                }
            });

            // Media Uploader for Image Upload
            let uploadButtons = document.querySelectorAll('.upload-button');
            uploadButtons.forEach(button => {
                button.addEventListener('click', function (e) {
                    let frame;
                    let inputField = e.target.closest('tr').querySelector('.sponsor-image');

                    if (frame) {
                        frame.open();
                        return;
                    }

                    frame = wp.media({
                        title: 'Select or Upload Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });

                    frame.on('select', function () {
                        let attachment = frame.state().get('selection').first().toJSON();
                        inputField.value = attachment.url;
                    });

                    frame.open();
                });
            });
        });
    </script>

    <?php
}

// Shortcode to Display Sponsors on the Homepage
function enhanced_football_homepage_shortcode() {
    $sponsors = get_option('fp_sponsors', []);
    if (!is_array($sponsors) || empty($sponsors)) {
        return '<p>No sponsors available.</p>';
    }

    ob_start();
    ?>
    <div class="slider-container" style="position: relative; max-width: 100%; margin: 0 auto;">
        <button class="slider-nav left">&lt;</button>
        <div class="enhanced-homepage-slider">
            <?php foreach ($sponsors as $sponsor): ?>
                <div class="sponsor-slide">
                    <a href="<?php echo esc_url($sponsor['link']); ?>" target="_blank">
                        <img src="<?php echo esc_url($sponsor['image']); ?>" alt="Sponsor">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="slider-nav right">&gt;</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slider = document.querySelector('.enhanced-homepage-slider');
            const leftButton = document.querySelector('.slider-nav.left');
            const rightButton = document.querySelector('.slider-nav.right');

            leftButton.addEventListener('click', function () {
                slider.scrollBy({ left: -250, behavior: 'smooth' });
            });

            rightButton.addEventListener('click', function () {
                slider.scrollBy({ left: 250, behavior: 'smooth' });
            });
        });
    </script>

    <style>
        .enhanced-homepage-slider {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 20px;
            padding: 10px 0;
            scroll-behavior: smooth;
            max-width: 100%;
            margin: 0 auto;
            position: relative;
        }

        .enhanced-homepage-slider::-webkit-scrollbar {
            display: none;
        }

        .sponsor-slide {
            flex: 0 0 200px;
            text-align: center;
        }

        .sponsor-slide img {
            width: 180px;
            height: auto;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .sponsor-slide img:hover {
            transform: scale(1.1);
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .slider-nav:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .slider-nav.left {
            left: 10px;
        }

        .slider-nav.right {
            right: 10px;
        }
    </style>

    <?php
    return ob_get_clean();
}
add_shortcode('football_pool_enhanced_homepage', 'enhanced_football_homepage_shortcode');