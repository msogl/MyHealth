<div id="main-container">
    <div class="left-side">
        <h1>Lab Order #<?=_W($orderNumber.(!_isNE($lab->labName) ? "[br]{$lab->labName}" : ''))?></h1>
    </div>
    <div class="right-side right no-print">
        <button class="button" onclick="window.print();return false;"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="clear"></div>

    <div class="main-inner">
        <?php if ($errorMsg != "") { ?>
        <p class="error"><?=_W($errorMsg)?></p>
        <?php }	else { ?>
        <div class="info-container">
            <div><div>Member Name:</div><div><?=$lab->patientName?></div></div>
            <div><div>Date of Birth:</div><div><?=_WDate($lab->birthDate)?></div></div>
            <div><div>Health Plan:</div><div><?=$lab->payorName?></div></div>
            <div><div>Ordering Provider:</div><div><?=$lab->orderingProvider?></div></div>
            <div><div>Lab Date:</div><div><?=_WDate($lab->labDate)?></div></div>
        </div>
        <?php } ?>
    </div>

    <?php if ($errorMsg == "") { ?>
        <br/>
        <div class="data-table">
            <table class="row-border medium-width-or-above">
                <thead>
                    <tr>
                        <th class="left">Description</th>
                        <th class="left">Value</th>
                        <th class="left">Range</th>
                        <th class="left">Abnormal</th>
                        <th class="left">Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($lab->labDetails as $detail) { ?>
                    <?php if ($detail->value == "***DELETE***") { continue; } ?>
                    <tr class="row-start">
                        <td class="top"><?=_W($detail->valueDescription)?></td>
                        <?php if (($detail->valueType == "NOTE" || ($detail->valueRange == "" && $detail->abnormal == "" && $detail->note == ""))) { ?>
                        <td class="top" colspan="4"><?=str_replace("~", "<br/>", _W($detail->value))?></td>
                        <?php } else { ?>
                        <td class="top nowrap"><?=_W($detail->value)?> <?=_W($detail->units)?></td>
                        <td class="top nowrap"><?=_W($detail->valueRange)?></td>
                        <td class="top"><?=_W($detail->abnormal)?></td>
                        <td class="top"><?=str_replace("~", "<br/>", _W($detail->note))?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <table class="row-border small-width">
                <thead>
                    <tr>
                        <th class="left">Description</th>
                        <th class="left">Value</th>
                        <th class="left">Range</th>
                        <th class="left">Abnormal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($lab->labDetails as $detail) { ?>
                    <?php if ($detail->value == "***DELETE***") { continue; } ?>
                    <tr class="row-start">
                        <td class="top"><?=_W($detail->valueDescription)?></td>
                        <?php if (($detail->valueType == "NOTE" || ($detail->valueRange == "" && $detail->abnormal == "" && $detail->note == ""))) { ?>
                        <td class="top" colspan="4"><?=str_replace("~", "<br/>", _W($detail->value))?></td>
                        <?php } else { ?>
                        <td class="top nowrap"><?=_W($detail->value)?> <?=_W($detail->Units)?></td>
                        <td class="top nowrap"><?=_W($detail->valueRange)?></td>
                        <td class="to"><?=_W($detail->abnormal)?></td>
                        <?php } ?>
                    </tr>
                    <?php if ($detail->note != "") { ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td colspan="3"><?=str_replace("~", "<br/>", _W($detail->note))?></td>
                    </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>

            <table class="row-border narrow-width">
                <thead>
                    <tr>
                        <th class="left">Description</th>
                        <th class="left">Value</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($lab->labDetails as $detail) { ?>
                    <?php if ($detail->value == "***DELETE***") { continue; } ?>
                    <tr class="row-start">
                        <td class="top">Description</td>
                        <td class="top"><?=_W($detail->valueDescription)?></td>
                    </tr>
                    <tr>
                        <td class="top">Value</td>
                        <td class="top">
                            <?php
                            if (($detail->valueType == "NOTE" || ($detail->valueRange == "" && $detail->abnormal == "" && $detail->note == ""))) {
                                echo str_replace("~", "<br/>", _W($detail->value));
                            }
                            else {
                                echo _W($detail->value." ".$detail->Units);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="top">Range</td>
                        <td class="top"><?=_W($detail->valueRange)?></td>
                    </tr>
                    <tr>
                        <td class="top">abnormal</td>
                        <td class="top"><?=_W($detail->abnormal)?></td>
                    </tr>
                    <?php if ($detail->note != "") { ?>
                    <tr>
                        <td class="top">Note</td>
                        <td class="top"><?=str_replace("~", "<br/>", _W($detail->note))?></td>
                    </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>