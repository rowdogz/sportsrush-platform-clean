<?php
/**
 * Plugin Name: Football Pool Ranking Score Breakdown
 * Description: Show score breakdown in the ranking table (full points, toto points, goal bonus, goal difference, question points). Please note that simple calculation method will limit the functionality of this extension: joker multipliers cannot be correctly counted (the '%..._points%' placeholders will show points scored without multipliers) and the '%breakdown_question_points%' placeholder cannot be determined (will always return 'unknown').
 * Version: 2.0
 * Author: Antoine Hurkmans
 * Author URI: mailto:wordpressfootballpool@gmail.com
 * License: MIT
 */

/*******************************************************
 *    HOW TO USE THIS PLUGIN
 *
 * 1. Save this plugin in the /wp-content/plugins folder
 * 2. Activate it via the Plugins screen in the WP admin
 *
 *******************************************************/


add_filter( 'plugins_loaded', array( 'FootballPoolExtensionRankingScoreBreakdown', 'init_extension' ) );

class FootballPoolExtensionRankingScoreBreakdown {
	public static function init_extension() {
		if ( ! class_exists( 'Football_Pool' ) ) {
			// display a message if the Football Pool plugin is not activated.
			add_action( 'admin_notices', array( __CLASS__, 'disable_extension' ) );
			return;
		} else {
			if ( ! Football_Pool::is_at_least_version( '2.10.0' ) ) {
				// this version of the extension will only work for v2.10.0+
				add_action( 'admin_notices', array( __CLASS__, 'disable_extension' ) );
				return;
			}
		}
		
		// add a row with column headers and the opening <tbody> to the template
		add_filter( 'footballpool_ranking_template_start', array( __CLASS__, 'template_start' ) , null, 6 );
		// add </tbody> before </table>
		add_filter( 'footballpool_ranking_template_end', array( __CLASS__, 'template_end' ) , null, 6 );
		// add the new columns to the row template
		add_filter( 'footballpool_ranking_ranking_row_template', array( __CLASS__, 'ranking_row_template' ), null, 3 );
		// add the score breakdown data to the data set
		add_filter( 'footballpool_ranking_ranking_row_params', array( __CLASS__, 'change_params' ), null, 7 );
	}
	
	public static function disable_extension() {
		echo '<div class="error"><p>The Football Pool plugin is not activated or not version 2.10.0 or higher. Make sure you activate the correct version to enable the Football Pool Score Breakdown plugin.</p></div>';
	}
	
	public static function template_end( $template_end, $league, $user, $ranking_id, $all_user_view, $type ) {
		return '</tbody></table>';
	}
	
	public static function template_start( $template_start, $league, $user, $ranking_id, $all_user_view, $type ) {
		$template_start .= sprintf( '<thead>
										<tr>
										<th></th>
										<th class="user">%s</th>
										<th class="num-predictions">%s</th>
										<th class="score-breakdown question">%s</th>
										<th class="score-breakdown full">%s</th>
										<th class="score-breakdown toto">%s</th>
										<th class="score-breakdown goalbonus">%s</th>
										<th class="score-breakdown goaldiff">%s</th>
										<th class="score">%s</th>
										%s</tr>
									</thead>
									<tbody>'
									, __( 'user', 'football-pool' )
									, __( 'predictions', 'football-pool' )
									, __( 'questions', 'football-pool' )
									, __( 'Correct', 'football-pool' )
									, __( 'toto', 'football-pool' )
									, __( 'Score bonus', 'football-pool' )
									, __( 'Score diff', 'football-pool' )
									, __( 'points', 'football-pool' )
									, ( $all_user_view ? '<th></th>' : '' )
							);
		return $template_start;
	}

	public static function ranking_row_template( $template, $all_user_view, $type ) {
		$ranking_template = '<tr class="%css_class%">
								<td class="user-rank">%rank%.</td>
								<td class="user-name"><a href="%user_link%">%user_avatar%%user_name%</a></td>
								<td class="num-predictions">%num_predictions%</td>
								<td class="score-breakdown question">%breakdown_question_points%</td>
								<td class="score-breakdown full">%breakdown_full_points%</td>
								<td class="score-breakdown toto">%breakdown_toto_points%</td>
								<td class="score-breakdown goalbonus">%breakdown_goalbonus_points%</td>
								<td class="score-breakdown goaldiff">%breakdown_goaldiff_points%</td>
								<td class="user-score ranking score">%points%</td>';
		if ( $all_user_view ) {
			$ranking_template .= '<td class="user-league">%league_image%</td>';
		}
		$ranking_template .= '</tr>';

		return $ranking_template;
	}

