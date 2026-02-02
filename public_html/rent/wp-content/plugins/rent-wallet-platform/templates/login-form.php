<?php
if (!defined('ABSPATH')) exit;
?>
<div class="rw-login-form">
    <?php if ($error): ?>
    <div class="rw-message rw-error"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo esc_url(wp_login_url()); ?>" class="rw-form">
        <div class="rw-form-row">
            <label for="user_login"><?php _e('Username or Email', 'rent-wallet-platform'); ?></label>
            <input type="text" name="log" id="user_login" required>
        </div>
        
        <div class="rw-form-row">
            <label for="user_pass"><?php _e('Password', 'rent-wallet-platform'); ?></label>
            <input type="password" name="pwd" id="user_pass" required>
        </div>
        
        <div class="rw-form-row rw-remember">
            <label>
                <input type="checkbox" name="rememberme" value="forever">
                <?php _e('Remember Me', 'rent-wallet-platform'); ?>
            </label>
        </div>
        
        <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
        
        <button type="submit" class="rw-button"><?php _e('Log In', 'rent-wallet-platform'); ?></button>
    </form>
    
    <p class="rw-login-links">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('Lost your password?', 'rent-wallet-platform'); ?></a>
    </p>
</div>
