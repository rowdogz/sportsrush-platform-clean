<div class="container-fluid">
    <div class="row sue-row">

        <div class="col-sm-9">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-5 text-uppercase"><?php 
esc_attr_e( 'Settings', 'send-users-email' );
?></h5>

                    <div class="sue-messages"></div>

                    <form action="javascript:void(0)" id="sue-settings-form" method="post">

                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <tr>
                                    <td style="width: 25%">
                                        <label for="logo" class="form-label"><?php 
esc_attr_e( 'Logo URL', 'send-users-email' );
?></label>
                                        <div id="logoHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Add email header logo URL here. If left blank, logo will not be used.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="logo" name="logo"
                                               value="<?php 
echo esc_url_raw( $logo );
?>"
                                               placeholder="<?php 
esc_attr_e( 'Add your logo URL', 'send-users-email' );
?>"
                                               aria-describedby="logoHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="title" class="form-label"><?php 
esc_attr_e( 'Email Title', 'send-users-email' );
?></label>
                                        <div id="titleHelp"
                                             class="form-text"><?php 
esc_attr_e( 'This value will be shown below logo image.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="title" name="title"
                                               value="<?php 
echo esc_attr( $title );
?>"
                                               placeholder="<?php 
bloginfo( 'name' );
?>"
                                               aria-describedby="titleHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="tagline" class="form-label"><?php 
esc_attr_e( 'Email Tagline', 'send-users-email' );
?></label>
                                        <div id="taglineHelp"
                                             class="form-text"><?php 
esc_attr_e( 'This value will be shown below email title image.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="tagline" name="tagline"
                                               value="<?php 
echo esc_attr( $tagline );
?>"
                                               placeholder="<?php 
bloginfo( 'description' );
?>"
                                               aria-describedby="taglineHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="footer" class="form-label"><?php 
esc_attr_e( 'Email Footer', 'send-users-email' );
?></label>
                                        <div id="footerHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Email footer content will be added to all emails at footer of email (supports HTML). Please use full https links for maximum compatibility among email clients.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="footer" name="footer"
                                               value="<?php 
echo esc_attr( $footer );
?>"
                                               placeholder="<?php 
esc_attr_e( 'Email footer content', 'send-users-email' );
?>"
                                               aria-describedby="footerHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="email_from_name"
                                               class="form-label"><?php 
esc_attr_e( 'Email From/Reply-To Name', 'send-users-email' );
?></label>
                                        <div id="emailFromNameHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Email from/reply-to name to use in send emails.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="email_from_name"
                                               name="email_from_name"
                                               value="<?php 
echo esc_attr( $email_from_name );
?>"
                                               placeholder="<?php 
esc_attr_e( 'Email from Name', 'send-users-email' );
?>"
                                               aria-describedby="emailFromNameHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="email_from_address"
                                               class="form-label"><?php 
esc_attr_e( 'Email From Address', 'send-users-email' );
?></label>
                                        <div id="emailFromAddressHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Email from address to use in send emails.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="email" class="form-control" id="email_from_address"
                                               name="email_from_address"
                                               value="<?php 
echo esc_attr( $email_from_address );
?>"
                                               placeholder="me@example.net"
                                               aria-describedby="emailFromAddressHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="reply_to_address"
                                               class="form-label"><?php 
esc_attr_e( 'Reply To Address', 'send-users-email' );
?></label>
                                        <div id="replyToAddressHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Reply To address to use.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <input type="email" class="form-control" id="reply_to_address"
                                               name="reply_to_address"
                                               value="<?php 
echo esc_attr( $reply_to_address );
?>"
                                               placeholder="reply@example.net"
                                               aria-describedby="replyToAddressHelp">
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="email_template_style"
                                               class="form-label"><?php 
esc_attr_e( 'Email Template Style', 'send-users-email' );
?></label>
                                        <div id="emailTemplateStyleHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Add your custom CSS style to email template. These style will be applied on top of default style.', 'send-users-email' );
?></div>
                                    </td>
                                    <td>
                                        <textarea
                                                name="email_template_style"
                                                id="email_template_style"
                                                class="form-control"
                                                rows="7"
                                                placeholder="body { background-color:#eee; }"><?php 
echo esc_attr( $email_template_style );
?></textarea>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="email_send_roles"
                                               class="form-label"><?php 
esc_attr_e( 'Roles', 'send-users-email' );
?></label>
                                        <div id="emailSendRolesHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Select role(s) that has access to send emails. Administrators by default has this access.', 'send-users-email' );
?><br>
                                            <span class="text-danger"><strong><?php 
esc_attr_e( 'Be careful that only intended users have this access.', 'send-users-email' );
?></strong></span>
                                        </div>
                                    </td>
                                    <td>
										<?php 
foreach ( $roles as $slug => $name ) {
    ?>
                                            <div class="form-check">
                                                <input
                                                        class="form-check-input"
                                                        name="email_send_roles[]"
                                                        type="checkbox"
                                                        value="<?php 
    echo esc_html( $slug );
    ?>"
                                                        id="role-<?php 
    echo esc_html( $slug );
    ?>"
													<?php 
    if ( in_array( $slug, $selected_roles ) ) {
        echo ' checked';
    }
    ?>
                                                        style="margin-top: 8px;">
                                                <label class="form-check-label"
                                                       for="role-<?php 
    echo esc_html( $slug );
    ?>">
													<?php 
    echo esc_html( $name );
    ?>
                                                </label>
                                            </div>
										<?php 
}
?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="email_social_links"
                                               class="form-label"><?php 
esc_attr_e( 'Social Media', 'send-users-email' );
?></label>
                                        <div id="emailSendRolesHelp"
                                             class="form-text"><?php 
esc_attr_e( 'Add link to your social media pages and this will be used on email you send to your users. Leave empty if you don\'t want it included.', 'send-users-email' );
?><br>
                                            <span class="text-warning"><?php 
esc_attr_e( 'Please use full URL to social media profile.', 'send-users-email' );
?></span>
                                        </div>
                                    </td>
                                    <td>
										<?php 
foreach ( Send_Users_Email_Admin::$social as $platform ) {
    ?>
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">
                                                    <img src="<?php 
    echo esc_attr( sue_get_asset_url( $platform . '.png' ) );
    ?>"
                                                         alt="<?php 
    echo esc_attr( $platform );
    ?>"
                                                         title="<?php 
    echo esc_attr( ucfirst( $platform ) );
    ?>" height="18">
                                                </span>
                                                <input type="text" class="form-control" id="<?php 
    echo esc_attr( $platform );
    ?>"
                                                       name="social[<?php 
    echo esc_attr( $platform );
    ?>]"
                                                       value="<?php 
    echo $social[$platform] ?? '';
    ?>"
                                                       placeholder="<?php 
    esc_attr_e( ucfirst( $platform ), 'send-users-email' );
    ?>"
                                                >
                                            </div>
										<?php 
}
?>
                                    </td>
                                </tr>

								<?php 
?>

                                <tr>
                                    <td>
                                        <div class="spinner-border text-info sue-spinner" role="status">
                                            <span class="visually-hidden"><?php 
esc_attr_e( 'Loading...', 'send-users-email' );
?></span>
                                        </div>
                                        <input type="hidden" id="_wpnonce" name="_wpnonce"
                                               value="<?php 
echo esc_attr( wp_create_nonce( 'sue-email-user' ) );
?>"/></td>
                                    <td>
                                        <button type="submit" class="btn btn-primary" id="sue-settings-btn">
                                            <span class="dashicons dashicons-admin-settings"></span> <?php 
esc_attr_e( 'Save Settings', 'send-users-email' );
?>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <div class="col-sm-3">

			<?php 
require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/donate.php';
?>

            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title text-uppercase"><?php 
esc_attr_e( 'Instruction', 'send-users-email' );
?></h5>
                    <p class="card-text"><?php 
esc_attr_e( 'Please configure email settings before sending emails.', 'send-users-email' );
?></p>
                    <p class="card-text"><?php 
esc_attr_e( 'If the settings fields are left blank, the corresponding section will not be added to email.', 'send-users-email' );
?></p>
                    <p class="card-text"><?php 
esc_attr_e( 'Example: If you leave logo setting blank, no logo will be added to outgoing emails.', 'send-users-email' );
?></p>
                </div>
            </div>

            <div class="card shadow bg-warning bg-opacity-10">
                <div class="card-body">
                    <h5 class="text-uppercase mb-4"><?php 
esc_attr_e( "CAUTION", 'send-users-email' );
?></h5>
                    <p><?php 
esc_attr_e( "Email from Name and Email from Address can be overwritten by other plugins.", 'send-users-email' );
?></p>
                    <p><?php 
esc_attr_e( "If from Name and from Email set here is not working, please make sure that other plugins are not overriding.", 'send-users-email' );
?></p>
                </div>
            </div>

			<?php 
?>


        </div>

		<?php 
require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/toast.php';
?>

    </div>
</div>