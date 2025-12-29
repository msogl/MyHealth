<?php if (count($claims) == 0) { ?>
    <p class="error">There are no claims on record for you.</p>
<?php } else { ?>
    <p class="more-details">Select a claim number to see more details</p>
    <div class="data-table">
        <table id="my-claims" class="sortable">
            <thead>
                <tr>
                    <th class="left">Claim&nbsp;#</th>
                    <th class="center">Date of Service</th>
                    <th class="left">Health Care Professional</th>
                    <th class="right">Amount<br />Billed</th>
                    <th class="center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $row = 1;
                foreach ($claims as &$claim) {
                    if (isset($claimLimit) && $row > $claimLimit) { break; }
                ?>
                    <tr class="clickable" data-id="<?=_WValue($claim->Claim_number)?>">
                        <td>
                            <span class="screen-reader">Claim number</span><?=_W($claim->Claim_number)?>
                        </td>
                        <td class="center">
                            <?= _WDate($claim->ServiceDate) ?>
                        </td>
                        <td>
                            <?=_W(libFormatName($claim->LastName, $claim->FirstName, '', $claim->Title, (int) $claim->Person))?>
                        </td>
                        <td class="right">
                            $<?=number_format($claim->AmountBilled ?? 0.00, 2)?>
                        </td>
                        <td class="center">
                            <?=$oCommon->ClaimStatus($claim->Status)?>
                        </td>
                    </tr>

                <?php
                    $row++;
                }
                ?>
            </tbody>
        </table>
    </div> <!-- data-table -->
<?php } ?>
