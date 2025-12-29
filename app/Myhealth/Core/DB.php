<?php
//===================================================================
// Copyright by MSO Great Lakes, 2004-Present. All rights reserved.
//===================================================================

namespace Myhealth\Core;

use \PDO as PDO;
use Myhealth\Classes\Permission;

class DB
{
	public $CentralDB = 'CI_CENTRAL';
	public $QCMembersDB;
	public $QCPortalDB;
	public $QuickCapDB;
	public $ClinicalDB;
	public $QCServer;
	public $BlankValue;
	public $currentDB;
	private $config;
	private $connstr;
	private $conn;
	private $logSql = false;
    private $affectedRows;

	private $connAttributes = array(		// 08/23/2020 JLC
		//PDO::ATTR_TIMEOUT=>120,							// PHP 8.0+ use LoginTimeout in connection string for sqlsrv
		PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
		PDO::SQLSRV_ATTR_QUERY_TIMEOUT=>900,
		//PDO::SQLSRV_ATTR_FETCHES_DATETIME_TYPE=>true,		// PHP 7.3
		//PDO::SQLSRV_ATTR_FORMAT_DECIMALS=>true,			// exact precision and leading zeros for money types (PHP 7.3)
	);

	private $stmtAttributes = array(		// 08/23/2020 JLC
		PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL,
		PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE=>PDO::SQLSRV_CURSOR_BUFFERED,
		//PDO::SQLSRV_ATTR_DATA_CLASSIFICATION=>true,		// could be useful for security (PHP 7.3)
	);

	public function __construct()
	{
		$this->conn = null;
		$this->BlankValue = "";
		$this->config = $this->getDBConfig();
		$this->QCPortalDB = $this->getDefaultDB();
		$this->QCServer = $this->config['host'];

		$this->QCMembersDB = $GLOBALS['DBNAMES']['QCMEMBERS'];
		$this->QCPortalDB = $GLOBALS['DBNAMES']['QCPORTAL'];
		$this->QuickCapDB = $GLOBALS['DBNAMES']['QUICKCAP'];
		$this->ClinicalDB = $GLOBALS['DBNAMES']['CLINICAL'];
	}

	public function __destruct()
	{
		$this->Close();
	}

	public function Connect($dbname)
	{
		$cString = "";

		if (is_null($dbname)) {
			$dbname = (!_isNE($this->currentDB) ? $this->currentDB : $this->getDefaultDB());
		}

		if ($this->currentDB != $dbname) {
			if (!is_null($this->conn)) {
				$this->Close();
			}
		}

		if ($this->getConnectionState() == 0) {
			$this->config = $this->getDBConfig();
			$cString = $this->connstr;

			if ($cString == "") {
				$cString = 'sqlsrv:server=127.0.0.1;database=QCPortal';
			}
			else {
				$cString = str_replace("%%DATABASE%%", $dbname, $cString);
			}

			try {
				$this->conn = new PDO($cString, $this->config['dbuser'], DecryptAESMSOGL($this->config['dbpass']), $this->connAttributes);
				$this->currentDB = $dbname;
			}
			catch(\Exception $e) {
				$this->handleConnectionError($cString, $e);;
			}
		}
	}

	public function setCurrentDB($dbname)
	{
		if (!empty($this->currentDB) && $this->currentDB != $dbname) {
			$this->Close();
		}

		$this->currentDB = $dbname;
	}

	private function getConnectionState()
	{
		return (is_null($this->conn) ? 0 : 1);
	}

	public function Close()
	{
		$this->conn = null;
	}

    public function affectedRows()
    {
        return $this->affectedRows ?? 0;
    }

	public function execute($sql, $params=null)
	{
        $this->affectedRows = 0;

		try {
			if (is_null($this->conn)) {
				$this->Connect(null);
			}

            if (is_null($this->conn)) {
                return false;
            }


			$hasParams = !empty($params);

			$preparedType = RecordSet::STATEMENT_PREPARED;
			$stmt = $this->conn->prepare($sql, $this->stmtAttributes);
            
            if ($hasParams) {
                $params = array_values($params);
            }

			$res = ($hasParams ? $stmt->execute($params) : $stmt->execute());

			if ($res === false) {
				return false;
			}

			if (preg_match('/^(insert|update|delete) /i', $sql)) {
				// no results on insert, update or delete
                $this->affectedRows = $stmt->rowCount();
				return false;
			}

			return new RecordSet($stmt, $preparedType, $sql);		//($hasParams ? RecordSet::STATEMENT_PREPARED : RecordSet::STATEMENT_UNPREPARED));
		}
		catch(\Exception $e) {
			$this->handleError($this->currentDB, $sql, $params, $e);
			throw $e;
		}
	}

