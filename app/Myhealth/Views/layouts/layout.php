<!DOCTYPE html>

<html lang="en">
<head>
	<title><?=_W($title)?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="<?=getTokenOnly()?>">
    <meta name="app-meta" content="<?=_W($_appMeta)?>">
	<link rel="stylesheet" type="text/css" href="<?=_asset('css/default.css')?>" />
	<link rel="stylesheet" type="text/css" href="<?=_asset('css/font-awesome.min.css', false)?>">
    <?php foreach($_links as $_link) { ?>
    <link rel="stylesheet" type="text/css" href="<?=_WValue($_link)?>">
    <?php } ?>
	<script src="<?=_asset('js/responsive.js', false)?>"></script>
	<script src="<?=_asset('js/jquery-3.7.1.min.js', false)?>"></script>
    <script src="<?=_asset('js/utils.js')?>"></script>
	<script src="<?=_asset('js/App.js')?>"></script>
	<script src="<?=_asset('js/shared/TrackMessage.js')?>"></script>
</head>
<body>
    <?php
    require_once VIEWS.'/components/header.php';
    if (authenticated() && !isNoWelcomeBar() && http_response_code() === 200) {
        require_once VIEWS.'/components/menubar.php';
    }
    ?>

	<div id="view-container" class="mb-2">
        <?php require_once $view; ?>
    </div>

    <?php if (!guest()) { ?>
    <script src="<?=_asset('js/shared/IdleTimer.js')?>"></script>
    <?php } ?>
	<script src="<?=_asset('js/sorttable.js')?>"></script>
</body>
</html>