<?
/* ----------------------------------------------------------------
// THIS IS THE USER-CONFIGURABLE SETTINGS FILE FOR dadaAntihack.php
// IT WILL NOT BE OVERWRITTEN BY UPGRADES TO THE SCRIPT FILE
//
// THE LOCATION OF THE dadaAntihack HOME DIRECTORY
// IS AT THE BOTTOM OF THIS FILE. ALTER TO SUIT YOUR CONFIGURATION.
//
// ONE CODE FILE CAN BE SHARED BY MULTIPLE SITES ON A SINGLE SERVER.
-------------------------------------------------------------------*/

$dadaAntihack = array();

// A one-setting ON/OFF switch.
// Set this to "off" to disable the anti-hack processing
$dadaAntihack['on_off'] = 'on';

// Code is either
// "403" (returns a "403 Forbidden" code AND logs the user as abusive) or
// "404" (returns a "404 Page Not Found" code)
$dadaAntihack['code'] = array();
$dadaAntihack['code']['default']		= '404';
$dadaAntihack['code']['user_agents']	= '403';
$dadaAntihack['code']['referrers']		= '403';
$dadaAntihack['code']['remote_ips']	= '403';

// The sort of page you want to display to the user
// The available options are:
// "false" => means that the user sees NO page other than the $dadaAntihack['response'] below
// "true" => means that the user will see whatever page your site would have displayed without dadaAntihack
// "/path/to/file.php" => If you specify a path on your site for a custom 404 page, that will be displayed
//
// this first variable is the default for all the others
$dadaAntihack['passthrough'] = array();
$dadaAntihack['passthrough']['default']		= false;
// improper GET values almost never deserve to be passed through no matter what the default is
$dadaAntihack['passthrough']['get_values']		= false;
$dadaAntihack['passthrough']['get_blacklist']	= false;
// GET whitelist are _so_ restrictive that we should probably be lenient with them until fully tested
$dadaAntihack['passthrough']['get_whitelist']	= true;
$dadaAntihack['passthrough']['post_values']	= false;

// The text string on the next line will be returned as the only response to the user
// Since this script is intended to prevent sheer abuse of the site, it does not load
// sufficient resources from the database to display a custom response page.
// It will use whatever text or HTML you include below as the text displayed to the user.
$dadaAntihack['response'] = array();
$dadaAntihack['response']['default'] = '<h1>'._('Your page request was not permitted.').'</h1>';

/* ----------------------------------------------------------------
// CHECKS
//
// SYNTAX:
// If the value of your check is a simple STRING, e.g. "foobar"
// it is considered to be the test string, and will use default
// parameters for response code and logging. Otherwise it is expected to be an
// associative array with the following name/value pairs:
// "s" => "foo.*" # The regular expression to search for
// "code" => 403|404 # The response triggered by this check
// "response" => "Anything" # Custom response text displayed to user
// "log" => 0|1 # Whether or not to log a hit to this check (default 0)
// "msg" => "Anything" # Custom message to log for a hit to this check
-------------------------------------------------------------------*/

// INITIALIZE THE USER-CONFIGURABLE ARRAYS
$get_values = array();
$get_blacklist = array();
$get_whitelist = array();
$post_values = array();
$query_strings = array();
$user_agents = array();
$referrers = array();
$remote_ips = array();

// $_GET VALUES (Illegal GET values)
/* ----------------------------------------------------------------
// If a page request GET parameter equals a value in this list,
// the entire request will be rejected with $dadaAntihack['code']
// USE THIS TO BLOCK REQUESTS THAT USE BOGUS PARAMETER VALUES
// (for stopping referrer spam, e.g. index.php?id=http://www.spam.com/)
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
// Matches are always case-insensitive
-------------------------------------------------------------------*/

// One should never be passing full URIs as values in GET parameters
$get_values[] = array(
				's'=>'\.\.\/',
				'code'=>'404',
				'log'=>true,
				'msg'=>'File system hack');
$get_values[] = array(
				's'=>'http:\/\/',
				'code'=>'404',
				'log'=>false);
$get_values[] = array(
				's'=>'ftp:\/\/',
				'code'=>'404',
				'log'=>false);
// One should never be passing web server root path parameters either
$get_values[] = array(
				's'=>'\/var\/www\/html',
				'code'=>'404',
				'log'=>true,
				'msg'=>'File system hack');
// An easy way to protect against attempts at executing a remote download
$get_values[] = array(
				's'=>'wget',
				'code'=>'404',
				'log'=>true,
				'msg'=>'Remote download attempt');
// An easy way to protect against attempts executing shell commands
$get_values[] = array(
				's'=>'passthru',
				'code'=>'404',
				'log'=>true,
				'msg'=>'Shell command attempt');
// An easy way to protect against attempts at reaching /etc/passwd
$get_values[] = array(
				's'=>'passwd',
				'code'=>'404',
				'log'=>true,
				'msg'=>'File system hack');
// An easy way to protect against attempts at SQL injection
$get_values[] = array(
				's'=>'union%20select',
				'code'=>'404',
				'log'=>true,
				'msg'=>'SQL Injection hack');
