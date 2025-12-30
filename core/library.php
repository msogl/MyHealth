<?php

use Myhealth\Core\Crypto;
use Myhealth\Core\Dates;
use Myhealth\Core\DB;
use Myhealth\Core\Html;
use Myhealth\Core\Logger;
use Myhealth\Core\Utility;

define('GLOBAL_SALT', 'sc@ryS!t3s');

function Request($key, $sanitize=true)
{
	$value = '';
    
    // Ajax calls without Content-Type set won't
    // populate $_GET or $_POST. Have to pull it from php://input
    if (empty($_POST) &&
        empty($_GET) &&
        isset($_SERVER['HTTP_CONTENT_TYPE'])) {
        if ($_SERVER['HTTP_CONTENT_TYPE'] === 'application/json') {
            $_POST = json_decode(file_get_contents("php://input"), true);
        }
        else {
            parse_str(file_get_contents("php://input"), $_POST);
        }
    }

	if (isset($_POST[$key])) {
		$value = $_POST[$key];
	}
	elseif (isset($_GET[$key])) {
		$value = $_GET[$key];
	}

    if (is_array($value)) {
        foreach($value as &$val) {
            $val = str_replace(chr(0), '', $val);
            $val = str_replace('%3D', '=', $val);
            $val = ($sanitize ? Html::sanitize($val) : $val);
        }
        
        return $value;
    }

	$value = str_replace(chr(0), '', $value);

	// This should be decoded for us already, but sometimes it doesn't work, like
	// via ajax.
	$value = str_replace('%3D', '=', $value);
	return ($sanitize ? Html::sanitize($value) : $value);
}

function aspDate()
{
	return date('m/d/Y');
}

function aspTime()
{
	return date('H:i:s');
}


function Contains($haystack, $needle)
{
	return (strpos($haystack, $needle) !== false);
}

/**
 * _isNE
 * 
 * Check if null, empty string or 0 (integer)
 * 
 * @param string $var
 * 
 * @return bool
 */
function _isNE($var)
{
	return Utility::isNullOrEmpty($var);
}

/**
 * _isNEZ
 * 
 * Check if null, empty string or 0 (integer)
 * 
 * @param string $var
 * 
 * @return bool
 */
function _isNEZ($var)
{
	return (_isNE($var) || (int)$var == 0);
}

function getPage()
{
	return basename($_SERVER['PHP_SELF']);
}

/**
 * Returns combination of $_SERVER keys into full url.
 */
function getPageUrl()
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return '';
    }

    $protocol = ($_SERVER['HTTPS'] ?? '' === 'on' ? 'https' : 'http');
    return "{$protocol}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

function getHome()
{
    $url = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL);
    return dirname($url);
}

function libFormatName($LastName, $FirstName, $Initial, $Title, $Person)
{
	$newName = $LastName;

	if ($FirstName != '' && $FirstName != '.') {
		$newName .= ($Person == 1 ? ', ' : ' ').trim($FirstName.' '.$Initial);
	}

	if ($Title != '') {
		$newName .= ' '.$Title;
	}

	// In case there is a comma already and we inserted another one...
	$newName = str_replace(",,", ",", $newName);
	return $newName;
}

function libFormatDate($toFormat)
{
	return Dates::toString($toFormat, Dates::DATE_FORMAT);
}

function libFormatDateTime($datetime, $ampm=false)
{
	return ($ampm ? date('m/d/Y g:i:s A', strtotime($datetime)) : date('m/d/Y H:i:s', strtotime($datetime)));
}

function sqlDate($datetime)
{
	return libFormatDt($datetime, "yyyy-mm-dd");
}

function sqlDateTime($datetime)
{
	return libFormatDt($datetime, "yyyy-mm-dd")." ".libFormatTm($datetime, "hh:mm:ss");
}

function ReadTemplate($filename)
{
	return ReadTemplateFromPath($filename, "");
}

function ReadTemplateFromPath($filename, $templatePath=null)
{
    $pathOk = false;

	if (_isNE($templatePath)) {
		$templatePath = 'templates/';

        // Try first with client-specific template
        $client = strtolower(client());
        $filePath = fixpath(APPPATH."/{$templatePath}/{$client}/{$filename}");
        $pathOk = file_exists($filePath);
    }

    if (!$pathOk) {
        $filePath = fixpath(APPPATH."/{$templatePath}/{$filename}");
    }

    if (!file_exists($filePath)) {
        return '';
    }

    $contents = file_get_contents($filePath);
    return mb_convert_encoding($contents, 'UTF-8', mb_detect_encoding($contents, 'UTF-8, ISO-8859-1', true));
}

