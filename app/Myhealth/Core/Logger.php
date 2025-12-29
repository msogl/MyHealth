<?php

namespace Myhealth\Core;

use Myhealth\Classes\Email;
use Myhealth\Classes\Syslog;

class Logger
{
	private static function user($withBrackets=true)
	{
		if (isset($_SESSION) && isset($_SESSION["user"]) && !_isNE($_SESSION["user"])) {
			if ($withBrackets) { 
				return "[" . $_SESSION["user"]->username . "]";
			}
			
			return  $_SESSION["user"]->username;
		}
		
		return "";
	}
	
	private static function message($message)
	{
		if (is_object($message) || is_array($message)) {
			return print_r($message, true);
		}
		
		return $message;
	}
	
	public static function info($message, $backtrace = null, $console = false)
	{
		self::logIt('[INFO]' . self::user() . ' ' . self::message($message), $backtrace, $console);
		self::sendToSyslog('info', self::user(false), self::message($message));
	}

	public static function error($message, $backtrace = null, $console = false)
	{
		self::logIt('[ERROR]' . self::user() . ' ' . self::message($message), $backtrace, $console);
		self::sendToSyslog('error', self::user(false), self::message($message));
	}

	public static function warn($message, $backtrace = null, $console = false)
	{
		self::logIt('[WARN]' . self::user() . ' ' . self::message($message), $backtrace, $console);
		self::sendToSyslog('warn', self::user(false), self::message($message));
	}

	public static function debug($message, $backtrace = null, $console = false)
	{
		if (!isset($GLOBALS["DEBUG"]) || $GLOBALS["DEBUG"] !== false) {
            self::logIt('[DEBUG]' . self::user() . ' ' . self::message($message), $backtrace, $console);
			self::sendToSyslog('debug', self::user(false), self::message($message));
        }
	}

	public static function debug_log($message, $console = false) {
		$filename = 'debug_%Y%m%d.log';
		$logFile = self::getLogFile();
		$logFile = str_replace(basename($logFile), $filename, $logFile);
		self::logIt($message, null, $console, $logFile);
	}

	private static function getLogFile()
	{
		if (!empty($GLOBALS['LOGFILE'])) {
			$logFile = $GLOBALS['LOGFILE'];
		}
		elseif (defined('LOGFILE')) {
			$logFile = LOGFILE;
		}
		else {
			$logFile = realpath(__DIR__.'/..').'/logs/myhealth_%Y%m%d.log';
		}

		return $logFile;
	}

	public static function logToFile($message, $logPath, $backtrace = null)
	{
		self::logIt($message, $backtrace, false, $logPath);
	}

	private static function logIt($message, $backtrace = null, $console = false, $logFile = null)
	{
		$dt = new \DateTime();
		$message = '['.$dt->format('Y-m-d H:i:s').']['._session('loggedin').']'.$message;

		if (is_null($logFile)) {
			$logFile = self::getLogFile();
		}
		
		$logFile = str_replace("%Y", date("Y"), $logFile);
		$logFile = str_replace("%m", date("m"), $logFile);
		$logFile = str_replace("%d", date("d"), $logFile);

		if ($backtrace != null) {
			$message .= ' [in ';
			if (!_isNE($backtrace[0]['class']))
				$message .= $backtrace[0]['class'] . '.';
			
			$message .= $backtrace[0]['function'] . '()]';
		}
		
		$message .= "\n";
		
		//if (!file_exists($logFile)) {
		//	self::deleteOldLogs();			
		//}

		file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);

