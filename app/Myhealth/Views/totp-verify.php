<div class="qrcode center">
    <div class="round-borders pt-1 pb-1" style="width:380px;margin:0 auto;">
        <div>Enter the One Time Password displayed
            in your Authenticator app for
            <strong><?= _W($totpLabel) ?></strong>
        </div>
        <div class="mb-1">
            <br>
            <div class="clearfix" style="width:300px;margin:0 auto;">
                <div class="left-side center" style="width:50%">
                    <img src="assets/images/google-authenticator.svg" width="48" height="48"><br>
                    <span class="nowrap" style="font-size:12px;">Google Authenticator</span>
                </div>
                <div class="left-side center" style="width:50%">
                    <img src="assets/images/microsoft-authenticator.svg" width="48" height="48"><br>
                    <span class="nowrap" style="font-size:12px;">Microsoft Authenticator</span>
                </div>
            </div>
        </div>

        <p id="errormsg" class="errormsg"></p>

        <form method="POST" autocomplete="off">
            <?=_csrf()?>
            <div class="code-entry">
                <input type="number" name="code" min="0" max="999999">
            </div>
            <?php if ($trustBrowserEnabled) { ?>
                <div class="mt-1 mb-1">
                    <input type="checkbox" id="remember" name="r" value="1">
                    <label for="remember">Trust this browser for 30 days</label>
                </div>
            <?php } ?>
            <button type="button" id="verify-btn" class="button">Verify</button>
        </form>
    </div>
</div>

<script src="<?= _asset('js/views/TOTP.js') ?>"></script>