<div id="main-container" class="mb-2">
    <h1>Track Vital Stats</h1>

    <div class="main-inner">
        <p id="track-message"></p>
        <div class="info-container">
            <div>
                <div>Date</div>
                <div class="nowrap">
                    <input type="date" id="vitals-date" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>Blood Pressure:</div>
                <div class="nowrap">
                    <input type="text" id="systolic" value="" size="4" maxlength="3"> /
                    <input type="text" id="diastolic" value="" size="4" maxlength="3">
                    <br>
                    <label>(Systolic / Diastolic)</label>
                </div>
            </div>
        </div>

        <p class="top-pad1"><strong><em>When is the best time to weigh yourself?</em></strong></p>
        <div>
            <p>There's no truly "right" answer to this, though here's a few things you'll want to consider:</p>
            <ul class="no-indent">
                <li>After you eat a meal, everything you just ate is still with you. Let some time go by, or perhaps consider waiting until the morning.</li>
                <li>Clothes and shoes do add to your weight. Your doctor may weigh you with your shoes off, but clothing can add an additional 1-3 pounds, depending on the material.</li>
                <li>A bladder can hold, on average, 16 fluid ounces comfortably, which is 1 pound. Consider using the facilities before weighing yourself.</li>
            </ul>
        </div>

        <div class="info-container">
            <div>
                <div>Weight:</div>
                <div>
                    <input type="text" id="weight" value="" size="6" maxlength="6">
                    <label>(lbs.)</label>
                </div>
            </div>
            <div>
                <div>Height:</div>
                <div>
                    <input type="text" id="feet" value="" size="2" maxlength="1">
                    <label> (feet)</label>
                    <input type="text" id="inches" value="" size="2" maxlength="2">
                    <label> (inches)</label>
                </div>
            </div>

            <p class="submit">
                <button type="button" id="save-btn" class="button">Save</button>
            </p>
        </div>
    </div>
</div>

<script src="<?= _asset('js/views/TrackVitals.js') ?>"></script>
<script>
    TrackVitals.populateHeight(<?=_W($height)?>);
</script>