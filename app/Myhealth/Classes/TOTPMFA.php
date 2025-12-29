<?php

namespace Myhealth\Classes;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;

class TOTPMFA
{
	private $nextPage = '';
	private $otpSecret = null;

	public function createQRCode($label)
	{
		// 05/31/2023 JLC switched to sha1; Google Authenticator suddenly refusing sha256
		$otp = TOTP::create(null, 30, 'sha1');

		$options = new QROptions([
			//'version'      => 11,
			'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
			'eccLevel'     => QRCode::ECC_H,
			'scale'        => 4,
			'imageBase64'  => true,
		]);

		$otp->setLabel($label); // The label (string)
		$provisioningUri = $otp->getProvisioningUri();
		$qrCode = (new QRCode($options))->render($provisioningUri);
		_session_put('OTP_SECRET', $otp->getSecret());
		return $qrCode;
	}

	public function verify($code, $otpSecret)
	{
		$nextPage = '';

		if (!_isNE($otpSecret)) {
			$nextPage = 'home';
		}
		else if (_session('OTP_SECRET') !== '') {
			$otpSecret = _session('OTP_SECRET');
			$nextPage = 'totp-setup-success';
		}

		if (_isNE($otpSecret)) {
			return false;
		}

		if ($code != '') {
			$otp = TOTP::create($otpSecret);
			
			if ($otp->verify($code)) {
				$this->otpSecret = $otpSecret;
				$otpSecret = null;
				unset($otpSecret);
				_session_remove('OTP_SECRET');
				$this->nextPage = $nextPage;
				return true;
			}
		}

		return false;
	}

	public function getNextPage()
	{
		return $this->nextPage;
	}

	public function getSecret()
	{
		return $this->otpSecret;
	}
}