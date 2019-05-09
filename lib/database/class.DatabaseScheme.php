<?php
/**
 * class DatabaseScheme
 * framework
 * install framework tables to db
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
require_once SYSBASE.'/model/class.FrameworkScheme.php';

/**
 * class DatabaseScheme
 * framework
 * install framework tables to db
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
class DatabaseScheme extends FrameworkScheme {
	/**
	 * database sheme
	 * @var array
	 */
	protected static $scheme =  NULL;

	/**
	 * @return array $scheme
	 */
	public static function getScheme(){
		if (self::$scheme === null){
			self::$scheme = self::$pScheme + self::$fScheme;
		}
		return self::$scheme;
	}
}

?>
