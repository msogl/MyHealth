<?php

namespace Myhealth\Models;

class ClaimModel extends BaseModel
{
    public function getByMember(string $memberId): array|null
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT * FROM (
            SELECT ch.ServiceDate, claim.Claim_number, ch.Claim_Member_SSN, claim.AmountBilled, claim.AmountPaid,
            p.LastName, p.FirstName, p.Degree AS Title, p.Person, ch.Status
            FROM (
                SELECT ch.Claim_number, SUM(ccpt.AmountBilled) AS AmountBilled
                , SUM(ccpt.Fee) AS AmountPaid
                FROM qcpClaim_Header ch
                JOIN qcpClaim_CPT ccpt ON ccpt.Claim_number = ch.Claim_number
                WHERE ch.Claim_Member_SSN = ?
                AND Status IN ('P', 'D', 'O')
                GROUP BY ch.Claim_number
            ) AS claim
            JOIN qcpClaim_Header ch ON ch.Claim_number = claim.Claim_number
            JOIN qcpProvider p ON p.Provider = ch.Claim_PCP_number
        ) AS data
        ORDER BY ServiceDate DESC
        ENDSQL;

        return $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$memberId])
        );
    }

    public function getDetails(string $claimNumber, string $memberId): array
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT ch.*, tos.Description as ServiceType, rc.ResponseDescription as Disposition
        , p.LastName, p.FirstName, p.Degree as Title, p.Person, m.*, pay.PayorName
        , ccpt.*
        , cpt.CPT_description
        FROM qcpClaim_Header ch
        JOIN qcpClaim_CPT ccpt ON ccpt.Claim_number = ch.Claim_number
        LEFT OUTER JOIN qcpTypeOfService tos on tos.Code = ccpt.TypeOfService
        JOIN qcpProvider p on p.Provider = ch.Claim_PCP_number
        JOIN qcpMember m on m.MBR_SSN_number = ch.Claim_Member_SSN
        JOIN qcpPayor pay on pay.Payor = ch.Payor
        LEFT OUTER JOIN qcpAdjustmentCodes rc on rc.ResponseCode = ccpt.DispositionCode
        JOIN qcpCPT_Code cpt on cpt.CPT_Number = ltrim(rtrim(ccpt.Claim_CPT))
        WHERE ch.Claim_number = ?
        and ch.Claim_Member_SSN = ?
        ENDSQL;

        return $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$claimNumber, $memberId])
        );
    }
}
