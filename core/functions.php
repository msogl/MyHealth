<?php

use Myhealth\Core\Dates;
use Myhealth\Core\FileSecurity;
use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Common;
use Myhealth\Classes\Session;
use Myhealth\Classes\LoginState;
use Myhealth\Models\AgreementModel;

/**
 * This function is identical to Html::encode, but Snyk
 * refuses to follow the code path. With this function in the
 * same file as the _W functions, Snyk is fine. Frustrating.
 * @param mixed $text
 * @param bool replaceNewline (default true)
 * @return string
 */
function html_encode($text, bool $replaceNewline=true)
{
	if (is_null($text)) {
		return '';
	}

	if (is_object($text) || is_array($text)) {
		return '';
	}


	$text = urldecode($text);
	$text = mb_detect_encoding($text, mb_detect_order(), true) === 'UTF-8' ? $text : mb_convert_encoding($text, 'UTF-8');
    
    $html = str_replace(['<br>', '<br/>', '<br />'], '[br]', $text);
	$html = htmlspecialchars($html, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

	// Handle some simple markup
	$html = str_replace('[br]', '<br/>', $html);
	$html = str_replace('[i]', '<em>', $html);
	$html = str_replace('[/i]', '</em>', $html);
	$html = str_replace('[u]', '<u>', $html);
	$html = str_replace('[/u]', '</u>', $html);
	$html = str_replace('[b]', '<strong>', $html);
	$html = str_replace('[/b]', '</strong>', $html);
	$html = str_replace('[nb]', '&nbsp;', $html);
	$html = str_replace('[indent]', '&nbsp;&nbsp;&nbsp;&nbsp;', $html);
	
	if ($replaceNewline) {
		$html = str_replace(['\r\n', '\r', '\n'], '<br>', $html);
	}
	
	return $html;
}

function redirect(string $location, bool $isPermanent=false)
{
    if (!str_starts_with($location, 'http') &&
        str_contains($location, '?') &&
        !str_contains($location, 'token=')) {
        $location .= getTokenParam();
    }

    if ($_ENV['ROUTEDEBUG'] === 'true') {
        Logger::info('redirecting to '.$location);
    }

    if ($isPermanent) {
        header("Location: $location", true, 301);
    }
    else {
        header("Location: $location");
    }
    exit;
}

function _session(array|string $key, ?string $default='')
{
    if (is_array($key)) {
        foreach($key as $k=>$val) {
            Session::put($k, $val);
        }
        return;
    }

	return Session::get($key, $default);
}

function _session_put(string $key, mixed $value)
{
    Session::put($key, $value);
}

function _session_get(string $key, ?string $default='')
{
    return Session::get($key, $default);
}

function _session_remove(string $key)
{
    return Session::remove($key);
}

function _session_all()
{
    return Session::all();
}

function _csrf()
{
    return '<input type="hidden" name="token" value="'.getTokenOnly().'">'."\n";
}

function _W($text, bool $replaceNewline=true, bool $convertMultipleSpacesToHTML=true)
{
	$toReturn = html_encode($text, $replaceNewline);
	
	if (!$convertMultipleSpacesToHTML) {
		$toReturn = str_replace("&nbsp;&nbsp;", "  ", $toReturn);
		$toReturn = str_replace(chr(194).chr(160), " ", $toReturn);		// UTF-8 version of non-breaking space
	}
	
	return $toReturn;
}

function _WF($text)
{
	$filtered = html_encode($text);
	$filtered = str_replace(chr(10), '', $filtered);
	$filtered = str_replace(chr(13), '<br/>', $filtered);
	return $filtered;
}

function _WDate($date, string $format='m/d/Y')
{
	return _W(Dates::toString($date, $format));
}

function _WDateTime($date)
{
	return _W(Dates::toString($date, 'm/d/Y H:i:s'));
}

function _WValue($text, $replaceNewline=true, $convertMultipleSpacesToHTML=true)
{
	return str_replace('"', '&quot;', _W($text, $replaceNewline, $convertMultipleSpacesToHTML));
}

function redirectToSecured()
{
	if (php_sapi_name() == 'cli') {
		return;
	}
	
	$unsecured = array(
		'localhost',
		'127.0.0.1',
	);

	if (!defined("HTTPS_ALWAYS_ON")) {
		define("HTTPS_ALWAYS_ON", false);
	}

	if (!HTTPS_ALWAYS_ON && !isSSL() && !in_array($_SERVER["SERVER_NAME"], $unsecured)) {
		header('Location: '.httpOverride('https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']));
		exit();
	}
	elseif (HTTPS_ALWAYS_ON && !isSSL() && !in_array($_SERVER["SERVER_NAME"], $unsecured) && !file_exists(APPPATH.'/http_override')) {
		// This shouldn't happen, but could during a server migration situation where SSL doesn't exist
		header('Location: https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
		exit();
	}
}

/**
 * authorized($permissions)
 * Determine if user is authorized (logged in, agreed to site agreement and has permissions for page)
 * $permissions - array
 * returns mixed - string containing page name to redirect to if not authorized, or null if authorized
 */
function authorized($permissions=null)
{
	/*
		We ask four questions:
		1. Is the login state LOGGED IN?
		2. Is the user logged in?
		3. Has the user agreed to the site agreement?
		4. Does the user have permissions for the page in question?
	*/
	if (_session('login_state') != LoginState::LOGGED_IN || _session('loggedin') === '') {
		// Check if we're past MFA, but before final login
		if (_session('login_state') == LoginState::MFA_REQUIRED &&
			(_session('mfavalid') == 'true' && _session('mfauser') != '')) {
			return 'login';
		}

        if (_session('login_state') == LoginState::AGREEMENT_REQUIRED) {
            return 'agreement';
        }

		@session_unset();
		return 'login';
	}

	if (_session('loggedInMemberId') == '') {
		@session_unset();
		return 'login';
	}

	if (!is_null($permissions)) {
		return null;			// TODO
	}

	return null;
}

function authenticated()
{
    return (
        _session('login_state') === LoginState::LOGGED_IN &&
        _session('loggedin') !== '' &&
        _session('loggedInMemberId') !== ''
    );
}

/**
 * User authenticated, but blocked from logging in fully for some reason.
 */
function partially_authenticated(): bool
{
    $states = [
        LoginState::PASSWORD_CHANGE_REQUIRED,
        LoginState::PASSWORD_EXPIRED ,
        LoginState::MFA_REQUIRED,
    ];

    return (
        in_array(_session('login_state'), $states) &&
        _session('loggedin') !== ''
    );
}

function guest()
{
    return (!authenticated() && !partially_authenticated());
}

function svg($path) {
	return (file_exists($path) ? file_get_contents($path) : '');
}

function ajaxSuccess()
{
	echo '{"response": "ok"}';
}

function ajaxError($error)
{
	echo json_encode(['error'=>$error]);
}

function ajaxResponse($response)
{
	echo json_encode(['response'=>$response]);
}

function isSSL()
{
	if (isset($_SERVER['SERVER_PORT_SECURE'])) {
		return ($_SERVER['SERVER_PORT_SECURE'] == 1);
	}

	if (isset($_SERVER['REQUEST_SCHEME'])) {
		return ($_SERVER['REQUEST_SCHEME'] == 'https');
	}

	if (isset($_SERVER['HTTPS'])) {
		return ($_SERVER['HTTPS'] == 'on');
	}

	if (isset($_SERVER['SERVER_PORT'])) {
		return ($_SERVER['SERVER_PORT'] == 443);
	}

	return false;
}

function echoln($text)
{
	if (php_sapi_name() == 'cli') {
		printf("%s\n", $text);
	}
	else {
		printf("%s<br>", $text);
	}
}

function _trace($var)
{
	printf("<pre>%s</pre>", print_r($var, true));
}

function unauthorized()
{
	View::render('common/unauthorized', 'Unauthorized');
	exit;
}

function render(string $view, string $title, array $data=[], string $layout='layout')
{
    View::render($view, $title, $data, $layout);
}

function component(string $name)
{
    View::component($name);
}

function client()
{
    $client = _session('client');

    if ($client === '') {
        $client = (new Common())->getConfig('CLIENTID', '');
        _session_put('client', $client);
    }
    
    return $client;
}

function config(string $name, string $reference='')
{
    return (new Common())->getConfig($name, $reference);
}

/**
 * Adds assetname, optionally with cache-busting (enabled by default)
 * 
 * @param string @assetName
 * @param bool $cacheBust (optional, defaults to true)
 * @return string
 */
function _asset(string $assetName, bool $cacheBust=true): string
{
    $fullPath = str_replace('//', '/', ASSETSDIR.'/'.$assetName);
    $path = FileSecurity::sanitizePath($fullPath, [ASSETSDIR]);
    $cacheBustStr = '';

    if ($cacheBust) {
        $assetFile = PUBLICPATH.'/'.ASSETSDIR.'/'.$assetName;
        $cacheBustStr = '?t='.strval(file_exists($assetFile) ? filemtime(PUBLICPATH.'/'.ASSETSDIR.'/'.$assetName) : strtotime('now'));
    }

    return $_ENV['SITEURL'].'/'.$path.($cacheBust ? $cacheBustStr: ''); 
}

function isNoWelcomeBar()
{
    return (defined('NOWELCOMEBAR') && NOWELCOMEBAR);
}