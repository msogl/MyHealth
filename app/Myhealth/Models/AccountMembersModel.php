<?php

namespace Myhealth\Models;

class AccountMembersModel extends BaseModel
{
    public $id = null;
    public $AccountID = null;
	public $MemberID = null;
	public $CreatedDateTime = null;
	public $CreatedBy = null;

    /**
     * This method gets manually linked members via some user interface. This is
     * not automatic.
     */
    public function getMembers(int $accountId) {
        $sql = "SELECT DISTINCT IIF(a.MemberID = m.MBR_SSN_Number, 1, 0) AS IsPrimary\n";
        $sql .= ", IIF(a.MemberID = m.MBR_SSN_Number, a.MemberID, am.MemberID) AS MemberID\n";
        $sql .= ", IIF(a.MemberID = m.MBR_SSN_Number, a.CreatedDateTime, am.CreatedDateTime) AS CreatedDateTime\n";
        $sql .= ", IIF(a.MemberID = m.MBR_SSN_Number, a.Username, am.CreatedBy) AS CreatedBy\n";
        $sql .= ", m.MBR_last_name, m.MBR_first_name, m.BirthDate\n";
        $sql .= "FROM Accounts a\n";
        $sql .= "JOIN AccountMembers am ON am.AccountID = a.AccountID\n";
        $sql .= "JOIN RPPGTEST..qcpMemberBasic m ON m.MBR_SSN_number = a.MemberID OR\n";
        $sql .= "                                   m.MBR_SSN_number = am.MemberID\n";
        $sql .= "WHERE a.AccountID = ?\n";
        $sql .= "ORDER BY IIF(a.MemberID = m.MBR_SSN_Number, 1, 0) DESC, m.MBR_last_name, m.MBR_first_name\n";

        $rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, [$accountId]);
        return $this->db->magicWrappers($rs);
    }

    public function hasMember($accountMembers, $memberId) {
        foreach($accountMembers as &$accountMember) {
            if ($accountMember->MemberID === $memberId) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method uses the member ID and relation ship to automatically determine
     * who gets members.
     */
    public function getMembers2(int $accountId) {
        $rs = $this->db->GetRecords($this->db->QCMembersDB, 'EXEC LinkedMembers @AccountID = ?', [$accountId]);
        return $this->db->magicWrappers($rs);
    }

    public function hasMember2($linkedMembers, $memberId) {
        foreach($linkedMembers as &$linkedMember) {
            if ($linkedMember->MBR_SSN_number === $memberId) {
                return true;
            }
        }

        return false;
    }
}