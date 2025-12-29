<?php

use Myhealth\Core\Dates;

?>
<style type="text/css">
    .memo_nu {
        color: Red;
        font-size: smaller;
        font-weight: bold;
        font-style: italic;
        text-decoration:none;
    }

    .scroll-tip {
        display: none;
    }
</style>
<div id="main-container">
    <?php if (!empty($pcpNote)) { ?>
    <div class="main-inner" style="border:none;background-color:#f00;color:#fff;">
        <strong>COVID-19 UPDATE FOR YOUR PCP, <?=_W(strtoupper($pcpNote->PCPName))?></strong><br>
        <?=_W($pcpNote->description)?>
    </div>
    <br><br>
    <?php } ?>

    <h2>For Your Information</h2>
    <div class="data-table">
        <table id="for-your-info" class="w-full">
            <thead>
                <tr>
                    <th class="left" style="width:100px;">Posted</th>
                    <th class="left">Subject</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <br><br><br>

    <h2>Member Memos</h2>

    <div class="main-inner">
        <?php if ($client == "RPPG") { ?>
            <div class="memo">
                <a href="docs/mbrltr_access.pdf" target="_blank"><img src="assets/images/2009_accessmemo.jpg" border="0" alt="Member Letter (PDF)" /><br>
                Access</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">Updated</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="docs/mbrltr_affirmation.pdf" target="_blank"><img src="assets/images/2009_affmemo.jpg" border="0" alt="Affirmation Letter (PDF)" /><br>
                Affirmation</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">Updated</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="docs/mbrltr_cm.pdf" target="_blank"><img src="assets/images/2009_cmmemo.jpg" border="0" alt="Case Management Letter (PDF)" /><br>
                Case Management</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">Updated</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="new-member"><img src="assets/images/newmember.jpg" border="0" alt="New Member Materials" /><br>
                New Member Info</a>
                <?php if (Dates::dateLE('now', '04/30/2020')) { ?>
                <br><span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <!--
            <div class="memo">
                <a href="docs/mbrltr_newmember.pdf" target="_blank"><img src="assets/images/newmember.jpg" border="0" alt="New Member Letter (PDF)" /><br>
                New Member Letter</a>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">Updated</span>
                <?php } ?>
            </div>
            -->
            <?php if (Dates::dateLE('now', '06/30/2019')) { ?>
            <div class="memo">
                <a href="docs/RPPG Member Newsletter Spring 2019.pdf" target="_blank"><img src="assets/images/rppg_newsletter_spring_2019.jpg" border="0" /><br>
                Spring 2019 Newsletter</a><br>
                <span class="memo_nu">New!</span>
            </div>
            <?php } ?>
            <?php if (Dates::dateLE('now', '12/11/2014')) { ?>
            <div class="memo">
                <a href="https://www.surveymonkey.com/s/VMNg7NN" target="_blank"><img src="assets/images/mbr_survey.jpg" border="0" /></a><br>
                <span class="memo_nu">New!</span> <a href="https://www.surveymonkey.com/s/VMNg7NN" target="_blank">2014 Member Satisfaction Survey</a>
            </div>
            <?php } ?>
            <?php if (Dates::dateLE('now', '09/25/2014')) { ?>
            <div class="memo">
                <a href="docs/LIVE%20WELL%20WITH%20DIABETES%20invitation.pdf" target="_blank"><img src="assets/images/201409_diabeticseminar.jpg" border="0" alt="Live Well With Diabetes Invitation (PDF)" /></a><br>
                <span class="memo_nu">New!</span> <a href="docs/LIVE%20WELL%20WITH%20DIABETES%20invitation.pdf" target="_blank">Invitation</a>
            </div>
            <?php } ?>

        <?php }
        elseif ($client == "RPA") { ?>
            <div class="memo">
                <a href="docs/mbrltr_access.pdf" target="_blank"><img src="assets/images/rpa_accessmemo.jpg" border="0" alt="Member Letter (PDF)" /><br>
                Access</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="docs/mbrltr_affirmation.pdf" target="_blank"><img src="assets/images/rpa_affirmationmemo.jpg" border="0" alt="Affirmation Letter (PDF)" /><br>
                Affirmation</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="docs/mbrltr_cm.pdf" target="_blank"><img src="assets/images/rpa_cmmemo.jpg" border="0" alt="Case Management Letter (PDF)" /><br>
                Case Management</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <div class="memo">
                <a href="new-member"><img src="assets/images/rpa_newmember.jpg" border="0" alt="New Member Materials" /><br>
                New Member Info</a>
                <?php if (Dates::dateLE('now', '04/30/2020')) { ?>
                <br><span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <!--
            <div class="memo">
                <a href="docs/rpa_mbrltr_newmember.pdf" target="_blank"><img src="assets/images/rpa_newmembermemo.jpg" border="0" alt="New Member Letter (PDF)" /><br>
                New Member Letter</a>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            -->
            <?php if (Dates::dateLE('now', '06/30/2019')) { ?>
            <div class="memo">
                <a href="docs/RPA Member Newsletter Spring 2019.pdf" target="_blank"><img src="assets/images/rpa_newsletter_spring_2019.jpg" border="0" /><br>
                Spring 2019 Newsletter</a><br>
                <?php if (Dates::dateLE('now', '04/30/2019')) { ?>
                <span class="memo_nu">New!</span>
                <?php } ?>
            </div>
            <?php } ?>
        <?php }
        elseif ($client == "HPPO") { ?>
            <div class="memo">
                <a href="docs/4_HPPO Member Welcome Letter_Full.pdf" target="_blank"><img src="assets/images/hppo_welcome_letter.jpg" border="0" alt="HPPO Member Welcome Letter (PDF)" /><br>
                Welcome Letter</a>
            </div>
            <div class="memo">
                <a href="docs/HPPO_Maternity_Care_Summary.pdf" target="_blank"><img src="assets/images/hppo_maternity_care_summary.jpg" border="0" alt="HPPO Materity Care Summary (PDF)" /><br>
                Maternity Care Summary</a>
            </div>
        <?php } ?>
        <div class="clear"></div>
    </div>
    <br>
    <br>
    <br>

    <?php if (count($goalDaos) > 0) { ?>
    <h2>My Goals</h2>
    <div class="data-table">
        <table class="w-full">
            <tr>
                <th class="left">Goal</th>
                <th class="center">Date to Meet</th>
                <th class="center">Goal Met?</th>
            </tr>
            <?php foreach($goalDaos as &$goalDao) { ?>
            <tr style="line-height:20px;">
                <td class="left"><?=_W($goalDao->Goal)?></td>
                <td class="center"><?=_WDate($goalDao->RevisedDate != "" ? $goalDao->RevisedDate : $goalDao->DateToMeet)?></td>
                <td class="center"><?=($goalDao->DateGoalMet != "" ? '&#x1F60A' : '')?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
    <br>
    <br>
    <br>
    <?php } ?>

    <?php if (count($materials) > 0) { ?>
    <h2>Recommended Materials</h2>
    <div class="data-table materials-table">
        <table class="w-full">
            <tr>
                <th class="left">Added</th>
                <th class="left">Filename</th>
            </tr>
            <?php foreach($materials as &$material) { ?>
                <tr style="line-height:20px;">
                    <td><?=libFormatDt($material->dateSent, "mm/dd/yyyy")?></td>
                    <td>
                        <?php
                        $icon = match(pathinfo($material->filename, PATHINFO_EXTENSION)) {
                            'pdf' => 'pdf_icon.gif',
                            'doc','docx' => 'word_icon.gif',
                            'xls', 'xlsx' => 'excel_icon.gif',
                            default => 'generic_icon.gif'
                        }
                        ?>
                        <img src="assets/images/<?=basename($icon)?>">
                        &nbsp;
                        <a
                            href="javascript:void(0);"
                            data-href="<?=_W(EncryptAESMSOGL($material->filename))?>"
                            data-type="<?=FileExtension(strtolower($material->filename))?>"
                        ><?=_W(basename($material->filename))?></a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <br>
    <br>
    <br>
    <?php } ?>

    <h2>Recent Claims</h2>
    <?php include VIEWS.'/components/claims.php'; ?>
    
    <?php if (count($claims) > 3) { ?>
    <div style="margin-top:0.5rem;">
        <a href="my-claims">View more...</a>
    </div>
    <br>
    <br>
    <br>
    <?php } ?>

    <h2>Recent Referrals</h2>
    <?php include VIEWS.'/components/referrals.php'; ?>

    <?php if (count($referrals) > 3) { ?>
    <div style="margin-top:0.5rem;">
        <a href="my-referrals">View more...</a>
    </div>
    <?php } ?>
</div> <!-- end main-container -->
<br>
<br>
<br>

<form name="detail_form" method="POST">
    <?=_csrf()?>
    <input type="hidden" name="id" value="">
    <input type="hidden" name="mid" value="<?=EncryptAESMSOGL(_session('loggedInMemberId'))?>">
</form>

<div id="overlay">
    <div id="popupMessage" class="popup">
        <div class="content"></div>
        <div class="footer right">
            <input type="button" id="fyi-ok-btn" class="button" value="Ok" onclick="hidePopup('popupMessage');return false;"/>
        </div>
    </div>

    <div id="popup-pdf" class="popup">
        <div class="content" id="pdf-body" data-href="">
        </div>
        <div class="footer right">
            <button type="button" class="button" id="pdf-download-btn" style="width:100px;">Download</button>
            <button type="button" class="button" style="width:100px;" onclick="hidePopup('popup-pdf');">Close</button>
        </div>
    </div>

    <div id="popup-video" class="popup">
        <div class="content" id="video-body">
        </div>
        <div class="footer right">
            <button type="button" class="button" onclick="vPlayer.dispose();hidePopup('popup-video');">Close</button>
        </div>
    </div>
</div>


<link href="assets/js/video-js/video-js.min.css" rel="stylesheet">
<script src="assets/js/video-js/video.js"></script>
<script src="<?=_asset('js/views/Home.js')?>"></script>
<script src="<?=_asset('js/views/Claim.js')?>"></script>
<script src="<?=_asset('js/views/Referral.js')?>"></script>
<script>
    const fyi = <?=json_encode($fyiMessages, JSON_PRETTY_PRINT)?>;
    Home.init(fyi);
</script>