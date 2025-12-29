<div id="main-container-narrow" class="mb-2">
    <h1>Registration - Step 2 of 3</h1>

    <div class="main-inner">
        <p id="track-message" data-init="<?=_WValue($errors ?? '')?>"></p>
        <form id="register-form" method="post" action="register2">
            <?=_csrf()?>
            <input type="hidden" name="postback" value="1" />
            <p>
                <label>Enter a username</label><br>
                <input type="text" id="username" name="username" class="w-full" value="<?= _WValue($username) ?>" maxlength="30" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
            </p>

            <p class="mt-1">
                <label>Enter your email address</label><br>
                <input type="email" id="email" name="email" class="w-full" value="<?= _WValue($email) ?>" maxlength="64" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
            </p>

            <p class="mt-1">
                <label>Preferred nickname (optional)</label><br>
                <input type="text" id="nickname" name="nickname" class="w-full" value="<?= _WValue($nickname) ?>" maxlength="30" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
            </p>

            <p class="mt-1">
                <label>Enter desired password</label>
            </p>
            <div class="inline">
                <div>
                    <input type="password" id="password" name="password" class="textbox" value="" style="width:190px;"><br>
                    <div id="password-char-count" class="text-smaller" style="color:#999"># chars: 0</div>
                    <div id="password-message" class="text-smaller field-message"></div>
                </div>
                <div id="password-meter" class="center" style="width:100px;">
                </div>
            </div>

            <p class="mt-1">
                <label>Re-type password</label>
            </p>
            <div class="inline">
                <div>
                    <input type="password" id="retype" name="retype" class="textbox" value="" style="width:190px;">
                    <div id="retype-message" class="text-smaller field-message"></div>
                </div>
                <div id="retype-error"></div>
            </div>

            <div id="password-policy" class="mt-1">
            </div>

            <p class="submit">
                <button type="button" id="prev-btn" class="button">&laquo; Prev</button>
                <button type="button" id="next-btn" class="button">Next &raquo;</button>
            </p>
        </form>
    </div>
</div>

<script src="<?=_asset('js/shared/PasswordEvaluator.js')?>"></script>
<script src="<?= _asset('js/views/Register2.js') ?>"></script>