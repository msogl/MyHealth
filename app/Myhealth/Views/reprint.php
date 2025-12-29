<?php

use Myhealth\Classes\Common;
use Myhealth\Classes\Event;
use Myhealth\Core\Dates;
use Myhealth\Core\DB;
use Myhealth\Core\Logger;

// 12/05/2025 JLC Ugh... this needs to be converted to a class or something.
// Defining these as global here because this is now an included file,
// which changes the scope.

global $oCommon;
global $db;
global $authorizedToView;
global $orMember;
global $orReferral;
global $orDetail;
global $orReferring;
global $orAddress;
global $UseSpecialAddress;
global $printWidth;
global $Heading;
global $ReprintType;
global $client;
global $RefType;
global $RefNum;
global $isError;

$oCommon = new Common();
$db = new DB();

$RefType = "";
$RefNum = html_encode(Request("refnum"));

LogEvent(Event::EVENT_REF_AUTH_VIEW, $_SESSION["loggedin"], "Ref/Auth #".$RefNum);

// ------------------------------------------------------------
// GLOBALS
// I loathe doing this
$authorizedToView = true;
$orMember = null;
$orReferral = null;
$orDetail = null;
$orReferring = null;
$orReferredTo = null;
$orAddress = null;
$UseSpecialAddress = false;
$printWidth = '100%';
$Heading = $oCommon->getDBName();
$ReprintType = '';
$client = $oCommon->getConfig("CLIENTID", "");
// ------------------------------------------------------------

FindRef($RefNum);
$oCommon->setDBName($db->QuickCapDB);

function GetPhoneAndFax($ProviderID)
{
	global $db, $orReferral;

	$phoneAndFax = new stdClass();
	$phoneAndFax->Phone = "";
	$phoneAndFax->Fax = "";

	if (!$orReferral->EOF) {
		$sql = "SELECT a.Phone, a.Fax FROM qcpProvider p ".
			"JOIN qcpAddress a on a.OwnerID = p.Provider AND ".
			"a.Type = 1 and a.Active = 1 ".
			"WHERE p.Provider = ?";
		$orProvider = $db->GetRecords($db->QuickCapDB, $sql, array($ProviderID));

		if (!$orProvider->EOF) {
			$phoneAndFax->Phone = libFormatPhone($db->rst($orProvider, "Phone"));
			$phoneAndFax->Fax = libFormatPhone($db->rst($orProvider, "Fax"));
		}
	}

	return $phoneAndFax;
}

function GetSpecialty($specCode)
{
	global $db;

	$sql = "SELECT Specialty FROM qcpSpecialties WHERE Code = ?";
	$rs = $db->GetRecords($db->QuickCapDB, $sql, array($specCode));

	return (!$rs->EOF ? $db->rst($rs, "Specialty") : '');
}

function FindRef($RefNum)
{
    global $db, $RefType, $authorizedToView, $UseSpecialAddress;
	global $orReferral, $orDetail, $orMember, $orReferring, $orReferredTo, $orAddress;

	$RefType = "";

	$sql = "SELECT * FROM qcpReferral WHERE ReferralNumber = ?";
	$orReferral = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));

	if (!$orReferral->EOF) {
		$RefType = "REFERRAL";
	}

	if ($RefType != "REFERRAL") {
		$sql = "SELECT * FROM qcpAuthorizationHeader WHERE AuthorizationNumber = ?";
		$orReferral = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));

		if (!$orReferral->EOF) {
			$RefType = "AUTHORIZATION";
		}
	}

	// Make sure the referral belongs to the member
	if ($db->rst($orReferral, "MemberID") != $_SESSION["loggedInMemberId"]) {
		$msg = "Unauthorized attempt to view referral reprint for #{$RefNum}; referral does not belong to member";
		Logger::info($msg);
		LogEvent(EVENT::EVENT_UNAUTHORIZED, $_SESSION['loggedin'], $msg);
		$authorizedToView = false;
		return;
	}

	if ($db->rst($orReferral, "Status") != 'A') {
		$msg = "Unauthorized attempt to view referral reprint for #{$RefNum}; referral is not approved";
		Logger::info($msg);
		LogEvent(EVENT::EVENT_UNAUTHORIZED, $_SESSION['loggedin'], $msg);
		$authorizedToView = false;
		return;
	}

    if ($RefType == "REFERRAL") {
		$orReferral = GetReferralHeader($RefNum);
		$orDetail = GetReferralDetail($RefNum);

		/* Is this used?
		$benefitCode = "";
		$sql = "select mh.ChangeDate, mh.Action, mh.ChangeFrom, mh.ChangeTo\n";
		$sql .= " from qcpReferral r with(nolock)\n";
		$sql .= " join qcpMemberHistory mh with(nolock) on mh.Member = r.MemberID\n";
		$sql = sql &	" where r.ReferralNumber = ?\n";
		$sql .= " and mh.Action LIKE 'Benefit%'\n";
		$sql = sql &	" and mh.ChangeDate > GETDATE()\n";
		$rs = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));
		if (!$rs->EOF) {
			$benefitCode = $db->rst($rs, "ChangeFrom");
		}

		$rs->Close();
		$rs = null;
		*/

		$sql = "exec ".$db->QCPortalDB."..sourceGetBenefitCopays ?, null";
		$orMember = $db->GetRecords($db->QuickCapDB, $sql, array($db->rst($orReferral, "MemberID")));
		$orReferredTo = GetProvider($db->rst($orReferral, "Provider"));

		// 01/03/2008 JLC
		$UseSpecialAddress = false;

		if (!$orReferral->EOF) {
			if ($db->rst($orReferral, "AddressId") != "") {
				$sql = "select * from qcpAddress where AddressId = ?";
				$orAddress = $db->GetRecords($db->QuickCapDB, $sql, array($db->rst($orReferral, "AddressId")));
				$UseSpecialAddress = true;
			}
		}
	}
	elseif ($RefType == "AUTHORIZATION") {
		$sql = "SELECT Auth.*, rc.ResponseDescription,\n";
		$sql .= "Member.MBR_PCP_number AS MemberPcp,\n";
		$sql .= "Member.SiteNumber AS MemberSiteNumber, Member.MBR_last_name AS MemberLastName,\n";
		$sql .= "Member.MBR_first_name AS MemberFirstName, Member.MiddleInitial AS MemberMiddleInitial,\n";
		$sql .= "PayorSite.Description AS SiteDescription, Payor.Payor, Payor.PayorName AS PayorName,\n";
		$sql .= "PCP.LastName AS PcpLastName, PCP.FirstName AS PcpFirstName,\n";
		$sql .= "AuthorizedPersons.Name AS UserID,\n";
		$sql .= "icd9.ICD_9_Description\n";
		$sql .= "FROM qcpAuthorizationHeader AS Auth\n";
		$sql .= "LEFT JOIN qcpMember Member ON Auth.MemberID = Member.MBR_SSN_number\n";
		$sql .= "LEFT JOIN qcpPayor Payor ON Member.Payor = Payor.Payor\n";
		$sql .= "LEFT JOIN qcpPayorSite PayorSite ON Member.SiteNumber = PayorSite.SiteNumber AND Member.Payor = PayorSite.Payor\n";
		$sql .= "LEFT JOIN qcpProvider AS PCP ON PCP.Provider = Member.MBR_PCP_number\n";
		$sql .= "LEFT JOIN qcpAuthorizedPersons ON AuthorizedPersons.Code = Auth.Authorizedby\n";
		$sql .= "LEFT OUTER JOIN qcpResponseCodes rc ON rc.ResponseCode = Auth.DispositionCode\n";
		$sql .= "LEFT JOIN qcpICD_9_Code icd9 ON icd9.ICD_9_number = Auth.DiagnosisCode\n";
		$sql .= "WHERE Auth.AuthorizationNumber = ?\n";
		$orReferral = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));

		$sql = "SELECT Detail.*, Provider.FirstName, Provider.LastName, Specialties.Specialty,\n";
		$sql .= "FacilityType.Name AS FacType, AdmitType.Name AS HospAdmitType, Reason.Name AS HospReason,\n";
		$sql .= "ds.Description AS DischargeStatus\n";
		$sql .= "FROM qcpAuthorizationDetail AS Detail\n";
		$sql .= "LEFT JOIN qcpProvider Provider ON Provider.Provider = Detail.Provider\n";
		$sql .= "LEFT JOIN qcpSpecialties Specialties ON Specialties.Code = Detail.SpecialtyCode\n";
		$sql .= "LEFT JOIN qcpFacilityType FacilityType ON FacilityType.Description = Detail.FacilityTyp\n";
		$sql .= "LEFT JOIN qcpHospitalAdmissionType AS AdmitType ON AdmitType.Code = Detail.AdmissionType\n";
		$sql .= "LEFT JOIN qcpReasonForHospitalization AS Reason ON Reason.Description = Detail.ReasonForHospitalization\n";
		$sql .= "LEFT JOIN qcpDischargeStatus ds ON ds.DischargeStatus = Detail.DischargeStatus\n";
		$sql .= "WHERE Detail.AuthorizationNumber = ?\n";
		$sql .= " ORDER BY Detail.FacilityCharge DESC, Provider.LastName, Provider.FirstName, Detail.FromDate\n";
		$orDetail = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));

		$sql = "select Member.* from qcpMember inner join qcpAuthorizationHeader as Auth on Auth.MemberID = Member.MBR_SSN_number where Auth.AuthorizationNumber = ?";
		$orMember = $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));
		$orReferring = GetProvider($db->rst($orReferral, "RequestingProviderId"));
	}
}

