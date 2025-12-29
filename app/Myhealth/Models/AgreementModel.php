<?php

namespace Myhealth\Models;

class AgreementModel extends BaseModel
{
    public function agreed($userId)
    {
        $sql = "SELECT * FROM Agreement WHERE UserID = ? ORDER BY AgreeDateTime DESC";
        $rs = $this->db->GetRecords($this->db->QCMembersDB, $sql, array($userId));
        return (!$rs->EOF && $rs->fields['Answer'] === 'Y');
    }

    public function recordAnswer($userId, $answer)
    {
        $sql = "INSERT INTO Agreement (UserID, Answer, AgreeDateTime) VALUES (?, ?, GETDATE())";
        $this->db->executeSQL($this->db->QCMembersDB, $sql, [$userId, $answer]);
    }
}
