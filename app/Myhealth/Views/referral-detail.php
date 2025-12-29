<div id="main-container">
    <h1>Referral #<?=_W($referralNumber)?></h1>
    <h2><?=_W($referral->CommonStatusDescription ?? '')?></h2>

    <div class="main-inner">
        <?php if ($errorMsg != "") { ?>
            <p class="error"><?=$errorMsg?></p>
        <?php } else { ?>
            <div class="info-container">
                <div><div>Member Name:</div><div><?=_W($memberName)?></div></div>
                <div><div>Date of Birth:</div><div><?=_WDate($referral->BirthDate)?></div></div>
                <div><div>Health Plan:</div><div><?=_W($referral->PayorName)?></div></div>
                <div><div>Referral Date:</div><div><?=_WDate($referral->DateEntered)?></div></div>
                <div><div>Referred By:</div><div><?=_W(libFormatName($referral->ByLastName, $referral->ByFirstName, '', $referral->ByTitle, (int) $referral->ByPerson))?></div></div>
                <div><div>Referred To:</div><div><?=_W(libFormatName($referral->ToLastName, $referral->ToFirstName, '', $referral->ToTitle, (int) $referral->ToPerson))?></div></div>
                <div><div>Valid Until:</div><div><?=_WDate($referral->DateValidTo)?></div></div>
            </div>
        <?php } ?>
    </div>

    <?php if ($errorMsg == "") { ?>
    <br/>
    <div class="main-inner">
        <p><strong>DIAGNOSIS</strong></p>
        <?php 
        foreach($referral->details as $detail) {
            if (!empty($detail->DiagnosisDescription)) {
                echoln(_W($detail->DiagnosisDescription));
            }
        }
        ?>

        <?php if (trim($referral->Notes) != "") {	?>
            <p class="top-pad1"><strong>NOTES</strong></p>
            <?=NotesHTML($referral->Notes)?>
        <?php } ?>

        <p class="top-pad1"><strong>PROCEDURE / SERVICE</strong></p>
        <?php
        foreach($referral->details as $detail) {
            if (!empty($detail->ProcedureDescription)) {
                echoln(_W($detail->ProcedureDescription));
            }
        }

        foreach($referral->details as $detail) {
            if (!empty($detail->ServiceDescription)) {
                echoln(_W($detail->ServiceDescription));
            }
        }
        ?>
    </div>

    <br/>
    <a href="javascript:void(0);" onclick="Referral.reprint('<?=_W($referralNumber)?>');">View Referral Reprint</a>
    <?php } ?>
</div>

<script src="<?=_asset('/js/views/Referral.js')?>"></script>
