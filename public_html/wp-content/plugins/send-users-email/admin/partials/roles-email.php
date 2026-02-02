<div class="container-fluid">
    <div class="row sue-row">

        <div class="col-sm-9">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-4 text-uppercase text-center"><?php 
esc_attr_e( 'Send email to selected roles', 'send-users-email' );
?></h5>

					<?php 
?>

                    <form action="javascript:void(0)" id="sue-roles-email-form" method="post">

                        <div class="mb-4">
                            <label for="subject"
                                   class="form-label"><?php 
esc_attr_e( 'Email Subject', 'send-users-email' );
?></label>
                            <input type="text" class="form-control subject" id="subject" name="subject" maxlength="200"
                                   placeholder="<?php 
esc_attr_e( 'Email subject here', 'send-users-email' );
?> <?php 
?>">
                        </div>

                        <?php 
if ( $allowed_title_tagline && sue_fs()->is__premium_only() && sue_fs()->can_use_premium_code() ) {
    ?>

                            <div class="mb-4">
                                <label for="title"
                                    class="form-label"><?php 
    esc_attr_e( 'Email Title', 'send-users-email' );
    ?></label>
                                <input type="text" class="form-control title" id="title" name="title" maxlength="200" value="<?php 
    echo esc_attr( $title );
    ?>"
                                    placeholder="<?php 
    esc_attr_e( 'Email title here', 'send-users-email' );
    ?> <?php 
    ?>">
                            </div>

                            <div class="mb-4">
                                <label for="tagline"
                                    class="form-label"><?php 
    esc_attr_e( 'Email Tagline', 'send-users-email' );
    ?></label>
                                <input type="text" class="form-control tagline" id="tagline" name="tagline" maxlength="200" value="<?php 
    echo esc_attr( $tagline );
    ?>"
                                    placeholder="<?php 
    esc_attr_e( 'Email tagline here', 'send-users-email' );
    ?> <?php 
    ?>">
                            </div>

                        <?php 
}
?>

                        <div class="mb-4">
                            <div class="sue-role-email-list">
                                <label class="form-label"><?php 
esc_attr_e( 'Select Role(s)', 'send-users-email' );
?></label>
                                <div class="row">
                                    <div class="col-md-6">
										<?php 
$roles_count = 0;
?>
                                        <ul class="list-group">
											<?php 
foreach ( $roles as $slug => $user_count ) {
    ?>
												<?php 
    if ( $user_count ) {
        ?>
													<?php 
        if ( $roles_count % 2 == 0 ) {
            ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input roleCheckbox"
                                                                       name="roles[]"
                                                                       type="checkbox"
                                                                       value="<?php 
            echo esc_attr( $slug );
            ?>"
                                                                       id="<?php 
            echo esc_attr( $slug );
            ?>"
                                                                       style="margin-top: 7px;">
                                                                <label class="form-check-label"
                                                                       for="<?php 
            echo esc_attr( $slug );
            ?>">
																	<?php 
            echo esc_attr( ucwords( str_replace( '_', ' ', esc_html( $slug ) ) ) );
            ?>
                                                                </label>
                                                            </div>
                                                            <span class="badge bg-primary rounded-pill"><?php 
            echo esc_html( $user_count );
            ?></span>
                                                        </li>
													<?php 
        }
        ?>
												<?php 
    }
    ?>
												<?php 
    $roles_count++;
    ?>
											<?php 
}
?>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">
										<?php 
$roles_count = 0;
?>
                                        <ul class="list-group">
											<?php 
foreach ( $roles as $slug => $user_count ) {
    ?>
												<?php 
    if ( $user_count ) {
        ?>
													<?php 
        if ( $roles_count % 2 == 1 ) {
            ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input roleCheckbox"
                                                                       name="roles[]"
                                                                       type="checkbox"
                                                                       value="<?php 
            echo esc_attr( $slug );
            ?>"
                                                                       id="<?php 
            echo esc_attr( $slug );
            ?>"
                                                                       style="margin-top: 7px;">
                                                                <label class="form-check-label"
                                                                       for="<?php 
            echo esc_attr( $slug );
            ?>">
																	<?php 
            echo esc_attr( ucwords( str_replace( '_', ' ', esc_html( $slug ) ) ) );
            ?>
                                                                </label>
                                                            </div>
                                                            <span class="badge bg-primary rounded-pill"><?php 
            echo esc_html( $user_count );
            ?></span>
                                                        </li>

													<?php 
        }
        ?>
												<?php 
    }
    ?>
												<?php 
    $roles_count++;
    ?>
											<?php 
}
?>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="sue_user_email_message"
                                   class="form-label"><?php 
esc_attr_e( 'Email Message', 'send-users-email' );
?></label>

							<?php 
// Initialize RTE
wp_editor( '', 'sue_user_email_message', [
    'textarea_rows' => 15,
] );
?>
                            <div class="message"></div>
                        </div>

                        <input type="hidden" id="_wpnonce" name="_wpnonce"
                               value="<?php 
echo esc_attr( wp_create_nonce( 'sue-email-user' ) );
?>"/>

						<?php 
?>
                        
                        <?php 
?>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-grid gap-2">
                                    <button type="submit" id="sue-roles-email-btn" class="btn btn-primary btn-block">
                                        <span class="dashicons dashicons-email"></span> <?php 
esc_attr_e( 'Send Message', 'send-users-email' );
?>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 mt-2">
                                <div class="spinner-border text-info sue-spinner" role="status">
                                    <span class="visually-hidden"><?php 
esc_attr_e( 'Loading...', 'send-users-email' );
?></span>
                                </div>
                            </div>
                            <div class="col-md-7 mt-2">
                                <div class="progress" style="height: 20px; display: none;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                         role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <div class="col-sm-3">

			<?php 
require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/donate.php';
?>

			<?php 
require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/warnings.php';
?>

			<?php 
?>
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-uppercase"><?php 
esc_attr_e( 'Instruction', 'send-users-email' );
?></h5>
                        <p class="card-text"><?php 
esc_attr_e( 'Send email to all users belonging to selected roles.', 'send-users-email' );
?></p>
                    </div>
                </div>
			<?php 
?>

			<?php 
// Include placeholder instructions
require plugin_dir_path( __FILE__ ) . 'templates/placeholder-instruction.php';
?>

        </div>

		<?php 
require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/partials/toast.php';
?>

    </div>
</div>