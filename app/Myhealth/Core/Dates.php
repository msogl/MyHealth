<?php

namespace Myhealth\Core;

use \DateInterval as DateInterval;
use \DateTime as DateTime;
use \DateTimeZone as DateTimeZone;
use \Exception as Exception;

class Dates
{
	const DATE_FORMAT = 'm/d/Y';					// 01/01/2021
	const DATETIME_FORMAT = 'm/d/Y H:i:s';
	const TIME_FORMAT = 'H:i:s';
	const SQLDATE_FORMAT = 'Y-m-d';					// 2021-01-01
	const SQLDATETIME_FORMAT = 'Y-m-d H:i:s';		// 2021-01-01 00:00:00
	const DATE_NOLEADINGZEROS_FORMAT = 'n/j/Y';		// 1/1/2021
	const DATE_ENGSHORT_FORMAT = 'M j, Y';			// Jan 1, 2021
	const DATE_ENGLONG_FORMAT = 'F j, Y';			// January 1, 2021
	const DATE_UTC_FORMAT = 'D, d M Y h:i:s';		// Fri, 01 Jan 2021 12:00:00 GMT
	const DATE_COOKIE_FORMAT = 'D, d M Y H:i:s';	// Fri, 01 Jan 2021 12:00:00 GMT (24hr clock)
	const DATETIME_FORMAT_AMPM = 'm/d/Y g:ia';		// 01/01/2021 1:30pm
    const DATE_ZULU_FORMAT = 'Y-m-d\TH:i:sp';       // 2021-01-01T00:00:00Z

	public static function toString($date, $format = self::DATETIME_FORMAT, $inFormat = null)
	{
		if (_isNE($date))
			return "";

		$toString = "";

		if (isset($date)) {
			if ($date instanceof DateTime) {
				$toString = $date->format($format);
			} elseif ($date == 'now') {
				$toString = date($format);
			} else {
				try {
					// if the incoming date is in a known format, then use it
					// otherwise we may have a month/day transposing issue
					if (!is_null($inFormat)) {
						$dateval = DateTime::createFromFormat($inFormat, $date);

						// if we can't create using the given format
						if ($dateval === FALSE) {
							throw new Exception("Failed to parse");
						}
					} else {
						$dateval = new DateTime($date);
					}

					$toString = $dateval->format($format);
					unset($dateval);
				} catch (Exception $ex) {
					if (strpos($ex->getMessage(), "Failed to parse") !== FALSE) {
						$attemptFormat = self::getDateFormat($date);

						if ($attemptFormat === FALSE || $attemptFormat == $inFormat) {
							return $date;
						} else {
							return self::toString($date, $format, $attemptFormat);
						}
					}
				}
			}
		}

		return $toString;
	}

	public static function getDateFormat($dateString)
	{
		if (preg_match('/(\d){4}-(\d){2}-(\d){2}/', $dateString)) {
			return "Y-m-d";
		} elseif (preg_match('/(\d){1}-(\d){1}-(\d){4}/', $dateString)) {
			return "n-j-Y";
		} elseif (preg_match('/(\d){1}-(\d){2}-(\d){4}/', $dateString)) {
			return "n-d-Y";
		} elseif (preg_match('/(\d){2}-(\d){1}-(\d){4}/', $dateString)) {
			return "m-j-Y";
		} elseif (preg_match('/(\d){2}-(\d){2}-(\d){4}/', $dateString)) {
			return "m-d-Y";
		} elseif (preg_match('/(\d){4}\/(\d){2}\/(\d){2}/', $dateString)) {
			return "Y/m/d";
		} elseif (preg_match('/(\d){1}\/(\d){1}\/(\d){4}/', $dateString)) {
			return "n/j/Y";
		} elseif (preg_match('/(\d){1}\/(\d){2}\/(\d){4}/', $dateString)) {
			return "n/d/Y";
		} elseif (preg_match('/(\d){2}\/(\d){1}\/(\d){4}/', $dateString)) {
			return "m/j/Y";
		} elseif (preg_match('/(\d){2}\/(\d){2}\/(\d){4}/', $dateString)) {
			return "m/d/Y";
		} elseif (preg_match('/(\d){8}/', $dateString)) {
			if (substr($dateString, 0, 2) == "19" || substr($dateString, 0, 2) == "20") {
				return "Ymd";
			} elseif (substr($dateString, 4, 2) == "19" || substr($dateString, 4, 2) == "20") {
				return "mdY";
			}
		}

		return FALSE;
	}