	public function GetRecords($dbname, $sql, $params=null)
	{
		try {
			$this->log("Connecting to ".$dbname);
			$this->Connect($dbname);
			$rs = $this->execute($sql, $params);
			$this->log($sql);
			return $rs;
		}
		catch(\Exception $e) {
			$this->handleError($dbname, $sql, null, $e);
			return false;
		}
	}

	public function GetAllRecords($dbname, $TableName, $OrderBy)
	{

		$this->log("Connecting to ".$dbname);
		$this->Connect($dbname);

		$sql = "SELECT * FROM [".$TableName."]";

		if ($OrderBy != "") {
			$sql .= " ORDER BY ";

			if (str_contains($OrderBy, ',')) {
				$fields = explode(',', $OrderBy);
				for($ix=0;$ix<count($fields);$ix++) {
					$fields[$ix] = "[".trim($fields[$ix])."]";
				}

				$sql .= implode(',', $fields);
			}
			else {
				$sql .= $OrderBy;
			}
		}

		$this->log($sql);
		return (!is_null($this->conn) ? $this->conn->execute($sql) : null);
	}

	/**
	 * ExecuteSQL
	 * 
	 * Executes SQL statement. Does not return Recordset. Throws \Exception
	 * 
	 * @param string $dbname
	 * @param string $sql
	 * @param array $params
	 */
	public function executeSQL($dbname, $sql, $params=null)
	{
		try {
			$this->log("Connecting to ".$dbname);
			$this->Connect($dbname);
			$this->log($sql);
			$this->execute($sql, $params);
		}
		catch(\Exception $e) {
			$this->handleError($dbname, $sql, null, $e);
			throw $e;
		}
	}

	public function executeNonQuery($sql, $params=null)
	{
		try {
			$this->log($sql);
			$rs = $this->execute($sql, $params);

			if ($rs !== false) {
				$rs->Close();
				$rs = null;
			}
		}
		catch(\Exception $e) {
			$this->handleError($this->currentDB, $sql, $params, $e);
			throw $e;
		}
	}

	public function MakeProper($sql)
	{
		return $this->MakeSQL($sql);
	}

	public function rst($Obj, $FieldName)
	{
		$rst = trim($this->rstRaw($Obj, $FieldName));

		// 11/02/2017 JLC - Detect uniqueidentifier fields and remove the curly braces the ODBC driver adds in
		if ((strlen($rst) == 38)) {
			if (str_starts_with($rst, '{') && str_ends_with($rst, '}')) {
                $rst = substr($rst, 1, strlen($rst)-2);
			}
		}

		return $rst;
	}

	public function rstRaw($Obj, $FieldName)
	{
		// Does not trim the returned data
		try {
			if (!isset($Obj->fields[$FieldName])) {
				return '';
			}
			elseif (!$this->isNullOrBlank($Obj->fields[$FieldName])) {
				return $Obj->fields[$FieldName];
			}
			else {
				return '';
			}
		}
		catch(\Exception $e) {
			return "";
		}
	}

	public function MakeSQL($sql, $sqlIsNumber=false)
	{
		if ($sqlIsNumber) {
			return $this->MakeSQLNumber($sql);
		}

		if (!$this->isNullOrBlank($sql)) {
			$NewSQL = str_replace("'", "''", $sql);
			$NewSQL = $this->SQLInjectionCheck($sql, $NewSQL);
			return "'".$NewSQL."'";
		}
		else {
			if ($this->BlankValue == "") {
				return "NULL";
			}
			else {
				return $this->BlankValue;
			}
		}
	}

	public function MakeSQLNumber($sql)
	{
		if ($sql == "") {
			$newSql = "0";
		}
		else {
			$newSql = $sql;
		}

		if (!is_numeric($newSql)) {
			$newSql = "0";
		}

		return $newSql;
	}

