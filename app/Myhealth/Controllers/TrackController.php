<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Daos\CiGlucoseDAO;
use Myhealth\Daos\CiLabResultDAO;
use Myhealth\Daos\CiVitalStatsDAO;
use Myhealth\Models\PatientModel;
use Myhealth\Models\CiGlucoseModel;
use Myhealth\Models\CiLabResultModel;
use Myhealth\Models\CiVitalStatsModel;

class TrackController
{
    public function trackVitals()
    {
        $memberId = _session('loggedInMemberId');
        $ciVitalStatsDao = (new CiVitalStatsModel())->getMostRecentForMember($memberId);
        $height = $ciVitalStatsDao->height ?? 0;

        $passInData = [
            'height' => (int) $height,
        ];

        View::render('track-vitals', 'Track Vital Stats', $passInData);
    }

    public function saveVitals()
    {
        $memberId = _session('loggedInMemberId');

        try {
            $patientId = (new PatientModel())->getIdForMember($memberId);
            if ($patientId == 0) {
                Logger::error('Could not find patient id for member');
                AjaxResponse::error('Could not save vitals; unknown error.');
            }

            $weight = (float) Request('weight');
            $height = 0;
            $feet = (int) Request('feet');
            $inches = (int) Request('inches');
            
            if ($feet > 0) {
                $height = $feet * 12 + $inches;
            }

            $bmi = 0.0;
            if ($height > 0 && $weight > 0) {
                $bmi = round(($weight / ($height * $height)) * 703, 2);
            }

            $ciVitalStatsDao = new CiVitalStatsDAO();
            $ciVitalStatsDao->patient_id = $patientId;
            $ciVitalStatsDao->qc_member_id = $memberId;
            $ciVitalStatsDao->date = Dates::datePortion(Request('vitals_date'));
            $ciVitalStatsDao->bp_systolic = (int) Request('systolic');
            $ciVitalStatsDao->bp_diastolic = (int) Request('diastolic');
            $ciVitalStatsDao->weight = $weight;
            $ciVitalStatsDao->height = $height;
            $ciVitalStatsDao->bmi = $bmi;
            $res = (new CiVitalStatsModel())->save($ciVitalStatsDao);

            if ($res) {
                LogEvent(Event::EVENT_TRACK_VITAL_STATS, _session('loggedin'), '');
                AjaxResponse::success();
            }

            AjaxResponse::error('Could not save vitals to database. Operation failed.');
        }
        catch(\Exception $e) {
            AjaxResponse::error('Could not save vitals to database');
        }
    }

    public function trackGlucose()
    {
        View::render('track-glucose', 'Track Glucose');
    }

    public function saveGlucose()
    {
        $memberId = _session('loggedInMemberId');

        try {
            $patientId = (new PatientModel())->getIdForMember($memberId);
            if ($patientId == 0) {
                Logger::error('Could not find patient id for member');
                AjaxResponse::error('Could not save glucose; unknown error.');
            }

            $ciGlucoseDao = new CiGlucoseDAO();
            $ciGlucoseDao->patient_id = $patientId;
            $ciGlucoseDao->qc_member_id = $memberId;
            $ciGlucoseDao->diabetes_type = Request('diabetes_type');
            $ciGlucoseDao->reading_date = Dates::datePortion(Request('reading_date'));
            $ciGlucoseDao->glucose = (int) Request('glucose');
            $ciGlucoseDao->time_of_day = (int) Request('time_of_day');
            $ciGlucoseDao->fasting = (int) Request('fasting');
            $ciGlucoseDao->note = Request('comments');
            $res = (new CiGlucoseModel())->save($ciGlucoseDao);

            if ($res) {
                LogEvent(Event::EVENT_TRACK_GLUCOSE, _session('loggedin'), '');
                AjaxResponse::success();
            }

            AjaxResponse::error('Could not save glucose to database. Operation failed.');
        }
        catch(\Exception $e) {
            AjaxResponse::error('Could not save glucose to database');
        }
    }

    public function trackLabResults()
    {
        View::render('track-lab-results', 'Track Lab Results');
    }

    public function saveLabResults()
    {
        $memberId = _session('loggedInMemberId');

        try {
            $patientId = (new PatientModel())->getIdForMember($memberId);
            if ($patientId == 0) {
                Logger::error('Could not find patient id for member');
                AjaxResponse::error('Could not save lab results; unknown error.');
            }

            $ciLabResultDao = new CiLabResultDAO();
            $ciLabResultDao->patient_id = $patientId;
            $ciLabResultDao->qc_member_id = $memberId;
            $ciLabResultDao->lab_date = Dates::datePortion(Request('lab_date'));
            $ciLabResultDao->ldl = (int) Request('ldl');
            $ciLabResultDao->hdl = (int) Request('hdl');
            $ciLabResultDao->triglycerides = (int) Request('triglycerides');
            $ciLabResultDao->cholesterol = (int) Request('cholesterol');
            $ciLabResultDao->hba1c = (float) Request('hba1c');
            $ciLabResultDao->glucose = (int) Request('glucose');
            $res = (new CiLabResultModel())->save($ciLabResultDao);

            if ($res) {
                LogEvent(Event::EVENT_TRACK_LAB_RESULTS, _session('loggedin'), '');
                AjaxResponse::success();
            }

            AjaxResponse::error('Could not save lab results to database. Operation failed.');
        }
        catch(\Exception $e) {
            AjaxResponse::error('Could not save lab results to database');
        }
    }
}