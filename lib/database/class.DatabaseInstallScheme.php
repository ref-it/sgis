<?php
/**
 * class DatabaseInstallScheme
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
require_once FRAMEWORK_PATH.'/database/class.DatabaseScheme.php';

/**
 * class DatabaseInstallScheme
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
class DatabaseInstallScheme extends DatabaseScheme {
	/**
	 * database inserts
	 * @var array
	 */
	private static $inserts = NULL;

	/**
	 * @return array $inserts
	 */
	private static function getInserts(){
		if (self::$inserts === null){
			self::$inserts = self::$pInserts + self::$fInserts;
		}
		return self::$inserts;
	}

	/**
	 *
	 * @var DatabaseCoreModel
	 */
	private $db;

	/**
     * class constructor
	 */
	public function __construct()
	{
		$this->db = DatabaseCoreModel::getInstance();
		self::getInserts();
		self::getScheme();
	}

	/**
	 * install tables
	 */
	public function installTables(){
		echo '<div style="padding: 15px 5px; border: 2px solid #555; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">'.
				'INSTALL TABLES</div>';
		foreach (self::$scheme as $table => $data){
			$this->_installTable($table, $data);
		}
	}

	/**
	 * install tables to database
	 * @param string $table
	 * @param array $scheme
	 */
	private function _installTable($table, $scheme) {
		//set sql options
		$sql1  = "START TRANSACTION;\n";
		$sql1 .= 'SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;'."\n";
		$sql1 .= 'SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;'."\n";
		$sql1 .= "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';"."\n\n";


		$sql2 = 'CREATE TABLE IF NOT EXISTS `'.$this->db->getTABLE_PREFIX().$table.'` (';
		$p = 0;
		$c = count($scheme['fields']);
		foreach ($scheme['fields'] as $field => $opt){
			$p++;
			$sql2.= "`$field` $opt".(($p != $c)?",\n":'');
		}
		foreach ($scheme['keys'] as $text){
			$sql2.= ",\n$text";
		}
		$sql2 .= ")\n";
		foreach ($scheme['options'] as $k => $v){
			$sql2.= "$k = $v\n";
		}
		$sql2 .= ";";

		//restore sql options
		$sql3 = "\n".'SET SQL_MODE=@OLD_SQL_MODE;'."\n";
		$sql3 .= 	 'SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;'."\n";
		$sql3 .= 	 'SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;'."\n";
		$sql3 .= "COMMIT;\n";

		//log/echo
		echo '<div style="padding: 5px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">'.
				'<div style="width: 100%; width: calc(100% + 10px); margin: -5px -5px 5px -5px; box-sizing: border-box; background: #333; color: #fff; padding: 3px 10px;">'.
					'Install Table: <strong>'.$table.'</strong></div>';

		$sql2 = str_replace('[TABLE_PREFIX]', $this->db->getTABLE_PREFIX(), $sql2);

		//run query
		$error = [];
		$sp = explode("\n", $sql1);
		foreach ($sp as $p => $line){

		}
		if (DatabaseCore::getProvider()=='pdo'){
			$this->db->query($sql1);
			if ($this->db->isError()) $error[1] = $this->db->getError();
		}
		$this->db->query($sql2);
		if ($this->db->isError()) $error[2] = $this->db->getError();
		if (DatabaseCore::getProvider()=='pdo'){
			$this->db->query($sql3);
			if ($this->db->isError()) $error[3] = $this->db->getError();
		}

		//echo result
		echo '<div style="padding: 5px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; white-space: pre-wrap; background: '.(count($error)!=0?'#efc9ae':'#d1ffd7').';">'.
				'    -> Result: <strong>'.(count($error)!=0?'Error':'OK').'</strong></div>';

		//if error echo error and sql
		if (count($error)!=0){
			echo '<div style="padding: 5px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; white-space: pre-wrap; background: #dedede;">'.
					'<span style="display: inline-block; width: 100%; max-width: 300px; background: #333; color: #fff; font-weight: bold; padding: 3px 10px;">SQL</span><br><br>'.
						'[2]<br>';
			$exp = explode("\n", $sql2);
			$pad = strlen(''.count($exp));
			foreach ($exp as $c => $line){
				echo '<div style="margin-top: 1px; padding: 1px 0 0 0; border-bottom: 1px solid #aaa;">'.
						'<span style="display: inline-block; background: #eee; min-width: 50px; padding-left: 5px; margin-right: 5px;'.
									'-webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;">'.
							str_pad($c, $pad, '0', STR_PAD_LEFT).'</span>'.$line.'<br></div>';
			}
				  '</div>';
			foreach ($error as $k => $e) {
				echo '<div style="padding: 5px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; white-space: pre-wrap; background: #efc9ae;">'.
						'<span style="display: inline-block; width: 100%; max-width: 300px; background: #333; color: #fff; font-weight: bold; padding: 3px 10px;">ERROR</span><br><br>'.
							$e.
					 '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * update tables
	 */
	public function installInserts(){
		echo '<div style="padding: 15px 5px; border: 2px solid #555; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">'.
			'INSTALL INSERTS</div>';

		foreach (self::$inserts as $table => $list){
			$pk = '';
			foreach (self::$scheme[$table]['keys'] as $v){
				if (mb_stripos($v, 'primary key') === 0){
					$pk = preg_replace('/[^a-zA-Z0-9_\-,]/', '', mb_substr($v, 11));
					break;
				}
			}
			$pk = explode(',', $pk);
			$c = 0;
			foreach ($list as $pos => $in){
				$where = [];
				//check for table primary key
				foreach ($pk as $kk){
					$where[$kk] = $in[$kk];
				}
				$r = $this->db->dbFetch($table, NULL, $where);
				if (count($r)==0){
					$c++;
					$this->db->dbInsert($table, $in);
				}
			}
			echo '<div style="padding: 5px 5px; border: 1px solid #ddd; border-radius: 5px; background: #d1ffd7; margin-top: 5px; white-space: pre-wrap;">'.
				"Table: <strong>$table</strong>: Insert Value Pairs: <strong>$c</strong></div>";
		}
	}

	/**
	 * check columns
	 */
	public function checkColumns(){
		echo '<div style="padding: 15px 5px; border: 2px solid #555; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">'.
			'CHECK COLUMNS</div>';
		foreach (self::$scheme as $table => $s){
			foreach ($s['fields'] as $f => $opt){
				$r = $this->db->queryResult("SELECT 1 as ok FROM information_schema.`COLUMNS` WHERE TABLE_NAME = '".$this->db->getTABLE_PREFIX().$table."' AND COLUMN_NAME = '".$f."'");
				if (count($r) == 0){
					echo '<div style="padding: 5px 5px; border: 1px solid #ddd; border-radius: 5px; background: #efc9ae; margin-top: 5px; white-space: pre-wrap;">'.
						"Table: <strong>$table</strong>: Missing Column: <strong>$f</strong></div>";
				}
			}
		}
	}

}

?>
