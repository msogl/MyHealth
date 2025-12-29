<?php

namespace Myhealth\Daos;

class LockoutDAO
{
	public $id = null;
	public $username = null;
	public $last_attempt = null;
	public $attempt_count = null;
	public $is_locked = null;
}
