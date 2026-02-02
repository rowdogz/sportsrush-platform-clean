<?php

global $preview;
if ( $preview ) {
    $options = get_option( 'sue_send_users_email' );
    // Ensure $options is an array before accessing its keys
    if ( !is_array( $options ) ) {
        $options = [];
    }
    // Use null coalescing operator (??) for default values
    $logo = $options['logo_url'] ?? SEND_USERS_EMAIL_PLUGIN_BASE_URL . '/assets/sample-logo.png';
    $title = $options['email_title'] ?? __( 'This is a preview title', 'send-users-email' );
    $tagline = $options['email_tagline'] ?? __( 'This is a preview tagline', 'send-users-email' );
    $footer = $options['email_footer'] ?? __( 'Demo Footer Content', 'send-users-email' );
    // Handle social links with a default array
    $social = $options['social'] ?? [
        "facebook"  => "#",
        "instagram" => "#",
        "linkedin"  => "#",
        "skype"     => "#",
        "tiktok"    => "#",
        "twitter"   => "#",
        "youtube"   => "#",
    ];
    // Default email body content
    $email_body = __( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.', 'send-users-email' );
    // Optional styles
    $styles = $options['email_template_style'] ?? '';
    ?>

<?php 
    ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow" style="text-align: center; margin-bottom: 5rem;">
                    <div class="card-body">
                        <h3 class="text-center pt-3 pb-3 display-7 text-uppercase"><?php 
    esc_attr_e( 'You are using the free version', 'send-users-email' );
    ?></h3>
                        <h6 style="margin-bottom: 2rem;"><?php 
    esc_attr_e( 'Upgrade to PRO to preview and use prebuilt templates. Or even use your own HTML template.', 'send-users-email' );
    ?></h6>
                        <a class="btn btn-success btn-lg" href="<?php 
    echo esc_attr( sue_fs()->get_upgrade_url() );
    ?>"
                           role="button"><?php 
    esc_attr_e( 'Upgrade to PRO', 'send-users-email' );
    ?></a>
                    </div>
                </div>

                <h3 class="text-center" style="margin-bottom: 3rem;"><?php 
    esc_attr_e( 'Email Plain Text Preview:', 'send-users-email' );
    ?></h3>
            </div>
        </div>
    </div>
    <style>
        .sue-main-table {
            max-width: 768px;
            margin: 0 auto;
        }
    </style>
<?php 
}
?>


<?php 
/**
 * @var $logo
 * @var $styles
 * @var $title
 * @var $tagline
 * @var $email_body
 * @var $footer
 * @var $social
 */
?>
<!DOCTYPE html>
<html lang="en" dir="auto" class="email-content">
<head>
    <title><?php 
bloginfo( 'name' );
?></title>
    <meta charset="UTF-8"/>

    <style>
        .sue-logo td {
            text-align: center;
        }

        .sue-logo img {
            max-height: 75px;
        }

        .sue-title {
            text-align: center;
        }

        .sue-tagline {
            text-align: center;
        }

        .sue-footer td {
            text-align: center;
            padding-top: 30px;
        }

        .sue-footer-social td {
            text-align: center;
            padding-top: 30px;
        }

        .aligncenter {
            display: block;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        .alignleft {
            float: left;
            margin-inline-start: 0;
            margin-inline-end: 2em;
        }

        .alignright {
            float: right;
            margin-inline-start: 2em;
            margin-inline-end: 0;
        }
    </style>

	<?php 
if ( $styles ) {
    ?>
        <style>
            <?php 
    echo stripslashes_deep( wp_strip_all_tags( $styles ) );
    ?>
        </style>
	<?php 
}
?>

</head>
<body>

<table class="sue-main-table">
	<?php 
if ( esc_url_raw( $logo ) ) {
    ?>
        <tr class="sue-logo">
            <td>
                <img src="<?php 
    echo esc_url_raw( $logo );
    ?>" alt="<?php 
    bloginfo( 'name' );
    ?>"/>
            </td>
        </tr>
	<?php 
}
?>

	<?php 
if ( $title || $tagline ) {
    ?>
        <tr class="sue-title-tagline">
            <td>
				<?php 
    if ( $title ) {
        ?>
                    <h2 class="sue-title"><?php 
        echo esc_html( stripslashes_deep( $title ) );
        ?></h2>
				<?php 
    }
    ?>

				<?php 
    if ( $tagline ) {
        ?>
                    <h5 class="sue-tagline"><?php 
        echo esc_html( stripslashes_deep( $tagline ) );
        ?></h5>
				<?php 
    }
    ?>
            </td>
        </tr>
	<?php 
}
?>

    <tr class="sue-email-body">
        <td>
			<?php 
echo wp_kses_post( stripslashes_deep( $email_body ) );
?>
        </td>
    </tr>

	<?php 
if ( $footer ) {
    ?>
        <tr class="sue-footer">
            <td>
				<?php 
    echo stripslashes_deep( $footer );
    ?>
            </td>
        </tr>
	<?php 
}
?>

	<?php 
if ( !empty( $social ) ) {
    ?>
        <tr class="sue-footer-social">
            <td>
				<?php 
    foreach ( Send_Users_Email_Admin::$social as $platform ) {
        ?>
					<?php 
        if ( isset( $social[$platform] ) ) {
            ?>
						<?php 
            if ( !empty( $social[$platform] ) ) {
                ?>
                            <a href="<?php 
                echo esc_url_raw( $social[$platform] );
                ?>" style="text-decoration: none;">
                                <img src="<?php 
                echo esc_attr( sue_get_asset_url( $platform . '.png' ) );
                ?>"
                                     alt="<?php 
                echo esc_attr( $platform );
                ?>" width="30"
                                     style="display:inline-block;border-width:0;max-width: 35px;">
                            </a>
						<?php 
            }
            ?>
					<?php 
        }
        ?>
				<?php 
    }
    ?>
            </td>
        </tr>
	<?php 
}
?>
</table>

</body>
</html>
