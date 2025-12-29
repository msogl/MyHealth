<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;

class EventModel extends BaseModel
{
    private $daoName = 'EventDAO';
    private $table = 'Events';

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->QCMembersDB;
    }

    public function getEvents(string $user, string $from, string $to, array $events, int $limit): array
    {
        $db = &$this->db;
        $sql = '';

        $params = [];
        $params[] = $limit;

        $userClause = '';
        $fromClause = '';
        $toClause = '';

        if (!in_array($user, ['', 'Any'])) {
            $userClause = "AND user_id = ?";
            $params[] = $user;
        }

        if ($from != '') {
            $fromClause = "AND event_date >= ?";
            $params[] = Dates::sqlDate($from);
        }

        if ($to != '') {
            $toClause = "AND event_date <= ?";
            $params[] = Dates::sqlDate($to);
        }

        $sql = <<<ENDSQL
        DECLARE @top INT = ?;
        SELECT TOP(@top) *
        FROM {$this->table} WITH(NOLOCK)
        WHERE 1=1
        {$userClause}
        {$fromClause}
        {$toClause}
        ENDSQL;

        if (count($events) > 0 && !in_array('Any', $events)) {
            $sql .= "AND event_type IN (" . $db->paramPlaceholders(count($events)) . ")\n";
            foreach ($events as $event) {
                $params[] = $event;
            }
        }

        $sql .= "ORDER BY event_date DESC";

        $rs = $db->execute($sql, $params);
        return $db->wrappers($rs, $this->daoName);
    }
}