	public function MakeSQLDate($sql)
	{
		return $this->MakeSQL(sqlDate($sql));			// found in library.asp
	}

	public function MakeSQLDateTime($sql)
	{
		return $this->MakeSQL(sqlDateTime($sql));		// found in library.asp
	}

	public function MakeSQLFromList($list, $delimiter=',')
	{
		return $this->MakeSQLFromArray(!is_array($list) ? explode($delimiter, $list) : $list);
	}

	public function MakeSQLFromArray($items)
	{
		$sql = '';
		foreach($items as &$item) {
			$sql = ($sql != '' ? ',' : '') . $this->MakeSQL($item);
		}

		return $sql;
	}

	public function MakeSQLNoInjectionCheck($sql)
	{
		if (!$this->isNullOrBlank($sql)) {
			$NewSQL = str_replace("'", "''", $sql);
			return "'".$NewSQL."'";
		}
		else {
			if ($this->BlankValue = "") {
				return "NULL";
			}
		else {
				return $this->BlankValue;
			}
		}
	}

	public function SQLInjectionCheck($Text, $SQL)
	{
		$isInjection = false;

		$testSQL = strtoupper($SQL);

		if (!str_starts_with($SQL, "Error")) {
			//If str_contains(SQL, "0x") Then
			//	isInjection = true
			//	logIt("Injection: 0x");
			//End If

			//If str_contains(SQL, "';") Then
			//	isInjection = true
			//	logIt("Injection: //;");
			//End If

			if (str_contains($testSQL, "UNION SELECT")) {
				$isInjection = true;
				logIt("Injection: UNION SELECT");
			}
		elseif (str_contains($testSQL, "CAST(") || str_contains($testSQL, "CAST (")) {
				$isInjection = true;
				logIt("Injection: CAST( || CAST (");
			}
			elseif (str_contains($testSQL, "VARCHAR")) {
				$isInjection = true;
				logIt("Injection: VARCHAR");
			}
			elseif (str_contains($testSQL, "DECLARE @")) {
				$isInjection = true;
				logIt("Injection: DECLARE @");
			}
			elseif (str_contains($testSQL, "$@")) {
				$isInjection = true;
				logIt("Injection: $@");
			}
			elseif (str_contains($testSQL, "EXEC @")) {
				$isInjection = true;
				logIt("Injection: EXEC @");
			}
			elseif (str_contains($testSQL, ";SET") || str_contains($testSQL, "; SET")) {
				$isInjection = true;
				logIt("Injection: ();$ || (); SET");
			}
			elseif (str_contains($testSQL, ";EXEC") || str_contains($testSQL, "; EXEC")) {
				$isInjection = true;
				logIt("Injection: ;EXEC || ; EXEC");
			}
			elseif (str_contains($testSQL, ";DECLARE") || str_contains($testSQL, "; DECLARE")) {
				logIt("Injection: ;DECLARE || ; DECLARE");
				$isInjection = true;
			}
			elseif (str_contains($testSQL, ";INSERT") || str_contains($testSQL, "; INSERT")) {
				$isInjection = true;
				logIt("Injection: ;INSERT || ; INSERT");
			}
			elseif (str_contains($testSQL, ";UPDATE") || str_contains($testSQL, "; UPDATE")) {
				$isInjection = true;
				logIt("Injection: ;UPDATE || ; UPDATE");
			}
			elseif (str_contains($testSQL, ";DELETE") || str_contains($testSQL, "; DELETE")) {
				$isInjection = true;
				logIt("Injection: ;DELETE || ; DELETE");
			}
			elseif (str_contains($testSQL, ";IF") || str_contains($testSQL, "; IF")) {
				$isInjection = true;
				logIt("Injection: ;if ( || ; IF");
			}
			elseif (str_contains($testSQL, "/*")) {
				$isInjection = true;
				logIt("Injection: /*");
			}
		}

		if (str_starts_with(trim($Text), ';')) {
			$isInjection = true;
			logIt("Injection: starts with ;");
		}

		// Safe? at this point
		if ($isInjection) {
			logBadScript("SQL Injection");
			logIt("Text = ".$Text);
			logIt("SQL = ".$SQL);
			showDetectionPage();
			return "";
		}
		else {
			return $SQL;
		}
	}

