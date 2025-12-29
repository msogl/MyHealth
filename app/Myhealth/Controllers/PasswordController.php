<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\AppMeta;
use Myhealth\Classes\LoginState;
use Myhealth\Classes\VerifyReset;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;

class PasswordController
{
    public function changePassword()
    {
        $validReturnPages = [
            'my-account',
        ];

        $meta = AppMeta::decrypt(Request('meta'));

        if (!empty($meta['return-page']) && in_array($meta['return-page'], $validReturnPages)) {
            _session_put('return-page', $meta['return-page']);
        }
        else {
            _session_remove('return-page');
        }

        $reasonMsg = match(_session('changereason')) {
            'password expired' => 'Your password has expired. Please set a new one now.',
            'change required' => 'Your password must be changed. Please set a new one now.',
            default => ''
        };

        $passInData = [
            'errorMsg' => '',
            'reasonMsg' => $reasonMsg,
            'passwordChanged' => false,
        ];

        View::render('change-password', 'Change Password', $passInData);
    }

    public function changePasswordCancel()
    {
        $loginState = _session('login_state');
        $nextPage = ($loginState == LoginState::LOGGED_IN ? _session('return-page', 'home') : 'home');
        AjaxResponse::response([ 'next' => $nextPage ]);
    }

    public function changePasswordAction()
    {
        $username = _session('loggedin');
        $password = Request('password');
        $accountModel = new AccountModel();

        if (trim($username) == '') {
            // Check if we're in the middle of a forgot/reset password situation
            $meta = AppMeta::decrypt(Request('meta'));
            
            if (isset($meta['verify-reset-info'])) {
                $resetInfo = VerifyReset::decrypt($meta['verify-reset-info']);

                if ($resetInfo === false) {
                    AjaxResponse::error('The reset code is not valid');
                }

                if (!$accountModel->verifyResetCode($resetInfo->u, $resetInfo->c)) {
                    AjaxResponse::error($accountModel->LastError);
                }

                $username = $resetInfo->u;
            }
        }

        if (trim($username) == '' || trim($password) == '') {
            AjaxResponse::error('Password change failed');
        }

        $passwordChanged = $accountModel->changePassword($username, $password);

        if (!$passwordChanged) {
            AjaxResponse::error($accountModel->LastError ?? 'Unexpected error');
        }

        $loginState = _session('login_state');
        $nextPage = ($loginState == LoginState::LOGGED_IN ? _session('return-page', 'home') : 'home');
        $accountModel->ClearResetCode();

        AjaxResponse::response([
            'next' => $nextPage,
            'message' => 'Your password has been changed'
        ]);
    }
}