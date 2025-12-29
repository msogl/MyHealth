<?php

namespace Myhealth\Models;

use Myhealth\Classes\Event;
use Myhealth\Daos\LockoutDAO;
use Myhealth\Core\Dates;


class LockoutModel extends BaseModel
{
	private $table = 'Lockout';
    private $daoName = 'LockoutDAO';
    public $attemptPeriod;
    public $lockoutTime;
    public $maxLoginAttempts;

	public $dao = null;

    public function __construct()
    {
        parent::__construct();

        $this->attemptPeriod = $this->common->getConfig('LOGIN_ATTEMPT_PERIOD', null);
        if ($this->attemptPeriod == '') {
            $this->attemptPeriod = '15';
        }

        $this->lockoutTime = $this->common->getConfig('LOCKOUT_TIME', null);
        if ($this->lockoutTime == '') {
            $this->lockoutTime = '15';
        }

        $this->maxLoginAttempts = $this->common->getConfig('MAX_LOGIN_ATTEMPTS', null);
        if ($this->maxLoginAttempts == '') {
            $this->maxLoginAttempts = '3';
        }
    }

	public function load($username)
	{
		$sql = "SELECT * FROM {$this->table} WHERE username = ?";
		$rs = $this->db->execute($sql, array($username));
		
		if (!$this->db->hasRecords($rs)) {
			$this->dao = null;
			return false;
		}
		
		$this->dao = $this->db->wrappers($rs, $this->daoName, true);
		return $this->dao;
	}
	
	public function incrementCount($username)
	{
		if ($this->load($username) === false) {
			return ($this->create($username) > 0 ? 1 : 0); 
		}
		
		$minutes = abs(strtotime('now') - strtotime($this->dao->last_attempt)) / 60;

		if ($minutes > $this->attemptPeriod) {
			$this->dao->attempt_count = 1;
		}
		
		$this->dao->last_attempt = Dates::sqlDateTime('now');
		$this->dao->attempt_count++;
		$this->update();
		return $this->dao->attempt_count;
	}
	
	public function lock($username)
	{
		if ($this->load($username) === false) {
			return;
		}
		
		$this->dao->is_locked = 1;
		$this->update();
        LogEvent(Event::EVENT_ACCOUNT_LOCKED, $username, "Account locked for {$username}");
	}
	
	public function clearLock($username)
	{
		$sql = "DELETE FROM {$this->table} WHERE username = ?";
		$this->db->execute($sql, array($username));
	}
	
	public function isLocked($username)
	{
		if ($this->load($username) === false) {
			return false;
		}
		
		if (intval($this->dao->is_locked) === 0) {
			return false;
		}
		
		$minutes = abs(strtotime('now') - strtotime($this->dao->last_attempt)) / 60;

		if ($minutes <= $this->lockoutTime) {
			return true;
		}
		else {
			$this->clearLock($username);
			return false;
		}
	}
	
	private function create($username)
	{
        $this->dao = new LockoutDAO();
		$this->dao->username = $username;
		$this->dao->last_attempt = Dates::sqlDateTime('now');
		$this->dao->attempt_count = 1;

        $sql = "INSERT INTO {$this->table} (username, last_attempt, attempt_count) VALUES (?, ?, ?)";
        $this->db->executeNonQuery($sql, [
            $this->dao->username,
            $this->dao->last_attempt,
            $this->dao->attempt_count
        ]);

        $rs = $this->db->execute("SELECT @@IDENTITY AS id");
        return ($this->db->hasRecords($rs) ? $rs->fields['id'] : 0);
	}
	
	private function update()
	{
		if (is_null($this->dao)) {
			return false;
		}

        $sql = "UPDATE {$this->table} SET last_attempt = ?, attempt_count = ?, is_locked = ?\n";
        $sql .= "WHERE id = ?\n";
        $this->db->executeNonQuery($sql, [
            Dates::sqlDateTime($this->dao->last_attempt),
            $this->dao->attempt_count,
            ($this->dao->is_locked ? 1 : 0),
            $this->dao->id,
        ]);
	}
}
