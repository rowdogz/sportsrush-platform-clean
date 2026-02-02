<?php

class SUE_Email_Subscription_Override
{
    static public function override($user_id, $sue_override_user_email_subscription)
    {
        $send_email = true;

        if ( sue_is_premium_and_can_use_premium_code() ) {
            // If the sue_override_user_email_subscription is not set, check if the user is subscribed to email notifications
            // If the user is not subscribed, do not send the email
            if ( ! $sue_override_user_email_subscription ) {
                // Check if user is subscribed to email notifications
                if ( ! sue_is_user_email_subscribed( $user_id ) ) {
                    $send_email = false;
                }
            }
        }

        return $send_email; 
    }

    static public function view_override_checkbox()
    {
        if ( sue_is_premium_and_can_use_premium_code() ) {
            require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/admin/partials/templates/override-user-email-subscription.php';
        }
    }
}