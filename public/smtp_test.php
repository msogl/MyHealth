<?php
//===================================================================
// Copyright by RPPG, 2009-Present. All rights reserved.
//===================================================================
require_once("../core/core.php");

use Myhealth\Classes\Email;

if (php_sapi_name() != 'cli' && _session('loggedin') == '') {
	redirect('login.php');
	exit;
}

if (Request('to') == '' && php_sapi_name() == 'cli') {
	$_POST['to'] = 'jclark@msogl.com';
}

if (Request("to") != "") {
	$htmlTemplate = ReadTemplate('smtp-test.html');

	if ($htmlTemplate == '') {
		$htmlTemplate = 'SMTP Test (template not working)';
	}
	else {
		$htmlTemplate = str_replace('%%SITEURL%%', siteUrl(), $htmlTemplate);
	}

	$mail = new Email();
	echo $mail->Settings();
	if (!$mail->send(Request("to"), null, null, "Testing SMTP", $htmlTemplate, null, true)) {
		echo "Could not send mail.<br/>\n";
		echo $mail->error;
	}
	else {
		echo "Test message sent to '".Request("to")."' via {$mail->sentVia}";
	}
}
else {
	echo "You need to specify a 'to' address.";
}
