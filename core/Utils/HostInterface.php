<?php

namespace WPD_Platform\Utils;

interface HostInterface {
	const TYPE_1        = 's5';
	const TYPE_2        = 'external';
	const TOKEN_OPTION  = 'wpd_token';
	const API_BASE_URL  = 'https://control.getdollie.com/';
	const API_URL       = 'https://control.getdollie.com/api/';
	const API_SIGNATURE = '7E0329D6C2A152629E10B40C26A9C1BF997B0A73153BF47920EC832E501EFD9AF3A1021075F873BDECD2EDC5931F931A59C8646BE0BD664AA909E68DFBED9460';
}
