<?php

namespace Myhealth\Classes;

use Myhealth\Core\Logger;
use Myhealth\Models\SmsOptOutModel;

class Twilio
{
    private $twilioNumber;
    private $twilioAccountSID;
    private $twilioAuthToken;
    private $twilioMsgSvcSID;

    public function __construct()
	{
		$common = new Common();
        $this->twilioNumber = $common->getConfig('TWILIO', 'NUMBER');
        $this->twilioAccountSID = $common->getConfig('TWILIO', 'ACCOUNTSID');
        $this->twilioAuthToken = $common->getConfig('TWILIO', 'AUTHTOKEN');
        $this->twilioMsgSvcSID = $common->getConfig('TWILIO', 'MSGSVCSID');
    }

    public function sendSMS($toNumber, $message)
	{
		if ($this->twilioNumber == '' || $this->twilioAccountSID == '' || $this->twilioAuthToken == '') {
			$sendSMS = 'Twilio not configured';
			Logger::warn($sendSMS);
			return $sendSMS;
		}

        if (!startsWith($toNumber, '+1')) {
            $toNumber = '+1'.$toNumber;
        }

		Logger::info('Twilio send SMS to '.$toNumber);

        if ((new SmsOptOutModel())->isOptedOut($toNumber, $this->twilioNumber)) {
            Logger::error("{$toNumber} is opted out of SMS. Not sending");
            return "Recipient opted out";
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/'.$this->twilioAccountSID.'/Messages.json';
		$params = 'To='.$toNumber.'&From='.$this->twilioNumber.'&Body='.urlencode($message);

        if (!_isNE($this->twilioMsgSvcSID)) {
            $params .= '&MessagingServiceSid='.$this->twilioMsgSvcSID;
        }

        //logIt($params);
		
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0); // 0 = Don't return the header, 1 = Return the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return contents as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);     // Force TLS 1.2
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Accept-Charset: utf-8',
            'Accept: application/json',
            'Authorization: Basic '.base64_encode("{$this->twilioAccountSID}:{$this->twilioAuthToken}"))
		);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);
		
		if (!curl_errno($ch)) {
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($httpCode == 401) {
				$msg = 'Twilio authentication failed';
				Logger::error($msg);
				return $msg;
			}
			else {
				Logger::debug($response);
				$resp = json_decode($response);
                Logger::debug($resp);
				if (isset($resp->sid)) {
					return 'Message sent';
				}
				elseif (isset($resp->code)) {
					return 'Message not sent';
				}
			}
		}

		curl_close($ch);
		return false;
    }

	public function receiveSMS($fromNumber, $body)
	{
        if (_isNE($fromNumber) || _isNE($body)) {
            return;
        }

        if (!$this->isSigned()) {
            return;
        }

        // ------------------------------------------------------------
        // NOTE: By default, Twilio handles START, STOP, and HELP.
        // START and STOP are passed along. HELP is not. Responses
        // are sent by Twilio.
        // ------------------------------------------------------------
        // Look for specific commands
        $cmd = trim($body);

        if (in_array($cmd, ['START','UNSTOP','SUBSCRIBE'])) {
            Logger::debug("Removing SMS opt out for {$fromNumber}");
            (new SmsOptOutModel())->removeOptOut($fromNumber, $this->twilioNumber);
        }
        elseif (in_array($cmd, ['STOP','CANCEL','UNSUBSCRIBE', 'QUIT'])) {
            Logger::debug("Opting out {$fromNumber} for SMS");
            (new SmsOptOutModel())->optOut($fromNumber, $this->twilioNumber);
        }

        $notify = (new Common())->getConfig('TWILIO', 'NOTIFYONRECEIPT');
        if ($notify != '') {
            try {
                $email = new Email();
                $email->send('jclark@msogl.com', null, null, 'Twilio SMS received from '.trim($fromNumber), $body);
            }
            catch(\Exception $e) {
                logIt(Event::EVENT_DEBUG, 'SMTP: '.$e->getMessage());
                logIt('SMTP: '.$e->getMessage());
            }
		}
	}

    public function isSigned()
    {
        if (!isset($_SERVER['HTTP_X_TWILIO_SIGNATURE'])) {
            Logger::debug('X_TWILIO_SIGNATURE is missing');
            return false;
        }

        $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'];

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
        $url = $protocol."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        return $this->validateSignature($signature, $url);
    }

    public function validateSignature($signature, $url)
    {
        // Sign the data ourselves
        if (!empty($_POST)) {
            $data = $_POST;
            ksort($data);

            foreach($data as $key=>$value) {
                $valueArr = (is_array($value) ? array_unique($value) : [$value]);
                sort($valueArr);

                foreach($valueArr as $item) {
                    $url .= $key.$item;
                }
            }
        }

        $signed = base64_encode(hash_hmac('sha1', $url, $this->twilioAuthToken, true));

        // Compare our signed data with Twilio's signature
        return hash_equals($signature, $signed);
    }
}
