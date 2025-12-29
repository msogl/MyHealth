<div id="main-container" class="mb-2">
    <div style="display:flex;justify-content:space-between;align-items:baseline;">
        <h1 style="display:flex;justify-content:flex-start;align-items:baseline;column-gap:1em;">
            <div>User Admin</div>
            <div id="active-indicator" class="text-smaller<?=($accountDao->Active == 1 ? ' hidden' : '')?>">(Account disabled)</div>
        </h1>
        <div>
            <button type="button" id="active-toggle-btn" class="button"><?=($accountDao->Active == 1 ? 'Disable' : 'Enable')?></button>
        </div>
    </div>
    <div class="main-inner">
        <p id="track-message"></p>
        <div class="edit-container" data-aid="<?=_WValue($encryptedAccountId)?>">
            <div class="font-bold">Username:</div>
            <div><?=_W($accountDao->Username)?></div>
            <div class="font-bold">Email:</div>
            <input type="text" id="email" value="<?=_WValue($accountDao->Email)?>" maxlength="64" size="40" />
            <div></div>
            <div class="smaller"><em>Confirmed? <?=($accountDao->Confirmed == 1 ? "Yes" : "No")?></em></div>
            <div class="font-bold">First name:</div>
            <input type="text" id="firstname" value="<?=_WValue($accountDao->Firstname)?>" maxlength="30" />
            <div class="font-bold">Nickname:</div>
            <input type="text" id="nickname" value="<?=_WValue($accountDao->Nickname)?>" maxlength="30" />
            <div class="font-bold">Member ID:</div>
            <input type="text" id="memberid" value="<?=_WValue($accountDao->MemberID)?>" maxlength="20" />
            <div class="font-bold">Member Name:</div>
            <div id="membername"><?=_W($memberDao == null ? '<member not found>' : $memberDao->MBR_last_name.', '.$memberDao->MBR_first_name)?></div>
            <div class="font-bold">Birthdate:</div>
            <div id="dob"><?=($memberDao == null ? '' : _WDate($memberDao->BirthDate))?></div>
            <div></div>
            <div class="font-bold">
                <label class="flex-middle gap-x-4px py-1">
                    <input type="checkbox" id="changenext" value="1"<?=($accountDao->ChangeNext == 1 ? CHECKED : '')?>>
                    <div>Require password change next login</div>
                </label>
            </div>
        </div>

        <?php if ($accountDao->MFAEnabled == 1 && !_isNE($accountDao->OTPSecret)) { ?>
        <p class="top-pad1">
            If the user has MFA enabled and has lost their device, and wishes to reset MFA
            so they can set it up again on a new device, have them first confirm their
            member ID and birthdate. BCBS members will not have the last two digits of the
            member ID, so those can be ignored. Clicking the "Revoke MFA" button below will
            clear MFA tokens from their account and will force a setup on their next login.
        </p>
        <?php } ?>

        <p class="top-pad1">
            For security purposes, passwords cannot be changed or reset by an administrator.
            Passwords are not sent via email. The only way to reset a password is to use the
            Forgot Password function on the login page. The email address must be correct and
            confirmed for that to work.
        </p>
        
        <p class="top-pad1">
            <strong>NOTE: If the email address is changed, the account will be marked as
            NOT CONFIRMED, and a confirmation email will be sent upon saving to the new email
            address. The user will need to select the link in the email to have the account
            reconfirmed. While the account is marked not confirmed, the user will not be able
            to log in.</strong>
        </p>

        <div class="top-pad1" style="display:flex;justify-content:space-between;">
            <div>
                <?php if ($accountDao->MFAEnabled == 1 && !_isNE($accountDao->OTPSecret)) { ?>
                <button type="button" id="revoke-mfa-btn" class="button button-danger" style="width:auto;padding-left:8px;padding-right:8px;">Revoke MFA</button>
                <?php } ?>
            </div>
            <div>
                <button type="button" id="cancel-btn" class="button">Cancel</button>
                <button type="button" id="save-btn" class="button">Save</button>
            </div>
        </div>
    </div>
</div>
<script src="<?= _asset('js/shared/Account.js') ?>"></script>
<script src="<?= _asset('js/views/UserEdit.js') ?>"></script>