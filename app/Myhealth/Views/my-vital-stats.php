<style>
    .chart1 {
        width: 400px;
        height: 250px;
    }

    .main-inner {
        border: none;
        margin-bottom: 2em;
    }

    .warnings {
        font-size: larger;
        font-style: italic;
        font-weight: bold;
        color: Red;
        padding: 1em;
        border: 1px solid Red;
        line-height: 1.5em;
    }

    #levels {
        padding: 1em;
        height: 360px;
        text-align: left;
        overflow: auto;
    }

    #levels table {
        border: 1px solid #999;
        border-collapse: collapse;
        background-color: White;
        width: 100%;
    }

    #levels table th,
    #levels table td {
        border: 1px solid #999;
    }

    #levels table th:first-child {
        width: 40%;
    }
</style>

<div id="main-container" class="mb-2">
    <h1>My Vital Stats</h1>
    <div class="no-print">
        <div class="right">
            <button type="button" class="button" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
        </div>

        <?php if ($client == "RPPG" || $client == "RPA") { ?>
            <p class="top-pad1 bottom-pad">
                <strong>Track: </strong>
                <a href="track-vitals">My vitals</a> |
                <a href="track-glucose">Glucose readings</a> |
                <a href="track-lab-results">Lab results</a>
            </p>
        <?php } ?>
    </div>
    <?php if ($errorMsg != "") { ?>
        <p class="error"><?= $errorMsg ?></p>
    <?php } else { ?>
        <?php if (count($vitalStats->weight) > 0) { ?>
            <div class="main-inner">
                <div id="weight-chart" class="chart">
                </div>
            </div>
        <?php } ?>

        <?php if (count($vitalStats->height) > 0) { ?>
            <div class="main-inner">
                <div id="height-chart" class="chart">
                </div>
            </div>
        <?php } ?>

        <?php if (count($vitalStats->bmi) > 0) { ?>
            <div class="main-inner">
                <div id="bmi-chart" class="chart">
                </div>
            </div>
        <?php } ?>

        <?php if (count($vitalStats->systolic) > 0) { ?>
            <br>
            <div class="warnings">
                If you are experiencing dizziness or headaches, please consult your physician immediately.<br>
                If three consecutive BP readings are above the red line, please consult your physician immediately.<br>
            </div>
            <br>
            <div class="main-inner">
                <div id="systolic-chart" class="chart">
                </div>
            </div>
            <div class="main-inner">
                <div id="diastolic-chart" class="chart">
                </div>
            </div>
        <?php } ?>

        <?php if (count($vitalStats->glucose) > 0) { ?>
            <div class="main-inner">
                <div id="glucose-chart" class="chart">
                </div>
            </div>
        <?php } ?>

        <?php if ($hasLabs) { ?>
            <div class="left-side">
                <h3>LAB RESULTS</h3>
            </div>
            <div class="right-side">
                <a href="javascript:void();" onclick="showPopup('popupLevels', 600, 420, true);return false;">Show levels</a>
            </div>
            <div class="clear"></div>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th class="center">Date</th>
                            <th class="center">LDL<br>(mg/dL)</th>
                            <th class="center">HDL<br>(mg/dL)</th>
                            <th class="center">Triglycerides<br>(mg/dL)</th>
                            <th class="center">Cholesterol<br>(mg/dL)</th>
                            <th class="center">Chol/HDL Ratio</th>
                            <th class="center">HbA1c</th>
                            <th class="center">Glucose<br>(mg/dL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labs as &$lab) { ?>
                            <tr>
                                <td class="center"><?= _WDate($lab->lab_date) ?></td>
                                <td class="center"><?= (!_isNEZ($lab->ldl) ? $lab->ldl : '') ?></td>
                                <td class="center"><?= (!_isNEZ($lab->hdl) ? _W($lab->hdl) : '') ?></td>
                                <td class="center"><?= (!_isNEZ($lab->triglycerides) ? _W($lab->triglycerides) : '') ?></td>
                                <td class="center"><?= (!_isNEZ($lab->cholesterol) ? _W($lab->cholesterol) : '') ?></td>
                                <td class="center">
                                    <?php
                                    if (notZero($lab->cholesterol, "n/a") != "n/a" && notZero($lab->hdl, "n/a") != "n/a") {
                                        echo _W(number_format($lab->cholesterol / $lab->hdl, 1));
                                    }
                                    ?>
                                </td>
                                <td class="center"><?= (!_isNEZ($lab->hba1c) ? _W($lab->hba1c) : '') ?></td>
                                <td class="center"><?= (!_isNEZ($lab->glucose) ? _W($lab->glucose) : '') ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <br><br>
        <?php } ?>

        <?php if (!empty($ciGlucoseDaos)) { ?>
            <h3>GLUCOSE READINGS</h3>
            <div class="data-table">
                <table cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th class="center">Date</th>
                            <th class="center">Type</th>
                            <th class="center">Glucose (mg/dL)</th>
                            <th class="center">Time of Day</th>
                            <th class="center">Fasting?</th>
                            <th class="left">Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($ciGlucoseDaos as $ciGlucoseDao) {
                            $diabetesType = match ($ciGlucoseDao->diabetes_type) {
                                'P' => 'Prediabetic',
                                '1' => 'Type 1',
                                '2' => 'Type 2',
                                default => 'Not specified'
                            };

                            $timeOfDay = match ($ciGlucoseDao->time_of_day) {
                                '0' => 'Morning (5am-Noon)',
                                '1' => 'Afternoon (Noon-5pm)',
                                '2' => 'Evening (5pm-7pm)',
                                '3' => 'Evening (5pm-7pm)',
                                default => 'Not specified'
                            }
                        ?>
                            <tr>
                                <td class="top center"><?= _WDate($ciGlucoseDao->reading_date) ?></td>
                                <td class="top center"><?= $diabetesType ?></td>
                                <td class="top center"><?= _isNEZ($ciGlucoseDao->glucose ? _W($ciGlucoseDao->glucose) : '') ?></td>
                                <td class="top center"><?= $timeOfDay ?></td>
                                <td class="top center"><?= ($ciGlucoseDao->fasting == 1 ? "Yes" : "No") ?></td>
                                <td class="top left"><?= _W($ciGlucoseDao->note) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    <?php } ?>
</div> <!-- data-wrapper -->
<br>
<br>
<br>
<br>
<div id="overlay">
    <div id="popupLevels" class="popup">
        <div id="levels" class="content">
            <table id="ldl-level">
                <thead>
                    <tr>
                        <th>LDL</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Less than 100</td>
                        <td>Optimal</td>
                    </tr>
                    <tr>
                        <td>100 - 129</td>
                        <td>Near optimal/above optimal</td>
                    </tr>
                    <tr>
                        <td>130 - 159</td>
                        <td>Borderline high</td>
                    </tr>
                    <tr>
                        <td>160 - 189</td>
                        <td>High</td>
                    </tr>
                    <tr>
                        <td>190 and above</td>
                        <td>Very high</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="hdl-level">
                <thead>
                    <tr>
                        <th>HDL</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>60 and above</td>
                        <td>High; Optimal; associated with lower risk</td>
                    </tr>
                    <tr>
                        <td>Less than 40 in men and less than 50 in women</td>
                        <td>Low; considered a risk factor for heart disease</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="triglycerides-level">
                <thead>
                    <tr>
                        <th>Triglycerides</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Less than 150</td>
                        <td>Normal</td>
                    </tr>
                    <tr>
                        <td>150 - 199</td>
                        <td>Mildly High</td>
                    </tr>
                    <tr>
                        <td>200 - 499</td>
                        <td>High</td>
                    </tr>
                    <tr>
                        <td>500 and above</td>
                        <td>Very High</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="cholesterol-level">
                <thead>
                    <tr>
                        <th>Total Cholesterol</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Less than 200</td>
                        <td>Desirable</td>
                    </tr>
                    <tr>
                        <td>200 - 239</td>
                        <td>Mildly High</td>
                    </tr>
                    <tr>
                        <td>240 and above</td>
                        <td>High</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="cholesterol-ratio-level">
                <thead>
                    <tr>
                        <th>Cholesterol/HDL Ratio</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Less than 5.0</td>
                        <td>Desirable</td>
                    </tr>
                    <tr>
                        <td>3.5</td>
                        <td>Optimum</td>
                    </tr>
                    <tr>
                        <td>5.0 and above</td>
                        <td>High</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="hba1c-level">
                <thead>
                    <tr>
                        <th>HbA1c</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>4% - 5.6%</td>
                        <td>Normal</td>
                    </tr>
                    <tr>
                        <td>5.7% - 6.4%</td>
                        <td>Prediabetes</td>
                    </tr>
                    <tr>
                        <td>6.5% and above</td>
                        <td>Indicates diabetes</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table id="glucose-level">
                <thead>
                    <tr>
                        <th>Glucose</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>70 - 100 mg/dL</td>
                        <td>Normal (fasting)</td>
                    </tr>
                    <tr>
                        <td>101 - 126 mg/dL</td>
                        <td>Prediabetes</td>
                    </tr>
                    <tr>
                        <td>126 mg/dL and above</td>
                        <td>Diabetes</td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size:smaller;font-style:italic;">
                Sources:<br>
                For LDL, HDL, Triglycerides and Total Cholesterol: <a href="http://www.webmd.com/cholesterol-management/guide/understanding-numbers" target="_blank">WebMD</a><br>
                For Cholesterol/HDL Ratio: <a href="http://www.webmd.com/cholesterol-management/finding-the-ideal-cholesterol-ratio" target="_blank">WebMD</a><br>
                For HbA1c: <a href="http://www.mayoclinic.com/health/a1c-test/MY00142/DSECTION=results" target="_blank">Mayo Clinic</a><br>
                For Glucose: <a href="http://bloodsugarlevelsnormal.com/blood-sugar-chart-what-do-the-numbers-mean/" target="_blank">Blood Sugar Levels Normal</a><br>
            </div>
        </div>
        <div class="footer right">
            <button type="button" class="button" onclick="hidePopup('popupLevels');">Close</button>
        </div>
    </div>
</div>

<script type="text/javascript" src="assets/js/jqplot/jquery.jqplot.min.js"></script>
<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasOverlay.min.js"></script>
<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.cursor.min.js"></script>
<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.css">
<script src="<?= _asset('js/views/VitalStats.js') ?>"></script>
<script>
    <?php if (count($vitalStats->weight) > 0) { ?>
        data = <?= json_encode($vitalStats->weight) ?>;
        VitalStats.doChart('weight-chart', data, 'Weight (lbs.)');
    <?php } ?>

    <?php if (count($vitalStats->height) > 0) { ?>
        data = <?= json_encode($vitalStats->height) ?>;
        VitalStats.doChart('height-chart', data, 'Height (inches)');
    <?php } ?>

    <?php if (count($vitalStats->bmi) > 0) { ?>
        data = <?= json_encode($vitalStats->bmi) ?>;
        VitalStats.doChart('bmi-chart', data, 'BMI');
    <?php } ?>

    <?php if (count($vitalStats->systolic) > 0) { ?>
        data = <?= json_encode($vitalStats->systolic) ?>;
        VitalStats.doBPChart('systolic-chart', data, 'Systolic');
        data = <?= json_encode($vitalStats->diastolic) ?>;
        VitalStats.doBPChart('diastolic-chart', data, 'Diastolic');
    <?php } ?>

    <?php if (count($vitalStats->glucose) > 0) { ?>
        data = <?= json_encode($vitalStats->glucose) ?>;
        VitalStats.doGlucoseChart('glucose-chart', data, 'Glucose (mg/dL) - High / low marks are based on fasting glucose levels (100-125mg/dL)');
    <?php } ?>

    VitalStats.debug('after charting');
    VitalStats.debug(VitalStats.plots.length + ' charts');
    $(window).on('resize', function() {
        VitalStats.debug(`resizing ${VitalStats.plots.length} charts`);
        for (var ix = 0; ix < VitalStats.plots.length; ix++) {
            VitalStats.plots[ix].replot({
                resetAxes: true
            });
        }
    });

    if (VitalStats.systolicTrouble  || VitalStats.diastolicTrouble) {
        if (VitalStats.systolicTroubleType == 170)
            alert("You've had at least one episode in the last 90 days where your blood pressure was extremely high (over 170). if (you haven't already, PLEASE consult your physician immediately.");
        else;
        alert("Your last three blood pressure readings were high. if (you haven't already, please consult your physician immediately.");
    }
</script>