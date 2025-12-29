<?php

namespace Myhealth\Models;

use Myhealth\Core\DB;

class PasswordPolicyModel extends BaseModel
{
	public $EnableStrength;
	public $MinLength;
	public $MinNumeric;
	public $MinUpper;
	public $MinSpecial;
	public $NoRepeat;
	public $NoNumSeq;
	public $NoAlphaSeq;
	public $MustDiffer;
	public $ValidSpecialCharacters;
	public $EnableExpiration;
	public $ValidPeriod;
	public $StrengthFailMsg;

	private $lowerChars = "abcdefghijklmnopqrstuvwxyz";
	private $upperChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	private $numberChars = "0123456789";
	private $specialChars = "`~!@#$%^&*()-=_+[]\{}|;':,./!=?\" ";

	public function __construct()
	{
		parent::__construct();
		$this->setDefaultPolicy();
	}

	public function setDefaultPolicy()
	{
		$this->EnableStrength = true;
		$this->MinLength = 8;
		$this->MinNumeric = 0;
		$this->MinUpper = 0;
		$this->MinSpecial = 0;
		$this->NoRepeat = false;
		$this->NoNumSeq = false;
		$this->NoAlphaSeq = false;
		$this->MustDiffer = false;
		$this->ValidSpecialCharacters = "~!@#$%^&*()";
		$this->EnableExpiration = true;
		$this->ValidPeriod = 90;
	}

	public function loadPolicyFromQuickCap()
	{
		$oQCDB = new DB();
		$rs = $oQCDB->GetRecords($this->db->QuickCapDB, "select top 1 * from qcpService");

		if (!$rs->EOF) {
			if ($oQCDB->rst($rs, "EnableStrength") == "1") {
				$this->EnableStrength = true;
			}
			else {
				//logIt("Password policy = ".$oQCDB->rst(rs, "EnableStrength"));
				$this->EnableStrength = false;
			}

			$this->MinLength = $oQCDB->rst($rs, "MinLength");
			$this->MinNumeric = $oQCDB->rst($rs, "MinNumeric");
			$this->MinUpper = $oQCDB->rst($rs, "MinUpper");
			$this->MinSpecial = $oQCDB->rst($rs, "MinSpecial");
			$this->NoRepeat = $oQCDB->rst($rs, "NoRepeat");
			$this->NoNumSeq = $oQCDB->rst($rs, "NoNumSeq");
			$this->NoAlphaSeq = $oQCDB->rst($rs, "NoAlphaSeq");
			$this->MustDiffer = $oQCDB->rst($rs, "MustDiffer");

			if ($oQCDB->rst($rs, "EnableExpiration") == 1) {
				$this->EnableExpiration = true;
			}
			else {
				$this->EnableExpiration = false;
			}

			$this->ValidPeriod = $oQCDB->rst($rs, "ValidPeriod");
			$loaded = true;
		}
		else {
			$loaded = false;
		}

		$rs->Close();
		$rs = null;
		$oQCDB = null;

		if (!$this->EnableStrength) {
			$loaded = false;
		}

		return $loaded;
	}

