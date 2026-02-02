<?php

$options = get_option('sue_send_users_email');

// Ensure $options is an array before accessing its keys
if ( ! is_array( $options ) ) {
    $options = [];
}

// Use null coalescing operator (??) for default values
$logo = $options['logo_url'] ?? SEND_USERS_EMAIL_PLUGIN_BASE_URL . '/assets/sample-logo.png';
$title = $options['email_title'] ?? __('This is a preview title', 'send-users-email');
$tagline = $options['email_tagline'] ?? __('This is a preview tagline', 'send-users-email');
$footer = $options['email_footer'] ?? __('Demo Footer Content', 'send-users-email');

// Handle social links with a default array
$social = $options['social'] ?? ["facebook" => "#", "instagram" => "#", "linkedin" => "#", "skype" => "#", "tiktok" => "#", "twitter" => "#", "youtube" => "#"];

// Default email body content
$email_body = __('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'send-users-email');

// Optional styles
$styles = $options['email_template_style'] ?? '';

$style = ( isset( $_GET['style'] ) ) ? sanitize_text_field( $_GET['style'] ) : 'default';

?>
<style>
    #wpbody {
        height: calc(100vh - 32px);
        background: #dde0e1;
        display: flex;
    }
    #wpcontent {
        background: #dde0e1;
    }
    .sue-main-table {
        max-width: 768px;
        margin: 0 auto;
    }
</style>

<div class="container" style="margin-bottom: 3rem;margin-top:2rem;">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <p><strong><?php esc_attr_e( 'Select email theme for preview', 'send-users-email' ); ?></strong></p>
            <select class="form-select" aria-label="Select email style" id="email_style" name="email_style">
                <?php foreach ( sue_get_email_theme_scheme() as $theme ): ?>
                    <option
                        value="<?php esc_attr_e( $theme, 'send-users-email' ); ?>"
                        <?php echo ( $style == $theme ) ? 'selected="selected"' : ''; ?>">
                        <?php esc_attr_e( ucfirst( esc_attr( $theme ) ), 'send-users-email' ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        const emailStyle = document.getElementById('email_style');
        emailStyle.addEventListener('change', function() {
            const style = emailStyle.value;
            window.location.href = '<?php echo esc_attr( admin_url( 'admin.php?page=send-users-email-preview' ) ); ?>&style=' + style;
        });
    });
</script>

<?php

switch ( $style ) {
    case "blue":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-blue.php';
        break;
    case "green":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-green.php';
        break;
    case "pink":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-pink.php';
        break;
    case "purple":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-purple.php';
        break;
    case "red":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-red.php';
        break;
    case "yellow":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-yellow.php';
        break;
    case "purity":
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/email/email-template-purity.php';
        break;
    case 'custom':
        echo SUE_Custom_Html_Template::parse_template(
            $title,
            $tagline,
            $email_body,
            $logo 
        );
        break;
    case 'woocommerce':
        global $preview;
        $preview = true;
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/templates/woo-email-template.php';
        break;
    case "default":
        global $preview;
        $preview = true;
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/email-template.php';
        break;
    default:
        global $preview;
        $preview = true;
        require SEND_USERS_EMAIL_PLUGIN_BASE_PATH .'/admin/partials/email-template.php';
}

?>