<?php

namespace Myhealth\Classes;

class AppMeta
{
    private static $instance;
    private $meta = [];

    private function __construct()
    {
        //
    }

    private static function getInstance()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        self::$instance = new AppMeta();
        return self::$instance;
    }

    public static function add(string $key, mixed $content)
    {
        $inst = self::getInstance();
        $inst->meta[$key] = $content;
    }

    /**
     * Returns app meta data added by the add() function
     * for inclusion in the app-meta meta tag. 
     * 
     * If $asArray is true, returns meta array, otherwise,
     * returns encrypted encrypted json-encoded array as a string.
     * Defaults to false.
     * 
     * @param bool $asArray (default to false)
     * @return string
     */
    public static function getMeta(bool $asArray=false): string|array
    {
        $inst = self::getInstance();

        if ($asArray) {
            return $inst->meta;
        }

        if (count($inst->meta) == 0) {
            return '';
        }

        return EncryptAESMSOGL(json_encode($inst->meta));
    }

    /**
     * Decode the app-meta tag.
     * 
     * Returns associative array from encrypted data.
     * 
     * @param ?string $meta
     * @return array
     */
    public static function decrypt(?string $meta): array
    {
        if (empty($meta)) {
            return [];
        }

        return json_decode(DecryptAESMSOGL($meta), true);
    }
}