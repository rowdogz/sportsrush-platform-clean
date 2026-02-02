<?php
// Load WordPress core
require_once( dirname(__DIR__, 5) . '/wp-load.php' );

// Check if the user is logged in (Optional: Remove if not needed)
if ( ! is_user_logged_in() ) {
    wp_die( 'Unauthorized access!' );
}

// Get file name from URL parameter securely
if ( isset( $_GET['file'] ) ) {
    $file_name = basename( $_GET['file'] ); // Prevent directory traversal
    $upload_dir = WP_CONTENT_DIR . '/uploads/registrationmagic/';
    $file_path = $upload_dir . $file_name;

    // Check if file exists
    if ( file_exists( $file_path ) ) {
        // Set the correct MIME type
        $mime = mime_content_type( $file_path );
        header("Content-Type: $mime");
        header("Content-Length: " . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        wp_die( 'File not found!' );
    }
} else {
    wp_die( 'Invalid request!' );
}
?>
