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

function date_now_postdate_custom_fieldset( $shortcode ) {
	$html = <<<'HTML'
		<div>
			<fieldset>
				<legend>
					<a href="//php.net/manual/en/function.date.php" title="%1$s" target="_blank">%2$s</a>
				</legend>
				<div>
					<div>
						<label class="mce-label fp-mce-radio">
							<input type="radio" id="%3$s-now" name="%3$s-date" value="now" checked="checked">
							%4$s
						</label>
					</div>
					<div>
						<label class="mce-label fp-mce-radio">
							<input type="radio" id="%3$s-postdate" name="%3$s-date" value="postdate">
							%5$s
						</label>
					</div>
					<div>
						<label class="mce-label fp-mce-radio">
							<input type="radio" id="%3$s-custom" name="%3$s-date" value="custom" 
								onclick="jQuery( '#%3$s-custom-value' ).focus();">
							%6$s: 
								<input class="mce-textbox" type="text" id="%3$s-date-custom-value" placeholder="Y-m-d H:i" 
									onclick="jQuery( '#%3$s-custom' ).prop( 'checked', true );">
						</label>
					</div>
				</div>
			</fieldset>
		</div>
HTML;
		
		printf( $html
				, __( 'information about PHP\'s date format', 'football-pool' )
				, __( 'Date', 'football-pool' )
				, $shortcode
				, __( 'now', 'football-pool' )
				, __( 'postdate', 'football-pool' )
				, __( 'custom date', 'football-pool' )
		);
}

/**
 * @param  array  $options
 *
 * @return string
 */
function create_options( array $options ): string {
	$html = '';
	foreach ($options as $key => $val ) {
		$html .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), esc_attr( $val ) );
	}
	return $html;
}

function print_group_options() {
	$groups = Football_Pool_Groups::get_groups();
	foreach ( $groups as $group ) {
		printf( '<option value="%d">%s</option>', $group->id, Football_Pool_Utils::xssafe( $group->name ) );
	}
}

function group_options(): string {
	$groups = Football_Pool_Groups::get_groups();
	$options = '';
	foreach ( $groups as $group ) {
		$options .= sprintf(
			'<option value="%d">%s</option>',
			$group->id,
			Football_Pool_Utils::xssafe( $group->name )
		);
	}
	return $options;
}

function ranking_options(): string {
	global $pool;
	$rankings = $pool->get_rankings( 'user defined' );
	$options = '';
	foreach ( $rankings as $ranking ) {
		$options .= sprintf(
			'<option value="%d">%s</option>',
			$ranking['id'],
			Football_Pool_Utils::xssafe( $ranking['name'] )
		);
	}
	return $options;
}

function league_options(): string {
	global $pool;
	$leagues = $pool->get_leagues( true );
	$options = '';
	foreach ( $leagues as $league ) {
		$options .= sprintf( '<option value="%d">%s</option>'
							, $league['league_id']
							, Football_Pool_Utils::xssafe( $league['league_name'] ) 
					);
	}
	return $options;
}

function user_options(): string {
	global $pool;
	$options = '';
	$users = $pool->get_users( 0 );
	foreach ( $users as $user ) {
		$options .= sprintf(
			'<option value="%d">%s</option>',
			$user['user_id'],
			Football_Pool_Utils::xssafe( $user['user_name'] )
		);
	}
	return $options;
}

function matchtype_options(): string {
	$options = '';
	$match_types = Football_Pool_Matches::get_match_types();
	foreach( $match_types as $match_type ) {
		$options .= sprintf(
			'<option value="%d">%s</option>',
			$match_type->id,
			Football_Pool_Utils::xssafe( $match_type->name )
		);
	}
	return $options;
}

/**
 * @throws Exception
 */
function bonusquestion_options(): string {
	global $pool;
	$questions = $pool->get_bonus_questions();
	$options = '';
	foreach( $questions as $question ) {
		if ( $question['match_id'] == 0 ) {
			$options .= sprintf(
				'<option value="%d">%d: %s</option>',
				$question['id'],
				$question['id'],
				Football_Pool_Utils::xssafe( $question['question'] )
			);
		}
	}
	return $options;
}

/**
 * Merge param values with defaults, without overwriting existing keys.
 *
 * @param  array<string, mixed>  $values  Defined key-value pairs for the params.
 * @param  string  $label
 *
 * @return array<string, mixed> Combined array with defaults filled in.
 */
