<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-inner">
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php bloginfo('name'); ?>
            </a>
        </div>
        
        <nav class="main-nav">
            <?php if (is_user_logged_in()): 
                $user = wp_get_current_user();
            ?>
            <ul>
                <?php if (in_array('rw_tenant', $user->roles)): ?>
                    <li><a href="<?php echo esc_url(home_url('/tenant-dashboard/')); ?>"><?php _e('Dashboard', 'rent-wallet-theme'); ?></a></li>
                <?php elseif (in_array('rw_landlord', $user->roles)): ?>
                    <li><a href="<?php echo esc_url(home_url('/landlord-dashboard/')); ?>"><?php _e('Dashboard', 'rent-wallet-theme'); ?></a></li>
                <?php elseif (in_array('rw_agency_staff', $user->roles)): ?>
                    <li><a href="<?php echo esc_url(home_url('/agency-portal/')); ?>"><?php _e('Agency Portal', 'rent-wallet-theme'); ?></a></li>
                <?php endif; ?>
                
                <?php if (current_user_can('manage_options')): ?>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=rent-wallet')); ?>"><?php _e('Admin', 'rent-wallet-theme'); ?></a></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </nav>
        
        <div class="user-nav">
            <?php if (is_user_logged_in()): ?>
                <span class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link"><?php _e('Logout', 'rent-wallet-theme'); ?></a>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php _e('Login', 'rent-wallet-theme'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="site-main">
    <div class="content-wrapper">
