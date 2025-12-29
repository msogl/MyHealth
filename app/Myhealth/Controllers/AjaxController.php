<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Models\MemberModel;
use Myhealth\Models\AccountModel;
use Myhealth\Classes\AjaxResponse;

class AjaxController
{
    public function enableDisableAccount()
    {
        $aid = DecryptAESMSOGL(Request('aid'));
        $cmd = Request('status');

        if (!in_array($cmd, ['enable', 'disable'])) {
            AjaxResponse::error('Unexpected error');
        }

        $accountModel = new AccountModel();
        $accountDao = $accountModel->getById($aid);

        if (is_null($accountDao)) {
            AjaxResponse::error('User not found');
        }

        if ($cmd === 'enable') {
            $accountModel->activate();
        }
        else {
            $accountModel->deactivate();
        }

        // Re-load the account to get the actual active status, in the
        // unlikely event Activate or Deactivate fails
        $accountDao = $accountModel->getById($aid);
        AjaxResponse::response(['isActive'=>($accountDao->Active == 1)]);
    }

    /**
     * Retrieves member info for a given member id
     * Used in user-edit
     */
    public function getMemberInfo()
    {
        $memberId = Request('mid');
        if ($memberId == '') {
            AjaxResponse::error('Missing parameter(s)');
        }

        if (strlen($memberId) > 20) {
            AjaxResponse::error('This does not appear to be a valid member ID');
        }

        $memberDao = (new MemberModel())->getById($memberId);
        if (_isNE($memberDao)) {
            AjaxResponse::error('Member not found');
        }

        AjaxResponse::response([
            'name'=>$memberDao->MBR_last_name.', '.$memberDao->MBR_first_name,
            'dob'=>Dates::datePortion($memberDao->BirthDate),
            'raw'=>json_encode($memberDao),
        ]);
    }
}