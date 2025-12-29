<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\Common;
use Myhealth\Classes\Event;
use Myhealth\Core\FileSecurity;
use Myhealth\Core\Html;
use Myhealth\Core\Logger;

class DownloadController
{
    public function download()
    {
        $type = Request('type');
        $path = DecryptAESMSOGL(Request('url'));
        $download = (Request('download') === '1');
        $defaultErrorMessage = 'Cannot download file';
        $allowedPaths = [];

        if ($type === 'material') {
            $materialsPath = (new Common())->getConfig('MATERIALS PATH', '');
            if ($materialsPath == '') {
                Logger::error('MATERIALS PATH not configured');
                die($defaultErrorMessage);
            }

            if (APP_ENVIRONMENT === 'local') {
                $materialsPath = str_replace("C:\\WEB_CONTENT\\TESTLOCAL_WEB_CONTENT", "C:\\dev", $materialsPath);
            }

            $path = fixpath("{$materialsPath}/{$path}");
            $allowedPaths = [$materialsPath];
        } else {
            $this->logFailure("Invalid download type: {$type}");
            die($defaultErrorMessage);
        }

        $path = FileSecurity::sanitizePath($path, $allowedPaths);
        if ($path === false) {
            $this->logFailure("Path sanitization failure for: {$path}");
            die($defaultErrorMessage);
        }

        $filename = basename($path);

        if (!isAllowedExtension($filename)) {
            $this->logFailure("Disallowed extension for {$filename}");
            die($defaultErrorMessage);
        }

        // Google Chrome disallows commas
        $path = str_replace(',', '_', $path);

        if (!file_exists($path)) {
            $this->logFailure("File not found: {$path}");
            die($defaultErrorMessage);
        }

        $fileHandle = FileSecurity::safeFopen($path, 'r');

        // Non-PDF files must be downloaded
        if (!$download && pathinfo(strtolower($filename), PATHINFO_EXTENSION) !== 'pdf') {
            $download = true;
        }

        Html::contentType($filename, $download);

        while (!feof($fileHandle)) {
            print(fread($fileHandle, 1024 * 8));
            flush();
        }

        fclose($fileHandle);

        LogEvent(Event::EVENT_FILE_DOWNLOAD_SUCCESS, _session('loggedin'), $path);
    }

    private function logFailure(string $msg)
    {
        Logger::error($msg);
        LogEvent(Event::EVENT_FILE_DOWNLOAD_FAILED, _session('loggedin'), $msg);
    }
}
