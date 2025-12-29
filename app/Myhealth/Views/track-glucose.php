
<div id="main-container" class="mb-2">
    <h1>Track Glucose</h1>
    <div class="main-inner">
        <p id="track-message"></p>

        <p><strong><em>Glucose Meter Reading</em></strong></p>
        <p class="mb-1">Use this form to track your glucose level from your own glucose meter.</p>
        <div class="info-container">
            <div>
                <div>I am:</div>
                <div>
                    <label>
                        <input type="radio" name="diabetes_type" value="P">
                        Prediabetic
                    </label>
                    <span class="medium-width-or-above">&nbsp;&nbsp;</span>
                    <span class="small-width"><br/></span>
                    <span class="narrow-width"><br/></span>
                    <label>
                        <input type="radio" name="diabetes_type" value="1">
                        Type 1
                    </label>
                    <span class="medium-width-or-above">&nbsp;&nbsp;</span>
                    <span class="small-width"><br/></span>
                    <span class="narrow-width"><br/></span>
                    <label>
                        <input type="radio" name="diabetes_type" value="2">
                        Type 2
                    </label>
                </div>
            </div>

            <div>
                <div>Reading date:</div>
                <div>
                    <input type="date" id="reading-date" value="" size="10" maxlength="10">
                </div>
            </div>

            <div>
                <div>Glucose:</div>
                <div>
                    <input type="text" id="glucose" value="" size="10" maxlength="10">
                    mg/dL
                </div>
            </div>

            <div>
                <div>Time of day:</div>
                <div>
                    <select id="time-of-day">
                        <option value="">-- select --</option>
                        <option value="0">Morning (5am-Noon)</option>
                        <option value="1">Afternoon (Noon-5pm)</option>
                        <option value="2">Evening (5pm-7pm)</option>
                        <option value="3">Night (7pm-5am)</option>
                    </select>
                </div>
            </div>

            <div>
                <div>Select:</div>
                <div>
                    <label>
                        <input type="radio" name="fasting" value="1">
                        Fasting
                    </label>
                    <span class="medium-width-or-above">&nbsp;&nbsp;</span>
                    <span class="small-width"><br></span>
                    <span class="narrow-width"><br></span>
                    <label>
                        <input type="radio" name="fasting" value="0">
                        Non-fasting
                    </label>
                </div>
            </div>

            <div>
                <div>Comments:</div>
            </div>
            <textarea id="comments" style="width:100%;height:100px;" placeholder="Enter any relevent comments here"></textarea>
        </div>

        <p class="submit">
            <button type="button" id="save-btn" class="button">Save</button>
        </p>
    </div>
</div>

<script src="<?= _asset('js/views/TrackGlucose.js') ?>"></script>