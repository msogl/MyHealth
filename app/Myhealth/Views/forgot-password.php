<div id="main-container-narrow" class="mb-2">
    <h1>Reset Password</h1>
    <?php if ($errorMsg != "") { ?>
        <p class="error"><?= _W($errorMsg) ?></p>
    <?php } ?>
    <div class="main-inner">
        <form method="POST" id="reset-password-form" action="forgot-password">
            <?=_csrf()?>
            <input type="hidden" name="req" value="<?= _W(_session('fp-protect')) ?>">
            <p class="instructions">
                Forgot your password? Enter your username below and we'll send
                a code to the email address on file so you can reset it.
            </p>

            <p class="mb-2">
                <label>Enter your username</label><br>
                <input type="text" id="username" name="username" value="" class="w-full" maxlength="200" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
            </p>

            <p class="submit">
                <button type="button" id="cancel-btn" class="button">Cancel</button>
                <button type="button" id="reset-btn" class="button">Reset</button>
            </p>
        </form>
    </div>
</div>

<script src="<?=_asset('js/views/ForgotPassword.js')?>"></script>