	public function isNullOrBlank($Text)
	{
		if (is_null($Text)) {
			return true;
		}
		elseif (trim($Text) == "") {
			return true;
		}
		elseif (strlen($Text) > 0) {
			if (ord(substr($Text, 0, 1)) == 0) {
				return true;
			}
		}
		else {
			return false;
		}
	}

	public function getConnection($dbname)
	{
		$this->Connect($dbname);
		return $this->conn;
	}

	public function toJson($Obj, $columns, $firstOnly)
	{
		$colArray = explode(', ', $columns);

		if ($firstOnly) {
			return "";
		}

		$toJson = "[";

		$i = 0;
		while(!$Obj->EOF) {
			if ($i > 0) {
				$toJson .= ",";
			}

			$toJson .= "{";

			for($cx=0;$cx<count($colArray);$cx++) {
				if ($cx > 0) {
					$toJson .= ",";
				}

				$toJson .= "\"".$colArray[$cx]."\":\"".$this->rst($Obj, $colArray[$cx])."\"";
			}

			$toJson .= "}";

			$i++;
			$Obj->MoveNext();
		}

		$Obj->MoveFirst();

		$toJson .= "]";
		return $toJson;
	}

	public function hasRecords(&$rs)
	{
		return (!is_null($rs->stmt) && $rs->EOF === false);
	}

	public static function isMSSQLUUID($value)
	{
        if (!is_string($value)) return false;
        if (strlen($value)!=16) return false;
        $version=ord(substr($value,7,1))>>4;
        // version 1 : Time-based version Uses timestamp, clock sequence, and MAC network card address
        // version 2 : Reserverd
        // version 3 : Name-based version Constructs values from a name for all sections
        // version 4 : Random version Use random numbers for all sections
        if ($version<1 || $version>4) return false;
        $typefield=ord(substr($value,8,1))>>4;
        $type=-1;
        if (($typefield & bindec(1000))==bindec(0000)) $type=0; // type 0 indicated by 0??? Reserved for NCS (Network Computing System) backward compatibility
        if (($typefield & bindec(1100))==bindec(1000)) $type=2; // type 2 indicated by 10?? Standard format
        if (($typefield & bindec(1110))==bindec(1100)) $type=6; // type 6 indicated by 110? Reserved for Microsoft Corporation backward compatibility
        if (($typefield & bindec(1110))==bindec(1110)) $type=7; // type 7 indicated by 111? Reserved for future definition
        // assuming Standard type for SQL GUIDs
        if ($type!=2) return false;
        return true;
	}

	public static function convertUUIDToString($uuid)
	{
		$unpacked = unpack('Va/v2b/n2c/Nd', $uuid);
		return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
	}

	public function wrappers($rs, $daoName, $firstRecord=false)
	{
		return (new RSToDAO($this))->toDAOs($rs, "Myhealth\\Daos\\{$daoName}", $firstRecord);
	}

	public function magicWrappers($rs, $firstRecordOnly=false)
	{
		$results = [];

		if ($this->hasRecords($rs)) {
			// Correct field names, if necessary
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
			
			while (!$rs->EOF) {
				$obj = new \stdClass();
				foreach($rs->fields as $key=>$field) {
					if ($this->isMSSQLUUID($rs->fields[$key])) {
						$obj->$key = $this->convertUUIDToString($rs->fields[$key]);
					}
					else {
						$obj->$key = $rs->fields[$key];
					}
				}

				$results[] = $obj;
				
				if ($firstRecordOnly) {
					return (_isNE($results) ? null : $results[0]);
				}

				$rs->MoveNext();
			}
		}
		else {
			if ($firstRecordOnly) {
				return (_isNE($results) ? null : $results[0]);
			}	
		}

		return $results;
	}

    public function paramPlaceholders($num)
	{
		$placeholders = "";

		for ($ix = 0; $ix < $num; $ix++) {
			if ($ix > 0)
				$placeholders .= ",";
			$placeholders .= "?";
		}

		return $placeholders;
	}