	public static function datePortion($dateString)
	{
		return self::toString($dateString, self::DATE_FORMAT);
	}

	public static function timePortion($dateString)
	{
		return self::toString($dateString, self::TIME_FORMAT);
	}

    /**
     * Returns number of days between two date strings. Optionally returns as absolute value;
     * @param string $dateString1
     * @param string $dateString2
     * @param bool $absolute (default false)
     * @return int
     */
	public static function dateDiff(string $dateString1, string $dateString2, bool $absolute=false): int
	{
		$dt1 = new DateTime($dateString1);
		$dt2 = new DateTime($dateString2);
		return $dt1->diff($dt2, $absolute)->days;
	}

    /**
     * Returns number of years, months, days, hours, seconds, or minutes between two date strings.
     * @param string $dateString1
     * @param string $dateString2
     * @param int $intervalType
     * @return int
     */
    public static function dateTimeDiff(string $dateString1, string $dateString2, string $intervalType)
    {
        $intervalType = strtolower($intervalType);
        $dt1 = date_create($dateString1);
        $dt2 = date_create($dateString2);
        $diff = date_diff($dt1, $dt2);

        $total = match($intervalType) {
            'y', 'year' => $diff->y + $diff->m / 12 + $diff->d / 365.25,
            'm', 'month' => $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24,
            'd', 'day' => $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60,
            'h', 'hour' => ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60,
            'i', 'minute' => (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60,
            's', 'second' => ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s,
            default => -1
        };

        if ($total == -1) {
            throw new \Exception('Invalid interval type');
        }

        return ($diff->invert ? -1 * $total : $total);
    }

	public static function dateBetweenOld($targetDate, $dateString1, $dateString2)
	{
		if (_isNE($targetDate)) {
			return false;
		}
		
		$target = strtotime($targetDate);
		$dt1 = strtotime($dateString1);
		$dt2 = strtotime($dateString2);
		
		return ($dt1 <= $target && $dt2 >= $target);
	}

	public static function dateBetween($targetDate, $dateString1, $dateString2)
	{
		if (_isNE($targetDate)) {
			return false;
		}
		
		$target = new DateTime($targetDate);
		$dt1 = new DateTime($dateString1);
		$dt2 = new DateTime($dateString2);
		
		return ($dt1 <= $target && $dt2 >= $target);
	}
	
	public static function dateLT($dateString1, $dateString2)
	{
		// is dateString1 less than than dateString2?
		return (self::dateCompare($dateString1, $dateString2) == -1);
	}	
	
	public static function dateGT($dateString1, $dateString2)
	{
		// is dateString1 greater than dateString2?
		return (self::dateCompare($dateString1, $dateString2) == 1);
	}	
	
	public static function dateEQ($dateString1, $dateString2)
	{
		// is dateString1 equal to dateString2?
		return (self::dateCompare($dateString1, $dateString2) == 0);
	}
	
	public static function dateGE($dateString1, $dateString2)
	{
		// is dateString1 greater than or equal to dateString2?
		return (self::dateGT($dateString1, $dateString2) == 1 || self::dateEQ($dateString1, $dateString2));
	}

	public static function dateLE($dateString1, $dateString2)
	{
		// is dateString1 less than or equal to dateString2?
		return (self::dateLT($dateString1, $dateString2) == 1 || self::dateEQ($dateString1, $dateString2));
	}

	public static function dateCompare($dateString1, $dateString2, $english=false, $ignoreTime=false)
	{
		if ($ignoreTime) {
			$dt1 = new DateTime(self::toString($dateString1, self::DATE_FORMAT));
			$dt2 = new DateTime(self::toString($dateString2, self::DATE_FORMAT));
		}
		else {
			$dt1 = new DateTime($dateString1);
			$dt2 = new DateTime($dateString2);
		}

		if ($dt1 < $dt2) {
			return ($english ? "prior" : -1);
		}
		else if ($dt1 == $dt2) {
			return ($english ? "same" : 0);
		}
		else if ($dt1 > $dt2) {
			return ($english ? "after" : 1);
		}
	}

	public static function dateAdd(string $dateString, int $interval, string $type, $dateFormat=Dates::DATETIME_FORMAT)
	{
		$dt = new DateTime($dateString);
		$period = "";

        $format = ($dateFormat ?? self::DATETIME_FORMAT);
		
		// 05/08/2019 JLC Dumb PHP... "P6M" would be 6 months. "PT6M" would be 6 minutes. Ugh.
		// Let's add some clarity.
		if ($type === "month" || $type === "M") {
			$period = "P".abs($interval)."M";
		}
		elseif ($type === "day" || $type === "D") {
			$period = "P".abs($interval)."D";
		}
		elseif ($type === "year" || $type === "Y") {
			$period = "P".abs($interval)."Y";
		}
		elseif ($type === "hour" || $type === "H") {
			$period = "PT".abs($interval)."H";
		}
		elseif ($type === "minute" || $type === "I") {
			$period = "PT".abs($interval)."M";		// here's the dumbness
		}
		elseif ($type === "second" || $type === "S") {
			$period = "PT".abs($interval)."S";
		}
		
		if ($period === "") {
			trigger_error("Unknown \$type in Dates::dateAdd ({$type})", E_USER_WARNING);
			return $dt->format($format);
		}
		
		$dateInterval = new DateInterval($period);

		if ($interval < 0) {
			return $dt->sub($dateInterval)->format($format);
		}
		else {
			return $dt->add($dateInterval)->format($format);
		}
	}
	
	public static function year($date)
	{
		return date('Y', strtotime($date));
	}	
	
	public static function month($date)
	{
		return date('m', strtotime($date));
	}	
	
	public static function day($date)
	{
		return date('d', strtotime($date));
	}
	
	public static function dayOfWeek($date)
	{
		return date('l', strtotime($date));		
	}

	public static function isDate($date)
	{
		if (self::getDateFormat($date) === false) {
			return false;
		}

		try {
			$dt = new DateTime($date);
			return true;
		}
		catch(Exception $e) {
			return false;
		}
	}

	public static function beginningOfMonth($date)
	{
		$dt = ($date == 'now' ? new DateTime() : new DateTime($date));
		return $dt->modify('first day of this month')->format('m/d/Y'); 
	}

	public static function endOfMonth($date)
	{
		$dt = ($date == 'now' ? new DateTime() : new DateTime($date));
		return $dt->modify('last day of this month')->format('m/d/Y'); 
	}

	public static function sqlDate($date)
	{
		$dt = self::toString($date, self::SQLDATE_FORMAT);
		return ($dt == '' ? null : $dt);
	}

	public static function sqlDateTime($date)
	{
		$dt = self::toString($date, self::SQLDATETIME_FORMAT);
		return ($dt == '' ? null : $dt);
	}

	public static function timeDiff($dateString1, $dateString2, $absolute=false)
	{
		$tm1 = strtotime($dateString1);
		$tm2 = strtotime($dateString2);
		echoln($tm1);
		echoln($tm2);
		$diff = $tm2 - $tm1;
		echoln($diff);
		return ($absolute ? abs($diff) : $diff);
	}

	public static function toUTCString($dateString)
	{
		return gmdate(self::DATE_UTC_FORMAT, strtotime($dateString)).' GMT';
	}

	public static function fromUTC(string $dateString, $format=self::DATETIME_FORMAT)
	{
		$dt = new DateTime($dateString, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
		return self::toString($dt, $format);
	}

    public static function zulu($dateString)
    {
        $dt = new DateTime($dateString, new DateTimeZone('UTC'));
		return self::toString($dt, self::DATE_ZULU_FORMAT);
    }
}
