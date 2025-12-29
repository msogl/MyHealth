<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Daos\AccountDAO;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Models\MFATrustModel;

class MfaController
{
    public function mfa()
    {
        if (_session('mfauser') == '') {
            redirect('home');
        }

        $accountDao = (new AccountModel())->getByUsername(_session('mfauser'));
        
        if (is_null($accountDao)) {
            Logger::error(__FUNCTION__.': Account not found for user '._session('mfauser'));
            redirect('home');
        }

        $mfaMethods = $this->getMfaMethods($accountDao);

        /**
         * Get active methods
         */
        $activeMethods = array_keys(
            array_filter($mfaMethods, function($key) use ($mfaMethods) {
            return ($mfaMethods[$key] === true);
            }, ARRAY_FILTER_USE_KEY)
        );

        /**
         * If there's only one active method, skip the MFA select page
         * and go straight into the process.
         */
        if (count($activeMethods) == 1) {
            $_POST['type'] = $activeMethods[0];
            $this->mfaSelect();
            return;
        }

        if ($mfaMethods['EMAIL']) {
            [$recipient, $domain] = explode('@', $accountDao->Email, 2);
            $maskedEmail = substr($recipient, 0, 1).str_repeat('*', strlen($recipient)-1);
            $maskedEmail .= '@'.$domain;
        }

        if ($mfaMethods['SMS']) {
            $smsnumber = '***-***-' . substr($accountDao->SMSNumber, -4);
        }

        $passInData = [
            'mfaMethods' => &$mfaMethods,
            'maskedEmail' => $maskedEmail ?? '',
            'smsnumber' => $smsnumber ?? '',
        ];

        View::addLink('css/mfa.css');
        View::render('mfa-select', 'MFA', $passInData);
    }

    /**
     * Initiated when the user chooses a specific method (e.g. email, sms, totp)
     * from a list of methods.
     */
    public function mfaSelect()
    {
        if (_session('mfauser') == '') {
            redirect('home');
        }

        $type = Request('type');

        if ($type == '') {
            redirect('mfa');
        }

        $accountModel = new AccountModel();
        $accountModel->getByUsername(_session('mfauser'));

        $nextPage = match(strtoupper($type)) {
            'EMAIL', 'SMS' => 'mfa-verify?'.$accountModel->generateAndSendMFACode($type),
            'TOTP' => 'totp',
            'DUO' => 'notsupported',
            default => null
        };

        if ($nextPage == null) {
            redirect('mfa');
        }

        redirect($nextPage);
    }

    public function verify()
    {
        $id = Request('id');
        $mfaCode = Request('c');
        $viasms = (Request('s') === '1');
        $rememberBrowser = (Request('r') === '1');
        $enteredCode = Request('code');
        $resend = (Request('resend') == '1');

        $displayMsg = '';
        $errorMsg = '';
        $showResendLink = false;
        $viasms = false;
        $trustBrowserEnabled = (config('MFA', 'ENABLE TRUST BROWSER') == 'true');
        $startDateTime = '';
        $client = client();

        if ($id == '') {
            $errorMsg = 'Unexpected error (1)';
        }
        else {
            $idObj = json_decode(DecryptAESMSOGL($id));
            $accountId = $idObj->accountId;
            $email = $idObj->email;
        }

        if ($resend && (_isNEZ($accountId) || $email == '')) {
            $errorMsg = 'Unexpected error (2)';
        }
        
        if (!$resend && (_isNEZ($accountId) || $email == '' || $mfaCode == '')) {
            $errorMsg = 'Unexpected error (3)';
        }

        if ($errorMsg == '') {
            $accountModel = new AccountModel();
            $accountDao = $accountModel->getById($accountId);
            if (is_null($accountDao) || strcasecmp($accountDao->Email, $email) != 0) {
                $errorMsg = 'Could not find account';
                $accountDao = null;
            }
        }

        // If we get here, and there's an error, don't bother verifying the entered code
        if ($errorMsg != '') {
            $enteredCode = '';
        }

        if ($resend) {
            redirect('mfa-verify?'.$accountModel->generateAndSendMFACode($viasms ? 'sms' : 'email'));
        }

        if ($enteredCode != '') {
            $result = $this->verifyCode($accountDao, $email, $mfaCode, $enteredCode);
            if ($result->verified) {
                if ($rememberBrowser && config('MFA', 'ENABLE TRUST BROWSER') == 'true') {
                    $thruDate = Dates::dateAdd('now', 30, 'day', Dates::DATE_FORMAT);
                    $remember = (new MFATrustModel())->rememberBrowser($accountId, $mfaCode, $thruDate);
                    libSetCookie("{$client}_trust", $remember, $thruDate);
                    LogEvent(Event::EVENT_MFA_TRUST_ENABLED, $accountDao->Username, "AccountID: ".$accountDao->AccountID);
                }

                // Verified codes are one-time use only.
                $accountModel->clearMFA();

                // Finally, finish up the login
                redirect('login');
            }
            else {
                $errorMsg = $result->errorMsg;
                $displayMsg = $result->displayMsg;
                $showResendLink = $result->showResendLink;

                if ($result->codeExpired) {
                    $accountModel->clearMFA();
                }
                
                
            }
        }
        
        if (!empty($accountDao)) {
            $startDateTime = Dates::toString($accountDao->MFAStartDate, Dates::DATETIME_FORMAT);

            if ($startDateTime == '' && $displayMsg == '') {
                // Quite likely the MFA Code is not valid
                $displayMsg = 'The security token for this authentication code is not valid.';
            }
        }

        $passInData = [
            'displayMsg' => $displayMsg,
            'errorMsg' => $errorMsg ?? '',
            'showResendLink' => $showResendLink,
            'viasms' => $viasms,
            'trustBrowserEnabled' => $trustBrowserEnabled,
            'startDateTime' => $startDateTime,
        ];

        View::addLink('css/mfa.css');
        View::render('mfa-verify', 'Verify MFA', $passInData);
    }

