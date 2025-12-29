<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;
use Myhealth\Daos\MFATrustDAO;

class MFATrustModel extends BaseModel
{
    private $table = 'MFATrust';
    private $daoName = 'MFATrustDAO';

    public $id;
    public $UserID;
    public $MFACode;

    public function __construct()
	{
        parent::__construct();
		$this->db->currentDB = $this->db->QCMembersDB;
    }

    /**
     * Stores the MFA code to use later to determine whether the
     * user trusts the browser for a period of time. The code is
     * combined with an IP address within this method.
     * 
     * Returns the encrypted MFA code if storage is successful, or
     * false if it fails.
     * 
     * @param int $accountId
     * @param string $mfaCode
     * @return string|bool
     */
    public function rememberBrowser(int $accountId, string $mfaCode, string $expiryDate): string|bool
	{
        $dao = new MFATrustDAO();
        $dao->AccountID = $accountId;
        $dao->TrustInfo = EncryptAESMSOGL(GetRemoteIPAddress().':'.$mfaCode);
        $dao->ExpiryDate = Dates::sqlDateTime($expiryDate);
        $id = $this->db->insertDao($this->table, $dao, 'id');
        return ($id > 0 ? EncryptAESMSOGL($mfaCode) : false);
    }

    /**
     * Checks if MFA code is valid. This comes from a browser cookie.
     * The code is combined with an IP address within this method and
     * compared against active records in the database
     * 
     * @param int $accountId
     * @param string $mfaCode
     * @return bool
     */
    public function isMFAValid(int $accountId, string $mfaCode): bool
	{
        $compare = GetRemoteIPAddress().':'.DecryptAESMSOGL(urldecode($mfaCode));
        $this->clearExpiredCodes();

        $sql = "select * from {$this->table} where AccountID = ?\n";
        $sql .= "and ExpiryDate >= GETDATE()";

        $daos = $this->db->wrappers(
            $this->db->execute($sql, [$accountId]),
            $this->daoName
        );
        
        foreach($daos as &$dao) {
            $mfa = DecryptAESMSOGL($dao->TrustInfo);
            if ($mfa == $compare) {
                return true;
            }
        }

        return false;
    }

    public function clearExpiredCodes()
	{
        $this->db->executeNonQuery("delete from {$this->table} where ExpiryDate < GETDATE()");
    }

    public function clearAllForAccount($accountId)
    {
        $this->db->executeNonQuery("delete from {$this->table} where AccountID = ?", [$accountId]);
    }
}
