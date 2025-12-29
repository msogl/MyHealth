<?php

use Myhealth\Classes\LoginState;
use Myhealth\Classes\Permission;
use Myhealth\Models\MemberModel;

$client = client();

if (_session('loggedin') != '' && _session('login_state') == LoginState::LOGGED_IN) {
	if (_session("loggedInAge") == "") {
		$memberDao = (new MemberModel())->GetById($_SESSION["loggedInMemberId"]);
		_session_put('loggedInAge', $memberDao->Age);
		unset($memberDao);
	}
?>
	<div id="menubar">
		<a href="#" class="mobile"><i class="fa fa-bars"></i></a>
		<div id="menu-wrapper" class="mb-2">
			<ul>
				<li><a href="index">Home</a></li>
				<li><a href="my-claims">Claims</a></li>
				<li><a href="my-referrals">Referrals</a></li>
				<?php if (in_array($client, ['RPPG', 'RPA']) && _session('loggedInAge') >= 18) { ?>
				<li><a href="my-vital-stats">Vital Stats</a></li>
				<?php } ?>
				<li><a href="my-immunizations">Immunizations</a></li>
				<?php if (_session('loggedInAge') >= 18) { ?>
				<li><a href="my-labs">Labs</a></li>
				<?php } ?>
				<li><a href="my-account">Account</a></li>
				<li><a href="doctor-search">Doctors</a></li>
				<li><a href="contact">Contact</a></li>
				<?php if (Permission::isAdmin()) { ?>
				<li><a href="view-logs">View Logs</a></li>
				<li><a href="users">Users</a></li>
				<li><a href="user-review">User Review</a></li>
				<li><a href="fyi">FYI</a></li>
				<?php } ?>
                <?php if (isDeveloper(_session('loggedin'))) { ?>
                <li><a href="sysinfo">System Info</a></li>
                <?php } ?>
			</ul>
		</div>
	</div>
	<script src="<?=_asset('js/views/Menubar.js')?>"></script>
<?php } ?>
