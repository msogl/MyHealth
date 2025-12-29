<?php

namespace Myhealth\Models;

class VitalStatsModel extends BaseModel
{
    private $daoName = 'VitalsDAO';

    public function getByMember(string $memberId)
    {
        $db = &$this->db;

        $sql = "exec GetVitalStatsForChart ?";
        $rs = $db->GetRecords($db->ClinicalDB, $sql, [$memberId]);
        return $db->wrappers($rs, $this->daoName);
    }
}
