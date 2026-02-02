<?php

/**
 * Custom HTML Template Class
 *
 * This class handles the custom HTML template functionality for the Send Users Email plugin.
 * It registers and enqueues CodeMirror for HTML editing in the WordPress admin area.
 *
 * @package Send_Users_Email
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Summary of SUE_Custom_Html_Template
 * Handles the custom HTML template functionality for the Send Users Email plugin.
 * Registers and enqueues CodeMirror for HTML editing in the WordPress admin area.
 * */
class SUE_Custom_Html_Template
{
    /**
     * Summary of parse_template
     * Parses the custom HTML template with provided email content.
     * This method replaces placeholders in the custom HTML template with actual email content.
     * 
     * @param mixed $email_title
     * @param mixed $email_tagline
     * @param mixed $email_body
     * @param mixed $email_logo
     * @return string
     */
    static public function parse_template($email_title, $email_tagline, $email_body, $email_logo = '' )
    {
        $content = SUE_Custom_Html_Template_Model::get_formatted_custom_html_template();

        $str_replace = [
            '{{email_title}}'   => $email_title,
            '{{email_tagline}}' => $email_tagline,
            '{{email_content}}' => $email_body,
            '{{email_logo}}'   => $email_logo,
        ];

        $email_body = str_replace(
            array_keys( $str_replace ), 
            array_values( $str_replace ),
            $content
        );

        return $email_body;
    }
    static public function ajax_form_update()
    {
        // Handle AJAX requests related to custom HTML templates
        // This method can be extended to handle specific AJAX actions if needed
        $arr_response = [
            'status' => 'error',
            'message' => esc_html__('An error occurred while processing your request.', 'send-users-email'),
        ];

        $update = SUE_Custom_Html_Template_Model::form_process($_POST);

        if ($update) {
            $arr_response['status'] = 'success';
            $arr_response['message'] = esc_html__('Custom HTML template updated successfully.', 'send-users-email');
        } else {
            $arr_response['message'] = esc_html__('Failed to update custom HTML template.', 'send-users-email');
        }

        wp_send_json($arr_response);
        wp_die(); // Always call wp_die() to properly terminate the AJAX request
    }

    /**
     * Register CodeMirror assets for HTML editing.
     * This method registers the necessary scripts and styles for CodeMirror.
     */
    static public function register(): void
    {
        // Register CodeMirror assets (WordPress core handles dependencies)
        wp_register_script('wp-codemirror', false, [], false, true);
        wp_register_style('wp-codemirror', false, [], false);
        
    }

    /**
     * Enqueue CodeMirror assets for HTML editing.
     * This method enqueues the CodeMirror editor and styles for use in the admin area.
     */
    static public function enqueue(): void
    {
        // Enqueue CodeMirror editor for HTML
        wp_enqueue_code_editor(['type' => 'text/html']);
        wp_enqueue_script('wp-codemirror');
        wp_enqueue_style('wp-codemirror');

        wp_enqueue_script('sue-admin-custom-html-template-js');
        wp_enqueue_style('sue-admin-custom-html-template-css');
    }

    /**
     * Render the custom HTML template editor.
     * This method outputs the HTML for the custom HTML template editor.
     *
     * @param array $args Optional arguments for rendering the template.
     */
    static public function render( $args = [] ): void 
    {
        $defaults = [
            'title' => esc_html__('Custom HTML Template', 'send-users-email'),
            'data' => SUE_Custom_Html_Template_Model::get_formatted_custom_html_template(),
            'is_default' => SUE_Custom_Html_Template_Model::is_default_custom_html_template(),
        ];

        $args = wp_parse_args( $args, $defaults );

        require_once SEND_USERS_EMAIL_PLUGIN_BASE_PATH . '/admin/partials/custom-html-template.php';
    }

}