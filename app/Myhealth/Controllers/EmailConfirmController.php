<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Models\AccountModel;

class EmailConfirmController
{
    public function confirm()
    {
        $this->resetSession();

        $code = Request('c');

        // Determine if it's from an email address change, such as in user admin or my account
        $fromEmailChange = (Request('ch') === '1');

        if ($code == '') {
            $errorMsg = "We're sorry, but we did not receive a confirmation code. Unable to confirm your email address.";
        }
        else {
            $accountId = 0;
            $email = '';
            $errorMsg = '';
            $cparam = DecryptAESMSOGL($code);
            $parts = explode(',', $cparam);

            $accountId = (int) $parts[0] ?? 0;
            $email = $parts[1] ?? '';

            if ($accountId == 0 || trim($email) == '') {
                $errorMsg = 'The confirmation code you are using appears to be invalid.';
            }
            else {
                $accountModel = new AccountModel();
                $accountDao = $accountModel->getById($accountId);
                if (is_null($accountDao)) {
                    $errorMsg = 'We were unable to find your email address. You may need to <a href="register1">register again</a>.';
                }
                elseif ($email !== $accountDao->Email) {
                    $errorMsg = 'The confirmation code is not valid.';
                }
                else if ($accountDao->Confirmed == 1) {
                    $errorMsg = 'Your email has already been confirmed. Click OK to return to the login page.';
                }
                else {
                    $accountDao->Confirmed = 1;
                    $accountModel->update($accountDao);

                    // Activation is handled separately
                    $accountModel->activate();
                    LogEvent(Event::EVENT_EMAIL_CONFIRMED, $accountDao->Username, 'Email: '.$accountDao->Email.($fromEmailChange ? ' (this was a reconfirmation from an email change)' : ''));
                }
            }
        }

        View::render('email-confirm', 'Email Confirmation', [ 'errorMsg' => $errorMsg ]);
    }

    public function resendEmail()
    {
        $accountId = _session('accountid');

        // Assume there's an being an error until proven otherwise
        $errorMsg = "We're sorry, we cannot find your account.";

        if ($accountId != '') {
            $accountModel = new AccountModel();
            $accountDao = $accountModel->getById($_SESSION['accountid']);

            if (!is_null($accountDao)) {
                $isRateLimited = false;

                if ($accountDao->ConfirmStartDate != null) {
                    // Date is stored in UTC, so deal with it as such
                    $rateLimitedMinutes = 15;
                    $origTimezone = date_default_timezone_get();
                    date_default_timezone_set('UTC');
                    $cantSendBeforeUTC = (new \DateTime($accountDao->ConfirmStartDate))->add(new \DateInterval("PT{$rateLimitedMinutes}M"));
                    $dtNowUTC = new \DateTime('now');
                    date_default_timezone_set($origTimezone);

                    if ($dtNowUTC <= $cantSendBeforeUTC) {
                        $errorMsg = "A confirmation email has already been sent within the last {$rateLimitedMinutes} minutes. Please wait before trying again.";
                        $isRateLimited = true;
                    }
                }

                if (!$isRateLimited) {
                    $accountModel->sendConfirmationEmail($accountDao);
                    $errorMsg = '';
                }
            }
        }

        $passInData = [
            'errorMsg' => $errorMsg,
            'accountDao' => &$accountDao,
        ];

        View::render('resend-confirmation', 'Resend Email Confirmation', $passInData);
    }

    private function resetSession()
    {
        _session_remove('logged_in');
        _session_remove('loggedin');
        _session_remove('loggedInMemberId');
        _session_remove('loggedInNickname');
        _session_remove('memid');
        _session_remove('dob');
        _session_remove('email');
        _session_remove('username');
        _session_remove('nickname');
        _session_remove('realmemid');
        _session_remove('loggedInAge');
        _session_remove('password');
        session_write_close();
    }
}