    /**
     * Determine available MFA methods based on configuration and
     * how the user is set up
     */
    private function getMfaMethods(AccountDAO $accountDao)
    {
        $methods = explode('|', config('MFA', 'METHODS'));

        return [
            'DUO' => in_array('DUO', $methods),
            'EMAIL' => (!_isNE($accountDao->Email) && in_array('EMAIL', $methods)),
            'SMS' => (config('TWILIO', 'ACCOUNTSID') != '' && !_isNE($accountDao->SMSNumber) && in_array('SMS', $methods)),
            'TOTP' => in_array('TOTP', $methods),
        ];
    }

    private function verifyCode($accountDao, string $email, string $mfaCode, string $enteredCode): object
    {
        $result = (object) [
            'verified' => false,
            'codeExpired' => false,
            'errorMsg' => '',
            'displayMsg' => '',
            'showResendLink' => false,
        ];

        $accountModel = new AccountModel();
        $accountModel->dao = &$accountDao;

		if ($accountDao->MFACode == '' || $accountDao->MFACode != ($mfaCode.$enteredCode)) {
			$result->errorMsg = 'Invalid authentication code';
		}
		elseif ($accountDao->MFACode === ($mfaCode.$enteredCode)) {
            $minutes = Dates::dateTimeDiff($accountDao->MFAStartDate, 'now', 'minute');
			if ($minutes > 15) {
				$result->errorMsg = 'Authentication code expired';
				$result->codeExpired = true;
				Logger::info("Authentication attempt for {$accountDao->Username}, but authentication code expired");
			}
		}

        /**
         * If no errors, code is valid
         */
		if ($result->errorMsg == '') {
			$result->verified = true;
            _session_put('mfavalid', 'true');
            LogEvent(Event::EVENT_MFA_SUCCESS, $accountDao->Username, "AccountID: ".$accountDao->AccountID);
            return $result;
        }

        /**
         * Handle errors
         */
        LogEvent(Event::EVENT_MFA_FAIL, $accountDao->Username, "AccountID: ".$accountDao->AccountID);

        $mfaAttempts  = _session('mfa-attempts', 0) + 1;
        _session_put('mfa-attempts', $mfaAttempts );

        /**
         * If there are too many attempts, most likely the user doesn't
         * have the right code. Either that or it's a malicious actor.
         * Either way, stop them here and offer to resend the code.
         */
        if ($mfaAttempts >= 3) {
            $result->errorMsg = 'Too many attempts.';
            $result->showResendLink = true;
            _session_put('loggedin', _session('mfauser'));
            Logger::info("MFA - {$result->errorMsg}");
        }

        $result->displayMsg =  $result->errorMsg;

        if ($result->errorMsg == 'Could not find account') {
            $result->displayMsg = 'Could not find an account record for your email address. If you need to register, please <a href="register1">click here</a>.';
        }
        elseif ($result->errorMsg == 'Account is not active') {
            $result->displayMsg = 'It looks like your account is not active. Perhaps you did not activate it. If you need a new activation link sent to you, <a href="request_account_activation.php">click here</a>.<br><br>';
        }
        elseif ($result->errorMsg == 'Invalid authentication code') {
            $result->displayMsg = 'The authentication code you provided is not valid. Please check the code and try again.';
            $result->showResendLink = true;
        }
        elseif ($result->errorMsg == 'Authentication code expired') {
            $result->displayMsg = 'The authentication code you entered has expired.';
            $result->showResendLink = true;
        }

        return $result;
    }
}
