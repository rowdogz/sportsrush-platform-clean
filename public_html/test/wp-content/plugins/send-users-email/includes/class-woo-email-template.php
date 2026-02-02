<?php

class SUE_Woo_Email_Template 
{

    static public function init_hook()
    {
        add_filter('sue_get_email_theme_scheme_choices', [__CLASS__ , 'add_woocommerce_dropdown_selection'], 10, 1);
        add_filter('sue_send_using_wp_mail_woocommerce', '__return_false');
        add_action('sue_process_sue_send_using_email_service_woocommerce', [__CLASS__, 'process_send_email'], 10, 2);
    }
    static public function add_woocommerce_dropdown_selection($email_template_scheme)
    {
        if (sue_is_woocommerce_active()) {
            $email_template_scheme[] = __( 'woocommerce', 'send-users-email' );
        }
        return $email_template_scheme;
    }

    static public function disable_system_mail()
    {
        return false;
    }

    static public function process_send_email(
        $send_email,
        $sue_data
    ) {
        if ($send_email) {
            $recipient     = $sue_data['to'];
            $subject       = $sue_data['subject'];
            $email_content = $sue_data['body'];
            $headers       = $sue_data['headers'];
            $title         = $sue_data['email_title'];

            $mailer = WC()->mailer();

            ob_start();

            wc_get_template(
            'emails/email-header.php',
            ['email_heading' => $title]
            );

            echo wp_kses_post($email_content);

            // Output WooCommerce email footer
            wc_get_template( 'emails/email-footer.php' );

            // Get the complete email content
            $email_content = ob_get_clean();

            return $mailer->send( $recipient, $subject, $email_content, $headers );
        }
    }
}