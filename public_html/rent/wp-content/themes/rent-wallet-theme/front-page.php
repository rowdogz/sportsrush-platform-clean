<?php
/**
 * Front page template
 *
 * @package Rent_Wallet_Theme
 */

get_header();
?>

<div class="front-page">
    <div class="hero">
        <h1><?php _e('Welcome to Rent Wallet', 'rent-wallet-theme'); ?></h1>
        <p class="hero-subtitle"><?php _e('Simulated wallet management for tenants and landlords', 'rent-wallet-theme'); ?></p>
        
        <?php if (!is_user_logged_in()): ?>
            <a href="<?php echo esc_url(home_url('/login/')); ?>" class="rw-button"><?php _e('Login to Your Dashboard', 'rent-wallet-theme'); ?></a>
        <?php else: ?>
            <a href="<?php echo esc_url(rw_get_dashboard_url()); ?>" class="rw-button"><?php _e('Go to Dashboard', 'rent-wallet-theme'); ?></a>
        <?php endif; ?>
    </div>
    
    <div class="features">
        <div class="feature">
            <h3><?php _e('For Tenants', 'rent-wallet-theme'); ?></h3>
            <p><?php _e('Manage your wallet balance, track rent payments, and earn cashback rewards based on your coverage tier.', 'rent-wallet-theme'); ?></p>
        </div>
        
        <div class="feature">
            <h3><?php _e('For Landlords', 'rent-wallet-theme'); ?></h3>
            <p><?php _e('View your properties, track tenancies, and monitor simulated payouts with transparent fee breakdowns.', 'rent-wallet-theme'); ?></p>
        </div>
        
        <div class="feature">
            <h3><?php _e('For Agencies', 'rent-wallet-theme'); ?></h3>
            <p><?php _e('Manage assigned landlords and properties, view tenancy coverage, and export reports.', 'rent-wallet-theme'); ?></p>
        </div>
    </div>
</div>

<style>
.front-page {
    text-align: center;
}

.hero {
    padding: 60px 20px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    border-radius: 8px;
    margin-bottom: 40px;
}

.hero h1 {
    font-size: 42px;
    margin: 0 0 16px 0;
}

.hero-subtitle {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 30px;
}

.hero .rw-button {
    background: #fff;
    color: #2563eb;
    font-size: 18px;
    padding: 14px 28px;
}

.hero .rw-button:hover {
    background: #f3f4f6;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.feature {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.feature h3 {
    color: #2563eb;
    margin-top: 0;
}

.feature p {
    color: #4b5563;
    margin-bottom: 0;
}
</style>

<?php
get_footer();
