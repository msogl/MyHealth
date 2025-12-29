<?php

namespace Myhealth\Models;

use Myhealth\Classes\Common;
use Myhealth\Core\Dates;

class MaterialModel extends BaseModel
{
    public function getByMember(string $memberId): array
    {
        $materialsPath = (new Common())->getConfig('MATERIALS PATH', '');

        if ($materialsPath == '') {
            return [];
        }
        
        if (APP_ENVIRONMENT === 'local') {
            $materialsPath = str_replace("C:\\WEB_CONTENT\\TESTLOCAL_WEB_CONTENT", "C:\\dev", $materialsPath);
        }
        
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT DISTINCT date_sent, filename 
        FROM case_material cm WITH(NOLOCK)
        JOIN [case] c WITH(NOLOCK) ON c.id = cm.case_id
        WHERE c.closed_date IS NULL AND c.opt_out_date IS NULL
        AND c.qc_member_id = ?
        AND cm.type = 6
        ORDER BY date_sent DESC, filename
        ENDSQL;

        $rs = $db->GetRecords($db->ClinicalDB, $sql, [$memberId]);
        $materials = [];

        while (!$rs->EOF) {
            if ($rs->fields['filename'] != '') {
                if (file_exists($materialsPath.'/'.$rs->fields['filename'])) {
                    $materials[] = (object) [
                        'filename' => $rs->fields['filename'],
                        'dateSent' => Dates::datePortion($rs->fields['date_sent']),
                    ];
                }
            }

            $rs->MoveNext();
        }

        return $materials;
    }
}
