<?
// Manifesto: $Id: dadaAntihack.php 99 2009-10-20 17:54:14Z spud $
// license: GNU LGPL
// copyright 2001-2014: dada typo and contributors

/* ----------------------------------------------------------------
// This file provides the first line of defense against
// abusive page requests. It allows you to define strings
// (actually full regular expressions)
// that should NOT be permitted in various parts of a page request.
// Currently, you can check for matches against
// QUERY_STRING -- the parameters sent as part of a GET request
// USER_AGENT -- the browser identification string
// REFERRER -- the page which directed the user to your site
// REMOTE_ADDR -- the user's IP address as provided by their request
-------------------------------------------------------------------*/

/* ----------------------------------------------------------------
// SETTINGS
-------------------------------------------------------------------*/
if (!isset($dadaAntihack)) $dadaAntihack = array();
if (!isset($dadaAntihack['path'])) $dadaAntihack['path'] = './';

/*
 * For debugging only. Do NOT turn on for production.
 */
$dadaAntihack['debugging'] = false;

/*
 * A simple ON/OFF switch for the config page.
 * To disable dadaAntihack on your site temporarily, simply set this to "off"
 */
if (!isset($dadaAntihack['on_off'])) {
	$dadaAntihack['on_off'] = 'on';
}

/*
 * Set the default HTTP status code to return in case of violation
 * - "403" (returns a "403 Forbidden" page AND logs the user as abusive) or
 * - "404" (returns a "404 Page Not Found" page)
 */
if (!isset($dadaAntihack['code'])) {
	$dadaAntihack['code'] = array();
	$dadaAntihack['code']['default']		= '404';
}

/*
 * What sort of response to render in case of rule violation.
 *
 * The available options are:
 * - "false" (the user sees NO HTML other than the $dadaAntihack['response'] below) or
 * - "true" (the user will see whatever page your site would have displayed without dadaAntihack, but with a 403 or 404 status code nonetheless)
 * - [/path/to/html/page] (the user will see the custom 404 page you specify)
 */
if (!isset($dadaAntihack['passthrough'])) {
	$dadaAntihack['passthrough'] = array();
	// this first variable is the default for all the others
	$dadaAntihack['passthrough']['default'] = false;
	// improper GET values almost never deserve to be passed through
	// no matter what the default is
	$dadaAntihack['passthrough']['get_values'] = false;
	// GET whitelist rules are _so_ restrictive that we should probably be lenient with them until fully tested
	$dadaAntihack['passthrough']['get_whitelist'] = true;
}

/*
 * The text string on the next line will be returned as the only response to the user
 *
 * Since the dadaAntihack script is intended to prevent sheer abuse of the site, it does not load
 * sufficient resources from the database to display a custom response page for CMSes.
 * It will use whatever text or HTML you include below as the text displayed to the user.
 */
if (!isset($dadaAntihack['response'])) {
	$dadaAntihack['response'] = array();
	$dadaAntihack['response']['default'] = '<h1>'._('Your page request was not permitted.').'</h1>';
}

//if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].': dadaAntihack initialized.');

/* ----------------------------------------------------------------
 * This function simply returns the appropriate header
 * ----------------------------------------------------------------*/
function stop_attack($trigger,$rule) {
	global $dadaAntihack;

	if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].' dadaAntihack matched on '.$trigger.' check with '.$regex);

	// GENERATE THE APPROPRIATE RESPONSE CODE
	$code = $dadaAntihack['default'];
	// Allow possible overrides of code on a per-rule basis
	if (isset($rule['code'])) {
		$code = $rule['code'];
	} elseif (isset($dadaAntihack[$trigger])) {
		$code = $dadaAntihack[$trigger];
	}

	// GENERATE THE RESPONSE MESSAGE
	$response = $dadaAntihack['response']['default'];
	// Allow possible overrides of response on a per-rule basis
	if (isset($rule['response'])) {
		$response = $rule['response'];
	} elseif (isset($dadaAntihack['response'][$trigger])) {
		$response = $dadaAntihack['response'][$trigger];
	}
	// GENERATE THE PASS-THROUGH BEHAVIOR
	$passthrough = $dadaAntihack['passthrough']['default'];
	// allow possible overrides of passthrough as a per-check default
	if (isset($dadaAntihack['passthrough'][$trigger])) {
		$passthrough = $dadaAntihack['passthrough'][$trigger];
	}
	// TO LOG OR NOT TO LOG?
	$tolog = false;
	if (isset($rule['log']) && $rule['log'] == true) {
		$tolog = true;
	}
	// BUILD THE HTML PAGE CONTENT
	$page = '<html><head><title>'._('Invalid page request').'</title></head><body>'.$response.'</body></html>';

	// RESPONSE CODE HEADER
	if ($code == '403') {
		header('http/1.1 403 Forbidden');
	} elseif ($code == '404') {
		header('http/1.1 404 Page Not Found');
		// make it UTF-8 for multi-lingual responses...
		header('Content-Type: text/html; charset=utf-8');
	}

	// Manifesto CMS-specific function
	// increment the hit count for this IP in the abuse table if used within Manifesto
	if (function_exists("check_abuse")) check_abuse($_SERVER['REMOTE_ADDR'],'Sitewide block');

	// LOGGING
	if ($tolog) {
		$msg = 'Regex string "'.stripslashes($rule['s']).'"';
		if (isset($rule['msg'])) $msg = $rule['msg'];
		error_log('dadaAntihack on '.$_SERVER['SERVER_NAME'].' stopped an attack by '.$_SERVER['REMOTE_ADDR'].'. Check: '.strtoupper($trigger).' -- '.$msg);
	}
	// Log machine-parseable record to dadaAntihack.log if it exists
	if (!file_exists($dadaAntihack['path'].'dadaAntihack.log')) touch($dadaAntihack['path'].'dadaAntihack.log');
	if (file_exists($dadaAntihack['path'].'dadaAntihack.log')) {
		if (!is_writeable($dadaAntihack['path'].'dadaAntihack.log')) error_log('dadaAntihack.log cannot be written by '.$_SERVER['SERVER_NAME']);
		error_log('['.date('d-M-Y H:i:s').'] '.$_SERVER['SERVER_NAME'].' '.strtoupper($trigger).' '.$_SERVER['REMOTE_ADDR']."\n",3,$dadaAntihack['path'].'dadaAntihack.log');
	}

	// PASS-THROUGH BEHAVIOR
	if ($passthrough === false) {
		// send the default error message
		exit($page);
	} elseif ($passthrough === true) {
		// allow the site to render whatever page it would normally try
		return true;
	} elseif (file_exists($passthrough)) {
		// include a custom 404 page
		include($passthrough);
	} else {
		// if all else fails, send the default error message
		exit($page);
	}
}

