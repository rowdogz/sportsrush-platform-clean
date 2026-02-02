<div class="card shadow">
    <div class="card-body">
        <h5 class="card-title text-uppercase"><?php esc_attr_e( 'Placeholder', 'send-users-email' ); ?></h5>
        <p class="card-text"><?php esc_attr_e( 'You can use following placeholder to replace user detail on email.',
				'send-users-email' ); ?></p>
        <table class="table table-borderless">
            <tr>
                <td>
                    {{user_id}}<br>
					<?php esc_attr_e( 'Use this placeholder to display user ID.', 'send-users-email' ); ?>
                </td>
            </tr>
            <tr>
                <td>
                    {{username}}<br>
					<?php esc_attr_e( 'Use this placeholder to display username', 'send-users-email' ); ?>
                </td>
            </tr>
            <tr>
                <td>
                    {{user_display_name}}<br>
					<?php esc_attr_e( 'Use this placeholder to display user display name', 'send-users-email' ); ?>
                </td>
            </tr>
            <tr>
                <td>
                    {{user_first_name}}<br>
					<?php esc_attr_e( 'Use this placeholder to display user first name', 'send-users-email' ); ?>
                </td>
            </tr>
            <tr>
                <td>
                    {{user_last_name}}<br>
					<?php esc_attr_e( 'Use this placeholder to display user last name', 'send-users-email' ); ?>
                </td>
            </tr>
            <tr>
                <td>
                    {{user_email}}<br>
					<?php esc_attr_e( 'Use this placeholder to display user email', 'send-users-email' ); ?>
                </td>
            </tr>
        </table>

        <div class="sue-messages"></div>

    </div>
</div>