<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;

class ProviderModel extends BaseModel
{
	public function getPcpNote(string $memberId, string $noteType)
	{
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT TOP(1) p.FirstName + ' ' + p.LastName + rtrim(' ' + p.Degree) AS PCPName
        , nte.*
        FROM qcpMember m
        JOIN qcpProvider p ON p.Provider = m.MBR_PCP_number
        JOIN crd_practice_notes nte WITH(NOLOCK) ON nte.prac_id = p.prac_id AND
                                                    nte.note_type = 'INFORMATION' AND
                                                    nte.subject = 'COVID-19' AND
                                                    nte.status = 'ACTIVE' AND
                                                    nte.is_deleted = 'N'
        WHERE m.MBR_SSN_number = ?
        ENDSQL;

        return $db->magicWrappers(
            $db->GetRecords($db->QuickCapDB, $sql, [$memberId]),
            true
        );
	}
}
