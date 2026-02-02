(function() {
    'use strict';

    // Theme toggle functionality
    const THEME_KEY = 'sportsrush_theme';
    const LIGHT_THEME = 'light';
    const DARK_THEME = 'dark';

    // Get saved theme from localStorage or user meta
    function getSavedTheme() {
        // Check if user preference was set via PHP (from user meta)
        if (typeof sportsrushTheme !== 'undefined' && sportsrushTheme.userTheme) {
            return sportsrushTheme.userTheme;
        }
        // Fall back to localStorage
        return localStorage.getItem(THEME_KEY) || DARK_THEME;
    }

    // Apply theme to body and html
    function applyTheme(theme) {
        if (theme === LIGHT_THEME) {
            document.documentElement.classList.add('light-theme');
            document.body.classList.add('light-theme');
            injectLightThemeStyles();
        } else {
            document.documentElement.classList.remove('light-theme');
            document.body.classList.remove('light-theme');
            removeLightThemeStyles();
        }
    }
    
    // Apply light theme styles directly to elements
    function injectLightThemeStyles() {
        // Apply styles directly to submenu elements
        const submenus = document.querySelectorAll('.sub-menu, .site-navigation ul ul, .site-navigation .menu ul');
        submenus.forEach(function(el) {
            el.style.setProperty('background', '#ffffff', 'important');
            el.style.setProperty('background-color', '#ffffff', 'important');
            el.style.setProperty('border', '1px solid #e2e8f0', 'important');
            el.style.setProperty('box-shadow', '0 4px 12px rgba(0, 0, 0, 0.1)', 'important');
        });
        
        // Apply styles to submenu links
        const submenuLinks = document.querySelectorAll('.sub-menu a, .sub-menu li a');
        submenuLinks.forEach(function(el) {
            el.style.setProperty('color', '#334155', 'important');
            el.style.setProperty('background', 'transparent', 'important');
        });
        
        // Apply styles to footer
        const footers = document.querySelectorAll('footer, .site-footer');
        footers.forEach(function(el) {
            el.style.setProperty('background', '#e2e8f0', 'important');
            el.style.setProperty('background-color', '#e2e8f0', 'important');
        });
        
        // Apply styles to HTML and body elements to fix dark band below footer
        document.documentElement.style.setProperty('background', '#f0f4f8', 'important');
        document.documentElement.style.setProperty('background-color', '#f0f4f8', 'important');
        document.body.style.setProperty('background', '#f0f4f8', 'important');
        document.body.style.setProperty('background-color', '#f0f4f8', 'important');
        
        // Apply styles to site layout wrapper
        const siteLayout = document.getElementById('site-layout-type');
        if (siteLayout) {
            siteLayout.style.setProperty('background', '#f0f4f8', 'important');
            siteLayout.style.setProperty('background-color', '#f0f4f8', 'important');
            // Make site layout extend to cover entire document
            siteLayout.style.setProperty('min-height', document.documentElement.scrollHeight + 'px', 'important');
        }
        
        // Add a light background overlay to cover any dark areas
        let bgOverlay = document.getElementById('light-theme-bg-overlay');
        if (!bgOverlay) {
            bgOverlay = document.createElement('div');
            bgOverlay.id = 'light-theme-bg-overlay';
            bgOverlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: #f0f4f8; z-index: -1; pointer-events: none;';
            document.body.insertBefore(bgOverlay, document.body.firstChild);
        }
        bgOverlay.style.display = 'block';
    }
    
    // Remove light theme styles from elements
    function removeLightThemeStyles() {
        // Remove styles from submenu elements
        const submenus = document.querySelectorAll('.sub-menu, .site-navigation ul ul, .site-navigation .menu ul');
        submenus.forEach(function(el) {
            el.style.removeProperty('background');
            el.style.removeProperty('background-color');
            el.style.removeProperty('border');
            el.style.removeProperty('box-shadow');
        });
        
        // Remove styles from submenu links
        const submenuLinks = document.querySelectorAll('.sub-menu a, .sub-menu li a');
        submenuLinks.forEach(function(el) {
            el.style.removeProperty('color');
            el.style.removeProperty('background');
        });
        
        // Remove styles from footer
        const footers = document.querySelectorAll('footer, .site-footer');
        footers.forEach(function(el) {
            el.style.removeProperty('background');
            el.style.removeProperty('background-color');
        });
        
        // Remove styles from HTML and body elements
        document.documentElement.style.removeProperty('background');
        document.documentElement.style.removeProperty('background-color');
        document.body.style.removeProperty('background');
        document.body.style.removeProperty('background-color');
        
        // Remove styles from site layout wrapper
        const siteLayout = document.getElementById('site-layout-type');
        if (siteLayout) {
            siteLayout.style.removeProperty('background');
            siteLayout.style.removeProperty('background-color');
            siteLayout.style.removeProperty('min-height');
        }
        
        // Hide the light background overlay
        const bgOverlay = document.getElementById('light-theme-bg-overlay');
        if (bgOverlay) {
            bgOverlay.style.display = 'none';
        }
    }

    // Save theme preference
    function saveTheme(theme) {
        // Save to localStorage for immediate persistence
        localStorage.setItem(THEME_KEY, theme);

        // If user is logged in, save to user meta via AJAX
        if (typeof sportsrushTheme !== 'undefined' && sportsrushTheme.ajaxUrl && sportsrushTheme.nonce) {
            fetch(sportsrushTheme.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sportsrush_save_theme',
                    theme: theme,
                    nonce: sportsrushTheme.nonce
                })
            }).catch(function(error) {
                console.log('Theme save error:', error);
            });
        }
    }

    // Toggle theme
    function toggleTheme() {
        const currentTheme = document.body.classList.contains('light-theme') ? LIGHT_THEME : DARK_THEME;
        const newTheme = currentTheme === LIGHT_THEME ? DARK_THEME : LIGHT_THEME;
        
        applyTheme(newTheme);
        saveTheme(newTheme);
    }

    // Create toggle switch
    function createToggleButton() {
        // Create the toggle container
        const toggle = document.createElement('div');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('role', 'switch');
        toggle.setAttribute('aria-label', 'Toggle dark/light theme');
        toggle.setAttribute('title', 'Toggle dark/light theme');
        toggle.setAttribute('tabindex', '0');
        
        // Create the track (background)
        const track = document.createElement('div');
        track.className = 'theme-toggle-track';
        
        // Create sun icon (left side - light mode)
        const sunIcon = document.createElement('span');
        sunIcon.className = 'theme-toggle-icon icon-sun';
        sunIcon.innerHTML = '☀️';
        
        // Create moon icon (right side - dark mode)
        const moonIcon = document.createElement('span');
        moonIcon.className = 'theme-toggle-icon icon-moon';
        moonIcon.innerHTML = '🌙';
        
        // Create the thumb (sliding circle)
        const thumb = document.createElement('div');
        thumb.className = 'theme-toggle-thumb';
        
        track.appendChild(sunIcon);
        track.appendChild(moonIcon);
        track.appendChild(thumb);
        toggle.appendChild(track);
        
        // Click handler
        toggle.addEventListener('click', toggleTheme);
        
        // Keyboard handler for accessibility
        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleTheme();
            }
        });
        
        document.body.appendChild(toggle);
    }

    // Initialize on DOM ready
    function init() {
        // Apply saved theme immediately (before button is created)
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);
        
        // Create toggle button
        createToggleButton();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also apply theme immediately to prevent flash
    (function() {
        const savedTheme = getSavedTheme();
        if (savedTheme === LIGHT_THEME) {
            document.documentElement.classList.add('light-theme-loading');
        }
    })();
})();