function TemplateExists($filename, $templatePath=null)
{
	if (_isNE($templatePath)) {
		$templatePath = 'templates/';
	}
	
	$filePath = realpath(APPPATH.'/'.$templatePath).'/'.$filename;

	return file_exists($filePath);
}

function LogEvent($eventType, $userId, $description)
{
	$db = new DB();
	$sql = "exec LogEvent ?, ?, ?, ?";
    $ipAddress = $_SESSION['remote_ip'] ?? GetRemoteIPAddress();

	$params = [
		$eventType,
		$userId,
		$description,
        $ipAddress,
    ];

	$db->executeSQL($db->QCMembersDB, $sql, $params);
}

function iif($Expression, $Iftrue, $Iffalse)
{
	// Don't really need this, but ASP didn't have a ternary function, so for compatibility sake
	return($Expression ? $Iftrue : $Iffalse);
}

function libFormatDt($toFormat, $format)
{
	if ($toFormat == '') {
		return $toFormat;
	}

	$origToFormat = $toFormat;

	if (strpos($toFormat, " 00:00:00") !== false) {
		$toFormat = substr($toFormat, 0, strpos($toFormat, " 00:00:00"));
	}

	$toFormat = strtotime($toFormat);
	
	try {
		if ($format == "m/d/yy") {
			return date('n/j/y', $toFormat);
		}
		elseif ($format == "m/d/yyyy") {
			return date('n/j/Y', $toFormat);
		}
		elseif ($format == "mm/dd/yyyy") {
			return date('m/d/Y', $toFormat);
		}
		elseif ($format == "mm-dd-yyyy") {
			return date('m-d-Y', $toFormat);
		}
		elseif ($format == 'yymmdd') {
			return date('ydm', $toFormat);
		}
		elseif ($format == 'ccyymmdd') {
			return date('Ymd', $toFormat);
		}
		elseif ($format == 'mm dd yy') {
			return date('m d y', $toFormat);
		}
		elseif ($format === 'SQLTZ') {
			return date('Y-m-d\TH:i:s\Z', $toFormat);
		}
		elseif ($format === 'yyyy-mm-dd') {
			return date('Y-m-d', $toFormat);
		}
		elseif ($format == "DDD, MMM d, yyyy") {
			return date('l, F j, Y', $toFormat);
		}
		else {
			return $origToFormat;
		}
	}
	catch(Exception $e) {
		return $origToFormat;
	}
}

function libFormatTm($toFormat, $format)
{
	if ($toFormat == "") {
		return $toFormat;
	}

	$toFormat = strtotime($toFormat);

	if ($format == "hhmm") {
		return date('Hi', $toFormat);
	}
	elseif ($format == "hhmmss") {
		return date('His', $toFormat);
	}
	elseif ($format == "hh:mm:ss") {
		return date('H:i:s', $toFormat);
	}
	elseif ($format == "h:mma") {
		return date('g:ia', $toFormat);
	}
	else {
		return $toFormat;
	}
}

function libLeading($text, $padChar, $size)
{
	return str_pad($text, $size, $padChar, STR_PAD_LEFT);
}

function libTruncate($text, $size)
{
	if (strlen($text) > $size) {
		return substr($text, 0, $size);
	}

	return $text;
}

function libFixedWidth($text, $size)
{
	if ($text == null) {
		$text = '';
	}

	if (strlen($text) < $size) {
		return $text.str_repeat(' ', $size - strlen($text));
	}
	else {
		return libTruncate($text, $size);
	}
}

function libNumericOnly($text)
{
	return preg_replace('/\D/', '', $text);
}

function libFormatPhone($PhoneNum)
{
	if (($PhoneNum == "") || (strlen($PhoneNum) != 10)) {
		return $PhoneNum;
	}
	else {
        return "(".substr($PhoneNum, 0, 3).") ".substr($PhoneNum, 3, 3)."-".substr($PhoneNum, -4);
	}
}

function generateGUID()
{
	// adapted from http://guid.us/GUID/PHP
	if (function_exists("com_create_guid")) {
		return preg_replace("/{|}/", "", strtoupper(com_create_guid()));
	}
	else {
		mt_srand();
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$guid = substr($charid, 0, 8)."-"
			.substr($charid, 8, 4)."-"
			.substr($charid,12, 4)."-"
			.substr($charid,16, 4)."-"
			.substr($charid,20,12);
		return preg_replace("/{|}/", "", strtoupper($guid));
	}
}