function GetReferralHeader($RefNum)
{
	global $db;

	$sql = "SELECT r.Payor, r.DateEntered, r.DateApproved, r.ValidDays, r.DateValidFrom, r.DateValidTo, r.Status\n";
	$sql .= ", r.NumberOfVisits, r.ReferralNumber, r.PayorReferralNumber, r.AuthorizedBy, r.MemberID\n";
	$sql .= ", r.DispositionCode, r.ReferringProvider, r.AuthorizationNumber, r.Notes AS refNotes, r.Provider AS ReferralProvider\n";
	$sql .= ", r.UserAdd AS refUserAdd, r.DateTimeAdd AS refDateTimeAdd, r.SpecialtyCode, r.UserNotes\n";
	$sql .= ", r.UserUpdate AS refUserUpdate, r.DateTimeUpdate AS refDateTimeUpdate, r.DateReceived,r.AddressId\n";
	$sql .= ", m.MBR_PCP_number AS MemberPcp, m.SiteNumber AS MemberSiteNumber,m.MBR_last_name AS MemberLastName, m.MBR_first_name AS MemberFirstName, m.MiddleInitial AS MemberMiddleInitial\n";
	$sql .= ", pay.PayorName AS PayorName\n";
	$sql .= ", pprov.Provider,pprov.FirstName AS ProviderFirstName, pprov.LastName AS ProviderLastName, pprov.Participating AS ProviderParticipating\n";
	$sql .= ", rprov.LastName AS ReferringProviderLastName, rprov.FirstName AS ReferringProviderFirstName\n";
	$sql .= ", rprovaddr.Street as ReferringProviderStreet, rprovaddr.Street2 as ReferringProviderStreet2\n";
	$sql .= ", rprovaddr.City as ReferringProviderCity, rprovaddr.State as ReferringProviderState\n";
	$sql .= ", rprovaddr.ZipCode as ReferringProviderZipCode, rprovaddr.Phone as ReferringProviderPhone\n";
	$sql .= ", rprovaddr.Fax as ReferringProviderFax\n";
	$sql .= ", pcp.LastName AS PcpLastName,pcp.FirstName AS PcpFirstName\n";
	$sql .= ", pcpaddr.Street as PcpStreet, pcpaddr.Street2 as PcpStreet2, pcpaddr.City as PcpCity, pcpaddr.State as PcpState\n";
	$sql .= ", pcpaddr.ZipCode as PcpZipCode, pcpaddr.Phone as PcpPhone, pcpaddr.Fax as PcpFax\n";
	$sql .= ", rc.ResponseDescription AS DispositionDescription\n";
	$sql .= "FROM qcpReferral r\n";
	$sql .= "JOIN qcpMember m ON r.MemberID = m.MBR_SSN_number\n";
	$sql .= "JOIN qcpProvider pprov ON r.Provider = pprov.Provider\n";
	$sql .= "JOIN qcpPayor pay ON m.Payor = pay.Payor\n";
	$sql .= "JOIN qcpProvider rprov ON r.ReferringProvider = rprov.Provider\n";
	$sql .= "JOIN qcpProvider pcp ON m.MBR_PCP_number = pcp.Provider\n";
	$sql .= "LEFT OUTER JOIN qcpAddress rprovaddr ON rprovaddr.OwnerId = r.ReferringProvider and rprovaddr.Active = 1 and rprovaddr.Type = 1\n";
	$sql .= "LEFT OUTER JOIN qcpAddress pcpaddr ON pcpaddr.OwnerId = m.MBR_PCP_number and pcpaddr.Active = 1 and pcpaddr.Type = 1\n";
	$sql .= "LEFT OUTER JOIN qcpResponseCodes rc on r.DispositionCode = rc.ResponseCode\n";
	$sql .= "WHERE r.ReferralNumber = ?\n";
	return $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));
}

function GetReferralDetail($RefNum)
{
	global $db;

	$sql = "SELECT rd.*, LTRIM(RTRIM(cpt.CPT_description)) AS ProcedureDescription\n";
	$sql .= ", icd.ICD_9_description AS DiagnosisDescription\n";
	$sql .= ", rp.Description AS ReferredProcedureDescription\n";
	$sql .= ", (SELECT TOP(1) ReferralUnitsTypeDesc FROM qcpReferralUnitTypes WHERE ReferralUnitsType = rd.UnitType) AS UnitTypeDesc\n";
	$sql .= "FROM qcpReferralDetail rd\n";
	$sql .= "JOIN qcpReferral r on r.ReferralNumber = rd.ReferralNumber\n";
	$sql .= "LEFT OUTER JOIN qcpCPT_code cpt ON ltrim(rtrim(rd.ProcedureCode)) = ltrim(rtrim(cpt.CPT_number))\n";
	$sql .= "LEFT OUTER JOIN qcpICD_9_code icd ON ltrim(rtrim(rd.DiagnosisCode)) = ltrim(rtrim(icd.ICD_9_number)) and icd.IcdVersion = isnull(r.IcdVersion, '09') and icd.DiagnosisType = 'D'\n";
	$sql .= "LEFT OUTER JOIN qcpReferredProcedure rp ON ltrim(rtrim(rd.ReferredProcedure)) = ltrim(rtrim(rp.Code))\n";
	$sql .= "WHERE rd.ReferralNumber = ?\n";
	return $db->GetRecords($db->QuickCapDB, $sql, array($RefNum));
}

function GetProvider($ProviderId)
{
	global $db;

	$sql = "select p.*, a.*\n";
	$sql .= "from qcpProvider p\n";
	$sql .= "join qcpAddress a on a.OwnerId = p.Provider and a.Active = 1 and a.Type = 1\n";
	$sql .= "where p.Provider = ?\n";
	return $db->GetRecords($db->QuickCapDB, $sql, array($ProviderId));
}

function Status($Code)
{
	if ($Code == 'A') { return 'Approved'; }
	if ($Code == 'D') { return 'Denied'; }
	if ($Code == 'P') { return 'Pending'; }
	if ($Code == 'H') { return 'On Hold'; }
}

function DispositionDesc($desc, $status)
{
	return ($desc == "" ? $status : $desc);
}

function PCPApproved($Code)
{
	if ($Code == 'Y') { return 'Yes'; }
	if ($Code == 'N') { return 'No'; }
	return 'Unknown';
}

function FormatName($LastName, $FirstName, $Initial, $ProviderID)
{
	global $db;
	$Person = "";

	if ($ProviderID != "") {
		$rs = $db->GetRecords($db->QuickCapDB, "select Person from qcpProvider where Provider = ?", array($ProviderID));
		if (!$rs->EOF) {
			$Person = $db->rst($rs, "Person");
		}
	}

	return libFormatName($LastName, $FirstName, $Initial, '', $Person);
}

function ReferredFor($rfType)
{
	global $db, $orDetail;
	$ReferredFor = "";

	$orDetail->MoveFirst();

	while(!$orDetail->EOF) {
		if ($rfType == 'code') {
			if ($db->rst($orDetail, "ReferredProcedure") != "") {
				$ReferredFor .= $db->rst($orDetail, "ReferredProcedure")."<br/>";
			}
		}
		elseif ($rfType == 'desc') {
			if ($db->rst($orDetail, "ReferredProcedureDescription") != "") {
				$ReferredFor .= $db->rst($orDetail, "ReferredProcedureDescription")."<br/>";
			}
		}

		$orDetail->MoveNext();
	}

	return $ReferredFor;
}

