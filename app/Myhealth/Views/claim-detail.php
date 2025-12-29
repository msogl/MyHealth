<style>
    #claimdetail2 th, #claimdetail2 td {
        padding-left: 2px;
        padding-right: 2px;
    }
</style>

<div id="main-container">
    <div class="left-side">
        <h1>Claim #<?=_W($claimNumber)?></h1>
    </div>
    <div class="right-side right no-print">
        <button type="button" class="button" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="clear"></div>

    <div class="main-inner">
        <?php if ($errorMsg != "") { ?>
            <p class="error"><?=_W($errorMsg)?></p>
        <?php } else { ?>
            <div class="info-container">
                <div><div>Member Name:</div><div><?=_W($memberName)?></div></div>
                <div><div>Date of Birth:</div><div><?=_WDate($claim->BirthDate)?></div></div>
                <div><div>Health Plan:</div><div><?=_W($claim->PayorName)?></div></div>
                <div><div>Provider:</div><div><?=_W(libFormatName($claim->LastName, $claim->FirstName, "", $claim->Title , $claim->Person))?></div></div>
                <div><div>Date of Claim:</div><div><?=_WDate($claim->Claim_date == "" ? $claim->ServiceDate : $claim->Claim_date)?></div></div>
            </div>
        <?php } ?>
    </div>

    <?php if ($errorMsg == "") { ?>
    <br>
    <div class="data-table">
        <table class="rounded" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th class="left break-word">Date of Service &amp; Service Provided</th>
                    <th class="right">Charges Submitted</th>
                    <th class="right">Agreed Upon Pricing</th>
                    <th class="right">Paid by Plan</th>
                    <th class="right">Applied to Your Deductible</th>
                    <th class="right">Your Copay</th>
                    <th class="right">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalAmountBilled = 0.00;
                $totalAllowedCharge = 0.00;
                $totalPaid = 0.00;
                $totalWithold = 0.00;
                $totalCopay = 0.00;

                foreach($details as $detail) {
                    $totalAmountBilled += floatval(noBlank($detail->AmountBilled));
                    $totalAllowedCharge += floatval(noBlank($detail->AllowedCharge));
                    $totalPaid += floatval(noBlank($detail->Fee));
                    $totalWithold += floatval(noBlank($detail->WitholdAmount));
                    $totalCopay += floatval(noBlank($detail->CopayAmount));
                ?>
                <tr>
                    <td class="middle left break-word">
                        <?=_WDate($detail->FromDate)?><br />
                        <?=_W($detail->CPT_description)?>
                    </td>
                    <td class="middle right">
                        $<?=number_format($detail->AmountBilled ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        $<?=number_format($detail->AllowedCharge ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        $<?=number_format($detail->Fee ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        $<?=number_format($detail->WitholdAmount ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        $<?=number_format($detail->CopayAmount ?? 0.00, 2)?>
                    </td>
                    <td class="middle right" class="break-word"> <!-- style="border-right:none;overflow-wrap:break-word;word-break:break-word;"> -->
                        <?=_W($detail->Disposition)?>
                    </td>
                </tr>

                <?php } ?>

                <tr>
                    <td class="middle right">
                        <strong>Total</strong>
                    </td>
                    <td class="middle right">
                        <?=number_format($totalAmountBilled ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        <?=number_format($totalAllowedCharge ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        <?=number_format($totalPaid ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        <?=number_format($totalWithold ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        <?=number_format($totalCopay ?? 0.00, 2)?>
                    </td>
                    <td class="middle right">
                        &nbsp;
                    </td>
                </tr>

                <tr>
                    <td colspan="4">
                        &nbsp;
                    </td>

                    <td class="middle right nowrap" colspan="2">
                        <strong>Your responsibility:</strong> $<?=number_format($totalCopay ?? 0.00, 2)?>
                    </td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>	<!-- data-table -->
    <?php } ?>
</div>	<!-- main-container -->
