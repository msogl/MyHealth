<?php

namespace Myhealth\Core\Syslog;

/*********************************************************************
 *
 * Description
 *
 *   The Syslog class is a syslog device implementation in PHP
 *   following the RFC 3164 & RFC 5424 (sort of) rules.
 *
 *   Facility values:
 *      0 kernel messages
 *      1 user-level messages
 *      2 mail system
 *      3 system daemons
 *      4 security/authorization messages
 *      5 messages generated internally by syslogd
 *      6 line printer subsystem
 *      7 network news subsystem
 *      8 UUCP subsystem
 *      9 clock daemon
 *     10 security/authorization messages
 *     11 FTP daemon
 *     12 NTP subsystem
 *     13 log audit
 *     14 log alert
 *     15 clock daemon
 *     16 local user 0 (local0) (default value)
 *     17 local user 1 (local1)
 *     18 local user 2 (local2)
 *     19 local user 3 (local3)
 *     20 local user 4 (local4)
 *     21 local user 5 (local5)
 *     22 local user 6 (local6)
 *     23 local user 7 (local7)
 *
 *   Severity values:
 *     0 Emergency: system is unusable
 *     1 Alert: action must be taken immediately
 *     2 Critical: critical conditions
 *     3 Error: error conditions
 *     4 Warning: warning conditions
 *     5 Notice: normal but significant condition (default value)
 *     6 Informational: informational messages
 *     7 Debug: debug-level messages
 *
 *
 * Usage
 *
 *   require_once('syslog.php');
 *   $syslog = new Syslog();
 *   $syslog->server = '127.0.0.1';
 *   $syslog->port = 514;    // Default = 514 if not specified
 *   $syslog->hostname = gethostname();
 *   $syslog->fqdn = gethostbyaddr(gethostbyname($hostname));
 *   $syslog->ip_from = gethostbyname($hostname);
 *   $syslog->facility = 1;
 *   $syslog->severity = 6;
 *   $syslog->appname = 'test@12345';
 *   $syslog->structured_data = 'test@test id="1" user="someuuser" event="login" result="login failed"';
 *   $syslog->message = "testing";
 *   $syslog->send();
 *  
 * Notes
 *   structured_data is optional. It must conform to the RFC. This library does not enforce
 *   conformance. Be sure to escape double-quote, backslash, left, and right square brackets within
 *   structured data parameters. (e.g., [test@12345 name="Dan \"The Man\""])
 *   message is optional and is freeform text. Either structured_data or message is required.
 *   procid is optional
 *   msgid is optional
 *
 * Change Log
 *
 * 	 2023-10-09 1.2 Joe Clark Initial release
 *
 *********************************************************************/
 
    class Syslog
    {
		public const RFC3164 = 3164;
		public const RFC5424 = 5424;
		public const VERSION = 1;
		public const NILVALUE = '-';
		public const UTF8_BOM = b"\xEF\xBB\xBF";

        public $facility; // 0-23
        public $severity; // 0-7
        public $hostname; // no embedded space, no domain name, only a-z A-Z 0-9 and other authorized characters
        public $fqdn;
        public $ip_from;
        public $process;
		public $appname;
		public $procid;
		public $msgid;
		public $structured_data;
        public $message;
        public $server;   // Syslog destination server
        public $port;     // Standard syslog port is 514
		public $rfc;
        
        function __construct() {
            $this->server   = '127.0.0.1';
            $this->port     = 514;
            $this->facility = 16;
            $this->severity = 5;
            $this->hostname = gethostname();
			$this->hostname = substr($this->hostname, 0, strpos($this->hostname.".", "."));
			$this->fqdn = $_SERVER['SERVER_NAME'] ?? gethostbyaddr(gethostbyname(gethostname()));
			$this->ip_from = $_SERVER['SERVER_ADDR'] ?? '';
			$this->process = $this->appname = 'PHP';
			$this->procid = self::NILVALUE;
			$this->msgid = self::NILVALUE;
			$this->structured_data = self::NILVALUE;
			$this->message = '';
			$this->rfc = self::RFC3164;
        }
        
        function send() {
            if ($this->facility <  0) { $this->facility =  0; }
            if ($this->facility > 23) { $this->facility = 23; }
            if ($this->severity <  0) { $this->severity =  0; }
            if ($this->severity >  7) { $this->severity =  7; }
            
            $this->process = substr($this->process, 0, 32);
			$pri = "<".($this->facility*8 + $this->severity).">";

			if ($this->rfc == self::RFC3164) {
				$message = $this->formatRFC3164($pri);
			}
			elseif ($this->rfc == self::RFC5424) {
				$message = $this->formatRFC5424($pri);
			}
			else {
				throw new \Exception('Unexpect format');
			}

			return $this->writeUDP($message);
        }

		public function formatRFC3164($pri) {
			if ($this->appname != 'PHP' && $this->process == 'PHP') {
				$this->process = $this->appname;
			}

			$timestamp = \date('M j H:i:s');
			$header = $timestamp." ".$this->hostname;
			$message = $this->process.": ".$this->fqdn." ".$this->ip_from." ".$this->message;
			return $this->truncate($pri.$header." ".$message, 1024);
		}

		public function formatRFC5424($pri) {
			if ($this->process != 'PHP' && $this->appname == 'PHP') {
				$this->appname = $this->process;
			}

			$timestamp = \date('Y-m-d\TH:i:sP');

			$header = $pri
				.self::VERSION
				.' '
				.$timestamp
				.' '
				.$this->truncate($this->hostname ?? self::NILVALUE, 255)
				.' '
				.$this->truncate($this->appname ?? self::NILVALUE, 48)
				.' '
				.$this->truncate($this->procid ?? self::NILVALUE, 128)
				.' '
				.$this->truncate($this->msgid ?? self::NILVALUE, 32);

			$this->structured_data = \mb_convert_encoding($this->structured_data, 'UTF-8');
			$this->message = \mb_convert_encoding($this->message, 'UTF-8');
			
			if ($this->structured_data != self::NILVALUE) {
				$structured_data = $this->structured_data;
			}
			else {
				$structured_data = self::NILVALUE;
			}

			$message = trim($header.' '.$structured_data.' '.$this->message);
			return $this->truncate($message, 2048*8);
		}

		public function writeUDP($message) {
			$fp = fsockopen("udp://".$this->server, $this->port, $errno, $errstr);

			if ($fp) {
					fwrite($fp, $message);
					fclose($fp);
					$result = $message;
			}
			else {
					$result = "ERROR: $errno - $errstr";
			}

			return $result;
		}

		public function escapeParam($value) {
			if ($value === null) {
				return '';
			}

			$value = \str_replace("\\", "\\\\", $value);
			$value = \str_replace("\"", "\\\"", $value);
			$value = \str_replace('[', '\[', $value);
			$value = \str_replace(']', '\]', $value);
			return $value;
		}

		private function truncate($text, $length) {
			return (strlen($text) > $length ? substr($text, 0, $length) : $text);
		}
    }
