<?php

namespace Myhealth\Classes;

/**
 * Respond with JSON for ajax requests.
 * Each response will exit the PHP script, so it
 * should be the last piece of code written for
 * a given response path.
 */
class AjaxResponse
{
    /**
     * Success response.
     * {"response":"ok"}
     */
    public static function success()
    {
        echo '{"response":"ok"}';
        exit;
    }

    /**
     * Error response.
     * {"error":"some error message"}
     * 
     * @param mixed $msg
     */
    public static function error(mixed $msg)
    {
        echo json_encode(['error'=>$msg]);
        exit;
    }

    /**
     * Normal response
     * {"response":$someData}
     * 
     * @param mixed $response
     */
    public static function response(mixed $response)
    {
        echo json_encode(['response'=>$response]);
        exit;
    }

    /**
     * Sends raw data
     * 
     * @param mixed $response
     */
    public static function raw(mixed $response)
    {
        echo (is_object($response) || is_array($response) ? json_encode($response) : $response);
        exit;
    }

    /**
     * Specific error message: {"error":"Missing parameter(s)"}
     */
    public static function missingParameters()
    {
        self::error('Missing parameter(s)');
        exit;
    }
}
