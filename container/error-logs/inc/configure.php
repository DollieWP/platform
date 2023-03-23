<?php
/*! logviewer - 1.7.14 - 025d83c29c6cf8dbb697aa966c9e9f8713ec92f1*/
/*
 * logviewer
 * http://logviewer.com
 *
 * Copyright (c) 2017 Potsky, contributors
 * Licensed under the GPLv3 license.
 */
?><?php
include_once 'global.inc.php';
load_default_constants();

/*
|--------------------------------------------------------------------------
| Prepare
|--------------------------------------------------------------------------
|
*/
include_once '../cfg/softwares.inc.php';


/*
|--------------------------------------------------------------------------
| Ajax tasks required by body
|--------------------------------------------------------------------------
|
*/
if ( isset( $_POST['s'] ) ) {

	$return = array(
		'reload' => false,
		'next'   => false,
	);

	$config_file      = PML_CONFIG_BASE . DIRECTORY_SEPARATOR . CONFIG_FILE_NAME;
	$config_file_temp = PML_CONFIG_BASE . DIRECTORY_SEPARATOR . CONFIG_FILE_TEMP;

	try {

		/*
		|--------------------------------------------------------------------------
		| Alert user if he has not configured the date.timezone setting
		|--------------------------------------------------------------------------
		|
		*/
		function pml_error_handler($errno, $errstr, $errfile, $errline, array $errcontext) { throw new ErrorException($errstr, 0, $errno, $errfile, $errline); };
		set_error_handler("pml_error_handler");
		$a = date('U');
		restore_error_handler();


		switch ( $_POST['s'] ) {

			/*
			|--------------------------------------------------------------------------
			| Auth 1. Ask for authentication
			|--------------------------------------------------------------------------
			|
			*/
			case 'auth':

				// Destroy a previous session
				Sentinel::destroy();

				$return[ 'notice' ] =
					'<h2>' . __t( 'Setup admin account') . '</h2>'
					. '<br/>'
					. __t('You can use <em>Log Viewer</em> without authentication. You will be able to add this feature later from the debugger web interface.') . '<br/>'
					. '<br/>'
					. __t('Setup an admin account will let you create other users later and give them access to certain log files only.') . '<br/>'
					. '<br/>'
					. __t( 'Do you want to create an admin account now?') . '<br/>'
					. '<br/>'
					. '<br/>'
					. '<a href="javascript:process_authentication_yes()" class="btn btn-primary">' . __t('Create an admin account') . '</a>'
					. '&nbsp;&nbsp;'
					. '<a href="javascript:process_authentication_no()" class="btn btn-default">' . __t('No') . '</a>';

				break;


			/*
			|--------------------------------------------------------------------------
			| Auth 2. Touch auth file
			|--------------------------------------------------------------------------
			|
			*/
			case 'authtouch':

				if ( Sentinel::isAuthSet() === true ) {
					$path = Sentinel::getAuthFilePath();
					$return[ 'notice' ] =
						sprintf( __t( 'File <code>%s</code> already exists!') , AUTH_CONFIGURATION_FILE )
						. '<br/><br/>'
						. __t( 'Please remove it from the root directory:' )
						. '<div class="row">'
						. '  <div class="col-md-10"><pre class="clipboardcontent">' . 'mv \'' . $path . '\' \'' . $path . '.bck\'</pre></div>'
						. '  <div class="col-md-2"><a class="btn btn-primary clipboard">' . __t('Copy to clipboard') . '</a><script>clipboard_enable("a.clipboard","pre.clipboardcontent" , "top" , "' . __t('Command copied!') . '");</script></div>'
						. '</div>';
					$return[ 'reload' ] = true;
				}

				// Auth file is touched so return the form to ask yes or no
				else if ( Sentinel::create() === true ) {
					$return[ 'authform' ] =
					'<h2>' . __t( 'Setup admin account') . '</h2>'
					. '<br/>'
					. __t( 'Please choose a username and a password for the admin account.')
					. '<br/><br/>'
					. '<form id="authsave" autocomplete="off">'
					. '<div class="container">'
					. 	'<div class="row">'
					. 		'<div class="input-group col-sm-6 col-md-4" id="usernamegroup" data-toggle="tooltip" data-placement="top" title="' . htmlentities( __t( 'Username is required' ) ) . '">
								<span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
								<input type="text" id="username" class="form-control" placeholder="' . __t('Username') . '" autofocus="autofocus">
							</div>'
					. 	'<br/>'
					. 	'</div>'
					. 	'<div class="row">'
					. 		'<div class="input-group col-sm-6 col-md-4" id="passwordgroup" data-toggle="tooltip" data-placement="bottom" title="' . htmlentities( __t( 'Password must contain at least 6 chars' ) ) . '">
								<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
								<input type="password" id="password" class="form-control" placeholder="' . __t('Password') . '">
							</div>'
					. 	'<br/>'
					. 	'</div>'
					. 	'<div class="row">'
					. 		'<div class="input-group col-sm-6 col-md-4" id="password2group" data-toggle="tooltip" data-placement="bottom" title="' . htmlentities( __t( 'Password is not the same' ) ) . '">
								<span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
								<input type="password" id="password2" class="form-control" placeholder="' . __t('Password Confirmation') . '">
							</div>'
					. 	'</div>'
					. '</div>'
					. '<br/><br/>'
					. '<input type="submit" class="btn btn-primary" value="' . __t('Continue') . '"/>'
					. '</form>'
					;
				}

				// Unable to touch, return an error
				else {
					$return[ 'notice' ] =
						'<div class="alert alert-danger">'
						. sprintf( __t( 'Unable to create file <code>%s</code>') , AUTH_CONFIGURATION_FILE )
						. '</div>'
						. __t( 'Please give temporary write access to the root directory:' )
						. '<div class="row">'
						. '  <div class="col-md-10"><pre class="clipboardcontent">' . 'chmod 777 ' . dirname( dirname( __FILE__ ) ) . '</pre></div>'
						. '  <div class="col-md-2"><a class="btn btn-primary clipboard">' . __t('Copy to clipboard') . '</a><script>clipboard_enable("a.clipboard","pre.clipboardcontent" , "top" , "' . __t('Command copied!') . '");</script></div>'
						. '</div>';
					$return[ 'reload' ] = true;
				}

				break;


			/*
			|--------------------------------------------------------------------------
			| Auth 3. Save data
			|--------------------------------------------------------------------------
			|
			*/
			case 'authsave':
				if ( ( mb_strlen( $_POST['u'] ) > 0 ) && ( mb_strlen( $_POST['p'] ) >= 6 ) ) {
					Sentinel::setAdmin( $_POST['u'] , $_POST['p'] );
					$return[ 'notice' ] = Sentinel::save();
				}
				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 1. Check if $config_file already exists
			|--------------------------------------------------------------------------
			|
			*/
			case 'exist':
				$config_file_name = get_config_file_name();
				if ( ! is_null( $config_file_name ) ) {
					$return[ 'notice' ] =
						__t( 'Please remove it manually if you want me to create it:' )
						. '<br/><br/>'
						. '<div class="row">'
						. '  <div class="col-md-9"><pre class="clipboardcontent">' . 'rm \'' . get_config_file_path() . '\'</pre></div>'
						. '  <div class="col-md-3"><a class="btn btn-primary clipboard">' . __t('Copy to clipboard') . '</a><script>clipboard_enable("a.clipboard","pre.clipboardcontent" , "top" , "' . __t('Command copied!') . '");</script></div>'
						. '</div>';
					throw new Exception( sprintf( __t( 'File <code>%s</code> already exists.') , $config_file_name ) );
				}
				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 2. Try to touch $config_file_temp
			|--------------------------------------------------------------------------
			|
			*/
			case 'touch':
				if ( ! @touch( $config_file_temp ) ) {
					$return[ 'notice' ] =
						'<div class="alert alert-danger">'
						. sprintf( __t( 'Unable to create file <code>%s</code>') , $config_file_temp)
						. '</div>'
						. __t( 'Please give temporary write access to the root directory:' )
						. '<div class="row">'
						. '  <div class="col-md-10"><pre class="clipboardcontent">' . 'chmod 777 ' . dirname( dirname( __FILE__ ) ) . '</pre></div>'
						. '  <div class="col-md-2"><a class="btn btn-primary clipboard">' . __t('Copy to clipboard') . '</a><script>clipboard_enable("a.clipboard","pre.clipboardcontent" , "top" , "' . __t('Command copied!') . '");</script></div>'
						. '</div>';
					$return[ 'reload' ] = true;
				}
				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 3. Return a list of software that the user could install
			|--------------------------------------------------------------------------
			|
			*/
			case 'soft' :
				$return[ 'notice' ] = '<h2>' . __t( 'Choose softwares to search log files for') . '</h2>';
				$return[ 'notice' ].= '<br/>';
				$return[ 'notice' ].= '<div class="table-responsive"><table id="soft"></table></div>';
				$return[ 'next' ]   = true;
				$return[ 'sofn' ]   = count( $softwares_all );
				$return[ 'soft' ]   = $softwares_all;
				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 4. Check for configuration files
			|--------------------------------------------------------------------------
			|
			*/
			case 'find':
				$software           = $_POST['so'];
				$softuser           = array();
				$tried              = array();
				$found              = 0;
				$software_paths     = '../cfg/' . $software . '.paths.php';
				$software_pathsuser = '../cfg/' . $software . '.paths.user.php';
				$return[ 'notice' ] = '<h2>' . sprintf( __t( 'Software <em>%s</em>') , $softwares_all[ $software ]['name'] ) . '</h2>';

				if ( file_exists( $software_pathsuser ) ) {
					include $software_pathsuser;
					$software_paths = $software_pathsuser;
				}
				else if ( file_exists( $software_paths ) ) {
					include $software_paths;
				}
				else {
					throw new Exception( sprintf( __t( 'Files <code>%s</code> or <code>%s</code> do not exist. Please review your software configuration.') , $software_paths , $software_pathsuser ) ) ;
				}

				foreach ( $paths as $userpath ) {

					$gpaths = glob( $userpath , GLOB_MARK | GLOB_NOCHECK | GLOB_ONLYDIR );

					if ( is_array( $gpaths ) ) {

						foreach( $gpaths as $path ) {

							$tried[ $software ][ $path ] = false;

							if ( is_dir( $path ) ) {

								$found = 1;
								$tried[ $software ][ $path ] = true;

								foreach ( $files as $type => $fpaths) {

									foreach ( $fpaths as $userfile ) {

										$gfiles   = glob( $path . $userfile , GLOB_MARK | GLOB_NOCHECK );

										if ( is_array( $gfiles ) ) {

											foreach( $gfiles as $file ) {

												$file              = basename( $file );
												$allfiles[ $file ] = $file;

												if ( ( is_readable( $path . $file ) ) && ( ! is_dir( $path . $file ) ) ) {

													if ( ! is_array( $tried[ $software ][ $path ] ) ) {
														$tried[ $software ][ $path ] = array();
													}

													$tried[ $software ][ $path ][ $type ][] = $file;
													$found = 2;
												}

											}
										}
									}
								}
							}
						}
					}
				}

				$softuser[ $software ] = array();
				foreach ( $files as $type => $fpaths) {
					$softuser[ $software ][ $type ] = 1;
				}

				$return[ 'files' ]  = $tried;
				$return[ 'found' ]  = $found;

				if ( $found == 0 ) {
					$return[ 'notice' ].= '<div class="alert alert-danger">' . __t( 'Unable to find any directory.') . '</div>';
					$return[ 'notice' ].= __t( 'Check in the following list if these directories are readable by the webserver user and refresh this page' );
				}
				else if ( $found == 1 ) {
					$return[ 'notice' ].= '<div class="alert alert-warning">' . __t( 'Directories are available but unable to find files inside.') . '</div>';
					$return[ 'notice' ].= __t( 'Check in the following list if these directories contain readable files by the webserver user and refresh this page.' ) . ' ';
					$return[ 'notice' ].= __t( 'Don\'t forget that to read a file, ALL parent directories have to be accessible too!' ) . ' ';

					$allfiles  = array();
					foreach ( $files as $type => $fpaths) {
						foreach ( $fpaths as $file ) {
							$allfiles[] = $file;
						}
					}
					$allfiles = '<code>' . json_encode( $allfiles ) . '</code>';

					$return[ 'notice' ].= sprintf( __t( 'These files have been checked in all paths: %s ' ) , $allfiles );
				}
				else {
					$return[ 'notice' ].= '<div class="alert alert-info">' . __t( 'Log files have been found!') . '</div>';
					$return[ 'notice' ].= __t( 'Check in the following list which files you want to configure.' );
					$return[ 'notice' ].= '<br/>';
					$return[ 'notice' ].= __t( 'If files or directories are missing, verify that they are readable by the webserver user' );
				}

				$user = get_server_user();
				$return[ 'notice' ] .= ( $user == '' )
					? ' (<em>' . __t( 'unable to detect web server user') . '</em>):'
					: ' (<em>' . sprintf( __t( 'web server user seems to be <code>%s</code>') , $user ) . '</em>):';

				$return[ 'notice' ] .= '<br/><br/>';
				$return[ 'notice' ] .= '<div class="table-responsive"><table id="find"></table></div>';
				$return[ 'notice' ] .= __t('You can also type log files path in the text area below separated by coma:');
				$return[ 'notice' ] .= '<br/><br/>';
				$return[ 'notice' ] .= '<div class="table-responsive"><table class="table table-striped table-bordered table-hover"><thead><tr><th>' . __t( 'Type' ) . '</th><th>' . __t( 'Custom paths' ) . '</th></tr></thead><tbody>';
				foreach( $softuser as $software => $types ) {
					foreach ( $types as $type => $dumb ) {
						$return[ 'notice' ] .= '<tr><td>' . $type . ' </td><td><textarea data-soft="' . $software . '" data-type="' . $type . '" class="userpaths form-control" rows="1"></textarea></td></tr>';
					}
				}
				$return[ 'notice' ] .= '</tbody></table></div>';

				$return[ 'next' ]   = true;
				$return[ 'reload' ] = true;
				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 5. Check for user files
			|--------------------------------------------------------------------------
			|
			*/
			case 'check':
				$user_files = $_POST['uf'];
				if ( ! is_array( $user_files ) ) {
					throw new Exception( __t( 'Unknown error') ) ;
				}

				$found    = array();
				$notfound = array();
				foreach ( $user_files as $files ) {
					$software = $files['s'];
					$type     = $files['t'];
					$file     = realpath( $files['f'] );
					if ( ( is_readable( $file ) ) && ( ! is_dir( $file ) ) ) {
						$found[] = $files;
					}
					else {
						$notfound[] = $files['f'];
					}
				}


				if ( count( $notfound ) > 0 ) {
					$return[ 'notice' ] = __t( 'Custom files below are not readable, please remove them or verify that they are readable by the webserver user' );
					$user = get_server_user();
					$return[ 'notice' ] .= ( $user == '' )
						? ' (<em>' . __t( 'unable to detect web server user') . '</em>):'
						: ' (<em>' . sprintf( __t( 'web server user seems to be <code>%s</code>') , $user ) . '</em>):<ul>';
					foreach( $notfound as $file ) {
						$return[ 'notice' ] .= '<li><code>' . $file . '</code></li>';
					}
					$return[ 'notice' ] .= '</ul>';
					$return[ 'next' ]   = true;
				}
				else {
					$return[ 'found' ] = $found;
				}

				break;


			/*
			|--------------------------------------------------------------------------
			| Logs 6. Check for user files
			|--------------------------------------------------------------------------
			|
			*/
			case 'configure':
				$logs = $_POST['l'];

				if ( ! is_array( $logs ) ) {
					throw new Exception( __t( 'Unknown error') ) ;
				}

				if ( count( $logs ) == 0 ) {
					throw new Exception( __t( 'Unknown error') ) ;
				}

				// Configure all logs
				$counter      = 0;
				$config_files = array();
				foreach( $logs as $log ) {
					$type       = $log['t'];
					$software   = $log['s'];
					$file       = $log['f'];
					$get_config = $software . '_get_config';
					$counter    = $counter + 1;
					$config     = '../cfg/' . $software . '.config.php';
					$configuser = PML_CONFIG_BASE . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . $software . '.config.user.php';

					if ( file_exists( $configuser ) ) {
						include_once $configuser;
						$config = $configuser;
					}
					else if ( file_exists( $config ) ) {
						include_once $config;
					}
					else {
						@unlink( $config_file_temp );
						throw new Exception( sprintf( __t( 'Files <code>%s</code> or <code>%s</code> do not exist. Please review your software configuration.') , $config , $configuser ) ) ;
					}

					if ( function_exists( $get_config ) ) {
						$config_files[] = call_user_func( $get_config , $type , $file , $software , $counter );
					}
					else {
						@unlink( $config_file_temp );
						throw new Exception( sprintf( __t( 'File <code>%s</code> does not define function <code>%s</code>. Please review your software configuration.') , $config , $get_config ) ) ;
					}
				}

				// Create and install file
				if ( count( $config_files ) > 0 ) {
					$base = file_get_contents( '../cfg/logviewer.config.php' );
					file_put_contents( $config_file_temp , str_replace( '"FILES":"FILES"' , implode( ",\n" , $config_files ) , $base ) );
					rename( $config_file_temp , $config_file );
					chmod( $config_file , CONFIG_FILE_MODE );
					$return[ 'next' ] = true;
				}
				else {
					throw new Exception( __t( 'No configuration found for softwares!') ) ;
				}
				break;


			/*
			|--------------------------------------------------------------------------
			| Unknown action
			|--------------------------------------------------------------------------
			|
			*/
			default:
				throw new Exception( __t( 'Unknown action, abort.' ) );
				break;
		}

	} catch (Exception $e) {
		// Error message for timezone not configured
		if ( strpos( $e->getMessage() , 'date.timezone' ) ) {
			$return[ 'error' ] = $e->getMessage() . '<hr/><span class="glyphicon glyphicon-info-sign"></span> ' . sprintf( __t('You should take a look on this %spage%s.') , '<a href="' . TIME_ZONE_SUPPORT_URL . '">' , '</a>' );
		}
		// Other error messages
		else {
			$return[ 'error' ] = $e->getMessage() . '<hr/><span class="glyphicon glyphicon-info-sign"></span> ' . sprintf( __t('You should take a look on this %spage%s.') , '<a href="' . SUHOSIN_URL . '">' , '</a>' );
		}
	}


	/*
	|--------------------------------------------------------------------------
	| Check if we have returned something
	|--------------------------------------------------------------------------
	|
	| If not, a command has stopped the execution and we need to alert user about
	| its configuration
	|
	*/
	$check = 0;
	if ( $return['reload'] === true ) $check++;
	if ( $return['next']   === true ) $check++;

	if ( count( $return ) === $check ) {
		$return[ 'error' ] = __t( 'Your PHP installation is not correctly configured to run Log Viewer.' ) . '<hr/><span class="glyphicon glyphicon-info-sign"></span> ' . sprintf( __t('You should take a look on this %spage%s.') , '<a href="' . SUHOSIN_URL . '">' , '</a>' );
	}


	header( 'Content-type: application/json' );
	echo json_encode( $return );
	die();
}



