<?php
define( 'PLATFORM_WORDPRESS_DIR', '/usr/src/app' );
require_once '/usr/src/dollie/wf-config.php';
// ini_set('log_errors','On');
// ini_set('display_errors','on');
// ini_set('error_reporting', E_ALL );

if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) {
	define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
}
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
	define( 'WP_DEBUG_DISPLAY', false );
}

$valid_passwords = array( 'container' => S5_APP_ID );
$valid_users     = array_keys( $valid_passwords );

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = ( in_array( $user, $valid_users ) ) && ( $pass == $valid_passwords[ $user ] );

if ( ! $validated ) {
	header( 'WWW-Authenticate: Basic realm="My Realm"' );
	header( 'HTTP/1.0 401 Unauthorized' );
	die( 'Not authorized' );
}
// user => password
$users = array( 'container' => S5_APP_ID );

// Force a short-init since we just need core WP, not the entire framework stack
// define( 'SHORTINIT', true );

// Require the wp-load.php file (which loads wp-config.php and bootstraps WordPress)
require PLATFORM_WORDPRESS_DIR . '/wp-load.php';

// Include the now instantiated global $wpdb Class for use
global $wpdb;
$wpdb->hide_errors();

/* Specific data only by request */
if ( isset( $_GET['get'] ) ) {
	$data = [];

	if ( $_GET['get'] === 'plugins' ) {

		$plugins        = get_plugins();
		$plugins_update = get_site_transient( 'update_plugins' );
		$plugins_active = get_option( 'active_plugins' );

		// Installed Plugins
		foreach ( $plugins as $k => $plugin ) {

			$path = explode( '/', $k );

			$data[] = [
				'name'    => $plugin['Name'],
				'slug'    => $path[0],
				'loader'  => $k,
				'status'  => isset( $plugins_active[ $k ] ) ? 'available' : 'none',
				'update'  => isset( $plugins_update->response[ $k ] ) ? 'available' : 'none',
				'version' => $plugin['Version'],
				'author'  => $plugin['Author'],
				'uri'     => $plugin['PluginURI'],
			];
		}
	} elseif ( $_GET['get'] === 'themes' ) {
		// Get All Themes
		$all_themes    = wp_get_themes();
		$themes_update = get_site_transient( 'update_themes' );

		foreach ( $all_themes as $theme ) {

			$data[] = [
				'name'    => $theme->get( 'Name' ),
				'slug'    => $theme->get( 'Template' ),
				'status'  => get_option( 'template' ) === $theme->get( 'Template' ) ? 'active' : 'inactive',
				'update'  => isset( $themes_update->response[ $theme->get_stylesheet() ] ) ? 'available' : 'none',
				'version' => $theme->get( 'Version' ),
				'author'  => $theme->get( 'Author' ),
				'uri'     => $theme->get( 'ThemeURI' ),
			];
		}
	}

	echo json_encode( $data, JSON_PRETTY_PRINT );
	exit;
}

// Basic Stats
$usercount = count_users();
$users     = $usercount['total_users'];
$emails    = wp_count_posts( 'log_emails_log' );
$posts     = wp_count_posts( 'post' );
$pages     = wp_count_posts( 'page' );
$sites     = wp_count_posts( 'container' );

$user_query = new WP_User_Query( array( 'role' => 'customer' ) );
// Get the total number of users for the current query. I use (int) only for sanitize.
$users_count = (int) $user_query->get_total();

// Get Plugins
$all_plugins = get_plugins();

// Get site icon
$icon     = get_option( 'site_icon' );
$url      = get_option( 'siteurl' );
$icon_url = wp_get_attachment_url( $icon );

// Get Performance Details
$opcache            = ini_get( 'opcache.enable' );
$caching            = get_option( 'platform_caching_method', 'communitycache' );
$object_cache_found = PLATFORM_WORDPRESS_DIR . '/wp-content/object-cache.php';

if ( file_exists( $object_cache_found ) ) {
	$object_cache = 'enabled';
} else {
	$object_cache = 'disabled';
}

// Get Active Theme Data
$theme_data = wp_get_theme();
$themes     = wp_get_themes();

$comments_count = wp_count_comments();

// Get All Themes
$allThemes = wp_get_themes();
foreach ( $allThemes as $theme ) {
	// print the theme title
	$installedthemes .= $theme->get( 'Name' ) . ',';
}

// Get All Plugins
$plugins   = get_plugins();
$revisions = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'" );
$updates   = wp_get_update_data();

$update_plugins = get_site_transient( 'update_plugins' );
$update_themes  = get_site_transient( 'update_themes' );

// Our Basic WP Feed
$wp_feed = array(
	'Emails'              => $emails,
	'Posts'               => $posts->publish,
	'Pages'               => $pages->publish,
	'Customers'           => $users_count,
	'Theme Name'          => $theme_data->get( 'Name' ),
	'Theme Version'       => $theme_data->get( 'Version' ),
	'Theme Template'      => $theme_data->get( 'Template' ),
	'Theme Description'   => $theme_data->get( 'Description' ),
	'Theme Author'        => $theme_data->get( 'Author' ),
	'Theme AuthorURI'     => $theme_data->get( 'AuthorURI' ),
	'Theme ThemeURI'      => $theme_data->get( 'ThemeURI' ),
	'Theme Screenshot'    => get_template_directory_uri() . '/' . $theme_data->screenshot,
	'Site Icon'           => $icon_url,
	'Plugin Details'      => $all_plugins,
	'Installed Themes'    => rtrim( $installedthemes, ',' ),
	'Installed Plugins'   => array(),
	'Active Plugins'      => array(),
	'Comments Total'      => $comments_count->total_comments,
	'Comments Moderation' => $comments_count->moderated,
	'Comments Approved'   => $comments_count->approved,
	'Comments Spam'       => $comments_count->spam,
	'Comments Trash'      => $comments_count->trash,
	'Revisions'           => $revisions,
	'Plugin Updates'      => count( $update_plugins->response ),
	'Theme Updates'       => count( $update_themes->response ),
);

