<?php
namespace intbf\database;

/**
 * abstract Database
 * framework
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
abstract class Database extends DatabaseCore
{
	// ======================== HELPER FUNCTIONS ======================================================
	/**
	 * escape string by database
	 * @param string $in
	 * @return string escaped string
	 */
	public abstract function escapeString($in);

	// ======================== BASE FUNCTIONS ========================================================

	/**
	 * run SQL query in database and fetch result set
	 * uses mysqli_bind to prevent SQL injection
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 * @return array fetched resultset
	 */
	abstract function getResultSet($sql, $bind_type = NULL, $bind_params = NULL);

	/**
	 * run SQL query in database and fetch result set
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return mixed
	 */
	abstract function queryResult($sql);

	/**
	 * run query on database -> set affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 */
	abstract function query($sql);

	/**
	 * run SQL query in database -> set affected rows
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 */
	abstract function protectedInsert($sql, $bind_type = NULL, $bind_params = NULL);

	/**
	 * executes prepared mysqli statement
	 * sets internal error variables
	 * @param mixed $stmt
	 */
	abstract function executeStmt($stmt);

	/**
	 * run query on database -> return last inserted id, sets affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return int last inserted id
	 */
	abstract function queryInsert($sql);

	/**
	 * insert into table given fields
	 * @param string $table
	 * @param array $data key=> value
	 * @param string $type - required in mysqli
	 */
	abstract function dbInsert($table, $data, $type = NULL);

	/**
	 * update table set given $data on $where
	 * @param string $table
	 * @param array $where
	 * @param array $data key=> value
	 * @param string $type - required in mysqli
	 */
	abstract function dbUpdate($table, $where, $data, $type = NULL);

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
	 * 				'table' => 'tablename'
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
	 */
	abstract function dbFetch($table, $fields = NULL, $where = NULL, $type = NULL, $join = NULL, $order = NULL, $limit = NULL, $mod = NULL);

	/**
	 * delete from table
	 * @param string $table
	 * @param array $where
	 * @param string $type - required in mysqli
	 */
	abstract function dbDelete($table, $where, $type = NULL);

	/**
	 * close db connection
	 */
	abstract function close();

	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @return false|int error -> false, last inserted id or
	 */
	protected abstract function _storeFile2Filedata( $filename, $filesize = null, $tablename = 'filedata' , $datacolname = 'data');

	/**
	 * close last stmt of getFiledataBinary
	 */
	abstract function fileCloseLastGet();

	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @return false|binary error -> false, binary data
	 */
	protected abstract function _getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data');
}
