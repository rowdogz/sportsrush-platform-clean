
 1
 2
 3
 4
 5
 6
 7
 8
 9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
<?php
/**
 * Plugin Name: Football Pool Ranking Template Extension
 * Description: Change the template for a row in the ranking.
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

add_filter( 'plugins_loaded', array( 'FootballPoolExtensionRankingTemplate', 'init_extension' ) );

class FootballPoolExtensionRankingTemplate {
	public static function init_extension() {
		// Display a message if the Football Pool plugin is not activated.
		if ( ! class_exists( 'Football_Pool' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'no_fp_plugin_error' ) );
			return;
		}
		
		// Change the template.
		add_filter( 'footballpool_ranking_ranking_row_template', array( __CLASS__, 'change_template' ), null, 2 );
	}
	
	public static function change_template( $all_user_view, $type ) {
		// Both views now return the same template, so the 'if' statement is a bit useless. But I left it here
		// for people who do want to learn how to differentiate between the two views.
		if ( $all_user_view ) {
			$ranking_template = '<tr class="%css_class% jokers-used-%jokers_used%">
									<td class="user-rank">%rank%.</td>
									<td class="user-name"><a href="%user_link%">%user_name%</a></td>
									<td class="user-score ranking score">%points%</td>
									<td class="user-league">%league_image%</td>
									</tr>';
		} else {
			$ranking_template = '<tr class="%css_class% jokers-used-%jokers_used%">
									<td class="user-rank">%rank%.</td>
									<td class="user-name"><a href="%user_link%">%user_name%</a></td>
									<td class="user-score ranking score">%points%</td>
									<td class="user-league">%league_image%</td>
									</tr>';
		}
		
		return $ranking_template;
	}
	
	public static function no_fp_plugin_error() {
		$plugin_data = get_plugin_data( __FILE__ );
		$plugin_name = isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : __CLASS__;
		echo "<div class='error'><p>The Football Pool plugin is not activated. "
			, "Make sure you activate it so the extension plugin '{$plugin_name}' has some use.</p></div>";
	}
}
