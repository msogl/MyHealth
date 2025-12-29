<?php

namespace Myhealth\Controllers;

use Myhealth\Core\Dates;
use Myhealth\Core\Logger;
use Myhealth\Classes\View;
use Myhealth\Classes\Event;
use Myhealth\Classes\Common;
use Myhealth\Models\LabModel;
use Myhealth\Models\PatientModel;
use Myhealth\Classes\AjaxResponse;
use Myhealth\Daos\CiVitalStatsDAO;
use Myhealth\Models\CiGlucoseModel;
use Myhealth\Models\VitalStatsModel;
use Myhealth\Models\CiVitalStatsModel;

class VitalStatsController
{
    public function myVitalStats()
    {
        $errorMsg = "";

        $hasLabs = false;
        $memberId = _session('loggedInMemberId');

        $vitalStatsDaos = (new VitalStatsModel())->getByMember($memberId);

        // By this time, regardless of whether we read via direct database or remote,
        // we will have the number of each category and a records array that has all the
        // data we need. Then, separate into an array for each category.
        $vitalStats = (object) [
            'systolic' => [],
            'diastolic' => [],
            'weight' => [],
            'height' => [],
            'bmi' => [],
            'glucose' => [],
        ];

        // Create data arrays for jqplot. This is a two-dimensional array in the form of [date, value]
        foreach ($vitalStatsDaos as $vitalStatsDao) {
            $vitalStatsDao->VitalsDate = Dates::datePortion($vitalStatsDao->VitalsDate);

            if ($vitalStatsDao->VitalsType == 'BP') {
                if (intval($vitalStatsDao->Value1) > 0) {
                    $vitalStats->systolic[] = [ $vitalStatsDao->VitalsDate, (int) $vitalStatsDao->Value1 ];
                    $vitalStats->diastolic[] = [ $vitalStatsDao->VitalsDate, (int) $vitalStatsDao->Value2 ];
                }
            }
            elseif ($vitalStatsDao->VitalsType == 'WEIGHT') {
                if (intval($vitalStatsDao->Value1) > 0) {
                    $vitalStats->weight[] = [ $vitalStatsDao->VitalsDate, (int) $vitalStatsDao->Value1 ];
                }
            }
            elseif ($vitalStatsDao->VitalsType == 'HEIGHT') {
                if (intval($vitalStatsDao->Value1) > 0) {
                    $vitalStats->height[] = [ $vitalStatsDao->VitalsDate, (int) $vitalStatsDao->Value1 ];
                }
            }
            elseif ($vitalStatsDao->VitalsType == 'BMI') {
                if (floatval($vitalStatsDao->Value1) > 0.0) {
                    $vitalStats->bmi[] = [ $vitalStatsDao->VitalsDate, (float) $vitalStatsDao->Value1 ];
                }
            }
            elseif ($vitalStatsDao->VitalsType == 'GLUCOSE') {
                if (floatval($vitalStatsDao->Value1) > 0.0) {
                    $vitalStats->glucose[] = [ $vitalStatsDao->VitalsDate, (float) $vitalStatsDao->Value1 ];
                }
            }
        }

        $ciGlucoseDaos = [];
        $labs = [];

        $patientId = (new PatientModel())->getIdForMember($memberId);
        if ($patientId == 0) {
            Logger::error("Could not find patient id for member");
        }
        else {
            $labs = (new LabModel())->getCMPLabs($patientId);
            $hasLabs = (count($labs) > 0);
            $ciGlucoseDaos = (new CiGlucoseModel())->getByPatientId($patientId);
        }

        if (count($vitalStats->systolic) == 0 &&
            count($vitalStats->weight) == 0 &&
            count($vitalStats->height) == 0 &&
            count($vitalStats->bmi) == 0 &&
            count($vitalStats->glucose) == 0 &&
            !$hasLabs) {
            $errorMsg = "There are no vital stats on record for you.";
        }

        $passInData = [
            'errorMsg' => $errorMsg,
            'vitalStats' => &$vitalStats,
            'hasLabs' => $hasLabs,
            'labs' => &$labs,
            'ciGlucoseDaos' => &$ciGlucoseDaos,
        ];

        View::render('my-vital-stats', 'My Vital Stats', $passInData);
    }
}