function ICD($ICDType)
{
	global $db, $orDetail;
	
	if ($orDetail->BOF && $orDetail->EOF) {
		return '';
	}

	$ICD = "";
	$orDetail->MoveFirst();

	while(!$orDetail->EOF) {
		if ($ICDType == 'code') {
			if ($db->rst($orDetail, "DiagnosisCode") != "") {
				$ICD .= $db->rst($orDetail, "DiagnosisCode")."<br/>";
			}
		}
		elseif ($ICDType == 'desc') {
			if ($db->rst($orDetail, "DiagnosisDescription") != "") {
				$ICD .= $db->rst($orDetail, "DiagnosisDescription")."<br/>";
			}
		}
		elseif ($ICDType == 'codedesc') {
			if ($db->rst($orDetail, "DiagnosisCode") != "") {
				$ICD .= $db->rst($orDetail, "DiagnosisCode")." - ".$db->rst($orDetail, "DiagnosisDescription") ."<br/>";
			}
		}

		$orDetail->MoveNext();
	}

	return $ICD;
}

function CPT($CPTType)
{
	global $db, $orDetail;

	if ($orDetail->BOF && $orDetail->EOF) {
		return '';
	}

	$CPT = "";
	$orDetail->MoveFirst();

	while(!$orDetail->EOF) {
		if ($CPTType == 'code') {
			if ($db->rst($orDetail, "ProcedureCode") != "") {
				$CPT .= $db->rst($orDetail, "ProcedureCode");
			}
		}
		elseif ($CPTType == 'desc') {
			if ($db->rst($orDetail, "ProcedureDescription") != "") {
				$CPT .= $db->rst($orDetail, "ProcedureDescription")."<br/>";
			}
		}
		elseif ($CPTType == 'codedesc') {
			if ($db->rst($orDetail, "ProcedureCode") != "") {
				$CPT .= $db->rst($orDetail, "ProcedureCode")." - ".$db->rst($orDetail, "ProcedureDescription")."<br/>";
			}
		}

		$orDetail->MoveNext();
	}

	return $CPT;
}

function ShowCPTTemplate()
{
	global $printWidth;
	$cptCode = CPT("code");
	$text = '';

	if (!_isNE($cptCode)) {
		$text = ReadTemplate("reprint_cpt.html");
		$text = str_replace("%%printwidth%%", $printWidth, $text);
		$text = str_replace("%%cpt_code%%", $cptCode, $text);
		$text = str_replace("%%cpt_description%%", CPT("desc"), $text);
	}

	return $text;
}

function ShowICDTemplate()
{
	global $printWidth;
	$icd9Code = ICD("code");
	$text = '';

	if (_isNE($icd9Code)) {
		$text = ReadTemplate("reprint_icd9.html");
		$text = str_replace("%%printwidth%%", $printWidth, $text);
		$text = str_replace("%%icd9_code%%", $icd9Code, $text);
		$text = str_replace("%%icd9_description%%", ICD("desc"), $text);
	}

	return $text;
}

function ShowReferredProcedureTemplate()
{
	global $printWidth;
	$procCode = ReferredFor("code");
	$text = '';

	if (!_isNE($procCode)) {
		$text = ReadTemplate("reprint_referred_procedure.html");
		$text = str_replace("%%printwidth%%", $printWidth, $text);
		$text = str_replace("%%referred_procedure_code%%", $procCode, $text);
		$text = str_replace("%%referred_procedure_description%%", ReferredFor("desc"), $text);
	}

	return $text;
}

function ShowDisclaimer()
{
	global $isError, $printWidth, $client;
	$disclaimer = '';

	if (!$isError) {
		$disclaimer = ReadTemplate(strtolower($client).'-disclaimer.html');
		if ($disclaimer == '') {
			$disclaimer = ReadTemplate("disclaimer.html");
		}
		$disclaimer = str_replace("%%printwidth%%", $printWidth, $disclaimer);
	}

	return $disclaimer;
}

function ShowAuthReferredToProvName()
{
	global $db, $orDetail;

	$name = "";

	$orDetail->MoveFirst();
	while(!$orDetail->EOF) {
		$name .= FormatName($db->rst($orDetail, "LastName"), $db->rst($orDetail, "FirstName"), "", $db->rst($orDetail, "Provider"), $db->rst($orDetail, "Person"))."<br/>";
		$orDetail->MoveNext();
	}

	return $name;
}

function ShowAuthDetails()
{
	global $db, $orDetail;
	$sectionOrig = ReadTemplate("auth_reprint_detail.html");
	$sections = array();

	$LastProv = "zzz";
	$orDetail->MoveFirst();
	while(!$orDetail->EOF) {
		if ($LastProv != $db->rst($orDetail, "Provider")) {
			$LineType = $db->rst($orDetail, "LineType");
			$section = $sectionOrig;

			$section = str_replace("%%prov_fullname%%", FormatName($db->rst($orDetail, "LastName"), $db->rst($orDetail, "FirstName"), "", $db->rst($orDetail, "Provider")), $section);
			$section = str_replace("%%prov_specialty%%", $db->rst($orDetail, "Specialty"), $section);
			$section = str_replace("%%auth_service%%", $db->rst($orDetail, "Service"), $section);
			$section = str_replace("%%from_date%%", $db->rst($orDetail, "FromDate"), $section);
			$section = str_replace("%%to_date%%", $db->rst($orDetail, "ToDate"), $section);
			$section = str_replace("%%discharge_status%%", $db->rst($orDetail, "DischargeStatus"), $section);

			$section = str_replace("%%iffac%%", ($LineType == "F" ? "" : "<!--"), $section);
			$section = str_replace("%%endiffac%%", ($LineType == "F" ? "" : "-->"), $section);
			$section = str_replace("%%ifprov%%", ($LineType == "P" ? "" : "<!--"), $section);
			$section = str_replace("%%endifprov%%", ($LineType == "P" ? "" : "-->"), $section);
			$section = str_replace("%%ifvisits%%", ($db->rst($orDetail, "Visits") != "" ? "" : "<!--"), $section);
			$section = str_replace("%%endifvisits%%", ($db->rst($orDetail, "Visits") != "" ?  "" : "-->"), $section);

			$section = str_replace("%%visits_label%%", ($db->rst($orDetail, "Visits") != "" ? "Visits:" : ""), $section);
			$section = str_replace("%%visits%%", $db->rst($orDetail, "Visits"), $section);
			$section = str_replace("%%note%%", $db->rst($orDetail, "Note"), $section);
			$section = str_replace("%%admit_type%%", $db->rst($orDetail, "HospAdmitType"), $section);
			$section = str_replace("%%admit_time%%", $db->rst($orDetail, "AdmissionTime"), $section);
			$section = str_replace("%%room_label%%", ($db->rst($orDetail, "RoomNumber") != "" ? "Room:" : ""), $section);
			$section = str_replace("%%room_number%%", $db->rst($orDetail, "RoomNumber"), $section);
			$section = str_replace("%%facility_type%%", $db->rst($orDetail, "FacType"), $section);
			$section = str_replace("%%hosp_reason%%", $db->rst($orDetail, "HospReason"), $section);

			$sections[] = $section;
		}

		$LastProv = $db->rst($orDetail, "Provider");
		$orDetail->MoveNext();
	}

	return implode(''. $sections);
}

function ShowOtherInsurance()
{
	global $db, $printWidth, $orMember;

	$sql = "select * from qcpOtherInsurance where OtherInsuranceMemberId = ?";
	$orOtherIns = $db->GetRecords($db->QuickCapDB, $sql, array($db->rst($orMember, "MBR_SSN_number")));

	if (!$orOtherIns->EOF) {
		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="100"><strong>Other Insurance:</strong></td>';
		echo '<td valign="top" width="0*">';
		echo $db->rst($orOtherIns, "OtherInsuranceName");

		if (strtoupper($db->rst($orMember, "PrimaryInsurance")) != "true") {
			echo '&nbsp;&nbsp;&nbsp;&nbsp;(PRIMARY)';
		}

		echo '<br/>';
		if ($db->rst($orOtherIns, "OtherInsurancePhone") != "") {
			echo 'Phone: '.libFormatPhone($db->rst($orOtherIns, "OtherInsurancePhone")).'<br/>';
		}
		if ($db->rst($orOtherIns, "OtherInsurancePlanNumber") != "") {
		echo 'Plan Number: '.$db->rst($orOtherIns, "OtherInsurancePlanNumber").'<br/>';
		}
		if ($db->rst($orOtherIns, "OtherInsuranceGroupNumber") != "") {
		echo 'Group Number: '.$db->rst($orOtherIns, "OtherInsuranceGroupNumber").'<br/>';
		}
		echo '</td></tr></table><br/>';
	}
}

