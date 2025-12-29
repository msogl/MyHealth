<?php

namespace Myhealth\Core;

class Crypto
{
	const LOWER = 'abcdefghijklmnopqrstuvwxyz';
	const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const NUMBER = '0123456789';
	const SPECIAL = "`~!@#$%^&*()-=_+[]\{}|;':,./!=?\"";

	/**
	 * AES-256 encryption using openssl
	 * @param string $value
	 * @param bool $encode - true = base64_encode, false = raw
	 * @return string
	 */
	public static function encrypt_msogl_2024(string $value, bool $encode=false, ?string $key=null): string
	{
		//$key = openssl_random_pseudo_bytes(32);
		if ($key == null) {
			$key = $_ENV['ENC_KEY'] ?? '';
		}

		if (empty($key) || strlen($key) != 32) {
			throw new \Exception('Cannot encrypt. Encryption key not properly configured.');
		}

		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
		$encrypted .= '=.'.base64_encode($iv);
		return ($encode ? base64_encode($encrypted) : $encrypted);
	}

	// 05/06/2024 JLC Old way. Eventually remove.
	public static function encrypt_msogl(string $value, bool $encode=false): string
	{
		$key = openssl_random_pseudo_bytes(32);
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($value, 'aes-256-cbc', $key, 0, $iv);
		$encrypted .= '=.'.base64_encode($key).'=.'.base64_encode($iv);
		return ($encode ? base64_encode($encrypted) : $encrypted);
	}

	/**
	 * AES-256 decryption using openssl
	 * If base64-encoded string passed in, it will be automatically
	 * decoded.
	 * @param string $value
	 * @return string
	 */
	public static function decrypt_msogl_2024(string $value, $key=null): string
	{
		// Attempt base64 decode
		$possible = base64_decode($value);
		if (base64_encode($possible) === $value) {
			$value = $possible;
		}

		$parts = explode('=.', $value);

		if (count($parts) == 1) {
			// Possibly legacy delimiter
			$parts = explode('||', $value);
		}

		if (count($parts) >= 3) {
			$bt = explode("\n", Utility::backtraceLite());
			Logger::logToFile("IMPORTANT! Legacy AES in use: {$bt[1]}", LOGPATH.'/legacy_%Y%m%d.log');
			return self::decrypt_msogl($value);
		}

		if (count($parts) < 2) {
			return $value;
		}

		if ($key == null) {
			if (empty($_ENV['ENC_KEY']) || strlen($_ENV['ENC_KEY']) != 32) {
				throw new \Exception('Cannot decrypt. Encryption key not properly configured.');
			}

			$key = $_ENV['ENC_KEY'];
		}

		// b64-encode pw, b64-decoded key, b64-decoded IV
		// rtrim the junk off the end
		// return rtrim(openssl_decrypt($parts[0], 'aes-256-cbc', base64_decode($parts[1]), OPENSSL_ZERO_PADDING, base64_decode($parts[2])), "\x00..\x20");
        
        
        // 08/25/2025 JLC Changed $options parameter to 0 (PKCS#7 padding, which is the default if options specified)
        // instead of OPENSSL_ZERO_PADDING, which matches the corresponding openssl_encrypt, and use custom trim.
        // 10/16/2025 JLC First attempt with PKCS#7 padding; and if that results is a zero-length string, try the
        // old OPENSSL_ZERO_PADDING. Some might have an issue with the PKCS#7 padding.
        if (count($parts) < 2) {
            throw new \Exception('Cannot decrypt. Unexpected data.');
        }
        
		$dec = openssl_decrypt($parts[0], 'aes-256-cbc', $key, 0, base64_decode($parts[1]));
        if (strlen($dec) == 0) {
            $dec = openssl_decrypt($parts[0], 'aes-256-cbc', $key, OPENSSL_ZERO_PADDING, base64_decode($parts[1]));
        }

        return self::trimDecrypted($dec);
	}

	// 05/06/2024 JLC Old way. Eventually remove.
	public static function decrypt_msogl(string $value): string
	{
		// Attempt base64 decode
		$possible = base64_decode($value);
		if (base64_encode($possible) === $value) {
			$value = $possible;
		}

		$parts = explode('=.', $value);

		if (count($parts) < 3) {
			// Possibly dealing with legacy delimiter
			$parts = explode('||', $value);
		}

		if (count($parts) < 3) {
			return $value;
		}

		// b64-encode pw, b64-decoded key, b64-decoded IV
		// rtrim the junk off the end
		//return rtrim(openssl_decrypt($parts[0], 'aes-256-cbc', base64_decode($parts[1]), OPENSSL_ZERO_PADDING, base64_decode($parts[2])), "\x00..\x20");
        
        
        // 08/25/2025 JLC Changed $options parameter to 0 (PKCS#7 padding, which is the default if options specified)
        // instead of OPENSSL_ZERO_PADDING, which matches the corresponding openssl_encrypt, and use custom trim.
        // 10/16/2025 JLC First attempt with PKCS#7 padding; and if that results is a zero-length string, try the
        // old OPENSSL_ZERO_PADDING. Some might have an issue with the PKCS#7 padding.
        $dec = openssl_decrypt($parts[0], 'aes-256-cbc', base64_decode($parts[1]), 0, base64_decode($parts[2]));
        if (strlen($dec) == 0) {
            $dec = openssl_decrypt($parts[0], 'aes-256-cbc', base64_decode($parts[1]), OPENSSL_ZERO_PADDING, base64_decode($parts[2]));
        }

        return self::trimDecrypted($dec);
	}

