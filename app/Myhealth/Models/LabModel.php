<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\LabDAO;
use Myhealth\Daos\LabDetailDAO;

class LabModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->ClinicalDB;
    }

    /**
     * Load the complete lab results, including detail records
     * @param int $id
     * return array|bool
     */
    public function load(int $id): object|bool
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT l.*, ld.*
        , case when p.Person = 1 then p.LastName+', '+p.FirstName else p.LastName+' '+p.FirstName end as ordering_provider_name
        , ext1.friendly_name
        FROM lab l
        JOIN lab_detail ld on ld.lab_id = l.id
        JOIN external_system ext1 ON ext1.id = l.external_system_id
        JOIN provider_ids prids on prids.provider_id = l.provider_id
        JOIN external_system ext2 ON ext2.id = prids.external_system_id and ext2.external_system_name = 'QUICKCAP'
        join {$db->QuickCapDB}..qcpProvider p on convert(varchar(40), p.Provider) = prids.external_id
        WHERE l.id = ?
        ORDER BY ld.set_id, ld.sequence_no
        ENDSQL;

        $rs = $db->GetRecords($db->ClinicalDB, $sql, [ $id ]);
        if (!$db->hasRecords($rs)) {
            return false;
        }

        $lab = new LabDAO();
        $lab->id = $db->rst($rs, 'id');
        $lab->labName = $db->rst($rs, 'friendly_name');
        $lab->orderNo = ($db->rst($rs, 'order_no') != '' ? $db->rst($rs, 'order_no') : $db->rst($rs, 'id'));
        $lab->labDate = Dates::datePortion($db->rst($rs, 'lab_date'));
        $lab->labProc = $db->rst($rs, 'lab_proc');
        $lab->orderingProvider = $db->rst($rs, 'ordering_provider_name');

        $lab->labDetails = [];
        while(!$rs->EOF) {
            $labDetail = new LabDetailDAO();
            $labDetail->valueType = $db->rst($rs, 'value_type');
            $labDetail->value = $db->rst($rs, 'value');
            $labDetail->valueRange = $db->rst($rs, 'value_range');
            $labDetail->valueDescription = $db->rst($rs, 'value_description');
            $labDetail->units = $db->rst($rs, 'units');
            $labDetail->abnormal = $db->rst($rs, 'abnormal');
            $labDetail->note = $db->rst($rs, 'note');
            $lab->labDetails[] = $labDetail;
            $rs->MoveNext();
        }

        return $lab;
    }

    public function getByMember(string $memberId): array
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT l.*, case when p.Person = 1 then p.LastName+', '+p.FirstName else p.LastName+' '+p.FirstName end as ordering_provider_name
        , ext1.friendly_name
        FROM lab l
        JOIN patient_ids ids ON ids.patient_id = l.patient_id
        JOIN external_system ext1 ON ext1.id = l.external_system_id
        JOIN provider_ids prids on prids.provider_id = l.provider_id
        JOIN external_system ext2 ON ext2.id = prids.external_system_id and ext2.external_system_name = 'QUICKCAP'
        JOIN {$db->QuickCapDB}..qcpProvider p on convert(varchar(40), p.Provider) = prids.external_id
        WHERE ids.external_id = ?
        AND ids.external_system_id = ext2.id
        ORDER BY l.lab_date desc
        ENDSQL;

        $rs = $db->GetRecords($db->ClinicalDB, $sql, [ $memberId ]);

        if (!$db->hasRecords($rs)) {
            return [];
        }

        $labs = [];
        while(!$rs->EOF) {
			$lab = new LabDAO();
			$lab->id = $db->rst($rs, 'id');
			$lab->labName = $db->rst($rs, 'friendly_name');
			$lab->orderNo = ($db->rst($rs, 'order_no') != '' ? $db->rst($rs, 'order_no') : $db->rst($rs, 'id'));
            $lab->labDate = Dates::datePortion($db->rst($rs, 'lab_date'));
			$lab->labProc = $db->rst($rs, 'lab_proc');
			$lab->orderingProvider = $db->rst($rs, 'ordering_provider_name');
			$labs[] = $lab;
			$rs->MoveNext();
		}
        return $labs;
    }

    /**
     * Return labs related to Comprehensive Metabolic Panel (CMP)
     * 
     * @param string $patientId
     * @return array
     */
    public function getCMPLabs(int $patientId): array
    {
        $db = &$this->db;
        if ($db->ClinicalDB == '' || !in_array($this->client, ['RPPG', 'RPA'])) {
            return [];
        }

        $sql = "exec GetLabResultsFromCiAndLabs ?";
        $labs = $db->magicWrappers(
            $db->GetRecords($db->ClinicalDB, $sql, [$patientId])
        );

        return $labs;
    }
}