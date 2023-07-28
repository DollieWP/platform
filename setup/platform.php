<?php
/**
 * Hide our platform plugins from the Plugin Dashboard
 *
 * @since 1.0.0
 *
 */
function pf_hide_core_plugins() {
	global $wp_list_table;
	$hidearr   = array(
		//'sar-friendly-smtp/sar-friendly-smtp.php',
		//'platform-dashboard/platform-dashboard.php'
		//'worker/init.php',
	);
	$myplugins = $wp_list_table->items;
	foreach ( $myplugins as $key => $val ) {
		if ( in_array( $key, $hidearr ) ) {
			unset( $wp_list_table->items[ $key ] );
		}
	}
}

add_action( 'pre_current_active_plugins', 'pf_hide_core_plugins' );


class PFSameSiteCookieSetter {
	static private $_is_browser_compatible = array();

	/*
	   * sets cookie
	   * setcookie ( string $name [, string $value = "" [, array $options = [] ]] ) : bool
	   * setcookie signature which comes with php 7.3.0
	   * supported $option keys: expires, path, domain, secure, httponly and samesite
	   * possible $option[samesite] values: None, Lax or Strict
	   */
	public static function setcookie( $name, $value = "", $options = array() ) {
		$same_site  = isset( $options['samesite'] ) ? $options['samesite'] : '';
		$is_secure  = isset( $options['secure'] ) ? boolval( $options['secure'] ) : false;
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if ( version_compare( '7.3.0', phpversion() ) == 1 ) {
			unset( $options['samesite'] );
			unset( $options['secure'] );

			$expires     = isset( $options['expires'] ) ? $options['expires'] : 0;
			$path        = isset( $options['path'] ) ? $options['path'] : '';
			$domain      = isset( $options['domain'] ) ? $options['domain'] : '';
			$is_httponly = isset( $options['httponly'] ) ? boolval( $options['httponly'] ) : false;

			$result = setcookie( $name, $value, $expires, $path, $domain );

			if ( self::isBrowserPFSameSiteCompatible( $user_agent ) ) {
				$new_headers  = array();
				$headers_list = array_reverse( headers_list() );
				$is_modified  = false;
				foreach ( $headers_list as $_header ) {
					if ( ! $is_modified && strpos( $_header, 'Set-Cookie: ' . $name ) === 0 ) {
						$additional_labels = array();

						$is_secure = ( $same_site == 'None' ? true : $is_secure );

						$new_label = '; HttpOnly';
						if ( $is_httponly && strpos( $_header, $new_label ) === false ) {
							$additional_labels[] = $new_label;
						}

						$new_label = '; Secure';
						if ( $is_secure && strpos( $_header, $new_label ) === false ) {
							$additional_labels[] = $new_label;
						}

						$new_label = '; PFSameSite=' . $same_site;
						if ( strpos( $_header, $new_label ) === false ) {
							$additional_labels[] = $new_label;
						}

						$_header     = $_header . implode( '', $additional_labels );
						$is_modified = true;
					}
					$new_headers[] = $_header;
				}

				header_remove();
				$new_headers = array_reverse( $new_headers );
				foreach ( $new_headers as $_header ) {
					header( $_header, false );
				}
			}
		} else {
			if ( self::isBrowserPFSameSiteCompatible( $user_agent ) == false ) {
				$same_site = '';
			}
			$is_secure = ( $same_site == 'None' ? true : $is_secure );

			$options['samesite'] = $same_site;
			$options['secure']   = $is_secure;

			$result = setcookie( $name, $value, $options );
		}

		return $result;
	}

	private static function _setIsBrowserCompatible( $user_agent_key, $value ) {
		self::$_is_browser_compatible[ $user_agent_key ] = $value;
	}

	private static function _getIsBrowserCompatible( $user_agent_key ) {
		if ( isset( self::$_is_browser_compatible[ $user_agent_key ] ) ) {
			return self::$_is_browser_compatible[ $user_agent_key ];
		}

		return null;
	}

