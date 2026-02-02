<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title><?php bloginfo( 'name' ); ?></title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap');

        .tableHead,
        .tableData {
            border: 1px solid #ccc;
            padding: 10px;
        }

        .tableT {
            width: 100%;
            border-collapse: collapse;
        }

        table {
            border-spacing: 0;
        }

        td {
            padding: 0;
        }

        p {
            font-size: 15px;
        }

        img {
            border: 0;
        }

        a {
            text-decoration: none;
            color: inherit;
            font-size: 16px;
        }

        .content {
            line-height: 20px;
            font-size: 15px;
        }

        @media screen and (max-width: 599.98px) {
            .two-columns .padding {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }

            img.two-col-img {
                width: 300px !important;
                max-width: 300px !important;
            }

            .two-third-columns .padding {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }

            .two-third-columns .padding.first {
                padding-bottom: 0 !important;
            }

            .two-third-columns .padding.last {
                padding-top: 0 !important;
            }

            img.one-third-col-img {
                width: 200px !important;
                max-width: 200px !important;
            }

            .two-third-columns .content {
                text-align: center !important;
            }

            img.three-col-img-last {
                width: 200px !important;
                max-width: 200px !important;
            }

            .three-columns .padding {
                padding-right: 0 !important;
                padding-left: 0 !important;
            }
        }

        @media screen and (max-width: 399.98px) {
            img.three-col-img {
                width: 200px !important;
                max-width: 200px !important;
            }
        }

        @media screen and (max-width: 500px) {
            .padding {
                /* padding-top: 0!important; */
            }
        }

        .sue-email-body table {
            padding-top: 20px;
        }

        .sue-email-body table th, .sue-email-body table td {
            border: 1px solid #cccccc;
            padding: 5px 10px;
            border-collapse: collapse;
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

    <!--[if (gte mso 9)|(IE)]>
    <style type="text/css">
        table {
            border-collapse: collapse !important;
        }
    </style>
    <![endif]-->

    <?php if ( $styles ): ?>
        <style>
            <?php echo stripslashes_deep( wp_strip_all_tags( $styles ) ); ?>
        </style>
    <?php endif; ?>

</head>

<body style="Margin:0;padding:0;min-width:100%;background-color:#dde0e1;">

<!--[if (gte mso 9)|(IE)]>
<style type="text/css">
    body {
        background-color: #dde0e1 !important;
    }

    body, table, td, p, a {
        font-family: sans-serif, Arial, Helvetica !important;
    }
</style>
<![endif]-->
<center style="width: 100%;table-layout:fixed;background-color: #dde0e1;padding-bottom:0">
    <table align="center"
           style="border-spacing:0;color:#565656;font-family: 'Lato',sans-serif, Arial, Helvetica;Margin:0;padding:10px 5px;width: 100%;max-width: 600px;"
           role="presentation">
        <tbody>
        <tr>
            <th class="column-top" width="200"
                style="font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; vertical-align:top;">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td class="text-top m-center mpb5 darkmode-bg"
                            style="padding-left: 10px; font-family: Times New Roman, Georgia, serif; font-size: 11px; line-height: 22px; text-align: left; text-transform: uppercase;"
                            data-darkreader-inline-color="">
                            <multiline><?php bloginfo( 'name' ); ?></multiline>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </th>
            <th class="column-top"
                style="font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; vertical-align:top;">
                <?php if ( ! empty( $social ) ): ?>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                            <td align="right">
                                <table class="center" border="0" cellspacing="0" cellpadding="0"
                                       style="text-align:center;padding-top: 5px;">
                                    <tbody>
                                    <tr>
                                        <?php foreach ( Send_Users_Email_Admin::$social as $platform ): ?>
                                            <?php if ( isset( $social[ $platform ] ) ): ?>
                                                <?php if ( ! empty( $social[ $platform ] ) ): ?>
                                                    <td class="img" width="32"
                                                        style="font-size:0pt; line-height:0pt; text-align:left;">
                                                        <a href="<?php echo esc_url_raw( $social[ $platform ] ); ?>"
                                                           target="_blank">
                                                            <img src="<?php echo esc_attr( sue_get_asset_url( $platform . '.png' ) ); ?>"
                                                                 width="18" height="18" border="0"
                                                                 alt="<?php echo esc_attr($platform); ?>">
                                                        </a>
                                                    </td>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </th>
        </tr>
        </tbody>
    </table>
</center>
<center style="width: 100%;table-layout:fixed;background-color: #dde0e1;padding-bottom:0">
    <div style="max-width: 600px;background-color: #fafdfe;box-shadow: 0 0 10px rgba(0, 0, 0, .2);border-radius: 30px 30px 30px 30px;">

        <div style="font-size: 10px;color: #fafdfe;line-height: 1px;mso-line-height-rule:exactly;display: none;max-width: 0px;max-height: 0px;opacity: 0;overflow: hidden;mso-hide:all;"></div>


        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" style="border-spacing:0;color:#565656;" role="presentation">
            <tr>
                <td style="padding:0;">
        <![endif]-->

        <table align="center"
               style="border-spacing:0;color:#565656;font-family: 'Lato',sans-serif, Arial, Helvetica;background-color: #fafdfe;Margin:0;padding:0;width: 100%;max-width: 600px;border-radius: 30px 30px 30px 30px;"
               role="presentation">

            <tr>
                <td style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
                    <table width="100%" style="border-spacing:0;" role="presentation">

                        <?php if ( esc_url_raw( $logo ) ): ?>
                            <tr class="sue-email-logo">
                                <td style="background-color:#ffffff;padding-top:10px;padding-bottom:8px;width:100%;width:600px;text-align:center;border-radius: 30px 30px 0 0;">
                                    <a href="<?php bloginfo( 'url' ); ?>">
                                        <img src="<?php echo esc_url_raw( $logo ); ?>"
                                             alt="<?php bloginfo( 'name' ); ?>" width="100" style="border-width:0;"
                                             border="0">
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
                                    <table width="100%" style="border-spacing:0;" role="presentation">
                                        <tr>
                                            <td class="padding content"
                                                style="background-color:#D64123;padding-top:2px;padding-bottom:1px;padding-right:0;padding-left:0;width:100%;text-align:center; width:600px;"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if ( $title || $tagline ): ?>
                            <tr>
                                <td class="banner" width="600" style="background-position: center top;" align="center">

                                    <!--[if (gte mso 9)|(IE)]>
                                    <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false"
                                            style="width:600px;height:274px;">
                                        <v:fill type="tile" src="https://via.placeholder.com/600x374"/>
                                        <v:textbox inset="0,0,0,0">
                                    <![endif]-->

                                    <table class="darkmode-transparent" cellpadding="0" cellspacing="0"
                                           role="presentation">
                                        <tr>
                                            <td width="600" align="center" valign="middle">
                                                <table class="darkmode-transparent" cellpadding="0" cellspacing="0"
                                                       role="presentation">
                                                    <tr>
                                                        <td align="center">
                                                            <table class="darkmode-transparent" role="presentation">
                                                                <tr>

                                                                    <!--[if (gte mso 9)|(IE)]>
                                                                    <td style="padding-top:100px;padding-bottom:25px;">
                                                                    <![endif]-->
                                                                    <td class="darkmode-bg" valign="top" align="center"
                                                                        style="padding-bottom:20px;padding-top:20px;vertical-align:middle; font-size: 22px; line-height: 26px;">

                                                                        <?php if ( $title ): ?>
                                                                            <p style="font-size: 24px; line-height: 32px; font-weight:bold;;text-shadow: 1px 1px 1px #3d3d3d;">
                                                                            <?php echo esc_html( stripslashes_deep( $title ) ); ?>
                                                                            </p>
                                                                        <?php endif; ?>

                                                                        <?php if ( $tagline ): ?>
                                                                            <p style="max-width: 150px;height: 2px;background-color:#D64123;border-top: 1px solid #ffffff;border-bottom: 1px solid #ffffff;line-height: 40px;"></p>

                                                                            <p align="center"
                                                                               style="font-size:20px; line-height: 22px;text-shadow: 1px 1px 1px #3d3d3d;">
                                                                               <?php echo esc_html( stripslashes_deep( $tagline ) ); ?>
                                                                            </p>
                                                                        <?php endif; ?>
                                                                    </td>

                                                                    <!--[if (gte mso 9)|(IE)]>
                                                                    </td>
                                                                    <![endif]-->

                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!--[if (gte mso 9)|(IE)]>
                                    </v:textbox>
                                    </v:rect>
                                    <![endif]-->

                                </td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </td>
            </tr>

            <?php if ( $title || $tagline ): ?>
                <tr>
                    <td style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
                        <table width="100%" style="border-spacing:0;" role="presentation">
                            <tr>
                                <td class="padding content"
                                    style="background-color:#D64123;padding-top:2px;padding-bottom:1px;padding-right:0;padding-left:0;width:100%;text-align:center; width:600px;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>

            <tr class="sue-email-body">
                <td style="padding: 25px 18px 25px 18px;">
                    <?php echo wp_kses_post( stripslashes_deep( $email_body ) ); ?>
                </td>
            </tr>

            <?php if ( $footer ): ?>
                <tr>
                    <td style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
                        <table width="100%" style="border-spacing:0;padding-top:5px" role="presentation">
                            <tr>
                                <td class="padding content sue-footer"
                                    style="background-color:#D64123;color:#ffffff;padding-top:30px;padding-bottom:30px;padding-right:0;padding-left:0;width:100%;text-align:center; width:600px;">
                                    <?php echo stripslashes_deep( $footer ); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ( ! empty( $social ) ): ?>
                <tr>
                    <td style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;">
                        <table width="100%" style="border-spacing:0;" role="presentation">
                            <tr>
                                <td class="padding content"
                                    style="padding-top:35px;padding-bottom:35px;padding-right:0;padding-left:0;width:100%;text-align:center; width:600px;">
                                    <?php foreach ( Send_Users_Email_Admin::$social as $platform ): ?>
                                        <?php if ( isset( $social[ $platform ] ) ): ?>
                                            <?php if ( ! empty( $social[ $platform ] ) ): ?>
                                                <a href="<?php echo esc_url_raw( $social[ $platform ] ); ?>">
                                                    <img src="<?php echo esc_attr( sue_get_asset_url( $platform . '.png' ) ); ?>"
                                                         alt="<?php echo esc_attr($platform); ?>" width="35"
                                                         style="display:inline-block;border-width:0;max-width: 35px;">
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>

        </table>

        <!--[if (gte mso 9)|(IE)]>
        </td>
        </tr>
        </table>
        <![endif]-->

    </div>
</center>

<p class="darkmode-bg" style="color: #000000;text-align: center;font-size: 13px;">
    <?php esc_attr_e( 'You are receiving this message because you are a member of', 'send-users-email' ) ?>
    <strong><?php bloginfo( 'name' ); ?></strong>.
</p>

</body>

</html>