function dada_quickDecode($str) {

	/* ------------------------------------------------------------*/
	// Takes decimal encoded input, strips 09,10,13 characters
	// and converts anything in the ASCII range back to character
	/* ------------------------------------------------------------*/
	if (!function_exists("dadaAntihack_check_dec")) {
		function dadaAntihack_check_dec($int) {
			if ($int <= 127) {
				switch($int) {
					case 9:
					case 10:
					case 13:
						return '';
					default:
						return chr($int);
				}
			} else {
				return '&#'.$int.';';
			}
		}
	}
	/* ------------------------------------------------------------*/
	// Convert hex encoding to decimal and send through dadaAntihack_check_dec
	/* ------------------------------------------------------------*/
	if (!function_exists("dadaAntihack_check_hex")) {
		function dadaAntihack_check_hex($str) {
			return dadaAntihack_check_dec(hexdec($str));
		}
	}

	// convert encoded single-quotes to a dummy string so we can preserve and restore them
	// this helps with URLs that contain embedded single quotes for search terms
	$newstr = str_replace('%22','___dt_quoteholder___',$str);
	// trim leading/trailing whitespace
	$newstr = rtrim($newstr);

	// strip NULL characters
	$newstr = preg_replace('/\0+/','',$newstr);
	$newstr = preg_replace('/(\\\\0)+/','',$newstr);
	// replace ampersands
	$newstr = str_replace('&','&amp;',$newstr);

	// handle named entities (&aquot; and the like)
	// the semi-colon can be made optional here ONLY if all matches are compared to known HTML named entities;
	// otherwise we can't distinguish the &amp;foo in "?id=123&amp;foo=bar" from an entity
	$newstr = preg_replace('/&amp;([a-z][a-z0-9]{0,19});/i','&\\1;',$newstr);
	// handle decimal encodings (&#106 or &#000114;)
	$newstr = preg_replace('/&amp;#0*([0-9]{1,5});/e','dadaAntihack_check_dec(\\1)',$newstr);
	// handle hex encodings (convert to decimal, then handle like above)
	$newstr = preg_replace('/&amp;#x0*(([0-9a-f]{2}){1,2});/ie',"dadaAntihack_check_hex('\\1')",$newstr);

	// restore the encode quote
	$newstr = str_replace('___dt_quoteholder___','%22',$newstr);
	if ($newstr != $str) {
		$newstr = dada_quickDecode($newstr);
	}
	return $newstr;
}

// Check on on/off switch!
if ($dadaAntihack['on_off'] == 'on') {

/* ----------------------------------------------------------------
// Loop through any GET request parameters and
// check the blacklist and whitelist
-------------------------------------------------------------------*/
if (isset($_GET) && !empty($_GET)) {
	foreach($_GET as $k=>$v) {

		// Cast all GET parameters to an array, so we can process string values and array values in the same loop
		$varr = (array)$v;

		// Check for strings that would NEVER appear as a value that
		// your website would pass for one of its variables,
		// e.g the "http://" in "?id=http://www.spam-site.com/"
		if (!empty($get_values)) {
			foreach($varr as $vv) {
				foreach($get_values as $i=>$rule) {
					// Convert simple strings to a proper rule array format
					if (is_string($rule)) {
						$rule = array('s'=>$rule);
					}
					$regex = $rule['s'];
					if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].' dadaAntihack comparing '.$regex.' to '.dada_quickDecode($vv));
					if (preg_match("/$regex/i",dada_quickDecode($vv))) {
						stop_attack('get_values',$rule);
					}
				}
			}
		}

		// Either check the blacklist
		if (!empty($get_blacklist)) {
			foreach($get_blacklist as $i=>$rule) {
				if (is_string($rule)) {
					$rule = array('s'=>$rule);
				}
				$regex = $rule['s'];
				if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].' dadaAntihack comparing '.$regex.' to '.dada_quickDecode($k));
				if (preg_match("/$regex/i",dada_quickDecode($k))) {
					stop_attack('get_blacklist',$rule);
				}
			}
		// OR the whitelist
		} elseif (!empty($get_whitelist)) {
			// here we check to make sure the key $k _IS_ in the whitelist
			if (!in_array($k,$get_whitelist)) {
				stop_attack('get_whitelist',$rule);
			}
		}

	}
}