	public static function isBrowserPFSameSiteCompatible( $user_agent ) {
		$user_agent_key = md5( $user_agent );
		$self_check     = self::_getIsBrowserCompatible( $user_agent_key );
		if ( $self_check !== null ) {
			return $self_check;
		}

		// check Chrome
		$regex = '#(CriOS|Chrome)/([0-9]*)#';
		if ( preg_match( $regex, $user_agent, $matches ) == true ) {
			$version = $matches[2];
			if ( $version < 67 ) {
				self::_setIsBrowserCompatible( $user_agent_key, false );

				return false;
			}
		}

		// check iOS
		$regex = '#iP.+; CPU .*OS (\d+)_\d#';
		if ( preg_match( $regex, $user_agent, $matches ) == true ) {
			$version = $matches[1];
			if ( $version < 13 ) {
				self::_setIsBrowserCompatible( $user_agent_key, false );

				return false;
			}
		}

		// check MacOS 10.14
		$regex = '#Macintosh;.*Mac OS X (\d+)_(\d+)_.*AppleWebKit#';
		if ( preg_match( $regex, $user_agent, $matches ) == true ) {
			$version_major = $matches[1];
			$version_minor = $matches[2];
			if ( $version_major == 10 && $version_minor == 14 ) {
				// check Safari
				$regex = '#Version\/.* Safari\/#';
				if ( preg_match( $regex, $user_agent ) == true ) {
					self::_setIsBrowserCompatible( $user_agent_key, false );

					return false;
				}
				// check Embedded Browser
				$regex = '#AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)#';
				if ( preg_match( $regex, $user_agent ) == true ) {
					self::_setIsBrowserCompatible( $user_agent_key, false );

					return false;
				}
			}
		}

		// check UC Browser
		$regex = '#UCBrowser/(\d+)\.(\d+)\.(\d+)#';
		if ( preg_match( $regex, $user_agent, $matches ) == true ) {
			$version_major = $matches[1];
			$version_minor = $matches[2];
			$version_build = $matches[3];
			if ( $version_major == 12 && $version_minor == 13 && $version_build == 2 ) {
				self::_setIsBrowserCompatible( $user_agent_key, false );

				return false;
			}
		}

		self::_setIsBrowserCompatible( $user_agent_key, true );

		return true;
	}
}

// display custom admin notice
function pf_community_restore_notice() {
	global $current_user;
	if ( current_user_can( 'manage_options' ) && get_option( 'platform_restore_notice' ) === 'yes' ) {
		?>
        <div class="notice platform-notice">
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    swal("Your Site was succesfully restored!", "When you see this message it means your site was successfully restored.", "success")
                });
            </script>
            <h2>Your Site was succesfully restored!</h2>
            <p>When you see this message it means your site was successfully restored.
                <br>
            </p>
        </div>
		<?php
		update_option( 'platform_restore_notice', '' );
	}
}

add_action( 'admin_notices', 'pf_community_restore_notice' );


function pf_last_update() {
	$date = new DateTime();
	update_option( 'platform_last_update', $date->getTimestamp() );
}

add_action( 'upgrader_process_complete', 'pf_last_update', 10, 2 );


// display custom admin notice
function pf_migration_notice() {
	global $current_user;
	$pathpluginurl = WP_PLUGIN_DIR . '/dollie/loader.php';

	$is_dollie = file_exists( $pathpluginurl );

	if ( current_user_can( 'manage_options' ) && is_plugin_active( 'migrate-guru/migrateguru.php' ) && $is_dollie && ! get_option( 'platform_migration_notice' ) ) {
		activate_plugin( 'dollie/loader.php' );
		?>
        <div class="notice platform-notice">
            <script type="text/javascript">
                jQuery(function () {
                    swal("You Have Copied Your Site to Dollie Cloud!", "You can now start working on your platform without touching your live site! Please reload this page to continue setting up your Hub.", "success")
                });
            </script>
            <h2>You Have Copied Your Site to Dollie Cloud!</h2>
            <p>You can now start working on your platform without touching your live site! Please reload this page to
                continue setting up your Hub.
                <br>
            </p>
        </div>
		<?php
		update_option( 'platform_migration_notice', 'done' );
	}
}

add_action( 'admin_notices', 'pf_migration_notice' );

function pf_notice_styles() {
	if (
		! get_option( 'platform_enable_insights' ) ||
		get_option( 'platform_restore_notice' ) == 'yes' ||
		! get_option( 'platform_community_cache_notice' ) ||
		! get_option( 'platform_rocket_notice' )
	) {
		$css = "
    .platform-notice {
      padding: 20px 20px 40px;
      color: #fff;
      font-weight: 400;
      font-size: 105%;
      background: #414951;
      background-size: cover;
      background-position: bottom 0 right 0;
      border: 0; /* reset default notice border */
      border-bottom: 10px solid #44c0a9;
      border-radius: 4px;
    }

    .platform-notice ul {
      background: rgba(48, 164, 136, 0.69) none repeat scroll 0 0;
      border-radius: 4px;
      list-style: outside none none;
      margin-bottom: 15px;
      margin-top: 15px;
      padding: 15px;
      width: 45%;
    }

    .platform-notice ul li {
      font-weight: 600;
       margin-bottom: 6px;
       padding: 4px 6px 4px 0;
    }

    .platform-notice h2,
    .platform-notice p {
      color: #DDD;
      font-size: 100%;
    }

    .platform-notice h2 {
      color: #fff;
      font-size: 24px;
    }

    .platform-notice small a {
      color: #fff;
    }

    .platform-notice a.button.button-primary{
      padding: 0px 12px !important;
    line-height: 140%;
    height: 34px;
    padding-top: 7px !important;
    }


    .platform-notice .nag-action {
      display: inline-block;
      position: absolute;
      bottom: 0;
      left: 20px;
      padding: 12px 15px;
      background-color: #44c0a9;
      border-radius: 4px 4px 0 0;
      color: #fff;
      font-size: 80%;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      text-decoration: none;
    }
  ";
		wp_add_inline_style( 'wp-admin', $css );
	}
}

add_action( 'admin_init', 'pf_notice_styles' );

