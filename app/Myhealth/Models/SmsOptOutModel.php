<?php

namespace Myhealth\Models;

class SmsOptOutModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->db->setCurrentDB($this->db->CentralDB);
    }

    public function loadByRecipient(string $recipientNumber, string $fromNumber)
    {
        $recipientNumber = preg_replace('/[^0-9]/', '', $recipientNumber);
        $fromNumber = preg_replace('/[^0-9]/', '', $fromNumber);
        $sql = "SELECT * FROM sms_opt_out WITH(NOLOCK) WHERE recipient_number = ? AND from_number = ?";
        $rs = $this->db->execute($sql, [$recipientNumber, $fromNumber]);
        return $this->db->magicWrappers($rs, true);
    }

    public function isOptedOut(string $recipientNumber, string $fromNumber)
    {
        $dao = $this->loadByRecipient($recipientNumber, $fromNumber);
        return (!is_null($dao));
    }

    public function optOut(string $recipientNumber, string $fromNumber)
    {
        $dao = $this->loadByRecipient($recipientNumber, $fromNumber);

        if (is_null($dao)) {
            $recipientNumber = preg_replace('/[^0-9]/', '', $recipientNumber);
            $fromNumber = preg_replace('/[^0-9]/', '', $fromNumber);
            $sql = "INSERT INTO sms_opt_out (recipient_number, from_number, opt_out_date) VALUES (?, ?, GETDATE())";
            $this->db->executeNonQuery($sql, [$recipientNumber, $fromNumber]);
        }
    }

    public function removeOptOut(string $recipientNumber, string $fromNumber)
    {
        $recipientNumber = preg_replace('/[^0-9]/', '', $recipientNumber);
        $fromNumber = preg_replace('/[^0-9]/', '', $fromNumber);
        $sql = "DELETE FROM sms_opt_out WHERE recipient_number = ? AND from_number = ?";
        $this->db->executeNonQuery($sql, [$recipientNumber, $fromNumber]);
    }
}