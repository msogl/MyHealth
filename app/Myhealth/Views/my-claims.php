<div id="main-container" class="mb-2">
    <h1>My Claims</h1>

    <?php include VIEWS.'/components/claims.php' ?>
</div>

<form name="detail_form" method="POST">
    <?= _csrf() ?>
    <input type="hidden" name="id" value="">
    <input type="hidden" name="mid" value="<?=EncryptAESMSOGL(_session('loggedInMemberId'))?>">
</form>

<script src="<?=_asset('js/sorttable.js', false)?>"></script>
<script src="<?=_asset('js/views/Claim.js')?>"></script>