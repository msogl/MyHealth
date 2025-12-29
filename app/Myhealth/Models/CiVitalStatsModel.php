<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\CiVitalStatsDAO;

class CiVitalStatsModel extends BaseModel
{
    private $daoName = 'CiVitalStatsDAO';
    private $table = 'ci_vital_stats';

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->ClinicalDB;
    }

    public function getMostRecentForMember(string $memberId): CiVitalStatsDAO|null
    {
        $sql = <<<ENDSQL
        SELECT TOP(1) *
        FROM {$this->table}
        WHERE qc_member_id = ?
        ORDER BY date DESC
        ENDSQL;

        $rs = $this->db->GetRecords($this->db->ClinicalDB, $sql, [ $memberId ]);
        return $this->db->wrappers($rs, $this->daoName, true);
    }

    public function save(CiVitalStatsDAO $dao): bool
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT *
        FROM {$this->table}
        WHERE patient_id = ?
        AND qc_member_id = ?
        AND date = ?
        AND entered_by = 'SELF'
        AND source = 'PATIENT'
        ENDSQL;

        $dao->date = Dates::sqlDate($dao->date);
        $dao->date_entered = Dates::sqlDateTime('now');
        $dao->entered_by = 'SELF';
        $dao->source = 'PATIENT';
        
        $params = [
            $dao->patient_id,
            $dao->qc_member_id,
            $dao->date,
        ];

        $rs = $db->GetRecords($db->ClinicalDB, $sql, $params);

        if ($db->hasRecords($rs)) {
            $origDao = $db->wrappers($rs, $this->daoName, true);
            $origDao->bp_systolic = $dao->bp_systolic;
            $origDao->bp_diastolic = $dao->bp_diastolic;
            $origDao->weight = $dao->weight;
            $origDao->height = $dao->height;
            $origDao->bmi = $dao->bmi;
            $origDao->date_entered = Dates::sqlDateTime('now');
            $origDao->updated_date = Dates::sqlDateTime('now');
            return $db->updateDao($this->table, $origDao, 'id', true);
        }

        $id = $db->insertDao($this->table, $dao, 'id');
        return ($id > 0);
    }
}