function notZero($data, $returnIfZero)
{
	return (_isNEZ($data) ? $returnIfZero : $data);
}

function csrfDetect($bypassCSRF=null)
{
	if (!is_null($bypassCSRF)) {
		$page = getPage();
		if (in_array($page, $bypassCSRF)) {
			return;
		}
	}

	if (_session('loggedin') === '') {
		return;
	}

	if (count($_GET) > 0 || count($_POST) > 0) {
		if (Request("token") == "") {
			if (count($_GET) > 0) {
				logIt('Security issue discovered: '.json_encode($_GET));
			}
			else {
				logIt('Security issue discovered: '.json_encode($_POST));
			}
			echo "Security issue discovered";
			exit;
		}
		elseif (Request("token") != encrypt(session_id())) {
            Logger::info('CSRF token mismatch');
			redirect('home');
			exit;
		}
	}
}

function logBadScript($scriptType)
{
	// Log IP address, user id and parameters

	$logLines = $scriptType." Detected!".PHP_EOL;
	$logLines .= "User ID: "._session('loggedin').PHP_EOL;
	$logLines .= "IP Address: ".$_SERVER['REMOTE_ADDR'].PHP_EOL;
	$logLines .= "User Agent: ".$_SERVER['HTTP_USER_AGENT'].PHP_EOL;
	$logLines .= "Referrer: ".$_SERVER['HTTP_REFERER'].PHP_EOL;

	if ($scriptType == "CSRF") {
		$logLines .= "Session: ".encrypt(session_id()).PHP_EOL;
		$logLines .= "Token:   ".Request("token").PHP_EOL;
	}
	
	$logLines .= "Query String (GET): ".implode('&', $_GET).PHP_EOL;

	if (count($_POST) > 0) {
		try { // Can't access with multipart-form (file upload);
			if (count($_POST) > 0) {
				$logLines .= "Form Variables (POST): ".PHP_EOL;

				foreach($_POST as &$Item) {
					$logLines .= "    ".$Item." = ".$Item.PHP_EOL;
				}
			}
		}
		catch(Exception $e) {}
	}

	logIt($logLines);
}

function showDetectionPage()
{
	$detected = '<div style="font-family:Verdana,Arial,Helvetica,Helv,San-serif;font-size:11pt;">';
	$detected .= '<p style="height:64px;"></p><p style="text-align:center;font-size:16pt;font-weight:bold;color:White;background-color:Red;height:60px;padding-top:14px;">Potentially harmful code detected! Terminating script.</p>';
	$detected .= '<div style="padding:0 15%;">Why are you seeing this message? We have detected script code passed in as parameters to this page that could be potentially harmful. This could be either Cross Site Scripting (XSS), Cross Site Request Forgery (CSRF) or SQL Injection. We have logged your IP address and the parameters passed in. if (you feel you are receiving this page in error, please contact us so we can review the logs to see what the issue is.</div></div>';

	echo $detected;
	exit;
}

function encrypt($text)
{
	return md5(GLOBAL_SALT."|".$text);
}

function getTokenParam()
{
	return "&token=".getTokenOnly();
}

function getTokenOnly()
{
	return encrypt(session_id());
}

function noBlank($value)
{
	return (_isNE($value) ? "0" : $value);
}

function URLEncodeComponent($value)
{
    $value = urlencode($value);
    $value = str_replace("/", "%2F", $value);
    $value = str_replace("+", "%20", $value);
	return $value;
}

function HttpWrite($text)
{
	echo URLEncodeComponent($text);
}

function epoch()
{
	return time();
}

function httpGet($url, $params, $authorization=null)
{
	if (strpos(strtolower($params), 'password') === false) {
		logIt($url.'?'.$params);
	}

	$headers = null;
	if (!_isNE($authorization)) {
		$headers = array('Authorization: Basic '.$authorization);
	}

	$resp = http('GET', $url, $params, $headers);
	return $resp->response;
}

function httpPost($url, $params, $authorization=null)
{
	if (strpos(strtolower($params), 'password') === false) {
		logIt($url.'?'.$params);
	}

	$headers = null;
	if (!_isNE($authorization)) {
		$headers = array('Authorization: Basic '.$authorization);
	}

	$resp = http('POST', $url, $params, $headers);
	return $resp->response;
}

