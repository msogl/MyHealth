<div id="main-container-narrow">
    <h1>Change Password</h1>
    <p id="track-message"></p>
    <div class="main-inner">
        <?php if ($reasonMsg != "") { ?>
        <p class="instructions">
            <?=_W($reasonMsg)?>
        </p>
        <?php } ?>

        <p><strong>Enter desired password</strong></p>
        <div class="inline">
            <div>
                <input type="password" id="password" name="password" class="textbox" value="" style="width:190px;"><br>
                <div id="password-char-count" class="text-smaller" style="color:#999"># chars: 0</div>
            </div>
            <div id="password-meter" class="center" style="width:100px;">
            </div>
        </div>

        <p class="mb-1"><strong>Re-type password</strong></p>
        <div class="inline">
            <div>
                <input type="password" id="retype" name="retype" class="textbox" value="" style="width:190px;">
            </div>
            <div id="retype-error"></div>
        </div>

        <div id="password-policy" class="mt-1">
        </div>

        <p class="right control-buttons" style="padding-top:1em;">
            <button type="button" id="cancel-btn" class="button" style="width:120px;">Cancel</button>
            &nbsp;&nbsp;
            <button type="button" id="change-btn" class="button" style="width:120px;">Change</button>
        </p>
    </div>
</div>

<script src="<?=_asset('js/shared/PasswordEvaluator.js')?>"></script>
<script src="<?=_asset('js/views/ChangePassword.js')?>"></script>