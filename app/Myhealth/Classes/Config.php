<?php

namespace Myhealth\Classes;

class Config
{
    public static function loadDotEnv(string $path, bool $overwrite=false): bool
	{
		if (!file_exists($path)) {
			return false;
		}
		
		try {
			$loader = (new \josegonzalez\Dotenv\Loader($path))->parse()->toEnv($overwrite);
		}
		catch(\Exception $e) {
			if ($e instanceof \LogicException &&
				!$overwrite &&
				strpos($e->getMessage(), 'has already been defined in $_ENV')) {
				Logger::debug($e->getMessage());
			}
		}

		return true;
	}
}