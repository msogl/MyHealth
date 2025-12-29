<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Classes\AjaxResponse;

class HeartbeatController
{
    public function heartbeat()
    {
        AjaxResponse::raw([
            'status' => 'UP',
            'timestamp' => Dates::zulu('now')
        ]);
    }
}
