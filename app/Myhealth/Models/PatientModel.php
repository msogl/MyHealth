<?php

namespace Myhealth\Models;

class PatientModel extends BaseModel
{
	public function getIdForMember(string $memberId, string $externalSystemName='QUICKCAP'): int
	{
        $db = &$this->db;

        $sql = "exec GetPatientByExternalId ?, ?";
        $rs = $db->GetRecords($db->ClinicalDB, $sql, [$memberId, $externalSystemName]);
        return $db->hasRecords($rs) ? $rs->fields['id'] : 0;
	}
}
