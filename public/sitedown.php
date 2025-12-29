<?php
//===================================================================
// Copyright by MSO Great Lakes, 2015-Present. All rights reserved.
//===================================================================
require_once("../core/core.php");

use Myhealth\Classes\Common;
use Myhealth\Classes\Permission;

if (_session('loggedin') == '') {
	redirect('login.php');
	exit;
}

$oCommon = new Common();
$client = $oCommon->getConfig('CLIENTID', '');
if (!Permission::isAdmin()) {
	unauthorized();
}

if (Request('go') == '1') {
	// Create a site down file

	$contents = '<!DOCTYPE html>'."\n";
	$contents .= '<html lang="en">'."\n";
	$contents .= '<head>'."\n";
	$contents .= '	<title>Site Down Message</title>'."\n";
	$contents .= '	<meta http-equiv="Pragma" content="no-cache">'."\n";
	$contents .= '	<meta http-equiv="Expires" content="0">'."\n";
	$contents .= '	<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";
	$contents .= '	<link rel="stylesheet" type="text/css" href="assets/css/default.css?<?=epoch()?>" />'."\n";
	$contents .= '	<link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">'."\n";
	$contents .= '	<script type="text/javascript" src="assets/js/responsive.js"></script>'."\n";
	$contents .= '</head>'."\n";
	$contents .= '<body>'."\n";
	$contents .= '	<div style="margin:1em;text-align:center;">'."\n";
	$contents .= '	<h1 style="margin-bottom:2em;">'.Request('heading').'</h1>'."\n";
	$contents .= str_replace("\n", '<br/>', Request('message'))."\n";
	$contents .= '	</div>'."\n";
	$contents .= '</body>';
	$contents .= '</html>';
	file_put_contents(__DIR__.'/../templates/sitedown.html', $contents);
	end_session();
	redirect('index.php');
	exit;
}
?>
<!DOCTYPE html>

<html lang="en">
<head>
	<title>Site Down Message</title>
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="0">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="assets/css/default.css?<?=epoch()?>" />
	<link rel="stylesheet" type="text/css" href="assets/css/font-awesome.min.css">
	<script type="text/javascript" src="assets/js/responsive.js"></script>
	<script type="text/javascript" src="assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
	<?php require_once("header.php"); ?>
	<?php require_once("menubar.php"); ?>

	<div id="main-container">
		<h1>Site Down Message</h1>
		<div class="main-inner">
			<form method="POST" action="sitedown.php" name="sitedown">
				<input type="hidden" name="go" value="1" />
				<input type="hidden" name="token" value="<?=getTokenOnly()?>" />
				<div>
					<strong>Main Heading: </strong><br/>
					<input type="text" class="text" id="heading" name="heading" value="SYSTEM DOWN FOR MAINTENANCE" size="62" maxlength="255" />
				</div>
				<div class="top-pad1">
					<strong>Site Down Message</strong><br/>
					<textarea id="message" name="message" style="width:100%;height:10em;"></textarea>
				</div>
				<div class="right">
					<input type="submit" class="button" value="Submit" style="width:60px;" onclick="document.sitedown.submit();"/>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