function merge_with_defaults( array $values, string $label ): array {
	$defaults = [
		'label_tooltip' => $label,
	];
	// `+` operator in PHP keeps existing keys from the left array,
	// and adds only missing keys from the right array.
	return $values + $defaults;
}

/**
 * Prepare parameters for a checkbox label+input combo.
 *
 * @param string               $label    Label text.
 * @param array<string, mixed> $params   Parameters.
 * @return array{
 *     label: string,
 *     div_id: string,
 *     param_string: string,
 *     label_param_string: string
 * }
 */
function prepare_label_checkbox_params( string $label, array $params ): array {
	$params = merge_with_defaults( $params, $label );

	// Special params
	$label_params = [ 'title' => $params['label_tooltip'] ?? '' ];
	unset( $params['label_tooltip'] );

	$div_id = '';
	if ( isset( $params['div_id'] ) ) {
		$div_id = sprintf( ' id="%s"', $params['div_id'] );
		unset( $params['div_id'] );
	}

	if ( isset( $params['label_link'] ) ) {
		$label = sprintf(
			'<a href="%s" target="_blank" title="%s">%s</a>',
			$params['label_link'],
			$label_params['title'],
			$label
		);
		unset( $params['label_link'] );
		unset( $label_params['title'] );
	}

	// Build strings
	$param_string = '';
	foreach ( $params as $param => $val ) {
		$param_string .= sprintf( '%s="%s" ', $param, $val );
	}

	$label_param_string = '';
	foreach ( $label_params as $param => $val ) {
		if ( $val !== '' ) {
			$label_param_string .= sprintf( '%s="%s" ', $param, $val );
		}
	}

	return [
		'label'              => $label,
		'div_id'             => $div_id,
		'param_string'       => $param_string,
		'label_param_string' => $label_param_string,
	];
}

