<?php
namespace intbf\file;
/**
 * class file - model
 * framework class
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @category        model
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */

/**
 * class file - model
 * framework class
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @category        model
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
class File
{
	/**
	 *
	 * @var int
	 */
	public $id;

	/**
	 *
	 * @var int
	 */
	public $link;

	/**
	 *
	 * @var string datetime
	 */
	public $added_on;

	/**
	 *
	 * @var string
	 */
	public $hashname;

	/**
	 *
	 * @var string
	 */
	public $filename;

	/**
	 *
	 * @var int
	 */
	public $size;

	/**
	 *
	 * @var string
	 */
	public $fileextension;

	/**
	 *
	 * @var string
	 */
	public $mime;

	/**
	 *
	 * @var string
	 */
	public $encoding;

	/**
	 *
	 * @var int
	 */
	public $data;

	/**
	 */
	function __construct()
	{
	}

	function getAddedOnDate(){
		if ($this->added_on){
			return date_create($this->added_on);
		} else {
			return null;
		}
	}
}
