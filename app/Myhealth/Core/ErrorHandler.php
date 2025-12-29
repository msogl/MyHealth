<?php

namespace Myhealth\Core;

class ErrorHandler
{
	public static function handleShutdown()
	{
		if (in_array(ini_get('display_errors'), ['On', 1, true], true)) {
			return;
		}

		$lastError = error_get_last();

		if (is_null($lastError)) {
			return;
		}

		if ($lastError['type'] === E_ERROR) {
			// fatal error
			self::HandleError(E_ERROR, $lastError['message'], $lastError['file'], $lastError['line']);
		}
	}

	public static function handleError($errno, $errstr, $errfile, $errline)
	{
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			return false;
		}

		if ($errno == E_ERROR) {
			$errtype = 'Fatal';
		} elseif ($errno == E_RECOVERABLE_ERROR || $errno == E_USER_ERROR) {
			$errtype = 'Error';
		} elseif ($errno == E_WARNING || $errno == E_USER_WARNING) {
			$errtype = 'Warning';
		} elseif ($errno == E_NOTICE || $errno == E_USER_NOTICE) {
			$errtype = 'Notice';
		} elseif ($errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
			$errtype = 'Deprecated';
		} else {
			$errtype = $errno;
		}

		$msg = "{$errtype}: in {$errfile} on line {$errline}: " . strip_tags($errstr);

		if ($errtype == 'Fatal' || $errtype == 'Error' || $errtype == 'Warning') {
			Logger::PushBullet($msg);
		}

		if (php_sapi_name() == 'cli' || (isset($GLOBALS['ERROR_NO_RESPONSE']) && $GLOBALS['ERROR_NO_RESPONSE'])) {
			Logger::debug_log($msg);
		}
        else {
			$uuid = generateGUID();
			Logger::debug_log("REF {$uuid}: {$msg}");

			if (isDeveloper(_session('loggedin'))) {
				echo '<span style="font-weight:normal;color:Red;"><strong>' . $errtype . ":</strong> ($errstr} in {$errfile} on line {$errline}</span>";
			}

			// Unless we're in a dire situation, don't bother the user with errors.
			if ($errtype == 'Fatal' || $errtype == 'Error') {
				echo '<div style="position:fixed;left:0;bottom:0;width:100vw;z-index:9999;background-color:Red;color:white;text-align:left;height:20px;line-height:20px;">An error was encountered (REF ' . $uuid . ')</div>';
				echo "\n";
			}
		}
	}
}
