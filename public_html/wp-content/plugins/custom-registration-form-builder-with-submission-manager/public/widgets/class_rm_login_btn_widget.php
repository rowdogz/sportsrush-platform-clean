<?php

/**
 * Adds Login Button Widget
 */
class RM_Login_Btn_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            'rm_login_btn_widget', // Base ID
            __( 'RegistrationMagic Login Button', 'custom-registration-form-builder-with-submission-manager' ), // Name
            array(
                'description' => __( 'Login Button', 'custom-registration-form-builder-with-submission-manager' ),
            )
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo wp_kses_post( (string) $args['before_widget'] );
        // These vars can then be used in the included file
        include RM_PUBLIC_DIR . 'widgets/html/login_btn.php';

        echo wp_kses_post( (string) $args['after_widget'] );
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {

        wp_enqueue_script(
            'rm_login_btn_widget',
            RM_BASE_URL . 'public/js/login_btn_widget.js',
            array( 'jquery' )
        );

        $title        = ! empty( $instance['title'] ) ? $instance['title'] : __( 'RegistrationMagic Login Button', 'custom-registration-form-builder-with-submission-manager' );
        $login_label  = isset( $instance['login_label'] ) ? $instance['login_label'] : __( 'Login', 'custom-registration-form-builder-with-submission-manager' );
        $login_method = isset( $instance['login_method'] ) ? $instance['login_method'] : 'popup';
        $login_url    = isset( $instance['login_url'] ) ? (int) $instance['login_url'] : 0;
        $logout_label = isset( $instance['logout_label'] ) ? $instance['logout_label'] : __( 'Logout', 'custom-registration-form-builder-with-submission-manager' );
        $display_card = isset( $instance['display_card'] ) ? (int) $instance['display_card'] : 1;

        $field_id_title       = $this->get_field_id( 'title' );
        $field_name_title     = $this->get_field_name( 'title' );
        $field_id_login_label = $this->get_field_id( 'login_label' );
        $field_name_login_lbl = $this->get_field_name( 'login_label' );
        $field_name_method    = $this->get_field_name( 'login_method' );
        $field_name_login_url = $this->get_field_name( 'login_url' );
        $field_id_logout_lbl  = $this->get_field_id( 'logout_label' );
        $field_name_logout_lbl = $this->get_field_name( 'logout_label' );
        $field_id_display_card = $this->get_field_id( 'display_card' );
        $field_name_display_card = $this->get_field_name( 'display_card' );

        $pages = RM_Utilities::wp_pages_dropdown();
        ?>

        <p>
            <label for="<?php echo esc_attr( $field_id_title ); ?>">
                <?php esc_html_e( 'Title:', 'custom-registration-form-builder-with-submission-manager' ); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr( $field_id_title ); ?>"
                name="<?php echo esc_attr( $field_name_title ); ?>"
                type="text"
                value="<?php echo esc_attr( $title ); ?>"
            />
        </p>

        <div>
            <div class="rm-logged-out-view">
                <div>
                    <h3><?php esc_html_e( 'Logged Out View', 'custom-registration-form-builder-with-submission-manager' ); ?></h3>
                </div>

                <p>
                    <label for="<?php echo esc_attr( $field_id_login_label ); ?>">
                        <?php esc_html_e( 'Login Label', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                    <input
                        type="text"
                        name="<?php echo esc_attr( $field_name_login_lbl ); ?>"
                        id="<?php echo esc_attr( $field_id_login_label ); ?>"
                        value="<?php echo esc_attr( $login_label ); ?>"
                        class="widefat"
                    />
                    <span class="rm-widget-helptext">
                        <?php esc_html_e( 'Label of the button when user is in logged out state.', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </span>
                </p>

                <p>
                    <label for="rm_login_method_popup" class="rm-widget-label-fw">
                        <?php esc_html_e( 'Login Method', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                    <br/>

                    <input
                        class="rm_login_method"
                        onchange="rmw_login_method_change(this)"
                        type="radio"
                        id="rm_login_method_popup"
                        name="<?php echo esc_attr( $field_name_method ); ?>"
                        value="popup"
                        <?php checked( $login_method, 'popup' ); ?>
                    />
                    <label for="rm_login_method_popup">
                        <?php esc_html_e( 'Popup', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>

                    <br/>

                    <input
                        class="rm_login_method"
                        onchange="rmw_login_method_change(this)"
                        type="radio"
                        id="rm_login_method_url"
                        name="<?php echo esc_attr( $field_name_method ); ?>"
                        value="url"
                        <?php checked( $login_method, 'url' ); ?>
                    />
                    <label for="rm_login_method_url">
                        <?php esc_html_e( 'URL', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                </p>

                <p
                    id="<?php echo esc_attr( $this->get_field_id( 'url_info' ) ); ?>"
                    style="<?php echo ( 'url' === $login_method ) ? 'display:none;' : 'display:block;'; ?>"
                >
                    <span class="rm-widget-helptext">
                        <?php esc_html_e( 'Define what happens when user clicks login button. Popup will open popup box with login fields.', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </span>
                </p>

                <p
                    id="<?php echo esc_attr( $this->get_field_id( 'url_select' ) ); ?>"
                    style="<?php echo ( 'url' !== $login_method ) ? 'display:none;' : 'display:block;'; ?>"
                >
                    <label for="<?php echo esc_attr( $this->get_field_id( 'login_url_select' ) ); ?>">
                        <?php esc_html_e( 'Login Page URL', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                    <select
                        name="<?php echo esc_attr( $field_name_login_url ); ?>"
                        id="<?php echo esc_attr( $this->get_field_id( 'login_url_select' ) ); ?>"
                        class="widefat"
                    >
                        <?php foreach ( $pages as $index => $page ) : ?>
                            <option
                                value="<?php echo esc_attr( $index ); ?>"
                                <?php selected( (int) $login_url, (int) $index ); ?>
                            >
                                <?php echo esc_html( $page ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="rm-widget-helptext">
                        <?php esc_html_e( 'Make sure the page you selected has login box.', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </span>
                </p>
            </div>

            <div class="rm-logged-in-view">
                <div>
                    <h3><?php esc_html_e( 'Logged In View', 'custom-registration-form-builder-with-submission-manager' ); ?></h3>
                </div>

                <p>
                    <label for="<?php echo esc_attr( $field_id_logout_lbl ); ?>">
                        <?php esc_html_e( 'Logout Label', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                    <input
                        type="text"
                        name="<?php echo esc_attr( $field_name_logout_lbl ); ?>"
                        id="<?php echo esc_attr( $field_id_logout_lbl ); ?>"
                        value="<?php echo esc_attr( $logout_label ); ?>"
                        class="widefat"
                    />
                    <span class="rm-widget-helptext">
                        <?php esc_html_e( 'Label of the button when user is in logged in state.', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </span>
                </p>

                <p>
                    <label for="<?php echo esc_attr( $field_id_display_card ); ?>">
                        <?php esc_html_e( 'Display User card on hover', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </label>
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr( $field_id_display_card ); ?>"
                        value="1"
                        name="<?php echo esc_attr( $field_name_display_card ); ?>"
                        <?php checked( $display_card, 1 ); ?>
                    />
                    <span class="rm-widget-helptext">
                        <?php esc_html_e( 'Displays user information card when user hovers cursor above the button.', 'custom-registration-form-builder-with-submission-manager' ); ?>
                    </span>
                </p>
            </div>
        </div>

        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();

        $instance['title'] = isset( $new_instance['title'] )
            ? sanitize_text_field( $new_instance['title'] )
            : '';

        $instance['login_label'] = isset( $new_instance['login_label'] )
            ? sanitize_text_field( $new_instance['login_label'] )
            : __( 'Login', 'custom-registration-form-builder-with-submission-manager' );

        // Ensure only popup or url
        $login_method = isset( $new_instance['login_method'] ) ? $new_instance['login_method'] : 'popup';
        $instance['login_method'] = in_array( $login_method, array( 'popup', 'url' ), true )
            ? $login_method
            : 'popup';

        // IMPORTANT: use isset, not empty, so "0" (first page) is saved correctly
        $instance['login_url'] = isset( $new_instance['login_url'] )
            ? (int) $new_instance['login_url']
            : 0;

        $instance['logout_label'] = isset( $new_instance['logout_label'] )
            ? sanitize_text_field( $new_instance['logout_label'] )
            : __( 'Logout', 'custom-registration-form-builder-with-submission-manager' );

        // Checkbox
        $instance['display_card'] = ! empty( $new_instance['display_card'] ) ? 1 : 0;

        return $instance;
    }
}
