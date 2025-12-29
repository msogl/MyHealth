<?php

use Myhealth\Classes\Event;

?>
<style type="text/css">
    #event-types {
        border: 1px solid #999;
        padding: 4px;
        height: 8rem;
        overflow-y: auto;
    }

    #event-types label {
        display: block;
    }
</style>

<div id="main-container">
    <h1>View Logs</h1>
    <?php if ($errorMsg != "") { ?>
    <p class="error"><?=$errorMsg?></p>
    <?php } else { ?>
    <div class="main-inner">
        <form id="logs-form" action="view-logs" method="POST" name="logs-form">
            <?= _csrf() ?>
            <div class="info-container" style="max-width:100%;">
                <div>
                    <div>Select user:</div>
                    <div>
                        <select name="user">
                            <option value="Any"<?=(in_array($filter->user, ['','Any']) ? SELECTED : '')?>>Any</option>
                            <?php foreach($usernames as $username) { ?>
                                <option<?=($filter->user == $username ? SELECTED : '')?>><?=_W($username)?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div>
                    <div>From date:</div>
                    <div>
                        <input type="date" value="<?=_WValue($filter->from)?>" name="from">
                    </div>
                </div>
                <div>
                    <div>To date:</div>
                    <div>
                        <input type="date" value="<?=_WValue($filter->to)?>" name="to">
                    </div>
                </div>
                <div>
                    <div>Event Type:</div>
                    <div>
                        <div id="event-types">
                            <label>
                                <input type="checkbox" name="event[]" value="Any"<?=(in_array('Any', $filter->events) ? CHECKED : '')?>>
                                Any
                            </label>
                            <?php foreach(Event::$description as $value=>$description) { ?>
                                <label>
                                    <input type="checkbox" name="event[]" value="<?=_W($value)?>"<?=(in_array($value, $filter->events) ? CHECKED : '')?>>
                                    <?=_W($description)?>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div># records:</div>
                    <div>
                        <input type="text" value="<?=_WValue($filter->limit)?>" name="limit" class="text" style="width:80px;">
                    </div>
                </div>
            </div>
            <br>
            <p class="submit right">
                <button type="button" id="reset-btn" class="button">Reset</button>
                <button type="button" id="filter-btn" class="button">Filter</button>
            </p>
        </form>
    </div>

    <div class="data-table mt-1">
        <table class="sortable">
            <thead>
                <tr>
                    <th class="left">ID</th>
                    <th class="left sort_mmdd">Event&nbsp;Type</th>
                    <th class="left">Date</th>
                    <th class="left">User</th>
                    <th class="left">IP</th>
                    <th class="left">Description</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($eventDaos as &$eventDao) { ?>
            <tr>
                <td class="top"><?=_W($eventDao->id)?></td>
                <td class="top nowrap"><?=Event::$description[intval($eventDao->event_type)]?></td>
                <td class="top nowrap"><?=_WDateTime($eventDao->event_date)?></td>
                <td class="top"><?=_W($eventDao->user_id)?></td>
                <td class="top"><?=_W($eventDao->ip_address)?></td>
                <td class="top"><?=_W($eventDao->description)?></td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <br/><br/>
    <?php } ?>
</div>

<script src="<?= _asset('js/views/ViewLog.js') ?>"></script>