function league_select( $shortcode, $params = [] ) {
	$label = __( 'Select a league', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-league-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-league-id" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		league_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function ranking_select_with_default( $shortcode, $params = [] ) {
	$label = __( 'Select a ranking', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-id" %8$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-id" %7$s>
					<optgroup label="%3$s">
						<option value="0" selected="selected">%4$s</option>
					</optgroup>
					<optgroup label="%5$s">
						%6$s
					</optgroup>
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		__( 'default', 'football-pool' ),
		__( 'all scores', 'football-pool' ),
		__( 'or choose a user defined ranking', 'football-pool' ),
		ranking_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function league_select_with_default( $shortcode, $params = [] ) {
	$label = __( 'Select a league', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-league" %8$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-league" %7$s>
					<optgroup label="%3$s">
						<option value="0" selected="selected">%4$s</option>
					</optgroup>
					<optgroup label="%5$s">
						%6$s
					</optgroup>
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		__( 'default', 'football-pool' ),
		__( 'all players', 'football-pool' ),
		__( 'or choose a league', 'football-pool' ),
		league_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function league_select_with_default_and_user( $shortcode, $params = [] ) {
	$label = __( 'Select a league', 'football-pool' );;
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-league" %10$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-league" %9$s>
					<optgroup label="%3$s">
						<option value="0" selected="selected">%4$s</option>
					</optgroup>
					<optgroup label="%7$s">
						<option value="user">%8$s</option>
					</optgroup>
					<optgroup label="%5$s">
						%6$s
					</optgroup>
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		__( 'default', 'football-pool' ),
		__( 'all players', 'football-pool' ),
		__( 'or choose a league', 'football-pool' ),
		league_options(),
		__( 'user', 'football-pool' ),
		__( 'league for logged in user', 'football-pool' ),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function label_textbox( $label, $input_id, $params = [] ) {
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div%4$s>
			<label class="mce-label fp-mce-text" for="%1$s" %5$s>%2$s</label>
			<div>
				<input class="mce-textbox" type="text" id="%1$s" %3$s/>
			</div>
		</div>
HTML;

	printf(
		$html,
		$input_id,
		$prepared['label'],
		$prepared['param_string'],
		$prepared['div_id'],
		$prepared['label_param_string']
	);
}

/**
 * @param $label string
 * @param $select_id string
 * @param $options array<string>
 * @param $params array<string>  Add [ 'multiple' => 'multiple' ] to the $params if you want to get a multi-select
 *
 * @return void
 */
function label_select( string $label, string $select_id, array $options, array $params = [] ) {
	$prepared = prepare_label_checkbox_params( $label, $params );

    $html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;

    printf(
		$html,
		$select_id,
		$label,
		create_options( $options ),
		$prepared['param_string'],
	    $prepared['label_param_string']
    );
}

function label_checkbox( $label, $input_id, $params = [] ) {
	$prepared = prepare_label_checkbox_params( $label, $params );
	
	$html = <<<'HTML'
		<div%4$s>
			<label class="mce-label fp-mce-checkbox" for="%1$s" %5$s>%2$s</label>
			<div>
				<input class="mce-checkbox" type="checkbox" id="%1$s" %3$s/>
			</div>
		</div>
HTML;

	printf(
		$html,
		$input_id,
		$prepared['label'],
		$prepared['param_string'],
		$prepared['div_id'],
		$prepared['label_param_string']
	);
}

function match_options(): string {
	global $pool;
	$options = '';
	foreach ( $pool->matches->matches as $match ) {
		$option_text = sprintf( '%d: %s - %s (%s)'
								, $match['id']
								, Football_Pool_Utils::xssafe( $match['home_team'] )
								, Football_Pool_Utils::xssafe( $match['away_team'] )
								, Football_Pool_Utils::date_from_gmt( $match['date'] )
						);
		$options .= sprintf( '<option value="%d">%s</option>', $match['id'], $option_text );
	}
	return $options;
}

function match_select_multiple( $shortcode, $params = [ 'multiple' => 'multiple' ] ) {
	$label = __( 'Match', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-match-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-match-id" style="height:100px;" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		match_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function match_select( $shortcode, $params = [] ) {
	$label = __( 'Match', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-match" %6$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-match" %5$s>
					<option value="0">%3$s</option>
					%4$s
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		__( 'Select a match', 'football-pool' ),
		match_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

/**
 * @throws Exception
 */
function question_select_multiple( $shortcode, $params = [ 'multiple' => 'multiple' ] ) {
	$label = __( 'Question', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-question-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-question-id" style="height:100px;" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		bonusquestion_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

/**
 * @throws Exception
 */
function question_select( $shortcode, $params = [] ) {
	$label = __( 'Question', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-question" %6$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-question" %5$s>
					<option value="0">%3$s</option>
					%4$s
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		__( 'Select a question', 'football-pool' ),
		bonusquestion_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function user_select_multiple( $shortcode, $params = [ 'multiple' => 'multiple' ] ) {
	$label = __( 'Select a user', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-user-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-user-id" style="height:100px;" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		user_options(),
		$prepared['param_string'],
		$prepared['label_param_string'],
	);
}

function user_select( $shortcode, $params = [] ) {
	$label = __( 'Select a user', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div%8$s>
			<label class="mce-label" for="%1$s-user-id" %9$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-user-id" %7$s>
					<optgroup label="%3$s">
						<option value="" selected="selected">%4$s</option>
					</optgroup>
					<optgroup label="%5$s">
						%6$s
					</optgroup>
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		__( 'default', 'football-pool' ),
		__( 'logged in user', 'football-pool' ),
		__( 'or choose another user', 'football-pool' ),
		user_options(),
		$prepared['param_string'],
		$prepared['div_id'],
		$prepared['label_param_string']
	);
}

function matchtype_select( $shortcode, $params = [ 'multiple' => 'multiple' ] ) {
	$label = __( 'Select one or more match types', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-matchtype-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-matchtype-id" style="height:100px;" %4$s>
					%3$s
				</select>
			</div>
		</div>
HTML;
	
	printf(
		$html,
		$shortcode,
		$label,
		matchtype_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}

function group_select( $shortcode, $params = [] ) {
	$label = __( 'Select a group', 'football-pool' );
	$prepared = prepare_label_checkbox_params( $label, $params );

	$html = <<<'HTML'
		<div>
			<label class="mce-label" for="%1$s-group-id" %5$s>%2$s</label>
			<div>
				<select class="mce-select" id="%1$s-group-id">
					<option value=""></option>
					%3$s
				</select>
			</div>
		</div>
HTML;

	printf(
		$html,
		$shortcode,
		$label,
		group_options(),
		$prepared['param_string'],
		$prepared['label_param_string']
	);
}
