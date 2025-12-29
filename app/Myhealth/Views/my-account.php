<link rel="stylesheet" type="text/css" href="assets/css/switch.css">
<style>
    .switch-setting {
        display: flex;
        align-items: center;
        height: 36px;
    }

    .switch-setting .setting {
        color: #aaa;
        padding-left: 1em;
        transition-duration: 200ms;
    }

    .switch-setting.on .setting {
        color: black;
    }
</style>

<div id="main-container-narrow" class="mb-2">
    <h1>My Account</h1>

    <div class="main-inner">
        <p id="track-message"></p>
        <div class="mb-2">
            <label>Username: <strong><?=_W($accountDao->Username)?></strong></label>
        </div>
        <div>
            <div><label>My email address</label></div>
            <input type="text" id="email" class="w-full" value="<?=_WValue($accountDao->Email)?>" maxlength="64" autocapitalize="off">
        </div>
        <div>
            <div><label>My nickname</label></div>
            <input type="text" id="nickname" class="w-full" value="<?=_WValue($accountDao->Nickname)?>" maxlength="30">
        </div>
        <?php if ($isMFAFeatureEnabled) { ?>
            <div class="switch-setting<?=($accountDao->MFAEnabled == 1 ? ' on' : '')?>">
                <label class="switch<?=($accountDao->MFAEnabled == 1 ? ' on' : '')?>">
                    <input type="checkbox" id="mfa" class="sr-only" value="1"<?=($accountDao->MFAEnabled == 1 ? CHECKED : '')?>> 
                    <span class="slider">
                        <span class="dot"></span>
                    </span>
                </label>
                <div class="setting">MFA</div>
            </div>
        <?php } ?>
        <p class="submit">
            <button type="button" class="button" onclick="window.location.href='home';">Cancel</button>
            <button type="button" class="button" id="update-btn">Update</button>
        </div>
        <div class="pt-1">
            <a href="change-password">Change your password</a>
        </div>
    </div>

    <?php if (count($accountMembers) > 0) { ?>
        <h2 class="mt-2">Linked Members</h2>
        <div class="main-inner">
            <form method="POST" name="linked-accounts-form" action="my-account.php">
                <?= _csrf() ?>
                <?php
                foreach($accountMembers as &$accountMember) {
                    echo _W($accountMember->MBR_last_name.', '.$accountMember->MBR_first_name).'<br>';
                }
                ?>
                <p class="submit">
                    <input type="hidden" name="cmd" value="link" />
                    <button type="button" class="button" id="add-btn">Add</button>
                </p>
            </form>
        </div>
    <?php } ?>
</div>

<script src="<?= _asset('js/views/MyAccount.js') ?>"></script>