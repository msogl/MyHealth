<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\CiGlucoseDAO;

class CiGlucoseModel extends BaseModel
{
    private $daoName = 'CiGlucoseDAO';
    private $table = 'ci_glucose';

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->ClinicalDB;
    }

    public function getByPatientId(int $patientId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE patient_id = ?";
        $rs = $this->db->getRecords($this->db->ClinicalDB, $sql, [$patientId]);
        return $this->db->wrappers($rs, $this->daoName);
    }

    public function save(CiGlucoseDAO $dao): bool
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT *
        FROM {$this->table}
        WHERE patient_id = ?
        AND qc_member_id = ?
        AND reading_date = ?
        AND entered_by = 'SELF'
        ENDSQL;

        $dao->reading_date = Dates::sqlDate($dao->reading_date);
        $dao->date_entered = Dates::sqlDateTime('now');
        $dao->entered_by = 'SELF';

        $params = [
            $dao->patient_id,
            $dao->qc_member_id,
            $dao->reading_date,
        ];

        $rs = $db->GetRecords($db->ClinicalDB, $sql, $params);

        if ($db->hasRecords($rs)) {
            $origDao = $db->wrappers($rs, $this->daoName, true);
            $origDao->diabetes_type = $dao->diabetes_type;
            $origDao->glucose = $dao->glucose;
            $origDao->time_of_day = $dao->time_of_day;
            $origDao->fasting = $dao->fasting;
            $origDao->date_entered = Dates::sqlDateTime('now');
            return $db->updateDao($this->table, $origDao, 'id', true);
        }

        $id = $db->insertDao($this->table, $dao, 'id');
        return ($id > 0);
    }
}