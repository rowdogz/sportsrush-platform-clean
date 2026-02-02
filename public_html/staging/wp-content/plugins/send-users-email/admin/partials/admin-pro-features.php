<div class="container-fluid">
    <div class="row row d-flex align-items-stretch pro-features-list">

        <div class="col-md-12">
            <h2 class="text-center pt-3 pb-3 display-7 text-uppercase"><?php 
echo __( 'Features of the PRO version', 'send-users-email' );
?></h2>
        </div>

        <?php 
?>
            <div class="col-sm-12 my-5 text-center">
                <a class="btn btn-success btn-lg" href="<?php 
echo sue_fs()->get_upgrade_url();
?>"
                   role="button"><?php 
echo __( 'Upgrade to PRO', 'send-users-email' );
?></a>
            </div>
        <?php 
?>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'queue-icon.svg' );
?>" class="card-img-top" alt="Queue">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'Queue System', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( 'Having trouble with your email service provider, due to reaching your daily or monthly limits?', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( "The queue system of this plugin will send a specified amount of emails regularly so that you don't hit that limit.", 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'For example: If your hosting/email provider only allows for 300 outgoing emails per day, but you are about to send 900 emails, you can configure the plugin in such a way that it only sends 300 emails per day, staying below the limit.', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'These emails will be sent periodically and automatically, using the WordPress cron functionality. You will just have to send 900 emails once and the plugin will do the rest.', 'send-users-email' );
?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'template-icon.svg' );
?>" class="card-img-top"
                         alt="Template">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'Email Template', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( 'Are you sending the same email content over and over again?', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'Are you tired of typing same email repeatedly?', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'The PRO version of Send Users Email allows you to save a email templates and reuse them when sending emails to your users.', 'send-users-email' );
?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'usergroup-icon.svg' );
?>" class="card-img-top" alt="Queue">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'User Groups', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( 'Choosing individual users or sending emails to roles is not cutting it for you?', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'The PRO version supports creation of user groups.', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'Easily add users to a group and send emails to all users in that group at once.', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'You can use the queue system when sending emails to groups as well. This will make sure, you stay within your providers daily email cap.', 'send-users-email' );
?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'styles-icon.svg' );
?>" class="card-img-top"
                         alt="Template">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'Email Styles', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( 'Having trouble crafting decent-looking emails?', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'Send Users Email provides you with an option to use prebuilt email styles.', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'The PRO version of Send Users Email provides email styles that are compatible with various screen sizes and you can choose different color schemes as per your need.', 'send-users-email' );
?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'smtp-icon.svg' );
?>" class="card-img-top"
                         alt="Template">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'SMTP Server', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( "Your emails often go to your user's spam folder? Or you want to send emails in bulk that actually reach your users?", 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'The PRO version of Send Users Email has the option to save your own SMTP server settings.', 'send-users-email' );
?></p>
                        <p class="card-text"><?php 
echo __( 'That way, you can send emails directly via your own email server or third-party providers like Mailgun, Brevo and others', 'send-users-email' );
?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card shadow">
                <div class="card-body" style="text-align: justify;">
                    <img src="<?php 
echo sue_get_asset_url( 'others-icon.svg' );
?>" class="card-img-top"
                         alt="Placeholder">
                    <div>
                        <h5 class="card-title text-uppercase"><?php 
echo __( 'Other features', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
echo __( 'The PRO version of Send Users Email provides these additional features to make your life a easier.', 'send-users-email' );
?></p>
                        <ul class="list-group list-group-numbered">
                            <li class="list-group-item"><?php 
echo __( 'Use placeholders on email subjects to personalize your emails even further.', 'send-users-email' );
?></li>
                            <li class="list-group-item"><?php 
echo __( 'Ability to send queued emails at a later date. Schedule your emails to be send in the future.', 'send-users-email' );
?></li>
                            <li class="list-group-item"><?php 
echo __( 'A clutter-free plugin area so that you can focus on things that matter to you.', 'send-users-email' );
?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

		<?php 
?>

    </div>

</div>