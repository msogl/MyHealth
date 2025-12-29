<?php
//===================================================================
// Copyright by RPPG, 2009-Present. All rights reserved.
//===================================================================

use \Myhealth\Classes\Config;
use \Myhealth\Core\PRG;

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('session.gc_maxlifetime', (60*60*1));		// 1 hours

define('OBJECT_NOT_SUPPORTED', JSON_ERROR_UNSUPPORTED_TYPE);
//define('HASH_VERSION', 2);		// 01/31/2024 JLC DEPRECATED
define('HASH_VERSION', 3);			// 01/31/2024 JLC
define('SYSTEM_NAME', 'QC7');
define('PRODUCT_ID', 'MYHEALTH');
define('MBR_AUTH_POS_EXCLUDE', '21,25,26,31,32,33,34,50,51,52,55,56,61');
define('SUPERADMIN', 'jclark');
define('HTTPS_ALWAYS_ON', false);


define('APPPATH', realpath(__DIR__.'/..'));
define('PUBLICPATH', APPPATH.'/public');
define('LOGPATH', realpath(APPPATH.'/logs'));
define('LOGFILE', LOGPATH.'/myhealth_%Y%m%d.log');
define('TEMPDIR', realpath(APPPATH.'/tmp'));
define('EXT_LIBS', __DIR__.'/lib');
define('VIEWS', APPPATH.'/app/Myhealth/Views');
define('ASSETSDIR', 'assets');

define('DEFAULT_DATETIME_FORMAT', 'm/d/Y H:i:s');
define('DEFAULT_DATE_FORMAT', 'm/d/Y');
define('CHECKMARK', '<span style="color:#00C000;">&#10004;</span>');
define('XMARK', '<span style="color:#C00000;font-weight:bold;font-size:16px;">&times;</span>');
define('SPACER', '&nbsp;&nbsp;&nbsp;&nbsp;');
define('SELECTED', ' selected');
define('CHECKED', ' checked');
define('DEMOMODE', true);
define('ERRORDISPLAYMODE', 'DEV');

$GLOBALS['appRoot'] = '';	// Normally current directory, but can be overridden

require_once __DIR__.'/library.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/AutoLoader.php';
require_once __DIR__.'/lib/vendor/autoload.php';

if (!Config::loadDotEnv(APPPATH . '/config/.env')) {
    die('Invalid configuration. (E1)');
}

require_once __DIR__.'/../config/config.php';

ini_set('display_errors', (defined('ERRORDISPLAYMODE') && ERRORDISPLAYMODE == 'DEV' ? 'On' : 'Off'));

$handlers = set_error_handler('\Myhealth\Core\ErrorHandler::handleError');
register_shutdown_function('\Myhealth\Core\ErrorHandler::handleShutdown');

redirectToSecured();
sessionStart();

checkSiteDown();

// ------------------------------------------------------------
// PRG Pattern
// ------------------------------------------------------------

if (php_sapi_name() != 'cli') {
	(new PRG())->go();
}

$bypassCSRF = [
    'email-confirm',
    'mfa-verify',
    'verify-reset',
    'doctor-search',
];

csrfDetect($bypassCSRF);	// 11/21/10 JLC
