<?php

namespace WPD_Platform\Services;

use WPD_Platform\Plugin;
use WPD_Platform\Singleton;

class StatsService extends Singleton {

	/**
	 * @param bool $full
	 *
	 * @return array
	 */
	public function get( $full = false ) {

		// Include the now instantiated global $wpdb Class for use.
		global $wpdb;
		$wpdb->hide_errors();

		$admin_email      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'admin_email' ) );
		$site_name        = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'blogname' ) );
		$site_description = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'blogdescription' ) );
		$caching          = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'platform_caching_method' ) );
		$last_updated     = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'platform_last_update' ) );
		$login_time       = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'wpd_last_login_activity' ) );

		/**
		 * Get users with administrator role
		 */
		$sql_admin = "
            SELECT ID, display_name
            FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta}
            ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id
            WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities'
            AND {$wpdb->usermeta}.meta_value LIKE '%administrator%'
            ORDER BY {$wpdb->users}.ID
        ";

		$admin_user_ids = $wpdb->get_col( $sql_admin );
		$admin_user     = null;

		if ( isset( $admin_user_ids[0] ) && ! empty( $admin_user_ids[0] ) ) {
			$admin_user = $wpdb->get_results( "SELECT user_login, user_email FROM $wpdb->users WHERE id = $admin_user_ids[0]" );
		}

		/**
		 * Get user with editor role
		 */
		$sql_editor = "
            SELECT ID, display_name
            FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta}
            ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id
            WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities'
            AND {$wpdb->usermeta}.meta_value LIKE '%editor%'
            ORDER BY {$wpdb->users}.ID
        ";

		$editor_user_ids = $wpdb->get_col( $sql_editor );
		$editor_user     = null;

		if ( isset( $editor_user_ids[0] ) && ! empty( $editor_user_ids[0] ) ) {
			$editor_user = $wpdb->get_results( "SELECT user_login, user_email FROM $wpdb->users WHERE id = $editor_user_ids[0]" );
		}

		$all_roles = wp_roles()->roles;
		$users_by_role = [];

		foreach ( $all_roles as $role_key => $role_info ) {
			$users = get_users([
				'role' => $role_key,
				'number' => 1,
			]);

			if ( ! empty( $users ) ) {
				$users_by_role[ $role_key ] = [
					'username' => $users[0]->user_login,
					'email' => $users[0]->email,
				];
			}
		}


		/**
		 * Plugins
		 */

		$plugins_updates      = 0;
		$plugins              = [];
		$plugins_auto_updates = (array) get_site_option( 'auto_update_plugins', array() );

		if ( $full === true ) {
			if ( ! function_exists( 'get_plugin_updates' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
			}

			wp_update_plugins();

			$plugins_update = get_site_transient( 'update_plugins' );
			$plugins_active = get_option( 'active_plugins' );

			foreach ( get_plugins() as $full_path => $plugin ) {
				$path   = explode( '/', $full_path );
				$slug   = $path[0];
				$update = false;

				if ( isset( $plugins_update->response[ $full_path ] ) ) {
					$plugins_updates ++;
					$update = true;
				}

				$plugins[] = [
					'name'        => $plugin['Name'],
					'slug'        => $slug,
					'loader'      => $full_path,
					'active'      => in_array( $full_path, $plugins_active, true ),
					'update'      => $update,
					'auto_update' => in_array( $full_path, $plugins_auto_updates, true ),
					'version'     => $plugin['Version'],
					'author'      => $plugin['Author'],
					'uri'         => $plugin['PluginURI'],
				];
			}
		}

		/**
		 * Themes
		 */

		$themes_updates      = 0;
		$themes              = [];
		$active_theme        = [];
		$themes_auto_updates = (array) get_site_option( 'auto_update_themes', array() );

		if ( $full === true ) {
			wp_update_themes();

			$theme_data = wp_get_theme();

			$active_theme = [
				'name'        => $theme_data->get( 'Name' ),
				'version'     => $theme_data->get( 'Version' ),
				'template'    => $theme_data->get( 'Template' ),
				'description' => $theme_data->get( 'Description' ),
				'author'      => $theme_data->get( 'Author' ),
				'author_url'  => $theme_data->get( 'AuthorURI' ),
				'uri'         => $theme_data->get( 'ThemeURI' ),
				'screenshot'  => get_template_directory_uri() . '/' . $theme_data->screenshot,
			];

			$themes_update = get_site_transient( 'update_themes' );

			foreach ( wp_get_themes() as $slug => $theme ) {
				if ( isset( $themes_update->response[ $theme->get_stylesheet() ] ) ) {
					$themes_updates ++;
				}

				$themes[] = [
					'name'        => $theme->get( 'Name' ),
					'slug'        => $slug,
					'active'      => get_option( 'template' ) === $theme->get_template(),
					'update'      => isset( $themes_update->response[ $theme->get_stylesheet() ] ),
					'auto_update' => in_array( $slug, $themes_auto_updates, true ),
					'version'     => $theme->get( 'Version' ),
					'author'      => $theme->get( 'Author' ),
					'uri'         => $theme->get( 'ThemeURI' ),
				];
			}
		}

		/**
		 * Stats
		 */

		$stats = [
			'posts_count'         => 0,
			'pages_count'         => 0,
			'users_count'         => 0,
			'comments_total'      => 0,
			'comments_moderation' => 0,
			'comments_approved'   => 0,
			'comments_spam'       => 0,
			'comments_trash'      => 0,
		];

		if ( $full === true ) {
			$comments = wp_count_comments();

			$stats = [
				'posts_count'         => wp_count_posts( 'post' )->publish,
				'pages_count'         => wp_count_posts( 'page' )->publish,
				'users_count'         => count_users()['total_users'],
				'comments_total'      => $comments->total_comments,
				'comments_moderation' => $comments->moderated,
				'comments_approved'   => $comments->approved,
				'comments_spam'       => $comments->spam,
				'comments_trash'      => $comments->trash,
			];
		}

		/**
		 * WP Core
		 */

		$wp_core = [];

		$update_path = PLATFORM_WORDPRESS_DIR . '/wp-admin/includes/update.php';

		if ( $full === true && file_exists( $update_path ) ) {
			$wp_core     = get_core_updates();
			$site_admins = get_super_admins();
		} else {
			$site_admins = false;
		}

		global $wp_version;

		/**
		 * Response object
		 */
		return [
			'secret'       => Plugin::instance()->get_host()->get_secret(),
			'token'        => Plugin::instance()->get_host()->is_type_internal() ? S5_APP_TOKEN : Plugin::instance()->get_host()->get_token(),
			'php'          => PHP_VERSION,
			'size'         => $this->folderSize( PLATFORM_WORDPRESS_DIR ),
			'last_updated' => isset( $last_updated->option_value ) ?? null,
			'login_time'   => isset( $login_time->option_value ) ?? null,
			'cache'        => [
				'method'       => isset( $caching->option_value ) ?? null,
				'op_cache'     => ini_get( 'opcache.enable' ),
				'object_cache' => file_exists( PLATFORM_WORDPRESS_DIR . '/wp-content/object-cache.php' ),
			],
			'site'         => [
				'name'         => $site_name->option_value,
				'description'  => $site_description->option_value,
				'stats'        => $stats,
				'admin'        => [
					'username' => $admin_user && isset( $admin_user[0] ) ? $admin_user[0]->user_login : '',
					'email'    => $admin_user && isset( $admin_user[0] ) ? $admin_user[0]->user_email : '',
				],
				'editor'       => [
					'username' => $editor_user && isset( $editor_user[0] ) ? $editor_user[0]->user_login : '',
					'email'    => $editor_user && isset( $editor_user[0] ) ? $editor_user[0]->user_email : '',
				],
				'roles' => $users_by_role,
				'other_admins' => get_super_admins(),
				'multisite'    => is_multisite(),
				'wp_version'   => $wp_version,
				'plugins'      => $plugins,
				'theme'        => $active_theme,
				'themes'       => $themes,
				'updates'      => [
					'themes'  => $themes_updates,
					'plugins' => $plugins_updates,
					'core'    => $wp_core,
				],
			],
		];
	}

	/**
	 * @param $dir
	 *
	 * @return int
	 */
	public function folderSize( $dir ) {
		$size  = 0;
		$paths = glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT );

		if ( $paths !== false ) {
			foreach ( $paths as $each ) {
				$size += is_file( $each ) ? filesize( $each ) : $this->folderSize( $each );
			}
		}

		return $size;
	}

}
