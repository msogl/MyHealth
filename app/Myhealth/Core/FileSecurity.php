<?php

namespace Myhealth\Core;

class FileSecurity
{
	const FS_NULL_CHARACTER_DETECTED = 100;
	const FS_PATH_TRAVERSAL_DETECTED = 101;
	const FS_DISALLOWED_EXTENSION = 102;
	const FS_UNSAFE_CHARS = 103;
	const FS_ALTERNATE_DATA_STREAM = 104;

	/**
	 * Prevents path traversal.
	 * @param string $path
	 * @param bool $allowAbsolutePath (optional, default true)
	 * @return string|bool (path if no traversal found, false if found)
	 */
	public static function pathTraversalPrevention(string $path, bool $allowAbsolutePath=true)
	{
		if (trim($path) === '') {
			return $path;
		}

		$caller = Utility::backtraceCaller();

		// First, go through decoding until a final decode matches the
		// last pass through the decoding process.
		$testPath = self::decode($path);

		// If there's even one occurrance of .. in the path, it's no good
		if (strpos($testPath, '..') !== false) {
			Logger::error(__method__." - Path traversal: {$path}".($caller != '' ? "  Called from {$caller}" : ''));
			return false;
		}

		// Normalize the path so we're dealing with the OS separator
		$testPath = Utility::normalizePath($testPath);

		// Treat double separators as one
		$testPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $testPath);

		// Strip trailing path separator
		if (Utility::endsWith($testPath, DIRECTORY_SEPARATOR)) {
			$testPath = substr($testPath, 0, strlen($testPath)-1);
		}

		$isAbsolutePath = Utility::startsWith($testPath, DIRECTORY_SEPARATOR);

		if (!$allowAbsolutePath && $isAbsolutePath) {
			Logger::error(__method__." - Path traversal: absolute path not allowed - {$path}".($caller != '' ? "  Called from {$caller}" : ''));
			return false;
		}

