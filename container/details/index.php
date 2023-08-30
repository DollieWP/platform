<?php
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

function wpd_platform_base_dir() {
	$path = dirname( __FILE__ );
	while ( true ) {
		if ( file_exists( $path . '/wp-config.php' ) ) {
			return $path . '/';
		}
		$path = dirname( $path );
	}
}
// Require the wp-load.php file (which loads wp-config.php and bootstraps WordPress)
require wpd_platform_base_dir() . '/wp-load.php';

if ( ! isset( $_GET['full'] ) ) {
	define( 'SHORTINIT', true );
}

// Restrict access to page.
\WPD_Platform\Plugin::instance()->get_host()->base_access();

// Include the now instantiated global $wpdb Class for use
global $wpdb;
$wpdb->hide_errors();

$admin_email      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'admin_email' ) );
$insights         = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'platform_enable_insights' ) );
$domain           = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'siteurl' ) );
$site_name        = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'blogname' ) );
$site_description = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'blogdescription' ) );
$caching          = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'platform_caching_method' ) );
$login_time       = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'wpd_last_login_activity' ) );

if ( defined( 'WP_INSTALL_RESTRICTED' ) ) {
	$restricted = true;
} else {
	$restricted = false;
}

$staging_last_seen = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'wpd_staging_last_seen' ) );
if ( $staging_last_seen ) {
	$staging_last_seen = $staging_last_seen->option_value;
}

// Get users with specified roles.

$roles = array_map( 'trim', explode( ',', 'administrator' ) );
$sql   = '
    SELECT  ID, display_name
    FROM        ' . $wpdb->users . ' INNER JOIN ' . $wpdb->usermeta . '
    ON          ' . $wpdb->users . '.ID             =       ' . $wpdb->usermeta . '.user_id
    WHERE       ' . $wpdb->usermeta . '.meta_key        =       \'' . $wpdb->prefix . 'capabilities\'
    AND     (
';
$i     = 1;
foreach ( $roles as $role ) {
	$sql .= ' ' . $wpdb->usermeta . '.meta_value    LIKE    \'%"' . $role . '"%\' ';
	if ( $i < count( $roles ) ) {
		$sql .= ' OR ';
	}

	$i ++;
}
$sql       .= ' ) ';
$sql       .= ' ORDER BY display_name ';
$userIDs    = $wpdb->get_col( $sql );
$admin_user = $wpdb->get_results( "SELECT user_login FROM $wpdb->users WHERE id = $userIDs[0]" );

// Get Users.
$user_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );

// Get Performance Details.
$opcache = ini_get( 'opcache.enable' );

// $caching = get_option('platform_caching_method', 'communitycache');
$object_cache_found = PLATFORM_WORDPRESS_DIR . '/wp-content/object-cache.php';

if ( file_exists( $object_cache_found ) ) {
	$object_cache = 'enabled';
} else {
	$object_cache = 'disabled';
}

function folderSize( $dir ) {
	$size = 0;
	foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $each ) {
		$size += is_file( $each ) ? filesize( $each ) : folderSize( $each );
	}

	return $size;
}

// Our Basic WP Feed.
$wp_feed = array(
	'Plan'           => S5_PLAN,
	'App ID'         => 'container',
	'Secret'         => \WPD_Platform\Plugin::instance()->get_host()->get_token(),
	'Token'          => \WPD_Platform\Plugin::instance()->get_host()->get_token(),
	'Customer ID'    => S5_CUSTOMER_ID,
	'Customer Email' => S5_EMAIL,
	'Restricted'     => $restricted,
	'OPCache'        => $opcache,
	'Members'        => $user_count,
	'Object Cache'   => $object_cache,
	'Caching'        => $caching->option_value,
	'Url'            => $domain->option_value,
	'Admin'          => $admin_user[0]->user_login,
	'Admin Email'    => $admin_email->option_value,
	'Insights'       => $insights->option_value,
	'PHP Version'    => PHP_VERSION,
	'Name'           => $site_name->option_value,
	'Description'    => $site_description->option_value,
	'Domain'         => $domain->option_value,
	'Login'          => $login_time->option_value,
	'Last Seen'      => $staging_last_seen,
	'Size'           => folderSize( wpd_platform_base_dir() ),
	'Multisite'      => is_multisite(),
	'Version'        => $wp_version,
);

echo json_encode( $wp_feed, JSON_PRETTY_PRINT );
