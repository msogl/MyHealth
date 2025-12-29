<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Models\ReferralModel;

class ReferralController
{
    public function myReferrals()
    {
        $referrals = (new ReferralModel())->getByMember(_session('loggedInMemberId'));
        
        $passInData = [
            'referrals' => &$referrals,
            'oCommon' => new Common(),
        ];

        View::render('my-referrals', 'My Referrals', $passInData);
    }

    public function referralDetail()
    {
        $referralNumber = Request('id');

        if ($referralNumber== "") {
            redirect('my-referrals');
            return;
        }

        $referral = (new ReferralModel())->loadReferral($referralNumber, _session('loggedInMemberId'));

        $errorMsg = '';
        $memberName = '';

        if (is_null($referral)) {
            LogEvent(Event::EVENT_REFERRAL_VIEW_FAILED, _session('loggedin'), "Referral #{$referralNumber} does not exist, does not belong to member, or is not approved");
            $errorMsg = "Cannot find specified referral in our system.";
        }
        else {
            LogEvent(Event::EVENT_REFERRAL_VIEW, _session('loggedin'), "Referral #{$referralNumber}");
            $memberName = libFormatName($referral->MBR_last_name, $referral->MBR_first_name, $referral->MiddleInitial, '', 1);
            $referral->CommonStatusDescription = (new Common())->ReferralStatus($referral->Status ?? '');
        }

        View::render('referral-detail', "Viewing Referral #{$referralNumber}", [
            'errorMsg' => $errorMsg,
            'memberName' => $memberName,
            'referralNumber' => $referralNumber,
            'referral' => &$referral,
        ]);
    }

    public function reprint()
    {
        View::render('reprint', 'Reprint', [], 'blank');
    }
}