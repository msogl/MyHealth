<?php

namespace Myhealth\Classes;

class Session
{
    public static function get(string $key, ?string $default='')
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key)
    {
        unset($_SESSION[$key]);
    }

    public static function all()
    {
        return $_SESSION;
    }
}