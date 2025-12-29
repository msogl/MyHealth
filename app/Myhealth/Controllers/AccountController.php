<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Classes\AppMeta;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Models\AccountMembersModel;

class AccountController
{
    public function myAccount()
    {
        $common = new Common();
        $accountDao = (new AccountModel())->getByUsername(_session('loggedin'));

        $isMFAFeatureEnabled = $common->isFeatureEnabled('MFA');

        if ($common->isFeatureEnabled('LINKED MEMBERS')) {
            $accountMembers = (new AccountMembersModel())->getMembers($accountDao->AccountID);
        }

        AppMeta::add('return-page', 'my-account');

        $passInData = [
            'accountDao' => &$accountDao,
            'isMFAFeatureEnabled' => $isMFAFeatureEnabled,
            'accountMembers' => $accountMembers ?? [],
        ];

        View::render('my-account', 'My Account', $passInData);
    }

    public function saveMyAccount()
    {
        $username = _session('loggedin');
        $accountModel = new AccountModel();
        $accountDao = $accountModel->getByUsername($username);
        $origMFAEnabled = $accountDao->MFAEnabled;

        $accountDao->Email = Request('email');
        $accountDao->Nickname = Request('nickname');
        $accountDao->MFAEnabled = (Request('mfa') === '1' ? 1 : 0);
        $accountModel->update($accountDao);

        _session_put('loggedInNickname', $accountDao->Nickname);

        if ($origMFAEnabled == 0 && $accountDao->MFAEnabled == 1) {
            _session_put('mfauser',  $username);
            _session_put('mfa-nav-after-setup', 'my-account');
            LogEvent(Event::EVENT_MFA_ENABLED, $username, 'AccountID: '.$accountDao->AccountID);
            AjaxResponse::response(['next' => 'totp']);
        }
        else {
            if ($origMFAEnabled == 1 && $accountDao->MFAEnabled == 0) {
                LogEvent(Event::EVENT_MFA_DISABLED, $username, 'AccountID: '.$accountDao->AccountID);
            }

            AjaxResponse::response(['next' => 'home']);
        }
    }
}