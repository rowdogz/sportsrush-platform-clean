<?php
/*
Plugin Name: SportsRush Stadium Manager
Description: Admin UI to upload/replace/clear stadium photos for pool_wpkl_stadiums and save them to the DB.
Version: 1.0
Author: SportsRush
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Stadiums',
        'Stadiums',
        'manage_options',
        'sr-stadiums',
        'sr_render_stadiums_list',
        'dashicons-location-alt',
        58
    );
    add_submenu_page(
        'sr-stadiums',
        'Edit Stadium',
        'Edit Stadium',
        'manage_options',
        'sr-edit-stadium',
        'sr_render_edit_stadium'
    );
});

function sr_render_notice($type, $msg){
    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($type), wp_kses_post($msg));
}

/** List screen */
function sr_render_stadiums_list() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    // Using fixed table name as provided
    $table = 'pool_wpkl_stadiums';
    $stadiums = $wpdb->get_results("SELECT id, name, photo FROM {$table} ORDER BY name ASC");

    echo '<div class="wrap"><h1 class="wp-heading-inline">Stadiums</h1>';
    if (isset($_GET['updated']) && $_GET['updated'] === '1') {
        sr_render_notice('success', 'Stadium photo updated.');
    }
    if (isset($_GET['cleared']) && $_GET['cleared'] === '1') {
        sr_render_notice('info', 'Stadium photo cleared.');
    }

    if (empty($stadiums)) {
        echo '<p>No stadiums found in <code>pool_wpkl_stadiums</code>.</p></div>';
        return;
    }

    echo '<table class="widefat striped fixed">';
    echo '<thead><tr><th style="width:80px;">ID</th><th>Name</th><th>Photo</th><th style="width:120px;">Actions</th></tr></thead><tbody>';
    foreach ($stadiums as $s) {
        echo '<tr>';
        echo '<td>' . intval($s->id) . '</td>';
        echo '<td>' . esc_html($s->name) . '</td>';
        echo '<td>';
        if (!empty($s->photo)) {
            echo '<img src="' . esc_url($s->photo) . '" alt="" style="max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;">';
        } else {
            echo '<em>None</em>';
        }
        echo '</td>';
        $edit_url = add_query_arg(['page' => 'sr-edit-stadium', 'id' => intval($s->id)], admin_url('admin.php'));
        echo '<td><a class="button button-primary" href="' . esc_url($edit_url) . '">Edit</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

/** Edit screen */
function sr_render_edit_stadium() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) {
        echo '<div class="wrap"><h1>Edit Stadium</h1><p>Invalid stadium ID.</p></div>';
        return;
    }

    $table = 'pool_wpkl_stadiums';
    $stadium = $wpdb->get_row($wpdb->prepare("SELECT id, name, photo FROM {$table} WHERE id = %d", $id));

    if (!$stadium) {
        echo '<div class="wrap"><h1>Edit Stadium</h1><p>Stadium not found.</p></div>';
        return;
    }

    $action_url = admin_url('admin-post.php');
    ?>
    <div class="wrap">
      <h1>Edit Stadium</h1>
      <form method="post" action="<?php echo esc_url($action_url); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('sr_save_stadium_photo_' . $stadium->id); ?>
        <input type="hidden" name="action" value="sr_save_stadium_photo">
        <input type="hidden" name="id" value="<?php echo intval($stadium->id); ?>">

        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label>Stadium</label></th>
            <td><input type="text" value="<?php echo esc_attr($stadium->name); ?>" class="regular-text" readonly></td>
          </tr>
          <tr>
            <th scope="row"><label>Current Photo</label></th>
            <td>
              <?php if (!empty($stadium->photo)): ?>
                <img src="<?php echo esc_url($stadium->photo); ?>" style="max-width:260px;height:auto;border:1px solid #ddd;padding:3px;background:#fff;"><br>
              <?php else: ?>
                <em>No photo set</em><br>
              <?php endif; ?>
              <label><input type="checkbox" name="clear_photo" value="1"> Clear photo</label>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="stadium_photo">Upload New Photo</label></th>
            <td>
              <input type="file" name="stadium_photo" id="stadium_photo" accept="image/*">
              <p class="description">Choose a new image to upload. It will be stored in the Media Library and the URL saved to <code>pool_wpkl_stadiums.photo</code>.</p>
            </td>
          </tr>
        </table>

        <?php submit_button('Save Stadium'); ?>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=sr-stadiums')); ?>">Back to Stadiums</a>
      </form>
    </div>
    <?php
}

/** Save handler */
add_action('admin_post_sr_save_stadium_photo', function () {
    if (!current_user_can('manage_options')) wp_die('Not allowed');

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) wp_die('Invalid stadium ID');

    check_admin_referer('sr_save_stadium_photo_' . $id);

    global $wpdb;
    $table = 'pool_wpkl_stadiums';

    $clear = !empty($_POST['clear_photo']) ? true : false;
    $new_url = '';

    // Handle upload (if any)
    if (isset($_FILES['stadium_photo']) && !empty($_FILES['stadium_photo']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $overrides = ['test_form' => false];
        $file = wp_handle_upload($_FILES['stadium_photo'], $overrides);

        if (!empty($file['error'])) {
            $redirect = add_query_arg(['page' => 'sr-edit-stadium', 'id' => $id], admin_url('admin.php'));
            $redirect = add_query_arg(['message' => urlencode($file['error'])], $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        // Attach to Media Library
        $attachment = [
            'post_mime_type' => $file['type'],
            'post_title'     => sanitize_file_name(basename($file['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];
        $attach_id = wp_insert_attachment($attachment, $file['file']);
        if (is_wp_error($attach_id)) {
            $new_url = $file['url']; // fallback to direct URL
        } else {
            $attach_data = wp_generate_attachment_metadata($attach_id, $file['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            $new_url = wp_get_attachment_url($attach_id);
        }
    }

    // Decide new DB value
    if ($clear) {
        $value = null;
    } elseif (!empty($new_url)) {
        $value = $new_url;
    } else {
        // No change
        $redirect = add_query_arg(['page' => 'sr-stadiums', 'updated' => '1'], admin_url('admin.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    // Update DB
    if ($value === null) {
        $wpdb->update($table, ['photo' => null], ['id' => $id], ['%s'], ['%d']);
        $redirect = add_query_arg(['page' => 'sr-stadiums', 'cleared' => '1'], admin_url('admin.php'));
    } else {
        $wpdb->update($table, ['photo' => $value], ['id' => $id], ['%s'], ['%d']);
        $redirect = add_query_arg(['page' => 'sr-stadiums', 'updated' => '1'], admin_url('admin.php'));
    }

    wp_safe_redirect($redirect);
    exit;
});