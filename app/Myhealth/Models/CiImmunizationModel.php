<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\CiImmunizationDAO;

class CiImmunizationModel extends BaseModel
{
    private $daoName = 'CiImmunizationDAO';
    private $table = 'ci_immunization';

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->ClinicalDB;
    }

    public function getForPatient(int $patientId): array
    {
        $sql = "exec GetImmunizationsForPatient ?";
        return $this->db->magicWrappers(
            $this->db->execute($sql, [ $patientId ])
        );
    }

    public function saveFluShot(string $memberId, string $fluShotDate): bool
    {
        $sql = <<<ENDSQL
        exec SaveImmunizations @QCMemberID = ?, @TransactionDate = ?, @StartDate = ?,
            @ImmunizationDose = 0, @ImmunizationId = 0, @Description = 'Flu Shot',
            @NPI = '', @UserID = 'SELF', @Source = 'PORTAL'
        ENDSQL;

        $params = [
            $memberId,
            Dates::sqlDate($fluShotDate),
            Dates::sqlDateTime('now'),
        ];

        $rs = $this->db->execute($sql, $params);
        return ($this->db->hasRecords($rs) && $rs->fields['id'] > 0);
    }
}
