<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\MemberDAO;

class MemberModel extends BaseModel
{
	private $daoName = 'MemberDAO';

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->QuickCapDB;
    }

	public function getById($id)
	{
        $sql = <<<ENDSQL
        SELECT m.*
        , RTRIM(m.MBR_last_name+', '+m.MBR_first_name+' '+isnull(m.MiddleInitial,'')) as FullName
        , pay.PayorName
        FROM qcpMemberBasic m
        JOIN qcpPayor pay ON pay.Payor = m.Payor
        WHERE m.MBR_SSN_number = ?
        ENDSQL;

		$rs = $this->db->execute($sql, [$id]);

		if (!$rs->EOF) {
			$dao = $this->db->wrappers($rs, $this->daoName, true);
			$dao->Age = intval(Dates::dateDiff($this->db->rst($rs, "BirthDate"), 'now', true) / 365.242);
			return $dao;
		}

		return null;
	}

    /**
     * Finds a member record given a subscriber ID. Looks for an exact match,
     * or a match with appended two digits (0# or 1#).
     */
    public function findBySubscriberId(string $first, string $memId, string $dob): ?MemberDAO
    {
        $sql = <<<ENDSQL
        SELECT TOP(1) MBR_first_name, MBR_SSN_number, BirthDate
        FROM qcpMemberBasic
        WHERE (MBR_SSN_number = ? OR MBR_SSN_number LIKE ? OR MBR_SSN_number LIKE ?)
        AND MBR_first_name = ?
        AND BirthDate = ?
        ENDSQL;

		$params = [
			$memId,
			"{$memId}0_",		// _ = single wildcard char
			"{$memId}1_",
            $first,
            Dates::sqlDate($dob),
		];

		$rs = $this->db->execute($sql, $params);
        return $this->db->wrappers($rs, $this->daoName, true);
    }
}