function http($reqType, $url, $params, $headers=null, &$status=null)
{
	$realUrl = ($reqType == 'GET' ? $url.'?'.$params : $url);
	
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $realUrl);
		curl_setopt($ch, CURLOPT_HEADER, 1); 			// 0 = Don't return the header, 1 = Return the header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// Return contents as a string
		curl_setopt($ch, CURLOPT_TIMEOUT, 0);			// Unlimited
			
		// See notes:
		// http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
		//curl_setopt($ch, CURLOPT_CAINFO, $ca); // Set the location of the CA-bundle
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		if (!is_null($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		if ($reqType == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$curl_response = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response = substr($curl_response, $header_size);
		$errno = curl_errno($ch);
		$error = ($errno > 0 ? curl_error($ch) : '');

		$responseObj = new stdClass();
		$responseObj->status = $status;
		$responseObj->errno = $errno;
		$responseObj->error = $error;
		$responseObj->response = $response;

		if($errno > 0) {
			logIt('Status '.$status);
			logIt('Curl error '.$errno.': '.$error);
			logIt($url);
			logIt($params);
			logIt($response);
		}

		if ($status != 200) {
			if ($status == 401) {
				$responseObj->response = 'Not authorized';
			}
			elseif ($status == 404) {
				$responseObj->response = 'Page not found';
			}
			else {
				$responseObj->response = '';
			}
		}

		return $responseObj;
	}
	catch(Exception $e) {
		logIt($e->getMessage());
		return '';
	}
}

function logIt($text)
{
	$msg = ((is_object($text) || is_array($text)) ? print_r($text, true) : $text);
	Logger::info($msg);
}

function checkSiteDown()
{
	if (php_sapi_name() == 'cli') {
		return;
	}

	$sitedown = ReadTemplate("sitedown.html");

	if ($sitedown != "") {
		echo $sitedown;
		exit;
	}
}

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) == $needle);
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0)
		return true;

	return (substr($haystack, -$length) == $needle);
}

function sessionStart()
{
	if (isset($_COOKIE["PHPSESSID"]) && $_COOKIE["PHPSESSID"] == "") {
		unset($_COOKIE["PHPSESSID"]);
	}
	
	session_name(APP_SESSION_NAME);
	@session_start();

	// add 1 hours every request
	setcookie(APP_SESSION_NAME, session_id(), time()+(60*60*1),
		ini_get("session.cookie_path"),
		ini_get("session.cookie_domain"),
		ini_get("session.cookie_secure"),
		ini_get("session.cookie_httponly")
	);

	if (php_sapi_name() == 'cli') {
		return;
	}
	
	// To prevent session fixaction, php.ini has session.use_trans_id = 0 and session.use_only_cookies = 1
	
	// defense against session hijacking
	if (isset($_SESSION['HTTP_USER_AGENT'])) {
		if ($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT'].PEPPER)) {
			session_destroy();
			session_name(APP_SESSION_NAME);
			session_start();
            $_SESSION['remote_ip'] = GetRemoteIPAddress();
			return;
		}
	}
	elseif (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
		$_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT'].PEPPER);
	}

    if (empty($_SESSION['remote_ip'])) {
        $_SESSION['remote_ip'] = GetRemoteIPAddress();
    }
}

function destroy_session()
{
	if (isset($_SESSION)) {
		@session_unset();
		@session_destroy();
		@session_write_close();
	}
	

	// 12/17/2020 JLC Commented out line below. Was doing some weird stuff with not saving cookies or session vars
	//libSetCookie(APP_SESSION_NAME, '', 'January 1, 2000');
	@session_name(APP_SESSION_NAME);
	@session_start();
	session_regenerate_id(true);
}

function end_session()
{
	destroy_session();
	clearAllCookies();
}

function clearAllCookies()
{
	foreach($_COOKIE as $key=>$value) {
		if (startsWith($key, 'MYH_') || startsWith($key, 'ASPSESSIONID') || startsWith($key, 'APSSESSIONID')) {
			if (startsWith($key, 'MYH_') && 
				(endsWith($key, '_username') || endsWith($key, '_trust'))) {
				continue;
			}

			libSetCookie($key, '', 'January 1, 2000');
		}
	}
}