		if ($console || (isset($GLOBALS["LOGGER_CONSOLE"]) && $GLOBALS["LOGGER_CONSOLE"])) {
			printf('%s', $message);
		}
	}
	
	public static function PushBullet($title, $body=null, $force=false)
	{
		// bug out if PushBullet is not configured
		if (!isset($GLOBALS["PUSHBULLET_ACCESSTOKEN"])) {
			return ;
		}
		
		if (file_exists(TEMPDIR."/pbdown.txt")) {
			return;
		} 
		
		$expirationSeconds = 120;
		$postData = array();
		$postData["type"] = "note";
		$postData["title"] = "({$GLOBALS["PUSHBULLET_PREFIX"]}) {$title}";
		
		if (!is_null($body)) {
			$postData["body"] = $body;
		}
		
		if ($force) {
			self::doPushBullet($postData);
			return;
		}

		// Add message to queue
		$message = json_encode($postData);
		
		if (!isset($GLOBALS["PUSHBULLET_MESSAGE_QUEUE"])) {
			$GLOBALS["PUSHBULLET_MESSAGE_QUEUE"] = array(
				array("message"=>$message, "expires"=>(microtime(true)+$expirationSeconds), "datetime"=>date("Y-m-d H:i:s"))
			);
		}
		else {
			// Don't allow duplicate messages in the queue
			$addToQueue = true;
			
			foreach($GLOBALS["PUSHBULLET_MESSAGE_QUEUE"] as &$queue) {
				if ($queue["message"] == $message) {
					$addToQueue = false;
					break;
				}
			}
			
			if ($addToQueue) {
				$GLOBALS["PUSHBULLET_MESSAGE_QUEUE"][] = array("message"=>$message, "expires"=>(microtime(true)+$expirationSeconds), "datetime"=>date("Y-m-d H:i:s"));
			}
		}

		// Send every 30 seconds
		if (isset($GLOBALS["PUSHBULLET_LASTSENTTIME"])) {
			if ((microtime(true) - $GLOBALS["PUSHBULLET_LASTSENTTIME"]) <= 30) {
				return;
			}
		}

		self::PushBulletFlushQueue();
	}

	public static function PushBulletFlushQueue()
	{
		self::info(count($GLOBALS["PUSHBULLET_MESSAGE_QUEUE"])." messages in PushBullet queue");

		$expiredCount = 0;
		$pbFile = LOGPATH."/pushbullet_expired_messages_%Y%m%d.log";
		$pbFile = str_replace("%Y", date("Y"), $pbFile);
		$pbFile = str_replace("%m", date("m"), $pbFile);
		$pbFile = str_replace("%d", date("d"), $pbFile);
		
		foreach($GLOBALS["PUSHBULLET_MESSAGE_QUEUE"] as &$queue) {
			// Only push if it's not expired in the queue
			if (microtime(true) > $queue["expires"]) {
				$logMsg = "[{$queue["datetime"]}] {$queue["message"]}\n";
				file_put_contents($pbFile, $logMsg, FILE_APPEND | LOCK_EX);
				$expiredCount++;
				continue;
			}

			$postData = json_decode($queue["message"], true);
			self::doPushBullet($postData);
		}
		
		if ($expiredCount > 0) {
			Logger::info($expiredCount." expired messages not sent; flushed to disk");
		}

		// Clear queue
		$GLOBALS["PUSHBULLET_MESSAGE_QUEUE"] = null;
		unset($GLOBALS["PUSHBULLET_MESSAGE_QUEUE"]);
		
		// Reset timer
		$GLOBALS["PUSHBULLET_LASTSENTTIME"] = microtime(true);
		Logger::info("Last sent: ".$GLOBALS["PUSHBULLET_LASTSENTTIME"]);
	} 

	private static function deleteOldLogs()
	{
		$logFolder = dirname(LOGFILE);
		
		if (file_exists($logFolder)) {
			foreach (new \DirectoryIterator($logFolder) as $fileInfo) {
				if ($fileInfo->isDir() || $fileInfo->getExtension() != "log") {
					continue;
				}
				
				// delete files older than 14 days
				if (time() - $fileInfo->getMTime() >= 14*24*60*60) {
					unlink($fileInfo->getRealPath());
				}
			}
		}
	}
	
	private static function doPushBullet(&$postData)
	{
		Logger::info(json_encode($postData));
		if (isset($postData["queue_time"])) {
			$postData["queue_time"] == null;
			unset($postData["queue_time"]);
		}
		
		// push to each configured account
		foreach($GLOBALS["PUSHBULLET_ACCESSTOKEN"] as &$accessToken) {
			$curl = curl_init();
			
			// Apply various settings
			curl_setopt($curl, CURLOPT_URL, $GLOBALS["PUSHBULLET_SERVICEURL"]);
			curl_setopt($curl, CURLOPT_HEADER, 0); // 0 = Don’t return the header, 1 = Return the header
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // Return contents as a string
				
			// See notes:
			// http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
			//curl_setopt($ch, CURLOPT_CAINFO, $ca); // Set the location of the CA-bundle
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			curl_setopt($curl, CURLOPT_USERPWD, $accessToken);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
			
			$curl_response = curl_exec($curl);
			//$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			//$header = substr($curl_response, 0, $header_size);
			//$body = substr($curl_response, $header_size);
			curl_close($curl);
		}
	}

	public static function ErrorHandler($errno, $errstr, $errfile, $errline)
	{
		self::error("PHP Error {$errno}");
		self::error("          {$errstr}");
		self::error("          {$errfile}");
		self::error("          {$errline}");
		
		if (isset($GLOBALS["E_NOTICE_ACTION"])) {
			if ($GLOBALS["E_NOTICE_ACTION"] === "exit") {
				die("Notice: Error: {$errstr} on {$errfile}:{$errline}\n\n");
			}
			elseif ($GLOBALS["E_NOTICE_ACTION"] === "trap") {
				try {
					throw new \Exception($errstr);
				}
				catch(\Exception $e) {
					$stophere = true;
				}
			}
        }
		
		return false;
	}

	public static function EmailNotification($subject, $body)
	{
		if (!isset($GLOBALS["LOGGER_EMAIL_ADDRESS"])) {
			return ;
		}

		$email = new Email();
		$subject = "({$GLOBALS["LOGGER_EMAIL_PREFIX"]}) {$subject}";
		$addresses = explode(";", $GLOBALS["LOGGER_EMAIL_ADDRESS"]);

		foreach($addresses as &$address) {
			$email->sendAsync($address, null, null, $subject, $body);
		}
	}
	
	public static function sendToSyslog($type, $user, $message)
	{
		if (!isset($GLOBALS['SYSLOG']) || $GLOBALS['SYSLOG']['enabled'] === false) {
			return;
		}
		
		if ($type == 'info' || $type == 'audit') {
			$severity = 6;
		}
		elseif ($type == 'warn') {
			$severity = 4;
		}
		elseif ($type == 'error') {
			$severity = 3;
		}
		elseif ($type == 'debug') {
			$severity = 7;
		}

		if ($message != null) {
			$message = \str_replace("\r\n", "x0Dx0A", $message);
			$message = \str_replace("\r", "x0D", $message);
			$message = \str_replace("\n", "x0A", $message);
			$message = \str_replace("\\", "\\\\", $message);
			$message = \str_replace("\"", "\\\"", $message);
			$message = \str_replace('[', '\[', $message);
			$message = \str_replace(']', '\]', $message);
		}
		
		$syslog = new Syslog();
		$syslog->SetFacility(1);
		$syslog->SetSeverity(7);
		$syslog->SetHostname($GLOBALS['SYSLOG']['hostname']);
		$syslog->SetFqdn($GLOBALS['SYSLOG']['fqdn']);
		$syslog->SetIpFrom($GLOBALS['SYSLOG']['ip_from']);
		$syslog->SetProcess($GLOBALS['SYSLOG']['process']);
		$syslog->SetContent($type.'|'.$user.'|'.$message);
		$syslog->SetServer($GLOBALS['SYSLOG']['syslog_server']);
		$syslog->SetPort($GLOBALS['SYSLOG']['syslog_port']);
		$syslog->Send();
	}
}
