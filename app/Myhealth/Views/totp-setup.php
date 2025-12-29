<div class="qrcode center">
    <h1>TWO-FACTOR AUTHENTICATION SETUP</h1>
    <div id="instructions">
        <p class="mb-1">
            Using Google Authenticator, Microsoft Authenticator, FreeOTP or any other TOTP (Time-based One Time Password) authenticators on your phone or tablet,
            scan the following QR Code.
        </p>
        <p class="mb-1">	
            If you don't have an authenticator app on your device, we recommend installing one of these:<br>
            <div class="clearfix" style="width:300px;margin:1em auto;">
                <div class="left-side center" style="width:50%">
                    <img src="assets/images/google-authenticator.svg" width="48" height="48"><br>
                    <span class="nowrap" style="font-size:12px;">Google Authenticator</span>
                </div>
                <div class="left-side center" style="width:50%">
                    <img src="assets/images/microsoft-authenticator.svg" width="48" height="48"><br>
                    <span class="nowrap" style="font-size:12px;">Microsoft Authenticator</span>
                </div>
            </div>
            <br>
        </p>
        <p class="mb-1">
            Scan the QR code with the authentication app. Once the scan is complete, it will appear as <strong><?=_W($totpLabel)?></strong> in your app. Enter the code below to confirm it's working.
        </p>
    </div>
    <img src="<?=$qrCode?>">
    <br>
    <p class="mb-1">If you can't scan the code, copy and paste the key into your Authenticator app</p>
    <div class="mb-1" style="display:grid;grid-template-columns:auto 24px;background-color:#ddd;color:#333;border-radius:6px;">
        <input type="text" disabled class="secret" style="font-family:'Courier New', Courier, monospace;border:none;height:100%;width:100%;padding:4px 4px 4px 8px;" value="<?=_session('OTP_SECRET')?>">
        <p style="border-left:1px solid #999;padding:4px 8px 4px 4px;text-align:center;"><i class="fa fa-copy" title="Copy to clipboard"></i></p>
    </div>
    <div id="copied-notify">Copied!</div>
    <p class="mb-1">Enter the code from your authenticator app, then click Verify</p>

    <p id="errormsg" class="errormsg"></p>

    <div class="code-entry">
        <input type="number" name="code" min="0" max="999999"/>
    </div>
    <button type="button" id="verify-btn" class="button">Verify</button>
</div>

<script src="<?=_asset('js/views/TOTP.js')?>"></script>


