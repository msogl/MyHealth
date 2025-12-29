<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\GoalDAO;

class GoalModel extends BaseModel
{
    public function getByMember(string $memberId): array
    {
        $db = &$this->db;

        $sql = <<<ENDSQL
        SELECT ccp.*, IIF(ccp.priority = 0, 999999999, ccp.priority) AS sequence
        FROM case_care_plan ccp with(nolock)
        JOIN [case] c with(nolock) ON c.id = ccp.case_id
        WHERE c.qc_member_id = ?
        AND c.closed_date IS NULL AND c.opt_out_date IS NULL AND c.enrolled_date IS NOT NULL
        AND ccp.deleted != 1
        ORDER BY sequence, ccp.created_date
        ENDSQL;

        $records = $db->magicWrappers(
            $db->GetRecords($db->ClinicalDB, $sql, [$memberId])
        );

        $goals = [];
        foreach($records as &$record) {
            if ((($record->do_not_show_for_patient != 1) &&
                ($record->unattainable != 1) &&
                (!str_contains(strtoupper($record->goal), "DEPRESSION SCREENING")))) {
                $goal = new GoalDAO();
                $goal->Goal = $record->goal;
                $goal->DateToMeet = Dates::toString($record->date_to_meet, Dates::DATE_FORMAT);
                $goal->RevisedDate = Dates::toString($record->revised_date, Dates::DATE_FORMAT);
                $goal->DateGoalMet = Dates::toString($record->date_goal_met, Dates::DATE_FORMAT);
                $goals[] = $goal;
            }
        }

        return $goals;
    }
}
