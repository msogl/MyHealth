<style>
    #popupMessage {
        height: 400px;
        max-height: 400px;
    }
</style>
<div id="main-container">
    <h1>Edit FYI</h1>
    <div class="main-inner">
        <p id="track-message"></p>
        <input type="hidden" id="id" value="<?=_WValue(EncryptAESMSOGL($fyiDao->id))?>">

        <div class="edit-container font-bold">
            <div class="label">Start Date:</div>
            <div>
                <input type="date" id="startdate" value="<?=_WDate($fyiDao->StartDate, 'Y-m-d')?>" maxlength="10">
            </div>
            <div class="label">End Date:</div>
            <div>
                <input type="date" id="enddate" value="<?=_WDate($fyiDao->EndDate, 'Y-m-d')?>" maxlength="10">
            </div>
            <div class="label">Subject:</div>
            <div>
                <input type="text" id="subject" class="w-full" value="<?=_WValue($fyiDao->Subject)?>" maxlength="100">
            </div>
        </div>
        <div class="label">Content:</div>
        <textarea id="content" class="w-full" style="height:20em;"><?=_W($fyiDao->Content)?></textarea>
        <div class="clearfix top-pad1">
            <div class="left-side">
                <button type="button" id="delete-btn" class="button button-danger">Delete</button>
            </div>
            <div class="right-side">
                <button type="button" id="cancel-btn" class="button">Cancel</button>
                <button type="button" id="preview-btn" class="button">Preview</button>
                <button type="button" id="save-btn" class="button">Save</button>
            </div>
        </div>
    </div>
</div>

<div id="overlay">
    <div id="popupMessage" class="popup">
        <div class="content"></div>
        <div class="footer right">
            <button type="button" id="fyi-ok-btn" class="button" onclick="hidePopup('popupMessage');">Ok</button>
        </div>
    </div>
</div>

<script src="<?=_asset('js/views/FyiEdit.js')?>"></script>
