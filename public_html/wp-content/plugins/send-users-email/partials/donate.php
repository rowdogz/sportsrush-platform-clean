
        <div class="card shadow">
            <div class="card-body">
                <h5 class="card-title mb-4 text-uppercase"><?php 
esc_html_e( 'Send Users Email PRO', 'send-users-email' );
?></h5>
				<?php 
echo '<p class="card-text"><a class="btn btn-success" href="' . esc_url( sue_fs()->get_upgrade_url() ) . '">';
esc_html_e( 'Upgrade Now!', 'send-users-email' );
echo '</a></p>';
?>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <p class="card-text text-uppercase" style="font-size: 1rem;">
                    <a style="text-decoration: none;" target="_blank"
                       href="https://wordpress.org/support/plugin/send-users-email/reviews/#new-post">
	                    <?php 
esc_html_e( 'Rate the plugin', 'send-users-email' );
?>
                        <span style="color: #ffb900; font-size: 1.3rem;">&starf;&starf;&starf;&starf;&starf;</span>
                    </a>
                </p>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <p class="card-text text-uppercase" style="font-size: 1rem;">
                    <a style="text-decoration: none;" target="_blank"
                       href="https://trello.com/b/ngaIRuqL/send-users-email-plugin-feature-requests">
                        <?php 
esc_html_e( 'Vote for new features 👍', 'send-users-email' );
?>
                    </a>
                </p>
            </div>
        </div>