function HandleGracePeriod()
{
	global $oCommon, $db, $orReferral, $orMember;

	if ($db->rst($orMember, 'IsReceiveAPTC') != 1) {
		return '';
	}

	if ($db->rst($orMember, 'GracePeriodEffectiveDate') != '') {
		$effDate = $db->rst($orMember, 'GracePeriodEffectiveDate');
		$numMonths = Dates::dateTimeDiff($db->rst($orReferral, 'DateValidTo'), $effDate, 'month');

		// Check for referrals that extend beyond the first month of the grace period
		if (abs($numMonths) > 0) {
			if ((Dates::month($effDate)) == date('m') && Dates::year(($effDate) == date('Y'))) {
				return $oCommon->getConfig('GRACE PERIOD', 'REF MONTH 1');
			}
			else {
				return $oCommon->getConfig('GRACE PERIOD', 'MANDATED');
			}
		}
	}

	return '';
}

function DoReferral()
{
	global $oCommon, $db, $client, $printWidth, $UseSpecialAddress, $Heading, $isError, $ReprintType;
	global $orReferral, $orDetail, $orMember, $orReferredTo, $orAddress;

	$template = ReadTemplate(strtolower($client)."-referral_reprint.html");

	if ($template != "") {
		$template = str_replace("%%company_name%%", $oCommon->getDBName(), $template);
		$template = str_replace("%%printwidth%%", $printWidth, $template);
		$template = str_replace("%%ref_num%%", $db->rst($orReferral, "ReferralNumber"), $template);
		$template = str_replace("%%auth_num%%", ($db->rst($orReferral, "AuthorizationNumber") != "" ? "Authorization: ".$db->rst($orReferral, "AuthorizationNumber") : ""), $template);
		$template = str_replace("%%referral_num%%", $db->rst($orReferral, "ReferralNumber"), $template);
		$template = str_replace("%%date_entered%%", libFormatDateTime($db->rst($orReferral, "DateEntered"), true), $template);
		$template = str_replace("%%date_last_update%%", libFormatDateTime($db->rst($orReferral, "DateTimeUpdate"), true), $template);
		$template = str_replace("%%date_valid_from%%", libFormatDate($db->rst($orReferral, "DateValidFrom")), $template);
		$template = str_replace("%%date_valid_to%%", libFormatDate($db->rst($orReferral, "DateValidTo")), $template);
		$template = str_replace("%%date_approved%%", libFormatDateTime($db->rst($orReferral, "DateApproved"), true), $template);
		$template = str_replace("%%payor_name%%", $db->rst($orReferral, "PayorName"), $template);
		$template = str_replace("%%authorized_by%%", $db->rst($orReferral, "AuthorizedBy"), $template);
		$template = str_replace("%%reviewed_by%%", $db->rst($orReferral, "AuthorizedBy"), $template);
		$template = str_replace("%%ref_status%%", strtoupper(Status($db->rst($orReferral, "Status"))), $template);

		$template = str_replace("%%member_id%%", $db->rst($orReferral, "MemberID"), $template);
		$template = str_replace("%%member_first%%", $db->rst($orReferral, "MemberFirstName"), $template);
		$template = str_replace("%%member_last%%", $db->rst($orReferral, "MemberLastName"), $template);
		$template = str_replace("%%member_initial%%", $db->rst($orReferral, "MemberMiddleInitial"), $template);
		$template = str_replace("%%member_dob%%", libFormatDate($db->rst($orMember, "BirthDate")), $template);
		$template = str_replace("%%member_age%%", getAge($db->rst($orMember, 'BirthDate')), $template);
		$template = str_replace("%%member_street1%%", $db->rst($orMember, "Street"), $template);
		$template = str_replace("%%member_street2%%", $db->rst($orMember, "Street2"), $template);
		$template = str_replace("%%member_city%%", $db->rst($orMember, "City"), $template);
		$template = str_replace("%%member_state%%", $db->rst($orMember, "State"), $template);
		$template = str_replace("%%member_zip%%", $db->rst($orMember, "Zip"), $template);
		$template = str_replace("%%member_phone%%", libFormatPhone($db->rst($orMember, "Phone")), $template);
		$template = str_replace("%%member_ov_copay%%", $db->rst($orMember, "OfficeCopayAmount"), $template);
		$template = str_replace("%%member_er_copay%%", $db->rst($orMember, "ERCopayAmount"), $template);
		$template = str_replace("%%member_mh_copay%%", $db->rst($orMember, "MentalHealthCopayAmount"), $template);
		$template = str_replace("%%member_spec_copay%%", $db->rst($orMember, "SpecialistCopayAmount"), $template);
		$template = str_replace("%%member_pt_copay%%", $db->rst($orMember, "PTCopayAmount"), $template);
		$template = str_replace("%%member_effective_date%%", libFormatDate($db->rst($orMember, "ServiceEffectiveDate")), $template);

		$phoneAndFax = GetPhoneAndFax($db->rst($orReferral, "MemberPcp"));
		$template = str_replace("%%pcp_id%%", $db->rst($orReferral, "MemberPcp"), $template);
		$template = str_replace("%%pcp_fullname%%", FormatName($db->rst($orReferral, "PcpLastName"), $db->rst($orReferral, "PcpFirstName"), "", $db->rst($orReferral, "MemberPcp")), $template);
		$template = str_replace("%%pcp_phone%%", $phoneAndFax->Phone, $template);
		$template = str_replace("%%pcp_fax%%", $phoneAndFax->Fax, $template);
		$template = str_replace('%%pcp_address%%', trim($db->rst($orReferral, 'PcpStreet').' '.$db->rst($orReferral, 'PcpStreet2')), $template);
		$template = str_replace('%%pcp_street1%%', $db->rst($orReferral, 'PcpStreet'), $template);
		$template = str_replace('%%pcp_street2%%', $db->rst($orReferral, 'PcpStreet2'), $template);
		$template = str_replace('%%pcp_city%%', $db->rst($orReferral, 'PcpCity'), $template);
		$template = str_replace('%%pcp_state%%', $db->rst($orReferral, 'PcpState'), $template);
		$template = str_replace('%%pcp_zip%%', $db->rst($orReferral, 'PcpZipCode'), $template);

		$template = str_replace("%%referring_provider_id%%", $db->rst($orReferral, "ReferringProvider"), $template);
		$template = str_replace("%%referring_fullname%%", FormatName($db->rst($orReferral, "ReferringProviderLastName"), $db->rst($orReferral, "ReferringProviderFirstName"), "", $db->rst($orReferral, "ReferringProvider")), $template);
		$template = str_replace("%%referring_address%%", $db->rst($orReferral, "ReferringProviderStreet")." ".$db->rst($orReferral, "ReferringProviderStreet2"), $template);
		$template = str_replace("%%referring_street1%%", $db->rst($orReferral, "ReferringProviderStreet"), $template);
		$template = str_replace("%%referring_street2%%", $db->rst($orReferral, "ReferringProviderStreet2"), $template);
		$template = str_replace("%%referring_city%%", $db->rst($orReferral, "ReferringProviderCity"), $template);
		$template = str_replace("%%referring_state%%", $db->rst($orReferral, "ReferringProviderState"), $template);
		$template = str_replace("%%referring_zip%%", $db->rst($orReferral, "ReferringProviderZipCode"), $template);
		$template = str_replace("%%referring_phone%%", libFormatPhone($db->rst($orReferral, "ReferringProviderPhone")), $template);
		$template = str_replace("%%referring_fax%%", libFormatPhone($db->rst($orReferral, "ReferringProviderFax")), $template);

		$phoneAndFax = GetPhoneAndFax($db->rst($orReferredTo, "Provider"));
		$template = str_replace("%%referred_to_provider_id%%", $db->rst($orReferral, "ReferralProvider"), $template);
		$template = str_replace("%%referred_to_specialty%%", GetSpecialty($db->rst($orReferral, "SpecialtyCode")), $template);
		$template = str_replace("%%referred_to_fullname%%", FormatName($db->rst($orReferral, "ProviderLastName"), $db->rst($orReferral, "ProviderFirstName"), "", $db->rst($orReferral, "ReferralProvider")), $template);
		$template = str_replace("%%referred_to_address%%", ($UseSpecialAddress ? $db->rst($orAddress, "Street")." ".$db->rst($orAddress, "Street2") : $db->rst($orReferredTo, "Street")." ".$db->rst($orReferredTo, "Street2")), $template);
		$template = str_replace("%%referred_to_street1%%", ($UseSpecialAddress ? $db->rst($orAddress, "Street") : $db->rst($orReferredTo, "Street")), $template);
		$template = str_replace("%%referred_to_street2%%", ($UseSpecialAddress ? $db->rst($orAddress, "Street2") : $db->rst($orReferredTo, "Street2")), $template);
		$template = str_replace("%%referred_to_city%%",($UseSpecialAddress ? $db->rst($orAddress, "City") :  $db->rst($orReferredTo, "City")), $template);
		$template = str_replace("%%referred_to_state%%", ($UseSpecialAddress ? $db->rst($orAddress, "State") : $db->rst($orReferredTo, "State")), $template);
		$template = str_replace("%%referred_to_zip%%", ($UseSpecialAddress ? $db->rst($orAddress, "ZipCode") : $db->rst($orReferredTo, "ZipCode")), $template);
		$template = str_replace("%%referred_to_phone%%", $phoneAndFax->Phone, $template);
		$template = str_replace("%%referred_to_fax%%", $phoneAndFax->Fax, $template);

		$template = str_replace("%%num_visits%%", $db->rst($orReferral, "NumberOfVisits"), $template);
		$template = str_replace("%%icd9_code%%", ICD("code"), $template);
		$template = str_replace("%%icd9_description%%", ICD("desc"), $template);
		$template = str_replace("%%icd_code_and_description%%", ICD("codedesc"), $template);
		$template = str_replace("%%cpt_code%%", CPT("code"), $template);
		$template = str_replace("%%cpt_description%%", CPT("desc"), $template);
		$template = str_replace("%%cpt_code_and_description%%", CPT("codedesc"), $template);
		$template = str_replace("%%referred_for%%", ReferredFor("desc"), $template);

		$template = str_replace("%%notes%%", NotesHTML($db->rst($orReferral, "refNotes")), $template);
		$template = str_replace("%%disposition_code%%", $db->rst($orReferral, "DispositionCode"), $template);
		$template = str_replace("%%disposition_description%%", DispositionDesc($db->rst($orReferral, "DispositionDescription"), strtoupper(Status($db->rst($orReferral, "Status")))), $template);

		$template = str_replace("%%if_cpt_code_include_cpt_$template%%", ShowCPTTemplate(), $template);
		$template = str_replace("%%if_icd9_code_include_icd9_$template%%", ShowICDTemplate(), $template);
		$template = str_replace("%%if_referred_procedure_include_referred_procedure_$template%%", ShowReferredProcedureTemplate(), $template);

		$template = str_replace("%%disclaimer%%", ShowDisclaimer(), $template);
		$template = str_replace("%%md_notes%%", "", $template);
		$template = str_replace('%%employer_group_number%%', $db->rst($orMember, 'EmployerGroupNumber'), $template);
		$template = str_replace('%%grace_period%%', HandleGracePeriod(), $template);
		$template = str_replace('%%date_mailed%%', ShowDateMailed(), $template);

        // 10/09/2025 JLC
        $template = str_replace(
            '%%signature%%',
            trim($db->rst($orReferral, 'ReferringProviderFirstName').' '.$db->rst($orReferral, 'ReferringProviderLastName').' '.$db->rst($orReferral, 'ReferringProviderDegree')),
            $template
        );

		printf('%s', $template);
		return;
	}

	echo '<div id="wrapReprint">';

	echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr><td valign="top" width="100%" align="center">';
	echo '<strong><span class="pagetitle">'.$Heading.'</span><br/>'.$ReprintType.'</strong></td></tr></table><br/>';

	if (!$orReferral->EOF) {
		$RefStatus = Status($db->rst($orReferral, "Status"));
		echo '<table width='.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="40%">';
		echo '<strong>Referral: '.$db->rst($orReferral, "ReferralNumber").'</strong></td>';
		echo '</tr></table><br/>';

		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="20%">';
		echo 'Status: <strong>'.strtoupper($RefStatus).'</strong></td>';
		echo '<td valign="top" width="20%">';
		echo 'Number of Visits: '.$db->rst($orReferral, "NumberOfVisits").'</td>';
		echo '<td valign="top" width="30%">';
		echo 'Service Start Date: '.libFormatDate($db->rst($orReferral, "DateValidFrom")).'</td>';
		echo '<td valign="top" width="30%">';
		echo 'Service End Date: '.libFormatDate($db->rst($orReferral, "DateValidTo")).'</td></tr></table><br/>';

		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="60%">';

		// 09/09/07 JLC Use AuthorizedBy instead of refUserUpdate per Davor
		// 02/26/2025 JLC Disabled per Dara and Tracy
		//echo 'Reviewed By: '.$db->rst($orReferral, "AuthorizedBy").'</td>';
		echo '</td>';
		echo '<td valign="top" width="40%">';
		echo 'Date: '.libFormatDateTime($db->rst($orReferral, "DateEntered"), true).'</td></tr></table>';

		echo '<table width="'.$printWidth.'" border="1" cellspacing="0" cellpadding="2" ';
		echo 'style=""border:solid 1px black"">';

		echo '<tr><td valign="top" width="90">Payor</td>';
		echo '<td valign=""middle"" colspan="3" width="0*">'.$db->rst($orReferral, "PayorName").'</td>';
		echo '<td valign=""middle"" align="center">&nbsp;</td></tr>';

		echo '<tr><td valign="top" width="90">Member ID</td>';
		echo '<td valign="top" width="110">'.$db->rst($orReferral, "MemberID").'</td>';
		echo '<td valign="top" align="left" width="0*" colspan="2">';
		echo FormatName($db->rst($orReferral, "MemberLastName"), $db->rst($orReferral, "MemberFirstName"), $db->rst($orReferral, "MemberMiddleInitial"), "").'<br/>';
		echo $db->rst($orMember, "Street").'<br/>';

		if ($db->rst($orMember, "Street2") != "") {
			echo $db->rst($orMember, "Street2").'<br/>';
		}

		echo $db->rst($orMember, "City").', '.$db->rst($orMember, "State").' '.$db->rst($orMember, "Zip").'</td>';
		echo '<td valign="bottom" align="left" width="0*">Phone: '.libFormatPhone($db->rst($orMember, "Phone")).'</td></tr>';

		echo '<tr><td valign="top" width="90">Date of Birth</td>';
		echo '<td valign="top" width="110">'.libFormatDate($db->rst($orMember, "BirthDate")).'</td>';
		echo '<td valign="top" width="65">Age: '.getAge($db->rst($orMember, "BirthDate")).'</td>';
		echo '<td valign="top" width="0*">Effective Date: '.libFormatDate($db->rst($orMember, "ServiceEffectiveDate")).'</td>';
		echo '<td valign="top" width="160" rowspan="3">';
		echo '<table width="100%" border="0" cellspacing="0" cellpadding="2">';
		echo '<tr><td valign="top">OV Copay</td>';
		echo '<td valign="top" align="right">'.$oCommon->myFormatMoney($db->rst($orMember, "OfficeCopayAmount")).'</td></tr>';
		echo '<tr><td valign="top">ER Copay</td>';
		echo '<td valign="top" align="right">'.$oCommon->myFormatMoney($db->rst($orMember, "ERCopayAmount")).'</td></tr>';
		echo '<tr><td valign="top">MH Copay</td>';
		echo '<td valign="top" align="right">'.$oCommon->myFormatMoney($db->rst($orMember, "MentalHealthCopayAmount")).'</td></tr>';
		echo '<tr><td valign="top">Specialist Copay</td>';
		echo '<td valign="top" align="right">'.$oCommon->myFormatMoney($db->rst($orMember, "SpecialistCopayAmount")).'</td></tr>';
		echo '</table></td></tr>';

		echo '<tr><td valign="top" width="90">PCP ID</td>';
		//echo '<td valign="top" width="110">'.$db->rst($orReferral, "MemberPcp").'</td>';
		echo '<td valign="top" width="0*" colspan="3">';
		echo FormatName($db->rst($orReferral, "PcpLastName"), $db->rst($orReferral, "PcpFirstName"), "", $db->rst($orReferral, "MemberPcp")).'<br/>';
		$phoneAndFax = GetPhoneAndFax($db->rst($orReferral, "MemberPcp"));
		echo 'Phone: '.$phoneAndFax->Phone."<br/>Fax: ".$phoneAndFax->Fax;
		echo '</td></tr>';

		echo '<tr><td valign="top" width="90">Referred By</td>';
		//echo '<td valign="top" width="110">'.$db->rst($orReferral, "ReferringProvider").'</td>';
		echo '<td valign="top" width="0*" colspan="3">';
		echo FormatName($db->rst($orReferral, "ReferringProviderLastName"), $db->rst($orReferral, "ReferringProviderFirstName"), "", $db->rst($orReferral, "ReferringProvider")).'</td></tr>';

		echo '<tr><td valign="top" width="90">Referred To</td>';
		//echo '<td valign="top" width="110">'.$db->rst($orReferral, "ReferralProvider").'</td>';
		echo '<td valign="top" width="0*" colspan="3">';
		echo FormatName($db->rst($orReferral, "ProviderLastName"), $db->rst($orReferral, "ProviderFirstName"), "", $db->rst($orReferral, "ReferralProvider")).' '.$db->rst($orReferredTo, "Degree").'<br/>';

		// 01/03/08 JLC
		if ($UseSpecialAddress) {
			echo $db->rst($orAddress, "Street").'<br/>';

			if ($db->rst($orAddress, "Street2") != "") {
				echo $db->rst($orAddress, "Street2").'<br/>';
			}

			echo $db->rst($orAddress, "City").", ".$db->rst($orAddress, "State")." ".$db->rst($orReferredTo, "ZipCode").'</td>';
		}
		else {
			echo $db->rst($orReferredTo, "Street").'<br/>';

			if ($db->rst($orReferredTo, "Street2") != "") {
				echo $db->rst($orReferredTo, "Street2").'<br/>';
			}

			echo $db->rst($orReferredTo, "City").", ".$db->rst($orReferredTo, "State")." ".$db->rst($orReferredTo, "ZipCode").'</td>';
		}

		echo '<td valign="bottom" align="left" width="0*">';
		$phoneAndFax = GetPhoneAndFax($db->rst($orReferredTo, "Provider"));
		echo 'Phone: '.libFormatPhone($phoneAndFax->Phone).'<br/>';
		echo 'Fax: '.libFormatPhone($phoneAndFax->Fax).'</td></tr>';

		echo "</table>\n";

		$ReferredForPrinted = false;
		while(!$orDetail->EOF) {
			echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
			if ($db->rst($orDetail, "DiagnosisCode") != "") {
				echo '<tr><td valign="top" width="100"><strong>Diagnosis:</strong></td>';
				echo '<td valign="top" width="80">'.$db->rst($orDetail, "DiagnosisCode").'</td>';
				echo '<td valign="top" width="0*"><em>'.$db->rst($orDetail, "DiagnosisDescription").'</em></td></tr></table><br/>';
			}

			if ($db->rst($orDetail, "ProcedureCode") != "") {
				echo '<tr><td valign="top" width="100">';

				if (!$ReferredForPrinted) {
					echo '<strong>Referred For:</strong>';
					$ReferredForPrinted = true;
				}
				else {
					echo '&nbsp;';
				}

				echo '</td>';

				echo '<td valign="top" width="80">'.$db->rst($orDetail, "ProcedureCode").'</td>';
				echo '<td valign="top" width="0*"><em>'.$db->rst($orDetail, "ProcedureDescription").'</em></td></tr></table><br/>';
			}

			if ($db->rst($orDetail, "Code") != "") {
				echo '<tr><td valign="top" width="100">';

				if (!$ReferredForPrinted) {
					echo '<strong>Referred For:</strong>';
					$ReferredForPrinted = true;
				}
				else {
					echo '&nbsp;';
				}

				echo '</td>';
				//echo '<td valign="top" width="80"><em>'.$db->rst($orDetail, "Code").'</em></td>';
				//echo '<td valign="top" width="80">&nbsp;</td>';
				echo '<td valign="top" width="0*" colspan="2"><em>'.$db->rst($orDetail, "ReferredProcedureDescription").'</em></td></tr></table><br/>';
			}

			$orDetail->MoveNext();
		}

		$Note = NotesHTML($db->rst($orReferral, "refNotes"));
		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="45"><strong>Note:</strong></td>';
		echo '<td valign="top" width="0*">'.$Note.'</td></tr></table>';

		$isError = false;
	}
	else {
		$isError = true;
		//echo $db->QuickCapDB.' - '.$isError;
	}

	echo ShowDisclaimer();
}

