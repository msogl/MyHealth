<?php

namespace Myhealth\Classes;

use Myhealth\Core\Crypto;
use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Core\Utility;
use Myhealth\Models\EmailAccountModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use Greew\OAuth2\Client\Provider\Azure;

class Email
{
	public $sentVia = null;
	public $error = '';
	private $config = null;
	private $attachments = array();
	
	public function __construct($config=null)
	{
		if (!is_null($config)) {
			$this->config = $config;
			$this->config['password'] = Crypto::decrypt_msogl_2024($config['password']);
		}
		elseif (array_key_exists('SMTP', $GLOBALS)) {
			$this->config = $GLOBALS['SMTP'];
			$this->config['password'] = Crypto::decrypt_msogl_2024($config['password']);
		}
		else {
			$common = new Common();
			$this->config['host'] = $common->getConfig('SMTP', 'HOSTNAME');
			$this->config['port'] = $common->getConfig('SMTP', 'PORT');
			$this->config['ssl_tls'] = strtolower($common->getConfig('SMTP', 'SSLTLS'));
			$this->config['username'] = $common->getConfig('SMTP', 'USERNAME');
			$this->config['password'] = Crypto::decrypt_msogl_2024($common->getConfig('SMTP', 'PASSWORD'));
			$this->config['default_from'] = $common->getConfig('SMTP', 'DEFAULT_FROM');
			$this->config['default_from_name'] = $common->getConfig('SMTP', 'DEFAULT_FROM_NAME');
			$this->config['replyto'] = $common->getConfig('SMTP', 'REPLYTO');
			$this->config['mode'] = $common->getConfig('SMTP', 'MODE');
			$this->config['devmode_to'] = $common->getConfig('SMTP', 'DEVMODE_TO');

			if (_isNE($this->config['default_from'])) {
				$this->config['default_from'] = $common->getConfig('SMTP', 'FROM');
			}
		}
	}
	
	public function addAttachment($path, $name, $encoding="base64", $type="application/octet-stream")
	{
		$this->attachments[] = array(
			"path"=>$path,
			"name"=>$name,
			"encoding"=>$encoding,
			"type"=>$type,
		);
	}
	