    public function insertDao(string $table, object $dao, ?string $idColumn=null): int
	{
		$columns = [];  
		$params = [];
		
		$attributes = getObjectVars($dao, false);
		foreach ($attributes as $attribute) {
			if ($attribute != $idColumn && !is_null($dao->$attribute)) {
				$columns[] = $attribute;
				$params[] = $dao->$attribute;
			}
		}
		
		$sql = $this->insertSql($table, $columns, $params);

		if (!empty($idColumn)) {
			$dao->$idColumn = $this->insert($sql, $params, true, true);
			return $dao->$idColumn;
		}
		else {
			$this->insert($sql, $params);
			return true;
		}
	}
	
	/**
	 * @param string $table
	 * @param mixed $dao
	 * @param string $id
	 * @param bool $nullableColumns
	 * @return bool
	 */
	public function updateDao(string $table, object $dao, string $idColumn, bool $nullableColumns=false): bool
	{
		$attributes = getObjectVars($dao, false);

		$columns = [];
		$params = [];
		
		foreach ($attributes as &$column) {
			if (strcasecmp($column, $idColumn) == 0) {
				continue;
			}

			if ($nullableColumns) {
				$columns[$column] = $dao->$column;
			}
			else if (!is_null($dao->$column)) {
				$columns[] = $column;
				$params[] = $dao->$column;
			}
		}

		if ($nullableColumns) {
			$results = $this->updateSqlWithNulls($table, $columns, $idColumn." = ?");
			$sql = $results['sql'];
			$params = $results["params"];
		}
		else {
			$sql = $this->updateSql($table, $columns, $idColumn." = ?");
		}
		
		$params[$idColumn] = $dao->$idColumn;
		$this->executeNonQuery($sql, $params);
		return true;
	}

    public function insert($sql, $vars=null, $displayException=true, $hasIDColumn=true)
	{
		$this->execute($sql, $vars, $displayException);
        if ($this->affectedRows > 0) {
			if ($hasIDColumn) {
                $rs = $this->execute("SELECT @@IDENTITY AS [ident]");
                return $rs->fields['ident'];
			}
			else {
				return ($this->affectedRows() > 0);
			}
		}

		return 0;
	}

	public function insertId()
	{
		if (!isset($this->pdo)) {
			return 0;
		}

        $rs = $this->execute("SELECT @@IDENTITY AS [ident]");
        return $rs->fields['ident'];
	}

	public static function insertSql($table, $columns, $values, $dbname=null)
	{
		$realTable = (!str_starts_with($table, "[") ? "[{$table}]" : $table);
		
		if (!is_null($dbname)) {
			$realTable = $dbname."..".$realTable;
		}
		
		$sql = "INSERT INTO {$realTable} (";

		for ($ix = 0; $ix < count($columns); $ix++) {
			if ($ix > 0) { $sql .= ", ";
			}
			$sql .= '['.$columns[$ix].']';
		}

		$sql .= ") values (";

		for ($ix = 0; $ix < count($values); $ix++) {
			if ($ix > 0) {
				$sql .= ", ";
			}
			$sql .= "?";
		}

		$sql .= ")";

		return $sql;
	}


    public static function updateSql(string $table, array $columns, string $whereClause, ?string $dbname=null)
	{
		$realTable = (!str_starts_with($table, "[") ? "[{$table}]" : $table);

		if (!is_null($dbname)) {
			$realTable = $dbname."..".$realTable;
		}
		
		$sql = "UPDATE {$realTable} SET ";

		for ($ix = 0; $ix < count($columns); $ix++) {
			if ($ix > 0) {
				$sql .= ", ";
			}

			$sql .= '['.$columns[$ix] . '] = ?';
		}

		if ($whereClause !== '') {
			$sql .= " WHERE {$whereClause}";
        }

		return $sql;
	}

