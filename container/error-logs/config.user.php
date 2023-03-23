<?php
/*! logviewer - 1.7.14 - 025d83c29c6cf8dbb697aa966c9e9f8713ec92f1*/
/*
 * logviewer
 * http://logviewer.com
 *
 * Copyright (c) 2017 Potsky, contributors
 * Licensed under the GPLv3 license.
 */
?>
<?php if(realpath(__FILE__)===realpath($_SERVER["SCRIPT_FILENAME"])){header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');die();}?>
{
	"globals": {
		"_remove_me_to_set_AUTH_LOG_FILE_COUNT"         : 100,
		"_remove_me_to_set_AUTO_UPGRADE"                : false,
		"_remove_me_to_set_CHECK_UPGRADE"               : true,
		"_remove_me_to_set_EXPORT"                      : true,
		"_remove_me_to_set_FILE_SELECTOR"               : "bs",
		"_remove_me_to_set_FORGOTTEN_YOUR_PASSWORD_URL" : "http:\/\/support.logviewer.com\/kb\/misc\/forgotten-your-password",
		"_remove_me_to_set_GEOIP_URL"                   : "http:\/\/www.geoiptool.com\/en\/?IP=%p",
		"_remove_me_to_set_PORT_URL"                    : "http:\/\/www.adminsub.net\/tcp-udp-port-finder\/%p",
		"_remove_me_to_set_GOOGLE_ANALYTICS"            : "UA-XXXXX-X",
		"_remove_me_to_set_HELP_URL"                    : "http:\/\/logviewer.com",
		"_remove_me_to_set_LOCALE"                      : "gb_GB",
		"_remove_me_to_set_LOGS_MAX"                    : 50,
		"_remove_me_to_set_LOGS_REFRESH"                : 0,
		"_remove_me_to_set_MAX_SEARCH_LOG_TIME"         : 5,
		"_remove_me_to_set_NAV_TITLE"                   : "",
		"_remove_me_to_set_NOTIFICATION"                : true,
		"_remove_me_to_set_NOTIFICATION_TITLE"          : "New logs [%f]",
		"_remove_me_to_set_PULL_TO_REFRESH"             : true,
		"_remove_me_to_set_SORT_LOG_FILES"              : "default",
		"_remove_me_to_set_TAG_DISPLAY_LOG_FILES_COUNT" : true,
		"_remove_me_to_set_TAG_NOT_TAGGED_FILES_ON_TOP" : true,
		"_remove_me_to_set_TAG_SORT_TAG"                : "default | display-asc | display-insensitive | display-desc | display-insensitive-desc",
		"_remove_me_to_set_TITLE"                       : "Developer Logs",
		"_remove_me_to_set_TITLE_FILE"                  : "Developer Logs [%f]",
		"_remove_me_to_set_USER_CONFIGURATION_DIR"      : "config.user.d",
		"_remove_me_to_set_USER_TIME_ZONE"              : "Europe\/Paris"
	},

	"badges": {
		"severity": {
			"debug"       : "success",
			"info"        : "success",
			"notice"      : "default",
			"Notice"      : "info",
			"warn"        : "warning",
			"error"       : "danger",
			"crit"        : "danger",
			"alert"       : "danger",
			"emerg"       : "danger",
			"Notice"      : "info",
			"fatal error" : "danger",
			"parse error" : "danger",
			"Warning"     : "warning"
		},
		"http": {
			"1" : "info",
			"2" : "success",
			"3" : "default",
			"4" : "warning",
			"5" : "danger"
		}
	},

	"files": {
	"wp": {
		"display" : "WordPress Debug Log",
		"path"    : "\/usr\/src\/app\/wp-content\/debug.log",
		"refresh" : 5,
		"max"     : 10,
		"notify"  : true,
		"format"    : {
			"type"         : "PHP",
			"regex"        : "@^\\[(.*)-(.*)-(.*) (.*):(.*):(.*)( (.*))*\\] ((PHP (.*):  (.*) in (.*) on line (.*))|(.*))$@U",
			"export_title" : "Error",
			"match"        : {
				"Date"     : [ 2 , " " , 1 , " " , 4 , ":" , 5 , ":" , 6 , " " , 3 ],
				"Severity" : 11,
				"Error"    : [ 12 , 15 ],
				"File"     : 13,
				"Line"     : 14
			},
			"types"    : {
				"Date"     : "date:H:i:s",
				"Severity" : "badge:severity",
				"File"     : "pre:\/-69",
				"Line"     : "numeral",
				"Error"    : "pre"
			},
			"exclude": {
				"Log": ["\\/PHP Stack trace:\\/", "\\/PHP *[0-9]*\\. \\/"]
			}
		}
	},
		"nginx": {
			"display" : "NGINX Error Log",
			"path"    : "\/var\/log\/nginx\/error.log",
			"refresh" : 5,
			"max"     : 10,
			"notify"  : true,
			"format"    : {
				"type"         : "NGINX",
				"regex"        : "@^(.*)/(.*)/(.*) (.*):(.*):(.*) \\[(.*)\\] [0-9#]*: \\*[0-9]+ (((.*), client: (.*), server: (.*), request: \"(.*) (.*) HTTP.*\", host: \"(.*)\"(, referrer: \"(.*)\")*)|(.*))$@U",
				"export_title" : "Error",
				"match"        : {
					"Date"     : [1,"\/",2,"\/",3," ",4,":",5,":",6],
					"Severity" : 7,
					"Error"    : [10,18],
					"Client"   : 11,
					"Method"   : 13,
					"Request"  : 14,
					"Host"     : 15,
					"Referer"  : 17
				},
				"types"    : {
					"Date"     : "date:d\/m\/Y H:i:s \/100",
					"Severity" : "badge:severity",
					"Error"    : "pre",
					"Client"   : "ip:http",
					"Method"   : "txt",
					"Request"  : "txt",
					"Host"     : "ip:http",
					"Referer"  : "link"
				}
			}
		}
	}
}
