<?php
/**
 * SportsRush Modern Child Theme Functions
 *
 * @package SportsRush_Modern
 */

// Enqueue parent and child theme styles
function sportsrush_modern_enqueue_styles() {
    // Enqueue Google Fonts
    wp_enqueue_style(
        'sportsrush-google-fonts',
        'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    
    // Enqueue parent theme style
    wp_enqueue_style(
        'sports-lite-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Enqueue parent theme responsive CSS
    wp_enqueue_style(
        'sports-lite-responsive',
        get_template_directory_uri() . '/css/responsive.css',
        array('sports-lite-style'),
        wp_get_theme()->parent()->get('Version')
    );
    
    // Enqueue child theme style
    wp_enqueue_style(
        'sportsrush-modern-style',
        get_stylesheet_uri(),
        array('sports-lite-style', 'sports-lite-responsive'),
        time() // Cache bust
    );
    
    // Enqueue theme toggle script
    wp_enqueue_script(
        'sportsrush-theme-toggle',
        get_stylesheet_directory_uri() . '/theme-toggle.js',
        array(),
        time(), // Cache bust
        true
    );
    
    // Pass data to JavaScript
    $user_theme = '';
    if (is_user_logged_in()) {
        $user_theme = get_user_meta(get_current_user_id(), 'sportsrush_theme', true);
    }
    
    wp_localize_script('sportsrush-theme-toggle', 'sportsrushTheme', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sportsrush_theme_nonce'),
        'userTheme' => $user_theme,
        'isLoggedIn' => is_user_logged_in()
    ));
}
add_action('wp_enqueue_scripts', 'sportsrush_modern_enqueue_styles', 9999);

// AJAX handler to save theme preference (for logged-in users)
add_action('wp_ajax_sportsrush_save_theme', function() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sportsrush_theme_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $theme = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : 'dark';
    if (!in_array($theme, array('light', 'dark'))) {
        $theme = 'dark';
    }
    
    $user_id = get_current_user_id();
    if ($user_id) {
        update_user_meta($user_id, 'sportsrush_theme', $theme);
        wp_send_json_success(array('theme' => $theme));
    } else {
        wp_send_json_error('User not logged in');
    }
});

// Add custom body classes
function sportsrush_modern_body_classes($classes) {
    $classes[] = 'sportsrush-modern';
    if (is_front_page()) {
        $classes[] = 'sr-home';
    }
    return $classes;
}
add_filter('body_class', 'sportsrush_modern_body_classes');

// Add theme support
function sportsrush_modern_theme_setup() {
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('editor-styles');
    add_editor_style('style.css');
}
add_action('after_setup_theme', 'sportsrush_modern_theme_setup');

// Add viewport meta tag for mobile
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
}, 0);

// Add preconnect for Google Fonts
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
}, 1);

// Add inline script to apply theme before page renders (prevent flash)
add_action('wp_head', function() {
    $user_theme = '';
    if (is_user_logged_in()) {
        $user_theme = get_user_meta(get_current_user_id(), 'sportsrush_theme', true);
    }
    ?>
    <script>
    (function() {
        var savedTheme = <?php echo json_encode($user_theme); ?> || localStorage.getItem('sportsrush_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-theme');
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('light-theme');
            });
        }
    })();
    </script>
    <?php
}, 1);

