<div id="main-container-narrow">
    <h1>Welcome!</h1>
    <?php if ($errorMsg != "") { ?>
        <p class="error left"><?=$errorMsg?></p>
    <?php } ?>

    <?php if (_session('login_state') != \Myhealth\Classes\LoginState::LOCKED) { ?>
    <div class="main-inner">
        <form method="POST" name="login_form" action="login">
            <input type="hidden" name="token" value="<?=getTokenOnly()?>">
            <p>
                <label for="username">Enter your username</label><br />
                <input type="text" id="username" name="u" value="<?=_WValue($rememberedUser)?>" class="input-text w-full" maxlength="200" tabindex="1" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"/>
            </p>

            <p class="top-pad1">
                <label>Enter your password</label><br />
                <input type="password" name="p" value="" class="input-text w-full" tabindex="10" aria-label="Enter your password"/>
            </p>

            <div class="top-pad1">
                <div class="left-side" style="margin-top:4px;">
                    <input type="checkbox" id="remember" name="remember" value="1"<?=($rememberedUser != '' ? CHECKED: '')?>/>
                    <label for="remember">Remember me</label>
                </div>
                <div class="right-side">
                    <input type="submit" value="Log In" class="button" tabindex="20"/>
                </div>
                <div class="clear"></div>
            </div>

            <p class="top-pad1">
                <a href="forgot-password" tabindex="30">Forgot password</a>
            </p>

            <p class="top-pad1"><strong>Not Registered?</strong></p>
                Registering for an account will let you see your claims
                and referrals at <?=_W($clientName)?>.
                <a href="register" tabindex="40"><strong>Register now</strong></a>
            </p>
        </form>
    </div>
    <?php } ?>
</div>
<?php
if ($_security['gpc_enabled']) {
    component('global-privacy-control');
}
?>

<script type="text/javascript">
    <?php if ($rememberedUser != "") { ?>
    document.login_form?.p.focus();
    <?php }
    else { ?>
    document.login_form?.u.focus();
    <?php } ?>
</script>
