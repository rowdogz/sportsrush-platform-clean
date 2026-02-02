<?php
global $preview;
if ( $preview ) :
    $options = get_option('sue_send_users_email');
    if ( ! is_array( $options ) ) {
        $options = [];
    }
    $logo = $options['logo_url'] ?? SEND_USERS_EMAIL_PLUGIN_BASE_URL . '/assets/sample-logo.png';
    $title = $options['email_title'] ?? __('This is a preview title', 'send-users-email');
    $tagline = $options['email_tagline'] ?? __('This is a preview tagline', 'send-users-email');
    $footer = $options['email_footer'] ?? __('Demo Footer Content', 'send-users-email');
    $social = $options['social'] ?? ["facebook" => "#", "instagram" => "#", "linkedin" => "#", "skype" => "#", "tiktok" => "#", "twitter" => "#", "youtube" => "#"];
    $email_body = __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'send-users-email');
    $styles = $options['email_template_style'] ?? '';

    $mailer = WC()->mailer();
    $email  = new WC_Email();

    /**
     * Render the woocommerce email template
     */
    $html = $mailer->wrap_message( $title, $email_body );

    /**
     * This apply the inline style
     */
    $html = $email->style_inline( $html );

    $html .= '<style>body[style]{text-align:unset!important;}</style>';
    echo $html;
endif;