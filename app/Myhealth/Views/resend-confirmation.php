<div id="main-container-narrow">
    <div class="main-inner">
        <?php if ($errorMsg != "") { ?>
            <p class="errormsg"><?=_W($errorMsg)?></p>
        <?php } else { ?>
            <div>
                <p class="mb-1">Your registration confirmation email has been resent to <?= _W(obfuscateEmail($accountDao->Email)) ?>.</p>
                <p>
                    Click "Confirm email" in that email to confirm your address and
                    complete your registration. Be sure to check your junk or spam folder if
                    the email doesn't appear in your inbox in the next few minutes.
                </p>
            </div>
        <?php } ?>

        <p class="submit mt-1">
            <button type="button" class="button" onclick="redirect('login')">OK</button>
        </p>
    </div>
</div>