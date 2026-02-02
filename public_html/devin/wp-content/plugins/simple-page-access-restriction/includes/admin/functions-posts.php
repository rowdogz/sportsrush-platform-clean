<?php
/**
 * Admin - Functions - Posts.
 *
 * @package Simple_Page_Access_Restriction.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the inline edit form.
 *
 * @param array $args The arguments.
 */
function ps_simple_par_admin_render_inline_edit( $args ) {
	?>
	<fieldset class="inline-edit-col ps-simple-par-inline-edit" id="ps-simple-par-<?php echo esc_attr( str_replace( '_', '-', $args['edit_action'] ) ); ?>" style="display: none;">
		<legend class="inline-edit-legend"><?php esc_html_e( 'Simple Page Access Restriction', 'simple-page-access-restriction' ); ?></legend>
		<div class="inline-edit-col ps-simple-par-fields">
			<input name="ps_simple_par_nonce_<?php echo esc_attr( $args['edit_action'] ); ?>" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'ps-simple-par-nonce' ) ); ?>">
			<input name="ps_simple_par_action" type="hidden" value="<?php echo esc_attr( str_replace( '_', '-', $args['edit_action'] ) ); ?>">
			<div class="ps-simple-par-field">
				<label class="alignleft ps-simple-par-label">
					<span><?php esc_html_e( 'Access Restriction', 'simple-page-access-restriction' ); ?></span>
					<br />
					<select name="ps_simple_par_fields[restricted]">
						<option value=""><?php printf( '&mdash; %s &mdash;', esc_html__( 'No Change', 'simple-page-access-restriction' ) ); ?></option>
						<option value="users"><?php esc_html_e( 'Logged-In Users Only', 'simple-page-access-restriction' ); ?></option>
						<option value="all"><?php esc_html_e( 'No Restrictions', 'simple-page-access-restriction' ); ?></option>
					</select>
				</label>
			</div>
		</div>
	</fieldset>
	<?php
}

/**
 * Render the bulk edit form.
 */
function ps_simple_par_admin_render_bulk_edit() {
	// Check the current page.
	if ( ! ps_simple_par_admin_is_page( 'any', 'restrictable-list-table' ) ) {
		return;
	}

	// Set the args.
	$args = array(
		'edit_action' => 'bulk_edit',
	);

	// Render the HTML.
	ps_simple_par_admin_render_inline_edit( $args );
}
add_action( 'admin_footer', 'ps_simple_par_admin_render_bulk_edit', 100, 2 );

/**
 * Save the inline edit data.
 *
 * @param array $args The arguments.
 */
function ps_simple_par_admin_save_inline_edit( $args ) {
	// Check the post id.
	if ( empty( $args['post_id'] ) ) {
		return;
	}

	// Get the post type.
	$post_type = get_post_type( $args['post_id'] );

	// Check the post type.
	if ( empty( $post_type ) ) {
		return;
	}

	// Get the settings.
	$settings = ps_simple_par_get_settings();

	// Get the post types.
	$post_types = $settings['post_types'];

	// Check the post type.
	if ( ! in_array( $post_type, $post_types, true ) ) {
		return;
	}

	// Check if the current user can't edit the post.
	if ( ! current_user_can( 'edit_post', $args['post_id'] ) ) {
		return;
	}

	// Check if the WordPress is doing autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Set the meta key.
	$meta_key = 'page_access_restricted';

	// Check the restricted.
	if ( 'users' === $args['restricted'] ) {
		// Update the meta key.
		update_post_meta( $args['post_id'], $meta_key, 1 );

		// Otherwise...
	} else {
		// Delete the meta key.
		delete_post_meta( $args['post_id'], $meta_key );
	}
}

/**
 * Save the bulk edit data.
 *
 * @param int $post_id The post id.
 */
function ps_simple_par_admin_save_bulk_edit( $post_id ) {
	// Check the action.
	if ( ! isset( $_GET['action'] ) || 'edit' !== $_GET['action'] ) {
		return;
	}

	// Set the nonce key.
	$nonce_key = 'ps_simple_par_nonce_bulk_edit';

	// Check if the nonce is not set.
	if ( ! isset( $_GET[ $nonce_key ] ) ) {
		return;
	}

	// Get the nonce.
	$nonce = sanitize_text_field( wp_unslash( $_GET[ $nonce_key ] ) );

	// Check if the nonce is invalid.
	if ( false === wp_verify_nonce( $nonce, 'ps-simple-par-nonce' ) ) {
		return;
	}

	// Check if the specific action is not defined.
	if ( ! isset( $_GET['ps_simple_par_action'] ) ) {
		return;
	}

	// Check if the specific action is not valid.
	if ( 'bulk-edit' !== $_GET['ps_simple_par_action'] ) {
		return;
	}

	// Check if the fields is not set.
	if ( ! isset( $_GET['ps_simple_par_fields'] ) ) {
		return;
	}

	// Get the fields.
	$fields = is_array( $_GET['ps_simple_par_fields'] ) ? map_deep( wp_unslash( $_GET['ps_simple_par_fields'] ), 'sanitize_text_field' ) : array();

	// Get the restricted.
	$restricted = isset( $fields['restricted'] ) && in_array( $fields['restricted'], array( 'all', 'users' ), true ) ? $fields['restricted'] : '';

	// Check the restricted.
	if ( empty( $restricted ) ) {
		return;
	}

	// Set the args.
	$args = array(
		'post_id'    => $post_id,
		'restricted' => $restricted,
	);

	// Save inline edit.
	ps_simple_par_admin_save_inline_edit( $args );
}
add_action( 'save_post', 'ps_simple_par_admin_save_bulk_edit' );
