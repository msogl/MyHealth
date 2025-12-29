<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\FyiDAO;

class FyiModel extends BaseModel
{
    private string $table = 'fyi';
    private string $daoName = 'FyiDAO';

    public function load(int $id): object
    {
        $db = &$this->db;

        $sql = "SELECT * FROM {$this->table} WHERE id = ?";

        return $db->wrappers(
            $db->GetRecords($db->QCMembersDB, $sql, [$id]),
            $this->daoName,
            true
        );
    }

    public function getMessages(string $memberId): array
    {
        $db = &$this->db;
        $member = (new MemberModel())->getById($memberId);
        $payor = $member->Payor ?? '';

        if (InList($payor, config('PAYORS', 'BCBSIL'))) {
            $payor = "BCBS";
        }

        $sql = <<<ENDSQL
        SELECT * FROM FYI WITH(NOLOCK)
        WHERE GETDATE() BETWEEN StartDate AND ISNULL(EndDate, '12/31/2050') + ' 23:59:59'
        AND (Payor IS NULL OR Payor = ?)
        ORDER BY StartDate DESC
        ENDSQL;

        return $db->wrappers(
            $db->GetRecords($db->QCMembersDB, $sql, [$payor]),
            $this->daoName
        );
    }

    public function save(FyiDAO $fyiDao): int
    {
		$fyiDao->StartDate = Dates::sqlDate($fyiDao->StartDate);
        $fyiDao->EndDate = Dates::sqlDate($fyiDao->EndDate);

        if ($fyiDao->id == 0) {
            return ($this->db->insertDao($this->table, $fyiDao, 'id') > 0);
        }
        else {
            return $this->db->updateDao($this->table, $fyiDao, 'id', true);
        }
	}

    public function delete(int $id): void
    {
        $sql = "DELETE FROM FYI WHERE id = ?";
        $this->db->executeSQL($this->db->QCMembersDB, $sql, [$id]);
    }
}