	// Example of the template with the number of correct predictions instead of the points scored
	public static function ranking_row_template_numbers( $template, $all_user_view, $type ) {
		$ranking_template = '<tr class="%css_class%">
								<td class="user-rank">%rank%.</td>
								<td class="user-name"><a href="%user_link%">%user_avatar%%user_name%</a></td>
								<td class="num-predictions">%num_predictions%</td>
								<td class="score-breakdown question">%breakdown_question%</td>
								<td class="score-breakdown full">%breakdown_full%</td>
								<td class="score-breakdown toto">%breakdown_toto%</td>
								<td class="score-breakdown goalbonus">%breakdown_goalbonus%</td>
								<td class="score-breakdown goaldiff">%breakdown_goaldiff%</td>
								<td class="user-score ranking score">%points%</td>';
		if ( $all_user_view ) {
			$ranking_template .= '<td class="user-league">%league_image%</td>';
		}
		$ranking_template .= '</tr>';

		return $ranking_template;
	}

	private static function get_breakdown( $ranking_id ) {
		$cache_key = "fpx_score_breakdown_r{$ranking_id}";
		$breakdown = wp_cache_get( $cache_key );
		
		if ( $breakdown === false ) {
			global $wpdb;
			$prefix = FOOTBALLPOOL_DB_PREFIX;
			$match = FOOTBALLPOOL_TYPE_MATCH;
			$question = FOOTBALLPOOL_TYPE_QUESTION;

			$simple_calculation_method =
				( Football_Pool_Utils::get_fp_option( 'simple_calculation_method', 0, 'int' ) === 1 );

			$pool = new Football_Pool_Pool();
			$scorehistory = $pool->get_score_table();

			$breakdown = array();

			// Breakdown for the matches
			$sql = "SELECT 
						`user_id`
						, SUM( `full` ) AS `breakdown_full`
						, SUM( `toto` ) AS `breakdown_toto`
						, SUM( `goal_bonus` ) AS `breakdown_goalbonus`
						, SUM( `goal_diff_bonus` ) AS `breakdown_goaldiff`
                        , SUM( IF( `joker_used` > 0, `full`, 0 ) ) AS `joker_full`
                        , SUM( IF( `joker_used` > 0, `toto`, 0 ) ) AS `joker_toto`
                        , SUM( IF( `joker_used` > 0, `goal_bonus`, 0 ) ) AS `joker_goalbonus`
                        , SUM( IF( `joker_used` > 0, `goal_diff_bonus`, 0 ) ) AS `joker_goaldiff`
						, SUM( `joker_used` ) AS `jokers_used`
					FROM `{$prefix}{$scorehistory}` 
					WHERE `ranking_id` = {$ranking_id} AND `type` = {$match}
					GROUP BY `user_id` 
					ORDER BY `user_id` ASC";
			$rows = $wpdb->get_results( $sql, ARRAY_A );

			$reset_for_calc_method = 1;
			if ( $simple_calculation_method ) {
				// When using the simple calculation method, the jokers cannot be linked to individual scores and therefor
				// we cannot use the joker_used value to multiply scores. So, we simply set it to 0.
				$reset_for_calc_method = 0;
			}

			foreach ( $rows as $row ) {
				$breakdown[(int) $row['user_id']] = array(
					'full' => (int) $row['breakdown_full'],
					'toto' => (int) $row['breakdown_toto'],
					'goalbonus' => (int) $row['breakdown_goalbonus'],
					'goaldiff' => (int) $row['breakdown_goaldiff'],
					'joker_full' => (int) $row['joker_full'] * $reset_for_calc_method,
					'joker_toto' => (int) $row['joker_toto'] * $reset_for_calc_method,
					'joker_goalbonus' => (int) $row['joker_goalbonus'] * $reset_for_calc_method,
					'joker_goaldiff' => (int) $row['joker_goaldiff'] * $reset_for_calc_method,
					'jokers_used' => (int) $row['jokers_used'],
				);
			}

			// Add bonusquestions (for simple calc method we can only count the correct questions, not the points)
			if ( $simple_calculation_method ) {
				$sql = "SELECT 
						`user_id` 
						, `score` AS `breakdown_question`
						, 'unknown' AS `breakdown_question_points`
					FROM `{$prefix}{$scorehistory}`
					WHERE `ranking_id` = {$ranking_id}
					ORDER BY `user_id` ASC";
			} else {
				$sql = "SELECT 
						`user_id` 
						, COUNT( IF( `score` > 0, 1, NULL ) ) AS `breakdown_question`
						, SUM( `score` ) AS `breakdown_question_points`
					FROM `{$prefix}{$scorehistory}`
					WHERE `ranking_id` = {$ranking_id} AND `type` = {$question}
					GROUP BY `user_id` 
					ORDER BY `user_id` ASC";
			}
			$rows = $wpdb->get_results( $sql, ARRAY_A );

			foreach( $rows as $row ) {
				$breakdown[(int) $row['user_id']]['question_correct'] = $row['breakdown_question'];
				$breakdown[(int) $row['user_id']]['question_points'] = $row['breakdown_question_points'];
			}

			wp_cache_set( $cache_key, $breakdown );
		}
		
		return $breakdown;
	}