    /**
     * Encrypts a file in place. Overwrites given filepath with encrypted data. Throws exception on error.
     * 
     * @param string $path full path to file
     * @param ?string $key Optional key; defaults to system key FILE_ENC_KEY
     * @return bool
     */
    public static function encrypt_file(string $path, ?string $key=null): bool
    {
        if ($key === null && !isset($_ENV['FILE_ENC_KEY'])) {
            throw new \Exception("Unable to encrypt {$path}; FILE_ENC_KEY does not exist");
        }

        if ($key === null) {
            $key = $_ENV['FILE_ENC_KEY'];
        }

        if (!file_exists($path)) {
            throw new \Exception("Unable to encrypt {$path}; file does not exist");
        }

        $tmpFile = TEMPDIR.'/'.basename($path).'.encrypt.tmp';

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \Exception("Unable to encrypt {$path}; could not open file");
        }

        file_put_contents($tmpFile, self::encrypt_msogl_2024($contents, true, $key));

        if (!self::verify_encrypted_file($path, $tmpFile, $key)) {
            unlink($tmpFile);
            throw new \Exception("Encryption of {$path} failed verification process");
        }

        // Encryption verified; delete original file and move encrypted one into place
        unlink($path);
        rename($tmpFile, $path);
        return true;
    }

    /**
     * Decrypts a given file into a new file. Throws exception on error.
     * 
     * @param string $path path of file to decrypt
     * @param ?string $decryptedFilename full path of decrypted file; if null, returns decrypted contents as string
     * @param ?string $key optional encryption key; defaults to system key FILE_ENC_KEY
     * @return string|bool
     */
    public static function decrypt_file(string $path, ?string $decryptedFilename, ?string $key=null): string|bool
    {
        if ($key === null && !isset($_ENV['FILE_ENC_KEY'])) {
            throw new \Exception("Unable to encrypt {$path}; FILE_ENC_KEY does not exist");
        }

        if ($key === null) {
            $key = $_ENV['FILE_ENC_KEY'];
        }

        if (!file_exists($path)) {
            throw new \Exception("Unable to decrypt {$path}; file does not exist");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \Exception("Unable to encrypt {$path}; could not open file");
        }

        $decrypted = self::decrypt_msogl_2024($contents, $key);

        if ($decryptedFilename == null) {
            return $decrypted;
        }
        $res = file_put_contents($decryptedFilename, $decrypted);
        if ($res === false) {
            return false;
        }

        return true;
    }

	/**
	 * @param string $secret
	 * @return object - base64_encoded hash and salt
	 */
	public static function pbkdf2hash(string $secret): object
	{
		$iterations = 10000;

		$AESKeyLength = 32;
		$AESIVLength = 16;
		$derived_bytes = 20;
		$raw_salt = random_bytes(16);
		$raw_hash = hash_pbkdf2('sha1', $secret, $raw_salt, $iterations, $AESKeyLength + $AESIVLength, true);

		$hash = new \stdClass();
		$hash->hash = base64_encode(substr($raw_hash, 0, $derived_bytes));
		$hash->salt = base64_encode($raw_salt);
		return $hash;
	}

	/**
	 * Hash a password with most recent algorithm
	 * 
	 * @param string $password
	 * @param ?int $hash_version (if null, uses system defined HASH_VERSION)
	 * @return object
	 */
	public function hash_password(string $password, ?int $hash_version=null): object
	{
		$useVersion = $hash_version ?? HASH_VERSION;

		if ($useVersion == 3) {
			// Options per OWASP recommendations
			// https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
			$opts = [
				'memory_cost'=>19456,
				'time_cost'=>2,
				'threads'=>1,
			];
			
			$hashed = password_hash($password, PASSWORD_ARGON2ID, $opts);
			$hash = new \stdClass();
			$hash->hash = $hashed;
			$hash->salt = null;
			$hash->version = $useVersion;
			return $hash;
		}
		elseif ($useVersion == 2) {
            return $this->pbkdf2hash($password);
		}

        throw new \Exception('Invalid hash version');
	}

	/**
	 * @param string $value - the password or some other raw string
	 * @param string $hash - the hash to compare to
	 * @param ?string $salt - the salt needed to hash the password (hash version 2 only)
	 * @param ?int $hash_version - defaults to current defined HASH_VERSION
	 * @param bool $forceCOM - forces use of COM component (hash version 2 only)
	 * @return bool
	 * NOTE: $hash and $salt are in the opposite order as MSOGLCrypto->HashCompare.
	 * The $salt will eventually go away with the use of the built-in password_hash
	 * function. Also, $hash and $salt may be base64_encode or not. This will
	 * attempt to decode it if it is.
	 */
	public function hash_compare(string $value, string $hash, ?string $salt, ?int $hash_version=null, $forceCOM=false): bool
	{
		$useVersion = (_isNEZ($hash_version) ? HASH_VERSION : $hash_version);

		$possible = base64_decode($hash);
		$isHashB64 = (base64_encode($possible) === $hash);
		if ($isHashB64) {
			// it came in base64, so use the decoded one
			$hash = $possible;
		}

		if ($useVersion == 3) {
			return password_verify($value, $hash);
		}
		elseif ($useVersion == 2) {
			$possible = base64_decode($salt);
			$isSaltB64 = (base64_encode($possible) === $salt);
			if ($isSaltB64) {
				// it came in base64, so use the decoded one
				$salt = $possible;
			}

			try {
                $iterations = 10000;

                $AESKeyLength = 32;
                $AESIVLength = 16;
                $derived_bytes = 20;

                $raw_hash = $hash;
                $raw_salt = $salt;
                $new_hash = hash_pbkdf2('sha1', $value, $raw_salt, $iterations, $AESKeyLength + $AESIVLength, true);

                return hash_equals(substr($raw_hash, 0, $derived_bytes), substr($new_hash, 0, $derived_bytes));
			}
			catch(\Exception $e) {
				Logger::error($e);
				return false;
			}
		}
		else {
			Logger::error('Unsupported hash version: '.$useVersion);
			return false;
		}
	}

	/**
	 * A more cryptographically-secure secret generator.
	 * @param number $length default 16
	 * @return string
	 */
	public static function generateSecret(int $length=16): string
	{
		$chars = Crypto::LOWER.Crypto::UPPER.Crypto::NUMBER.Crypto::SPECIAL;

		$shuffleTimes = random_int(1,10);
		for($ix=0;$ix<$shuffleTimes;$ix++) {
			$chars = str_shuffle($chars);
		}

		do {
			$secret = '';
			
			for($ix=0;$ix<$length;$ix++) {
				$pos = random_int(0, strlen($chars)-1);
				$secret .= substr($chars, $pos, 1);
			}
		}
		while(!self::testStrength($secret));

		return $secret;
	}

	/**
	 * Tests a password strength to see if it meets at least
	 * one lower case, one upper case, one numeric, and one
	 * special character.
	 * @param string $secret
	 * @return bool
	 */
	public static function testStrength(string $secret): bool
	{
		$match = '0000';

		for($ix=0;$ix<strlen($secret);$ix++) {
			$ch = substr($secret, $ix, 1);

			if (strpos(Crypto::LOWER, $ch) !== false) {
				$match = substr_replace($match, '1', 0, 1);
			}
			elseif (strpos(Crypto::UPPER, $ch) !== false) {
				$match = substr_replace($match, '1', 1, 1);
			}
			elseif (strpos(Crypto::NUMBER, $ch) !== false) {
				$match = substr_replace($match, '1', 2, 1);
			}
			elseif (strpos(Crypto::SPECIAL, $ch) !== false) {
				$match = substr_replace($match, '1', 3, 1);
			}

			if ($match === '1111') {
				break;
			}
		}

		return ($match === '1111');
	}

    private static function trimCharList()
    {
        $list = '';
        $keep = [9, 10, 13, 32];

        for($ix=0;$ix<=32;$ix++) {
            $list .= (!in_array($ix, $keep) ? chr($ix) : '');
		}

        return $list;
	}

	private static function trimDecrypted(string $value)
	{
		return rtrim($value, self::trimCharList());
	}

    private static function verify_encrypted_file(string $origFile, string $encryptedFile, ?string $key=null): bool
    {
        try {
            $verified = false;
            $decryptedFilename = $encryptedFile.'.decrypt.tmp';

            if (self::decrypt_file($encryptedFile, $decryptedFilename, $key)) {
                $verified = filesize($origFile) === filesize($decryptedFilename);
            }

            unlink($decryptedFilename);
            return $verified;
        }
        catch(\Exception $e) {
            return false;
        }
    }
}
