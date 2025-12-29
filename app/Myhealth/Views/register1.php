<div id="main-container-narrow">
    <h1>Registration - Step 1 of 3</h1>
    <div class="main-inner">
        <p id="track-message"><?=_W($errorMsg ?? '')?></p>
        <form id="register-form" method="post" action="register1">
            <?= _csrf() ?>
            <div class="mb-1">
                <label for="first">Enter your first name</label><br>
                <input type="text" id="first" name="first" class="w-full" value="<?= _WValue($first) ?>">
            </div>

            <div class="mb-1">
                <label for="memid">Enter your member ID</label> <i id="memberid-help" class="fa fa-question-circle"></i>
                <input type="text" id="memid" name="memid" class="w-full" value="<?= _WValue($memId) ?>">
            </div>

            <div class="mb-1">
                <label for="dob">Enter your date of birth<br><em class="text-smaller">You must be at least 13 years old to register</em></label><br>
                <input type="date" id="dob" name="dob" class="w-full" max="<?= date('Y-m-d', strtotime('13 years ago')) ?>" value="<?= _WValue($dob) ?>">
            </div>

            <div class="submit">
                <input type="button" id="cancel-btn" value="Cancel" class="button">
                <input type="button" id="next-btn" value="Next &raquo;" class="button">
            </div>
        </form>
    </div>

    <div class="well mt-2">
        <div>
            <strong>Need help with your member ID?</strong><br><br>
            Sometimes, the member ID on your card isn't exactly what is needed
            to register. Select your health insurance below and we'll try to
            help you locate your member ID.
        </div>
        <select id="insurance">
            <option value="">-- Select --</option>
            <option value="BCBSIL">Blue Cross Blue Shield of Illinois</option>
            <option value="HUMANA">Humana</option>
            <?php if ($client == "RPA") { ?>
                <option value="MERIDIAN">Meridian Health Plan</option>
                <option value="WELLCARE">Wellcare</option>
                <option value="CIGNA">Cigna / Healthspring</option>
            <?php } ?>
        </select>
    </div>
</div>

<div id="membercard" class="hidden pt-1">
    <img id="membercardimg" src="" alt="Member ID Card" style="max-width:100%;">
</div>

<script src="<?= _asset('js/views/Register1.js') ?>"></script>