	public static function change_params( $params, $league, $user, $ranking_id, $all_user_view, $type, $row ) {
		$user_id = (int) $params['user_id'];
		$breakdown = self::get_breakdown( $ranking_id );
		
		// set the params to 0
		$params['breakdown_full'] = $params['breakdown_full_points'] = 0;
		$params['breakdown_toto'] = $params['breakdown_toto_points'] = 0;
		$params['breakdown_goalbonus'] = $params['breakdown_goalbonus_points'] = 0;
		$params['breakdown_goaldiff'] = $params['breakdown_goaldiff_points'] = 0;
		$params['breakdown_question'] = $params['breakdown_question_points'] = 0;
		$params['jokers_used'] = 0;
		
		if ( array_key_exists( $user_id, $breakdown ) ) {
			$full = Football_Pool_Utils::get_fp_option( 'fullpoints', FOOTBALLPOOL_FULLPOINTS, 'int' );
			$toto = Football_Pool_Utils::get_fp_option( 'totopoints', FOOTBALLPOOL_TOTOPOINTS, 'int' );
			$goal = Football_Pool_Utils::get_fp_option( 'goalpoints', FOOTBALLPOOL_GOALPOINTS, 'int' );
			$diff = Football_Pool_Utils::get_fp_option( 'diffpoints', FOOTBALLPOOL_DIFFPOINTS, 'int' );
			$joker_multiplier = Football_Pool_Utils::get_fp_option( 'joker_multiplier', FOOTBALLPOOL_DIFFPOINTS, 'int' );

			// check for matches
			if ( array_key_exists( 'full', $breakdown[$user_id] ) ) {
				$params['breakdown_full'] = $breakdown[$user_id]['full'];
				$params['breakdown_full_points'] =
					$full * ( $breakdown[$user_id]['full'] - $breakdown[$user_id]['joker_full'] ) +
					$full * $joker_multiplier * $breakdown[$user_id]['joker_full'];
				
				$params['breakdown_toto'] = $breakdown[$user_id]['toto'];
				$params['breakdown_toto_points'] =
					$toto * ( $breakdown[$user_id]['toto'] - $breakdown[$user_id]['joker_toto'] ) +
					$toto * $joker_multiplier * $breakdown[$user_id]['joker_toto'];
				
				$params['breakdown_goalbonus'] = $breakdown[$user_id]['goalbonus'];
				$params['breakdown_goalbonus_points'] =
					$goal * ( $breakdown[$user_id]['goalbonus'] - $breakdown[$user_id]['joker_goalbonus'] ) +
					$goal * $joker_multiplier * $breakdown[$user_id]['joker_goalbonus'];
				
				$params['breakdown_goaldiff'] = $breakdown[$user_id]['goaldiff'];
				$params['breakdown_goaldiff_points'] =
					$diff * ( $breakdown[$user_id]['goaldiff'] - $breakdown[$user_id]['joker_goaldiff'] ) +
					$diff * $joker_multiplier * $breakdown[$user_id]['joker_goaldiff'];

				$params['jokers_used'] = $breakdown[$user_id]['jokers_used'];
			}
			
			// check for questions
			if ( array_key_exists( 'question_correct', $breakdown[$user_id] ) ) {
				$params['breakdown_question'] = $breakdown[$user_id]['question_correct'];
				$params['breakdown_question_points'] = $breakdown[$user_id]['question_points'];
			}
		}
		
		return $params;
	}
}