<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\AppMeta;
use Myhealth\Daos\AccountDAO;
use Myhealth\Models\MemberModel;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Models\PasswordPolicyModel;

class RegistrationController
{
    private $lastError = null;

    public function register1()
    {
        $first = Request('first');
        $memId = Request('memid');
        $dob = Request('dob');

        if ($first !== '' || $memId !== '' || $dob !== '') {
            _session_put('first', $first);
            _session_put('memid', $memId);
            _session_put('dob', $dob);

            if ($this->step1Validate($first, $memId, $dob)) {
                redirect('register2');
            }
        }

        $passInData = [
            'first' => _session('first'),
            'memId' => _session('memid'),
            'dob' => _session('dob'),
            'client' => client(),
            'errorMsg' => $this->lastError,
        ];

        View::render('register1', 'Register - Step 1 of 3', $passInData);
    }

    public function register2()
    {
        $username = Request('username');
        $email = Request('email');
        $nickname = Request('nickname');
        $password = Request('password');

        if ($username !== '' || $email !== '' || $nickname !== '' || $password !== '') {
            _session_put('username', $username);
            _session_put('email', $email);
            _session_put('nickname', $nickname);

            $errors = $this->step2Validate($username, $email, $password);
            if (count($errors) == 0) {
                _session_put('password', EncryptAESMSOGL($password));
                redirect('register3');
            }
        }

        $passInData = [
            'username' => _session('username'),
            'email' => _session('email'),
            'nickname' => _session('nickname'),
            'errors' => (empty($errors) ? '' : json_encode($errors)),
        ];

        View::render('register2', 'Register - Step 2 of 3', $passInData);
    }

    public function register3()
    {
        if (_session('first') == '' ||
            _session('memid') == '' ||
            _session('realmemid') == '' ||
            _session('dob') == '' ||
            _session('username') == '' ||
            _session('email') == '') {
            redirect('login');
        }

        /**
         * This will be evaluated during the save process to make sure the
         * save is indeed coming from here, not from some malicious attempt
         * elsewhere.
         */
        $safeRegister = bin2hex(random_bytes(16));
        _session_put('safe-register', $safeRegister);
        AppMeta::add('safe-register', $safeRegister);

        $passInData = [
            'first' => _session('first'),
            'memId' => _session('memid'),
            'dob' => _session('dob'),
            'username' => _session('username'),
            'email' => _session('email'),
            'nickname' => _session('nickname'),
            'passwordLength' => strlen(DecryptAESMSOGL(_session('password'))),
        ];

        View::render('register3', 'Register - Step 3 of 3', $passInData);
    }

    public function save()
    {
        $meta = AppMeta::decrypt(Request('meta'));

        $genericError = 'Cannot complete registration. An unexpected error occurred.';
        
        if (!isset($meta['safe-register'])) {
            Logger::error(__FUNCTION__.': safe-register is missing');
            AjaxResponse::error($genericError.' (Error 1)');
        }
        
        if (_session('first') == '' ||
            _session('memid') == '' ||
            _session('realmemid') == '' ||
            _session('dob') == '' ||
            _session('username') == '' ||
            _session('email') == '' ||
            _session('safe-register') == '') {
            Logger::error(__FUNCTION__.': one or more session values are blank');
            AjaxResponse::error($genericError.' (Error 2)');
        }
        
        if (_session('safe-register') !== $meta['safe-register']) {
            Logger::error(__FUNCTION__.': safe-register mismatch. Session: '._session('safe-register').'  Meta: '.$meta['safe-register']);
            AjaxResponse::error($genericError.' (Error 3)');
        }

        $accountDao = new AccountDAO();
        $accountDao->Firstname = _session('first');
        $accountDao->MemberID = _session('realmemid');
        $accountDao->Username = _session('username');
        $accountDao->Email = _session('email');
        $accountDao->Nickname = _session('nickname');
        $accountDao->Password = _session('password');

        $accountModel = new AccountModel();
        $accountId = $accountModel->create($accountDao);
        LogEvent(Event::EVENT_ACCOUNT_CREATED, _session('username'), 'Email: '._session('email'));

        if ($accountId == -1) {
            // Theoretically, it's not possible to get to this point as checks for this
            // are done in previous steps.
            AjaxResponse::error("We're sorry, that account already exists.");
        }
        elseif ($accountId == 0) {
            $details = [
                "Firstname" => $accountDao->Firstname,
                "MemberID" => $accountDao->MemberID,
                "Username" => $accountDao->Username,
                "Email" => $accountDao->Email,
            ];

            Logger::error(__FUNCTION__.': Account creation failed. Details: '.json_encode($details));
            AjaxResponse::error('Account creation failed. Please try again. If it continues to fail, please contact us.');
        }

        if (!$accountModel->sendConfirmationEmail($accountDao)) {
            AjaxResponse::error("We're sorry. Could not send registration confirmation email.");
        }

        _session_remove('safe-register');

        /**
         * Set up a value that's only good for one page. We don't want the
         * user to be able to get to the completion page willy-nilly.
         */
        $safeRegisterComplete = bin2hex(random_bytes(16));
        _session_put('safe-register-complete', $safeRegisterComplete);
        AjaxResponse::response(['next' => 'register-complete', 'safe' => EncryptAESMSOGL($safeRegisterComplete)]);
    }