	public function send($to, $from, $fromName, $subject, $body, $cc=null, $overrideDevMode=false)
	{
		$this->sentVia = 'SMTP';
		
		if (is_null($this->config)) {
			Logger::error("Cannot send email to {$to}. SMTP not configured.");
			return;	
		}

		// 12/12/2018 JLC In development mode, send only to logged in user
		// 06/04/2020 JLC Or to configured devmode address, in case of a CLI script where there is no logged in user
		// 02/02/2021 JLC Added $overrideDevMode parameter for testing external emails
		if (isset($this->config["mode"]) && !Utility::in("prod|production", strtolower($this->config["mode"]), true) && !$overrideDevMode) {
			$origTo = $to;
			
			if (isset($_SESSION["user"])) {
				$to = $_SESSION["user"]->email;
			}
			elseif (isset($this->config['devmode_to'])) {
				$to = $this->config['devmode_to'];
			}
			else {
				$to = '';		
			}
			
			$cc = null;		// and no CC
			Logger::info("Email - non-production mode; setting recipient from {$origTo} to {$to} and no CC");
		}

		if (Utility::isNullOrEmpty($to)) {
			Logger::error("Cannot send email. To address is empty.");
			return;	
		}
		
		try {
			$mail = new PHPMailer(true);
			$mail->IsSMTP();
			$mail->Host = $this->config['host'];
			$mail->Port = $this->config['port'];
			$mail->SMTPSecure = $this->config['ssl_tls'];
			
			if (!Utility::isNullOrEmpty($this->config['username'])) {
				$mail->SMTPAuth = TRUE;
				$mail->Username = $this->config['username'];
				$mail->Password = $this->config['password'];
			}
			
			$mail->IsHTML(true);
			
			if (isset($this->config['return_path']) && Utility::isNullOrEmpty($this->config['return_path'])) {
				$mail->From = $this->config['return_path'];
			}
			else {
				$mail->From = (Utility::isNullOrEmpty($from) ? $this->config['default_from'] : $from);
			}
			
			$mail->FromName = (Utility::isNullOrEmpty($fromName) ? $this->config['default_from_name'] : $fromName);
			$mail->Subject = $subject;
			$mail->Body = $body."<br/><br/>\n";
			$mail->AddAddress($to);
			
			if (!is_null($cc)) {
				$ccArr = explode(";", $cc);
				foreach ($ccArr as &$ccAddress) {
					$mail->AddCC($ccAddress);
				}
			}

			if (!_isNE($this->config['replyto'])) {
				$mail->AddReplyTo($this->config['replyto']);
			}

			if (count($this->attachments) > 0) {
				foreach($this->attachments as &$attachment) {
					$mail->AddAttachment(
						$attachment["path"],
						$attachment["name"],
						$attachment["encoding"],
						$attachment["type"]
					);
				}
			}
			
			// $mail->SMTPOptions = array(
			// 	"ssl"=>array(
			// 		"verify_peer"=>false,
			// 		"verify_peer_name"=>false,
			// 	),
			// );

			// ------------------------------------------------------------
			// SMTP OAUTH
			// ------------------------------------------------------------
			$emailAccountModel = new EmailAccountModel();
			$emailAccountDao = $emailAccountModel->load($mail->From, true);

			if ($emailAccountDao != null && $emailAccountDao->oauth_provider == 'AZURE') {
				Logger::debug("Sending email from {$mail->From} via SMTP OAUTH");
				$mail->AuthType = 'XOAUTH2';
				$mail->Username = '';
				$mail->Password = '';
				//$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;

				$provider = null;
				$clientId = null;
				$clientSecret = null;
				$tenantId = null;

				if ($emailAccountDao->oauth_provider == 'AZURE') {
					$clientId = $emailAccountDao->client_id;
					$clientSecret = Crypto::decrypt_msogl_2024($emailAccountDao->client_secret);
					$tenantId = $emailAccountDao->tenant_id;

					$provider = new Azure([
						'clientId' => $clientId,
						'clientSecret' => $clientSecret,
						'tenantId' => $tenantId ,
					]);
				}

				if ($provider != null && $clientId != null) {
					// Refresh the refresh token, if necessary
					if (_isNE($emailAccountDao->token_updated_date) ||
						Dates::dateDiff('h', $emailAccountDao->token_updated_date, 'now', true) > 24) {
						$token = $provider->getAccessToken('refresh_token', ['refresh_token' => $emailAccountDao->refresh_token]);
						$emailAccountDao->refresh_token = $token->getRefreshToken();
						$emailAccountDao->token_updated_date = Dates::sqlDateTime('now');
						$emailAccountModel->save($emailAccountDao);
						Logger::debug('OAUTH refresh_token refreshed');
					}

					$mail->setOAuth(
						new OAuth([
							'provider' => $provider,
							'clientId' => $emailAccountDao->client_id,
							'clientSecret' => $emailAccountDao->client_secret,
							'refreshToken' => $emailAccountDao->refresh_token,
							'userName' => $mail->From,
						])
					);

					$this->sentVia = 'SMTP OAUTH';
				}
			}

			if (!$mail->Send()) {
				Logger::error("Problem sending mail");
				throw new \Exception('Email.php: Unknown problem sending email');
				return FALSE;
			}
		}
		catch (\Exception $e) {
			Logger::error($e->getMessage());
			$this->error = $e->getMessage();
			throw $e;
		}

		Logger::info("Email sent to {$to}, subject = {$subject}");
		return TRUE;
	}

	public function sendAsync($to, $from, $fromName, $subject, $body)
	{
		if (!array_key_exists('SMTP', $GLOBALS)) {
			Logger::error("Cannot send email to {$to}. SMTP not configured.");
			return;	
		}

		// 12/12/2018 JLC In development mode, send only to logged in user
		if (isset($this->config["mode"]) && !Utility::in("prod|production", $this->config["mode"], true)) {
			$origTo = $to;
			$to = (isset($_SESSION["user"]) ? $_SESSION["user"]->email : "");
			Logger::info("Email - non-production mode; setting recipient from {$origTo} to {$to}");
		}

		if (Utility::isNullOrEmpty($to)) {
			Logger::error("Cannot send email. To address is empty.");
			return;	
		}

		Logger::info("Sending mail asynchronously to {$to}");
		$params = array();
		$params["to"] = &$to;

		if (!is_null($from)) {
			$params["from"] = &$from;
		}

		if (!is_null($fromName)) {
			$params["fromName"] = &$fromName;
		}

		$params["subject"] = &$subject;
		$params["body"] = base64_encode(!is_null($body) ? $body : "");
		
		$tempFile = str_replace("/", DIRECTORY_SEPARATOR, TEMPDIR."/".Utility::generateGUID().".tmp");
		file_put_contents($tempFile, json_encode($params));

		$sendMailPath = str_replace("/", DIRECTORY_SEPARATOR, APPPATH."/core/SendMail.php");

		$cmd = "php {$sendMailPath} \"".$tempFile."\"";

		if (substr(php_uname(), 0, 7) == "Windows"){
			pclose(popen("start /B ". $cmd, "r")); 
		}
		else {
			exec($cmd . " > /dev/null &");  
		}
	}

	public function Settings()
	{
		$html = "<pre>Hostname: ".$this->config["host"]."\n";
		$html .= "Port: ".$this->config["port"]."\n";
		$html .= "Username:".$this->config["username"]."\n";
		$html .= "Password: (not displayed)"."\n";
		$html .= "From:".$this->config["default_from"]."\n";
		$html .= "</pre>\n";
		return $html;
	}
}