/*
|--------------------------------------------------------------------------
| Javascript Lemma
|--------------------------------------------------------------------------
|
*/
$lemma = array(
	'complete'       => __t( '%s% Complete' ),
	'error'          => __t( 'An error occurs!' ),
	'pleasewait'     => __t( 'Please wait...' ),
	'software'       => __t( 'Software' ),
	'path'           => __t( 'Path' ),
	'file'           => __t( 'File' ),
	'readable'       => __t( 'Readable' ),
	'type'           => __t( 'Type' ),
	'no'             => __t( 'No' ),
	'yes'            => __t( 'Yes' ),
	'name'           => __t( 'Name' ),
	'description'    => __t( 'Description' ),
	'notes'          => __t( 'Notes' ),
	'choosesoftware' => __t( 'You have to select at least one software to configure!' ),
	'chooselog'      => __t( 'You have to select at least one log file or type the path of a log file!' ),
	'suhosin'        => sprintf( __t('You should take a look on this %spage%s.') , '<a href="' . SUHOSIN_URL . '">' , '</a>' ),
);


/*
|--------------------------------------------------------------------------
| HTML
|--------------------------------------------------------------------------
|
*/
?><!DOCTYPE html><!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]--><!--[if IE 7]><html class="no-js lt-ie9 lt-ie8"><![endif]--><!--[if IE 8]><html class="no-js lt-ie9"><![endif]--><!--[if gt IE 8]><!--><html class="no-js"><!--<![endif]--><head><meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><title><?php echo TITLE;?></title><?php $fav = '../' ; include_once 'favicon.inc.php'; ?><meta name="description" content=""><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="../css/pml.min.css"><script>var lemma       = <?php echo json_encode($lemma);?>,
			uuid        = <?php echo json_encode($uuid);?>,
			querystring = "<?php echo $_SERVER['QUERY_STRING'];?>";</script></head><body><!--[if lt IE 7]><p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p><![endif]--><div class="navbar navbar-inverse navbar-fixed-top"><div class="container"><div class="logo"></div><div class="navbar-header"><a class="navbar-brand" href="?<?php echo $_SERVER['QUERY_STRING'];?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo __t('Configurator');?></a></div></div></div><div class="jumbotronflat"><div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"><span class="sr-only"></span></div></div></div><div class="container" id="process"><br><?php
			if ( SUHOSIN_LOADED === true ) {
				echo '<div class="alert alert-danger"><strong>';
				echo sprintf( __t('Suhosin extension is loaded, according to its configuration, Log Viewer could not run normally... More information %shere%s.') , '<a href="' . SUHOSIN_URL . '">' , '</a>' );
				echo '</strong></div>';
			}
		?><div id="error"></div><div id="user"></div><br><p id="buttons"><a id="next" class="btn btn-primary" href="#" style="display:none"><?php _pml_e('Continue');?></a> &nbsp; <a id="reload" class="btn btn-default" href="javascript:location.reload();" style="display:none"><?php _pml_e('Reload');?></a>&nbsp;</p></div><div class="jumbotron" id="congratulations" style="display:none"><div class="container"><h1><?php _pml_e( "Congratulations!" ); ?></h1><p><?php
				echo '<br/>';
				_pml_e( 'Your <em>Log Viewer</em> instance is ready to use.' );
				echo '<br/>';
				echo '<br/>';
				echo sprintf( __t( 'You can manually adjust settings in the <code>%s</code> file.' ) , CONFIG_FILE_NAME );
				echo '<br/>';
				_pml_e( 'Please visit <a href="http://logviewer.com">logviewer.com</a> for more informations.' );
				echo '<br/>';
				echo '<br/>';
				echo '<a class="btn btn-primary" href="../?' . $_SERVER['QUERY_STRING'] . '">' . __t('Log Viewers now!') . '</a>';
			?></p></div></div><div class="container"><hr><footer class="text-muted"><small><?php echo FOOTER;?></small></footer></div><script src="../js/pml.min.js"></script><script src="../js/configure.min.js"></script></body></html>
