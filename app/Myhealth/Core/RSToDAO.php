<?php

namespace Myhealth\Core;

class RSToDAO
{
    private $db;
   	public $autoConvertToUTF8 = false;

    public function __construct(&$db)
    {
        $this->db = &$db;
    }

    public function toDAOs($rs, $daoName, $firstRecordOnly=false)
	{
		if (!$this->db->hasRecords($rs)) {
			return ($firstRecordOnly ? null : array());
		}

		$results = array();
		$obj = new $daoName();
		$objVars = getObjectVars($obj);
		
		$this->correctFieldNames($rs);
		
		while (!$rs->EOF) {
			$obj = new $daoName();
			foreach ($objVars as &$var) {
				if (array_key_exists($var, $rs->fields)) {
					if ($this->db->isMSSQLUUID($rs->fields[$var])) {
						$obj->$var = $this->db->convertUUIDToString($rs->fields[$var]);
					}
					elseif ($this->autoConvertToUTF8) {
						$obj->$var = mb_detect_encoding($rs->fields[$var], 'UTF-8,ASCII,ISO-8859-1,Windows-1252', true) === 'UTF-8' ? $rs->fields[$var] : mb_convert_encoding($rs->fields[$var], 'UTF-8');
					}
					else {
						$obj->$var = $rs->fields[$var];
					}
				}
			}

			$results[] = $obj;
			
			if ($firstRecordOnly) {
				return $this->firstWrapper($results);
			}

			$rs->MoveNext();
			$this->correctFieldNames($rs);
		}

		return $results;
	}

	public function firstWrapper($wrappers)
	{
		return (Utility::isNullOrEmpty($wrappers) ? null : $wrappers[0]);
	}

	private function correctFieldNames(&$rs)
	{
		if ($rs->EOF) {
			return;
		}
		// 11/19/2019 JLC Correct field names, if necessary
		foreach($rs->fields as $key=>$field) {
			$needsChange = false;
			$origkey = $key;

			if (strpos($key, " ") !== false) {
				$key = str_replace(" ", "_", $key);
				$needsChange = true;
			}
	
			if (strpos($key, ":") !== false) {
				$key = str_replace(":", "_", $key);
				$needsChange = true;
			}				

			if ($needsChange) {	
				$rs->fields[$key] = $field;
				unset($rs->fields[$origkey]);
			}
		}
	}
}