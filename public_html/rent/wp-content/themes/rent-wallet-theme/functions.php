<?php
/**
 * Rent Wallet Theme Functions
 *
 * @package Rent_Wallet_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup
 */
function rw_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'rent-wallet-theme'),
        'footer'  => __('Footer Menu', 'rent-wallet-theme'),
    ));
}
add_action('after_setup_theme', 'rw_theme_setup');

/**
 * Enqueue scripts and styles
 */
function rw_theme_scripts() {
    wp_enqueue_style('rw-theme-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Enqueue plugin frontend styles if plugin is active
    if (class_exists('Rent_Wallet_Platform')) {
        wp_enqueue_style('rw-frontend', plugins_url('rent-wallet-platform/assets/css/frontend.css'), array(), '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'rw_theme_scripts');

/**
 * Get current user dashboard URL based on role
 */
function rw_get_dashboard_url() {
    if (!is_user_logged_in()) {
        return home_url('/login/');
    }

    $user = wp_get_current_user();
    
    if (in_array('rw_tenant', $user->roles)) {
        return home_url('/tenant-dashboard/');
    } elseif (in_array('rw_landlord', $user->roles)) {
        return home_url('/landlord-dashboard/');
    } elseif (in_array('rw_agency_staff', $user->roles)) {
        return home_url('/agency-portal/');
    } elseif (in_array('administrator', $user->roles)) {
        return admin_url();
    }

    return home_url();
}

/**
 * Redirect users to appropriate dashboard after login
 */
function rw_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('rw_tenant', $user->roles)) {
            return home_url('/tenant-dashboard/');
        } elseif (in_array('rw_landlord', $user->roles)) {
            return home_url('/landlord-dashboard/');
        } elseif (in_array('rw_agency_staff', $user->roles)) {
            return home_url('/agency-portal/');
        } elseif (in_array('administrator', $user->roles)) {
            return admin_url();
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'rw_login_redirect', 10, 3);

/**
 * Create theme pages on activation
 */
function rw_theme_create_pages() {
    $pages = array(
        'tenant-dashboard' => array(
            'title'   => 'Tenant Dashboard',
            'content' => '[rw_tenant_dashboard]',
        ),
        'landlord-dashboard' => array(
            'title'   => 'Landlord Dashboard',
            'content' => '[rw_landlord_dashboard]',
        ),
        'agency-portal' => array(
            'title'   => 'Agency Portal',
            'content' => '[rw_agency_portal]',
        ),
        'login' => array(
            'title'   => 'Login',
            'content' => '[rw_login_form]',
        ),
    );

    foreach ($pages as $slug => $page_data) {
        $existing = get_page_by_path($slug);
        if (!$existing) {
            wp_insert_post(array(
                'post_title'     => $page_data['title'],
                'post_name'      => $slug,
                'post_content'   => $page_data['content'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
        }
    }
}
add_action('after_switch_theme', 'rw_theme_create_pages');

/**
 * Check if Rent Wallet plugin is active
 */
function rw_is_plugin_active() {
    return class_exists('Rent_Wallet_Platform');
}

/**
 * Display admin notice if plugin is not active
 */
function rw_plugin_notice() {
    if (!rw_is_plugin_active()) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('The Rent Wallet Theme requires the Rent Wallet Platform plugin to be installed and activated.', 'rent-wallet-theme');
        echo '</p></div>';
    }
}
add_action('admin_notices', 'rw_plugin_notice');
