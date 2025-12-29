<?php
class MyHealthAutoLoader
{
	public static function Register() {
        return spl_autoload_register(array('MyHealthAutoLoader', 'Load'));
    }

    public static function Load($class)
	{
        if (class_exists($class)) {
            return FALSE;
        }

		if (!defined('APPPATH')) {
			// This is mainly for PHPStan
			require_once 'config.php';
			require_once 'Functions.php';
		}
		
		$paths = explode(PATH_SEPARATOR, get_include_path());
		$paths[] = APPPATH.'/core';
		//$paths[] = APPPATH.'/app/Myhealth/Classes';
		//$paths[] = APPPATH.'/app/Myhealth/Daos';
		//$paths[] = APPPATH.'/app/Myhealth/Models';
		//$paths[] = APPPATH.'/app/Myhealth/Views';

		$classFound = FALSE;
		
		foreach ($paths as &$path) {
			$classFilePath = "{$path}/{$class}.php";
			$classFilePath = str_replace("/", DIRECTORY_SEPARATOR, $classFilePath);
			$classFilePath = str_replace("\\", DIRECTORY_SEPARATOR, $classFilePath);
            
            if ((file_exists($classFilePath) !== FALSE) && (is_readable($classFilePath) !== FALSE)) {
				$classFound = TRUE;
                break;
            }
        }
		
        
        if (!$classFound) {
			// Try the PSR-0 autoload function
			$classFile = self::autoload($class);

			foreach ($paths as &$path) {
				$classFilePath = "{$path}/{$classFile}";
				$classFilePath = str_replace("/", DIRECTORY_SEPARATOR, $classFilePath);
				$classFilePath = str_replace("\\", DIRECTORY_SEPARATOR, $classFilePath);
				
				if ((file_exists($classFilePath) !== FALSE) && (is_readable($classFilePath) !== FALSE)) {
					$classFound = TRUE;
					break;
				}
			}
        }

        if (!$classFound) {
			// Try PSR-4 autoload
			if (strpos($class, "\\") !== false) {
                $classMap = [
                    "Myhealth" => "{ROOT}/app/Myhealth",
                ];

                $root = realpath(__DIR__.'/..');

				$class = ltrim($class, '\\');
				$paths = explode("\\", $class);

                $testClass = null;

                foreach($classMap as $key => $val) {
                    if (str_starts_with($class, $key)) {
                        $testClass = str_replace($key, str_replace('{ROOT}', $root, $val), $class);
                        break;
                    }
                }

                // We're only looking for classes that start with stuff in the $classMap above
                if ($testClass == null) {
                    return false;
                }

                $classFilePath = str_replace("\\", DIRECTORY_SEPARATOR, $testClass).'.php';
                if ((file_exists($classFilePath) !== false) && (is_readable($classFilePath) !== false)) {
					$classFound = true;
				}
			}
		}
		
        if ($classFound) {
        	//echoln("Found {$classFilePath}");
			require_once($classFilePath);
			return TRUE;
        }
		
        return FALSE;
    }

	private static function autoload($className)
	{
		$className = ltrim($className, '\\');
		$fileName  = '';
		$namespace = '';
		
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		return $fileName;
	}
}

MyHealthAutoLoader::Register();
