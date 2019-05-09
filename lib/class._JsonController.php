<?php
/**
 * FRAMEWORK JsonController
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

namespace intbf;

class JsonController {
	/**
	 * json result of the function
	 * @var array
	 */
	protected $json_result;

	// ================================================================================================

	/**
	 * private class constructor
	 * implements singleton pattern
	 */
	function __construct(){
	}

	/**
	 * dummy function for inheritance
	 * so mother controller may implements a translator pattern
	 * @param string $in
	 * @return string
	 */
	function translate ($in){
		return $in;
	}

	/**
	 * send redirect header and exit
	 * @param string $url
	 */
	function redirect($url){
		header('Location: '. $url);
		die();
	}

	// ================================================================================================

	/**
	 * returns 403 access denied in json format
	 * @param boolean|string $message
	 */
	function json_access_denied($message = false){
		http_response_code (403);
		$this->json_result = array('success' => false, 'msg' => (($message)? $message : 'Access Denied.'), 'code' => 403);
		$this->print_json_result(true);
	}

	/**
	 * returns 404 not found in html format
	 * @param false|string $message (optional) error message
	 */
	function json_not_found($message = false){
		http_response_code (404);
		$this->json_result = array('success' => false, 'msg' => (($message)? $message : 'Page not Found.'), 'code' => 404);
		$this->print_json_result(true);
	}

	/**
	 * echo json result  stored in $this->json_result
	 */
	protected function print_json_result($setJsonHeader = false){
		self::print_json($this->json_result, $setJsonHeader);
	}

	/**
	 *echo data as json string
	 * @param array $json json data
	 * @param boolean $jsonHeader, default: true
	 */
	public static function print_json($json, $jsonHeader = true){
		if ($jsonHeader) header("Content-Type: application/json; charset=UTF-8");
		echo json_encode($json, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
		die();
	}
}
