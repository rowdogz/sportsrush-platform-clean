<?php
// Check if the file is being accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template for displaying the custom HTML email template editor.
 * @var $args
 */
$title      = $args['title'];
$data       = $args['data'] ?? [];
$is_default = $args['is_default'] ?? false;
?>

<div class="container-fluid">
    <div class="row sue-row">

        <div class="col-sm-9">  
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title text-uppercase mb-4"><?php echo esc_attr( $title, 'send-users-email' ); ?></h5>

                    <div class="sue-messages"></div>

                    <form action="javascript:void(0)" id="sue-custom-html-template-form" method="post">

                        <div class="mb-4">
                            <label for="custom_html_css" class="form-label">
                                <?php esc_attr_e( 'Add your custom HTML/CSS template here.', 'send-users-email' ); ?>
                            </label>
                            <div class="wp-editor-wrap">
                                <textarea id="custom_html_css" name="custom_html_css" rows="20" class="form-control"><?php echo esc_html( $data ); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="spinner-border text-info sue-spinner" role="status">
                                <span class="visually-hidden"><?php esc_attr_e( 'Loading...',
                                        'send-users-email' ) ?></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary" id="sue-custom-html-template-btn">
                                <span class="dashicons dashicons-admin-settings"></span> Save                                        
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-sm-3">
            
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title text-uppercase"><?php esc_attr_e( 'Instruction', 'send-users-email' ); ?></h5>
                    <p class="card-text">
                        <?php esc_attr_e( 'You can paste your own custom HTML template here. Copy your HTML from your favorite newsletter tool, or use free templates from the web. Once pasted here, you can use the placeholders below to define were to ouput your email content. Only use those placeholders and use individual placeholders for personalization only on the email writing interface. You can set this template as your default template on the settings section. Before you use it, click on the Theme Preview section to preview it.', 'send-users-email' ); ?>
                    </p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title text-uppercase"><?php esc_attr_e( 'Placeholder', 'send-users-email' ); ?></h5>
                    <p class="card-text"><?php esc_attr_e( 'You can use the following placeholders to output your content.', 'send-users-email' ); ?></p>
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td>
                                        {{email_title}}<br>
                                        <?php esc_attr_e( 'Your email title, as set in the email interface', 'send-users-email' ); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                        {{email_tagline}}<br>
                                        <?php esc_attr_e( 'Your email tagline, as set in the email interface', 'send-users-email' ); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                        {{email_content}}<br>
                                        <?php esc_attr_e( 'Your email content, as set in the email interface', 'send-users-email' ); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                        {{email_logo}}<br>
                                        <?php esc_attr_e( 'Email logo URL you set in the settings', 'send-users-email' ); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="sue-messages"></div>
                </div>
            </div>

        </div>
    </div>
</div>    

<?php require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/toast.php'; ?>