// Active Plugins.
$activated_plugins = array();
foreach ( get_option( 'active_plugins' ) as $p ) {
	if ( isset( $plugins[ $p ] ) ) {
		$wp_feed['Active Plugins'][] = $plugins[ $p ]['Name'];
	}
}

// Installed Plugins.
foreach ( $plugins as $plugin ) {
	$wp_feed['Installed Plugins'][] = $plugin['Name'];
}

// BuddyPress Data.
if ( class_exists( 'BuddyPress' ) ) {

	if ( function_exists( 'bp_has_groups' ) ) {

		function pf_groups_get_total_group_count() {
			$count = wp_cache_get( 'bp_total_group_count', 'bp' );
			if ( false === $count ) {
				$count = BP_Groups_Group::get_total_group_count();
				wp_cache_set( 'bp_total_group_count', $count, 'bp' );
			}

			return $count;
		}

		function pf_groups_get_recent_groups() {
			$results = array();
			if ( bp_has_groups( 'type=newest&per_page=12&max=12' ) ) {
				while ( bp_groups() ) :
					bp_the_group();
					$results[] = array(
						'name'    => bp_get_group_name(),
						'avatar'  => bp_get_group_avatar( 'type=full&width=100&height=100' ),
						'link'    => bp_get_group_permalink(),
						'type'    => bp_get_group_type(),
						'id'      => bp_get_group_id(),
						'members' => bp_get_group_member_count(),
					);
				endwhile;
			}

			return $results;
		}

		$bp_feed = array(
			'BP Total Groups'  => pf_groups_get_total_group_count(),
			'BP Recent Groups' => pf_groups_get_recent_groups(),
		);
	}
}

// BBPress  Data.
if ( class_exists( 'BBPress' ) ) {

	$forums  = wp_count_posts( 'forum' );
	$topics  = wp_count_posts( 'topic' );
	$replies = wp_count_posts( 'reply' );

	function pf_forums_get_recent_topics() {
		if ( bbp_has_topics(
			array(
				'author'         => 0,
				'show_stickies'  => false,
				'order'          => 'DESC',
				'post_parent'    => 'any',
				'posts_per_page' => 10,
			)
		) ) {
			while ( bbp_topics() ) :
				bbp_the_topic();
				$results[] = array(
					'name'    => bbp_get_topic_title(),
					'link'    => bbp_get_topic_permalink(),
					'id'      => bbp_get_topic_id(),
					'replies' => bbp_get_topic_voice_count(),
					'author'  => bbp_get_topic_author_link(),
				);
			endwhile;
		}

		return $results;
	}

	$bb_feed = array(
		'BB Recent Topics' => pf_forums_get_recent_topics(),
		'BB Total Forums'  => $forums->publish,
		'BB Total Replies' => $replies->publish,
		'BB Total Topics'  => $topics->publish,
	);
}

if ( defined( 'DOLLIE_RUNDECK_URL' ) ) {

	if ( class_exists( 'WooCommerce' ) ) {

		function wpd_count_active_subscriptions() {

			$gp_args = array(
				'numberposts' => - 1,
				'post_type'   => 'shop_subscription', // Subscription post type
				'post_status' => 'wc-active', // Active subscription
			);

			$query = new WP_Query( $gp_args );

			$total = $query->found_posts;

			wp_reset_postdata();

			return $total;
		}

		/**
		 * Get sales report data.
		 *
		 * @return object
		 */
		function wpd_get_sales_report_data() {
			include_once PLATFORM_WORDPRESS_DIR . '/wp-content/plugins/woocommerce/includes/admin/reports/class-wc-report-sales-by-date.php';
			$sales_by_date                 = new WC_Report_Sales_By_Date();
			$sales_by_date->start_date     = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
			$sales_by_date->end_date       = strtotime( date( 'Y-m-d', current_time( 'timestamp' ) ) );
			$sales_by_date->chart_groupby  = 'day';
			$sales_by_date->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';

			return $sales_by_date->get_report_data();
		}

		/**
		 * Show status widget.
		 */
		function wpd_status_widget() {
			include_once PLATFORM_WORDPRESS_DIR . '/wp-content/plugins/woocommerce/includes/admin/reports/class-wc-admin-report.php';
			$reports     = new WC_Admin_Report();
			$report_data = wpd_get_sales_report_data();
			if ( $report_data ) {
				return $report_data;
			}
		}
	}

	if ( function_exists( 'dollie' ) ) {
		$dollie_feed = array(
			'Sales'      => wpd_status_widget(),
			'Sites'      => wp_count_posts( 'container' ),
			'Blueprints' => dollie()->count_total_blueprints(),
			// "Undeployment"  => wpd_count_undeployed_containers(),
			// "Subscriptions" => wpd_count_active_subscriptions(),
		);
	}
}

$feed = array_merge( (array) $wp_feed, (array) $bp_feed, (array) $bb_feed, (array) $dollie_feed );

echo json_encode( $feed, JSON_PRETTY_PRINT );
