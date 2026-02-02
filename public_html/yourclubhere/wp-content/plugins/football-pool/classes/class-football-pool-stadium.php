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

/** @noinspection HtmlUnknownTarget */

class Football_Pool_Stadium extends Football_Pool_Stadiums {
	public $id = 0;
	public $name = '';
	public $photo = '';
	public $comments = '';
	
	public function __construct( $stadium = 0 ) {
		if ( is_int( $stadium ) && $stadium != 0 ) {
			$s = $this->get_stadium_by_id( $stadium );
			if ( is_object( $s ) ) {
				$this->id = $s->id;
				$this->name = $s->name;
				$this->photo = $s->photo;
				$this->comments = $s->comments;
			}
		} elseif ( is_array( $stadium ) ) {
			$this->id = $stadium['id'];
			$this->name = $stadium['name'];
			$this->photo = $stadium['photo'];
			$this->comments = $stadium['comments'];
		}
	}

	/** @noinspection HttpUrlsUsage */
	private function get_photo_url( $photo ) {
		$path = '';
		if ( stripos( $photo, 'http://' ) !== 0 && stripos( $photo, 'https://' ) !== 0 ) {
			$path = trailingslashit( FOOTBALLPOOL_UPLOAD_URL . 'stadiums' );
		}
		
		return $path . $photo;
	}
	
	public function HTML_image( $return = 'image' ) {
		$thumb = ( $return == 'thumb' ) ? ' thumb stadium-list' : '';
		return sprintf( '<img src="%s" title="%s" alt="%s" class="stadium-photo%s">'
						, esc_attr( Football_Pool_Utils::xssafe( $this->get_photo_url( $this->photo ) ) )
						, esc_attr( Football_Pool_Utils::xssafe( $this->name ) )
						, esc_attr( Football_Pool_Utils::xssafe( $this->name ) )
						, $thumb
					);
	}
	
	public function get_plays() {
		global $pool;
		$matches = $pool->matches->matches;
		
		$plays = [];
		foreach ( $matches as $match ) {
			if ( $match['stadium_id'] == $this->id ) $plays[] = $match;
		}
		
		return $plays;
	}
}
