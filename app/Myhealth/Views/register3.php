<div id="main-container-narrow" class="mb-2">
    <h1>Registration - Step 3 of 3</h1>
    <div class="well mb-1">
        <p>Please confirm your information to complete your registration.</p>
        <p>
            You will receive an email that simply confirms your email address. Once you click
            on the link in that email, your account will be activated.
        </p>
    </div>

    <div class="main-inner">
        <p id="track-message"><?=_W($errorMsg ?? '')?></p>
        <p class="mb-1">
            Please confirm your information to complete your registration.
        </p>

        <div class="registerconfirmelement">Username:</div>
        <div class="registerconfirmvalue"><label><?= _W($username) ?></label></div>
        <div class="clear"></div>

        <div class="registerconfirmelement">Member ID:</div>
        <div class="registerconfirmvalue"><label><?= _W($memId) ?></label></div>
        <div class="clear"></div>

        <div class="registerconfirmelement">Date of Birth:</div>
        <div class="registerconfirmvalue"><label><?= _W($dob) ?></label></div>
        <div class="clear"></div>

        <div class="registerconfirmelement">Nickname:</div>
        <div class="registerconfirmvalue"><label><?= _W($nickname) ?></label></div>
        <div class="clear"></div>

        <div class="registerconfirmelement">Email:</div>
        <div class="registerconfirmvalue"><label><?= _W($email) ?></label></div>
        <div class="clear"></div>

        <p class="submit mt-1">
            <button type="button" id="prev-btn" class="button">&laquo; Prev</button>
            <button type="button" id="confirm-btn" class="button">Confirm</button>
        </p>
    </div>
</div>

<script src="<?= _asset('js/views/Register3.js') ?>"></script>