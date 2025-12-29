<?php

namespace Myhealth\Models;

use Myhealth\Core\Dates;

class EmailAccountModel extends BaseModel
{
    private $table = 'email_account';
    public $dao = null;

    public function __construct()
    {
        parent::__construct();
        $this->db->currentDB = $this->db->CentralDB;
    }

    public function load(string $email, bool $activeOnly=false)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";

        if ($activeOnly) {
            $sql .= " AND active = 1";
        }

        $rs = $this->db->execute($sql, [$email]);
        return $this->db->magicWrappers($rs, true);
    }

    public function loadAll(bool $activeOnly=false): array
    {
        $sql = "SELECT * FROM {$this->table}\n";
        
        if ($activeOnly) {
            $sql .= " WHERE active = 1\n";
        }

        $sql .= "ORDER BY token_updated_date DESC";
        $rs = $this->db->execute($sql);
        return $this->db->magicWrappers($rs);
    }

    public function save(&$dao)
    {
        if ($dao == null) {
            return;
        }

        if (_isNEZ($dao->id)) {
            $tmpDao = $this->load($dao->email);
            if ($tmpDao != null) {
                $dao->id = $tmpDao->id;
            }
        }

        if (!_isNEZ($dao->id)) {
            $this->update($dao);
            return;
        }

        $this->create($dao);
    }

    public function create(&$dao)
    {
        if ($dao == null) {
            return;
        }

        $sql = "INSERT INTO {$this->table} (oauth_provider, email, client_id, client_secret, tenant_id, refresh_token, token_updated_date, active)\n";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?)\n";

        $params = [
            $dao->oauth_provider,
            $dao->email,
            $dao->client_id,
            $dao->client_secret,
            $dao->tenant_id,
            $dao->refresh_token,
            Dates::sqlDateTime($dao->token_updated_date),
            $dao->active,
        ];

        $sql = "INSERT INTO {$this->table} (oauth_provider, email, client_id, client_secret, tenant_id, refresh_token, token_updted_date, active)\n";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?)\n";

        $params = [
            $dao->oauth_provider,
            $dao->email,
            $dao->client_id,
            $dao->client_secret,
            $dao->tenant_id,
            $dao->refresh_token,
            Dates::sqlDateTime($dao->token_updated_date),
            $dao->active,
        ];

        $dao->id = $this->db->execute($sql, $params);
        return $dao->id;
    }

    public function update(&$dao)
    {
        if ($dao == null) {
            return;
        }

        $sql = "UPDATE {$this->table} SET\n";
        $sql .= "oauth_provider = ?,\n";
        $sql .= "email = ?,\n";
        $sql .= "client_id = ?,\n";
        $sql .= "client_secret = ?,\n";
        $sql .= "tenant_id = ?,\n";
        $sql .= "refresh_token = ?,\n";
        $sql .= "token_updated_date = ?,\n";
        $sql .= "active = ?\n";
        $sql .= "WHERE id = ?\n";

        $params = [
            $dao->oauth_provider,
            $dao->email,
            $dao->client_id,
            $dao->client_secret,
            $dao->tenant_id,
            $dao->refresh_token,
            Dates::sqlDateTime($dao->token_updated_date),
            $dao->active,
            $dao->id,
        ];
        
        $this->db->executeNonQuery($sql, $params);
    }
}