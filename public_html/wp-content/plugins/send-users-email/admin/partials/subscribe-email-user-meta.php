<?php
/**
 * The code that runs during plugin activation.
 * 
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$title              = $args['title'];
$email_subscription = $args['email_subscription'];
$is_subscribed      = $args['is_subscribed'];

?>
<!-- <h3><?php //echo esc_html($title); ?></h3> -->
<table class="form-table">
    <tr>
        <th>
            <label for="sue_email_subscription">
                <?php esc_html_e('Subscribe to email notifications', 'send-users-email'); ?></label>
            </th>
        <td>
            <input 
                type="checkbox" 
                name="sue_email_subscription" 
                id="sue_email_subscription" 
                value="<?php echo esc_attr($is_subscribed);?>" 
                <?php checked($is_subscribed, 1); ?> 
            />
            <span class="description">
                <?php
                
                printf(
                            /* translators: %s: Blog name */
                    esc_html__('Subscribe to emails from %s', 'send-users-email'),
                    esc_html( get_bloginfo('name') )
                );
                ?>
            </span>
        </td>
    </tr>
</table>