<div id="main-container" style="width:1200px;">
    <h1>User Admin</h1>
    <div class="edit-container mb-1">
        <div class="label">Find:</div>
        <div>
            <input type="text" id="filter" class="w-full">
        </div>
    </div>
    <div class="data-table">
        <table class="tabular sortable filterable">
            <thead>
                <tr>
                    <th class="left">ID</th>
                    <th class="left">Username</th>
                    <th class="left">Email</th>
                    <th class="left">Nickname</th>
                    <th class="left">Member ID</th>
                    <th class="sort_mmdd">Created</th>
                    <th>Confirmed</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($accountDaos as $accountDao) { ?>
                    <tr class="clickable" data-id="<?=_W(EncryptAESMSOGL($accountDao->AccountID))?>">
                        <td><?=_W($accountDao->AccountID)?></td>
                        <td class="username"><?=_W($accountDao->Username)?></td>
                        <td class="email"><?=_W($accountDao->Email)?></td>
                        <td class="nickname"><?=_W($accountDao->Nickname)?></td>
                        <td class="memid"><?=_W($accountDao->MemberID)?></td>
                        <td class="center nowrap"><?=_WDateTime($accountDao->CreatedDateTime)?></td>
                        <td class="center"><?=($accountDao->Confirmed == 1 ? 'Yes' : 'No')?></td>
                        <td class="center"><?=($accountDao->Active == 1 ? 'Yes' : 'No')?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<form id="edit-form" method="POST" action="user-edit" style="display:none;">
    <?= _csrf() ?>
    <input type="hidden" name="id" value="">
</form>

<script src="<?= _asset('js/views/User.js') ?>"></script>
