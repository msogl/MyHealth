<link rel="stylesheet" type="text/css" href="assets/css/switch.css">
<style>
    .data-table td {
        height: 20px;
    }

    table.row-border tbody > tr:not(:last-child) {
        border-bottom: 1px solid #ddd;
    }

    #users-table {
        width: 100% !important;
    }
</style>
<div id="main-container" style="width:90%;margin:0 auto;">
    <h1 class="left">User Review</h1>
    <div class="mb-1">
        Base recommendation on users not logged in during last 
        <select id="idle-days">
            <option value="30"<?=($idleDays == 30 ? SELECTED : '')?>>30 days</option>
            <option value="60"<?=($idleDays == 60 ? SELECTED : '')?>>60 days</option>
            <option value="90"<?=($idleDays == 90 ? SELECTED : '')?>>90 days</option>
            <option value="120"<?=($idleDays == 120 ? SELECTED : '')?>>120 days</option>
            <option value="180"<?=($idleDays == 180 ? SELECTED : '')?>>6 months</option>
            <option value="365"<?=($idleDays == 365 ? SELECTED : '')?>>1 year</option>
            <option value="730"<?=($idleDays == 730 ? SELECTED : '')?>>2 years</option>
        </select>
        <?=SPACER?>
        <label>
            <input type="checkbox" id="show-inactive-cb"<?=($activeOnly == 0 ? CHECKED : '')?>> Show inactive users
        </label>
        <?=SPACER?>
        <button type="button" id="redisplay-btn" class="button">Redisplay</button>
    </div>
    <div class="data-table">
        <table id="users-table" class="row-border sortable" border="0" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th class="columnheader left">ID</th>
                    <th class="columnheader left">Username</th>
                    <th class="columnheader left nowrap">Member</th>
                    <th class="columnheader center">Confirmed</th>
                    <th class="columnheader center">Active</th>
                    <th class="columnheader left nowrap sort_mmdd">Created Date</th>
                    <th class="columnheader left nowrap sort_mmdd">Last Logged In Date</th>
                    <th class="columnheader left nowrap">Last Logged In</th>
                    <th class="columnheader left">Recommendation</th>
                    <th class="columnheader center sorttable_nosort">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reviews as &$review) { ?>
                    <tr data-id="<?=_W($review->encryptedAccountId)?>">
                        <td class="columndata top"><?=_W($review->AccountID)?></td>
                        <td class="columndata top"><?=_W($review->Username)?></td>
                        <td class="columndata top">
                            <?php if (str_contains($review->MemberName, 'not found')) { ?>
                                <span class="error"><?=_W($review->MemberName)?></span><br>
                            <?php } else { ?>
                                <?=_W($review->MemberName)?><br>
                            <?php } ?>
                            ID: <?=_W($review->MemberID)?><br>
                            <?=_W($review->Email)?>
                            <?php if (!_isNE($review->TerminationDate)) { ?>
                                <br>
                                <span class="error">Termed <?=_WDate($review->TerminationDate)?></span>
                            <?php } ?>
                        </td>
                        <td class="columndata top center confirmed" data-value="<?=($review->Confirmed ? '1' : '0')?>"><?=($review->Confirmed ? CHECKMARK : XMARK)?></td>
                        <td class="columndata top center active" data-value="<?=($review->Active ? '1' : '0')?>"><?=($review->Active ? CHECKMARK : XMARK)?></td>
                        <td class="columndata top nowrap"><?=_WDateTime($review->CreatedDateTime)?></td>
                        <td class="columndata top nowrap"><?=_WDateTime($review->LastLoggedInDate)?></td>
                        <td class="columndata top"><?=_W($review->LastLoggedIn)?></td>
                        <td class="columndata top"><?=_W($review->Recommendation)?></td>
                        <td class="columndata top toggle">
                            <div class="center debug">
                                <label class="switch<?=($review->Active == 1 ? ' on' : '')?>">
                                    <input type="checkbox" class="active" value="1"<?=($review->Active == 1 ? CHECKED : '')?>> 
                                    <span class="slider">
                                        <span class="dot"></span>
                                    </span>
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= _asset('js/shared/Account.js') ?>"></script>
<script src="<?= _asset('js/views/UserReview.js') ?>"></script>