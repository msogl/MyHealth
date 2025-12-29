<div id="mfa-options">
    <h1>SELECT AN AUTHENTICATION METHOD</h1>
    <br>
    <?php if ($mfaMethods['DUO']) { ?>
        <div class="send-to" data-type="duo">
            <div><img src="assets/images/duo_logo.png" alt="DUO Authentication" style="width:150px;"></div>
        </div>
    <?php } ?>
    <?php if ($mfaMethods['TOTP']) { ?>
        <div class="send-to" data-type="totp">
            <div class="title">Time-based One Time Password</div>
            <div class="svg">
                <span style="font-size:36px;"><i class="fa fa-unlock"></i></span>
            </div>
            <div class="svg">
                <i class="fa fa-asterisk"></i><i class="fa fa-asterisk"></i><i class="fa fa-asterisk"></i>
                <i class="fa fa-asterisk"></i><i class="fa fa-asterisk"></i><i class="fa fa-asterisk"></i>
            </div>
        </div>
    <?php } ?>
    <?php if ($mfaMethods['SMS']) { ?>
        <div class="send-to" data-type="sms">
            <div class="title">SMS (Text Message)</div>
            <div>Mobile: <?= _W($smsnumber) ?></div>
        </div>
    <?php } ?>
    <?php if ($mfaMethods['EMAIL']) { ?>
        <div class="send-to" data-type="email">
            <div class="title">Email</div>
            <div><?= _W($maskedEmail) ?></div>
        </div>
    <?php } ?>
</div>

<script src="<?=_asset('js/views/MFASelect.js')?>"></script>