	public function testPasswordStrength($parPassword)  //20080619 JMS
	{
		$passed = false;

		if (!$this->EnableStrength) {
			$this->StrengthFailMsg = "strength not enabled";
			return true;
		}
		else {
			$pLen = strlen(trim($parPassword));
			if ($pLen < $this->MinLength) {
				$this->StrengthFailMsg = "Must be ".$this->MinLength." characters minimum";
				return false;
			}
			else {
				$nCnt = 0;
				$uCnt = 0;
				$sCnt = 0;
				$SawRepeat = false;
				$SawNumSeq = false;
				$SawAlphaSeq = false;
				for($pPdx=0;$pPdx<$pLen;$pPdx++) {
					$Char1 = substr($parPassword, $pPdx, 1);
					if ($pPdx < $pLen - 1) {
						$Char2 = substr($parPassword, $pPdx + 1, 1);
					}
					if ($pPdx < $pLen - 2) {
						$Char3 = substr($parPassword, $pPdx + 2, 1);
						if ($Char1 == $Char2 && $Char1 == $Char3) {
							$SawRepeat = true;
						}
						if (is_numeric($Char1)) {
							if (ord($Char1) == (ord($Char2) - 1) && ord($Char1) == (ord($Char3) - 2)) {
								$SawRepeat = true;
							}
							if (ord($Char1) == (ord($Char2) + 1) && ord($Char1) == (ord($Char3) + 2)) {
								$SawRepeatNumSeq = true;
							}
						}
					}
					if ($pPdx < $pLen - 3) {
						$Char4 = substr($parPassword, $pPdx + 3, 1);
						if ($this->isAlpha($Char1)) {
							if (ord($Char1) == (ord($Char2) - 1) && ord($Char1) == (ord($Char3) - 2) && ord($Char1) == (ord($Char4) - 3)) {
								$SawAlphaSeq = true;
							}
							if (ord($Char1) == (ord($Char2) + 1) && ord($Char1) == (ord($Char3) + 2) && ord($Char1) == (ord($Char4) + 3)) {
								$SawAlphaSeq = true;
							}
						}
					}
					if (is_numeric($Char1)) {
						$nCnt++;
					}
					//If pIdx > 1 && isUpper($Char1) Then
					if ($this->isUpper($Char1)) {
						$uCnt++;
					}
					if ($this->isSpecial($Char1)) {
						$sCnt++;
					}
				}

				if ($nCnt < $this->MinNumeric) {
					$this->StrengthFailMsg = "Must have ".$this->MinNumeric." numeric characters minimum";
					$passed = false;
				}
				elseif ($uCnt < $this->MinUpper) {
					//20080908 JMS Improve error message
					//StrengthFailMsg = "(must have ".typService.MinUpper." upper case letters minimum)";
					$this->StrengthFailMsg = "Must have ".$this->MinUpper." upper case letter";

					//If mMinUpper > 1 Then
					//	StrengthFailMsg = StrengthFailMsg."s";
					//End If

					$this->StrengthFailMsg .= " minimum ";	//other than the first character";

					//20080908 JMS
					$passed = false;
				}
				elseif ($sCnt < $this->MinSpecial) {
					$this->StrengthFailMsg = "Must have ".$this->MinSpecial." special character";

					if ($this->MinSpecial > 1) {
						$this->StrengthFailMsg .= "s";
					}

					$this->StrengthFailMsg .= " minimum";
					$passed = false;
				}
				elseif ($this->NoRepeat && $SawRepeat) {
					$this->StrengthFailMsg = "Cannot have same character 3 times in succession";
					$passed = false;
				}
				elseif ($this->NoAlphaSeq && $SawAlphaSeq) {
					$this->StrengthFailMsg = "Cannot have 4 sequential letters in succession";
					$passed = false;
				}
				elseif ($this->NoNumSeq && $SawNumSeq) {
					$this->StrengthFailMsg = "Cannot have 3 sequential numbers in succession";
					$passed = false;
				}
				else {
					$passed = true;
				}
			}
		}

		return $passed;
	}

	private function isAlpha($char)
	{
		return (strpos($this->lowerChars, $char) !== false || strpos($this->upperChars, $char) !== false);
	}

	public function isUpper($char)
	{
		return (strpos($this->upperChars, $char) !== false);
	}

	private function isSpecial($char)
	{
		return (strpos($this->specialChars, $char) !== false);
	}

	public function ShowRules()
	{
		$rules = "";

		if ($this->MinLength > 0) {
			$rules .= "<li>Minimum length: ".$this->MinLength."</li>\n";
		}

		if ($this->MinNumeric > 0) {
			$rules .= "<li>Must have at least ".$this->MinNumeric." numeric characters</li>\n";
		}

		if ($this->MinUpper > 0) {
			$rules .= "<li>Must have at least ".$this->MinUpper." uppercase characters</li>\n";
		}

		if ($this->MinSpecial > 0) {
			$rules .= "<li>Must have at least ".$this->MinSpecial." special characters</li>\n";
		}

		if ($this->NoRepeat) {
			$rules .= "<li>Cannot have the same characters repeating 3 or more times in a row</li>\n";
		}

		if ($this->NoAlphaSeq) {
			$rules .= "<li>Cannot have 4 or more sequential letters</li>\n";
		}

		if ($this->NoNumSeq) {
			$rules .= "<li>Cannot have 3 or more sequential numbers</li>\n";
		}

		if ($this->MustDiffer) {
			$rules .= "<li>Password must differ from your previous 3 passwords</li><br/>\n";
		}

		if ($rules != "") {
			$rules = '<ul style="margin:.5em 1.25em 1em 3.25em;padding:0;">'.$rules."</ul>\n";
		}

		return $rules;
	}

	public function generatePassword($pwLength=0)
	{
		if (!$this->loadPolicyFromQuickCap()) {
			logIt("Could not load password policy from QuickCap");
			return '';
		}

		$allowedChars = "";

		$allowedChars .= $this->lowerChars;
		$allowedChars .= $this->upperChars;
		$allowedChars .= $this->numberChars;
		$allowedChars .= $this->specialChars;

		// Do not allow less than the configured minimum length
		if ($pwLength < $this->MinLength) {
			$pwLength = $this->MinLength;
		}

		do {
			$generated = "";
			for($ix=1;$ix<=$pwLength;$ix++) {
				$rpos = random_int(0, strlen($allowedChars)-1);
				$generated .= substr($allowedChars, $rpos, 1);
			}
		}
		while(!$this->testPasswordStrength($generated));

		if ($generated == "") {
			logIt("Generated password is blank.");
		}

		return $generated;
	}
}
