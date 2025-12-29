<?php

namespace Myhealth\Core;

class Utility
{
	public static function backtraceCaller()
	{
		$bt = debug_backtrace();
		array_shift($bt);			// remove this class
		array_shift($bt);			// and the one before it

		if (count($bt) == 0) {
			return '';
		}
		
		$trace = &$bt[0];
		
		if (!empty($trace['class'])) {
			return $trace['class']."::".$trace['function'];
		}

		return $trace['function'];
	}

    public static function normalizePath($path)
	{
		$path = str_replace("/", DIRECTORY_SEPARATOR, $path);
		$path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
		return $path;
	}

    public static function startsWith($haystack, $needle)
	{
		if (is_null($haystack) || is_null($needle)) {
			return false;
		}

		$length = strlen($needle);
		return (substr($haystack, 0, $length) == $needle);
	}

	public static function endsWith($haystack, $needle)
	{
		if (is_null($haystack) || is_null($needle)) {
			return false;
		}

		$length = strlen($needle);
		if ($length == 0)
			return true;

		return (substr($haystack, -$length) == $needle);
	}

	public static function in($haystack, $needle, $caseInsensitive=false)
	{
		if ($caseInsensitive) {
			return InList(strtolower($needle), strtolower($haystack));
		}
		else {
			return InList($needle, $haystack);
		}
	}

	public static function isNullOrEmpty($value)
	{
        if (!isset($value) || is_null($value)) {
            return true;
        }
    
        if (is_array($value) && count($value) == 0) {
            return true;
        }
    
        if (!is_array($value) && !is_object($value) && trim($value) == "") {
            return true;
        }
    
        return false;
	}

	public static function generateGUID()
	{
		return generateGUID();
	}

    public static function numericOnly($str)
	{	
		return preg_replace('/[^\d]/', '', $str);
	}

    public static function obfuscate($varName, $value, $type=null, &$phiRandomizer=null)
	{
		if (is_null($phiRandomizer)) {
			$phiRandomizer = new PHIRandomizer();
		}
		$v = strtolower(str_replace("_", "", $varName));
		
		if (is_null($type)) {
			$foundType = null;
			foreach($GLOBALS["PHI_VARS"] as $field=>&$type) {
				if ($v == $field) {
					$foundType = $type;
				}
			}
		}
		else {
			$foundType = $type;
		}
		
		if (!is_null($foundType)) {
			if (!Utility::isNullOrEmpty($value)) {
				// set random seed based on current date and crc32 value of $value
				// so that the same string will randomize the same way each time
				srand(crc32(date('Ym').$value));
				$alphaChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
				$numericChars = "0123456789";
				$specialChars = str_split("\ \"\\`~!@#$%^&*()-_=+[];'{}:,.<>/?");
				
				if ($foundType == "date") {
					$boy = strtotime("01/01/".Dates::year($value));
					$eoy = strtotime("12/31/".Dates::year($value));
					$dt = rand($boy, $eoy);
					
					if (strpos($value, ":") !== false) {
						return date("m/d/Y H:i:s", $dt);
					}
					else {
						return date("m/d/Y", $dt);
					} 					
				}
				elseif ($foundType == "phone") {
					if (strlen(self::numericOnly($value)) == 10) {
						return "9999999999";
					}
				}
				elseif ($foundType == 'randomfirst') {
					return strtoupper($phiRandomizer->firstName($value));
				}
				elseif ($foundType == 'randomlast') {
					return strtoupper($phiRandomizer->lastName($value));
				}
				elseif ($foundType == 'randomfullname') {
					return strtoupper($phiRandomizer->fullName($value));
				}
				elseif ($foundType == 'randomaddress') {
					return strtoupper($phiRandomizer->address($value));
				}
				elseif ($foundType == 'randomaddress2') {
					return strtoupper($phiRandomizer->address2($value));
				}
				elseif ($foundType == 'randomcity') {
					return strtoupper($phiRandomizer->city($value));
				}
				elseif ($foundType == 'randomstate') {
					return 'IL';
				}
				elseif ($foundType == 'randomzip') {
					return strtoupper($phiRandomizer->zip($value));
				}

				$len = strlen($value);
				$newval = "";

				for($ix=0;$ix<$len;$ix++) {
					$ch = substr($value, $ix, 1);
					if (is_numeric($ch)) {
						$newval .= substr($numericChars, rand(0,9), 1);
					}
					elseif (in_array($ch, $specialChars)) {
						$newval .= $ch;
					}
					else { 
						$newval .= substr($alphaChars, rand(0,25), 1);
					}
				}
				
				return $newval;
			}
		}
			
		return $value;
	}

	public static function backtraceLite()
	{
		$bt = debug_backtrace();
		array_shift($bt);			// remove this class
		
		$btText = "";
		
		foreach($bt as &$trace) {
			if (!isset($trace['line'])) {
				$trace['line'] = 'unknown';
			}
			if (isset($trace["class"]) && $trace["class"] != "") {
				$btText .= $trace["class"]."->".$trace["function"]."() line ".$trace["line"]."\n";
			}
			else {
				$btText .= basename($trace["file"]).", ".$trace["function"]." line ".$trace["line"]."\n";
			}
		}
		
		return $btText;
	}
}