	/*
	 * updateSqlWithNulls - creates an update statement
	 * $table - name of the table
	 * $columns - associative array *MUST CONTAIN VALUES*
	 * $whereClause - conditional where clause
	 *
	 * returns two element associative array
	 *   'sql': the resulting sql
	 *   'params': the params array to pass to execute
	 */
	public static function updateSqlWithNulls(string $table, array $columns, string $whereClause, ?string $dbname=null)
	{
		$params = [];
		$realTable = (!str_starts_with($table, "[") ? "[{$table}]" : $table);

		if (!is_null($dbname)) {
			$realTable = $dbname."..".$realTable;
		}
		
		$sql = "UPDATE {$realTable} SET ";

		$ix = 0;
		foreach ($columns as $name => $value) {
			if ($ix > 0) { $sql .= ", ";
			}

			if (!is_null($value)) {
				$sql .= '['.$name.'] = ?';
				$params[$name] = $value;
			} else
				$sql .= '['.$name.'] = NULL';

			$ix++;
		}

		if ($whereClause !== '') {
			$sql .= " WHERE {$whereClause}";
        }

		$results = ['sql' => $sql, 'params' => $params, ];

		return $results;
	}

	private function handleError($dbname, $sql, $params, $e)
	{
		if ($e->getMessage() != "") {
			// Get the error number.description as the Err object gets reset in libFormatDt
			$errNo = $e->getCode();
			$errDesc = $e->getMessage();

			if (php_sapi_name() !== 'cli') {
				echo '<p class="errormsg">Error encountered</p>';
			}
			else {
				echo "Error encountered\n";
			}

			$logLines = "\Exception\n";
			$logLines .= "  Error {$errNo}: {$errDesc}\n";
			$logLines .= "  DBName: {$dbname}\n";
			$logLines .= "  SQL: {$sql}\n";

			if (!_isNE($params)) {
				$logLines .= "  Params: ";
				$logLines .= print_r($params, true);
			}

			$logLines .= "  Page: ".getPage()."\n";
			$logLines .= "  Query String (GET): ".http_build_query($_GET)."\n";
			$logLines .= "  Form Variables (POST): "."\n";
			foreach($_POST as $key=>&$val) {
				$logLines .= "    Var: ". $key." = ".$val."\n";
			}

			Logger::info($logLines);

			if (Permission::isSuperAdmin()) {
				printf('%s', "<div style=\"border:1px solid red;text-align:left;\">".str_replace(["\r\n", "\n"], '<br/>', _W($logLines))."</div>");
			}

			exit;
		}
	}

	private function handleConnectionError(string $connstr, \Exception $e)
	{
		if ($e->getMessage() != "") {
			// Get the error number.description as the Err object gets reset in libFormatDt
			$errNo = $e->getCode();
			$errDesc = $e->getMessage();

			$errorMsg = 'Database connection failed';

			if (php_sapi_name() !== 'cli') {
				printf('<p class="errormsg">%s</p>', $errorMsg);
			}
			else {
				printf("%s\n", $errorMsg);
			}

			$logLines = $errorMsg."\n";
			$logLines .= "  Error {$errNo}: {$errDesc}\n";
			$logLines .= "  Connection string: {$connstr}";

			Logger::error($logLines);

			if (Permission::isSuperAdmin()) {
				if (php_sapi_name() !== 'cli') {
					printf(
						'<div style="border:1px solid red;text-align:left;">%s</div>',
						str_replace(["\r\n", "\n", "\r"], '<br>', $logLines)
					);
				}
				else {
					printf("%s\n", $logLines);
				}
			}

			exit;
		}
	}
    

	private function getDBConfig()
	{
		$this->connstr = "sqlsrv:server={$_ENV['DB_HOST']};database=%%DATABASE%%;LoginTimeout=120;Encrypt=True;";

		if (!empty($_ENV['DB_CERTHOSTNAME'])) {
			$this->connstr .= 'TrustServerCertificate=false;';
			$this->connstr .= "HostNameInCertificate={$_ENV['DB_CERTHOSTNAME']};";
		}
		else {
			$this->connstr .= 'TrustServerCertificate='.($_ENV['DB_TRUSTCERT'] ?? 'true').';';
		}

		return [
			'database' => $_ENV['DB_NAME'],
			'host' => $_ENV['DB_HOST'],
			'dbuser' => $_ENV['DB_USER'],
			'dbpass' => $_ENV['DB_PASS'],
		];
	}

	private function getDefaultDB()
	{
		return $_ENV['DB_NAME'] ?? '';
	}

	private function log($text)
	{
		if ($this->logSql) {
			Logger::logToFile("[INFO]{$text}", LOGPATH.'/database_'.date('Ymd').'.log');
		}
	}
}
