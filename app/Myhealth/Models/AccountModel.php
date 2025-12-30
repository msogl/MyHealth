<?php

namespace Myhealth\Models;

use Exception;
use Myhealth\Core\Dates;
use Myhealth\Classes\SMS;
use Myhealth\Core\Crypto;
use Myhealth\Core\Logger;
use Myhealth\Classes\Email;
use Myhealth\Classes\Event;
use Myhealth\Daos\AccountDAO;

class AccountModel extends BaseModel
{
	const TOKEN_SIZE = 6;
    const ACTIVE_ONLY = 1;
    const ACTIVE_FIRST = 2;

	private $daoName = 'AccountDAO';
	public $LastError = null;
	public $dao = null;

	public function getById($id)
	{
		$sql = "SELECT * FROM Accounts WHERE AccountID = ?";
		$rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, [$id]);
		$this->dao = (!$rs->EOF ? $this->db->wrappers($rs, $this->daoName, true) : null);

        if (is_null($this->dao)) {
            return null;
        }
        
        $this->addSMSNumber();
		return $this->dao;
	}

	public function getByUsername($Username)
	{

		$sql = "SELECT * FROM Accounts WHERE Username = ?";
		$rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, [$Username]);
		$this->dao = (!$rs->EOF ? $this->db->wrappers($rs, $this->daoName, true) : null);

        if (is_null($this->dao)) {
            return null;
        }

        $this->addSMSNumber();
		return $this->dao;
	}

	public function getByFirstAndMemberID($first, $id)
	{
		$sql = "SELECT * FROM Accounts\n";
		$sql .= "WHERE Firstname = ? AND MemberID LIKE ?";
		$rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, [$first, "{$id}%"]);
		$this->dao = (!$rs->EOF ? $this->db->wrappers($rs, $this->daoName, true) : null);
		return $this->dao;
	}

	public function getAllUsernames(): array
	{
		$sql = "SELECT Username FROM Accounts ORDER BY Username";
        $rs = $this->db->GetRecords($this->db->QCMembersDB, $sql);

        $usernames = [];
        while(!$rs->EOF) {
            $usernames[] = $rs->fields['Username'];
            $rs->MoveNext();
        }

        return $usernames;
	}
    
    public function loadAllActiveFirst()
    {
        $sql = "SELECT * FROM Accounts\n";
        $sql .= "ORDER BY Active DESC, AccountId\n";
        $rs = $this->db->GetRecords($this->db->QCMembersDB, $sql);
        return $this->db->wrappers($rs, $this->daoName);
    }

	public function create(&$dao)
	{
		if ($dao === null) {
			return 0;
		}

		$policy = new PasswordPolicyModel();

		if (!$policy->loadPolicyFromQuickCap()) {
			$policy->setDefaultPolicy();
		}

		// Password strength was tested in register2, so no need to do it again here

		$hash = (new Crypto())->hash_password($dao->Password.PEPPER);
		$dao->Password = $hash->hash;
		$dao->Salt = $hash->salt;
		$dao->Version = $hash->version;
		$dao->ChangeNext = 0;

		if ($policy->EnableExpiration) {
            $dao->NextPasswordChangeDate = Dates::dateAdd('now', $policy->ValidPeriod, 'day', Dates::DATE_FORMAT);
		}
		else {
			$dao->NextPasswordChangeDate = '12/31/2050';		// Way out in the future
		}

		$params = [
			$dao->Firstname,
			$dao->MemberID,
			libTruncate($dao->Username, 30),
			$dao->Email,
			libTruncate($dao->Nickname, 30),
			$dao->Password,
			Dates::sqlDate($dao->NextPasswordChangeDate),
			$dao->Salt,
			$dao->Version,
		];

		$sql = "exec AccountCreate ?";		// Firstname
		$sql .= ", ?";		// MemberID
		$sql .= ", ?";		// Username
		$sql .= ", ?";		// Email
		$sql .= ", ?";		// Nickname
		$sql .= ", ?";		// Password
		$sql .= ", ?";		// NextPasswordChangeDate
		$sql .= ", ?";		// Salt
		$sql .= ", ?";		// Version

		$rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, $params);

		$dao->AccountID = 0;
		if (!$rs->EOF) {
			$dao->AccountID = intval($this->db->rst($rs, "AccountID"));
		}

		return $dao->AccountID;
	}

	public function update(&$dao=null)
	{
		if ($dao === null) {
			$dao = $this->dao;

			if ($dao === null) {
				return false;
			}
		}

		$sql = "exec AccountUpdate ?, ?, ?, ?, ?, ?, ?, ?";

		$params = [
			$dao->AccountID,
			$dao->MemberID,
			libTruncate($dao->Email, 64),
			libTruncate($dao->Nickname, 30),
			Dates::sqlDate($dao->NextPasswordChangeDate),
			$dao->ChangeNext,
			$dao->Confirmed,
			$dao->MFAEnabled,
		];

		$this->db->executeSQL($this->db->QCMembersDB, $sql, $params);
		return true;
	}

	public function GenerateToken()
	{
		$validChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

		$code = "";
		for($ix=0;$ix<64;$ix++) {
			$pos = random_int(0, 64);
			$code .= substr($validChars, $pos, 1);
		}

		return $code;
	}

	public function RequestPasswordReset()
	{
		if ($this->dao === null || $this->dao->AccountID == "" || $this->dao->Email == "") {
			logIt('RequestPasswordReset: no account id or email');
			return false;
		}

		$this->dao->ResetCode = bin2hex(random_bytes(32));

		$sql = "UPDATE Accounts SET ResetRequested = 1\n";
		$sql .= ", ResetStartDate = GETUTCDATE()\n";	// Date must be stored in UTC so verify-reset can convert to local time for a proper countdown
		$sql .= ", ResetCode = ?\n";
		$sql .= "WHERE AccountID = ?\n";
		$sql .= "and Username = ?\n";

		$params = [
			(new Crypto())->hash_password($this->dao->ResetCode.PEPPER)->hash,
			$this->dao->AccountID,
			$this->dao->Username,
		];

		$this->db->executeSQL($this->db->QCMembersDB, $sql, $params);
		LogEvent(Event::EVENT_PASSWORD_RESET, $this->dao->Username, "User: ".$this->dao->Username."  Email: ".$this->dao->Email);
		return $this->SendResetEmail($this->dao->Username, $this->dao->ResetCode);
	}

	private function SendResetEmail(string $username, string $resetCode)
	{
		if ($this->dao === null || $this->dao->Email == '') {
			return false;
		}
		
		$subject = $this->common->getConfig('SUBJECT', 'PASSWORD RESET REQUEST');
		if ($subject == '') {
			$subject = 'MyHealth Password Reset Request';
		}

		$htmlBody = ReadTemplate('password-reset-code.html');

        $logo = $this->common->getConfig('LOGO', 'EMAIL');
        if (empty($logo)) {
            $logo = $this->common->getConfig('LOGO', '');
        }

		$htmlBody = str_replace("%%SITEURL%%", siteUrl(), $htmlBody);
		$htmlBody = str_replace("%%LOGO%%", $logo, $htmlBody);

		$resetInfo = EncryptAESMSOGL(
			json_encode((object) ['u'=>$username, 'c'=>$resetCode]),
		);

		$htmlBody = str_replace('%%RESET_INFO%%', $resetInfo, $htmlBody);

		try {
			$mail = new Email();
			$mail->send($this->dao->Email, null, null, $subject, $htmlBody);
			logIt("Sent password reset email to ".$this->dao->Email);
			LogEvent(Event::EVENT_DEBUG, $this->dao->Username, "Sent password reset email to ".$this->dao->Email);
            return true;
		}
		catch(\Exception $e) {
			LogEvent(Event::EVENT_DEBUG, $this->dao->Username, "SMTP: ".$e->getMessage());
			logIt("SMTP: ".$e->getMessage());
            return false;
		}
	}

	public function GetResetCodeStart($username, $resetCode)
	{
		$sql = "SELECT * FROM Accounts WITH(NOLOCK)\n";
		$sql .= "WHERE Username = ?\n";
		$sql .= "AND ResetCode like ?\n";
		$sql .= "AND len(ResetCode) = ?\n";

		$params = [
			$username,
			$resetCode.'%',
			(strlen($resetCode) + 6),
		];

		$rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, $params);
		return (!$rs->EOF ? $this->db->rst($rs, "ResetStartDate") : "");
	}

	public function clearResetCode()
	{
		if ($this->dao === null || $this->dao->AccountID == "" || $this->dao->Username == "") {
			return;
		}

		$sql = "UPDATE Accounts SET ResetRequested = 0\n";
		$sql .= ", ResetStartDate = NULL\n";
		$sql .= ", ResetCode = NULL\n";
		$sql .= "WHERE AccountID = ?\n";
		$sql .= "AND Username = ?\n";

		$this->db->executeSQL($this->db->QCMembersDB, $sql, [$this->dao->AccountID, $this->dao->Username]);

		$this->dao->ResetCode = "";
		$this->dao->ResetStartDate = "";
	}

	public function verifyResetCode($username, $resetCode)
	{
        // Standard error message that we'll return if there's one.
        // Don't tip hand to malicious actor.
        $defaultErrorMessage = 'Invalid reset code';
		$dao = $this->getByUsername($username);

		$this->LastError = '';
		$isActiveAccount = (!is_null($dao) && $dao->Active == 1);

		if (!$isActiveAccount) {
			// Don't tip hand to malicious actor
			$this->LastError = $defaultErrorMessage;
			Logger::error("verifyResetCode: attempted on {$username} but account not found or not active");
            return false;
		}
		
        if ($dao->ResetCode == '' && $dao->ResetRequested == 0) {
			$this->LastError = 'Password reset not requested';
            return false;
		}

        // Date is stored in UTC, so deal with it as such
        $origTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $dtExpiresUTC = new \DateTime($dao->ResetStartDate);
        $validForMinutes = 24*60;
        $dtExpiresUTC->add(new \DateInterval("PT{$validForMinutes}M"));
        $dtNowUTC = new \DateTime('now');
        date_default_timezone_set($origTimezone);

        if ($dtNowUTC > $dtExpiresUTC) {
            $this->LastError = $defaultErrorMessage;
            Logger::info("verifyResetCode: Password reset attempt for {$username} but reset code expired, and is too stale. Invalidating.");
            return false;
        }
        
        if (! (new Crypto())->hash_compare($resetCode.PEPPER, $dao->ResetCode, null)) {
            $this->LastError = $defaultErrorMessage;
            Logger::info("verifyResetCode: Password reset attempt for {$username} but reset code does not match.");
            return false;
        }

        /**
         * SIDE EFFECT!!!
         * One last thing... if the Confirmed flag isn't set to 1, we can assume
         * the email works because they got the reset code successfully, so
         * set it to 1 here.
         */
		if ($dao->Confirmed != 1) {
			$dao->Confirmed = 1;
			$this->update($dao);
            Logger::info("verifyResetCode: Password reset attempt for {$username} successful. Email address not previously confirmed. Updated to confirmed.");
            LogEvent(Event::EVENT_EMAIL_CONFIRMED, $dao->Username, "Email: {$dao->Email} (confirmed during password reset process)");
		}

		return true;
	}

	public function changePassword($username, $password)
	{
		$this->LastError = null;
		$dao = $this->getByUsername($username);

		if (is_null($dao)) {
			$this->LastError = 'Could not load account information. Password change failed.';
			return false;
		}
		
		if ((new Crypto())->hash_compare(
			$password.PEPPER,
			$dao->Password,
			$dao->Salt,
			$dao->Version
		)) {
			$this->LastError = 'Your new password cannot be the same as your current password.';
			return false;
		}

		$errorMsg = $this->setPassword($password);

		if ($errorMsg != '') {
			$this->LastError = $errorMsg;
			return false;
		}
		
		return true;
	}

	public function setPassword($password)
	{
		if ($this->dao === null || $this->dao->Username == "") {
			return 'Unable to change password';
		}

		$policy = new PasswordPolicyModel();

		if (!$policy->loadPolicyFromQuickCap()) {
			$policy->setDefaultPolicy();
		}

		if ($policy->testPasswordStrength($password)) {
			$hash = (new Crypto())->hash_password($password.PEPPER);
			$this->dao->Password = $hash->hash;
			$this->dao->Salt = $hash->salt;
			$this->dao->Version = $hash->version;
			$this->dao->ChangeNext = 0;
			$this->dao->NextPasswordChangeDate = ($policy->EnableExpiration ? Dates::dateAdd('now', $policy->ValidPeriod, 'day') : '12/31/2050');

			$params = [
				$this->dao->Password,
				$this->dao->Salt,
				$this->dao->Version,
				Dates::sqlDate($this->dao->NextPasswordChangeDate),
				$this->dao->AccountID,
			];

			$sql = "UPDATE Accounts SET\n";
			$sql .= "Password = ?\n";
			$sql .= ", Salt = ?\n";
			$sql .= ", Version = ?\n";
			$sql .= ", ChangeNext = 0\n";
			$sql .= ", NextPasswordChangeDate = ?\n";
			$sql .= "WHERE AccountID = ?\n";

			$this->db->executeNonQuery($sql, $params);
			LogEvent(Event::EVENT_PASSWORD_CHANGED, $this->dao->Username, "");
			return '';		// Empty string = success
		}
		else {
			$errorMsg = "We're sorry. Your new password does not meet our password requirements.<br/>";

			if ($policy->MinLength > 0) {
				$errorMsg .= "Must be at least ".$policy->MinLength." characters long<br/>";
			}

			if ($policy->MinUpper > 0) {
				$errorMsg .= "Must contain at least ".$policy->MinUpper." uppercase characters<br/>";
			}

			if ($policy->MinNumeric > 0) {
				$errorMsg .= "Must contain at least ".$policy->MinNumeric." numeric characters<br/>";
			}

			if ($policy->MinSpecial > 0) {
				$errorMsg .= "Must contain at least ".$policy->MinSpecial." special characters (".$policy->ValidSpecialCharacters.")<br/>";
			}

			return $errorMsg;
		}
	}

	public function updatePasswordHash($hash, $salt, $version)
	{
		if ($this->dao === null || _isNEZ($this->dao->AccountID)) {
			return;
		}

		$sql = "UPDATE Accounts SET oldpw = NULL, Password = ?, Salt = ?, Version = ?\n";
		$sql .= " where AccountID = ?\n";

		$params = [
			$hash,
			$salt,
			$version,
			$this->dao->AccountID,
		];

		$this->db->executeNonQuery($sql, $params);

		$this->dao->Password = $hash;
		$this->dao->Salt = $salt;
		$this->dao->Version = $version;
	}

	public function generateMFACode()
	{
		// MFA code is a token plus a 6-digit code. The 6-digit code is for the end user.
		$mfaCode = $this->GenerateToken();

		for($ix=0;$ix<self::TOKEN_SIZE;$ix++) {
			$mfaCode .= strval(random_int(0,9));
		}

		return $mfaCode;
	}

	public function generateAndSendMFACode($destination)
	{
		if ($this->dao === null) {
			return '';
		}

		$this->dao->MFACode = $this->generateMFACode();

		$sql = "update Accounts set MFACode = ?";
		$sql .=  ", MFAStartDate = GETUTCDATE()";
		$sql .=  " where Username = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, [$this->dao->MFACode, $this->dao->Username]);

		if ($destination == 'email') {
			return $this->sendMFAEmail();
		}
		elseif ($destination == 'sms') {
			return $this->sendMFASMS();
		}
		else {
			return '';
		}
	}

	public function updateOTPSecret($otpSecret)
	{
		if ($this->dao === null || _isNEZ($this->dao->AccountID)) {
			return;
		}

		$sql = "update Accounts set OTPSecret = ?";
		$sql .= " where AccountID = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, array($otpSecret, $this->dao->AccountID));
	}

    /**
	 * Clears MFA code and start date. Typically used when a code is
     * expired.
	 */
	public function clearMFA()
	{
		if ($this->dao === null) {
			return;
		}

		$sql = "update Accounts set MFACode = NULL, MFAStartDate = NULL";
		$sql .= " where AccountID = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, array($this->dao->AccountID));
	}
    
	/**
	 * Revokes MFA for a user without disabling it. Clears the OTP secret, and any
	 * remembered MFA codes. User will have to set MFA up again on next login.
	 * 
	 * @return bool
	 */
	public function revokeMFA()
	{
		if ($this->dao === null) {
			return false;
		}

		$sql = "UPDATE Accounts SET MFACode = NULL, MFAStartDate = NULL, OTPSecret = NULL";
		$sql .= " where AccountID = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, array($this->dao->AccountID));

		(new MFATrustModel())->clearAllForAccount($this->dao->AccountID);

		return true;
	}

	public function sendMFAEmail()
	{
		$subject = $this->common->GetConfig('SUBJECT', 'MFA');
		if ($subject == '') {
			$subject = 'MyHealth Authentication Code';
		}

		$mfaToken = substr($this->dao->MFACode, 0, strlen($this->dao->MFACode)-self::TOKEN_SIZE);
		$mfaCode = substr($this->dao->MFACode, 0-self::TOKEN_SIZE);

		$subject = str_replace('%%mfa_code%%', $mfaCode, $subject);			// For those who want the code in the subject line
		
		$htmlBody = ReadTemplate('MFACode.html');

        $logo = $this->common->getConfig('LOGO', 'EMAIL');
        if ($logo == '') {
            $logo = $this->common->getConfig('LOGO', '');
        }

        $altName = $this->common->GetConfig('CLIENT NAME', '');
        if ($altName == '') {
            $altName = $this->client;
        }
        $altName .= ' MyHealth';

        $htmlBody = str_replace('%%logo_embed%%', siteUrl().'/'.$logo, $htmlBody);
        $htmlBody = str_replace('%%logo_alt%%', $altName, $htmlBody);
        $htmlBody = str_replace('%%mfa_code%%', $mfaCode, $htmlBody);

		try {
			$email = new Email();
			$email->send($this->dao->Email, null, null, $subject, $htmlBody);
			logIt('Sent MFA email to '.$this->dao->Email);
			LogEvent(Event::EVENT_DEBUG, $this->dao->Username, 'Send MFA email to '.$this->dao->Email);
		}
		catch(\Exception $e) {
			LogEvent(Event::EVENT_DEBUG, $this->dao->Username, 'Email: '.$e->getMessage());
			logIt('Email: '.$e->getMessage());
		}

		$id = json_encode(['accountId'=>$this->dao->AccountID, 'email'=>trim($this->dao->Email)]);
		return 'id='.EncryptAESMSOGL($id).'&c='.$mfaToken;
	}

	public function sendMFASMS()
	{
		if (($this->dao->MFAEnabled != 1 && $this->common->getConfig('MFA', 'GLOBALLY ENABLED') != 'true') || 
			_isNE($this->dao->SMSNumber) ||
			_isNE($this->dao->MFACode)) {
			return false;
		}

		$mfaToken = substr($this->dao->MFACode, 0, strlen($this->dao->MFACode)-self::TOKEN_SIZE);
		$mfaCode = substr($this->dao->MFACode, 0-self::TOKEN_SIZE);
		$message = $mfaCode.' is your '.$this->client.' portal authentication code';

		$result = SMS::sendSMS($this->dao->Username, $this->dao->SMSNumber, $message);

		if ($result == 'Message sent') {
			Logger::info('sendMFASMS: '.$result);
		}

		$id = json_encode(['accountId'=>$this->dao->AccountID, 'email'=>trim($this->dao->Email)]);
		return 'id='.EncryptAESMSOGL($id).'&c='.$mfaToken.'&s=1';
	}

	public function activate()
	{
		if ($this->dao === null) {
			return false;
		}

		$sql = "UPDATE Accounts SET Active = 1, LastUpdateDateTime = GETDATE() WHERE AccountID = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, [$this->dao->AccountID]);
		return true;
	}

	public function deactivate()
	{
		if ($this->dao === null) {
			return false;
		}

		$sql = "UPDATE Accounts SET Active = 0, LastUpdateDateTime = GETDATE() WHERE AccountID = ?";
		$this->db->executeSQL($this->db->QCMembersDB, $sql, [$this->dao->AccountID]);
		return true;
	}

    public function getUserReview(int $idleDays, bool $activeOnly): array
    {
        $sql = "exec UserReview ?, ?";
        return $this->db->magicWrappers(
            $this->db->GetRecords($this->db->QCMembersDB, $sql, [$idleDays, ($activeOnly ? 1 : 0)])
        );
    }

    /**
     * Sends email for the email confirmation process. This is intended to 
     * confirm that the email address is real and working. The user must confirm
     * receipt of the email, after which the account is activated. This also
     * updates the ConfirmStartDate.
     * 
     * @param AccountDAO $accountDao
     * return string|bool - error message if failed, true if succeeded
     */
    public function sendConfirmationEmail(AccountDAO $accountDao, bool $fromEmailChange=false): bool
    {
        if (is_null($accountDao)) {
            return 'Unable to send email. Unexpected error.';
        }

        $htmlTemplate = ReadTemplate('email-confirm.html');

        $confirmationCode = EncryptAESMSOGL($accountDao->AccountID.','.$accountDao->Email);

        if ($fromEmailChange) {
            $confirmationCode .= '&ch=1';
        }

        $htmlTemplate = str_replace('%%CLIENTNAME%%', $this->common->getConfig('CLIENT NAME', ''), $htmlTemplate);
        $htmlTemplate = str_replace('%%FIRSTNAME%%', $accountDao->Firstname, $htmlTemplate);
        $htmlTemplate = str_replace('%%CONFIRMATIONCODE%%', $confirmationCode, $htmlTemplate);
        $htmlTemplate = str_replace('%%SITEURL%%', siteUrl(), $htmlTemplate);

        try {
            (new Email())->send($accountDao->Email, null, null, 'Email Confirmation', $htmlTemplate);
            $sql = "UPDATE Accounts SET ConfirmStartDate = GETUTCDATE() WHERE AccountID = ?\n";
            $this->db->executeSQL($this->db->QCMembersDB, $sql, [ $accountDao->AccountID ]);
            return true;
        }
        catch(Exception $e) {
            Logger::error(__FUNCTION__.': '.$e->getMessage());
            return false;
        }
    }

    /**
     * At this time, accounts do not have a phone/SMS number field,
     * however, we'll add it as a dynamic property for the developer
     * for testing purposes.
     */
    private function addSMSNumber()
    {
        if (!isset($this->dao->SMSNumber)) {
            $this->dao->SMSNumber = null;
        }

        if (isDeveloper($this->dao->Username)) {
            $this->dao->SMSNumber = $_ENV['DEV_SMS_NUMBER'] ?? null;
        }
    }
}
