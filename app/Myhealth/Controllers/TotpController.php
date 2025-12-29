<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\AjaxResponse;
use Myhealth\Classes\Common;
use Myhealth\Classes\Event;
use Myhealth\Classes\TOTPMFA;
use Myhealth\Classes\View;
use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Daos\AccountDAO;
use Myhealth\Models\AccountModel;
use Myhealth\Models\MFATrustModel;

class TotpController
{
    public function totp()
    {
        $common = new Common();

        if (_session('mfauser') == '' || !InList('TOTP', $common->getConfig('MFA', 'METHODS'))) {
            _trace(_session_all());
            die;
            http_response_code(404);
            View::render('404', '404 Page not found');
            return;
        }

        $totp = new TOTPMFA();

        $accountModel = new AccountModel();
        $accountDao = $accountModel->getByUsername(_session('mfauser'));

        if ($accountDao === null) {
            redirect('home');
        }

        $totpLabel = $common->getConfig('TOTP', 'LABEL');

        if (_isNE($totpLabel)) {
            $totpLabel = 'MyHealth';
        }

        $totpLabel .= ' (' . _session('mfauser') . ')';

        $qrCode = (_isNE($accountDao->OTPSecret) ? $totp->createQRCode($totpLabel) : '');

        $passInData = [
            'qrCode' => $qrCode,
            'totpLabel' => $totpLabel,
            'trustBrowserEnabled' => ($common->getConfig('MFA', 'ENABLE TRUST BROWSER') == 'true'),
        ];

        $setupRequired = ($qrCode !== '');

        View::addLink('css/mfa.css');
        View::render(($setupRequired ? 'totp-setup' : 'totp-verify'), 'MFA', $passInData);
    }

    public function verify()
    {
        $code = Request('code');
        $rememberBrowser = (Request('r') === '1');
        $client = client();
        $common = new Common();
        $totp = new TOTPMFA();

        $accountModel = new AccountModel();
        $accountDao = $accountModel->getByUsername(_session('mfauser'));

        if (is_null($accountDao)) {
            redirect('home');
        }

        if (!$totp->verify($code, ($accountDao->OTPSecret != null ? DecryptAESMSOGL($accountDao->OTPSecret) : ''))) {
            Logger::error('MFA failed');
            LogEvent(Event::EVENT_MFA_FAIL, $accountDao->Username, "AccountID: " . $accountDao->AccountID);
            AjaxResponse::response('fail');
        }

        _session_put('mfavalid', 'true');
        LogEvent(Event::EVENT_MFA_SUCCESS, $accountDao->Username, "AccountID: " . $accountDao->AccountID);

        $otpSecret = $totp->getSecret();
        if (_isNE($accountDao->OTPSecret) && !_isNE($otpSecret)) {
            $accountDao->OTPSecret = $otpSecret;
            $accountModel->updateOTPSecret(EncryptAESMSOGL($otpSecret));
        }

        if ($common->getConfig('MFA', 'ENABLE TRUST BROWSER') == 'true' && $rememberBrowser) {
            if (!_isNE($accountDao->AccountID)) {
                // With TOTP, there is no MFA Code, so we have to generate one for future comparisons
                $mfaCode = $accountModel->generateMFACode();
                $thruDate = Dates::dateAdd('now', 30, 'day', Dates::DATETIME_FORMAT);
                $remember = (new MFATrustModel())->rememberBrowser($accountDao->AccountID, $mfaCode, $thruDate);
                libSetCookie("{$client}_trust", $remember, $thruDate);
                LogEvent(Event::EVENT_MFA_TRUST_ENABLED, $accountDao->Username, "AccountID: " . $accountDao->AccountID);
            }
        }

        AjaxResponse::response(['next' => $totp->getNextPage()]);
    }

    public function setupSuccess()
    {
        $navToPage = _session('mfa-nav-after-setup', 'home');
        _session_remove('mfa-nav-after-setup');

        View::addLink('css/mfa.css');
        View::render('totp-setup-success', 'MFA', ['navToPage' => $navToPage]);
    }
}
