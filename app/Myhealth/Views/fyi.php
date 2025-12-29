<div id="main-container">
    <h1>FYI Manager</h1>
    <div class="data-table">
        <table class="tabular sortable">
            <thead>
                <tr>
                    <th class="center">ID</th>
                    <th class="left">Start</th>
                    <th class="left">End</th>
                    <th class="left">Subject</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($records as &$record) { ?>
                    <tr class="clickable" data-id="<?=_WValue($record->id)?>">
                        <td class="center"><?=_W($record->id)?></td>
                        <td><?=_WDate($record->StartDate)?></td>
                        <td><?=_WDate($record->EndDate)?></td>
                        <td><?=_W($record->Subject)?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <p class="submit">
        <button type="button" id="add-btn" class="button">Add</button>
    </p>
</div>

<form name="edit" action="fyi-edit" method="POST">
    <?=_csrf()?>
    <input type="hidden" name="id" value="">
</form>

<script src="<?=_asset('js/sorttable.js', false)?>"></script>
<script src="<?=_asset('js/views/Fyi.js')?>"></script>
