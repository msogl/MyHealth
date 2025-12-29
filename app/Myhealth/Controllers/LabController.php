<?php

namespace Myhealth\Controllers;

use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Models\LabModel;
use Myhealth\Models\MemberModel;
use Myhealth\Models\AccountModel;
use Myhealth\Models\AccountMembersModel;

class LabController
{
    public function myLabs()
    {
        LogEvent(Event::EVENT_LAB_BROWSE, _session('loggedin'), '');
        $labs = (new LabModel())->getByMember(_session('loggedInMemberId'));
        View::render('my-labs', 'My Labs', [ 'labs' => &$labs ]);
    }

    public function labDetail()
    {
        $labId = Request('id');
        $errorMsg = '';
        if ($labId === '') {
            redirect('my-labs');
            return;
        }

        $labModel = new LabModel();
        $lab = $labModel->load((int) $labId);

        if ($lab === false) {
            LogEvent(Event::EVENT_LAB_VIEW_FAILED, _session('loggedin'), "Lab ID {$labId} either does not exist or does not belong to member");
            $errorMsg = 'Cannot find specified lab result in our system.';
        }
        else {
            LogEvent(Event::EVENT_LAB_VIEW, _session('loggedin'), "Lab ID {$labId}");

            $lastValueType = "zzz";
            $note = '';
            $noteStartIx = 0;

            for($ix=0;$ix<count($lab->labDetails);$ix++) {
                if (($lastValueType == "FT" && $lab->labDetails[$ix]->valueType != "FT")) {
                    $lab->labDetails[$noteStartIx]->valueType = "NOTE";
                    $lab->labDetails[$noteStartIx]->value = $note;
                    $note = "";
                }

                if (($lastValueType == "FT" && $lab->labDetails[$ix]->valueType == "FT")) {
                    $note .= "<br/>".$lab->labDetails[$ix]->value;
                    $lab->labDetails[$ix]->value = "***DELETE***";		// for later
                }

                if (($lab->labDetails[$ix]->valueType == "FT" && $lastValueType != "FT")) {
                    $note = $lab->labDetails[$ix]->value;
                    $noteStartIx = $ix;
                }

                $lastValueType = $lab->labDetails[$ix]->valueType;
            }


            if (count($lab->labDetails) == 0) {
                $errorMsg = 'Lab records are incomplete.';
            }
            else {
                $member = (new MemberModel())->getById(_session('loggedInMemberId'));
                $lab->patientName = $member->FullName;
                $lab->birthDate = $member->BirthDate;
                $lab->payorName = $member->PayorName;
            }

            View::render('lab-detail', 'Viewing Lab Details', [
                'errorMsg' => $errorMsg,
                'orderNumber' => Request('order'),
                'lab' => &$lab,
            ]);
        }
    }
}