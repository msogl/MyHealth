<div id="main-container" class="mb-2">
    <h1>Track Lab Results</h1>
    <div class="main-inner">
        <p id="track-message"></p>
    <p><strong><em>Lab Work</em></strong></p>
    <div>
        <p class="mb-1">While we have access to lab data, it may not be complete. You can enter your own lab results and/or glucose meter reading.</p>

        <div class="info-container">
            <div>
                <div>Lab date:</div>
                <div>
                    <input type="date" id="lab-date"value="" size="10" maxlength="10">
                </div>
            </div>

            <div>
                <div>LDL:</div>
                <div>
                    <input type="text" id="ldl" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>HDL:</div>
                <div>
                    <input type="text" id="hdl" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>Triglycerides:</div>
                <div>
                    <input type="text" id="triglycerides" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>Total Cholesterol:</div>
                <div>
                    <input type="text" id="cholesterol" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>Hemoglobin A1c:</div>
                <div>
                    <input type="text" id="hba1c" value="" size="10" maxlength="10">
                </div>
            </div>
            <div>
                <div>Glucose:</div>
                <div>
                    <input type="text" id="glucose" value="" size="10" maxlength="10">
                </div>
            </div>
        </div>
    </div>

    <p class="submit">
        <button type="button" id="save-btn" class="button">Save</button>
    </p>
</div>

<script src="<?= _asset('js/views/TrackLabResults.js') ?>"></script>