function DoAuthorization()
{
	global $oCommon, $db, $client, $printWidth, $Heading, $ReprintType;
	global $orReferral, $orDetail, $orMember, $orReferring, $orReferredTo;

	$template = ReadTemplate(strtolower($client)."-auth_reprint.html");

	if ($template != "") {
		$template = str_replace("%%company_name%%", $oCommon->getDBName(), $template);
		$template = str_replace("%%printwidth%%", $printWidth, $template);
		$template = str_replace("%%ref_num%%", $db->rst($orReferral, "AuthorizationNumber"), $template);
		$template = str_replace("%%date_entered%%", libFormatDateTime($db->rst($orReferral, "DateTimeAdd"), true), $template);
		$template = str_replace("%%date_valid_from%%", libFormatDate($db->rst($orReferral, "DateValidFrom")), $template);
		$template = str_replace("%%date_valid_to%%", libFormatDate($db->rst($orReferral, "DateValidTo")), $template);
		$template = str_replace("%%date_approved%%", libFormatDateTime($db->rst($orReferral, "DateApproved"), true), $template);
		$template = str_replace("%%payor_name%%", $db->rst($orReferral, "PayorName"), $template);
		$template = str_replace("%%authorized_by%%", $db->rst($orReferral, "AuthorizedBy"), $template);
		$template = str_replace("%%reviewed_by%%", $db->rst($orReferral, "AuthorizedBy"), $template);
		$template = str_replace("%%ref_status%%", strtoupper(Status($db->rst($orReferral, "Status"))), $template);
		$template = str_replace("%%status%%", strtoupper(Status($db->rst($orReferral, "Status"))), $template);

		$template = str_replace("%%hmo_authorization%%", $db->rst($orReferral, "HmoAuthorizationNumber"), $template);
		$template = str_replace("%%hmo_authorized_days%%", iif(intval($db->rst($orReferral, "HmoAuthorizedDays")) > 0, $db->rst($orReferral, "HmoAuthorizedDays"), "&nbsp;"), $template);

		$template = str_replace("%%elos%%", iif(intval($db->rst($orReferral, "ELOS")) > 0, $db->rst($orReferral, "ELOS"), "&nbsp;"), $template);

		$template = str_replace("%%member_id%%", $db->rst($orReferral, "MemberID"), $template);
		$template = str_replace("%%member_first%%", $db->rst($orReferral, "MemberFirstName"), $template);
		$template = str_replace("%%member_last%%", $db->rst($orReferral, "MemberLastName"), $template);
		$template = str_replace("%%member_initial%%", $db->rst($orReferral, "MemberMiddleInitial"), $template);
		$template = str_replace("%%member_fullname%%", FormatName($db->rst($orReferral, "MemberLastName"), $db->rst($orReferral, "MemberFirstName"), $db->rst($orReferral, "MemberMiddleInitial"), 0), $template);
		$template = str_replace("%%member_dob%%", libFormatDate($db->rst($orMember, "BirthDate")), $template);
		$template = str_replace("%%member_age%%", getAge($db->rst($orMember, "BirthDate")), $template);
		$template = str_replace("%%member_street1%%", $db->rst($orMember, "Street"), $template);
		$template = str_replace("%%member_street2%%", $db->rst($orMember, "Street2"), $template);
		$template = str_replace("%%member_city%%", $db->rst($orMember, "City"), $template);
		$template = str_replace("%%member_state%%", $db->rst($orMember, "State"), $template);
		$template = str_replace("%%member_zip%%", $db->rst($orMember, "Zip"), $template);
		$template = str_replace("%%member_phone%%", libFormatPhone($db->rst($orMember, "Phone")), $template);
		$template = str_replace("%%member_ov_copay%%", libFormatPhone($db->rst($orMember, "MBR_copayment")), $template);
		$template = str_replace("%%member_er_copay%%", libFormatPhone($db->rst($orMember, "EmergencyRoomAmount")), $template);
		$template = str_replace("%%member_mh_copay%%", libFormatPhone($db->rst($orMember, "MentalHealthCopay")), $template);
		$template = str_replace("%%member_spec_copay%%", libFormatPhone($db->rst($orMember, "SpecialistCopay")), $template);
		$template = str_replace("%%member_effective_date%%", $db->rst($orMember, "ServiceEffectiveDate"), $template);

		$phoneAndFax = GetPhoneAndFax($db->rst($orReferral, "MemberPcp"));
		$template = str_replace("%%pcp_id%%", $db->rst($orReferral, "MemberPcp"), $template);
		$template = str_replace("%%pcp_fullname%%", FormatName($db->rst($orReferral, "PcpLastName"), $db->rst($orReferral, "PcpFirstName"), "", $db->rst($orReferral, "MemberPcp")), $template);
		$template = str_replace("%%pcp_phone%%", $phoneAndFax->Phone, $template);
		$template = str_replace("%%pcp_fax%%", $phoneAndFax->Fax, $template);
		$template = str_replace('%%pcp_address%%', trim($db->rst($orReferral, 'PcpStreet').' '.$db->rst($orReferral, 'PcpStreet2')), $template);
		$template = str_replace('%%pcp_street1%%', $db->rst($orReferral, 'PcpStreet'), $template);
		$template = str_replace('%%pcp_street2%%', $db->rst($orReferral, 'PcpStreet2'), $template);
		$template = str_replace('%%pcp_city%%', $db->rst($orReferral, 'PcpCity'), $template);
		$template = str_replace('%%pcp_state%%', $db->rst($orReferral, 'PcpState'), $template);
		$template = str_replace('%%pcp_zip%%', $db->rst($orReferral, 'PcpZipCode'), $template);
		$template = str_replace("%%pcp_approved%%", PCPApproved($db->rst($orReferral, "PcpApproved")), $template);

		$phoneAndFax = GetPhoneAndFax($db->rst($orReferral, "ReferringProvider"));
		$template = str_replace("%%referring_provider_id%%", $db->rst($orReferral, "ReferringProvider"), $template);
		$template = str_replace("%%referring_fullname%%", FormatName($db->rst($orReferral, "ReferringProviderLastName"), $db->rst($orReferral, "ReferringProviderFirstName"), "", $db->rst($orReferral, "ReferringProvider")), $template);
		$template = str_replace("%%referring_address%%", $db->rst($orReferring, "Street")." ".$db->rst($orReferring, "Street2"), $template);
		$template = str_replace("%%referring_street1%%", $db->rst($orReferring, "Street"), $template);
		$template = str_replace("%%referring_street2%%", $db->rst($orReferring, "Street2"), $template);
		$template = str_replace("%%referring_city%%", $db->rst($orReferring, "City"), $template);
		$template = str_replace("%%referring_state%%", $db->rst($orReferring, "State"), $template);
		$template = str_replace("%%referring_zip%%", $db->rst($orReferring, "ZipCode"), $template);
		$template = str_replace("%%referring_phone%%",  $phoneAndFax->Phone, $template);
		$template = str_replace("%%referring_fax%%", $phoneAndFax->Fax, $template);

		$phoneAndFax = GetPhoneAndFax($db->rst($orReferredTo, "Provider"));
		$template = str_replace("%%referred_to_provider_id%%", $db->rst($orReferral, "ReferralProvider"), $template);
		$template = str_replace("%%referred_to_fullname%%", ShowAuthReferredToProvName(), $template);
		$template = str_replace("%%referred_to_address%%", $db->rst($orReferredTo, "Street")." ".$db->rst($orReferredTo, "Street2"), $template);
		$template = str_replace("%%referred_to_street1%%", $db->rst($orReferredTo, "Street"), $template);
		$template = str_replace("%%referred_to_street2%%", $db->rst($orReferredTo, "Street2"), $template);
		$template = str_replace("%%referred_to_city%%", $db->rst($orReferredTo, "City"), $template);
		$template = str_replace("%%referred_to_state%%", $db->rst($orReferredTo, "State"), $template);
		$template = str_replace("%%referred_to_zip%%", $db->rst($orReferredTo, "ZipCode"), $template);
		$template = str_replace("%%referred_to_phone%%", $phoneAndFax->Phone, $template);
		$template = str_replace("%%referred_to_fax%%", $phoneAndFax->Fax, $template);

		$template = str_replace("%%num_visits%%", $db->rst($orDetail, "Visits"), $template);
		$template = str_replace("%%icd9_code%%", ICD("code"), $template);
		$template = str_replace("%%icd9_description%%", ICD("desc"), $template);
		$template = str_replace("%%cpt_code%%", CPT("code"), $template);
		$template = str_replace("%%cpt_description%%", CPT("desc"), $template);
		$template = str_replace("%%referred_for%%", ReferredFor("desc"), $template);

		$template = str_replace("%%notes%%", NotesHTML($db->rst($orReferral, "refNotes")), $template);
		$template = str_replace("%%disposition_code%%", $db->rst($orReferral, "DispositionCode"), $template);
		$template = str_replace("%%disposition_description%%", $db->rst($orReferral, "DispositionDescription"), $template);

		$template = str_replace("%%caller%%", $db->rst($orReferral, "CallerName"), $template);
		$template = str_replace("%%caller_from%%", $db->rst($orReferral, "CallerFrom"), $template);
		$template = str_replace("%%caller_phone%%", $db->rst($orReferral, "CallerPhone"), $template);
		$template = str_replace("%%third_party_liability%%", $db->rst($orReferral, "ThirdPartyLiability"), $template);
		$template = str_replace("%%workmans_comp%%", $db->rst($orReferral, "WorkmansComp"), $template);
		$template = str_replace("%%other_insurance%%", $db->rst($orReferral, "OtherInsurance"), $template);
		$template = str_replace("%%other_insurance_name%%", $db->rst($orReferral, "OtherInsuranceName"), $template);
		$template = str_replace("%%preliminary_diagnosis%%", $db->rst($orReferral, "Diagnosis"), $template);
		$template = str_replace("%%payment_note%%", $db->rst($orReferral, "PaymentNote"), $template);

		$template = str_replace("%%disclaimer%%", ShowDisclaimer(), $template);
		$template = str_replace('%%employer_group_number%%', $db->rst($orMember, 'EmployerGroupNumber'), $template);
		$template = str_replace('%%grace_period%%', HandleGracePeriod(), $template);

		$template = str_replace("%%auth_details%%", ShowAuthDetails(), $template);

		echo $template;
		return;
	}

	echo '<div id="wrapReprint">';

	echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr><td valign="top" width="100%" align="center">';
	echo '<strong><span class="pagetitle">'.$Heading.'</span><br/>'.$ReprintType.'</strong></td></tr></table><br/>';

	if (!$orReferral->EOF) {
		$RefStatus = Status($db->rst($orReferral, "Status"));

		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr><td valign="top" width="40%">';
		echo 'Authorization Number: <strong>'.$db->rst($orReferral, "AuthorizationNumber").'</strong>';
		echo '<td valign="top" width="20%" align="center">';
		echo 'Status: <strong>'.strtoupper($RefStatus).'</strong></td>';
		echo '<td valign="top" width="40%">';
		echo 'By: <strong>'.$db->rst($orReferral, "AuthorizedBy").'&nbsp;&nbsp;&nbsp;&nbsp;';
		echo $db->rst($orReferral, "UserID").'</strong></td></tr></table>';

		echo '<br/><br/>';

		echo '<table width="'.$printWidth.'" border="0" cellspacing="0" cellpadding="0">';

		echo '<tr><td valign="top" width="20%">Date Entered:</td>';
		echo '<td valign="top" width="'.$printWidth.'" colspan="4">'.libFormatDateTime($db->rst($orReferral, "DateTimeAdd"), true);

		echo '<br/><br/></td></tr>';

		echo '<tr><td valign="top" width="20%">Member:</td>';
		echo '<td valign="top" width="60%"><strong>';
		echo FormatName($db->rst($orReferral, "MemberLastName"), $db->rst($orReferral, "MemberFirstName"), $db->rst($orReferral, "MemberMiddleInitial"), 0);
		echo '</strong></td><td valign="top" width="10%">Member ID:</td>';
		echo '<td valign="top" width="10%">'.$db->rst($orReferral, "MemberID").'</td></tr>';

		echo '<tr><td valign="top" width="20%">Date of Birth:</td>';
		echo '<td valign="top" width="60%">'.libFormatDate($db->rst($orMember, "BirthDate")).'</td>';
		echo '<td valign="top" width="10%">Date Effective:</td>';
		echo '<td valign="top" width="10%">'.$db->rst($orMember, "EffectiveDate").'<br/><br/></td></tr>';

		echo '<tr><td valign="top" width="10%">HMO:</td>';
		echo '<td valign="top" width="90%" colspan="4">'.$db->rst($orReferral, "PayorName").'</td></tr>';

		echo '<tr><td valign="top" width="20%">HMO Authorization:</td>';
		echo '<td valign="top" width="'.$printWidth.'" colspan="4">'.$db->rst($orReferral, "HmoAuthorizationNumber").'</td></tr>';

		echo '<tr><td valign="top" width="20%">HMO Authorized Days:</td>';
		$Days = intval($db->rst($orReferral, "HmoAuthorizedDays"));

		if ($Days > 0) {
			echo '<td valign="top" width="'.$printWidth.'" colspan="4">'.$db->rst($orReferral, "HmoAuthorizedDays").'<br/><br/></td></tr>';
		}
		else {
			echo '<td valign="top" width="'.$printWidth.'" colspan="4">&nbsp;<br/><br/></td></tr>';
		}

		echo '<tr><td valign="top" width="20%">PCP:</td>';
		echo '<td valign="top" width="60%">'.FormatName($db->rst($orReferral, "PcpLastName"),
		$db->rst($orReferral, "PcpFirstName"), "", $db->rst($orReferral, "MemberPcp")).'<br/>';
		$phoneAndFax = GetPhoneAndFax($db->rst($orReferral, "MemberPcp"));
		echo 'Phone: "'.libFormatPhone($phoneAndFax->Phone).'<br/>Fax: '.libFormatPhone($phoneAndFax->Fax);
		echo '</td>';
		echo '<td valign="top" width="10%">PCP Approved:</td>';
		echo '<td valign="top" width="10%">'.PCPApproved($db->rst($orReferral, "PcpApproved")).'</td></tr>';

		echo '<tr><td valign="top" width="20%">ELOS:</td>';
		echo '<td valign="top" width="'.$printWidth.'" colspan="3">';

		if (intval($db->rst($orReferral, "ELOS")) > 0) {
			echo $db->rst($orReferral, "ELOS");
		}
		else {
			echo '&nbsp;';
		}

		echo '</td></tr>';

		echo '<tr><td valign="top" width="20%">ICD:</td>';
		echo '<td valign="top" width="'.$printWidth.'" colspan="3">'.$db->rst($orReferral, "DiagnosisCode").'</td></tr>';

		echo '<tr><td valign="top" width="100%" colspan="5">';
		echo '<hr noshade width="100%" size="1" color=""black"">';
		echo '</td></tr>';

		$LastProv = "zzz";
		$LastSvc = "zzz";
		$LastDOS = "zzz";
		while(!$orDetail->EOF) {
			// 10/29/08 JLC Don't display if this provider is the same as the last provider
			if (($LastProv != $db->rst($orDetail, "Provider")) ||
				($LastSvc != $db->rst($orDetail, "Service")) ||
				($LastDOS != $db->rst($orDetail, "FromDate"))) {
				echo '<tr><td valign="top" width="100%" colspan="5">';
				echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

				echo '<tr><td valign="top" width="50%" colspan="3">';
				echo '<strong>Provider: '.FormatName($db->rst($orDetail, "LastName"), $db->rst($orDetail, "FirstName"), "", $db->rst($orDetail, "Provider"));
				echo '</strong></td>';
				echo '<td valign="top" width="23%">';
				echo '<strong>Provider Specialty:</strong></td>';
				echo '<td valign="top" width="27%">';
				echo $db->rst($orDetail, "Specialty").'</td></tr>';

				$LineType = $db->rst($orDetail, "LineType");
				echo '<tr><td width="5%">&nbsp;</td>';
				echo '<td valign="top" width="15%"><strong>Service:</strong></td>';
				echo '<td valign="top" width="30%">'.$db->rst($orDetail, "Service").'</td>';
				echo '<td valign="top" width="50%" colspan="2">&nbsp;</td></tr>';

				if ($LineType == "F") {
					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>Admission Date:</strong></td>';
					echo '<td valign="top" width="30%">'.libFormatDate($db->rst($orDetail, "FromDate")).'</td>';
					echo '<td valign="top" width="23%"><strong>DC Date:</strong></td>';
					echo '<td valign="top" width="27%">'.libFormatDate($db->rst($orDetail, "ToDate")).'</td>';
				}
				else {
					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>From Date:</strong></td>';
					echo '<td valign="top" width="30%">'.libFormatDate($db->rst($orDetail, "FromDate")).'</td>';
					echo '<td valign="top" width="23%"><strong>To Date:</strong></td>';
					echo '<td valign="top" width="27%">'.libFormatDate($db->rst($orDetail, "ToDate")).'</td>';
				}

				if ($db->rst($orDetail, "Visits") > 0) {
					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>Visits:</strong></td>';
					echo '<td valign="top" width="30%">'.$db->rst($orDetail, "Visits").'</td>';
					echo '<td colspan="2">&nbsp;</td></tr>';
				}

				// 03/23/09 JLC
				if (trim($db->rst($orDetail, "Note")) != "") {
					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>Note:</strong></td>';
					echo '<td valign="top" width="'.$printWidth.'" colspan="3">'.str_replace("\r\n", "<br/>", $db->rst($orDetail, "Note")).'</td></tr>';
				}

				if ($LineType == "F") {
					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>Admission Type:</strong></td>';
					echo '<td valign="top" width="30%">'.$db->rst($orDetail, "HospAdmitType").'</td>';
					echo '<td valign="top" width="50%" colspan="2">&nbsp;</td></tr>';

					echo '<tr><td width="5%">&nbsp;</td>';
					echo '<td valign="top" width="15%"><strong>Facility Type:</strong></td>';
					echo '<td valign="top" width="30%">'.$db->rst($orDetail, "FacType").'</td>';
					echo '<td valign="top" width="23%"><strong>Reason For Hospitalization:</strong></td>';
					echo '<td valign="top" width="27%">'.$db->rst($orDetail, "HospReason").'</td>';
				}

				echo '</table></td></tr>';

				echo '<tr><td valign="top" width="100%" colspan="5">';
				echo '<hr noshade width="100%" size="1" color="black">';
				echo '</td></tr>';
			}

			$LastProv = $db->rst($orDetail, "Provider");
			$LastSvc = $db->rst($orDetail, "Service");
			$LastDOS = $db->rst($orDetail, "FromDate");
			$orDetail->MoveNext();
		}

		echo '<tr><td valign="top" width="100%" colspan="5">';
		echo '<strong>Preliminary Diagnosis:</strong></td></tr>';
		echo '<tr><td valign="top" width="100%" colspan="5">';

		if ($db->rst($orReferral, "DiagnosisCode") != "") {
			echo $db->rst($orReferral, "DiagnosisCode").' - '.$db->rst($orReferral, "ICD_9_Description");
		}

		echo '<p style="margin:0 0 0 1em;padding:0;">';
		echo str_replace("\r\n", "<br/>", $db->rst($orReferral, "Diagnosis")).'</p></td></tr>';

		echo '</table>';

		$isError = false;
	}
	else {
		$isError = true;
	}

	echo ShowDisclaimer();
}

