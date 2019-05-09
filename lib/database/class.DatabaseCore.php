<?php
/**
 * class DatabaseCore
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
 * DatabaseCore
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
class DatabaseCore
{
	// STATIC VARIABLES ===============================================
	/**
	 * database host name
	 * static - set by framework
	 * @var string
	 */
	private static $DB_HOST;
	/**
	 * database host name
	 * @var string
	 */
	protected $HOST;

	/**
	 * database name
	 * static - set by framework
	 * @var string
	 */
	private static $DB_NAME;
	/**
	 * database name
	 * @var string
	 */
	protected $NAME;

	/**
	 * database username
	 * static - set by framework
	 * @var string
	 */
	private static $DB_USERNAME;
	/**
	 * database username
	 * @var string
	 */
	protected $USERNAME;

	/**
	 * database password
	 * static - set by framework
	 * @var string
	 */
	private static $DB_PASSWORD;
	/**
	 * database password
	 * @var string
	 */
	protected $PASSWORD;

	/**
	 * database table prefix
	 * static - set by framework
	 * @var string
	 */
	private static $DB_TABLE_PREFIX;
	/**
	 * database table prefix
	 * @var string
	 */
	protected $TABLE_PREFIX;

	/**
	 * database charset
	 * static - set by framework
	 * @var string
	 */
	private static $DB_CHARSET;
	/**
	 * database charset
	 * @var string
	 */
	protected $CHARSET;

	/**
	 * database provider: mysqli|pdo
	 * static - set by framework
	 * @var string
	 */
	private static $DB_PROVIDER;
	/**
	 * database provider: mysqli|pdo
	 * @var string
	 */
	protected $PROVIDER;

	/**
	 * database dsn
	 * required if database provider is set to pdo
	 * static - set by framework
	 * @var string
	 */
	private static $DB_DSN;
	/**
	 * database dsn
	 * required if database provider is set to pdo
	 * @var string
	 */
	protected $DSN;

	/**
	 * @return string $DB_PROVIDER
	 */
	public static function getProvider()
	{
		return DatabaseCore::$DB_PROVIDER;
	}

	// NON STATIC VARIABLES ===========================================

	/**
	 * db error state: last request was error or not
	 * @var bool
	 */
	protected $_isError = false;

	/**
	 * last error message
	 * @var $string
	 */
	protected $msgError = '';

	/**
	 * db state: db was closed or not
	 * @var bool
	 */
	protected $_isClose = false;

	/**
	 * Contains affected rows after update, delete and insert requests
	 * set by memberfunction: protectedInsert
	 * @var integer
	 */
	protected $affectedRows = 0;

	// constructor ====================================================

	/**
	 * class constructor
	 * protected -> singleton
	 * @param array $options
	 */
	protected function __construct($options = NULL){
		$this->HOST			= (!is_array($options) || !isset($options['HOST']))		? 	self::$DB_HOST 			: $options['HOST'];
		$this->NAME			= (!is_array($options) || !isset($options['NAME']))		? 	self::$DB_NAME 			: $options['NAME'];
		$this->USERNAME		= (!is_array($options) || !isset($options['USERNAME']))	? 	self::$DB_USERNAME 		: $options['USERNAME'];
		$this->PASSWORD		= (!is_array($options) || !isset($options['PASSWORD']))	? 	self::$DB_PASSWORD 		: $options['PASSWORD'];
		$this->TABLE_PREFIX	= (!is_array($options) || !isset($options['TABLE_PREFIX']))? self::$DB_TABLE_PREFIX : $options['TABLE_PREFIX'];
		$this->CHARSET		= (!is_array($options) || !isset($options['CHARSET']))	? 	self::$DB_CHARSET 		: $options['CHARSET'];
		$this->PROVIDER		= (!is_array($options) || !isset($options['PROVIDER']))	? 	self::$DB_PROVIDER 		: $options['PROVIDER'];
		$this->DSN			= (!is_array($options) || !isset($options['DSN']))		? 	self::$DB_DSN 			: $options['DSN'];
	}

	/**
	 * private -> singleton
	 */
	final private function __clone(){
	}

	/**
	 * get database element
	 * @param array $options
	 * @return DatabaseCoreModel
	 */
	final public static function getInstance($options = NULL){
		static $instances = array();
		$calledClass = get_called_class();
		if (!isset($instances[$calledClass])){
			$instances[$calledClass] = new $calledClass($options);
		}
		return $instances[$calledClass];
	}

	// STATIC Setter ==================================================

	/**
	 * set db constants
	 * set by framework
	 * @param array $db
	 * @return bool
	 */
	public static function setConfig($db){
		if (!is_array($db)
			|| !isset($db['HOST'])
			|| !isset($db['NAME'])
			|| !isset($db['USERNAME'])
			|| !isset($db['PASSWORD'])
			|| !isset($db['TABLE_PREFIX'])
			|| !isset($db['CHARSET'])
			|| !isset($db['PROVIDER'])
			|| !isset($db['DSN'])

			|| isset(self::$DB_HOST)
			|| isset(self::$DB_NAME)
			|| isset(self::$DB_USERNAME)
			|| isset(self::$DB_PASSWORD)
			|| isset(self::$DB_TABLE_PREFIX)
			|| isset(self::$DB_CHARSET)
			|| isset(self::$DB_PROVIDER)
			|| isset(self::$DB_DSN)
			){
			return false;
		}
		self::$DB_HOST 			= $db['HOST'];
		self::$DB_NAME 			= $db['NAME'];
		self::$DB_USERNAME		= $db['USERNAME'];
		self::$DB_PASSWORD 		= $db['PASSWORD'];
		self::$DB_TABLE_PREFIX 	= $db['TABLE_PREFIX'];
		self::$DB_CHARSET 		= $db['CHARSET'];
		self::$DB_PROVIDER 		= $db['PROVIDER'];
		self::$DB_DSN = str_replace([
			'[DB_HOST]',			'[DB_NAME]', 				'[DB_USERNAME]',
			'[DB_PASSWORD]',		'[DB_TABLE_PREFIX]',		'[DB_CHARSET]',	], [
			self::$DB_HOST,		self::$DB_NAME,			self::$DB_USERNAME,
			self::$DB_PASSWORD,	self::$DB_TABLE_PREFIX,	self::$DB_CHARSET,
		], $db['DSN']);
		return true;
	}

	// NON STATIC Getter/Setter =======================================

	/**
	 * @param string $TABLE_PREFIX
	 */
	public function setTABLE_PREFIX($TABLE_PREFIX)
	{
		$this->TABLE_PREFIX = $TABLE_PREFIX;
	}

	/**
	 * @return string $TABLE_PREFIX
	 */
	public function getTABLE_PREFIX()
	{
		return $this->TABLE_PREFIX;
	}

	/**
	 * db: return las inserted id
	 * @return int last inserted id
	 */
	function lastInsertId(){
		return $this->db->insert_id;
	}

	/**
	 * db: return affected rows
	 * @return int affected rows
	 */
	function affectedRows(){
		return $this->affectedRows;
	}

	/**
	 * @return int $this->_isError
	 */
	public function isError(){
		return $this->_isError;
	}

	/**
	 * @return bool $this->_isClose
	 */
	public function isClose(){
		return $this->_isClose;
	}

	/**
	 * @retun string last error message
	 */
	public function getError(){
		return $this->msgError;
	}

	// ======================== HELPER FUNCTIONS ======================

	/**
	 * generate reference array of array
	 * @param array $arr
	 * @return array
	 */
	function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
		{
			$refs = array();
			foreach($arr as $key => $value)
				$refs[$key] = &$arr[$key];
			return $refs;
		}
		return $arr;
	}
}