		return $path;
	}

	/**
	 * Detects path traversal.
	 * @param string $path
	 * @param string $allowAbsolutePath (default true)
	 * @return string|bool (path if no traversal found, false if found)
	 */
	public static function pathTraversalDetection(string $path, bool $allowAbsolutePath=true)
	{
		return (self::pathTraversalPrevention($path, $allowAbsolutePath) === false);
	}

	public static function defaultAllowedPaths(): array
	{
		$allowed = [ 
			Utility::normalizePath(TEMPDIR),
		];

		return $allowed;
	}

	/**
	 * Returns whether given path is one of the specific allowed paths
	 * @param string $path
	 * @param array $allowedPaths (optional - uses default allowed paths if not specified)
	 * @return bool
	 */
	public static function isAllowedPath(string $path, array $allowedPaths = []): bool
	{
		$caller = Utility::backtraceCaller();

		if (count($allowedPaths) == 0) {
			$allowedPaths = self::defaultAllowedPaths();
		}

		$testPath = strtolower(Utility::normalizePath($path));

        if (self::isUrlPath($path)) {
            return false;
        }

		foreach ($allowedPaths as &$allowedPath) {
			$allowedPath = strtolower(Utility::normalizePath($allowedPath));

			// Ensure allowed path does not have trailing slash
			if (Utility::endsWith($allowedPath, DIRECTORY_SEPARATOR)) {
				$allowedPath = substr($allowedPath, 0, -1);
			}

			//Logger::debug('testPath:    '.$testPath);
			//Logger::debug('allowedPath: '.$allowedPath);

			if (strcasecmp($testPath, $allowedPath) == 0 ||
				Utility::startsWith($testPath, $allowedPath.DIRECTORY_SEPARATOR)) {
				return true;
			}
		}

		Logger::error(__METHOD__." - Path not allowed: {$path}".($caller != '' ? "  Called from {$caller}" : ''));
		return false;
	}

	/**
	 * Combines a path traversal check and an allowed paths check. Returns normalized
	 * path if all is well, or false if something fails.
	 * Returns false if a path is a URL
	 * @param string $path
	 * @param array $allowedPaths (optional - used default allowed paths if not specified)
	 * @return bool|string
	 */
	public static function sanitizePath(string $path, array $allowedPaths = [])
	{
		$filePath = self::pathTraversalPrevention($path);

		if ($filePath === false) {
			return false;
		}

		if (!self::isAllowedPath($filePath, $allowedPaths)) {
			return false;
		}

		return $filePath;
	}

	public static function isSafeFilename(string $filename)
	{
		$file = str_replace(
			['..', '?', '*', ':', '<', '>', '~', '|'],
			'',
			$filename
		);

		$file = preg_replace(
			'~
			[\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
			[\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
			~x',
			'',
			$file
		);

		return ($file === $filename && strlen($filename) < 256);
	}

	/**
	 * Looks for a colon (:) in the filename. This is a method used
	 * in Windows for Alternate Data Stream, and could be used by 
	 * a threat actor to try to circumvent other protections. This
	 * method will return true if a colon is found at all, regardless
	 * of whether it's part of ADS or not.
	 * Malicious files might be something like: mal.php:.txt or
	 * test::$data.txt
	 * 
	 * @param string $filename
	 * @return Bool_
	 */
	public static function isAlternateDataStream(string $filename): bool
	{
		return (strpos(self::decode($filename), ':') !== false);
	}

	/**
	 * Detects null byte from filename and
	 * return portion of filename up to null byte
	 * to expose the real filename a malicious actor
	 * intends to use.
	 * @param string $filename
	 * @return string
	 */
	public static function nullCharacterDetection(string $filename): string
	{
		$nullChars = [chr(0), '%00', '\x00', '&#0;', '0x00'];
		
		foreach($nullChars as &$nullChar) {
			$pos = strpos($filename, $nullChar);
			if ($pos !== false) {
				$filename = substr($filename, 0, $pos);
			}
		}

		return $filename;
	}

	/**
	 * Sanitized filename by protecting from path traversal. This is
	 * simply a wrapper around decoding, null detection, and basename.
	 * Recommend use of FileSecurity::sanitizePath most of the time.
	 * @param string $filename
	 * @return string
	 */
	public static function sanitizeFilename(string $filename): string
	{
		$filename = self::decode($filename);
		$filename = self::nullCharacterDetection($filename);
		$filename = basename($filename);

		if ($filename === '..') {
			return '';
		}

		return $filename;
	}

	/**
	 * Replaces all characters that are not alphanumerics, space,
	 * period, underscore, or dash with a given replacement
	 * character. Use on filenames only, not on paths.
	 * 
	 * Default replacement character, if not specified, is underscore.
	 * 
	 * @param string $filename
	 * @param string $replaceChar (default = _)
	 * @return string
	 */
	public static function replaceNonAsciiCharacters(string $filename, string $replaceChar = '_')
	{
		return preg_replace("/[^A-Z0-9\ \._-]/i", $replaceChar, $filename);
	}

	public static function isAllowedExtension(string $filename, ?array $allowed = null): bool
	{
		$allowedExt = ($allowed != null ? $allowed : [
			'doc',
			'docx',
			'xls',
			'xlsx',
			'pdf',
			'jpg',
			'gif',
			'png',
			'tif',
			'tiff',
			'csv',
			'txt',
			'pptx',
			'zip'
		]);

		foreach ($allowedExt as &$ext) {
			// Handle regular expression
			if (Utility::startsWith($ext, '/') && Utility::endsWith($ext, '/')) {
				if (!Utility::endsWith($ext, '$/')) {
					$ext = substr($ext, 0, -1).'$/';
				}
				
				if (preg_match($ext, $filename)) {
					return true;
				}
			}

			if (Utility::endsWith(strtolower($filename), "." . strtolower($ext))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets a decoded file from the $_FILES global.
	 * it will return false if a previous error was detected.
	 * @param string $key
	 * return object
	 */
	public static function getFileObject(string $key)
	{
		if (!isset($_FILES[$key])) {
			return null;
		}

		$file = &$_FILES[$key];

		if (is_array($file['name'])) {
			// multiple files
			foreach($file['name'] as &$name) {
				$name = self::decode($name);
			}
		}
		else {
			$file['name'] = self::decode($file['name']);
		}

		return (object)$file;
	}

	public static function getError(string $key)
	{
		if (empty($_FILES[$key]['error'])) {
			return UPLOAD_ERR_OK;
		}

		return $_FILES[$key]['error'];
	}

	public static function safeFileGetContents(string $path)
	{
		return (self::isUrlPath($path) ? false : file_get_contents($path));
	}

	public static function safeFilePutContents(string $filename, mixed $data, int $flags = 0)
	{
		return (self::isUrlPath($filename) ? false : file_put_contents($filename, $data, $flags));
	}

	public static function safeFopen(string $path, string $mode)
	{
		return (self::isUrlPath($path) ? false : fopen($path, $mode));
	}

	public static function safeMove(string $fromPath, string $toPath, array $allowedPaths = [])
	{
		$fromPath = FileSecurity::pathTraversalPrevention($fromPath);
        $toPath = FileSecurity::sanitizePath($toPath, $allowedPaths);

        if ($fromPath === false || $toPath === false) {
            return false;
        }

		try {
			copy($fromPath, $toPath);
			@unlink($fromPath);
		}
		catch(\Exception $e) {
			Logger::error($e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Decode files until they can't be decode anymore.
	 * This should handle double-encoded ones.
	 * @param string $path
	 * @return string
	 */
	private static function decode(string $path): string
	{
		$lastAttempt = $path;
		$decoded = urldecode($path);
		$decoded = html_entity_decode($decoded);
		while ($decoded !== $lastAttempt) {
			$lastAttempt = $decoded;

			$decoded = urldecode($lastAttempt);
			$decoded = html_entity_decode($decoded);
		}

		return $decoded;
	}

    private static function isUrlPath(string $path)
    {
        return (
            preg_match('/^http[s]?:\/\//', $path) ||
            preg_match('/^ftp[s]:\/\//', $path)
        );
    }
}
