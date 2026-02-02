<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Send_Users_Email_Admin {
    private $plugin_name;

    private $version;

    public static $social = [
        'facebook',
        'instagram',
        'linkedin',
        'skype',
        'tiktok',
        'twitter',
        'youtube'
    ];

    /**
     * Add all admin page slugs here ...
     */
    private $plugin_pages_slug = array(
        'send-users-email',
        'send-users-email-users',
        'send-users-email-roles',
        'send-users-email-settings',
        'send-users-email-pro-features',
        'send-users-email-email-templates',
        'send-users-email-email-queue',
        'send-users-email-error-logs',
        'send-users-email-user-groups',
        'send-users-email-groups',
        'send-users-email-preview',
        'send-users-html-template'
    );

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        // Add css to this plugin page only
        $page = ( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : "" );
        if ( in_array( $page, $this->plugin_pages_slug ) ) {
            wp_enqueue_style(
                'sue-bootstrap-5',
                plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css',
                array(),
                '5.2.2',
                'all'
            );
            wp_enqueue_style(
                'sue-bootstrap-5-datatable',
                plugin_dir_url( __FILE__ ) . 'css/dataTables.bootstrap5.min.css',
                array('sue-bootstrap-5'),
                '1.11.2',
                'all'
            );
            wp_enqueue_style(
                $this->plugin_name,
                plugin_dir_url( __FILE__ ) . 'css/send-users-email-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        // Add JS to this plugin page only
        $page = ( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : "" );
        if ( in_array( $page, $this->plugin_pages_slug ) ) {
            wp_enqueue_script(
                'bootstrap-js',
                plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js',
                array('jquery'),
                '5.1.1',
                true
            );
            wp_enqueue_script(
                'datatable-js',
                plugin_dir_url( __FILE__ ) . 'js/jquery.dataTables.min.js',
                array('jquery'),
                '1.11.2',
                true
            );
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url( __FILE__ ) . 'js/send-users-email-admin.js',
                array('jquery'),
                $this->version,
                true
            );
        }
    }

    /**
     * Register admin menu Items
     */
    public function admin_menu() {
        add_menu_page(
            __( "Send Users Email", "send-users-email" ),
            __( "Email to Users", "send-users-email" ),
            SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY,
            'send-users-email',
            [$this, 'admin_dashboard'],
            'dashicons-email-alt2',
            250
        );
        add_submenu_page(
            'send-users-email',
            __( 'Dashboard', "send-users-email" ),
            __( 'Dashboard', "send-users-email" ),
            SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY,
            'send-users-email',
            [$this, 'admin_dashboard']
        );
        add_submenu_page(
            'send-users-email',
            __( 'Email Users', "send-users-email" ),
            __( 'Email Users', "send-users-email" ),
            SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY,
            'send-users-email-users',
            [$this, 'users_email']
        );
        add_submenu_page(
            'send-users-email',
            __( 'Email Roles', "send-users-email" ),
            __( 'Email Roles', "send-users-email" ),
            SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY,
            'send-users-email-roles',
            [$this, 'roles_email']
        );
        add_submenu_page(
            'send-users-email',
            __( 'Email Theme Preview', 'send-users-email' ),
            __( 'Theme Preview', 'send-users-email' ),
            'manage_options',
            'send-users-email-preview',
            [$this, 'theme_preview']
        );
        add_submenu_page(
            'send-users-email',
            __( 'Settings', "send-users-email" ),
            __( 'Settings', "send-users-email" ),
            'manage_options',
            'send-users-email-settings',
            [$this, 'settings']
        );
        add_submenu_page(
            'send-users-email',
            __( 'PRO Features', "send-users-email" ),
            __( 'PRO Features', "send-users-email" ),
            SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY,
            'send-users-email-pro-features',
            [$this, 'pro_features']
        );
        add_submenu_page(
            'send-users-email',
            __( 'Email & Error Log', "send-users-email" ),
            __( 'Email & Error Log', "send-users-email" ),
            'manage_options',
            'send-users-email-error-logs',
            [$this, 'email_and_error_log']
        );
    }

    /**
     * Admin Dashboard page
     */
    public function admin_dashboard() {
        $users = count_users();
        require_once 'partials/admin-dashboard.php';
    }

    /**
     * Admin pro features page
     */
    public function pro_features() {
        require_once 'partials/admin-pro-features.php';
    }

    /**
     * Handle Email send selecting users
     */
    public function users_email() {
        $users = count_users();
        $total_users = $users['total_users'];
        // Get system users
        $templates = [];
        $blog_users = get_users( array(
            'fields' => array(
                'ID',
                'display_name',
                'user_email',
                'user_login'
            ),
        ) );
        // Get the default Email title and Tagline
        $options = get_option( 'sue_send_users_email' );
        $title = $options['email_title'] ?? '';
        $tagline = $options['email_tagline'] ?? '';
        $allowed_title_tagline = $options['allow_title_and_tagline'] ?? 0;
        require_once 'partials/users-email.php';
    }

    /**
     * Handle Email send selecting roles
     */
    public function roles_email() {
        $users = count_users();
        $roles = $users['avail_roles'];
        $templates = [];
        // Get the default Email title and Tagline.
        $options = get_option( 'sue_send_users_email' );
        $title = $options['email_title'] ?? '';
        $tagline = $options['email_tagline'] ?? '';
        $allowed_title_tagline = $options['allow_title_and_tagline'] ?? 0;
        require_once 'partials/roles-email.php';
    }

    /**
     * @return void
     * Handle bulk user selection for email groups on user list page
     */
    public function add_custom_group_selection_button() {
        global $wpdb;
        $groups = $wpdb->get_results( "SELECT * FROM " . SEND_USERS_EMAIL_USER_GROUP_NAME_TABLE );
        ?>
        <div class="alignleft actions" style="margin-left: 10px;">
            <select name="custom_group_id" id="custom_group_select">
                <option value="">Select Email Group ...</option>
                <?php 
        foreach ( $groups as $group ) {
            ?>
                    <option value="<?php 
            echo esc_attr( $group->id );
            ?>"><?php 
            echo esc_html( $group->name );
            ?></option>
                <?php 
        }
        ?>
            </select>
            <button type="button" class="button assign_email_group_button" id="assign_email_group_button">
                <?php 
        echo esc_attr( 'Assign Group', 'send-users-email' );
        ?>
            </button>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.assign_email_group_button');
                buttons.forEach(button => {
                    button.addEventListener('click', function () {
                        let groupId = document.getElementById('custom_group_select').value;
                        const selectElement = this.previousElementSibling;

                        if (selectElement && selectElement.value) {
                            groupId = selectElement.value;
                        } else {
                            return;
                        }

                        let userIds = Array.from(document.querySelectorAll('input[name="users[]"]:checked'))
                            .map(checkbox => checkbox.value);

                        if (groupId && userIds.length > 0) {
                            // Send AJAX request
                            jQuery.post(ajaxurl, {
                                action: 'assign_email_group',
                                group_id: groupId,
                                user_ids: userIds,
                                security: '<?php 
        echo esc_attr( wp_create_nonce( "assign_email_group_nonce" ) );
        ?>'
                            }, function(response) {
                                if (response.success) {
                                    jQuery.post(ajaxurl, {
                                        action: 'set_admin_notification',
                                        message: response.data.message
                                    }, function() {
                                        // Reload the page to display the notification
                                        location.reload();
                                    });
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            });
                        } else {
                            alert('Please select a group and at least one user.');
                        }
                    });
                })
            });
        </script>
        <?php 
    }

    /**
     * @return void
     * Handle AJAX request to assign users to email group
     */
    public function handle_assign_email_group_ajax() {
        check_ajax_referer( 'assign_email_group_nonce', 'security' );
        if ( !empty( $_POST['group_id'] ) && !empty( $_POST['user_ids'] ) ) {
            global $wpdb;
            $group_id = intval( $_POST['group_id'] );
            $user_ids = array_map( 'intval', $_POST['user_ids'] );
            foreach ( $user_ids as $user_id ) {
                $wpdb->delete( SEND_USERS_EMAIL_GROUP_USER_TABLE, [
                    'user_id'  => $user_id,
                    'group_id' => $group_id,
                ] );
                $wpdb->insert( SEND_USERS_EMAIL_GROUP_USER_TABLE, [
                    'group_id' => $group_id,
                    'user_id'  => $user_id,
                ] );
            }
            wp_send_json_success( [
                'message' => count( $user_ids ) . ' users were assigned to the selected group.',
            ] );
        } else {
            wp_send_json_error( [
                'message' => 'Invalid group or user selection.',
            ] );
        }
    }

    /*
     * @return void
     * Handle AJAX request to set admin notification message
     */
    public function set_admin_notification() {
        if ( isset( $_POST['message'] ) ) {
            update_option( 'admin_notification_message', sanitize_text_field( $_POST['message'] ) );
        }
        wp_send_json_success();
    }

    /**
     * @return void
     * Display admin notification message after adding users to group
     */
    public function display_admin_notification() {
        $message = get_option( 'admin_notification_message' );
        if ( $message ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            delete_option( 'admin_notification_message' );
            // Clear message after displaying
        }
    }

    /**
     * Summary of html_template
     * This function is a placeholder for the HTML template page.
     * 
     * @return void
     */
    public function html_template() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
    }

    /**
     * Theme preview page
     * @return void
     */
    public function theme_preview() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        // Retrieve data from the WordPress database as needed
        $data = [
            'site_name' => get_bloginfo( 'name' ),
            'user'      => wp_get_current_user(),
        ];
        global $preview;
        $preview = true;
        require_once 'partials/email-template.php';
    }

    /**
     * Settings page
     */
    public function settings() {
        $options = get_option( 'sue_send_users_email' );
        $logo = $options['logo_url'] ?? '';
        $title = $options['email_title'] ?? '';
        $tagline = $options['email_tagline'] ?? '';
        $footer = $options['email_footer'] ?? '';
        $email_from_name = $options['email_from_name'] ?? '';
        $email_from_address = $options['email_from_address'] ?? '';
        $reply_to_address = $options['reply_to_address'] ?? '';
        $email_template_style = $options['email_template_style'] ?? '';
        $roles = sue_get_roles( ['administrator'] );
        $selected_roles = sue_get_selected_roles();
        $social = $options['social'] ?? [];
        require_once 'partials/settings.php';
    }

    /**
     * Handle Error and Email log page
     */
    public function email_and_error_log() {
        // Error log file setup
        $errorLog = null;
        $errorLogSize = 0;
        $errorFileSizeMB = 0;
        $errorFileMaxSizeLimit = 8;
        $errorLogFileName = sue_get_error_log_filename();
        if ( $errorLogFileName && file_exists( sue_log_path( $errorLogFileName ) ) ) {
            $errorLogSize = filesize( sue_log_path( $errorLogFileName ) );
            $errorFileSizeMB = sue_bytes_to_mb( $errorLogSize );
            $errorLog = file_get_contents( sue_log_path( $errorLogFileName ) );
        }
        // Delete old log files
        Send_Users_Email_cleanup::cleanEmailLogFiles();
        // Get email log files
        $emailLogFiles = array_diff( scandir( sue_log_path() ), array(
            '..',
            '.',
            $errorLogFileName,
            '.htaccess'
        ) );
        $emailLogFiles = sue_remove_non_email_log_filename( $emailLogFiles );
        $emailLogFiles = array_reverse( $emailLogFiles );
        require_once 'partials/email_and_error_log.php';
    }

    /**
     * Handles request to send user email selecting users
     */
    public function handle_ajax_admin_user_email() {
        if ( check_admin_referer( 'sue-email-user' ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'send_email_user' && $action == 'sue_user_email_ajax' ) {
                $options = get_option( 'sue_send_users_email' );
                $option_title = $options['email_title'] ?? '';
                $option_tagline = $options['email_tagline'] ?? '';
                $subject = ( isset( $_REQUEST['subject'] ) ? sanitize_text_field( $_REQUEST['subject'] ) : "" );
                $title = ( isset( $_REQUEST['title'] ) ? sanitize_text_field( $_REQUEST['title'] ) : $option_title );
                $tagline = ( isset( $_REQUEST['tagline'] ) ? sanitize_text_field( $_REQUEST['tagline'] ) : $option_tagline );
                $message = ( isset( $_REQUEST['sue_user_email_message'] ) ? wp_kses_post( $_REQUEST['sue_user_email_message'] ) : "" );
                $users = $_REQUEST['users'] ?? [];
                $users = array_map( 'sanitize_text_field', $users );
                $email_style = 'default';
                $message = sue_remove_caption_shortcode( $message );
                $resMessage = __( '🚀🚀🚀 Email(s) sent successfully!', 'send-users-email' );
                $warningMessage = '';
                // Validate inputs
                $validation_message = [];
                if ( empty( $subject ) || strlen( $subject ) < 2 || strlen( $subject ) > 200 ) {
                    $validation_message['subject'] = __( 'Subject is required and should be between 2 and 200 characters.', "send-users-email" );
                }
                if ( empty( $message ) ) {
                    $validation_message['message'] = __( 'Please provide email content.', "send-users-email" );
                }
                if ( empty( $users ) ) {
                    $validation_message['sue-user-email-datatable'] = __( 'Please select users.', "send-users-email" );
                }
                // If validation fails send, error messages
                if ( count( $validation_message ) > 0 ) {
                    wp_send_json( array(
                        'errors'  => $validation_message,
                        'success' => false,
                    ), 422 );
                }
                // Cleanup email progress record
                Send_Users_Email_cleanup::cleanupUserEmailProgress();
                if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
                    $current_user_id = get_current_user_id();
                    $total_email_send = 0;
                    $total_email_to_send = count( $users );
                    $total_failed_email = 0;
                    $options = get_option( 'sue_send_users_email' );
                    if ( !$options ) {
                        update_option( 'sue_send_users_email', [] );
                    }
                    $options = get_option( 'sue_send_users_email' );
                    $options['email_users_total_email_send_' . $current_user_id] = $total_email_send;
                    $options['email_users_total_email_to_send_' . $current_user_id] = $total_email_to_send;
                    update_option( 'sue_send_users_email', $options );
                    $user_details = get_users( [
                        'include' => $users,
                        'fields'  => [
                            'ID',
                            'display_name',
                            'user_email',
                            'user_login'
                        ],
                    ] );
                    // Email header setup
                    $headers = $this->get_email_headers();
                    foreach ( $user_details as $user ) {
                        $email_body = $message;
                        $username = $user->user_login;
                        $display_name = $user->display_name;
                        $user_email = sanitize_email( $user->user_email );
                        $user_id = (int) $user->ID;
                        $user_meta = get_user_meta( $user->ID );
                        $first_name = $user_meta['first_name'][0] ?? '';
                        $last_name = $user_meta['last_name'][0] ?? '';
                        // Replace placeholder with user content
                        $email_body = $this->replace_placeholder(
                            $email_body,
                            $username,
                            $display_name,
                            $first_name,
                            $last_name,
                            $user_email,
                            $user_id
                        );
                        $email_subject = stripslashes_deep( $subject );
                        $email_title = stripslashes_deep( $title );
                        $email_tagline = stripslashes_deep( $tagline );
                        $email_subject = strip_tags( $this->replace_placeholder(
                            $email_subject,
                            $username,
                            $display_name,
                            $first_name,
                            $last_name,
                            $user_email,
                            $user_id
                        ) );
                        // Send email
                        $input_request = [
                            'title'   => $email_title,
                            'tagline' => $email_tagline,
                        ];
                        $email_template = $this->email_template( $email_body, $email_style, $input_request );
                        $args_send_mail = [
                            'user_id'       => $user_id,
                            'email_style'   => $email_style,
                            'to'            => $user_email,
                            'subject'       => $email_subject,
                            'body'          => $email_template,
                            'headers'       => $headers,
                            'email_title'   => $email_title,
                            'email_tagline' => $email_tagline,
                        ];
                        $sue_override_user_email_subscription = ( isset( $_REQUEST['sue_override_user_email_subscription'] ) ? sanitize_text_field( $_REQUEST['sue_override_user_email_subscription'] ) : 0 );
                        $send_mail = $this->send_email( $sue_override_user_email_subscription, $args_send_mail );
                        if ( !$send_mail ) {
                            $total_failed_email++;
                        } else {
                            sue_log_sent_emails( $user_email, $email_subject, $email_body );
                        }
                        $email_body = '';
                        $email_template = '';
                        $total_email_send++;
                        $options['email_users_total_email_send_' . $current_user_id] = $total_email_send;
                        update_option( 'sue_send_users_email', $options );
                    }
                    // Cleanup email progress record
                    Send_Users_Email_cleanup::cleanupUserEmailProgress();
                    if ( $total_failed_email > 0 ) {
                        $warningMessage = 'Plugin tried to send ' . count( $users ) . ' ' . _n(
                            'email',
                            'emails',
                            count( $users ),
                            'send-users-email'
                        ) . ' but ' . $total_failed_email . ' ' . _n(
                            'email',
                            'emails',
                            $total_failed_email,
                            'send-users-email'
                        ) . ' failed to send. Please check logs for possible errors.';
                    }
                    wp_send_json( array(
                        'message' => $resMessage,
                        'success' => true,
                        'warning' => $warningMessage,
                    ), 200 );
                }
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    /**
     * Handle users email progress
     */
    public function handle_ajax_email_users_progress() {
        if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'send_email_user_progress' && $action == 'sue_email_users_progress' ) {
                $user_id = get_current_user_id();
                $options = get_option( 'sue_send_users_email' );
                $total_email_send = $options['email_users_total_email_send_' . $user_id];
                $total_email_to_send = $options['email_users_total_email_to_send_' . $user_id];
                $progress = ( $total_email_to_send ? floor( $total_email_send / $total_email_to_send * 100 ) : 0 );
                wp_send_json( array(
                    'progress' => $progress,
                ), 200 );
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    /**
     * Handles Ajax request to send user email selecting users
     */
    public function handle_ajax_admin_role_email() {
        if ( check_admin_referer( 'sue-email-user' ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'send_email_role' && $action == 'sue_role_email_ajax' ) {
                $options = get_option( 'sue_send_users_email' );
                $option_title = $options['email_title'] ?? '';
                $option_tagline = $options['email_tagline'] ?? '';
                $subject = ( isset( $_REQUEST['subject'] ) ? sanitize_text_field( $_REQUEST['subject'] ) : "" );
                $title = ( isset( $_REQUEST['title'] ) ? sanitize_text_field( $_REQUEST['title'] ) : $option_title );
                $tagline = ( isset( $_REQUEST['tagline'] ) ? sanitize_text_field( $_REQUEST['tagline'] ) : $option_tagline );
                $message = ( isset( $_REQUEST['sue_user_email_message'] ) ? wp_kses_post( $_REQUEST['sue_user_email_message'] ) : "" );
                $roles = $_REQUEST['roles'] ?? [];
                $roles = array_map( 'sanitize_text_field', $roles );
                $email_style = 'default';
                $message = sue_remove_caption_shortcode( $message );
                $roles_string = implode( ', ', $roles );
                $resMessage = __( '🚀🚀🚀 Email(s) sent successfully!', 'send-users-email' );
                $warningMessage = '';
                // Validate inputs
                $validation_message = [];
                if ( empty( $subject ) || strlen( $subject ) < 2 || strlen( $subject ) > 200 ) {
                    $validation_message['subject'] = __( 'Subject is required and should be between 2 and 200 characters.', "send-users-email" );
                }
                if ( empty( $message ) ) {
                    $validation_message['message'] = __( 'Please provide email content.', "send-users-email" );
                }
                if ( empty( $roles ) ) {
                    $validation_message['sue-role-email-list'] = __( 'Please select role(s).', "send-users-email" );
                }
                // If validation fails send, error messages
                if ( count( $validation_message ) > 0 ) {
                    wp_send_json( array(
                        'errors'  => $validation_message,
                        'success' => false,
                    ), 422 );
                }
                // Cleanup email progress record
                Send_Users_Email_cleanup::cleanupRoleEmailProgress();
                if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
                    $current_user_id = get_current_user_id();
                    $total_email_send = 0;
                    $total_failed_email = 0;
                    $user_details = get_users( array(
                        'fields'   => array(
                            'ID',
                            'display_name',
                            'user_email',
                            'user_login'
                        ),
                        'role__in' => $roles,
                    ) );
                    $total_email_to_send = count( $user_details );
                    $options = get_option( 'sue_send_users_email' );
                    if ( !$options ) {
                        update_option( 'sue_send_users_email', [] );
                    }
                    $options = get_option( 'sue_send_users_email' );
                    $options['email_roles_total_email_send_' . $current_user_id] = $total_email_send;
                    $options['email_roles_total_email_to_send_' . $current_user_id] = $total_email_to_send;
                    update_option( 'sue_send_users_email', $options );
                    // Email header setup
                    $headers = $this->get_email_headers();
                    foreach ( $user_details as $user ) {
                        $email_body = $message;
                        $username = $user->user_login;
                        $display_name = $user->display_name;
                        $user_email = sanitize_email( $user->user_email );
                        $user_id = (int) $user->ID;
                        $user_meta = get_user_meta( $user->ID );
                        $first_name = $user_meta['first_name'][0] ?? '';
                        $last_name = $user_meta['last_name'][0] ?? '';
                        // Replace placeholder with user content
                        $email_body = $this->replace_placeholder(
                            $email_body,
                            $username,
                            $display_name,
                            $first_name,
                            $last_name,
                            $user_email,
                            $user_id
                        );
                        $email_subject = stripslashes_deep( $subject );
                        $email_title = stripslashes_deep( $title );
                        $email_tagline = stripslashes_deep( $tagline );
                        $email_subject = strip_tags( $this->replace_placeholder(
                            $email_subject,
                            $username,
                            $display_name,
                            $first_name,
                            $last_name,
                            $user_email,
                            $user_id
                        ) );
                        // Send email
                        $input_request = [
                            'title'   => $email_title,
                            'tagline' => $email_tagline,
                        ];
                        $email_template = $this->email_template( $email_body, $email_style, $input_request );
                        $args_send_mail = [
                            'user_id'       => $user_id,
                            'email_style'   => $email_style,
                            'to'            => $user_email,
                            'subject'       => $email_subject,
                            'body'          => $email_template,
                            'headers'       => $headers,
                            'email_title'   => $email_title,
                            'email_tagline' => $email_tagline,
                        ];
                        $sue_override_user_email_subscription = ( isset( $_REQUEST['sue_override_user_email_subscription'] ) ? sanitize_text_field( $_REQUEST['sue_override_user_email_subscription'] ) : 0 );
                        $send_mail = $this->send_email( $sue_override_user_email_subscription, $args_send_mail );
                        if ( !$send_mail ) {
                            $total_failed_email++;
                        } else {
                            sue_log_sent_emails( $user_email, $email_subject, $email_body );
                        }
                        $email_body = '';
                        $email_template = '';
                        $total_email_send++;
                        $options['email_roles_total_email_send_' . $current_user_id] = $total_email_send;
                        update_option( 'sue_send_users_email', $options );
                    }
                    // Cleanup email progress record
                    Send_Users_Email_cleanup::cleanupRoleEmailProgress();
                    if ( $total_failed_email > 0 ) {
                        $warningMessage = 'Plugin tried to send ' . $total_email_to_send . ' ' . _n(
                            'email',
                            'emails',
                            $total_email_to_send,
                            'send-users-email'
                        ) . ' but ' . $total_failed_email . ' ' . _n(
                            'email',
                            'emails',
                            $total_failed_email,
                            'send-users-email'
                        ) . ' failed to send. Please check logs for possible errors.';
                    }
                    wp_send_json( array(
                        'message' => $resMessage,
                        'success' => true,
                        'warning' => $warningMessage,
                    ), 200 );
                }
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    /**
     * Handle users email progress
     */
    public function handle_ajax_email_roles_progress() {
        if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'send_email_role_progress' && $action == 'sue_email_roles_progress' ) {
                $user_id = get_current_user_id();
                $options = get_option( 'sue_send_users_email' );
                $total_email_send = $options['email_roles_total_email_send_' . $user_id];
                $total_email_to_send = $options['email_roles_total_email_to_send_' . $user_id];
                $progress = ( $total_email_to_send ? floor( $total_email_send / $total_email_to_send * 100 ) : 0 );
                wp_send_json( array(
                    'progress' => $progress,
                ), 200 );
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    /**
     * Email template
     */
    private function email_template( $email_body, $style = 'default', $input_request = [] ) {
        ob_start();
        /**
         * Allan
         * I added this input request to allow users to pass their own title and tagline.
         */
        $user_input_request = $input_request;
        $request_title = ( isset( $input_request['title'] ) ? $input_request['title'] : '' );
        $request_tagline = ( isset( $input_request['tagline'] ) ? $input_request['tagline'] : '' );
        $options = get_option( 'sue_send_users_email' );
        $logo = $options['logo_url'] ?? '';
        $title = $request_title ?? $options['email_title'];
        $tagline = $request_tagline ?? $options['email_tagline'];
        $footer = $options['email_footer'] ?? '';
        $styles = $options['email_template_style'] ?? '';
        $social = $options['social'] ?? [];
        if ( !$style ) {
            $style = 'default';
        }
        require 'partials/email-template.php';
        $output = ob_get_contents();
        ob_end_clean();
        /*
        		if ( sue_fs()->is__premium_only() ) {
        			if ( sue_fs()->can_use_premium_code() ) {
        
        blue   : #142467 / #042467 -> This is default scheme
        red    : #D64123 / #C64123
        green  : #04AA6D / #03AA6D
        purple : #692c91 / #592c91
        pink   : #ee3e80 / #de3e80
        yellow : #ffbd00 / #efbd00
        */
        /*
        				switch ( $style ) {
        					case "green":
        						$output = str_replace( '#042467', '#03AA6D', $output );
        						$output = str_replace( '#142467', '#04AA6D', $output );
        						break;
        					case "pink":
        						$output = str_replace( '#042467', '#de3e80', $output );
        						$output = str_replace( '#142467', '#ee3e80', $output );
        						break;
        					case "purple":
        						$output = str_replace( '#042467', '#592c91', $output );
        						$output = str_replace( '#142467', '#692c91', $output );
        						break;
        					case "red":
        						$output = str_replace( '#042467', '#C64123', $output );
        						$output = str_replace( '#142467', '#D64123', $output );
        						break;
        					case "yellow":
        						$output = str_replace( '#042467', '#efbd00', $output );
        						$output = str_replace( '#142467', '#ffbd00', $output );
        						break;
        					default:
        						$output = str_replace( '#042467', '#042467', $output );
        						$output = str_replace( '#142467', '#142467', $output );
        				}
        			}
        		}
        */
        return $output;
    }

    /**
     * Plugin settings
     */
    public function handle_ajax_admin_settings() {
        if ( check_admin_referer( 'sue-email-user' ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'sue_settings' && $action == 'sue_settings_ajax' ) {
                $logo = ( isset( $_REQUEST['logo'] ) ? esc_url_raw( $_REQUEST['logo'] ) : "" );
                $title = ( isset( $_REQUEST['title'] ) ? sanitize_text_field( $_REQUEST['title'] ) : "" );
                $tagline = ( isset( $_REQUEST['tagline'] ) ? sanitize_text_field( $_REQUEST['tagline'] ) : "" );
                $footer = ( isset( $_REQUEST['footer'] ) ? wp_kses_post( $_REQUEST['footer'] ) : "" );
                $email_from_name = ( isset( $_REQUEST['email_from_name'] ) ? sanitize_text_field( $_REQUEST['email_from_name'] ) : "" );
                $email_from_address = ( isset( $_REQUEST['email_from_address'] ) ? sanitize_text_field( $_REQUEST['email_from_address'] ) : "" );
                $reply_to_address = ( isset( $_REQUEST['reply_to_address'] ) ? sanitize_text_field( $_REQUEST['reply_to_address'] ) : "" );
                $email_template_style = ( isset( $_REQUEST['email_template_style'] ) ? sanitize_text_field( $_REQUEST['email_template_style'] ) : "" );
                $selected_roles = $_REQUEST['email_send_roles'] ?? [];
                $socials = $_REQUEST['social'] ?? [];
                // Validate inputs
                $validation_message = [];
                if ( !empty( $logo ) && !wp_http_validate_url( $logo ) ) {
                    $validation_message['logo'] = __( 'Please provide valid image URL..', "send-users-email" );
                }
                if ( !empty( $title ) && strlen( $title ) <= 2 ) {
                    $validation_message['title'] = __( 'Please provide a bit more title.', "send-users-email" );
                }
                if ( !empty( $tagline ) && strlen( $tagline ) <= 4 ) {
                    $validation_message['tagline'] = __( 'Please provide a bit more tagline.', "send-users-email" );
                }
                if ( !empty( $footer ) && strlen( $footer ) <= 4 ) {
                    $validation_message['footer'] = __( 'Please provide a bit more footer content.', "send-users-email" );
                }
                if ( !empty( $email_from_name ) && strlen( $email_from_name ) <= 2 ) {
                    $validation_message['email_from_name'] = __( 'Please provide a bit more email from Name.', "send-users-email" );
                }
                if ( !empty( $email_from_address ) && !filter_var( $email_from_address, FILTER_VALIDATE_EMAIL ) ) {
                    $validation_message['email_from_address'] = __( 'Please provide a valid email from address.', "send-users-email" );
                }
                if ( !empty( $reply_to_address ) && !filter_var( $reply_to_address, FILTER_VALIDATE_EMAIL ) ) {
                    $validation_message['reply_to_address'] = __( 'Please provide a valid reply to address.', "send-users-email" );
                }
                if ( !empty( $email_outgoing_rate ) && !is_integer( $email_outgoing_rate ) && $email_outgoing_rate < 1 ) {
                    $validation_message['email_outgoing_rate'] = __( 'Please provide a valid positive number.', "send-users-email" );
                }
                if ( !empty( $sent_email_save_for ) && !is_integer( $sent_email_save_for ) && $sent_email_save_for < 0 ) {
                    $validation_message['sent_email_save_for'] = __( 'Please provide a valid number. Can be zero or greater.', "send-users-email" );
                }
                if ( !empty( $email_from_address ) && empty( $email_from_name ) ) {
                    $validation_message['email_from_name'] = __( 'Please provide a valid email from name.', "send-users-email" );
                }
                if ( !empty( $reply_to_address ) && empty( $email_from_name ) ) {
                    $validation_message['email_from_name'] = __( 'Please provide a valid email from name.', "send-users-email" );
                }
                if ( !empty( $save_email_log_till_days ) && $save_email_log_till_days < 1 ) {
                    $validation_message['save_email_log_till_days'] = __( 'Please provide a valid positive number.', "send-users-email" );
                }
                if ( !empty( $save_smtp_host ) && strlen( $save_smtp_host ) < 3 ) {
                    $validation_message['save_smtp_host'] = __( 'Please provide a valid SMTP host.', "send-users-email" );
                }
                if ( !empty( $save_smtp_port ) && !is_integer( $save_smtp_port ) && $save_smtp_port < 0 ) {
                    $validation_message['save_smtp_port'] = __( 'Please provide a valid SMTP port number.', "send-users-email" );
                }
                if ( !empty( $save_smtp_username ) && strlen( $save_smtp_username ) < 2 ) {
                    $validation_message['save_smtp_username'] = __( 'Please provide a valid SMTP username.', "send-users-email" );
                }
                if ( !empty( $save_smtp_password ) && strlen( $save_smtp_password ) < 2 ) {
                    $validation_message['save_smtp_password'] = __( 'Please provide a valid SMTP password.', "send-users-email" );
                }
                // If validation fails send, error messages
                if ( count( $validation_message ) > 0 ) {
                    wp_send_json( array(
                        'errors'  => $validation_message,
                        'success' => false,
                    ), 422 );
                }
                if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
                    $options = get_option( 'sue_send_users_email' );
                    if ( !$options ) {
                        update_option( 'sue_send_users_email', [] );
                    }
                    $options = get_option( 'sue_send_users_email' );
                    $options['logo_url'] = esc_url_raw( $logo );
                    $options['email_title'] = stripslashes_deep( wp_strip_all_tags( $title ) );
                    $options['email_tagline'] = stripslashes_deep( wp_strip_all_tags( $tagline ) );
                    $options['email_footer'] = stripslashes_deep( $footer );
                    $options['email_from_name'] = stripslashes_deep( wp_strip_all_tags( $email_from_name ) );
                    $options['email_from_address'] = stripslashes_deep( wp_strip_all_tags( $email_from_address ) );
                    $options['reply_to_address'] = stripslashes_deep( wp_strip_all_tags( $reply_to_address ) );
                    $options['email_template_style'] = stripslashes_deep( wp_strip_all_tags( $email_template_style ) );
                    // Roles array adjustments
                    $roles = '';
                    foreach ( $selected_roles as $selected_role ) {
                        $roles .= wp_strip_all_tags( $selected_role ) . ',';
                    }
                    $roles = rtrim( $roles, ',' );
                    // Social media links
                    $socialMedias = [];
                    foreach ( $socials as $platform => $url ) {
                        $platform = sanitize_text_field( $platform );
                        $url = sanitize_text_field( $url );
                        if ( !empty( $url ) ) {
                            $socialMedias[$platform] = $url;
                        }
                    }
                    $options['email_send_roles'] = $roles;
                    $options['social'] = $socialMedias;
                    update_option( 'sue_send_users_email', $options );
                    // Add or remove email capacity of role
                    sue_add_email_capability_to_roles( $roles );
                    wp_send_json( array(
                        'message' => 'success',
                        'success' => true,
                    ), 200 );
                }
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    /**
     * Replace placeholder text to content
     */
    private function replace_placeholder(
        $email_body,
        $username,
        $display_name,
        $first_name,
        $last_name,
        $user_email,
        $user_id
    ) {
        $email_body = str_replace( '{{username}}', $username, $email_body );
        $email_body = str_replace( '{{user_display_name}}', $display_name, $email_body );
        $email_body = str_replace( '{{user_first_name}}', $first_name, $email_body );
        $email_body = str_replace( '{{user_last_name}}', $last_name, $email_body );
        $email_body = str_replace( '{{user_email}}', $user_email, $email_body );
        $email_body = str_replace( '{{user_id}}', $user_id, $email_body );
        return wpautop( $email_body );
    }

    /**
     * @return array
     */
    private function get_email_headers() {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $options = get_option( 'sue_send_users_email' );
        $email_from_name = $options['email_from_name'] ?? '';
        $email_from_address = $options['email_from_address'] ?? '';
        $reply_to_address = $options['reply_to_address'] ?? '';
        if ( !empty( $email_from_name ) && !empty( $email_from_address ) ) {
            $headers[] = "From: {$email_from_name} <{$email_from_address}>";
        }
        if ( !empty( $email_from_name ) && !empty( $reply_to_address ) ) {
            $headers[] = "Reply-To: {$email_from_name} <{$reply_to_address}>";
        }
        return $headers;
    }

    /**
     * Assign capability to send email to admin users automatically if it is not there
     */
    public function check_administrator_capability() {
        global $current_user;
        $user_roles = $current_user->roles;
        if ( in_array( 'administrator', $user_roles ) ) {
            if ( !current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
                $role = get_role( 'administrator' );
                if ( $role ) {
                    $role->add_cap( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY );
                }
            }
        }
        // Temporarily cleanup script for user progress bug
        if ( get_option( 'sue_db_version' ) ) {
            if ( get_option( 'sue_db_version' ) != SEND_USERS_EMAIL_VERSION ) {
                $options = get_option( 'sue_send_users_email' );
                $optionsKeys = [
                    'email_users_email_send_start_',
                    'email_users_total_email_send_',
                    'email_users_total_email_to_send_',
                    'email_roles_email_send_start_',
                    'email_roles_total_email_send_',
                    'email_roles_total_email_to_send_'
                ];
                if ( is_array( $options ) ) {
                    foreach ( $options as $key => $value ) {
                        foreach ( $optionsKeys as $options_key ) {
                            if ( strpos( $key, $options_key ) === 0 ) {
                                unset($options[$key]);
                            }
                        }
                    }
                    update_option( 'sue_send_users_email', $options );
                }
            }
        }
    }

    /**
     * @param $wp_error
     *  Log wp_mail error to file
     *
     * @return void
     */
    public function handle_wp_mail_failed_action( $wp_error ) {
        $smtp_error = null;
        $to = null;
        $subject = null;
        if ( !empty( $wp_error ) ) {
            $errors = $wp_error->errors ?? null;
            if ( $errors ) {
                $wp_mail_failed = $errors['wp_mail_failed'] ?? null;
                $smtp_error = $wp_mail_failed[0] ?? null;
            }
            $error_data = $wp_error->error_data ?? null;
            if ( $error_data ) {
                $smtp_wp_mail_failed = $error_data['wp_mail_failed'] ?? null;
                if ( $smtp_wp_mail_failed ) {
                    $toArr = $smtp_wp_mail_failed['to'] ?? null;
                    if ( is_array( $toArr ) ) {
                        $to = implode( ', ', $toArr );
                    }
                    $subject = $smtp_wp_mail_failed['subject'] ?? null;
                }
            }
            $message = '[' . date( 'Y-m-d h:i:s' ) . ']: ';
            $message .= 'ERROR';
            if ( $smtp_error ) {
                $message .= ' | ' . $smtp_error;
            }
            if ( $to ) {
                $message .= ' | To: ' . sue_obscure_text( $to );
            }
            if ( $subject ) {
                $message .= ' | Subject: ' . strip_tags( $subject );
            }
            sue_log_wp_mail_failed_error( $message );
        }
    }

    /**
     *  Delete error log file
     * @return void
     */
    public function delete_error_log() {
        if ( isset( $_POST['sue_delete_error_log'] ) ) {
            if ( check_admin_referer( 'sue-delete-error-log' ) ) {
                Send_Users_Email_cleanup::cleanErrorLogFile();
            }
        }
    }

    /**
     *  Truncate emails table clearing out all sent and unsent emails
     * @return void
     */
    public function delete_all_queued_emails() {
        if ( isset( $_POST['sue_delete_all_queued_emails'] ) ) {
            if ( check_admin_referer( 'sue-delete-all-queued-emails' ) ) {
                SUE_Emails::truncateQueueTable();
            }
        }
    }

    /**
     *  View email log data
     * @return void
     */
    public function handle_ajax_view_email_log() {
        if ( check_admin_referer( 'sue-email-log-view' ) ) {
            $param = ( isset( $_REQUEST['param'] ) ? sanitize_text_field( $_REQUEST['param'] ) : "" );
            $action = ( isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : "" );
            if ( $param == 'sue_view_email_log' && $action == 'sue_view_email_log_ajax' ) {
                $filename = ( isset( $_REQUEST['sue_view_email_log_file'] ) ? sanitize_text_field( $_REQUEST['sue_view_email_log_file'] ) : "" );
                $filename = sanitize_file_name( $filename );
                if ( current_user_can( SEND_USERS_EMAIL_SEND_MAIL_CAPABILITY ) ) {
                    $emailLog = file_get_contents( sue_log_path( $filename ) );
                    $emailLogSize = sue_bytes_to_mb( filesize( sue_log_path( $filename ) ) );
                    wp_send_json( array(
                        'message'  => $emailLog,
                        'filesize' => (float) $emailLogSize,
                        'success'  => true,
                    ), 200 );
                }
            }
        }
        wp_send_json( array(
            'message' => 'Permission Denied',
            'success' => false,
        ), 200 );
    }

    public function deliver_email_via_smtp( $phpmailer ) {
        // Todo:
        // - if pw field is empty and no new pw is set, make sure it wont get nulled at saving
        // - make sure this function also works on a localhost
        $options = get_option( 'sue_send_users_email' );
        $smtp_host = $options['save_smtp_host'];
        $smtp_port = $options['save_smtp_port'];
        $smtp_security = $options['save_smtp_security'];
        $smtp_security = ( $smtp_security === 'none' ? '' : $smtp_security );
        $smtp_username = $options['save_smtp_username'];
        $smtp_password = $options['save_smtp_password'];
        $smtp_default_from_name = $options['email_from_name'];
        $smtp_default_from_email = $options['email_from_address'];
        $smtp_bypass_ssl_verification = $options['save_smtp_bypass_ssl'];
        $smtp_force_from = true;
        // $options['smtp_force_from'];
        // $smtp_debug = $options['smtp_debug'];
        // Return if fields are missing
        if ( empty( $smtp_host ) || empty( $smtp_port ) || empty( $smtp_security ) || empty( $smtp_username ) || empty( $smtp_password ) ) {
            return;
        }
        // Maybe override FROM email and/or name if the sender is "WordPress <wordpress@sitedomain.com>", the default from WordPress core and not yet overridden by another plugin.
        $from_name = $phpmailer->FromName;
        $from_email_beginning = substr( $phpmailer->From, 0, 9 );
        // Replace From and FromName
        if ( $smtp_force_from ) {
            $phpmailer->FromName = $smtp_default_from_name;
            $phpmailer->From = $smtp_default_from_email;
        }
        // Send using SMTP
        $phpmailer->isSMTP();
        $phpmailer->SMTPAuth = true;
        // $phpmailer->CharSet  = 'utf-8';
        $phpmailer->XMailer = 'Send Users Email v' . $this->version . ' - a WordPress plugin';
        $phpmailer->Host = $smtp_host;
        $phpmailer->Port = $smtp_port;
        $phpmailer->SMTPSecure = $smtp_security;
        $phpmailer->Username = trim( $smtp_username );
        $phpmailer->Password = trim( $smtp_password );
        // If verification of SSL certificate is bypassed
        // Reference: https://www.php.net/manual/en/context.ssl.php & https://stackoverflow.com/a/30803024
        if ( $smtp_bypass_ssl_verification ) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }
        // If debug mode is enabled, send debug info (SMTP::DEBUG_CONNECTION) to WordPress debug.log file set in wp-config.php
        // Reference: https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
        /*
                if (true) {
                    $phpmailer->SMTPDebug = 4;
                    //phpcs:ignore
                    $phpmailer->Debugoutput = 'error_log';
                    //phpcs:ignore
                }
        */
    }

    /**
     * Summary of send_email
     * This will send email using wp_mail function.
     * It can be override using the hook sue_send_using_wp_mail_{$email_style}
     * It can use different mail service, use hook sue_process_sue_send_using_email_service_{$email_style}
     * @param mixed $sue_data
     * @param mixed $input_requests
     * @return bool
     */
    private function send_email( $sue_override_user_email_subscription, $sue_data = [] ) {
        $user_id = $sue_data['user_id'];
        $email_style = $sue_data['email_style'];
        $to = $sue_data['to'];
        $subject = $sue_data['subject'];
        $body = $sue_data['body'];
        $headers = $sue_data['headers'];
        $attachments = $sue_data['attachments'] ?? [];
        // this is for the free
        $send_email = true;
        // does the system will send even the unsubscribed users?
        if ( sue_is_premium_and_can_use_premium_code() ) {
            $send_email = SUE_Email_Subscription_Override::override( $user_id, $sue_override_user_email_subscription );
        }
        /**
         * Use this hook to by-pass wp_mail function.
         * Always return true.
         * @see SUE_Woo_Email_Template::init_hook
         * @var mixed
         */
        $send_using_wp_mail = apply_filters( 'sue_send_using_wp_mail_' . $email_style, '__return_true' );
        /**
         * If ok to send email and user is subscribed then send.
         * And if no hook returned false then send mail.
         */
        if ( $send_email && $send_using_wp_mail ) {
            return wp_mail(
                $to,
                $subject,
                $body,
                $headers,
                $attachments
            );
        }
        /**
         * Hook Use for custom mail service.
         */
        do_action( 'sue_process_sue_send_using_email_service_' . $email_style, $send_email, $sue_data );
        /**
         * If the user is unsubscribed, then return true to bypass.
         */
        if ( !sue_is_user_email_subscribed( $user_id ) || !$send_using_wp_mail ) {
            return true;
        }
    }

}