/**
 * Save last login activity for admins.
 *
 */
function pf_last_login_activity( $user_login, $user ) {

	if ( user_can( $user, 'manage_options' ) ) {
		update_option( 'wpd_last_login_activity', time() );
	}
}

add_action( 'wp_login', 'pf_last_login_activity', 10, 2 );

$site_url = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if ( strpos( $site_url, 'dollie.io' ) ) {
	add_filter( 'admin_email_check_interval', '__return_false' );
	//Disable plugin auto-update email notification
	add_filter( 'auto_plugin_update_send_email', '__return_false' );

	//Disable theme auto-update email notification
	add_filter( 'auto_theme_update_send_email', '__return_false' );
}

function pf_code_editor_access() {
	if ( current_user_can( 'manage_options' ) ) {
		PFSameSiteCookieSetter::setcookie( 'wordpress_allow_code_editor', 'yes', array(
			'path'     => '/',
			'httponly' => false,
			'secure'   => true,
			'samesite' => 'None'
		) );
	}

	$plugin_cache                      = get_option( 'powered_cache_settings' );
	$plugin_cache['gzip_compression']  = 1;
	// $plugin_cache['enable_page_cache'] = 1;
	update_option( 'powered_cache_settings', $plugin_cache );

	// update_option(DB_VERSION_OPTION_NAME, POWERED_CACHE_DB_VERSION);
}

add_action( 'admin_init', 'pf_code_editor_access' );

function pf_revoke_code_editor() {
	setcookie( 'wordpress_allow_code_editor', null, - 1, '/' );
}

add_action( 'wp_logout', 'pf_revoke_code_editor' );

add_filter( 'gettext', 'pf_replace_strings' );
add_filter( 'ngettext', 'pf_replace_strings' );
function pf_replace_strings( $translated ) {
	$words      = array(
		// 'word to translate' => 'translation'
		'Powered Cache'                 => 'Caching',
		'PoweredCache'                  => 'Caching',
		'Limit Login Attempts Reloaded' => 'Login Security',
		'Login Security'                => 'Login Security'
	);
	$translated = str_ireplace( array_keys( $words ), $words, $translated );

	return $translated;
}


add_action( 'admin_head', 'pf_custom_css' );
function pf_custom_css() { ?>
    <style>
        .toplevel_page_powered-cache #extension-varnish,
        .toplevel_page_powered-cache #extension-ga,
        .toplevel_page_powered-cache #extension-fb-pixel,
        .toplevel_page_powered-cache .sui-footer,
        .toplevel_page_powered-cache footer, #llar-header-upgrade-message,
        .dashboard-section-1 .info-box-3,
        #llar-apps-accordion {
            display: none;
        }
    </style>
<?php }

add_action( 'admin_notices', 'pf_show_platform_cache_notice' );

function pf_show_platform_cache_notice() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$incompatible_plugin = false;
	$plugins             = array(
		//'hummingbird-performance'           => 'hummingbird-performance/wp-hummingbird.php',
		'hyper-cache'                       => 'hyper-cache/plugin.php',
		'hyper-cache-extended'              => 'hyper-cache-extended/plugin.php',
		'wp-fast-cache'                     => 'wp-fast-cache/wp-fast-cache.php',
		'flexicache'                        => 'flexicache/wp-plugin.php',
		'wp-http-compression'               => 'wp-http-compression/wp-http-compression.php',
		'wordpress-gzip-compression'        => 'wordpress-gzip-compression/ezgz.php',
		'gzip-ninja-speed-compression'      => 'gzip-ninja-speed-compression/gzip-ninja-speed.php',
		'speed-booster-pack'                => 'speed-booster-pack/speed-booster-pack.php',
		'wp-performance-score-booster'      => 'wp-performance-score-booster/wp-performance-score-booster.php',
		'check-and-enable-gzip-compression' => 'check-and-enable-gzip-compression/richards-toolbox.php',
	);

	$plugins_message = '';
	foreach ( $plugins as $plugin ) {
		if ( is_plugin_active( $plugin ) ) {
			$incompatible_plugin = true;
			$plugin_data         = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin );
			$plugins_message     .= '<li><strong>' . esc_attr( $plugin_data['Name'] ) . '</strong><br><br> <a href="' . esc_url_raw( wp_nonce_url( admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $plugin ) ), 'deactivate-plugin_' . $plugin ) ) . '" class="button-secondary">' . esc_html__( 'Deactivate', 'powered-cache' ) . '</a></li>';
		}
	}

	if ( $incompatible_plugin && current_user_can( 'activate_plugins' ) ) {
		$err_msg = '<div class="error">';
		$err_msg .= '<p>'
		            . esc_html__( 'We found a caching plugin that we do not recommend using on our platform. We advise you to disable this plugin so that our built-in platform caching can take over.', 'powered-cache' )
		            . '</p>';
		$err_msg .= '<ul class="incompatible-plugin-list">';
		$err_msg .= $plugins_message;
		$err_msg .= '</ul>';
		$err_msg .= '</div>';

        echo $err_msg;
	}
}
