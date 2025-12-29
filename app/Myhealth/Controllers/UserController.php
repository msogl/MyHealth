<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Crypto;
use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Email;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Models\MemberModel;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;

class UserController
{
    public function users()
    {
        $accountDaos = (new AccountModel())->loadAllActiveFirst();

        $passInData = [
            'accountDaos' => &$accountDaos,
        ];

        View::render('users', 'Users', $passInData);
    }

    public function userEdit()
    {
        $id = Request('id');
        if ($id == '') {
            redirect('users');
            return;
        }

        $errorMsg = '';

        $accountId = DecryptAESMSOGL($id);
        if (intval($accountId) == 0) {
            Logger::error(__METHOD__.": Invalid account id: {$accountId}");
            $errorMsg = 'Invalid account id';
        }

        $accountDao = (new AccountModel())->getById($accountId);

        if (is_null($accountDao)) {
            $errorMsg = 'Account not found';
        }

        if ($errorMsg != '') {
            View::errorPage($errorMsg);
            return;
        }

        $memberDao = (new MemberModel())->getById($accountDao->MemberID);

        $passInData = [
            'accountDao' => &$accountDao,
            'memberDao' => &$memberDao,
            'encryptedAccountId' => EncryptAESMSOGL($accountDao->AccountID),
        ];

        View::render('user-edit', 'Users', $passInData);
    }

    public function userSave()
    {
        $aid = DecryptAESMSOGL(Request('aid'));

        $accountModel = new AccountModel();
        $accountDao = $accountModel->getById($aid);

        if (is_null($accountDao)) {
            Logger::error(__METHOD__.": Account for found for account id {$aid}");
            AjaxResponse::error('Could not save account.');
        }

        $confirmed = $accountDao->Confirmed;
        $emailChanged = false;

        // If email address is different (case-insensitive), set to not confirmed
        if (strcasecmp($accountDao->Email, trim(Request("email"))) != 0) {
            $confirmed = 0;
            $emailChanged = true;
        }

        $accountDao->Firstname = strtoupper(trim(Request('firstname')));
        $accountDao->MemberID = strtoupper(trim(Request('memberid')));
        $accountDao->Email = trim(Request('email'));
        $accountDao->Nickname = trim(Request('nickname'));
        $accountDao->Confirmed = $confirmed;
        //$accountDao->MFAEnabled = (Request('mfa') == 1 ? 1 : 0);

        $changeNext = $accountDao->ChangeNext;

        // Disallow blank password and require it to be changed next time in
        if ($changeNext == 0 && $accountDao->Password == '') {
            $accountDao->Password = (new Crypto())->hash_password(bin2hex(random_bytes(8)));
            $accountDao->ChangeNext = 1;
        }
        else {
            $accountDao->ChangeNext = (Request('changenext') == '1' ? 1 : 0);
        }

        // Final validation before updating
        if ($accountDao->Email == '') {
            AjaxResponse::error('Email is required');
        }

        if ($accountDao->Firstname == '') {
            AjaxResponse::error('First name is required');
        }

        if ($accountDao->MemberID == '') {
            AjaxResponse::error('Member ID is required');
        }

        $accountModel->update($accountDao);
        LogEvent(Event::EVENT_ACCOUNT_UPDATED, $_SESSION["loggedin"], "AccountID: ".$accountDao->AccountID);

        // Send a confirmation email, if necessary
        if ($confirmed == 0 && $emailChanged && trim($accountDao->Email) != '') {
            if (!$accountModel->sendConfirmationEmail($accountDao, true)) {
                AjaxResponse::error("We're sorry. Could not send registration confirmation email.");
            }
            
            AjaxResponse::response('User saved successfully and new confirmation email sent');
        }

        AjaxResponse::response('User saved successfully');
    }

    public function revokeMfa()
    {
        $aid = DecryptAESMSOGL(Request('aid'));

        $errorPhrase = 'Unable to revoke MFA';
        
        $accountModel = new AccountModel();
        $accountDao = $accountModel->getById($aid);

        if (is_null($accountDao)) {
            Logger::error("Attempt to revoke MFA for account {$aid} failed. Account not found.");
            AjaxResponse::error($errorPhrase);
        }

        if (!$accountModel->revokeMFA()) {
            Logger::error("Could not revoke MFA for account {$aid}. Unknown error.");
            AjaxResponse::error($errorPhrase);
        }

        Logger::info("MFA revoked for account {$aid}");
        LogEvent(Event::EVENT_MFA_REVOKED, _session('loggedin'), "AccountID: {$accountDao->AccountID}  Username: {$accountDao->Username}");
        AjaxResponse::success();
    }
    
    public function userReview()
    {
        $idleDays = (Request('idledays') != '' ? Request('idledays') : 60);
        $activeOnly = (Request('inactive') == '0');

        $reviews = (new AccountModel())->getUserReview($idleDays, $activeOnly);
        foreach($reviews as &$review) {
            $review->encryptedAccountId = EncryptAESMSOGL($review->AccountID);
        }

        $passInData = [
            'idleDays' => $idleDays,
            'activeOnly' => $activeOnly,
            'reviews' => &$reviews,
        ];

        View::render('user-review', 'User Review', $passInData);
    }
}