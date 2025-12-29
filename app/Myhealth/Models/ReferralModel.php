<?php

namespace Myhealth\Models;

class ReferralModel extends BaseModel
{
    public function getByMember(string $memberId): array|null
    {
        $db = &$this->db;

        $sql = <<< ENDSQL
        SELECT * FROM (
            SELECT r.*, p.LastName, p.FirstName,
            p.Degree as Title, p.Person, s.Specialty
            FROM qcpReferral r
            JOIN qcpProvider p on p.Provider = r.Provider
            JOIN qcpSpecialties s on s.Code = r.SpecialtyCode
            WHERE r.MemberID = ?
            AND r.Status = 'A'
        ) AS data
        ORDER BY DateEntered desc
        ENDSQL;

        return $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$memberId])
        );
    }

    public function loadReferral(string $referralNumber, string $memberId): object|null
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT r.*, p1.LastName as ByLastName, p1.FirstName as ByFirstName,
        p1.Degree as ByTitle, p1.Person as ByPerson,
        p2.LastName as ToLastName, p2.FirstName as ToFirstName,
        p2.Degree as ToTitle, p2.Person as ToPerson,
        s.Specialty, m.MBR_first_name, m.MBR_last_name, m.MiddleInitial,
        m.BirthDate, pay.PayorName
        FROM qcpReferral r
        JOIN qcpMember m on m.MBR_SSN_Number = r.MemberID
        JOIN qcpProvider p1 on p1.Provider = r.Provider
        JOIN qcpProvider p2 on p2.Provider = r.ReferringProvider
        JOIN qcpSpecialties s on s.Code = r.SpecialtyCode
        JOIN qcpPayor pay on pay.Payor = r.Payor
        WHERE r.ReferralNumber = ?
        AND r.MemberID = ?
        AND r.Status = 'A'
        ENDSQL;

        $referral = $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$referralNumber, $memberId]),
            true
        );

        if (is_null($referral)) {
            return null;
        }

        $referral->details = $this->loadDetails($referralNumber);
        return $referral;
    }

    private function loadDetails(string $referralNumber): array
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT rd.*
        , icd9.ICD_9_Description as DiagnosisDescription
        , cpt.CPT_description as ProcedureDescription
        , rp.Description as ServiceDescription
        FROM qcpReferralDetail rd
        JOIN qcpReferral r on r.ReferralNumber = rd.ReferralNumber
        LEFT OUTER JOIN qcpICD_9_code icd9 on icd9.ICD_9_number = LTRIM(RTRIM(rd.DiagnosisCode)) AND icd9.IcdVersion = r.IcdVersion
        LEFT OUTER JOIN qcpCPT_code cpt on cpt.CPT_number = LTRIM(RTRIM(rd.ProcedureCode))
        LEFT OUTER JOIN qcpReferredProcedure rp on rp.Code = LTRIM(RTRIM(rd.ReferredProcedure))
        WHERE rd.ReferralNumber = ?
        ENDSQL;

        return $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$referralNumber])
        );
    }
}
