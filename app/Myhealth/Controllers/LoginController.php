<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\Common;
use Myhealth\Classes\Event;
use Myhealth\Classes\LoginState;
use Myhealth\Classes\View;
use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Models\AccountModel;
use Myhealth\Models\AgreementModel;
use Myhealth\Models\AuthenticateModel;
use Myhealth\Models\LockoutModel;

class LoginController
{
    public function login()
    {
        $errorMsg = '';
        $username = Request('u');
        $password = Request('p');
        $mfaValidated = false;

        if (_session('mfavalid') == 'true' && _session('mfauser') != '') {
            $username = _session('mfauser');
            $password = '';
            $mfaValidated = true;
        }

        if (!$mfaValidated && _session('loggedInMemberId') == '') {
            @session_unset();
        }

        $client = client();

        if ($username != "") {
            $authResult = _session('login_state');

            $lockoutModel = new LockoutModel();
            $auth = new AuthenticateModel();
            $authResult = $auth->authenticateUser($username, $password, $mfaValidated);
            _session_put('login_state', $authResult);
            Logger::info('login_state = '._session('login_state'));

            if (!$mfaValidated && $authResult != LoginState::LOGIN_FAILED) {
                if (Request("remember") == "1") {
                    libSetCookie("{$client}_username", EncryptAESMSOGL($username), Dates::dateAdd('now', 14, 'day', Dates::DATETIME_FORMAT));
                    $rememberedUser = $username;
                }
                else {
                    clearCookie("{$client}_username");
                    $rememberedUser = "";
                }
            }

            if ($authResult == LoginState::LOGIN_FAILED) {
                $errorMsg = "Invalid username or password. Please try again.";
                LogEvent(Event::EVENT_LOGIN_FAIL, $username, "Invalid username or password.");

                if ($lockoutModel->incrementCount($username) >= $lockoutModel->maxLoginAttempts) {
                    $lockoutModel->lock($username);
                    _session_put('login_state', LoginState::LOCKED);
                    $authResult = LoginState::LOCKED;
                }
            }
            
            if ($authResult == LoginState::NOT_CONFIRMED) {
                $errorMsg = "Your email has not been confirmed, yet.";
                $errorMsg .= "<br/>Didn't get the confirmation email? <a href=\"resend-confirmation\">Resend</a>";
                LogEvent(Event::EVENT_LOGIN_FAIL, $username, "Email not yet confirmed");
            }
            elseif ($authResult == LoginState::LOCKED) {
                $errorMsg = "Too many attempts. Account locked. Please try again later.";
                LogEvent(Event::EVENT_LOGIN_FAIL, $username, "Too many attempts.");
            }
            elseif ($authResult == LoginState::SUSPENDED) {		// 01/16/2023 JLC Added auto-suspend
                $errorMsg = "We're sorry. Your access has been suspended since your health plan membership has expired.";
                LogEvent(Event::EVENT_LOGIN_FAIL, $username, "Member termed; access suspended");
            }
            elseif ($authResult == LoginState::PASSWORD_EXPIRED) {		// Password expired
                _session_put('loggedin', $username);
                _session_put('loggedInMemberId', '');
                _session_put('changereason', 'password expired');
                LogEvent(Event::EVENT_LOGIN_SUCCESS, $username, "");
                LogEvent(Event::EVENT_PASSWORD_EXPIRED, $username, "");
                redirect('change-password');
                exit;
            }
            elseif ($authResult == LoginState::PASSWORD_CHANGE_REQUIRED) {		// Password change required
                _session_put('loggedin', $username);
                _session_put('loggedInMemberId', '');
                _session_put('changereason', 'change required');
                LogEvent(Event::EVENT_LOGIN_SUCCESS, $username, "");
                LogEvent(Event::EVENT_PASSWORD_CHANGE_REQUIRED, $username, "");
                redirect('change-password?meta='.urlencode(EncryptAESMSOGL(json_encode(['return-page'=>'my-claims']))));
                exit;
            }
            elseif ($authResult == LoginState::MFA_REQUIRED) {
                _session_put('loggedin', $username);
                _session_put('loggedInMemberId', '');
                Logger::info("Phase 1 login successful for {$username}");
                Logger::info("Starting phase 2 (MFA)");
                // If we get here, MFA is enabled and computer is not remembered,
                // so we have to send the MFA code. The question is, to where? Email or SMS?
                
                _session_put('mfauser', $username);
                redirect('mfa');
                exit;
            }
            elseif ($authResult == LoginState::AGREEMENT_REQUIRED) {
                _session_put('loggedin', $username);
                LogEvent(Event::EVENT_LOGIN_SUCCESS, $username, "");
                LogEvent(Event::EVENT_AGREEMENT_REQUIRED, $username, "");
                $this->agreement();
                return;
            }
            elseif ($authResult == LoginState::LOGGED_IN) {		// Normal login
                $lockoutModel->clearLock($username);
                destroy_session();		// creates new session id
                _session_put('login_state', LoginState::LOGGED_IN);
                _session_put('loggedin', $username);
                $accountDao = (new AccountModel())->getByUsername(_session('loggedin'));
                _session_put('loggedInMemberId', $accountDao->MemberID ?? '');
                _session_put('loggedInNickname', (!empty($accountDao->Nickname) ? $accountDao->Nickname : $accountDao->Firstname));
                LogEvent(Event::EVENT_LOGIN_SUCCESS, $username, "");

                $this->agreement();
                return;
            }
        }

        // Remembered may or may not be b64-encoded. If it is, decrypt it, too.
        $rememberedUser = getCookie("{$client}_username");
        $decoded = base64_decode($rememberedUser);
        if (base64_encode($decoded) === $rememberedUser) {
            $rememberedUser = DecryptAESMSOGL($decoded);
        }

        // If user is already logged in, don't go through this process
        if (_session('loggedInMemberId') != "") {
            redirect('home');
            exit;
        }
        
        $passInData = [
            'errorMsg' => $errorMsg,
            'clientName' => (new Common())->getConfig('CLIENT NAME', ''),
            'rememberedUser' => $rememberedUser,
        ];

        View::render('login', 'Login', $passInData);
    }

    public function logout()
    {
        end_session();
        redirect('login');
    }

    public function agreement()
    {
        $username = _session('loggedin');

        $agreementModel = new AgreementModel();

        if ($agreementModel->agreed($username)) {
            redirect('home');
            return;
        }

        if (Request('a') != "") {
            $answer = strtoupper(Request('a'));

            // Only valid answers get us past this screen
            if ($answer === 'Y' || $answer === 'N') {
                $agreementModel->recordAnswer($username, $answer);
                
                if ($answer === 'Y') {
                    _session_put('login_state', LoginState::LOGGED_IN);
                    redirect('home');
                }
                else {
                    end_session();
                    redirect('login');
                }

                return;
            }
        }

        _session_put('login_state', LoginState::AGREEMENT_REQUIRED);
        $client = client(); 

        $template = strtolower($client).'-agreement.html';
        if (!templateExists($template)) {
            $template = 'agreement.html';
        }

        $contents = ReadTemplate($template);

        View::render('agreement', 'Agreement', ['client' => $client, 'contents' => &$contents]);
    }
}