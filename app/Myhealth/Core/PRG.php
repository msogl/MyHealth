<?php

namespace Myhealth\Core;

class PRG
{
	public function go()
	{
		$requestURI = &$_SERVER['REQUEST_URI'];
		if (strpos($requestURI, '?') !== false) {
			$requestURI = substr($requestURI, 0, strpos($requestURI, '?'));
		}

		if (endsWith($requestURI, '/')) {
			$requestURI .= getPage();
		}

		if (!empty($_POST) && empty($_FILES)) {
			if (!empty($_GET)) {
				// combine POST and GET in the off chance both are sent (mfaduo does this)
				foreach($_GET as $key=>$val) {
					if (!isset($_POST[$key])) {
						$_POST[$key] = $val;
					}
				}
			}
			$postData = EncryptAESMSOGL(serialize($_POST));
			if (strlen($postData) > 1000) {
				$tmpFilename = $this->writeTempFile($postData);
				$tmpPost = array('__TMPPRG__'=>$tmpFilename);
				$postData = EncryptAESMSOGL(serialize($tmpPost));
			}

			header("Location: {$requestURI}?_state=($postData)", true, 303);
			exit;
		}
		else {
			if (isset($_GET['_state'])) {
                // _state should be surrounded by parentheses
                if (str_starts_with($_GET['_state'], '(') && str_ends_with($_GET['_state'], ')')) {
                    $_GET['_state'] = substr($_GET['_state'], 1, strlen($_GET['_state']) - 2);
                }
				$postData = DecryptAESMSOGL($_GET['_state']);
				$_POST = unserialize($postData);
				
				if (isset($_POST['__TMPPRG__'])) {
					$tmpData = $this->readTempFile($_POST['__TMPPRG__'], false);
					if ($tmpData !== false) {
						$postData = DecryptAESMSOGL($tmpData);
						$_POST = unserialize($postData);
					}
				}
			}
		}
	}

	private function writeTempFile($data)
	{
		$filename = generateGUID().'.tmp';
		file_put_contents(TEMPDIR.'/'.$filename, $data);
		return $filename;
	}

	private function readTempFile($filename, $deleteAfterReading=false)
	{
		if (strpos($filename, '..') !== false) {
			return '';
		}

		$filename = basename($filename);

		if (!file_exists(TEMPDIR.'/'.$filename)) {
			return false;
		}

		$contents = file_get_contents(TEMPDIR.'/'.$filename);
		
		if ($deleteAfterReading) {
			$this->deleteTempFile($filename);
		}

		return $contents;
	}

	private function deleteTempFile($filename)
	{
		if (strpos($filename, '..') !== false) {
			return '';
		}

		@unlink(TEMPDIR.'/'.basename($filename));
	}
}