/* ----------------------------------------------------------------
// Loop through any POST request parameters and
// check for a few blatant spam attempts
-------------------------------------------------------------------*/
if (isset($_POST) && !empty($_POST)) {
	if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].' dadaAntihack detected POST fields');
	foreach($_POST as $k=>$v) {

		// Cast all POST parameters to an array, so we can process string values and array values in the same loop
		$varr = (array)$v;

		if (!empty($post_values)) {
			foreach($varr as $vv) {
				foreach($post_values as $i=>$rule) {

					if ($k == $rule['s']) {

						if ($dadaAntihack['debugging']) error_log($_SERVER['SERVER_NAME'].' dadaAntihack checking POST field '.$k.' for '.$rule['check']);

						// Compare the request value to the checks defined by this rule
						switch($rule['check']) {
							case 'empty':
								if (empty($vv)) stop_attack('post_values',$rule);
								break;
							case 'nonempty':
								if ($vv !== '') stop_attack('post_values',$rule);
								break;
							case 'match':
								if (!isset($rule['pattern'])) break;
								$pat = $rule['pattern'];
								if (!preg_match("/$pat/i",dada_quickDecode($vv))) {
									stop_attack('post_values',$rule);
								}
								break;
							case 'length':
								if (strlen($vv) > 3072) stop_attack('post_values',$rule);
								break;
							case 'links':
								if (!isset($rule['limit'])) $rule['limit'] = 1;
								$regex = 'http:\/\/';
								$ct = preg_match_all("/$regex/i",dada_quickDecode($vv),$m);
								if ($ct > $rule['limit']) stop_attack('post_values',$rule);
								break;
						}
					}
				}
			}
		}
	}
}

/* ----------------------------------------------------------------
 * Loop through all the query string rules and
 * stop if any of them produce a match
 * --------------------------------------------------------------*/
if (!empty($query_strings)) {
	foreach($query_strings as $i=>$rule) {
		if (is_string($rule)) {
			$rule = array('s'=>$rule);
		}
		$regex = $rule['s'];
		if (preg_match("/$regex/i",dada_quickDecode($_SERVER['QUERY_STRING']))) {
			stop_attack('query_strings',$rule);
		}
	}
}

/* ----------------------------------------------------------------
 * Loop through all the user agent rules and
 * stop if any of them produce a match
 * --------------------------------------------------------------*/
if (!empty($user_agents) && isset($_SERVER['HTTP_USER_AGENT'])) {
	foreach($user_agents as $i=>$rule) {
		if (is_string($rule)) {
			$rule = array('s'=>$rule);
		}
		$regex = $rule['s'];
		if (preg_match("/$regex/i",dada_quickDecode($_SERVER['HTTP_USER_AGENT']))) {
			stop_attack('user_agents',$rule);
		}
	}
}

/* ----------------------------------------------------------------
 * Loop through all the referer rules and
 * stop if any of them produce a match
 * --------------------------------------------------------------*/
if (!empty($referrers) && isset($_SERVER['HTTP_REFERER'])) {
	foreach($referrers as $i=>$rule) {
		if (is_string($rule)) {
			$rule = array('s'=>$rule);
		}
		$regex = $rule['s'];
		if (preg_match("/$regex/i",dada_quickDecode($_SERVER['HTTP_REFERER']))) {
			stop_attack('referrers',$rule);
		}
	}
}

/* ----------------------------------------------------------------
// Loop through all the remote IP rules and
// stop if any of them produce a match
-------------------------------------------------------------------*/
if (!empty($remote_ips) && isset($_SERVER['REMOTE_ADDR'])) {
	foreach($remote_ips as $i=>$rule) {
		if (is_string($rule)) {
			$rule = array('s'=>$rule);
		}
		$regex = $rule['s'];
		if (preg_match("/$regex/i",dada_quickDecode($_SERVER['REMOTE_ADDR']))) {
			stop_attack('remote_ips',$rule);
		}
	}
}

} // end the on-off toggle

/* ----------------------------------------------------------------
// Some cleanup to prevent pollution of the global variable space
-------------------------------------------------------------------*/
unset($get_values,$post_values,$get_blacklist,$get_whitelist,$query_strings,$user_agents,$referrers,$remote_ips);
?>