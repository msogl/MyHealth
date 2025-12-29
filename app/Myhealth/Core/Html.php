<?php

namespace Myhealth\Core;

use voku\helper\AntiXSS;

class Html
{
	public const SAFE_QUOTES = 1;
	
	public static function write($text, $replaceNewline=TRUE)
	{
		if (is_null($text))
			echo '';
		else
			echo self::encode($text, $replaceNewline);
	}

	public static function writeln ($text, $replaceNewline=TRUE)
	{
		echo self::write($text, $replaceNewline);
		echo "<br />";	
	}
	
	public static function writeOrBlank($text, $replaceNewLine=TRUE)
	{
		if (Utility::isNullOrEmpty($text)) {
			echo "&nbsp;";
		}
		else {
			self::write($text, $replaceNewLine);
		}
	}
	
	public static function encode($text, bool $replaceNewline=true)
	{
		if (is_null($text)) {
			return '';
		}

		if (is_object($text) || is_array($text)) {
			return '';
		}

		$text = urldecode($text);
		$text = mb_detect_encoding($text, mb_detect_order(), true) === 'UTF-8' ? $text : mb_convert_encoding($text, 'UTF-8');
		$html = htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

		// Handle some simple markup
		$html = str_replace('[br]', '<br/>', $html);
		$html = str_replace('[i]', '<em>', $html);
		$html = str_replace('[/i]', '</em>', $html);
		$html = str_replace('[u]', '<u>', $html);
		$html = str_replace('[/u]', '</u>', $html);
		$html = str_replace('[b]', '<strong>', $html);
		$html = str_replace('[/b]', '</strong>', $html);
		$html = str_replace('[nb]', '&nbsp;', $html);
		$html = str_replace('[indent]', '&nbsp;&nbsp;&nbsp;&nbsp;', $html);
		
		if ($replaceNewline) {
			$html = str_replace(['\r\n', '\r', '\n'], '<br>', $html);
		}
		
		return $html;
	}
	
	public static function contentType($filename, $forceDownload)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	
		switch($ext) {
			case "doc":
				header('Content-type: application/msword');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "docx":
				header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "xls":
				header('Content-type: application/ms-excel');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "xlsx":
				header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "pdf":
				header('Content-type: application/pdf');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
			case "jpg":
				header('Content-type: image/jpeg');
				break;
			case "gif":
				header('Content-type: image/gif');
				break;
			case "png":
				header('Content-type: image/x-png');
				break;
			case "ppt":
				header('Content-type: application/vnd.ms-powerpoint');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "pptx":
				header('Content-type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				break;
			case "txt":
				header('Content-type: text/plain');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
			case "mp3":
				header('Content-type: audio/mp3');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
			case "mp4":
				header('Content-type: video/mp4');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
			case "wav":
				header('Content-type: audio/wav');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
			case "xml":
				header('Content-type: text/xml');
				break;
			default:
				header('Content-type: text/html');
				header('Content-Disposition: '.($forceDownload ? "attachment" : "inline").'; filename="'.$filename.'"');
				break;
		}
	}

	public static function sanitize($value)
	{
		$antiXss = new AntiXSS();
		$value = "[".$value."]";								// wrap
		$value = $antiXss->xss_clean($value);
		$value = substr($value, 1, strlen($value)-2);			// unwrap
		return $value; 		
	}
	
	public static function deepEncode($thing)
	{
		$thingIsObj = false;

		if (is_object($thing)) {
			$vars = get_object_vars($thing);
			$thingIsObj = true;
		}
		elseif (is_array($thing)) {
			$vars = &$thing;
		}
		else {
			$thing = self::encode($thing);
			return $thing;
		}

		foreach($vars as $key=>$val) {
			if ($thingIsObj) {
				$thing->$key = self::deepEncode($val);
			}
			else {
				$thing[$key] = self::deepEncode($val);
			}
		}

		return $thing;
	}
}
