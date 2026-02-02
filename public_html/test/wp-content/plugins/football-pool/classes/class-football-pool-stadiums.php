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

class Football_Pool_Stadiums {
	public function get_stadiums() {
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = "SELECT id, name, photo, comments FROM {$prefix}stadiums ORDER BY name ASC";
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		
		$stadiums = array();
		foreach ( $rows as $row ) {
			$stadiums[] = new Football_Pool_Stadium($row);
		}
		return $stadiums;
	}
	
	public function print_lines( $stadiums ) {
		$thumbs_in_listing = Football_Pool_Utils::get_fp_option( 'listing_show_venue_thumb', 1, 'int' ) === 1;
		$comments_in_listing = Football_Pool_Utils::get_fp_option( 'listing_show_venue_comments', 1, 'int' ) === 1;
		$output = '';
		while ( $stadium = array_shift( $stadiums ) ) {
			$photo = ( $thumbs_in_listing && $stadium->photo != '' ) ? $stadium->HTML_image( 'thumb' ) : '';
			$comments = ( $comments_in_listing ) ? $stadium->comments : '';
			$line = sprintf( '<div><a href="%1$s">%2$s</a><h2><a href="%1$s">%3$s</a></h2><p>%4$s</p></div>'
								, esc_url( add_query_arg( array( 'stadium' => $stadium->id ) ) )
								, $photo
								, Football_Pool_Utils::xssafe( $stadium->name )
								, Football_Pool_Utils::xssafe( $comments )
							);
			$output .= apply_filters( 'footballpool_stadiums_print_line', $line, $stadium );
		}
		return $output;
	}
	
	public function get_stadium_by_id( $id ) {
		if ( ! is_numeric( $id ) ) return 0;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		$sql = $wpdb->prepare( "SELECT id, name, photo, comments FROM {$prefix}stadiums WHERE id = %d", $id );
		$row = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( $row ) ? new Football_Pool_Stadium( $row ) : null;
	}
	
	// returns object

	/**
	 * @param string $name
	 * @param string $addnew
	 * @param array $extra_data
	 * @return stdClass|null
	 */
	public function get_stadium_by_name( string $name, string $addnew = 'no', array $extra_data = [] ) {
		if ( $name === '' ) return null;
		
		global $wpdb;
		$prefix = FOOTBALLPOOL_DB_PREFIX;
		
		$sql = $wpdb->prepare( "SELECT id, name, photo, comments
								FROM {$prefix}stadiums WHERE name = %s", $name );
		$result = $wpdb->get_row( $sql );
		
		if ( $addnew == 'addnew' && $result == null ) {
			$photo = $comments = '';
			
			if ( count( $extra_data ) > 0 ) {
				$photo    = $extra_data['photo'];
				$comments = $extra_data['comments'] ?? '';
			}
			
			$sql = $wpdb->prepare( 
							"INSERT INTO {$prefix}stadiums ( name, photo, comments ) 
							 VALUES ( %s, %s, %s )"
							, $name, $photo, $comments
					);
			$wpdb->query( $sql );
			$id = $wpdb->insert_id;
			$result = (object) array( 
									'id'       => $id, 
									'name'     => $name,
									'photo'    => $photo,
									'comments'    => $comments,
									'inserted' => true
								);
		}
		
		return $result;
	}
}
