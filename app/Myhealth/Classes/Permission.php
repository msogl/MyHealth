<?php

namespace Myhealth\Classes;

class Permission
{
    public static function isSuperAdmin(): bool
    {
        return (defined('SUPERADMIN') && strcasecmp(_session('loggedin'), SUPERADMIN) === 0);
    }

    public static function isAdmin(?string $userId=null): bool
    {
        if (is_null($userId)) {
            $userId = _session('loggedin');
        }

        return (new Common())->getConfig('ADMIN', $userId) === 'true';
    }

    public static function isDeveloper(?string $userId=null): bool
    {
        if (empty($GLOBALS['DEVELOPERS'])) {
            return false;
        }

        if (is_null($userId)) {
            $userId = _session('loggedin');
        }

        $developers = (is_array($GLOBALS['DEVELOPERS']) ? $GLOBALS['DEVELOPERS'] : [$GLOBALS['DEVELOPERS']]);
        foreach($developers as $developer) {
            if (strcasecmp($userId, $developer) == 0) {
                return true;
            }
        }
        
        return false;
    }
}