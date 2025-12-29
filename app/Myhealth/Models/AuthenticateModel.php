<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Core\Crypto;
use Myhealth\Classes\Event;
use Myhealth\Classes\LoginState;
use Myhealth\Classes\Permission;
use Myhealth\Models\AgreementModel;

class AuthenticateModel extends BaseModel
{
	private $SALT = '|89AF7834ED10|';			// completely random when I programmed this (and legacy)

	public function authenticateUser($username, $password, $mfaValidated)
	{
		$result = LoginState::LOGIN_FAILED;

		$accountModel = new AccountModel();
		$accountDao = $accountModel->getByUsername($username);

		// Lockouts apply whether the user exists or not.
		// We don't want to allow unlimited attempts on non-existent users.
		$lockoutModel = new LockoutModel();
		if ($lockoutModel->isLocked($username)) {
			return LoginState::LOCKED;
		}

        // account not found or not active
		if ($accountDao === null || $accountDao->Active != 1) {
			return LoginState::LOGIN_FAILED;
		}

		if (!$mfaValidated) {
			$crypto = new Crypto();
			$upgradeRequired = false;

			// Check if we're using the old encryption technique.
			// If so, check password, then convert to latest algorithm.
			if (_isNE($accountDao->Version)) {
				$encryptedPassword = $this->legacyHash($password, $username);
				if ($accountDao->Password != $encryptedPassword) {
					return LoginState::LOGIN_FAILED;
				}

				$upgradeRequired = true;
			}
			else {
				if (!$crypto->hash_compare(
					$password.PEPPER,
					$accountDao->Password,
					$accountDao->Salt,
					$accountDao->Version
				)) {
					return LoginState::LOGIN_FAILED;
				}

				$upgradeRequired = (intval($accountDao->Version) < HASH_VERSION);
			}

			if ($upgradeRequired) {
				// Password matches, but need to convert to new hash scheme
				$hashInfo = $crypto->hash_password($password.PEPPER);
				$accountModel->updatePasswordHash($hashInfo->hash, $hashInfo->salt, HASH_VERSION);
			}
		}

		// 01/16/2023 JLC Added auto-suspend after member terms. Per Kim, let them have access for up to 90 days
		// after term so they can see claims that come in after the fact.
		// 05/18/2023 JLC Kim asked for 1 year instead of 90 days
		$memberDao = (new MemberModel())->getById($accountDao->MemberID);
		if (!Permission::isAdmin($username)) {
			if ($memberDao === null || (!_isNE($memberDao->TerminationDate) && Dates::dateTimeDiff($memberDao->TerminationDate, 'now', 'y') >= 1)) {
				return LoginState::SUSPENDED;		// Member termed, access prevented; supercedes other reasons
			}
		}
		
		if ($accountDao->Confirmed == 0) {
			$_SESSION["accountid"] = $accountDao->AccountID;
            return LoginState::NOT_CONFIRMED;		// account not confirmed
		}
        if (!$mfaValidated && $this->isMFARequired($accountDao)) {
            return LoginState::MFA_REQUIRED;
        }

        if ($accountDao->ChangeNext) {
            return LoginState::PASSWORD_CHANGE_REQUIRED;		// password change required
        }

        if (Dates::dateGE('now', $accountDao->NextPasswordChangeDate)) {
            return LoginState::PASSWORD_EXPIRED;		// password expired
        }

		if (!(new AgreementModel())->agreed($username)) {
            return LoginState::AGREEMENT_REQUIRED;
        }

        return LoginState::LOGGED_IN;		// account found; everything is ok
	}

	// 02/13/2024 JLC Legacy - keep around while passwords gradually get upgraded.
	// When it's removed, also remove the private $SALT variable.
	private function legacyHash($toEncode, $key)
	{
		// We don't just encode . . . we use a combination of a key, plus the SALT variable.
		// SALT must NEVER change. Key must always be lowercase
		$encodeThis = trim(strtolower($key)).$this->SALT.$toEncode;
		return md5($encodeThis);
	}

	private function isMFARequired(&$accountDao) {
		$mfaRequired = false;

		if ($this->common->isFeatureEnabled('MFA') &&
			($this->common->getConfig('MFA', 'METHODS') != '')) {
			logIt('MFA feature enabled');
			$mfaRequired = true;

			if ($accountDao->MFAEnabled != 1 && $this->common->getConfig('MFA', 'GLOBALLY ENABLED') != 'true') {
				logIt("MFA not enabled for user {$accountDao->Username}");
				$mfaRequired = false;
			}
			else {
				logIt("MFA enabled for user {$accountDao->Username}");
				$mfaRequired = true;
				$mfaCode = ($this->common->getConfig('MFA', 'ENABLE TRUST BROWSER') == 'true' ? getCookie("{$this->client}_trust") : '');

				// 10/02/2020 JLC If there's an MFA cookie, that means the user wanted their computer
				// remembered for 30 days. We'll validate the cookie and skip the MFA process if it's valid.
				if ($mfaCode != '') {
					logIt("MFACode via cookie for {$accountDao->Username} (trusted browser)");
					if ((new MFATrustModel())->isMFAValid($accountDao->AccountID, $mfaCode)) {
						$mfaRequired = false;
						LogEvent(Event::EVENT_MFA_TRUST, $accountDao->Username, "AccountID: ".$accountDao->AccountID);
					}
					else {
						// Not a valid MFA code for this user, so delete the cookie
						
						libSetCookie("{$this->client}_trust", '', 'January 1, 2000');
					}
				}
			}
		}

		return $mfaRequired;
	}
}
