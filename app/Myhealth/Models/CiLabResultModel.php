<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\CiLabResultDAO;

class CiLabResultModel extends BaseModel
{
    private $daoName = 'CiLabResultDAO';
    private $table = 'ci_lab_results';
    
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

    public function save(CiLabResultDAO $dao): bool
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT *
        FROM {$this->table}
        WHERE patient_id = ?
        AND qc_member_id = ?
        AND lab_date = ?
        AND entered_by = 'SELF'
        ENDSQL;

        $dao->lab_date = Dates::sqlDate($dao->lab_date);
        $dao->date_entered = Dates::sqlDateTime('now');
        $dao->entered_by = 'SELF';

        $params = [
            $dao->patient_id,
            $dao->qc_member_id,
            $dao->lab_date,
        ];

        $rs = $db->GetRecords($db->ClinicalDB, $sql, $params);

        if ($db->hasRecords($rs)) {
            $origDao = $db->wrappers($rs, $this->daoName, true);
            $origDao->ldl = $dao->ldl;
            $origDao->hdl = $dao->hdl;
            $origDao->triglycerides = $dao->triglycerides;
            $origDao->cholesterol = $dao->cholesterol;
            $origDao->hba1c = $dao->hba1c;
            $origDao->glucose = $dao->glucose;
            $origDao->date_entered = Dates::sqlDateTime('now');
            return $db->updateDao($this->table, $origDao, 'id', true);
        }

        $id = $db->insertDao($this->table, $dao, 'id');
        return ($id > 0);
    }
}