<?php
/**
 * The code that runs during plugin activation.
 * 
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Summary of SUE_Email_Subscription
 * This class handles email subscriptions for users.
 * It creates a database table to store user email subscriptions and provides methods to manage them.
 * It is used to track which users have subscribed to receive emails from the plugin.
 * It is initialized during the plugin's activation process.
 * It is part of the Send Users Email plugin.
 * @package Send Users Email
 */
class SUE_Email_Subscription_User_Meta 
{
    /**
     * Summary of META_KEY
     * This constant defines the meta key used to store email subscription status for users.
     * It is used to save and retrieve the email subscription status in user meta.
     * It is set to 'sue_email_subscription' which is a unique identifier for the email subscription meta field.
     * It is used in the user profile to allow users to subscribe or unsubscribe from email notifications.
     * 
     * @var string
     */
    const META_KEY = 'sue_email_subscription';

    /**
     * Summary of init_hook
     * This method initializes the hooks for rendering user meta fields and saving email subscription status.
     * It adds actions to display the email subscription checkbox in user profiles.
     * It also adds actions to save the email subscription status when the user profile is updated.
     * It is called during the plugin's initialization process to set up the necessary hooks.
     * @return void
     */
    static public function init_hook()
    {
        /**
         * Summary of add_action
         * This function adds actions to WordPress to render the email subscription meta fields in user profiles.
         * It hooks into 'show_user_profile' and 'edit_user_profile' to display the email subscription checkbox.
         * It also hooks into 'personal_options_update' and 'edit_user_profile_update' to save the email subscription status.
         * It is used to allow users to manage their email subscription status directly from their profile page.
         * 
         * @param void
         * @return void
         */
        add_action('show_user_profile', [__CLASS__, 'render_meta_fields']);
        add_action('edit_user_profile', [__CLASS__, 'render_meta_fields']);

        /**
         * Summary of add_action
         * This function adds actions to WordPress to save the email subscription status when the user profile is updated.
         * It hooks into 'personal_options_update' and 'edit_user_profile_update' to save the email subscription checkbox value.
         * It is used to ensure that the user's email subscription status is saved when they update their profile.
         * It checks if the current user has permission to edit the user profile before saving the subscription status.
         * 
         * @return void
         */
        add_action('personal_options_update', [__CLASS__, 'save_email_subscribe_checkbox']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_email_subscribe_checkbox']);
    }

    /**
     * Summary of render_meta_fields
     * This method renders the meta fields for email subscription in the user profile.
     * It displays a checkbox for users to subscribe or unsubscribe from email notifications.
     * It retrieves the current email subscription status for the user and displays it in the checkbox.
     * It is used to allow users to manage their email subscription status directly from their profile page.
     * 
     * @param mixed $user
     * @return void
     */
    static public function render_meta_fields($user)
    {
        $email_subscription = self::get_email_subscription_meta($user->ID);

        $args = [
            'title'              => esc_html__('Subscribe or Un-Subscribe Email', 'send-users-email'),
            'email_subscription' => $email_subscription,
            'is_subscribed'      => self::is_email_subscribed($user->ID),
        ];

        require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/admin/partials/subscribe-email-user-meta.php';
    }

    /**
     * Summary of save_email_subscribe_checkbox
     * This method saves the email subscription checkbox value when the user profile is updated.
     * It checks if the current user has permission to edit the user profile before saving.
     * It updates the user meta with the email subscription status (1 for subscribed, 0 for unsubscribed).
     * It is used to ensure that the user's email subscription status is saved when they update their profile.
     * 
     * @param mixed $user_id
     * @return bool
     */
    static public function save_email_subscribe_checkbox($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $email_subscription = isset($_POST['sue_email_subscription']) ? 1 : 0;

        update_user_meta($user_id, self::META_KEY, $email_subscription);
    }

    /**
     * Summary of get_email_subscription
     * This method retrieves the email subscription status for a user.
     * It checks the user meta for the email subscription status using the defined meta key.
     * It returns the email subscription status (1 for subscribed, 0 for unsubscribed).
     * It is used to check if a user is subscribed or unsubscribed from email notifications.
     * 
     * @param mixed $user_id
     */
    static public function get_email_subscription_meta($user_id)
    {
        return get_user_meta($user_id, self::META_KEY, true);
    }

    /**
     * Summary of is_email_subscribed
     * This method checks if a user is subscribed to email notifications.
     * It retrieves the email subscription status using the get_email_subscription method.
     * It returns true if the user is subscribed (1) and false if unsubscribed (0).
     * It is used to determine if a user has opted in to receive emails from the plugin.
     * 
     * @param mixed $user_id
     */
    static public function is_email_subscribed($user_id)
    {
        return self::get_email_subscription($user_id);
    }

    /**
     * Summary of show_default_email_subscription_checkbox
     * This method returns the default email subscription checkbox value for a user.
     * It checks the user's email subscription status and returns 1 for subscribed, 0 for unsubscribed.
     * If the email subscription status is not set, it defaults to 1 (subscribed).
     * It is used to display the email subscription checkbox in user profiles with the correct default value.
     * 
     * @param mixed $user_id
     */
    static public function get_email_subscription($user_id)
    {
        $email_subscription = self::get_email_subscription_meta($user_id);

        if (!$email_subscription) {
            if ($email_subscription === '0' || $email_subscription === 0) {
                $email_subscription = 0; // Explicitly set to not subscribed
            } else {
                // Default to subscribed if not set
                $email_subscription = 1;
            }
        }

        return $email_subscription;
    }

    /**
     * Summary of get_all_subscribed_users
     * This method retrieves all users who are subscribed to email notifications.
     * It performs a user query to get users with the email subscription meta key set to 1.
     * It returns an array of user IDs who are subscribed to emails.
     * It is used to get a list of all users who have opted in to receive emails from the plugin.
     * 
     * @return array
     */
    static public function get_all_subscribed_users()
    {
        $args = [
            'meta_key' => self::META_KEY,
            'meta_value' => 1,
            'fields' => 'ID',
        ];
        $user_query = new WP_User_Query($args);
        return $user_query->get_results();
    }

    /**
     * Summary of get_all_unsubscribed_users
     * This method retrieves all users who are unsubscribed from email notifications.
     * It performs a user query to get users with the email subscription meta key set to 0.
     * It returns an array of user IDs who are unsubscribed from emails.
     * It is used to get a list of all users who have opted out of receiving emails from the plugin.
     * 
     * @return array
     */
    static public function get_all_unsubscribed_users()
    {
        $args = [
            'meta_key' => self::META_KEY,
            'meta_value' => 0,
            'fields' => 'ID',
        ];
        $user_query = new WP_User_Query($args);
        return $user_query->get_results();
    }

    /**
     * Summary of delete_email_subscription_meta
     * This method deletes the email subscription meta for a user.
     * It removes the email subscription status from the user meta using the defined meta key.
     * It is used to clear the email subscription status when a user unsubscribes or when their account is deleted.
     * 
     * @param mixed $user_id
     */
    static public function delete_email_subscription_meta($user_id)
    {
        if ( ! $user_id ) {
            return false; // No user ID provided
        }

        // Delete the email subscription meta for the user
        delete_user_meta($user_id, self::META_KEY);
    }
}