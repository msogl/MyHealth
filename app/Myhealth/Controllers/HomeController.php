<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Common;
use Myhealth\Models\FyiModel;
use Myhealth\Models\GoalModel;
use Myhealth\Models\ClaimModel;
use Myhealth\Models\MaterialModel;
use Myhealth\Models\ProviderModel;
use Myhealth\Models\ReferralModel;

class HomeController
{
    public function index()
    {
        if (!authenticated()) {
            redirect('login');
            return;
        }
        
        $memberId = _session('loggedInMemberId');

        $fyiMessages = (new FyiModel())->getMessages($memberId);

        foreach($fyiMessages as &$msg) {
            $msg->Content = str_replace(chr(13), "", $msg->Content);
            $msg->Content = str_replace(chr(10), "<br>", $msg->Content);
            $msg->Content = str_replace("[b]", "<strong>", $msg->Content);
            $msg->Content = str_replace("[/b]", "</strong>", $msg->Content);
            $msg->Content = str_replace("[i]", "<em>", $msg->Content);
            $msg->Content = str_replace("[/i]", "</em>", $msg->Content);
            $msg->Content = str_replace("[u]", "<u>", $msg->Content);
            $msg->Content = str_replace("[/u]", "</u>", $msg->Content);
            $msg->Content = str_replace(chr(9), "", $msg->Content);
            $msg->StartDate = _WDate($msg->StartDate);
            $msg->EndDate = _WDate($msg->EndDate);
            unset($msg->Payor);
        }

        // Get PCP info for COVID-19
        // 05/12/2023 JLC Disabled
        if (false) {
            $pcpNote = (new ProviderModel())->getPcpNote($memberId, 'COVID-19');
        }

        $claims = (new ClaimModel())->getByMember($memberId);
        $referrals = (new ReferralModel())->getByMember($memberId);

        $passInData = [
            'client' => client(),
            'fyiMessages' => $fyiMessages,
            'pcpNote' => null,
            'claims' => &$claims,
            'referrals' => &$referrals,
            'claimLimit' => 3,
            'referralLimit' => 3,
            'oCommon' => new Common(),
        ];

        // 06/12/14 JLC If the member is part of a case, get the goals
        // 03/30/16 JLC and materials
        if (!empty($_ENV['CLINICAL'])) {
            $passInData['goalDaos'] = (new GoalModel())->getByMember($memberId);
            $passInData['materials'] = (new MaterialModel())->getByMember($memberId);
        }

        View::render('home', 'Home', $passInData);
    }

    public function newMember()
    {
        $client = strtolower(client() ?? '');

        if ($client == '') {
            $msg = __FUNCTION__.': missing client';
            Logger::error($msg);
            Logger::PushBullet('Missing client', $msg);
            View::errorPage('Sorry, the new member packet is missing. This error has been logged and the developers have been notified.');
        }

        $view = "new-member-{$client}";
        View::render($view, 'Welcome New Member!', []);
    }
}
