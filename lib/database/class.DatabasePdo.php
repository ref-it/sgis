<?php
/**
 * class DatabaseProvider - Pdo
 * framework
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @category        framework
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
namespace intbf\database;

/**
 * DatabaseProvider - Pdo
 * framework
 * provides db constants
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @namespace		intbf\database
 * @category        framework
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
class DatabaseProvider extends Database
{
	/**
	 * database member
	 * @var \PDO
	 */
	public $db;

	private $last_insert_id = 0;

	/**
	 * class constructor
	 * @param array options
	 */
	protected function __construct($options = [])
	{
		if (is_array($options)) $options['PROVIDER'] = 'mysqli';
		else $options = ['PROVIDER' => 'mysqli'];
		parent::__construct($options);
		$this->db = new \PDO($this->DSN, $this->USERNAME, $this->PASSWORD, [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,
			\PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode = 'STRICT_ALL_TABLES';",
			\PDO::MYSQL_ATTR_FOUND_ROWS => true]);

		$einfo = $this->db->errorInfo();
		if (!isset($this->db) || isset($einfo[1])){
			$this->_isError = true;
			$this->msgError = "Connect failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog($this->msgError);
			printf($this->msgError);
			die();
		}
	}

	// ======================== HELPER FUNCTIONS ======================================================

	/**
	 * escape string by database
	 * @param string $in
	 * @return string escaped string
	 */
	function escapeString($in){
		return $this->db->quote($in);
	}

	/**
	 * close db connection
	 * only delete reference to pdo object
	 * you need to remove all other references on your own
	 * connection stay alive for the lifetime of that PDO object
	 */
	function close(){
		if (!$this->_isClose){
			$this->_isClose = true;
			if ($this->db){
				$this->db = NULL;
			}
		}
	}

	/**
	 * db: return las inserted id
	 * @return int last inserted id
	 */
	function lastInsertId(){
		return $this->last_insert_id;
	}

	// ======================== BASE FUNCTIONS ========================================================

	/**
	 * run SQL query in database and fetch result set
	 * uses mysqli_bind to prevent SQL injection
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 * @return array fetched resultset
	 */
	function getResultSet($sql, $bind_type = NULL, $bind_params = NULL){ //use to bind params
		if ($bind_params !== NULL && !is_array($bind_params)){
			$bind_params = array($bind_params);
		}
		//prepare statement
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Prepare failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return [];
		}
		//bind params
		$bind_pos = 0;
		if ($bind_params) foreach ($bind_params as $k => $value){
			$letter = '';
			if (is_array($bind_type) && isset($bind_type[$k]) || is_string($bind_type) && isset($bind_type[$k])){
				$letter = $bind_type[$k];
			}
			$type = NULL;
			switch ($letter){
				case 'i':{
					$type = \PDO::PARAM_INT;
				} break;
				case 's':{
					$type = \PDO::PARAM_STR;
				}break;
				case '':{
					$type = is_null($value) ? \PDO::PARAM_NULL : is_bool($value) ? \PDO::PARAM_BOOL : is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
				}break;
				default:{
					$type = NULL;
				}break;
			}
			if (is_int($k)){
				$bind_pos++;
				$stmt->bindParam($bind_pos, $bind_params[$k], $type);
			} else {
				$stmt->bindParam(($k[0]==':')? $k : ":$k" , $bind_params[$k], $type);
			}
		}
		$result = $stmt->execute();
		if ($result === false){
			$this->_isError = true;
			$einfo = $stmt->errorInfo();
			$this->msgError = "Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return [];
		} else {
			$this->_isError = false;
		}
		$return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$this->affectedRows = count($return);
		$stmt = NULL;
		return $return;
	}

	/**
	 * run SQL query in database and fetch result set
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return array
	 */
	function queryResult($sql){ //use for no secure params
		$results = array();
		$stmt   = $this->db->query($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Query failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return [];
		}
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		if ($result) {
			$ii = 0;
			foreach ($result as $key => $value) {
				$ii++;
				$results [] = $value;
			}
			$this->_isError = false;
			$this->msgError = '';
			$this->affectedRows = $ii;
		} else {
			$this->_isError = true;
			$einfo = $stmt->errorInfo();
			$this->msgError = "{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog($this->msgError);
			$this->affectedRows = -1;
		}
		return $results;
	}

	/**
	 * run query on database -> set affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return boolean
	 */
	function query($sql){
		$stmt = $this->db->query($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Query failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return -1;
		} else {
			$this->affectedRows = $stmt->rowCount();
			$this->_isError = false;
			$this->last_insert_id = $this->db->lastInsertId();
			return $this->affectedRows;
		}
	}

	/**
	 * run SQL query in database -> set affected rows
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 * @return boolean
	 */
	function protectedInsert($sql, $bind_type = NULL, $bind_params = NULL){ //use to bind params
		if ($bind_params !== NULL && !is_array($bind_params)){
			$bind_params = array($bind_params);
		}
		//prepare statement
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Prepare failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		}
		//bind params
		$bind_pos = 0;
		if ($bind_params) foreach ($bind_params as $k => $value){
			$letter = '';
			if (is_array($bind_type) && isset($bind_type[$k]) || is_string($bind_type) && isset($bind_type[$k])){
				$letter = $bind_type[$k];
			}
			$type = NULL;
			switch ($letter){
				case 'i':{
					$type = \PDO::PARAM_INT;
				} break;
				case 's':{
					$type = \PDO::PARAM_STR;
				}break;
				case 'b':{
					$type = \PDO::PARAM_LOB;
				}break;
				case '':{
					$type = is_null($value) ? \PDO::PARAM_NULL : is_bool($value) ? \PDO::PARAM_BOOL : is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
				}break;
				default:{
					$type = NULL;
				}break;
			}
			if (is_int($k)){
				$bind_pos++;
				$stmt->bindParam($bind_pos, $bind_params[$k], $type);
			} else {
				$stmt->bindParam(($k[0]==':')? $k : ":$k" , $bind_params[$k], $type);
			}
		}
		$result = $stmt->execute();
		if ($result === false){
			$this->_isError = true;
			$einfo = $stmt->errorInfo();
			$this->msgError = "Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		} else {
			$this->affectedRows = $stmt->rowCount();
			$this->_isError = false;
			$this->last_insert_id = $this->db->lastInsertId();
			return true;
		}
	}

	/**
	 * executes prepared mysqli statement
	 * sets internal error variables
	 * @param \mysqli_stmt $stmt
	 * @return boolean false
	 */
	function executeStmt($stmt){
		$this->_isError = true;
		$this->msgError = 'Execute Failed: Invalid Method on PDO: executeStmt' ;
		\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' );
		$this->affectedRows = -1;
		return false;
	}

	/**
	 * run query on database -> return last inserted id, sets affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return int last inserted id
	 */
	function queryInsert($sql){
		return $this->query($sql);
	}

	/**
	 * insert into table given fields
	 * @param string $table
	 * @param array $data key=> value
	 * @param string $type - required in mysqli
	 * @return boolean
	 */
	function dbInsert($table, $data, $type = NULL){
		$sql_fields = [];
		$sql_values = [];
		$sql_data = [];
		foreach ($data as $k => $v){
			$sql_fields[] = '`'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k).'`';
			$sql_values[] = ':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k);
			$sql_data[':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k)] = $v;
		}
		$sql_fields = implode(', ', $sql_fields);
		$sql_values = implode(', ', $sql_values);
		return $this->protectedInsert(
			'INSERT INTO '.$this->TABLE_PREFIX.$table." ($sql_fields) VALUES ($sql_values)",
			$type, $sql_data);
	}

	/**
	 * delete from table
	 * @param string $table
	 * @param array $where
	 * @param string $type - required in mysqli
	 * @return boolean
	 */
	function dbDelete($table, $where, $type = NULL){
		$sql_data = [];
		// where -----------------------
		$w = '';
		if ($where && is_array($where) && count($where) > 0){
			$w = " WHERE ";
			$sql_and = [];
			foreach ($where as $k => $v){
				if (!is_array($v)) $v = [$k => $v];
				$sql_or = [];
				foreach ($v as $ko => $vo){
					$op = '=';
					$wv = $vo;
					if (is_array($wv)){
						$op = $vo[1];
						$wv = $vo[0];
					}
					$sql_or[] = preg_replace('/^(([a-zA-Z0-9_\-]*)(\.))?([a-zA-Z0-9_\-]*)$/', '$1`$4`' ,preg_replace('/[^a-zA-Z0-9\._\-]/', '', $ko)).
					$op.':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko);
					$sql_data[':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko)] = $wv;
				}
				$sql_and[] = '('.implode(' OR ', $sql_or).')';
			}
			$w .= implode(' AND ', $sql_and);
		}
		if (!$w){
			$this->_isError = true;
			$this->msgError = 'dbDelete called with empty where clause.';
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' );
			return false;
		}
		return $this->protectedInsert(
			'DELETE FROM '.$this->TABLE_PREFIX.$table." $w ;",
			NULL, $sql_data);
	}

	/**
	 * update table set given $data on $where
	 * @param string $table
	 * @param array $data key=> value
	 * @param array $where [['or1' => 1, 'or2' => 2], ['or3' => 3]] -> (or1 = 1 OR or2 = 2) AND (or3 = 3)
	 * @param string $type - required in mysqli
	 * @return boolean
	 */
	function dbUpdate($table, $where, $data, $type = NULL){
		$sql_fields = [];
		$sql_data = [];
		foreach ($data as $k => $v){
			$sql_fields[] = '`'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k).'`' .
			'='.':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k);
			$sql_data[':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $k)] = $v;
		}
		$sql_fields = implode(', ', $sql_fields);
		$w = '';
		$count = 0;
		if ($where && is_array($where) && count($where) > 0){
			$w = " WHERE ";
			$sql_and = [];
			foreach ($where as $k => $v){
				if (!is_array($v)) $v = [$k => $v];
				$sql_or = [];
				foreach ($v as $ko => $vo){
					$op = '=';
					$wv = $vo;
					if (is_array($wv)){
						$op = $vo[1];
						$wv = $vo[0];
					}
					$sql_or[] = preg_replace('/^(([a-zA-Z0-9_\-]*)(\.))?([a-zA-Z0-9_\-]*)$/', '$1`$4`' ,preg_replace('/[^a-zA-Z0-9\._\-]/', '', $ko)).
					$op.':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko).$count;
					$sql_data[':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko).$count] = $wv;
					$count++;
				}
				$sql_and[] = '('.implode(' OR ', $sql_or).')';
			}
			$w .= implode(' AND ', $sql_and);
		}
		return $this->protectedInsert(
			'UPDATE '.$this->TABLE_PREFIX.$table." SET $sql_fields $w",
			$type, $sql_data);
	}

	/**
	 * fetch from table
	 * supports only
	 *    where clause
	 * @param string|array $table rename tables with array key if non numeric
	 * @param array $fields
	 * @param array $where [['or1' => 1, 'or2' => 2], ['or3' => 3]] -> (or1 = 1 OR or2 = 2) AND (or3 = 3)
	 * @param string $type - required in mysqli (e.g. iisssisis)
	 * @param array $join
	 * 		$join = [
	 * 			[
	 * 				'type'	=> 'inner|left|right|natural|full outer|...',
	 * 				'table' => 'tablename',
	 * 				'alias' => 'tnlname',
	 * 				'on' 	=> [
	 * 					['tbl1.col', 'tbl2.col', 'operator: =|<=|...(optional - default: =)'],
	 * 					['tbl1.col', 'tbl2.col', 'operator: =|<=|...(optional - default: =)']
	 * 				]
	 * 			]
	 * 		]
	 * @param array $order e.g.:['col1', 'col2', 'col3' => 'DESC']
	 * @param int|NULL limit
	 * @param string $mod = 'DISTINCT'
	 * @param array $group e.g.:['col1', 'col2', 'col3' => 'DESC']
	 * @return array
	 */
	function dbFetch($table, $fields = NULL, $where = NULL, $type = NULL, $join = NULL, $order = NULL, $limit = NULL, $mod = NULL, $group = NULL){
		$sql_data = [];
		//table -----------------
		if (!$table || is_array($table) && count($table) == 0){
			$this->_isError = true;
			$this->msgError = 'Invalid $table Value'.
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"');
			$this->affectedRows = -1;
			return NULL;
		}
		$t = '';
		if (!is_array($table)){
			$t = $this->TABLE_PREFIX.$table;
		} else {
			foreach ($table as $k => $v){
				if (is_numeric($k)){
					$t .= (($t)?', ':''). $this->TABLE_PREFIX.$v;
				} else {
					$t .= (($t)?', ':''). $this->TABLE_PREFIX.$v . ' as '.$k;
				}
			}
		}
		// fields ---------------------
		$f = '*';
		if ($fields && is_array($fields) && count($fields) > 0){
			$f = "";
			foreach ($fields as $k => $v){
				if (is_numeric($k)){
					$f .= (($f)?', ':''). $v;
				} else {
					$f .= (($f)?', ':''). $v . ' as '.$k;
				}
			}
		}
		// join -----------------------
		$j = '';
		if ($join && is_array($join) && count($join) > 0){
			foreach ($join as $jset){
				$j .= (($j)?"\n":'');
				$onprefix = ((isset($jset['alias']))?'':$this->TABLE_PREFIX);
				//type
				$j .= strtoupper($jset['type']).
				//table
				' JOIN '.$this->TABLE_PREFIX.$jset['table'].
				//table alias
				((isset($jset['alias']))?' '.$jset['alias']:'');
				//on
				if (isset($jset['on']) && is_array($jset['on']) && count($jset['on']) > 0){
					$on_out = '';
					foreach ($jset['on'] as $on){
						$on_out .= (($on_out)?" AND ":'');
						$op = ($on[2])? $on[2] : '=';
						$on_out .= $onprefix.$on[0].$op.$onprefix.$on[1];
					}
					$j .= ' ON '.$on_out;
				}
			}
		}
		// where -----------------------
		$w = '';
		if ($where && is_array($where) && count($where) > 0){
			$w = " WHERE ";
			$sql_and = [];
			foreach ($where as $k => $v){
				if (!is_array($v)) $v = [$k => $v];
				$sql_or = [];
				foreach ($v as $ko => $vo){
					$op = '=';
					$wv = $vo;
					if (is_array($wv)){
						$op = $vo[1];
						$wv = $vo[0];
					}
					$sql_or[] = preg_replace('/^(([a-zA-Z0-9_\-]*)(\.))?([a-zA-Z0-9_\-]*)$/', '$1`$4`' ,preg_replace('/[^a-zA-Z0-9\._\-]/', '', $ko)).
					$op.':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko);
					$sql_data[':'.preg_replace('/[^a-zA-Z0-9_\-]/', '', $ko)] = $wv;
				}
				$sql_and[] = '('.implode(' OR ', $sql_or).')';
			}
			$w .= implode(' AND ', $sql_and);
		}
		// order ------------------------
		$o = '';
		if ($order && is_array($order) && count($order) > 0){
			$o = '';
			foreach ($order as $k => $v){
				if (is_numeric($k)){
					$o .= (($o)?', ':''). $v;
				} else {
					$o .= (($o)?', ':'').'`'. $k .'`'. ((strtoupper($v)=='ASC')?' ASC':''). ((strtoupper($v)=='DESC')?' DESC':'');
				}
			}
			$o = " ORDER BY " . $o;
		}
		// group ------------------------
		$g = '';
		if ($group && is_array($group) && count($group) > 0){
			$g = '';
			foreach ($group as $k => $v){
				if (is_numeric($k)){
					$g .= (($g)?', ':''). $v;
				} else {
					$g .= (($g)?', ':'').'`'. $k .'`'. ((strtoupper($v)=='ASC')?' ASC':''). ((strtoupper($v)=='DESC')?' DESC':'');
				}
			}
			$g = " GROUP BY " . $g;
		}
		// limit ------------------------
		$l = '';
		if ($limit && is_int($limit) && $limit > 0){
			$l = ' LIMIT '.$limit;
		}
		// modificator ------------------
		$m = '';
		if ($mod == 'DISTINCT'){
			$m = " $mod";
		}

		return $this->getResultSet(
			"SELECT{$m} {$f} FROM {$t} {$j} {$w} {$g} {$o} {$l}",
			$type, $sql_data);
	}

	// FILE HANDLER ===================================================

	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @param integer|NULL id
	 * @return false|int error -> false, last inserted id or
	 */
	protected function _storeFile2Filedata( $filename, $filesize = null, $tablename = 'filedata' , $datacolname = 'data', $id = NULL){
		$this->fileCloseLastGet();
		if ($id){
			$sql = "INSERT INTO `".$this->TABLE_PREFIX."$tablename` (id, $datacolname) VALUES(?, ?)";
		} else {
			$sql = "INSERT INTO `".$this->TABLE_PREFIX."$tablename` ($datacolname) VALUES(?)";
		}
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Prepare failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		}
		$fp = fopen($filename, 'rb');
		if ($id){
			$stmt->bindParam(1, $insert_id);
			$stmt->bindParam(2, $fp, \PDO::PARAM_LOB);
		} else {
			$stmt->bindParam(1, $fp, \PDO::PARAM_LOB);
		}
		try {
			$this->last_insert_id = 0;
			$this->db->beginTransaction();
			$stmt->execute();
			$this->last_insert_id = $this->db->lastInsertId();
			$this->db->commit();
			fclose($fp);
			$this->affectedRows = $stmt->rowCount();
			return $this->last_insert_id;
		} catch (\Exception $e){
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			$this->fileCloseLastGet();
			return false;
		}
	}

	/**
	 * last file get statement
	 * @var \PDOStatement
	 */
	private $lastFileStmt;

	/**
	 * close last stmt of getFiledataBinary
	 */
	public function fileCloseLastGet(){
		if ($this->lastFileStmt != NULL){
			$this->lastFileStmt->closeCursor();
			$this->lastFileStmt = NULL;
		}
	}

	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @return false|binary error -> false, binary data
	 */
	protected function _getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data'){
		$sql = "SELECT FD.$datacolname FROM `".$this->TABLE_PREFIX."$tablename` FD WHERE id=:dataid";
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //syntax errors, connection error, missing privileges, ...
			$this->_isError = true;
			$einfo = $this->db->errorInfo();
			$this->msgError = "Prepare failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n";
			\intbf\ErrorHandler::_errorTraceLog('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		}
		try {
			$stmt->execute(array(':dataid' => $id));
			$this->affectedRows = $stmt->rowCount();
			$stmt->bindColumn(1, $file, \PDO::PARAM_LOB);
			$stmt->fetch();
			$this->fileCloseLastGet();
			$this->lastFileStmt = $stmt;
			$this->_isError = false;
			return $file;

		} catch (\Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			$this->fileCloseLastGet();
			return false;
		}
	}
}

