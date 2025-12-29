<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Email;
use Myhealth\Classes\Common;
use Myhealth\Models\MemberModel;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;

class ContactController
{
    public function start()
    {
        View::render('contact', 'Contact');
    }

    /**
     * Send contact.
     * @AJAX
     */
    public function send()
    {
        $subject = Request('subject');

        if ($subject === '') {
            AjaxResponse::error(['elementId' => 'subject']);
        }

        $common = new Common();

        $accountDao = (new AccountModel())->getByUsername(_session('loggedin'));
        $memberDao = (new MemberModel())->getById($accountDao->MemberID);

        $body = "Member ID: {$accountDao->MemberID}\r\n";
        $body .= "Member Name: {$memberDao->MBR_first_name} {$memberDao->MBR_last_name}\r\n";
        $body .= "Email: {$accountDao->Email}\r\n";
        $body .= str_repeat('-', 60)."\r\n";
        $body .= Request('message');

        try {
            (new Email())->send(
                $common->getConfig("EMAIL", "CUSTOMER SERVICE"),
                null,
                null,
                "Patient Portal: {$subject}",
                str_replace("\r\n", "<br>", $body)
            );

            AjaxResponse::success();
        }
        catch(\Exception $e) {
            $errorMsg = 'An error occurred while sending the contact email. Please wait a few minutes, then try again.';
            Logger::error("An error occurred");
            Logger::error("From: ".$accountDao->Email);
            Logger::error("To: ".$common->getConfig('EMAIL', 'CUSTOMER SERVICE'));
            Logger::error($e->getMessage());
            AjaxResponse::error($errorMsg);
        }
    }
}