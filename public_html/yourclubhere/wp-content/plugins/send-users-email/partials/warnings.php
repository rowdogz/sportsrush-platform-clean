<?php

if ( ini_get( 'max_execution_time' ) <= 60 ) {
    ?>
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title text-uppercase mb-4"><?php 
    echo esc_html_e( 'Warning', 'send-users-email' );
    ?></h5>
            <div class="alert alert-warning" role="alert">
                <p>
                    <?php 
    echo sprintf( 
        /* translators: %d is the PHP max execution time */
        esc_html_e( 'Your PHP max execution time is %d seconds. Please consider increasing this limit if you are trying to send email to lots of users at once.', 'send-users-email' ),
        esc_html( ini_get( 'max_execution_time' ) )
     );
    ?>
                </p>
                <p><?php 
    esc_html_e( 'Consider sending email to users in batches. Email User feature allows you to filter users by ID range and can be used to send email in batches.', 'send-users-email' );
    ?></p>
				<?php 
    ?>

            </div>
        </div>
    </div>
<?php 
}