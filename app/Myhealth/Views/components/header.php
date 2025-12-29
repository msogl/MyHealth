<div id="header">
	<div id="logo">
		<a href="home"><img src="<?=_W($_common['LOGO'])?>" border="0" alt="<?=_W($_common['COMPANYNAME'])?> Logo" /></a>
	</div>
	<?php if (_session('loggedInNickname') != '' && http_response_code() === 200) { ?>
	<div id="headerlinks">
		<span class="welcome">Welcome, <?=_W(_session('loggedInNickname'))?></span>&nbsp;&nbsp;
		<a href="logoff">Log off</a> &#149;
		<a href="privacy">Privacy policy</a>
	</div>
	<?php }	else { ?>
	<div class="clear"></div>
	<div id="shadow"></div>
	<?php } ?>
</div>
