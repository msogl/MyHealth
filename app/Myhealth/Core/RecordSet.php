<?php
//===================================================================
// Copyright by MSO Great Lakes, 2004-Present. All rights reserved.
//===================================================================

namespace Myhealth\Core;

use \Exception as Exception;

class RecordSet
{
	const STATEMENT_UNPREPARED = 0;
	const STATEMENT_PREPARED = 1;

	public $stmt = null;
	public $fields = null;
	public $BOF = true;
	public $EOF = true;

	private $statementType;
	
	public function __construct($stmt, $statementType)
	{
		if ($statementType != RecordSet::STATEMENT_UNPREPARED && $statementType != RecordSet::STATEMENT_PREPARED) {
			throw new Exception('Invalid statement type');
		}

		$this->stmt = (!is_bool($stmt) ? $stmt : null);
		$this->statementType = $statementType;
		$this->MoveFirst();
	}

	public function __destruct()
	{
		$this->stmt = null;
	}

	public function MoveNext()
	{
		if (is_null($this->stmt)) {
			$this->BOF = false;
			$this->EOF = true;
			return;
		}

		if ($this->statementType == RecordSet::STATEMENT_UNPREPARED) {
			$this->fields = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
		}
		elseif ($this->statementType == RecordSet::STATEMENT_PREPARED) {
			$this->fields = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
		}

		$this->BOF = false;
		$this->EOF = ($this->fields === false);
	}

	public function MoveFirst()
	{
		if (is_null($this->stmt)) {
			$this->BOF = false;
			$this->EOF = true;
			return;
		}

		if ($this->statementType == RecordSet::STATEMENT_UNPREPARED) {
			$this->fields = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_FIRST);
		}
		elseif ($this->statementType == RecordSet::STATEMENT_PREPARED) {
			try {
				$this->fields = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_FIRST);
			}
			catch(Exception $e) {
				if (Contains($e->getMessage(), 'The active result for the query contains no fields.')) {
					$this->fields = false;
				}
				else {
					logIt($e->getCode().': '.$e->getMessage());

					if (isset($this->stmt->queryString)) {
						logIt($this->stmt->queryString);
					}
				}
			}
		}

		$this->BOF = ($this->fields === true);
		$this->EOF = ($this->fields === false);
	}

	public function Close()
	{
		$this->stmt = null;
	}
}