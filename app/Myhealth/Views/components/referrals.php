<?php if (count($referrals) == 0) { ?>
    <p class="error">There are no referrals on record for you.</p>
    <?php } else { ?>
    <p class="more-details">Select a referral number to see more details</p>
    <div class="data-table">
        <table id="my-referrals" class="sortable">
            <thead>
                <tr>
                    <th class="left">Referral&nbsp;#</th>
                    <th class="center">Referral Date</th>
                    <th class="left">Health Care Professional</th>
                    <th class="left">Specialty</th>
                    <th class="center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $row = 1;
                foreach($referrals as &$referral) {
                    if (isset($referralLimit) && $row > $referralLimit) { break; }
                ?>
                <tr class="clickable" data-id="<?=_WValue($referral->ReferralNumber)?>">
                    <td>
                        <span class="screen-reader">Referral number</span><?=_W($referral->ReferralNumber)?></a>
                        <!-- <a href="#" onClick="return ViewReferral('<?=_W($referral->ReferralNumber)?>');"><span class="screen-reader">Recent referral number</span><?=_W($referral->ReferralNumber)?></a> -->
                    </td>
                    <td class="center">
                        <?=_WDate($referral->DateEntered)?>
                    </td>
                    <td>
                        <?=_W(libFormatName($referral->LastName, $referral->FirstName, '', $referral->Title, (int) $referral->Person))?>
                    </td>
                    <td class="left">
                        <?=_W($referral->Specialty)?>
                    </td>
                    <td class="center">
                        <?=$oCommon->ReferralStatus($referral->Status)?>
                    </td>
                </tr>

                <?php
                    $row++;
                }
                ?>
            </tbody>
        </table>
    </div>	<!-- data-table -->
<?php } ?>
