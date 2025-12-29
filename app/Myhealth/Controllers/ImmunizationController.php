<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Models\PatientModel;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Models\CiImmunizationModel;

class ImmunizationController
{
    public function myImmunizations()
    {
        $memberId = _session('loggedInMemberId');
        $patientId = (new PatientModel())->getIdForMember($memberId);
        if ($patientId == 0) {
            Logger::error('Could not find patient id for member');
        }

        $immunizations = (new CiImmunizationModel())->getForPatient($patientId);

        usort($immunizations, function($a, $b) {
            return strtotime($b->immunization_date) <=> strtotime($a->immunization_date);
        });

        $passInData = [
            'immunizations' => $immunizations,
            'age' => _session('loggedInAge'),
        ];
        
        View::render('my-immunizations', 'My Immunizations', $passInData);
    }

    public function saveImmunization()
    {
        $memberId = _session('loggedInMemberId');

        try {
            $fluShotDate = Request('flu_shot_date');

            if ($fluShotDate == '') {
                AjaxResponse::error('Missing flu shot date');
            }

            $res = (new CiImmunizationModel())->saveFluShot($memberId, $fluShotDate);

            if ($res) {
                LogEvent(Event::EVENT_TRACK_IMMUNIZATION, _session('loggedin'), '');
                AjaxResponse::success();
            }

            AjaxResponse::error('Could not save immunization to database. Operation failed.');
        }
        catch(\Exception $e) {
            AjaxResponse::error('Could not save immunization to database');
        }
    }
}