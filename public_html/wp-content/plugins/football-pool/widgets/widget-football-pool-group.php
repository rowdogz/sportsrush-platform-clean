<?php

/*
 * Football Pool WordPress plugin
 *
 * @copyright Copyright (c) 2025 Antoine Hurkmans
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

/**
 * Widget: Group Standing Widget
 */

defined( 'ABSPATH' ) or die( 'Cannot access widgets directly.' );
add_action( 'widgets_init', function() { register_widget( 'Football_Pool_Group_Widget' ); } );

class Football_Pool_Group_Widget extends Football_Pool_Widget {
	protected $widget = [];

	public function __construct() {
		$this->widget = array(
			'name' => __( 'Group Widget', 'football-pool' ),
			'description' => __( 'this widget displays the tournament standing for a group.', 'football-pool' ),
			'do_wrapper' => true,

			'fields' => array(
				array(
					'name' => __( 'Title', 'football-pool' ),
					'desc' => '',
					'id' => 'title',
					'type' => 'text',
					'std' => __( 'standing', 'football-pool' )
				),
				array(
					'name'    => __( 'Show this group', 'football-pool' ),
					'desc'    => '',
					'id'      => 'group',
					'type'    => 'select',
					'options' => [] // get data from the database later on
				),
				array(
					'name'    => __( 'Layout of the widget', 'football-pool' ),
					'desc'    => '',
					'id'      => 'layout',
					'type'    => 'select',
					'options' => [] // get data later on
				),
			)
		);

		$classname = str_replace( '_', '', get_class( $this ) );
		parent::__construct(
			$classname,
			$this->widget['name'] ?? $classname,
			$this->widget['description']
		);
	}

	public function html( string $title, array $args, array $instance ) {
		extract( $args );
		
		$group = ! empty( $instance['group'] ) ? $instance['group'] : '1';
		$layouts = array( 1 => 'wide', 2 => 'small' );
		$layout = ! empty( $instance['layout'] ) ? $layouts[$instance['layout']] : $layouts[1];
		
		$output = '';
		if ( $title !== '' ) {
			/** @var string $before_title */
			/** @var string $after_title */
			$output .= $before_title . $title . $after_title;
		}
		
		$groups = new Football_Pool_Groups();
		$output .= $groups->print_group_standing( $group, $layout );
		echo apply_filters( 'footballpool_widget_html_group', $output );
	}

	public function initiate_widget_dynamic_fields() {
		// get the groups from the database
		$groups = ( new Football_Pool_Groups() )->get_group_names();
		$options = [];
		foreach ( $groups as $id => $group ) {
			$options[$id] = Football_Pool_Utils::xssafe( $group );
		}
		$this->widget['fields'][1]['options'] = $options;

		// set the layout options
		$this->widget['fields'][2]['options'] = array (
			1 => __( 'wide', 'football-pool' ),
			2 => __( 'small', 'football-pool' )
		);
	}

}