// Inject critical inline CSS at the very end to override everything
add_action('wp_head', function() {
    ?>
    <style id="sportsrush-critical-overrides">
    /* =============================================
       BASE STYLES - Full width layout fix
       ============================================= */
    html, body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    
    #site-layout-type {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .container {
        width: 100% !important;
        max-width: 1200px !important;
        margin: 0 auto !important;
        padding: 0 20px !important;
        box-sizing: border-box !important;
    }
    
    /* =============================================
       DARK THEME (DEFAULT)
       ============================================= */
    body, body.wp-theme-sports-lite, body.theme-sports-lite {
        background: #0a0a0f !important;
        background-color: #0a0a0f !important;
        color: #e5e7eb !important;
        font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }
    
    .mainmenu-left-area, .wp-theme-sports-lite .mainmenu-left-area, div.mainmenu-left-area {
        background: transparent !important;
    }
    
    .mainmenu-left-area::before, .mainmenu-left-area::after {
        display: none !important;
        background: transparent !important;
    }
    
    .hdr_sitemenu { background: transparent !important; }
    
    .site-header, .site-header.siteinner {
        background: rgba(10, 10, 15, 0.95) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        height: auto !important;
        position: relative !important;
    }
    
    .site-navigation, .site-navigation.primary-navigation, #main-navigation {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 9999px !important;
        padding: 8px 16px !important;
        margin: 12px auto 16px !important;
        max-width: fit-content !important;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    }
    
    .site-navigation ul, .primary-menu, #primary {
        background: transparent !important;
        display: flex !important;
        gap: 4px !important;
    }
    
    .site-navigation ul li a, .primary-menu > li > a {
        color: #e5e7eb !important;
        background: transparent !important;
        padding: 10px 18px !important;
        border-radius: 9999px !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
    }
    
    .site-navigation ul li a:hover { color: #fff !important; background: rgba(99, 102, 241, 0.2) !important; }
    
    body:not(.light-theme) .site-navigation .menu ul,
    body:not(.light-theme) .sub-menu {
        background: rgba(18, 18, 26, 0.98) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 12px !important;
    }
    
    h1, h2, h3, h4, h5, h6 {
        color: #fff !important;
        font-family: 'Space Grotesk', 'Outfit', sans-serif !important;
    }
    
    button, input[type="submit"], .button, .btn, a.blogreadmore {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%) !important;
        color: #fff !important;
        border: none !important;
        border-radius: 9999px !important;
        padding: 14px 32px !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3) !important;
    }
    
    .dropdown-toggle, button.dropdown-toggle, .site-navigation .dropdown-toggle { display: none !important; }
    
    footer, .site-footer {
        background: linear-gradient(180deg, #0a0a0f 0%, #050508 100%) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
    }
    
    .entry-content, .page-content, article, .hentry {
        background: rgba(18, 18, 26, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 16px !important;
    }
    
    table { background: rgba(18, 18, 26, 0.6) !important; border-radius: 16px !important; }
    thead { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%) !important; }
    th { color: #fff !important; }
    td { color: #e5e7eb !important; border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important; }
    
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea, select {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 2px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 12px !important;
    }
    
    input:focus, textarea:focus, select:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
    }
    
    /* =============================================
       LIGHT THEME
       ============================================= */
    body.light-theme, html.light-theme body, .light-theme {
        background: #f0f4f8 !important;
        background-color: #f0f4f8 !important;
        color: #1e293b !important;
    }
    
    html.light-theme { background: #f0f4f8 !important; }
    
    body.light-theme .site-header, html.light-theme .site-header, .light-theme .site-header {
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    
    body.light-theme .site-header *, html.light-theme .site-header * { background-color: transparent !important; }
    
    body.light-theme .site-navigation, html.light-theme .site-navigation {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.08) 100%) !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    }
    
    body.light-theme .site-navigation ul li a, html.light-theme .site-navigation ul li a { color: #334155 !important; }
    body.light-theme .site-navigation ul li a:hover { color: #0f172a !important; background: rgba(99, 102, 241, 0.15) !important; }
    
    body.light-theme .sub-menu, html.light-theme .sub-menu,
    body.light-theme .site-navigation .menu ul, html.light-theme .site-navigation .menu ul,
    body.light-theme .site-navigation ul ul, html.light-theme .site-navigation ul ul {
        background: #ffffff !important;
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
    
    body.light-theme .sub-menu li a, html.light-theme .sub-menu li a {
        color: #334155 !important;
        background: transparent !important;
    }
    
    body.light-theme h1, body.light-theme h2, body.light-theme h3, body.light-theme h4, body.light-theme h5, body.light-theme h6,
    html.light-theme h1, html.light-theme h2, html.light-theme h3 { color: #0f172a !important; }
    
    body.light-theme .entry-content, body.light-theme .page-content, body.light-theme article, body.light-theme .hentry {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
    }
    
    body.light-theme table, html.light-theme table { background: #ffffff !important; border: 1px solid #e2e8f0 !important; }
    body.light-theme thead, html.light-theme thead { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important; }
    body.light-theme th, html.light-theme th { color: #ffffff !important; }
    body.light-theme td, html.light-theme td { color: #1e293b !important; border-bottom: 1px solid #e2e8f0 !important; }
    body.light-theme tbody tr { background: #ffffff !important; }
    body.light-theme tbody tr:nth-child(even) { background: #f8fafc !important; }
    body.light-theme tbody tr:hover { background: #f1f5f9 !important; }
    
    body.light-theme input[type="text"], body.light-theme input[type="email"], body.light-theme input[type="password"],
    body.light-theme input[type="number"], body.light-theme textarea, body.light-theme select {
        background: #ffffff !important;
        border: 2px solid #cbd5e1 !important;
        color: #1e293b !important;
    }
    
    body.light-theme input::placeholder { color: #94a3b8 !important; }
    body.light-theme label, html.light-theme label { color: #334155 !important; }
    
    body.light-theme button, body.light-theme input[type="submit"], body.light-theme .button {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
        color: #ffffff !important;
    }
    
    body.light-theme footer, body.light-theme .site-footer, html.light-theme footer, html.light-theme .site-footer {
        background: #e2e8f0 !important;
        background-color: #e2e8f0 !important;
        border-top: 1px solid #cbd5e1 !important;
        color: #475569 !important;
    }
    
    body.light-theme footer *, body.light-theme .site-footer * { color: #475569 !important; background-color: transparent !important; }
    body.light-theme footer a, body.light-theme .site-footer a { color: #6366f1 !important; }
    
    body.light-theme a, html.light-theme a { color: #6366f1 !important; }
    body.light-theme p, body.light-theme span, body.light-theme div { color: #334155 !important; }
    
    body.light-theme form, html.light-theme form, body.light-theme .user-points-summary {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
    }
    
    body.light-theme .competition-stats, body.light-theme .stats-box, body.light-theme .fp-stats,
    body.light-theme aside, body.light-theme .sidebar, body.light-theme .widget {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
    }
    
    body.light-theme div[class*="stats"], body.light-theme div[class*="summary"], body.light-theme div[class*="filter"] {
        background: #ffffff !important;
    }
    
    body.light-theme .logo, html.light-theme .logo { background: #f8fafc !important; }
    body.light-theme .logo::before, body.light-theme .logo::after, html.light-theme .logo::before, html.light-theme .logo::after {
        background: #f8fafc !important;
    }
    
    body.light-theme .mainmenu-left-area, html.light-theme .mainmenu-left-area { background: transparent !important; }
    body.light-theme .mainmenu-left-area::before, body.light-theme .mainmenu-left-area::after { display: none !important; }
    
    body.light-theme::before, body.light-theme::after { display: none !important; background: transparent !important; }
    
    body.light-theme .hdr_contactdetails, body.light-theme .hdr_leftstyle { background: transparent !important; }
    body.light-theme .hdr_contactdetails::before, body.light-theme .hdr_contactdetails::after,
    body.light-theme .hdr_leftstyle::before, body.light-theme .hdr_leftstyle::after { display: none !important; }
    
    body.light-theme .site-header::before, body.light-theme .site-header::after { display: none !important; }
    body.light-theme .container::before, body.light-theme .container::after { display: none !important; }
    
    /* Light theme logo - make text visible */
    body.light-theme .custom-logo, body.light-theme .custom-logo-link img,
    html.light-theme .custom-logo, html.light-theme .custom-logo-link img {
        filter: brightness(0.6) contrast(1.2) !important;
    }
    
    /* Button text visibility */
    body.light-theme .wp-block-button__link, body.light-theme .wp-element-button,
    html.light-theme .wp-block-button__link, html.light-theme .wp-element-button { color: #ffffff !important; }
    
    /* Footer submenu hiding */
    .site-footer .sub-menu, .site-footer ul ul, footer .sub-menu, footer ul ul { display: none !important; }
    .site-footer .dropdown-toggle, footer .dropdown-toggle { display: none !important; }
    
    /* Predictions page fixes */
    .fp-predictions-form table tbody tr:nth-child(1),
    .fp-predictions-form table tbody tr:nth-child(2),
    .fp-predictions-form table tbody tr:nth-child(3) { background: transparent !important; }
    
    body.light-theme .fp-predictions-form table tbody tr { background: #ffffff !important; }
    body.light-theme .fp-predictions-form table tbody tr:nth-child(even) { background: #f8fafc !important; }
    
    .fp-predictions-form table tbody td { border-bottom: none !important; }
    .fp-predictions-form table tbody tr { border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important; }
    body.light-theme .fp-predictions-form table tbody tr { border-bottom: 1px solid #e2e8f0 !important; }
    
    .fp-predictions-form td img { display: block !important; margin: 0 auto 5px !important; }
    
    /* Hide mobile predictions container on large desktop - show only the table */
    @media screen and (min-width: 1201px) {
        .mobile-predictions-container, #sr-mobile-container, .match-card {
            display: none !important;
        }
    }
    
    /* =============================================
       GLOBAL MATCH-CARD STYLING (applies at all sizes where cards are visible)
       ============================================= */
    
    /* Base match-card styling - applies whenever cards are visible */
    .match-card {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        background: rgba(18, 18, 26, 0.9) !important;
        border-radius: 12px !important;
        margin: 0 auto 12px auto !important;
        padding: 12px 10px !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        width: 100% !important;
        max-width: 500px !important;
        box-sizing: border-box !important;
    }
    
    body.light-theme .match-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    }
    
    /* Match card header */
    .match-card-header, .match-card > div:first-child {
        width: 100% !important;
        text-align: center !important;
        font-size: 11px !important;
        color: rgba(255, 255, 255, 0.7) !important;
        margin-bottom: 10px !important;
        padding-bottom: 8px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    
    body.light-theme .match-card-header,
    body.light-theme .match-card > div:first-child {
        color: #64748b !important;
        border-bottom-color: #e2e8f0 !important;
    }
    
    /* Teams container - horizontal layout */
    .match-card .matchinfo, .match-card > div:nth-child(2) {
        display: flex !important;
        flex-direction: row !important;
        align-items: stretch !important;
        justify-content: space-between !important;
        width: 100% !important;
        gap: 8px !important;
    }
    
    /* Team sections - home and away */
    .match-card .home, .match-card .away,
    .match-card > div:nth-child(2) > div:first-child,
    .match-card > div:nth-child(2) > div:last-child {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 !important;
        max-width: 40% !important;
        text-align: center !important;
        min-height: 80px !important;
    }
    
    /* Team logos - base size */
    .match-card img, .match-card .flag img {
        width: 44px !important;
        height: 44px !important;
        max-width: 44px !important;
        max-height: 44px !important;
        object-fit: contain !important;
        margin-bottom: 6px !important;
    }
    
    /* Team names */
    .match-card .home span, .match-card .away span,
    .match-card > div:nth-child(2) > div:first-child,
    .match-card > div:nth-child(2) > div:last-child {
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1.3 !important;
        color: #fff !important;
        word-wrap: break-word !important;
    }
    
    body.light-theme .match-card .home span,
    body.light-theme .match-card .away span {
        color: #1e293b !important;
    }
    
    /* Score/prediction input area */
    .match-card .score, .match-card > div:nth-child(2) > div:nth-child(2) {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        align-self: center !important;
        gap: 6px !important;
        flex: 0 0 auto !important;
        min-width: 80px !important;
    }
    
    /* Prediction inputs - base size */
    .match-card input[type="number"],
    .match-card input.prediction {
        width: 40px !important;
        height: 40px !important;
        text-align: center !important;
        font-size: 18px !important;
        font-weight: 600 !important;
        border: 2px solid rgba(99, 102, 241, 0.5) !important;
        border-radius: 8px !important;
        background: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        padding: 0 !important;
        -webkit-appearance: none !important;
        -moz-appearance: textfield !important;
    }
    
    body.light-theme .match-card input[type="number"],
    body.light-theme .match-card input.prediction {
        background: #f8fafc !important;
        border-color: #6366f1 !important;
        color: #1e293b !important;
    }
    
    .match-card input[type="number"]:focus,
    .match-card input.prediction:focus {
        outline: none !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
    }
    
    /* Hide number input spinners */
    .match-card input[type="number"]::-webkit-inner-spin-button,
    .match-card input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    
    /* Hyphen between scores */
    .match-card .score span, .match-card .match-hyphen {
        color: rgba(255, 255, 255, 0.5) !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }
    
    body.light-theme .match-card .score span,
    body.light-theme .match-card .match-hyphen {
        color: #94a3b8 !important;
    }
    
    /* Predictions form/container centering - applies globally */
    .fp-predictions-form,
    .predictions-container,
    .matchinfo.new-layout {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
    }
    
    .mobile-predictions-container, #sr-mobile-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        padding: 0 10px !important;
    }
    
    /* Hide desktop table and show cards on screens up to 1200px */
    @media screen and (max-width: 1200px) {
        .fp-predictions-form table,
        .predictions-container table {
            display: none !important;
        }
        
        .mobile-predictions-container, #sr-mobile-container {
            display: block !important;
        }
        
        .match-card {
            display: flex !important;
        }
    }
    
    /* =============================================
       MOBILE RESPONSIVE STYLES
       ============================================= */
    
    /* Mobile Portrait View - max 767px */
    @media screen and (max-width: 767px) {
        * { box-sizing: border-box !important; }
        
        html, body {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        
        #site-layout-type {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        .container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 10px !important;
        }
        
        /* Make predictions form fill full width on mobile */
        .entry-content,
        div.entry-content,
        article .entry-content,
        .page .entry-content,
        .single .entry-content {
            padding: 5px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .entry-content form,
        div.entry-content form,
        .fp-predictions-form form {
            padding: 5px !important;
            width: 100% !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }
        
        .fp-predictions-form,
        .predictions-container,
        div.fp-predictions-form,
        div.predictions-container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }
        
        .mobile-predictions-container, 
        #sr-mobile-container,
        div.mobile-predictions-container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }
        
        /* Make match cards fill full width on mobile */
        .match-card,
        div.match-card {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 10px 0 !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
        }
        
        /* Header mobile styles */
        .site-header {
            position: relative !important;
            height: auto !important;
            padding: 10px 0 !important;
        }
        
        .logo {
            position: relative !important;
            padding: 10px 0 !important;
            float: none !important;
            width: auto !important;
            text-align: center !important;
            background: transparent !important;
        }
        
        .logo::before, .logo::after {
            display: none !important;
        }
        
        .custom-logo, .custom-logo-link img {
            max-width: 150px !important;
            height: auto !important;
        }
        
        /* Hide desktop navigation on mobile */
        .site-navigation, #main-navigation, .primary-navigation {
            display: none !important;
        }
        
        /* Show mobile menu toggle */
        .menu-toggle {
            display: block !important;
            position: absolute !important;
            right: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 10px 15px !important;
            font-size: 14px !important;
            cursor: pointer !important;
            z-index: 1000 !important;
        }
        
        /* Mobile navigation when toggled */
        .toggled .site-navigation,
        .toggled #main-navigation,
        .toggled .primary-navigation {
            display: block !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            background: rgba(18, 18, 26, 0.98) !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            z-index: 9999 !important;
        }
        
        body.light-theme .toggled .site-navigation,
        body.light-theme .toggled #main-navigation {
            background: #ffffff !important;
        }
        
        .toggled .site-navigation ul,
        .toggled .primary-menu {
            display: block !important;
            flex-direction: column !important;
            width: 100% !important;
        }
        
        .toggled .site-navigation ul li {
            width: 100% !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        body.light-theme .toggled .site-navigation ul li {
            border-bottom: 1px solid #e2e8f0 !important;
        }
        
        .toggled .site-navigation ul li a {
            display: block !important;
            padding: 15px 20px !important;
            border-radius: 0 !important;
            text-align: left !important;
        }
        
        /* Content area mobile */
        .content-area, .innerpage_content_layout {
            width: 100% !important;
            float: none !important;
            padding: 20px 15px !important;
            margin: 0 !important;
        }
        
        #sidebar {
            width: 100% !important;
            float: none !important;
            padding: 20px 15px !important;
        }
        
        /* Entry content mobile */
        .entry-content, .page-content, article, .hentry {
            padding: 15px !important;
            margin: 0 0 20px !important;
            border-radius: 12px !important;
        }
        
        /* Tables mobile - make scrollable */
        table {
            display: block !important;
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        table thead, table tbody, table tr {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        th, td {
            padding: 8px 5px !important;
            font-size: 12px !important;
        }
        
        /* Forms mobile */
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea, select {
            width: 100% !important;
            max-width: 100% !important;
            font-size: 16px !important; /* Prevents zoom on iOS */
        }
        
        button, input[type="submit"], .button, .btn {
            width: 100% !important;
            padding: 12px 20px !important;
            font-size: 14px !important;
        }
        
        /* Footer mobile */
        .site-footer, footer {
            padding: 20px 15px !important;
        }
        
        .site-footer .container {
            padding: 0 !important;
        }
        
        .footer-widget-1, .footer-widget-2, .footer-widget-3, .widget-column-4 {
            width: 100% !important;
            float: none !important;
            margin: 0 0 15px !important;
            padding: 0 !important;
            text-align: center !important;
        }
        
        .copyrigh-wrapper {
            text-align: center !important;
            padding: 15px !important;
        }
        
        .copyright-txt, .design-by, .powerby {
            float: none !important;
            display: block !important;
            text-align: center !important;
            width: 100% !important;
        }
        
        /* Slider mobile */
        #slider {
            top: 0 !important;
            margin: 0 !important;
        }
        
        .nivo-caption {
            width: 90% !important;
            bottom: 10% !important;
        }
        
        .nivo-caption h2 {
            font-size: 18px !important;
            line-height: 22px !important;
            margin-bottom: 10px !important;
        }
        
        .nivo-caption p {
            font-size: 12px !important;
            line-height: 16px !important;
            display: none !important;
        }
        
        .nivo-directionNav a {
            top: 35% !important;
        }
        
        .nivo-controlNav {
            display: none !important;
        }
        
        /* Headings mobile */
        h1 { font-size: 24px !important; line-height: 30px !important; }
        h2 { font-size: 20px !important; line-height: 26px !important; }
        h3 { font-size: 18px !important; line-height: 24px !important; }
        
        /* Blog posts mobile */
        .blogpost_styling {
            width: 100% !important;
            float: none !important;
            margin: 0 0 20px !important;
        }
        
        .blogpost_styling .blogpost_featuredimg {
            float: none !important;
            width: 100% !important;
            margin: 0 0 15px !important;
        }
        
        /* Front page boxes mobile */
        #pageboxes_section {
            padding: 30px 0 !important;
            margin: 0 !important;
        }
        
        #pageboxes_section .container {
            top: 0 !important;
            margin: 0 auto !important;
        }
        
        .front_3column {
            float: none !important;
            width: 100% !important;
            padding: 20px !important;
            margin-bottom: 15px !important;
        }
        
        .boxstyling {
            padding: 0 !important;
            border: none !important;
        }
        
        /* Mainmenu area mobile */
        .mainmenu-left-area {
            background: transparent !important;
            position: initial !important;
        }
        
        #mainnavigator {
            width: 100% !important;
            float: none !important;
            text-align: left !important;
            padding: 10px 0 !important;
            background-color: transparent !important;
        }
        
        /* Social bar mobile */
        .hdr_socialbar {
            position: relative !important;
            text-align: center !important;
            padding: 10px 0 !important;
            float: none !important;
        }
        
        .hdr_socialbar a {
            display: inline-block !important;
            padding: 0 5px !important;
            line-height: 40px !important;
            width: 30px !important;
        }
        
        /* Left/right styles mobile */
        .left, .right, .hdr_leftstyle, .hdr_rightstyle {
            float: none !important;
            text-align: center !important;
            width: 100% !important;
            display: block !important;
        }
        
        .hdr_leftstyle, .hdr_rightstyle {
            padding: 10px 0 !important;
        }
        
        /* Info box mobile */
        .hdr_infbx {
            max-width: 200px !important;
            margin: 10px auto !important;
        }
        
        /* Theme toggle mobile positioning */
        .theme-toggle-wrapper {
            position: fixed !important;
            top: 10px !important;
            right: 60px !important;
            z-index: 10001 !important;
        }
        
        /* Show mobile predictions container on mobile - centered */
        .mobile-predictions-container, #sr-mobile-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 auto !important;
        }
        
        /* Predictions form centering */
        .fp-predictions-form,
        .predictions-container,
        .matchinfo.new-layout {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        
        /* Match card - stacked card layout - edge to edge */
        .match-card {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            background: rgba(18, 18, 26, 0.9) !important;
            border-radius: 12px !important;
            margin: 0 auto 10px auto !important;
            padding: 10px 8px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        
        body.light-theme .match-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* Match card header - date and competition */
        .match-card-header, .match-card > div:first-child {
            width: 100% !important;
            text-align: center !important;
            font-size: 10px !important;
            color: rgba(255, 255, 255, 0.7) !important;
            margin-bottom: 8px !important;
            padding-bottom: 6px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        body.light-theme .match-card-header,
        body.light-theme .match-card > div:first-child {
            color: #64748b !important;
            border-bottom-color: #e2e8f0 !important;
        }
        
        /* Teams container - horizontal layout with proper vertical centering */
        .match-card .matchinfo, .match-card > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: stretch !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 4px !important;
        }
        
        /* Team sections - home and away - vertically centered */
        .match-card .home, .match-card .away,
        .match-card > div:nth-child(2) > div:first-child,
        .match-card > div:nth-child(2) > div:last-child {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 !important;
            max-width: 40% !important;
            text-align: center !important;
            min-height: 70px !important;
        }
        
        /* Team logos - smaller size */
        .match-card img, .match-card .flag img {
            width: 36px !important;
            height: 36px !important;
            max-width: 36px !important;
            max-height: 36px !important;
            object-fit: contain !important;
            margin-bottom: 4px !important;
        }
        
        /* Team names */
        .match-card .home span, .match-card .away span,
        .match-card > div:nth-child(2) > div:first-child,
        .match-card > div:nth-child(2) > div:last-child {
            font-size: 10px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            color: #fff !important;
            word-wrap: break-word !important;
        }
        
        body.light-theme .match-card .home span,
        body.light-theme .match-card .away span {
            color: #1e293b !important;
        }
        
        /* Score/prediction input area - center and vertically aligned */
        .match-card .score, .match-card > div:nth-child(2) > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: center !important;
            justify-content: center !important;
            gap: 4px !important;
            flex: 0 0 auto !important;
            min-width: 70px !important;
        }
        
        /* Prediction inputs */
        .match-card input[type="number"],
        .match-card input.prediction {
            width: 36px !important;
            height: 36px !important;
            text-align: center !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            border: 2px solid rgba(99, 102, 241, 0.5) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            padding: 0 !important;
            -webkit-appearance: none !important;
            -moz-appearance: textfield !important;
        }
        
        body.light-theme .match-card input[type="number"],
        body.light-theme .match-card input.prediction {
            background: #f8fafc !important;
            border-color: #6366f1 !important;
            color: #1e293b !important;
        }
        
        .match-card input[type="number"]:focus,
        .match-card input.prediction:focus {
            outline: none !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
        }
        
        /* Hide number input spinners */
        .match-card input[type="number"]::-webkit-inner-spin-button,
        .match-card input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
        }
        
        /* Hyphen between scores */
        .match-card .score span, .match-card .match-hyphen {
            color: rgba(255, 255, 255, 0.5) !important;
            font-weight: 600 !important;
            font-size: 14px !important;
        }
        
        body.light-theme .match-card .score span,
        body.light-theme .match-card .match-hyphen {
            color: #94a3b8 !important;
        }
        
        /* Hide desktop table on mobile for predictions */
        .fp-predictions-form table,
        .predictions-container table {
            display: none !important;
        }
        
        /* Predictions form mobile - full width */
        .fp-predictions-form,
        .predictions-container {
            padding: 5px !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Content area full width on mobile */
        .content-area, .innerpage_content_layout {
            padding: 10px 5px !important;
            width: 100% !important;
        }
        
        /* Results page mobile */
        .user-points-summary {
            padding: 15px !important;
            margin-bottom: 20px !important;
        }
        
        /* Rankings page mobile */
        .fp-ranking-table {
            font-size: 11px !important;
        }
        
        /* Rankings page layout fix - ensure correct order on mobile */
        .entry-content > div {
            display: flex !important;
            flex-direction: column !important;
        }
        
        /* Main rankings section should come first */
        .entry-content > div > div:first-child {
            order: 1 !important;
        }
        
        /* Competition stats section should come second */
        .entry-content > div > div:last-child {
            order: 2 !important;
        }
        
        /* Rankings table responsive styling */
        .entry-content table {
            width: 100% !important;
            font-size: 11px !important;
            table-layout: fixed !important;
        }
        
        .entry-content table th,
        .entry-content table td {
            padding: 6px 4px !important;
            font-size: 10px !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }
        
        /* Rank column - narrow */
        .entry-content table th:first-child,
        .entry-content table td:first-child {
            width: 35px !important;
            text-align: center !important;
        }
        
        /* User name column - wider and left-aligned */
        .entry-content table th:nth-child(2),
        .entry-content table td:nth-child(2) {
            width: 40% !important;
            text-overflow: ellipsis !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            text-align: left !important;
        }
        
        /* Points columns - equal width and centered */
        .entry-content table th:nth-child(3),
        .entry-content table td:nth-child(3),
        .entry-content table th:nth-child(4),
        .entry-content table td:nth-child(4) {
            width: 22% !important;
            text-align: center !important;
        }
        
        /* Make table scrollable if needed */
        .entry-content > div > div {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        /* Competition Stats section styling */
        .entry-content > div > div:last-child {
            margin-top: 20px !important;
        }
        
        .entry-content > div > div:last-child h4 {
            font-size: 14px !important;
            margin-bottom: 10px !important;
        }
    }
    
    /* Mobile Landscape View - 481px to 767px */
    @media screen and (min-width: 481px) and (max-width: 767px) {
        .container, .content-area {
            width: 100% !important;
            max-width: 480px !important;
            margin: 0 auto !important;
        }
        
        .blogpost_featuredimg {
            float: left !important;
            width: 50% !important;
            margin: 5px 20px 10px 0 !important;
        }
    }
    
    /* Small Mobile - 300px to 481px */
    @media screen and (min-width: 300px) and (max-width: 481px) {
        .nivo-directionNav {
            display: none !important;
        }
        
        .hdr_socialbar a {
            width: 25px !important;
        }
    }
    
    /* Tablet View - 768px to 980px */
    @media screen and (min-width: 768px) and (max-width: 980px) {
        .site-header {
            height: auto !important;
        }
        
        .mainmenu-left-area {
            background: transparent !important;
            position: initial !important;
            float: none !important;
        }
        
        .mainmenu-left-area::before, .mainmenu-left-area::after {
            display: none !important;
        }
        
        .logo {
            background-color: transparent !important;
        }
        
        .logo::before, .logo::after {
            display: none !important;
        }
        
        #mainnavigator {
            width: 100% !important;
            text-align: center !important;
            float: none !important;
        }
        
        .hdr_socialbar {
            float: none !important;
            margin-top: 0 !important;
            padding: 10px 0 !important;
        }
        
        .menu-toggle {
            right: 30px !important;
        }
        
        #pageboxes_section .container {
            top: -60px !important;
            margin: 0 auto !important;
        }
        
        .front_3column .front_imgbx {
            height: 150px !important;
        }
        
        .front_3column .front_imgbx img {
            height: 150px !important;
        }
        
        /* Show mobile predictions on tablet too - with full styling */
        .mobile-predictions-container, #sr-mobile-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 10px !important;
            margin: 0 auto !important;
        }
        
        /* Predictions form centering on tablet */
        .fp-predictions-form,
        .predictions-container,
        .matchinfo.new-layout {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        
        /* Match card styling for tablet - 2 cards per row */
        .match-card {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            background: rgba(18, 18, 26, 0.9) !important;
            border-radius: 12px !important;
            margin: 0 auto 12px auto !important;
            padding: 12px 10px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            width: 100% !important;
            max-width: 400px !important;
            box-sizing: border-box !important;
        }
        
        body.light-theme .match-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* Teams container for tablet */
        .match-card .matchinfo, .match-card > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: stretch !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 8px !important;
        }
        
        /* Team sections for tablet */
        .match-card .home, .match-card .away,
        .match-card > div:nth-child(2) > div:first-child,
        .match-card > div:nth-child(2) > div:last-child {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 !important;
            max-width: 40% !important;
            text-align: center !important;
            min-height: 80px !important;
        }
        
        /* Team logos for tablet - slightly larger */
        .match-card img, .match-card .flag img {
            width: 44px !important;
            height: 44px !important;
            max-width: 44px !important;
            max-height: 44px !important;
            object-fit: contain !important;
            margin-bottom: 6px !important;
        }
        
        /* Team names for tablet */
        .match-card .home span, .match-card .away span {
            font-size: 12px !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            color: #fff !important;
        }
        
        body.light-theme .match-card .home span,
        body.light-theme .match-card .away span {
            color: #1e293b !important;
        }
        
        /* Score/prediction input area for tablet */
        .match-card .score, .match-card > div:nth-child(2) > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: center !important;
            gap: 6px !important;
            flex: 0 0 auto !important;
            min-width: 80px !important;
        }
        
        /* Prediction inputs for tablet */
        .match-card input[type="number"],
        .match-card input.prediction {
            width: 40px !important;
            height: 40px !important;
            text-align: center !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            border: 2px solid rgba(99, 102, 241, 0.5) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            padding: 0 !important;
        }
        
        body.light-theme .match-card input[type="number"],
        body.light-theme .match-card input.prediction {
            background: #f8fafc !important;
            border-color: #6366f1 !important;
            color: #1e293b !important;
        }
        
        .fp-predictions-form table,
        .predictions-container table {
            display: none !important;
        }
    }
    
    /* Tablet View - 768px to 1169px */
    @media screen and (max-width: 1169px) and (min-width: 768px) {
        .container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 20px !important;
        }
        
        .nivo-caption {
            width: 60% !important;
        }
        
        .nivo-caption h2 {
            font-size: 32px !important;
            line-height: 36px !important;
        }
        
        .content-area {
            width: 100% !important;
            margin: 0 !important;
            padding: 30px 20px !important;
        }
        
        #sidebar {
            width: 30% !important;
        }
        
        .innerpage_content_layout {
            width: 65% !important;
        }
        
        #site-layout-type {
            width: 100% !important;
        }
    }
    
    /* 980px to 1200px - small desktop / large tablet - show card layout */
    @media screen and (min-width: 981px) and (max-width: 1200px) {
        /* Show mobile predictions on small desktop too */
        .mobile-predictions-container, #sr-mobile-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 20px !important;
            margin: 0 auto !important;
        }
        
        /* Predictions form centering */
        .fp-predictions-form,
        .predictions-container,
        .matchinfo.new-layout {
            display: block !important;
            width: 100% !important;
            max-width: 800px !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        
        /* Match card styling for small desktop */
        .match-card {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            background: rgba(18, 18, 26, 0.9) !important;
            border-radius: 12px !important;
            margin: 0 auto 15px auto !important;
            padding: 15px 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            width: 100% !important;
            max-width: 500px !important;
            box-sizing: border-box !important;
        }
        
        body.light-theme .match-card {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        }
        
        /* Teams container */
        .match-card .matchinfo, .match-card > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: stretch !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 10px !important;
        }
        
        /* Team sections */
        .match-card .home, .match-card .away,
        .match-card > div:nth-child(2) > div:first-child,
        .match-card > div:nth-child(2) > div:last-child {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 !important;
            max-width: 40% !important;
            text-align: center !important;
            min-height: 90px !important;
        }
        
        /* Team logos */
        .match-card img, .match-card .flag img {
            width: 50px !important;
            height: 50px !important;
            max-width: 50px !important;
            max-height: 50px !important;
            object-fit: contain !important;
            margin-bottom: 8px !important;
        }
        
        /* Team names */
        .match-card .home span, .match-card .away span {
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            color: #fff !important;
        }
        
        body.light-theme .match-card .home span,
        body.light-theme .match-card .away span {
            color: #1e293b !important;
        }
        
        /* Score/prediction input area */
        .match-card .score, .match-card > div:nth-child(2) > div:nth-child(2) {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: center !important;
            gap: 8px !important;
            flex: 0 0 auto !important;
            min-width: 90px !important;
        }
        
        /* Prediction inputs */
        .match-card input[type="number"],
        .match-card input.prediction {
            width: 44px !important;
            height: 44px !important;
            text-align: center !important;
            font-size: 20px !important;
            font-weight: 600 !important;
            border: 2px solid rgba(99, 102, 241, 0.5) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            padding: 0 !important;
        }
        
        body.light-theme .match-card input[type="number"],
        body.light-theme .match-card input.prediction {
            background: #f8fafc !important;
            border-color: #6366f1 !important;
            color: #1e293b !important;
        }
        
        .fp-predictions-form table,
        .predictions-container table {
            display: none !important;
        }
    }
    
    /* Max 980px - general mobile/tablet fixes */
    @media screen and (max-width: 980px) {
        .site-header {
            position: relative !important;
        }
        
        .site-navigation a {
            color: #fff !important;
        }
        
        body.light-theme .site-navigation a {
            color: #334155 !important;
        }
        
        .site-navigation .menu ul a,
        .site-navigation .menu ul ul a {
            padding: 0.75em 1.75em !important;
        }
        
        #mainnavigator {
            background-color: transparent !important;
        }
        
        /* Show dropdown toggle on mobile */
        .dropdown-toggle {
            display: block !important;
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: transparent !important;
            border: none !important;
            color: #fff !important;
            padding: 5px 10px !important;
            box-shadow: none !important;
        }
        
        body.light-theme .dropdown-toggle {
            color: #334155 !important;
        }
        
        .menu-item-has-children {
            position: relative !important;
        }
    }
    </style>
    <?php
}, 99999);

// Customize login page
add_action('login_enqueue_scripts', function() {
    ?>
    <style type="text/css">
        body.login { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important; }
        .login h1 a { background-size: contain !important; width: 100% !important; }
        .login form { border-radius: 20px !important; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important; }
        .login #backtoblog a, .login #nav a { color: rgba(255, 255, 255, 0.8) !important; }
        .login #backtoblog a:hover, .login #nav a:hover { color: #ffd700 !important; }
        .wp-core-ui .button-primary {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%) !important;
            border: none !important; border-radius: 50px !important;
            padding: 8px 30px !important; font-weight: 600 !important;
            text-transform: uppercase !important;
        }
    </style>
    <?php
});