function libSetCookie($cookieName, $cookieValue, $expires)
{
    if (empty($cookieName)) {
        return;
    }
    
	$securePhrase = ($_SERVER['SERVER_PORT_SECURE'] != "0" ? "Secure; " : "");
	$sameSitePhrase = "SameSite=Lax; ";

	if ($expires != "") {
		// Expire must be in format: <day-name (abbrev)>, <day> <month (abbrev)> <year> <hour>:<minute>:<second> GMT
		$expiresTimestamp = (is_string($expires) && !is_numeric($expires) ? strtotime($expires) : $expires);

        // 10/09/2025 JLC SMELL These two lines of code don't really do anything. What was I thinking? Attemping
        // to convert to Central time? If so, it's not the right way to do it. Anyway, $expires isn't used any
        // further, so this is pointless.
		//$tzOffset = -5;
        //$expires = Dates::dateAdd($expires, $tzOffset, 'hour');
		$expiresPhrase = "Expires=".date('l', $expiresTimestamp).", ";
		$expiresPhrase .= date('j', $expiresTimestamp)." ".date('M', $expiresTimestamp)." ".date('Y', $expiresTimestamp)." ";
		$expiresPhrase .= date('H:i:s', $expiresTimestamp)." GMT; ";
	}
	else {
		$expiresPhrase = "";
	}

	if ($cookieName == APP_SESSION_NAME || startsWith($cookieName, "MYH_")) {
		$newCookieName = $cookieName;
	}
	else {
		$newCookieName = "MYH_".$cookieName;
	}

	$cookie = $newCookieName."=".$cookieValue."; path=/; ".$expiresPhrase.$sameSitePhrase.$securePhrase."HttpOnly";
	header("Set-Cookie: ".$cookie);
}

function clearCookie($cookieName)
{
	if ($cookieName == APP_SESSION_NAME || startsWith($cookieName, "MYH_")) {
		libSetCookie($cookieName, '','January 1, 2000');
	}
	else {
		libSetCookie("MYH_".$cookieName, '', 'January 1, 2000');
	}
}

function getCookie($cookieName)
{

	if ($cookieName == APP_SESSION_NAME || startsWith($cookieName, "MYH_")) {
		$cName = $cookieName;
	}
	else {
		$cName = 'MYH_'.$cookieName;
	}

	return (isset($_COOKIE[$cName]) ? $_COOKIE[$cName] : '');
}

function InList($value, $list)
{
	if ($list == '') {
		return false;
	}

	$arr = explode('|', $list);
	for($ix=0;$ix<count($arr);$ix++) {
		if ($arr[$ix] == $value) {
			return true;
		}
	}

	return false;
}

function EncryptAESMSOGL($value, $base64encode=true)
{
	return Crypto::encrypt_msogl_2024($value, $base64encode);
}

function DecryptAESMSOGL($value)
{
	return Crypto::decrypt_msogl_2024($value);
}

function siteUrl($withPage=false)
{
	if (defined('SITEURL')) {
		return SITEURL;
	}

	$unsecured = array(
		'localhost',
		'127.0.0.1',
	);

	$serverPortSecure = (isset($_SERVER['SERVER_PORT_SECURE']) ? ($_SERVER['SERVER_PORT_SECURE'] == 1) : false);
	$serverName = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
	$httpPrefix = 'https://';

	if (HTTPS_ALWAYS_ON ||
		($serverPortSecure) ||
		(!HTTPS_ALWAYS_ON && !$serverPortSecure && !in_array($serverName, $unsecured))) {
			$httpPrefix = 'https://';
	}
	else {
		$httpPrefix = 'http://';
	}
	
	$domain = $serverName;
	$uri = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');

	if ($uri != '') {
		$qmark = strpos($uri, '?');
		if ($qmark !== false) {
			$uri = substr($uri, 0, $qmark-1);
		}

		$domain .= '/'.$uri;
		$domain = str_replace('//', '/', $domain);		// just in case there was a slash at the end of domain before appending the uri
	}

	if (!$withPage) {
		$page = substr($uri, strrpos($uri,"/")+1);
		$domain = str_replace('/'.$page, '', $domain);
	}

	$domain = $httpPrefix.$domain;

	return $domain;
}

function isLeap($year)
{
	return (date('L', strtotime('01/01/'.$year)) == 1);
}

function getAge($birthDate)
{
	return getAgeAsOf($birthDate, 'now');
}

function getAgeAsOf($birthDate, $asOfDate)
{
	// days since the birthdate    
	$days = Dates::dateDiff($birthDate, $asOfDate, true);
	$age = 0;

	// iterate through the years and account for leap years
    $birthYear = (int) Dates::year($birthDate);
    $asOfYear = (int) Dates::year($asOfDate);

	for($y=$birthYear; $y<=$asOfYear; $y++) {
		$daysInYear = (isLeap($y) ? 366 : 365);
		if ($days >= $daysInYear) {
			$days -= $daysInYear;
			$age++;
		}
	}

	return $age;
}