// 08/10/09 JLC
function ShowDateMailed()
{
	global $db;

	$sql = "SELECT PrintDate FROM ToBeMailed WHERE AuthorizationNumber = ?";
	$rs = $db->GetRecords($db->QCPortalDB, $sql, array(Request('refnum')));

	if (!$rs->EOF) {
		if ($db->rst($rs, "PrintDate") != "") {
			return libFormatDate($db->rst($rs, "PrintDate"));
		}
	}

	return '';
}
?>
<!DOCTYPE html>

<html>
<head>
	<title><?=$oCommon->getDBName()?></title>
	<link rel="stylesheet" type="text/css" href="<?=_asset('css/reprint.css')?>" media="screen">
	<link rel="stylesheet" type="text/css" href="<?=_asset('css/reprint_printer.css')?>" media="print">
    <script src="<?=_asset('assets/js/jquery-3.7.1.min.js', false)?>"></script>
	<script src="<?=_asset('js/utils.js')?>"></script>
	<script>
	// For AJAX
	var origDisplay = "";
	window.onbeforeprint = beforeprint;
	window.onafterprint = afterprint;

	function beforeprint()
	{
		var obj = new getObj('buttons');
		origDisplay = obj.style.display;
		obj.style.display = "none";
	}

	function afterprint()
	{
		var obj = new getObj('buttons');
		obj.style.display = origDisplay;
	}
	</script>
</head>
<body style="font-family: Times New Roman,Roman,Serif;">
<div class="center">

<?php
$ReprintType = 'Reprint';

if ($RefType == 'REFERRAL') {
	$ReprintType = 'Referral Reprint';
}
elseif ($RefType == 'AUTHORIZATION') {
	$ReprintType = 'Referral Reprint';
}

$isError = false;
if ($authorizedToView) {
	echo '<div id="buttons" class="right">';
	echo '<a href="javascript:void(0);" onclick="window.print();return false;">Print Referral</a>';
	echo '</div><br/>';

	if ($RefType == 'REFERRAL') {
		DoReferral();
	}
	elseif ($RefType == 'AUTHORIZATION') {
		DoAuthorization();
	}
	else {
        _trace('isError 999');
	}
}

if ($isError || !$authorizedToView) {
	echo '<table width="90%" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr><td valign="top" width="100%" align="center" class="errormsg">';

	if ($isError) {
		printf('%s', 'Error encountered! Referral '._W($RefNum).' not found in the database.');
	}
	elseif (!$authorizedToView) {
		echo 'Error encountered! You are not authorized to view this referral.';
	}

	echo '</td></tr></table><br/>';
}
//echo '</div>';
?>
</body>
</html>

