<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Models\ClaimModel;
use Myhealth\Models\AccountModel;
use Myhealth\Models\AccountMembersModel;

class ClaimController
{
    public function myClaims()
    {
        $claims = (new ClaimModel())->getByMember(_session('loggedInMemberId'));

        $passInData = [
            'claims' => &$claims,
            'oCommon' => new Common(),
        ];
        
        View::render('my-claims', 'My Claims', $passInData);
    }

    public function claimDetail()
    {
        $accountDao = (new AccountModel())->getByUsername(_session('loggedin'));
        $claimNumber = Request('id');

        if ($claimNumber == '') {
            redirect("my-claims");
            return;
        }

        $memberId = DecryptAESMSOGL(base64_decode(Request('mid')));
        $validMember = false;

        // Validate that the member passed in is either the member themself, or a linked member
        if ($memberId != '') {
            if ($memberId == $_SESSION["loggedInMemberId"]) {
                $validMember = true;
            }
            elseif ((new Common())->isFeatureEnabled('LINKED MEMBERS')) {
                $accountMembersModel = new AccountMembersModel();
                if ($accountMembersModel->hasMember($accountMembersModel->getMembers2($accountDao->AccountID), $memberId)) {
                    $validMember = true;
                }
            }
        }

        $errorMsg = '';
        $memberName = '';

        if ($validMember) {
            $details = (new ClaimModel())->getDetails($claimNumber, $memberId);

            $errorMsg = '';
            if (count($details) == 0) {
                LogEvent(Event::EVENT_CLAIM_VIEW_FAILED, _session('loggedin'), "Claim #{$claimNumber} either does not exist or does not belong to member");
                $errorMsg = "Cannot find specified claim in our system.";
            }
            else {
                LogEvent(Event::EVENT_CLAIM_VIEW, _session('loggedin'), "Claim #{$claimNumber}");
                $claim = &$details[0];
                $memberName = libFormatName($claim->MBR_last_name, $claim->MBR_first_name, $claim->MiddleInitial, '', 1);
            }
        }
        else {
            LogEvent(Event::EVENT_CLAIM_VIEW_FAILED, _session('loggedin'), "Member #{$memberId} not valid for user");
            $errorMsg = "You do not have permissions to view this claim.";
        }

        View::render('claim-detail', "Viewing Claim #{$claimNumber}", [
            'errorMsg' => $errorMsg,
            'memberName' => $memberName,
            'claimNumber' => $claimNumber,
            'claim' => $details[0] ?? null,
            'details' => &$details,
        ]);
    }
}