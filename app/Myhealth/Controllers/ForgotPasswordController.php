<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\AppMeta;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\VerifyReset;

class ForgotPasswordController
{
    public function forgotPassword()
    {
        $errorMsg = '';
        $username = Request('username');
        $requestSubmitted = false;
        $proceed = false;

        // fp-protect is designed to prevent page reloads from resubmitting the
        // password reset request
        $protection = Request('req');
        if ($protection !== '' && _session('fp-protect') === $protection) {
            $proceed = true;
        }

        if ($errorMsg == '' && $username != '' && $proceed) {
            $accountModel = new AccountModel();
            $accountDao = $accountModel->getByUsername($username);

            // 10/10/2024 JLC Bug fix: account must not only exist, but also be active
            $isAccountActive = (!is_null($accountDao) && $accountDao->Active == 1);
            $requestSubmitted = true;

            if ($isAccountActive) {
                if (!$accountModel->RequestPasswordReset()) {
                    // do something with an error
                }
            }
            else {
                Logger::info("forgot-password: User not found or not active: {$username}");
                LogEvent(Event::EVENT_DEBUG, 'system', "Unsuccessful password reset attempted for user {$username}; user not found or not active");

                // Add artificial delay to make it seem like something is going on behind the scenes. If this were real,
                // an email would be sent, which would naturally add a small delay.
                sleep(rand(1,3));
            }
            
            _session_remove('fp-protect');
        }
        else {
            _session_put('fp-protect', bin2hex(random_bytes(16)));
        }

        $passInData = [
            'errorMsg' => $errorMsg,
        ];

        $view = ($requestSubmitted ? 'forgot-password-after' : 'forgot-password');
        View::render($view, 'Forgot Password', $passInData);
    }

    public function verifyReset()
    {
        $errorMsg = '';
        $displayMsg = '';
        $view = '';
        $showResendLink = false;
        $invalid = true;

        $encryptedResetInfo = Request('x');

        if ($encryptedResetInfo !== '') {
            $invalid = false;

            $resetInfo = VerifyReset::decrypt($encryptedResetInfo);
            if ($resetInfo === false) {
                $invalid = true;
            }
        }

        if ($invalid) {
            $displayMsg = 'The password reset code is not valid';
        }
        else {
            $username = $resetInfo->u;
            $resetCode = $resetInfo->c;

            $accountModel = new AccountModel();

            if ($accountModel->verifyResetCode($username, $resetCode)) {
                $view = 'change-password';
            }
            else {
                $errorMsg = $accountModel->LastError;
            }

            if ($errorMsg != '') {
                /**
                 * To avoid tipping our hand to malicious actors, there are some conditions we will
                 * never show to the end user, such as account not found or account not active. We
                 * will only show limited information here. Most other errors will be logged, but
                 * not shown to the end user.
                 */
                if ($errorMsg == 'Password reset not requested') {
                    $displayMsg = 'It looks like your account does not have a pending password reset request. You may have already reset your password. If you have forgotten your password, <a href="forgot-password">click here</a>.<br/><br/>';
                    $displayMsg .= 'If you do know your password, <a href="index">click here</a> to log in.';
                }
                elseif ($errorMsg == 'Invalid reset code') {
                    $displayMsg = 'The password reset code you provided is not valid.';
                }
                elseif ($errorMsg == 'Reset code expired') {
                    $displayMsg = 'The reset code you entered has expired.';
                    $showResendLink = true;
                }
                else {
                    $displayMsg = _W($errorMsg);
                }
            }
        }

        $passInData = [
            'errorMsg' => $errorMsg,
            'displayMsg' => $displayMsg,
            'showResentLink' => $showResendLink,
        ];

        if ($displayMsg != '' && $view == '') {
            $view = 'verify-reset-error';
        }

        if ($view == 'change-password') {
            // The change password process happens in one place in the code.
            // We don't come back here once we render this view. It goes into the
            // normal change password flow.
            AppMeta::add('return-page', 'verify-reset');
            AppMeta::add('verify-reset-info', $encryptedResetInfo);
            View::render($view, 'Change Password', $passInData);
            return;
        }

        View::render($view, 'Verify Reset Code', $passInData);
    }
}