<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap rw-admin">
    <h1><?php _e('Demo Data Generator', 'rent-wallet-platform'); ?></h1>
    
    <?php settings_errors('rw_messages'); ?>
    
    <div class="rw-admin-section">
        <h2><?php _e('Generate Demo Data', 'rent-wallet-platform'); ?></h2>
        <p><?php _e('This will create sample data for testing purposes:', 'rent-wallet-platform'); ?></p>
        <ul>
            <li><?php _e('1 Agency (Demo Property Management Ltd)', 'rent-wallet-platform'); ?></li>
            <li><?php _e('2 Landlords', 'rent-wallet-platform'); ?></li>
            <li><?php _e('3 Tenants', 'rent-wallet-platform'); ?></li>
            <li><?php _e('3 Properties', 'rent-wallet-platform'); ?></li>
            <li><?php _e('3 Tenancies with varying wallet balances:', 'rent-wallet-platform'); ?>
                <ul>
                    <li><?php _e('Tenant 1: 3 months coverage (Gold tier - cashback scenario)', 'rent-wallet-platform'); ?></li>
                    <li><?php _e('Tenant 2: 0.5 months coverage (arrears scenario)', 'rent-wallet-platform'); ?></li>
                    <li><?php _e('Tenant 3: 1.5 months coverage (Silver tier)', 'rent-wallet-platform'); ?></li>
                </ul>
            </li>
            <li><?php _e('1 Agency Staff user', 'rent-wallet-platform'); ?></li>
        </ul>
        
        <form method="post" action="" class="rw-form">
            <?php wp_nonce_field('rw_admin_action', 'rw_nonce'); ?>
            <input type="hidden" name="rw_admin_action" value="generate_demo_data">
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Generate Demo Data', 'rent-wallet-platform'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="rw-admin-section">
        <h2><?php _e('Testing Instructions', 'rent-wallet-platform'); ?></h2>
        <ol>
            <li><?php _e('Generate demo data using the button above', 'rent-wallet-platform'); ?></li>
            <li><?php _e('Go to Tenancies page and click "Process Rent" on any tenancy to test rent release', 'rent-wallet-platform'); ?></li>
            <li><?php _e('Or go to Dashboard and click "Run Rent Release Now" to process all tenancies due today', 'rent-wallet-platform'); ?></li>
            <li><?php _e('Check the Ledger to see transaction entries with hash chains', 'rent-wallet-platform'); ?></li>
            <li><?php _e('Check Payouts to see simulated landlord payouts', 'rent-wallet-platform'); ?></li>
            <li><?php _e('Use Verify Integrity to confirm hash chains are valid', 'rent-wallet-platform'); ?></li>
        </ol>
        
        <h3><?php _e('Demo User Credentials', 'rent-wallet-platform'); ?></h3>
        <p><?php _e('Demo users are created with random passwords. To log in as a demo user, reset their password from Users > All Users.', 'rent-wallet-platform'); ?></p>
        <p><?php _e('Demo user emails use @demo.rentwallet.local domain (not real emails).', 'rent-wallet-platform'); ?></p>
    </div>
</div>
