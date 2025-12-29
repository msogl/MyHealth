<div id="verify-container" data-start="<?=_WValue($startDateTime)?>" style="width:640px;margin:0 auto;">
    <h2>VERIFY CODE</h2>
    <div class="errormsg input-error rounded<?=($displayMsg == '' ? ' hidden' : '')?>" style="width:600px;margin:2em auto 1em auto;padding:1em;">
        <?php if ($displayMsg != '') { ?>
            <p id="display-msg" class="mb-2"><?=_W($displayMsg)?></p>

            <?php if ($showResendLink) { ?>
                <p><button type="button" id="resend-btn" class="button mb-2" style="width:140px;">Send new code</button></p>
            <?php } ?>
            <?php if ($errorMsg == "Too many attempts.") { ?>
                <a href="login">Return to login screen</a>
            <?php } ?>
        <?php } ?>
    </div>

    <p id="instructions" style="padding-top:1em;">
        If you haven't already, you will receive <?=($viasms ? 'a text message' : 'an email')?> shortly containing a code to verify your identity. When you receive it, enter it below. This code will expire in <span id="expire-time">15</span> minutes.<br><br>
    </p>

    <form id="mfa-code-form" method="post" action="mfa-verify">
        <input type="hidden" name="id" value="<?=_WValue(Request('id'))?>">
        <input type="hidden" name="c" value="<?=_WValue(Request('c'))?>">
        <input type="hidden" name="s" value="<?=_WValue(Request('s'))?>">
        <p>Enter the code you received in your <?=($viasms ? 'text message' : 'email')?></p>
        <div class="flex-middle gap-x-4px">
            <input type="number" id="mfa-code" class="mfa-code" name="code" min="0" max="999999">
            <button type="button" id="submit-btn" class="button btn-large">Submit</button>
        </div>
        <?php if ($trustBrowserEnabled) { ?>
        <p class="mt-1">
            <label class="flex-middle gap-x-4px">
                <input type="checkbox" id="remember" name="r" value="1"/>
                <span>Trust this browser for 30 days</span>
            </label>
        </p>
        <?php } ?>
    </form>
</div>

<script src="<?=_asset('js/views/MFAVerify.js')?>"></script>
