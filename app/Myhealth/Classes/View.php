<?php

namespace Myhealth\Classes;

class View
{
    private static $instance;
    private $links = [];

    private function __construct()
    {
        //
    }

    private static function getInstance()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        self::$instance = new View();
        return self::$instance;
    }

    public static function render(string $view, string $title, array $data=[], string $layout = 'layout')
    {
        $inst = self::getInstance();

        $view = VIEWS."/{$view}.php";
        $title = preg_replace("/&#?[a-z0-9]{2,8};/i", '', $title);
        $title = APPNAME.($title !== '' ? "::{$title}" : '');
        
        $data['_appMeta'] = AppMeta::getMeta();
        $data['_links'] = $inst->links;
        $data['_common'] = self::commonData();
        $data['_security'] = self::securitySettings();

        if (!empty($data)) {
            extract($data, EXTR_REFS);
        }

        header('Content-Security-Policy: '.self::contentSecurityPolicy());
        include VIEWS."/layouts/{$layout}.php";

        // Clear links
        $inst->links = [];
    }

    public static function errorPage(?string $errorMsg=null)
    {
        self::render('error-page', 'Error', [ 'errorMsg' => $errorMsg ]);
    }

    public static function component(string $name)
    {
        $path = VIEWS."/components/{$name}.php";
        if (!file_exists($path)) {
            return;
        }

        include $path;
    }

    /**
     * Adds a <link> tag in the <head> section of the document AFTER standard
     * links.
     * Must be called before View::render.
     * 
     * @param string $linkHref - The href portion only of the link tag
     * @return void
     */
    public static function addLink(string $linkHref): void
    {
        $inst = self::getInstance();
        $inst->links[] = _asset($linkHref);
    }

    private static function commonData(): array
    {
        $common = new Common();
        return [
            'LOGO' => $_ENV['SITEURL'].'/'.$common->getConfig('LOGO', ''),
            'COMPANYNAME' => $common->getCompanyName(),
        ];
    }

    private static function securitySettings(): array
    {
        return [
            'gpc_enabled' => $_SERVER['HTTP_SEC_GPC'] ?? 0,
        ];
    }

    private static function contentSecurityPolicy()
    {
        $sources = [
            "default-src 'self'",
            "img-src 'self' data:",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' fonts.googleapis.com 'unsafe-inline'",
            "font-src 'self' data: fonts.gstatic.com",
            "base-uri ".siteUrl(),
        ];

        $policy = implode('; ', $sources);
        return $policy;
    }
}
