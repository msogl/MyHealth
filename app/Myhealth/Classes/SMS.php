<?php

namespace Myhealth\Classes;

class SMS
{
	public static function sendSMS($username, $number, $message)
	{
        $logMsg = "To: {$number}"; 
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $result = ($serverName === 'localhost1' ? 'FAIL' : (new Twilio())->sendSMS($number, $message));
		$event = ($result == 'Message sent' ? Event::EVENT_SMS_SEND_SUCCESS : Event::EVENT_SMS_SEND_FAIL);
        LogEvent($event, $username, $logMsg);
		return $result;
	}
}
