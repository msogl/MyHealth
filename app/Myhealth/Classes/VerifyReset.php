<?php

namespace Myhealth\Classes;

class VerifyReset
{
    public static function decrypt(string $encryptedResetInfo): object|bool
    {
        $decryptedResetInfo = DecryptAESMSOGL($encryptedResetInfo);
        if ($decryptedResetInfo === $encryptedResetInfo) {
            return false;
        }

        $resetInfo = json_decode($decryptedResetInfo);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }

        if (empty($resetInfo->u) || empty($resetInfo->c)) {
            return false;
        }



        return $resetInfo;
    }
}