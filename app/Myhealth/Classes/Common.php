<?php
//===================================================================
// Copyright by RPPG, 2009-Present. All rights reserved.
//===================================================================

namespace Myhealth\Classes;

use Myhealth\Core\DB;

class Common
{

	private $mDBName;

	public function ControlCache()
	{
		header("Expires: Tue, 01 Jan 1900 1:00:00 GMT");
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, no-store, must-revalidate");
	}

	public function setDBName($DBName)
	{
		$oDB = new DB();
		$orConfig = $oDB->GetRecords($DBName, "select ServiceName from qcpService");

		if (!$orConfig->EOF) {
			$this->mDBName = $oDB->rst($orConfig, "ServiceName");
		}
		else {
			$this->mDBName = "";
		}
	}

	public function getDBName()
	{
		return $this->mDBName;
	}

	public function getConfig($name, $reference)
	{
		if ($name == 'LOGO' ) {
			if ($this->getConfig('DEMO MODE', '') == 'true' ) {
				return $GLOBALS['appRoot']."images/qcportal_logo.gif";
			}
		}

		$oDB = new DB();

		if ($reference != '') {
			$rs = $oDB->GetRecords($oDB->QCMembersDB, 'exec GetConfig ?, ?', [$name, $reference]);
		}
		else {
			$rs = $oDB->GetRecords($oDB->QCMembersDB, 'exec GetConfig ?', [$name]);
		}

		return ($oDB->hasRecords($rs) ? $oDB->rst($rs, 'value') : '');
	}

	public function myFormatMoney($toFormat)
	{
		return '$'.$this->myFormatNumber($toFormat, '0.00');
	}

	// 10/16/2020 JLC SMELL? May be able to just use number_format
	public function myFormatNumber($NumberToFormat, $ReturnIfBlank)
	{
		// NumberToFormat - String
		// ReturnIfBlank - String
		// Returns: String

		return ($NumberToFormat != "" ? number_format($NumberToFormat, 2) : $ReturnIfBlank);
	}

	public function getString($StringName, $DefaultValue)
	{

		$oDB = New DB();
		$rs = $oDB->GetRecords($oDB->QCMembersDB, "SELECT * FROM Strings WHERE Name = ?", [$StringName]);
		return ($oDB->hasRecords($rs) ? $oDB->rst($rs, 'Value') : $DefaultValue);
	}


	public function ClaimStatus($code)
	{
        return match(strtoupper($code ?? '')) {
			'O' => $this->getString('ClaimStatus_O', 'Open'),
			'P' => $this->getString('ClaimStatus_P', 'Paid'),
			'H' => $this->getString('ClaimStatus_H', 'On Hold'),
			default => ''
		};
	}

	public function ReferralStatus($code)
	{
		return match(strtoupper($code ?? '')) {
			'A' => $this->getString('ReferralStatus_A', 'Approved'),
			'P' => $this->getString('ReferralStatus_P', 'Pending'),
			'H' => $this->getString('ReferralStatus_H', 'On Hold'),
			default => ''
		};
	}

	public function hasModule($moduleName)
	{
		return ($this->getConfig('MODULE', $moduleName) == 'true');
	}

	public function isFeatureEnabled($moduleName)
	{
		return ($this->getConfig('FEATURE', $moduleName) == 'true');
	}

	public function getCompanyName()
	{

		$db = new DB();
		$rs = $db->GetRecords($db->QuickCapDB, "select ServiceName from qcpService");

		if (!$rs->EOF) {
			return $db->rst($rs, "ServiceName");
		}
		else {
			return "";
		}
	}
}

?>