function NotesHTML($notes)
{
	$note = str_replace(chr(10), "", $notes ?? '');
	$note = str_replace(chr(13), "<br/>", $note);
	$note = str_replace("&#x0D;", "<br/>", $note);
	$note = str_replace("#x0D;", "<br/>", $note);
	$note = str_replace("&", "&amp;", $note);

	while(str_contains($note, "  ")) {
		$note = str_replace("  ", "&nbsp;&nbsp;", $note);
	}

	if (startsWith($note, "<br/>")) {
		$note = substr($note, 5);
	}

	return trim($note);
}

function isAllowedExtension($filename)
{
	$allowedExt = array("doc","docx","xls","xlsx","pdf","jpg","gif","png","tif","tiff","csv","txt","pptx","zip");

	foreach ($allowedExt as &$ext) {
		if (endsWith($filename, ".".$ext)) {
			return true;
		}
	}

	return false;
}

function FileExtension($filename)
{
	return pathinfo($filename, PATHINFO_EXTENSION);
}

/**
 * obfuscateEmail
 * For display purposes, shows only the first initial, then @ symbol and domain.
 * Example: john.doe@gmail.com becomes j*******@gmail.com
 */
function obfuscateEmail($email)
{
	$obfuscated = str_repeat('*', strlen($email));
    $parts = explode('@', $email, 2);

    if (count($parts) == 2) {
        $obfuscated = substr($email, 0, 1).str_repeat('*', strlen($parts[0])-1).'@'.$parts[1];
    }
    else {
        $obfuscated = str_repeat('*', strlen($email));
    }

	return $obfuscated;
}

function httpOverride($url)
{
	// This is used when testing a new installation or migration to a new server
	// where SSL does not yet exist. To override SSL settings, simply
	// place a file in the application's root directory named http_override
	// and the contents should be "http://". 
	if (file_exists(APPPATH.'/http_override')) {
		$override = trim(file_get_contents(APPPATH.'/http_override'));

		if (startsWith(strtolower($url), 'http')) {
			$url = str_replace('https://', $override, $url);
			$url = str_replace('http://', $override, $url);
		}
	}

	return $url;
}

function scriptName()
{
	$uri = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
	$pos = strrpos($uri, '/');
	return ($pos === false ? $uri : substr($uri, $pos+1));
}

function getDomain()
{
	return (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
}

function isDeveloper($userId)
{
    return \Myhealth\Classes\Permission::isDeveloper($userId);
}

function getObjectVars(&$object, $returnAll=true)
{
	$vars = get_object_vars($object);

	if ($returnAll) {
		$results = array();
		foreach ($vars as $property=>$val) {
			$results[] = $property;
		}

		return $results;
	}

	// We may want only the properties (attributes) that are part of the table, not necessarily
	// all attributes in the DAO. In that case, reference the DAO's attributes array, if it exists.
	if (array_key_exists('attributes', $vars) && is_array($object->attributes)) {
		$results = &$object->attributes;
	}
	else {
		$results = array();
		foreach ($vars as $property=>$val) {
			$results[] = $property;
		}
	}

	return $results;
}

function GetRemoteIPAddress()
{
	$vars = [
		'HTTP_X_CLIENT_IP',
		'HTTP_CLIENT_IP',
		'HTTP_X_REAL_CLIENT_IP',
		'HTTP_REAL_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_CLUSTER_CLIENT_IP',
		'HTTP_CF_CONNECTING_IP',		// CloudFlare
    ];

	foreach($vars as &$var) {
		$ipAddress = (!empty($_SERVER[$var]) ? trim($_SERVER[$var]) : '');
		if ($ipAddress == '') {
			continue;
		}

		if (strpos($ipAddress, ',') > 0) {
			$addr = explode(',', $ipAddress);
			$ipAddress = trim($addr[0]);
		} else {
			$ipAddress = trim($ipAddress);
		}

		$ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP);
		if ($ipAddress !== false) {
			return $ipAddress;
		}
	}

	// If we get here, real IP address not found in any of the known 
	// server variables we look for
	$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

	if ($ipAddress == '::1' || $ipAddress == '') {
		$ipAddress = '127.0.0.1';
	}

	/* Not sure I want this
    if ($ipAddress == '127.0.0.1') {
        $ipAddress = getHostByName(getHostName());
	}
	*/

	return $ipAddress;
}

function fixpath($path)
{
	$path = Utility::normalizePath($path);
	$path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
	return $path;
}