    public function registerComplete()
    {
        $safe = Request('safe');

        if ($safe == '' || DecryptAESMSOGL($safe) != _session('safe-register-complete')) {
            http_response_code(403);
            render('403', '403 Forbidden');
            return;
        }

        View::render('register-complete', 'Registration Complete');
    }

    public function checkUsername()
    {
        $username = Request('username');
        if ($username == '') {
            AjaxResponse::error('username is blank');
        }

        $accountDao = (new AccountModel())->getByUsername($username);
        AjaxResponse::response(['isAvailable' => is_null($accountDao)]);
    }

    private function step1Validate(string $first, string $memId, string $dob): bool
    {
        $this->lastError = null;

        /**
         * Input validation
         */
        if ($first == '') {
            $this->lastError = 'First name is required';
            return false;
        }

        if ($memId == '') {
            $this->lastError = 'Member ID is required';
            return false;
        }

        if ($dob == '') {
            $this->lastError = 'Date of birth is required';
            return false;
        }

        if (!Dates::isDate($dob)) {
            $this->lastError = 'Date of birth is not a valid date';
            return false;
        }

        if ($this->accountExists($first, $memId)) {
            $this->lastError = 'It looks like there is already an account registered for you. If you need to reset your password, <a href="forgot-password">click here</a>.';
            return false;
        }

        $memberDao = (new MemberModel())->findBySubscriberId($first, $memId, $dob);
        if (is_null($memberDao)) {
            $this->lastError = "We're sorry, but we cannot find your record in our database. Make sure the first name is entered as it appears on you insurance card, and that the member ID and date of birth are correct.";
            return false;
        }

        _session_put('first', strtoupper($first));
		_session_put('realmemid', $memberDao->MBR_SSN_number);

        return true;
    }

    private function step2Validate(string $username, string $email, string $password): array
    {
        $errors = [];

        if ($username == '') {
            $errors[] = 'Username is required';
        }
        elseif (!is_null((new AccountModel())->getByUsername($username))) {
            $errors[] = 'Username is unavailable';
        }

        if ($email == '') {
            $errors[] = 'Email is required';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid';
        }

        if ($password == '') {
            $errors[] = 'Password is required';
        }
        elseif (!$this->checkPasswordStrength($password)) {
            $errors[] = 'Password is not strong enough';
        }

        return $errors;
    }

    private function accountExists(string $first, string $memId): bool
    {
        $accountDao = (new AccountModel())->getByFirstAndMemberID($first, $memId);
        return ($accountDao !== null);
    }

    private function checkPasswordStrength(string $password)
    {
        $policy = new PasswordPolicyModel();

		if (!$policy->loadPolicyFromQuickCap()) {
			$policy->setDefaultPolicy();
		}

        if ($policy->testPasswordStrength(Request('password'))) {
            return true;
        }

        $errorMsg = "We're sorry. Your new password does not meet our password requirements.[br]";

        if ($policy->MinLength > 0) {
            $errorMsg .= "Must be at least {$policy->MinLength} characters long[br]";
        }

        if ($policy->MinUpper > 0) {
            $errorMsg .= "Must contain at least {$policy->MinUpper} uppercase characters[br]";
        }

        if ($policy->MinNumeric > 0) {
            $errorMsg .= "Must contain at least {$policy->MinNumeric} numeric characters[br]";
        }

        if ($policy->MinSpecial > 0) {
            $errorMsg .= "Must contain at least {$policy->MinSpecial} special characters ({$policy->ValidSpecialCharacters})[br]";
        }

        $this->lastError = $errorMsg;
        return false;
    }
}