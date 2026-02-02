<?php
/**
 * @var $errorFileSizeMB
 * @var $errorFileMaxSizeLimit
 * @var $emailLogFiles
 * @var $errorLog
 */
?>
<div class="container-fluid">
    <div class="row sue-row">

        <div class="col-md-12">

			<?php if ( empty( $emailLogFiles ) && empty( $errorLog ) ): ?>
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-uppercase mb-4 text-primary text-center"><?php esc_attr_e( 'Logs',
								'send-users-email' ); ?></h5>
                        <div class="alert alert-info" role="alert">
                            <p class="text-center"><?php esc_attr_e( 'There are no logs to display right now.',
									'send-users-email' ); ?></p>
                            <p class="text-center"><?php esc_attr_e( 'You will find error log and email log here once available.',
									'send-users-email' ); ?></p>
                        </div>

                    </div>
                </div>
			<?php endif; ?>

			<?php if ( ! empty( $emailLogFiles ) ): ?>
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title text-uppercase mb-4 text-primary"><?php esc_attr_e( 'Email Log',
								'send-users-email' ); ?></h5>
                        <form class="row row-cols-lg-auto g-3 align-items-center" action="javascript:void(0)"
                              method="post" id="sue-view-email-log">
                            <input type="hidden" id="_wpnonce" name="_wpnonce"
                                   value="<?php echo esc_attr( wp_create_nonce( 'sue-email-log-view' ) ); ?>"/>
                            <div class="form-group">
                                <select class="form-select" aria-label="Select date to view email log"
                                        name="sue_view_email_log_file" id="sue_view_email_log_file">
                                    <option value="0" selected>Select date to view sent email logs</option>
									<?php foreach ( $emailLogFiles as $file ): ?>
										<?php $displayName = ucwords( str_replace( [ '-', '.log' ], ' ', $file ) ); ?>
                                        <option value="<?php echo esc_attr($file); ?>"><?php echo esc_attr($displayName); ?></option>
									<?php endforeach; ?>
                                </select>
                            </div>
                            <button id="sue-view-log-btn" class="btn btn-primary btn-sm text-uppercase mx-3"
                                    type="submit" disabled>
								<?php esc_attr_e( 'View Log', 'send-users-email' ); ?>
                            </button>
                        </form>
                        <div class="form-group mt-4" id="emailLogTextAreaContainer" style="display: none;">
                            <textarea class="form-control" id="email_log_view_area" rows="12" readonly></textarea>
                            <div class="form-text" id="logFileSize"></div>
                        </div>
                    </div>
                </div>
			<?php endif; ?>

			<?php if ( ! empty( $errorLog ) ): ?>
                <div class="card shadow">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-9">
                                <h5 class="card-title text-uppercase mb-4 text-danger"><?php esc_attr_e( 'Error Log',
										'send-users-email' ); ?></h5>
                            </div>
                            <div class="col-md-3">
                                <form action="" method="post" style="float: right;"
                                      onsubmit="return confirm('Are you sure?')">
                                    <input type="hidden" id="_wpnonce" name="_wpnonce"
                                           value="<?php echo esc_attr( wp_create_nonce( 'sue-delete-error-log' ) ); ?>"/>
                                    <button class="btn btn-danger btn-sm text-uppercase" type="submit"
                                            name="sue_delete_error_log"><?php esc_attr_e( 'Delete Error Logs', 'send-users-email' ); ?></button>
                                </form>
                            </div>
                        </div>

                        <div class="form-group">
                            <textarea class="form-control" id="error_log" rows="8"
                                      readonly><?php echo esc_textarea( $errorLog ); ?></textarea>
                            <div class="form-text">
								<?php esc_attr_e( 'Please note that the error logged here is only when wp_mail function fails.',
									'send-users-email' ) ?>
								<?php esc_attr_e( 'It is possible that wp_mail is able to send email successfully but your email service provider dropped sent email at their end.',
									'send-users-email' ) ?>
								<?php esc_attr_e( 'In this case there is nothing this plugin can do and you need to contact your email service provider for more details on why your emails are not being sent.',
									'send-users-email' ) ?>
                            </div>
                        </div>

						<?php
						$barColor       = 'success';
						$warningMessage = '';
						if ( $errorFileSizeMB > 0.75 * $errorFileMaxSizeLimit ) {
							$barColor       = 'info';
							$warningMessage = __( 'Error log file is getting large. Please consider clearing it soon.',
								'send-users-email' );
						}
						if ( $errorFileSizeMB > 0.9 * $errorFileMaxSizeLimit ) {
							$barColor       = 'danger';
							$warningMessage = __( 'IMPORTANT! Error log file is getting large. Please clear log file if you are experiencing slow page load.',
								'send-users-email' );
						}
						$barPercent = floor( $errorFileSizeMB * 100 / $errorFileMaxSizeLimit );
						?>

						<?php if ( $barPercent > 15 ): ?>
                            <div class="progress mt-3" role="progressbar" aria-label="Success example"
                                 aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-<?php echo esc_attr($barColor); ?>"
                                     style="width: <?php echo esc_attr($barPercent); ?>%">
									<?php esc_attr_e( 'Error log file size is ',
										'send-users-email' ) ?> <?php echo esc_attr($errorFileSizeMB); ?> MB.
									<?php echo esc_attr($warningMessage); ?>
                                </div>
                            </div>
						<?php endif; ?>


                    </div>
                </div>
			<?php endif; ?>

        </div>

    </div>
</div>