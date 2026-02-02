<?php
/*
Plugin Name: Direct Password Reset Link
Description: It shows you a password reset link in the user profile page and in the users list page.
Author: Jose Mortellaro
Author URI: https://josemortellaro.com/
Domain Path: /languages/
Text Domain: direct-password-rest-link
Version: 0.0.2
*/
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action( 'edit_user_profile',function( $user ){
  if ( current_user_can( 'edit_users' ) ) {
  	$reset_key = get_password_reset_key( $user );
    $link = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=".rawurlencode( esc_attr( $user->user_login ) ),'login' );
    ?>
    <table id="password-reset-link" style="margin:32px 0">
      <tr>
        <th><?php esc_html_e( 'Password reset link','direct-password-reset-link' ); ?></th>
        <td><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_url( $link ); ?></a></td>
      </tr>
    </table>
    <script id="password-reset-link-js">
    function password_reset_link_init(){
      var pswWrp = document.getElementsByClassName('user-generate-reset-link-wrap');
      if(pswWrp && pswWrp.length > 0){
        resetLinkWrp = document.getElementById('password-reset-link');
        resetLinkWrp.style.display = 'none';
        pswWrp[0].parentNode.innerHTML += resetLinkWrp.innerHTML;
        resetLinkWrp.style.display = 'block';
      }
    }
    password_reset_link_init();
    </script>
    <?php
  }
  return $user;
} );

add_filter( 'user_row_actions',function( $actions,$user ){
  if( current_user_can( 'edit_users' ) ) {
    $reset_key = get_password_reset_key( $user );
    $link = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=".rawurlencode( esc_attr( $user->user_login ) ),'login' );
    $actions['resetpassword_directlink'] = '<a href="'.esc_url( $link ).'">'.esc_html__( 'Direct password reset link','direct-password-reset-link').'</a>';
  }
  return $actions;
},99,2 );