$get_values[] = array(
				's'=>'CONCAT',
				'code'=>'404',
				'log'=>true,
				'msg'=>'SQL Injection hack');
$get_values[] = array(
				's'=>'CHAR',
				'code'=>'404',
				'log'=>true,
				'msg'=>'SQL Injection hack');



// $_GET BLACKLIST (GET parameter names that are never permitted)
/* ----------------------------------------------------------------
// If a page request uses ANY parameter in this list,
// their entire request will be rejected with $dadaAntihack['code']
// USE THIS TO BLOCK REQUESTS THAT USE BOGUS PARAMETERS
// (You might use this blacklist or whitelist below, but not both)
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
-------------------------------------------------------------------*/

/*
$get_blacklist[] = array(
				's'=>'menu',
				'code'=>'403',
				'log'=>false);
$get_blacklist[] = array(
				's'=>'referer',
				'code'=>'403',
				'log'=>false);
*/


// $_GET WHITELIST (Whitelist of the only acceptable GET parameter names)
/* ----------------------------------------------------------------
// If a page request uses a parameter NOT in this list,
// their entire request will be rejected with $dadaAntihack['code']
// (THIS SHOULD BE USED ONLY ON SITES WHERE YOU CAN ENUMERATE
// EXACTLY WHAT THE LIST OF ACCEPTABLE PARAMETERS IS!)
// (You might use the blacklist above or whitelist, but not both)
//
// Since this is a whitelist, the checks do not "trigger" anything.
// The response code and logging is handled only by the default settings
//
// These are NOT regular expressions. They are a strict enumeration
// of possible acceptable GET parameter names. And since we only
// need the search string, we do not use the full array syntax.
-------------------------------------------------------------------*/

/*
$get_whitelist[] = 'id';
$get_whitelist[] = 'function';
$get_whitelist[] = 'searchtext';
*/



// $_POST VALUES (A few basic rules)
/* ----------------------------------------------------------------
// If a page request POST parameter KEY equals an "s" value in this list,
// the value is checked based on the "check" value. Possible checks are:
// --empty (the field is not allowed to be empty)
// --nonempty (the field is required to be empty)
// --links (the field contains more than 'limit' links)
// --length (the submission is longer than 4k)
// --match (the field is required to be match the pattern specified by a "pattern" element)
-------------------------------------------------------------------*/

// Check for non-empty form fields
$post_values[] = array(
				's'=>'form_code',
				'check'=>'nonempty',
				'code'=>'403',
				'log'=>true,
				'msg'=>'Failed anti-spam test');
$post_values[] = array(
				's'=>'form_body',
				'check'=>'links',
				'limit'=>0,
				'code'=>'403',
				'log'=>true,
				'msg'=>'Too many links');
$post_values[] = array(
				's'=>'form_body',
				'check'=>'match',
				'pattern'=>'http',
				'code'=>'403',
				'log'=>true,
				'msg'=>'Links not permitted in comments');



// QUERY STRING matches
/* ----------------------------------------------------------------
// Add any strings that will match ANY PART of the QUERY_STRING of
// the page request, e.g. "index.php?anything_here=the_query_string"
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
-------------------------------------------------------------------*/

/*
$query_strings[] = array(
				's'=>'badstring',
				'code'=>'404',
				'log'=>true);
*/



// USER AGENT (browser) matches
/* ----------------------------------------------------------------
// Add any strings that will match any part of the USER_AGENT (browser) of the page request,
// e.g. "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
-------------------------------------------------------------------*/

/*
$user_agents[] = array(
				's'=>'Twiceler',
				'code'=>'403');
*/




// REFERRER (referring page) matches
/* ----------------------------------------------------------------
// Add any strings that will match any part of the REFERER of the page request
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
-------------------------------------------------------------------*/

/*
$referrers[] = array(
				's'=>'http:\/\/www.pornsite.com\/',
				'code'=>'403');
*/



// REMOTE ADDRESS (User IP address) matches
/* ----------------------------------------------------------------
// Add any strings that will match any part of the REMOTE_ADDR of the page request,
// e.g. "1\.2\.3\.4"
//
// REMEMBER THAT YOU ARE WRITING REGULAR EXPRESSIONS, SO YOU MUST
// ESCAPE CERTAIN CHARACTERS, NOTABLY "/" => "\/" and "." => "\."
-------------------------------------------------------------------*/

// DON'T FORGET TO ESCAPE THE "." CHARACTERS
/*
$remote_ips[] = array(
				's'=>'192\.168\.255\.255',
				'code'=>'403');
*/



/* ----------------------------------------------------------------
//
// PATH TO dadaAntihack CODE FILE
//
-------------------------------------------------------------------*/
if (!isset($dadaTools_path)) $dadaTools_path = './';
include($dadaTools_path.'dadaAntihack.php');

/* ----------------------------------------------------------------
// TODO
// 1. IP address whitelists
-------------------------------------------------------------------*/
?>