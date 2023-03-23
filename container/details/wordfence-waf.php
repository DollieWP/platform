<?php
// Compat for Wordfence
if (file_exists('/usr/src/dollie/bootstrap.php')) {
	include_once '/usr/src/dollie/bootstrap.php';
}
if (file_exists(__DIR__ . '/wp-content/plugins/wordfence/waf/bootstrap.php')) {
	define("WFWAF_LOG_PATH", __DIR__ . '/wp-content/wflogs/');
	include_once __DIR__ . '/wp-content/plugins/wordfence/waf/bootstrap.php';
}
