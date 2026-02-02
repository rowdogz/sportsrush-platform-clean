<?php

/*
 * Football Pool WordPress plugin
 *
 * @copyright Copyright (c) 2024 Antoine Hurkmans
 * @link https://wordpress.org/plugins/football-pool/
 * @license https://plugins.svn.wordpress.org/football-pool/trunk/COPYING
 *
 * This file is part of Football pool.
 *
 * Football pool is free software: you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * Football pool is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Football pool.
 * If not, see <https://www.gnu.org/licenses/>.
 */

/** @noinspection SqlResolve */

class Football_Pool_Shoutbox {
	public function get_messages( $nr = - 1 ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$sql = "SELECT s.id, u.display_name AS user_name, u.ID AS user_id
					, s.shout_text, s.date_entered as shout_date
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.user_id = u.ID 
				ORDER BY s.date_entered DESC, s.id DESC";
		if ( $nr > 0 ) {
			$sql .= " LIMIT %d";
			$sql = $wpdb->prepare( $sql, $nr );
		}

		return apply_filters( 'footballpool_shoutbox_messages', $wpdb->get_results( $sql, ARRAY_A ) );
	}

	public function get_message( $id ) {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$sql = "SELECT s.id, u.display_name AS user_name, u.ID AS user_id
					, s.shout_text, s.date_entered as shout_date
				FROM {$prefix}shoutbox s, {$wpdb->users} u 
				WHERE s.user_id = u.ID AND s.id = %d";
		$sql = $wpdb->prepare( $sql, $id );

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	public function save_shout( $text, $user_id, $max_chars ): bool {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$shout_date = Football_Pool_Utils::gmt_from_date( current_time( 'mysql' ) );
		if ( ! $this->is_double_post( $text, $user_id, $shout_date ) && $user_id > 0 ) {
			if ( strlen( $text ) > $max_chars ) {
				$text = substr( $text, 0, $max_chars );
			}

			$sql = $wpdb->prepare( "INSERT INTO {$prefix}shoutbox ( user_id, shout_text, date_entered ) 
									VALUES ( %d, %s, %s )",
				$user_id, $text, $shout_date );
			do_action( 'footballpool_shoutbox_before_save', $text, $user_id );
			$result = $wpdb->query( $sql ) !== false;

			if ( $result && Football_Pool_Utils::get_fp_option( 'shoutbox_notifications', false, 'bool' ) ) {
				$this->send_notification( $text, $user_id );
			}

			do_action( 'footballpool_shoutbox_after_save', $text, $user_id );
		} else {
			$result = false;
		}

		return $result;
	}

	private function send_notification( $text, $user_id ) {
		global $pool;

		$shoutbox_text = Football_Pool_Utils::xssafe( $text, 'UTF-8', false );
		$user_name = $pool->user_name( $user_id );

		$mail_subject = 'Football Pool: New shoutbox message';
		$mail_message = sprintf(
			'<p>New shoutbox message from <strong>%s</strong>:</p><p>%s</p>',
			$user_name,
			$shoutbox_text
		);

		$mail_to = defined( 'FOOTBALLPOOL_MAIL_TO' ) ? FOOTBALLPOOL_MAIL_TO : get_bloginfo( 'admin_email' );
		$mail_headers = ['Content-Type: text/html; charset=UTF-8'];

		wp_mail( $mail_to, $mail_subject, $mail_message, $mail_headers );
	}

	/**
	 * @param $text
	 * @param $user
	 * @param $date
	 *
	 * @return bool
	 */
	private function is_double_post( $text, $user, $date ): bool {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;

		$sql = $wpdb->prepare( "SELECT COUNT( * ) FROM {$prefix}shoutbox
								WHERE user_id = %d AND shout_text = %s
									AND TIMESTAMPDIFF( SECOND, date_entered, %s ) <= %d",
			$user, $text, $date, FOOTBALLPOOL_SHOUTBOX_DOUBLE_POST_INTERVAL );

		$result = $wpdb->get_var( $sql );

		return ( $result > 0 );
	}
}
