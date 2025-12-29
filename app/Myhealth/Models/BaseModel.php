<?php

namespace Myhealth\Models;

use Myhealth\Classes\Common;
use Myhealth\Core\DB;

class BaseModel
{
	protected $db = null;
	protected $common = null;
	protected $client = null;

	public function __construct()
	{
		$this->db = new DB();
		$this->common = new Common();
		$this->client = $this->common->getConfig("CLIENTID", "");
	}
}