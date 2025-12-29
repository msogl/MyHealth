<div id="main-container" class="mb-2">
    <div class="left-side">
        <h1>My Labs</h1>
    </div>
    <div class="right-side right no-print">
        <button class="button" onclick="window.print();return false;"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="clear"></div>

    <?=ReadTemplate("lab-disclaimer.html")?>

    <?php if (count($labs) == 0) { ?>
    <p class="error">There are no lab results on record for you.</p>
    <?php }
    else { ?>
    <div class="data-table">
        <table id="my-labs" class="sortable">
            <tr>
                <th class="left">Lab</th>
                <th class="left nowrap">Order #</th>
                <th class="center nowrap sort_mmdd">Lab Date</th>
                <th class="left">Test</th>
                <th class="left nowrap">Ordered By</th>
            </tr>

            <?php foreach($labs as &$lab) { ?>
            <tr class="clickable" data-id="<?=_WValue($lab->id)?>" data-order="<?=_WValue($lab->orderNo)?>">
                <td class="top nowrap"><?=_W($lab->labName)?></td>
                <td class="top nowrap"><?=_W($lab->orderNo)?></td>
                <td class="top center nowrap"><?=_WDate($lab->labDate)?></td>
                <td class="top"><?=_W($lab->labProc)?></td>
                <td class="top nowrap"><?=_W($lab->orderingProvider)?></td>
            </tr>
            <?php } ?>
        </table>
    </div>
    <?php } ?>
</div>

<form name="detail_form" method="POST">
    <?=_csrf()?>
    <input type="hidden" name="id" value="">
    <input type="hidden" name="order" value="">
</form>

<script src="<?=_asset('js/views/Lab.js')?>"></script>
