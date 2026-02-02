<?php
/**
 * Plugin Name:       Simple Page Access Restriction
 * Plugin URI:        https://www.pluginsandsnippets.com/downloads/simple-page-access-restriction/
 * Description:       This plugin offers a simple way to restrict visits to select pages only to logged-in users and allows for page redirection to a defined (login) page of your choice.
 * Version:           1.0.34
 * Author:            Plugins & Snippets
 * Author URI:        https://www.pluginsandsnippets.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-page-access-restriction
 * Requires at least: 3.9
 * Tested up to:      6.8
 *
 * @package           Simple_Page_Access_Restriction
 * @author            PluginsandSnippets.com
 * @copyright         All rights reserved Copyright (c) 2022, PluginsandSnippets.com
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Simple_Page_Access_Restriction' ) ) {

	/**
	 * Main Simple_Page_Access_Restriction class
	 *
	 * @since       1.0.0
	 */
	class Simple_Page_Access_Restriction {

		/**
		 * @var         Simple_Page_Access_Restriction $instance The one true Simple_Page_Access_Restriction
		 * @since       1.0.0
		 */
		private static $instance;
		private static $admin_instance;

		public function __construct() {}

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      object self::$instance The one true Simple_Page_Access_Restriction
		 */
		public static function instance() {

			if ( ! self::$instance ) {
				self::$instance = new Simple_Page_Access_Restriction();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				
				self::$instance->hooks();

				if ( is_admin() ) {
					self::$admin_instance = new Simple_Page_Access_Restriction_Admin();
				}
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_constants() {

			// Plugin related constants
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_VER', '1.0.34' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_NAME', 'Simple Page Access Restriction' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_URL', plugin_dir_url( __FILE__ ) );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_FILE', __FILE__ );

			// Action links constants
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_DOCUMENTATION_URL', 'https://www.pluginsandsnippets.com/' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_OPEN_TICKET_URL', 'https://www.pluginsandsnippets.com/open-ticket/' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_SUPPORT_URL', 'https://www.pluginsandsnippets.com/support/' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_REVIEW_URL', 'https://wordpress.org/plugins/simple-page-access-restriction/#reviews' );

			// Licensing related constants
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_API_URL', 'https://www.pluginsandsnippets.com/' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_PURCHASES_URL', 'https://www.pluginsandsnippets.com/purchases/' );
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_STORE_PRODUCT_ID', 00 );

			// Endpoint for Receiving Subscription Requests
			define( 'SIMPLE_PAGE_ACCESS_RESTRICTION_SUBSCRIBE_URL', 'https://www.pluginsandsnippets.com/?ps-subscription-request=1' );
		}

		public static function get_admin_instance() {
			return self::$admin_instance;
		}

		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			if ( is_admin() ) {
				require_once SIMPLE_PAGE_ACCESS_RESTRICTION_DIR . 'includes/admin/admin.php';
			}

			require_once SIMPLE_PAGE_ACCESS_RESTRICTION_DIR . 'includes/functions.php';
			require_once SIMPLE_PAGE_ACCESS_RESTRICTION_DIR . 'includes/class-redirection.php';
		}

		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 *
		 */
		private function hooks() {
			add_action( 'init', array( $this, 'add_rest_api_filters' ) );
			add_filter( 'pre_get_posts', array( $this, 'exclude_restricted_posts' ) );
			add_action( 'template_redirect', array( $this, 'check_page_access' ), 1 );
		}

		/**
		 * Change the GET and REST search queries
		 * to exclude restricted posts.
		 *
		 * @param WP_Query $query The query.
		 * @return WP_Query The query.
		 */
		public function exclude_restricted_posts( $query ) {
			// Check if the request is for the backend.
			if ( is_admin() ) {
				// Return the query.
				return $query;
			}

			// Set the restrict.
			$restrict = false;

			// Check if the request is for the main search query.
			if ( $query->is_search && $query->is_main_query() ) {
				// Set the restrict.
				$restrict = true;
			}

			// Check if the request is a REST request.
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $query->query_vars['s'] ) ) {
				// Set the restrict.
				$restrict = true;
			}

			// Check the restrict.
			if ( $restrict ) {
				// Set the meta_query.
				$meta_query = array(
					'relation' => 'OR',
					array(
						array(
							'key'     => 'page_access_restricted',
							'value'   => '0',
							'compare' => '=',
						),
					),
					array(
						'key'     => 'page_access_restricted',
						'compare' => 'NOT EXISTS',
					),
				);

				// Set the query.
				$query->set( 'meta_query', $meta_query );
			}

			// Return the query.
			return $query;
		}

		/**
		 * Checks if current request is for a restricted page
		 * If Yes, and Current User is not logged in then redirects
		 * user to configured Login Page or to Homepage (if not cofigured)
		 * 
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function check_page_access() {
			$settings = ps_simple_par_get_settings();

			if (
				! is_user_logged_in() &&
				(
					( ( is_page() || is_singular() ) && ps_simple_par_is_page_restricted( get_queried_object_id() ) ) ||
					( function_exists( 'is_shop' ) && is_shop() && ps_simple_par_is_page_restricted( get_option( 'woocommerce_shop_page_id' ) ) ) || 
					( is_array( $settings['taxonomies'] ) && ! empty( $settings['taxonomies'] ) && (
					is_tax( $settings['taxonomies'] ) ||
					( in_array( 'category', $settings['taxonomies'], true ) && is_category() ) ||
					( in_array( 'post_tag', $settings['taxonomies'], true ) && is_tag() )
				) )
				)
			) {
				
				$redirect_url = '';
				if ( 'url' === $settings['redirect_type'] && ! empty( $settings['redirect_url'] ) ) {
					$redirect_url = $settings['redirect_url'];
				} elseif ( 'page' === $settings['redirect_type'] && ! empty( $settings['login_page'] ) && ! ps_simple_par_is_page_restricted( $settings['login_page'] ) ) {
					$redirect_url = get_permalink( $settings['login_page'] );
				}

				if ( empty( $redirect_url ) ) {
					$redirect_url = home_url( '/' );
				}

				if ( ! empty( $settings['redirect_parameter'] ) ) {
					// Remove unintentional '?' from the parameter name.
					$settings['redirect_parameter'] = str_replace( '?', '', $settings['redirect_parameter'] );
					
					$redirect_url = add_query_arg( $settings['redirect_parameter'], urlencode( home_url() . $_SERVER['REQUEST_URI'] ), $redirect_url );
				}

				// Checks if headers have been sent.
				if ( ! headers_sent() ) {
					// Set headers to prevent caching.
					nocache_headers();
				}

				wp_redirect( apply_filters( 'ps_simple_par_redirect_url', $redirect_url ) );
				exit;
			}
		}

		/**
		 * Add the filters for REST API requests related to enabled post types.
		 */
		public function add_rest_api_filters() {
			// Check if there is a logged-in user.
			if ( is_user_logged_in() ) {
				return;
			}

			// Get the settings.
			$settings = ps_simple_par_get_settings();

			// Get the post types.
			$post_types = $settings['post_types'];

			// Loop through the post types.
			foreach ( $post_types as $post_type ) {
				// Add the filters.
				add_filter( "rest_{$post_type}_query", array( $this, 'filter_rest_query' ) );
				add_filter( "rest_prepare_{$post_type}", array( $this, 'filter_rest_response' ), 10, 2 );
			}
		}

		/**
		 * Filter the query arguments for a post type in a REST API request.
		 *
		 * @param array $args The arguments.
		 * @return array The arguments.
		 */
		public function filter_rest_query( $args ) {
			// Set the meta query.
			$meta_query = array(
				'relation' => 'OR',
				array(
					'compare' => '!=',
					'key'     => 'page_access_restricted',
					'value'   => '1',
				),
				array(
					'compare' => 'NOT EXISTS',
					'key'     => 'page_access_restricted',
				),
			);

			// Check the args.
			if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
				// Set the args.
				$args['meta_query'] = array();
			}

			// Set the args.
			$args['meta_query'] = array_merge( $args['meta_query'], $meta_query );

			// Return the args.
			return $args;
		}

		/**
		 * Filter the response for a post type in a REST API request.
		 *
		 * @param WP_REST_Response $response The response.
		 * @param WP_Post $post The post.
		 * @return WP_REST_Response The response.
		 */
		public function filter_rest_response( $response, $post ) {
			// Check the post.
			if ( ps_simple_par_is_page_restricted( $post->ID ) ) {
				// Set the response.
				$response = new WP_REST_Response( null, 403 );
			}

			// Return the response.
			return $response;
		}

		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = SIMPLE_PAGE_ACCESS_RESTRICTION_DIR . '/languages/';
			$lang_dir = apply_filters( 'plugin_template_ps_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'simple-page-access-restriction' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'simple-page-access-restriction', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/simple-page-access-restriction/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/simple-page-access-restriction/ folder
				load_textdomain( 'simple-page-access-restriction', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/simple-page-access-restriction/languages/ folder
				load_textdomain( 'simple-page-access-restriction', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'simple-page-access-restriction', false, $lang_dir );
			}
		}

	}   
}

/**
 * The main function responsible for returning the one true Simple_Page_Access_Restriction
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \Simple_Page_Access_Restriction The one true Simple_Page_Access_Restriction
 */
function ps_simple_par_get_instance() {
	return Simple_Page_Access_Restriction::instance();
}
add_action( 'plugins_loaded', 'ps_simple_par_get_instance' );
