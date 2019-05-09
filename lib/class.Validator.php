<?php
namespace intbf;
/**
 * FRAMEWORK Validator
 * filter and validation class
 *
 * INTERTOPIA BASE FRAMEWORK
 * @package         intbf
 * @category        framework
 * @author 			Michael Gnehr
 * @author 			Intertopia
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */

class Validator {

	/**
	 * validator tracks if last test was successfull or not
	 * boolean
	 */
	protected $isError;

	/**
	 * last error message
	 *  -> short error message
	 *  -> on json retuned to sender
	 * string
	 */
	protected $lastErrorMsg;

	/**
	 * last stores last map key if map validation is used
	 */
	protected $lastMapKey = '';

	/**
	 * last error description
	 *  -> error description
	 * string
	 */
	protected $lastErrorDescription;

	/**
	 * last error message
	 * string
	 */
	protected $lastErrorCode;

	/**
	 * filter may sanitize inputs
	 * mixed
	 */
	protected $filtered;

	/**
	 * class constructor
	 */
	function __contstruct(){
	}

	// ==========================================

	/**
	 * set validation status
	 *
	 * @param boolean $isError	error flag
	 * @param integer $code 	html code
	 * @param string  $msg  	short message
	 * @param string  $desc 	error description
	 * @return bool
	 */
	private function setError($isError, $code=0, $msg='', $desc=''){
		$this->isError = $isError;
		$this->lastErrorCode = $code;
		$this->lastErrorMsg = $msg;
		$this->lastErrorDescription = ($desc == '')? $msg : $desc;
		return $isError;
	}

	/**
	 * @return boolean $isError
	 */
	public function getIsError()
	{
		return $this->isError;
	}

	/**
	 * @return string $lastErrorMsg
	 */
	public function getLastErrorMsg()
	{
		return $this->lastErrorMsg;
	}

	/**
	 * @return string $lastErrorDescription
	 */
	public function getLastErrorDescription()
	{
		return $this->lastErrorDescription;
	}

	/**
	 * @return integer $lastErrorCode
	 */
	public function getLastErrorCode()
	{
		return $this->lastErrorCode;
	}

	/**
	 * @return string $lastMapKey
	 */
	public function getLastMapKey()
	{
		return $this->lastMapKey;
	}

	/**
	 * filter may sanitize input values are stored here
	 * Post validators will create sanitized array
	 * @param string $key
	 * @return array|mixed $filtered
	 */
	public function getFiltered($key = NULL)
	{
		if ($key === NULL)
			return $this->filtered;
		else
			return $this->filtered[$key];
	}

	// ==========================================

	/**
	 * call selected validator function
	 * @param mixed $value
	 * @param string $validator
	 * @return boolean value is ok
	 */
	public function validate($value, $validator){
		$validatorName = (is_array($validator))? $validator[0] : $validator;
		$validatorParams = (is_array($validator))? array_slice($validator, 1) : [];
		if (
			method_exists($this, 'V_'.$validatorName)
			&& is_callable([$this, 'V_'.$validatorName]) ){
			return $this->{'V_'.$validatorName}($value, $validatorParams);
		} else {
			$this->setError(true, 403, 'Access Denied', "POST unknown validator: $validatorName");
			if (class_exists('\intbf\ErrorHandler')){
				ErrorHandler::_errorLog("Validator: Unknown Validator: $validatorName", 'Validator: validate');
			} else {
				error_log("Validator: validate: Unknown Validator: $validatorName", 'Validator: validate');
			}
			return !$this->isError;
		}
	}

	/**
	 * validate POST data with a validation list
	 *
	 * $map format:
	 * 	[
	 * 		'postkey' => 'validator',
	 * 		'postkey2' => ['validator', 'validator_param' => 'validator_value', 'validator_param', ...],
	 *  ]
	 *  validator may contains parameter 'optional' -> so required can be disabled per parameter
	 *
	 * @param array $source_unsafe
	 * @param array $map
	 * @param boolean $required key is required, overwritable with validator parameter 'optional'
	 * @param boolean $errormap don't break on error, and sum up all errors messages, does not affect required error, so dont answer incomplete posts, return errors fields as arrays
	 * @param boolean $multi_validator allow multiple validators on same key, you have to set additional parameter 'multivalidator' and add additional array layer
	 *		e.g.
	 *			['key' => ['multi', ['validator1'], ['validator2'], ...]]
	 * @return boolean
	 */
	public function validateMap($source_unsafe, $map, $required = true, $errormap = false, $multi_validator = false){
		$out = [];
		$errorMsgs = [];
		$errorDesc = [];
		$errorCode = [];
		$hasError = false;
		foreach($map as $key => $validator){
			$this->lastMapKey = $key;
			if (!isset($source_unsafe[$key])){
				if ($required && !in_array('optional', $validator, true)){
					$this->setError(true, 403, 'Access Denied', "missing parameter: '$key'");
					return !$this->isError;
				} else {
					$this->setError(false);
				}
			} else {
				$tmp_vali = [];
				if ($multi_validator && (($pos =  array_search('multivalidator' , $validator, true)) !== false)){
					$tmp_vali = $validator;
					unset($tmp_vali[$pos]);
				} else {
					$tmp_vali[] = $validator;
				}
				foreach($tmp_vali as $vali){
					$this->validate($source_unsafe[$key], $vali);
					if ($this->isError){
						if ($errormap===true){
							$hasError = true;
							$errorMsgs[$key][] = $this->lastErrorMsg;
							$errorDesc[$key][] = $this->lastErrorDescription;
							$errorCode[$key][] = $this->lastErrorCode;
						} else {
							break 2;
						}
					} else {
						$out[$key] = $this->filtered;
					}
				}
			}
		}
		if ($hasError){
			$this->isError = true;
			$this->lastErrorCode = $errorCode;
			$this->lastErrorMsg = $errorMsgs;
			$this->lastErrorDescription = $errorDesc;
		}
		$this->filtered = $out;
		return !$this->isError;
	}

	/**
	 * validate POST data with a validation list
	 * add additional mfunction layer, so this will be required
	 *
	 * $map format:
	 * 	[ 'mfunction_name' =>
	 * 		[
	 * 			'postkey' => 'validator',
	 * 			'postkey2' => ['validator', 'validator_param' => 'validator_value', 'validator_param', ...],
	 *  	]
	 *  ]
	 *
	 * @param array $map
	 * @param string $groupKey
	 * @param boolean $required keys is required (groupKey key is always required)
	 * @return boolean
	 */
	public function validatePostGroup($map, $groupKey = 'mfunction', $required = true){
		if (!isset($_POST[$groupKey]) || !isset($map[$_POST[$groupKey]])){
			$this->setError(true, 403, 'Access Denied', "POST request don't match $groupKey.");
			return !$this->isError;
		} else {
			$ret = $this->validateMap($_POST, $map[$_POST[$groupKey]], $required);
			if ($ret) $this->filtered = [$_POST[$groupKey].'' => $this->filtered];
			return $ret;
		}
	}

	// ====== VALIDATORS ========================
	// functions must start with 'V_validatorname'

	/**
	 * dummy validator
	 * always return 'valid'
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_dummy($value = NULL, $params = NULL){
		$this->filtered = $value;
		return true;
	}

	/**
	 * boolean validator
	 *
	 * params:
	 *  error	2	error message on error case
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_boolean($value, $params = []){
		$v = trim($value);
		if ($v === true || $v === 'true' || $v === '1' || $v === 1){
			$this->filtered = true;
			return !$this->setError(false);
		} else if ($v === false || $v === 'false' || $v === '0' || $v === 0 || $v === '') {
			$this->filtered = false;
			return !$this->setError(false);
		}
		$msg = (isset($params['error']))? $params['error'] : 'No Boolean' ;
		return !$this->setError(true, 200, $msg, 'No Boolean');
	}

	/**
	 * integer validator
	 *
	 * params:
	 *  KEY  1-> single value, 2-> key value pair
	 * 	min 	2
	 * 	max 	2
	 *  even 	1
	 *  odd 	1
	 *  modulo	2
	 *  error	2	error message on error case
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_integer($value, $params = []){
		if (filter_var($value, FILTER_VALIDATE_INT) === false){
			$msg = (isset($params['error']))? $params['error'] : 'No Integer' ;
			return !$this->setError(true, 200, $msg, 'No Integer');
		} else {
			$v = filter_var($value, FILTER_VALIDATE_INT);
			$this->filtered = $v;
			if (in_array('even', $params, true) && $v%2 != 0){
				$msg = (isset($params['error']))? $params['error'] : 'Integer have to be even' ;
				return !$this->setError(true, 200, $msg, 'integer not even');
			}
			if (in_array('odd', $params, true) && $v%2 == 0){
				$msg = (isset($params['error']))? $params['error'] : 'Integer have to be odd' ;
				return !$this->setError(true, 200, $msg, 'integer not odd');
			}
			if (isset($params['min']) && $v < $params['min']){
				$msg = (isset($params['error']))? $params['error'] : "Integer out of range: smaller than {$params['min']}" ;
				return !$this->setError(true, 200, $msg, 'integer to small');
			}
			if (isset($params['max']) && $v > $params['max']){
				$msg = (isset($params['error']))? $params['error'] : "Integer out of range: larger than {$params['max']}" ;
				return !$this->setError(true, 200, $msg, 'integer to big');
			}
			if (isset($params['modulo']) && $v%$params['modulo'] != 0){
				$msg = (isset($params['error']))? $params['error'] : "Integer modulo failed" ;
				return !$this->setError(true, 200, $msg, 'modulo failed');
			}
			return !$this->setError(false);
		}
	}

	/**
	 * float validator
	 *
	 *	params:
	 *		KEY  1-> single value, 2-> key value pair
	 *		decimal_seperator	2	[. or ,] default: .
	 *		min 				2	min value
	 *		max 				2	max value
	 *		step				2	step - be carefull may produce errors (wrong deteced values)
	 *		format				2	trim to x decimal places
	 *		parse				2	has to be array with additional keys, parse after validation -> funs number format
	 *			decimals		2	decimal count, 		 default: 2
	 *			dec_point		2	dec point/seperator, default: ','
	 *			thousands		2	thousands seperator, default: ''
	 *			append			2	appends XX to float value, e.g. ' EUR', or ' $', default: disabled
	 *		error				2	error message on error case
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_float($value, $params = []){
		$decimal = (isset($params['decimal_seperator']))? $params['decimal_seperator'] : '.' ;
		if (filter_var($value, FILTER_VALIDATE_FLOAT, array('options' => array('decimal' => $decimal))) === false){
			$msg = (isset($params['error']))? $params['error'] : 'No Float' ;
			return !$this->setError(true, 200, $msg, 'No Float');
		} else {
			$v = filter_var($value, FILTER_VALIDATE_FLOAT, array('options' => array('decimal' => $decimal)));
			if (isset($params['min']) && $v < $params['min']){
				$msg = (isset($params['error']))? $params['error'] : "Float out of range: smaller than {$params['min']}" ;
				return !$this->setError(true, 200, $msg, 'float to small');
			}
			if (isset($params['max']) && $v > $params['max']){
				$msg = (isset($params['error']))? $params['error'] : "Float out of range: larger than {$params['max']}" ;
				return !$this->setError(true, 200, $msg, 'float to big');
			}
			if (isset($params['step'])){
			$mod = $params['step'];
				$cv = $v;
				$ex = '';
				if (($p = strpos($mod , '.'))!== false){
					$ex = strlen(substr($params['step'], $p + 1));
					$ex = (pow(10, $ex));
					$mod = $mod * $ex;
					$cv = $cv * $ex;
				}
				$k = strlen($ex);
				if ((is_numeric( $cv ) && mb_strpos($value, '.') && mb_strpos($value, '.') + ($k) < mb_strlen($value)) || $cv % $mod != 0){
					$msg = (isset($params['error']))? $params['error'] : "float invalid step" ;
					return !$this->setError(true, 200, $msg, 'float invalid step');
				}
			}
			if (isset($params['format'])){
				$this->filtered = number_format($v, $params['format'], $decimal, '');
			} else {
				$this->filtered = $v;
			}
			if (isset($params['parse']) && is_array($params['parse'])) {
				$this->filtered = number_format(
					$v, 
					(isset($params['parse']['decimals']))? $params['parse']['decimals'] : 2, 
					(isset($params['parse']['dec_point']))? $params['parse']['dec_point'] : ',', 
					(isset($params['parse']['thousands']))? $params['parse']['thousands'] : ''
				);
				if (isset($params['parse']['append'])){
					$this->filtered = $this->filtered . $params['parse']['append'];
				}
			}
			return !$this->setError(false);
		}
	}

	/**
	 * check if integer and larger than 0
	 * @param integer $value
	 * @return boolean
	 */
	public function V_id ($value, $params = NULL){
		return $this->V_integer($value, ['min' => 1]);
	}

	/**
	 * text validator
	 *
	 * params:
	 *  KEY  1-> single value, 2-> key value pair
	 * 	strip 				1
	 * 	trim 				1
	 *  htmlspecialchars	1
	 *  htmlentities 		1
	 *  minlength 2		minimum string length
	 *  maxlength 2		maximum string length - default 127, set -1 for unlimited value
	 *  error	  2 	replace whole error message on error case
	 *  empty	  1 	allow empty value
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_text($value, $params = []) {
		if (!is_string($value)){
			$msg = "No Text";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'No Text');
		} else {
			if (in_array('empty', $params, true) && $value === ''){
				$this->filtered = '';
				return !$this->setError(false);
			}
			$s = ''.$value;
			if (in_array('strip', $params, true) ){
				$s = strip_tags($s);
			}
			if (in_array('htmlspecialchars', $params, true)){
				$s = htmlspecialchars($s);
			}
			if (in_array('htmlentities', $params, true)){
				$s = htmlentities($s);
			}
			if (in_array('trim', $params, true)){
				$s = trim($s);
			}
			if (isset($params['minlength']) && strlen($s) < $params['minlength']){
				$msg = "The text is too short (Minimum length: {$params['minlength']})";
				if (isset($params['error'])) $msg = $params['error'];
				return !$this->setError(true, 200, $msg, 'text validation failed - too short');
			}
			if (isset($params['maxlength']) && $params['maxlength'] != -1 && strlen($s) > $params['maxlength']){
				$msg = "The text is too long (Maximum length: {$params['maxlength']})";
				if (isset($params['error'])) $msg = $params['error'];
				return !$this->setError(true, 200, $msg, 'text validation failed - too long');
			}
			$this->filtered = $s;
			return !$this->setError(false);
		}
	}

	/**
	 * email validator
	 *
	 * $param
	 *  empty		1	allow empty value
	 *  maxlength	2	maximum string length
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_mail ($value, $params = []) {
		$email = filter_var($value, FILTER_SANITIZE_EMAIL);
		if (in_array('empty', $params, true) && $email === ''){
			$this->filtered = $email;
			return !$this->setError(false);
		}
		if (isset($params['maxlength']) && strlen($email) >= $params['maxlength']){
			$msg = "E-Mail is too long (Maximum length: {$params['maxlength']})";
			return !$this->setError(true, 200, $msg);
		}
		$re = '/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/';
		if ($email !== '' && (filter_var($email, FILTER_VALIDATE_EMAIL) === false || !preg_match($re, $email) )){
			return !$this->setError(true, 200, "mail validation failed", 'mail validation failed');
		} else {
			$this->filtered = $email;
			return !$this->setError(false);
		}
	}

	/**
	 * phone validator
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_phone($value, $params = NULL) {
		$phone = ''.trim(strip_tags(''.$value));
		$re = '/[^0-9+ ]/';
		$phone = trim(preg_replace($re, '' ,$phone));
		if ($phone == '' || $phone == false) $this->filtered = '';
		elseif (strlen($phone) > 40) {
			return !$this->setError(true, 200, "phone validation failed", 'phone validation failed');
		} else {
			$this->filtered = $phone;
		}
		return !$this->setError(false);
	}

	/**
	 * name validator
	 *
	 * @param $value
	 * @param array $params
	 *  minlength 2		minimum string length
	 *  maxlength 2		maximum string length - default 127, set -1 for unlimited value
	 *  error	  2 	replace whole error message on error case
	 *  empty	  1 	allow empty value
	 *  multi	  2		allow multiple names seperated with this seperator, length 1
	 *  multi_add_space  1 adds space after seperator to prettify list
	 * @return boolean
	 */
	public function V_name($value, $params = NULL)  {
		$name = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $name === ''){
			$this->filtered = '';
			return !$this->setError(false);
		}
		$re = NULL;
		$re_no_sep = '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9\-_ .äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+$/';
		if (!isset($params['multi']) || strlen($params['multi']) != 1 ){
			$re = $re_no_sep;
			$params['multi'] = NULL;
			unset($params['multi']);
		} else {
			$sep = $params['multi'];
			if (mb_strpos("/\\[]()-", $sep) !== false) $sep = "\\".$sep;
			$re = '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9\-_ '.$sep.'.äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+$/';
		}
		if ( $name !== '' && (!preg_match($re, $name))){
			$msg = ((isset($params['error']) )?$params['error']:'name validation failed');
			return !$this->setError(true, 200, $msg, 'name validation failed');
		}
		if (!isset($params['maxlength'])){
			$params['maxlength'] = 127;
		}
		if (isset($params['maxlength']) && $params['maxlength'] != -1 && strlen($name) > $params['maxlength']){
			$msg = "The name is too long (Maximum length: {$params['maxlength']})";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'name validation failed - too long');
		}
		if (isset($params['minlength']) && strlen($name) < $params['minlength']){
			$msg = "The name is too short (Minimum length: {$params['minlength']})";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'name validation failed - too short');
		}
		if (!isset($params['multi']) || !mb_strpos($name, $params['multi'])){
			$this->filtered=$name;
		} elseif(mb_strpos($name, $params['multi'])) {
			$tmp_list = explode($params['multi'], $name);
			$tmp_names = [];
			foreach ($tmp_list as $tmp_name){
				$tmp_name = trim($tmp_name);
				if (preg_match($re_no_sep, $tmp_name) && $tmp_name){
					$tmp_names[] = $tmp_name;
				}
			}
			$this->filtered=implode($params['multi'].((in_array('multi_add_space', $params, true))?' ':''), $tmp_names);
		}
		return !$this->setError(false);
	}

	/**
	 * url validator
	 *
	 * @param $value
	 * @param $params
	 * 	empty	  		1 	allow empty value
	 * 	error	 		2 	replace whole error message on error case
	 *  forceprotocol	1	force http://|https:// in url
	 *  forceslash		1	force trailingslash
	 * @return boolean
	 */
	public function V_url($value, $params = NULL)  {
		$url = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $url === ''){
			$this->filtered = '';
			return !$this->setError(false);
		}
		$re = '/^((http[s]?)((:|%3A)\/\/))'.((in_array('forceprotocol', $params, true))?'':'?').'(((\w)+((-|\.)(\w+))*)+(\w){0,6}?(:([0-5]?[0-9]{1,4}|6([0-4][0-9]{3}|5([0-4][0-9]{2}|5([0-2][0-9]|3[0-5])))))?\/'.((in_array('forceslash', $params, true))?'':'?').')((\w)+((\.|-)(\w)+)*\/'.((in_array('forceslash', $params, true))?'':'?').')*((\?([\w]+(=(\w|%2F|%3A|\.|\-)+)?))(&([\w]+(=(\w|%2F|%3A|\.|\-)+)?))*)?$/';
		if (!preg_match($re, $url) || strlen($url) >= 128){
			$msg = "url validation failed";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'url validation failed');
		} else {
			$this->filtered=$url;
		}
		return !$this->setError(false);
	}

	/**
	 * client ip validator
	 * check if string is a valid ip on whitelist, or not in blacklist (supports ipv4 and ipv6)
	 * if value is not set function checks SERVER['REMOTE_ADDR'], so filtered value contains real client IP
	 * keep in mind, in array validation to set and override may given userinput to NULL, or set 'ignoreval' param
	 * this function does not check if the given value contains a valid ip address
	 * checks blacklist first
	 *
	 * v4subnet_black and v4subnet_white will only work with IPv4 IP addresses and clients
	 *
	 * @param mixed $value ignored
	 * @param array $params
	 *  	ignoreval		1 	ignore value - use $_SERVER['REMOTE_ADDR']
	 *  	whitelist		2 	if set only allow client ips from this whitelist
	 *  	blacklist		2 	if set block clients from this ips
	 *  	subnet			1 	allow cidr subnets on black and whitelist format: '127.0.0.1/32' or '22AD:00DD:0000:1F33::/64'
	 *  	v4subnet_white	2 	check if ip is in ip range, format: 127.0.0.1/32
	 *  	error		  2	replace whole error message on error case
	 * @return boolean
	 */
	public function V_clientIp($value = NULL, $params = NULL){
		//ignore value?
		if ($value === NULL||!is_string||empty($value)||in_array('ignoreval', $params, true)) $value = $_SERVER['REMOTE_ADDR'];
		//check blacklist
		if (isset($params['blacklist']) && is_array($params['blacklist']) && in_array( $value, $params['blacklist'], true)){
			$msg = ((isset($params['error']) )?$params['error']:'Blocked ip address.');
			return !$this->setError(true, 200, $msg, 'Blocked ip address.');
		}
		if (isset($params['blacklist']) && is_array($params['blacklist']) && in_array( 'subnet', $params, true)){
			foreach($params['blacklist'] as $b){
				if (strpos($b, '/') !== false){
					if ($this->V_ipCidr($value, ['onlyreturn', 'cidr' => $b])){
						$msg = ((isset($params['error']) )?$params['error']:'Blocked ip address.');
						return !$this->setError(true, 200, $msg, 'Blocked ip address.');
					}
				}
			}
		}
		//check whitelist
		$found = false;
		if (isset($params['whitelist']) && is_array($params['whitelist']) && in_array( 'subnet', $params, true)){
			$found = in_array( $value, $params['whitelist'], true);
			if (!$found) foreach($params['whitelist'] as $w){
				if (strpos($w, '/') !== false){
					if ($this->V_ipCidr($value, ['onlyreturn', 'cidr' => $w])){
						$found = true;
						break;
					}
				}
			}
		}
		if (!$found && isset($params['whitelist']) && (!is_array($params['whitelist']) || !in_array( $value, $params['whitelist'], true))){
			$msg = ((isset($params['error']) )?$params['error']:'Blocked ip address.');
			return !$this->setError(true, 200, $msg, 'Blocked ip address.');
		}

		$this->filtered = $value;
		return !$this->setError(false);
	}

	/**
	 * ip v4 validator
	 * check if string is a valid ip v4 address
	 * @param $value
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @return boolean
	 */
	public function V_ip4($value = NULL, $params = []){
    	$flag = FILTER_FLAG_IPV4;
		if (in_array('no_priv_range', $params, true)){
			$flag = $flag | FILTER_FLAG_NO_PRIV_RANGE;
		}
		if (in_array('no_res_range', $params, true)){
			$flag = $flag | FILTER_FLAG_NO_RES_RANGE;
		}
		$r = filter_var(
	        $value,
	        FILTER_VALIDATE_IP,
	        array('flags' => $flag)
	    );
		if (!$r && !in_array('onlyreturn', $params, true)){
			$msg = ((isset($params['error']) )?$params['error']:'Invalid IPv4.');
			return !$this->setError(true, 200, $msg, 'Invalid IPv4.');
		} elseif ($r && !in_array('onlyreturn', $params, true)) {
			$this->filtered = $r;
			return !$this->setError(false);
		} elseif(!$r) {
			return false;
		} else {
			return $r;
		}
	}

	/**
	 * ip v6 validator
	 * check if string is a valid ip v6 address
	 * @param $value
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @return boolean
	 */
	public function V_ip6($value = NULL, $params = []){
    	$flag = FILTER_FLAG_IPV6;
		if (in_array('no_priv_range', $params, true)){
			$flag = $flag | FILTER_FLAG_NO_PRIV_RANGE;
		}
		if (in_array('no_res_range', $params, true)){
			$flag = $flag | FILTER_FLAG_NO_RES_RANGE;
		}
		$r = filter_var(
	        $value,
	        FILTER_VALIDATE_IP,
	        array('flags' => $flag)
	    );
		if (!$r && !in_array('onlyreturn', $params, true)){
			$msg = ((isset($params['error']) )?$params['error']:'Invalid IPv6.');
			return !$this->setError(true, 200, $msg, 'Invalid IPv6.');
		} elseif ($r && !in_array('onlyreturn', $params, true)) {
			$this->filtered = $r;
			return !$this->setError(false);
		} elseif(!$r) {
			return false;
		} else {
			return $r;
		}
	}

	/**
	 * ip validator
	 * check if string is a valid ip address (supports ipv4 and ipv6)
	 * @param $value
	 *		getversion		1	do not only return valid value, do also return ip version -> return value: [$value, version]
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @return boolean
	 */
	public function V_ip($value, $params = []){
		$tmp_p = ['onlyreturn'];
		if (in_array('no_priv_range', $params, true)) $tmp_p[] = 'no_priv_range';
		if (in_array('no_res_range', $params, true)) $tmp_p[] = 'no_res_range';
		if ($v4 = $this->V_ip4( $value, $tmp_p )){
			if (!in_array('onlyreturn', $params, true)) $this->filtered = (in_array('getversion', $params, true))? [$v4, 4] : $v4;
			return (in_array('onlyreturn', $params, true))? ($v4) : (!$this->setError(false));
		} elseif ($v6 = $this->V_ip6( $value, $tmp_p )){
			if (!in_array('onlyreturn', $params, true)) $this->filtered = (in_array('getversion', $params, true))? [$v6, 6] : $v6;
			return (in_array('onlyreturn', $params, true))? ($v6) : (!$this->setError(false));
		} else {
			$msg = ((isset($params['error']) )?$params['error']:'Invalid IP.');
			return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'Invalid IP.');
		}
	}

	/**
	 * ipv4 cidr(subnet) validator
	 * check if string is in given ip range - given in cidr format
	 *
	 * partial based on, see in line comment
	 * @see https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5#14841828
	 *
	 * @param $value
	 *		cidr (required)	2	cidr e.g. 127.0.0.1/32
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @throws \Exception
	 * @return boolean
	 */
	public function V_ipv4Cidr($value, $params = []){
		$tmp_p = ['onlyreturn'];
		if (in_array('no_priv_range', $params, true)) $tmp_p[] = 'no_priv_range';
		if (in_array('no_res_range', $params, true)) $tmp_p[] = 'no_res_range';
		// check cidr
		if (!isset($params['cidr']) || !is_string($params['cidr']) || empty($params['cidr']) ) {
			throw new \Exception('Validator[ipv4Cidr]: Missing cidr parameter.');
		}
		$cidr = explode('/', $params['cidr']);
        $subnet = isset($cidr[0]) ? $cidr[0] : NULL;
        $mask   = isset($cidr[1]) ? $cidr[1] : NULL;
		if ($subnet === null || empty($subnet) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new \Exception('Validator[ipv4Cidr]: Invalid cidr parameter, missing or invalid subnet');
        }
	    if ($mask === null || empty($mask) || $mask < 0 || $mask > 32) {
            throw new \Exception('Validator[ipv4Cidr]: Invalid cidr parameter, missing or invalid mask');
        }
		// check is ip v4
		if ($v4 = $this->V_ip4( $value, $tmp_p )){
			// if condition -> based on: https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5#14841828
			if ((ip2long($v4) & ~((1 << (32 - $mask)) - 1) ) == ip2long($subnet)){
				if (!in_array('onlyreturn', $params, true)) $this->filtered = $v4;
				return (in_array('onlyreturn', $params, true))? ($v4) : (!$this->setError(false));
			} else {
				$msg = ((isset($params['error']) )?$params['error']:'No IPv4 cidr match.');
				return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'No IPv4 cidr match.');
			}
		} else {
			$msg = ((isset($params['error']) )?$params['error']:'Invalid IPv4.');
			return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'Invalid IPv4.');
		}
	}

	/**
	 * ipv6 cidr(subnet) validator
	 * check if string is in given ip range - given in cidr format
	 *
	 * partial based on, see in line comment
	 * @see https://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet#7952169
	 *
	 * @param $value
	 *		cidr (required)	2	cidr e.g. '22AD:00DD:0000:1F33::/64'
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @throws \Exception
	 * @return boolean
	 */
	public function V_ipv6Cidr($value, $params = []){
		$tmp_p = ['onlyreturn'];
		if (in_array('no_priv_range', $params, true)) $tmp_p[] = 'no_priv_range';
		if (in_array('no_res_range', $params, true)) $tmp_p[] = 'no_res_range';
		// check cidr
		if (!isset($params['cidr']) || !is_string($params['cidr']) || empty($params['cidr']) ) {
			throw new \Exception('Validator[ipv6Cidr]: Missing cidr parameter.');
		}
		$cidr = explode('/', $params['cidr']);
        $subnet = isset($cidr[0]) ? $cidr[0] : NULL;
        $mask   = isset($cidr[1]) ? $cidr[1] : NULL;
		if ($subnet === null || empty($subnet)) {
            throw new \Exception('Validator[ipv6Cidr]: Invalid cidr parameter, missing or invalid subnet');
        }
	    if ($mask === null || empty($mask) || $mask < 0 || $mask > 128) {
            throw new \Exception('Validator[ipv6Cidr]: Invalid cidr parameter, missing or invalid mask');
        }
		// check is ip v6
		if ($v6 = $this->V_ip6( $value, $tmp_p )){
			// until if condition -> based on: https://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet#7952169
			$subnet = inet_pton($subnet);
        	$addr = inet_pton($v6);
			// iPv6MaskToByteArray
			$mask_tmp = $mask;
			  $addr_tmp = str_repeat("f", $mask_tmp / 4);
			  switch ($mask_tmp % 4) {
				case 0:
				  break;
				case 1:
				  $addr_tmp .= "8";
				  break;
				case 2:
				  $addr_tmp .= "c";
				  break;
				case 3:
				  $addr_tmp .= "e";
				  break;
			  }
			  $addr_tmp = str_pad($addr_tmp, 32, '0');
			  $addr_tmp = pack("H*" , $addr_tmp);
			  $mask_bin = $addr_tmp;
			// ---
        	$match = (($addr & $mask_bin) == $subnet);
			if ($match){
				if (!in_array('onlyreturn', $params, true)) $this->filtered = $v6;
				return (in_array('onlyreturn', $params, true))? ($v6) : (!$this->setError(false));
			} else {
				$msg = ((isset($params['error']) )?$params['error']:'No IPv6 cidr match.');
				return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'No IPv6 cidr match.');
			}
		} else {
			$msg = ((isset($params['error']) )?$params['error']:'Invalid IPv6.');
			return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'Invalid IPv6.');
		}
	}

	/**
	 * ip cidr(subnet) validator
	 * check if string is in given ip range - given in cidr format
	 *
	 * @param $value
	 *		cidr (required)	2	cidr e.g. '22AD:00DD:0000:1F33::/64' or '127.0.0.1/32'
	 *		no_priv_range	1	prevent private address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		no_res_range	1	prevent reservated address range to be validated as true @see http://php.net/manual/de/filter.filters.flags.php
	 *		error			2	replace whole error message on error case
	 *		onlyreturn		1	dont set $this->filtered or $this->error - required for other validators - return false or valid value
	 * @param array $params
	 * @throws \Exception
	 * @return boolean
	 */
	public function V_ipCidr($value, $params = []){
		$tmp_p = ['onlyreturn'];
		if (in_array('no_priv_range', $params, true)) $tmp_p[] = 'no_priv_range';
		if (in_array('no_res_range', $params, true)) $tmp_p[] = 'no_res_range';
		// check cidr
		if (!isset($params['cidr']) || !is_string($params['cidr']) || empty($params['cidr']) ) {
			throw new \Exception('Validator[ipCidr]: Missing cidr parameter.');
		} else {
			$tmp_p['cidr'] = $params['cidr'];
		}
		$cidr = explode('/', $params['cidr']);
        $subnet = isset($cidr[0]) ? $cidr[0] : NULL;
        $mask   = isset($cidr[1]) ? $cidr[1] : NULL;

		if ($subnet === null || empty($subnet)) {
            throw new \Exception('Validator[ipCidr]: Invalid cidr parameter, missing or invalid subnet');
        }
	    if ($mask === null || empty($mask) || $mask < 0 || $mask > 128) {
            throw new \Exception('Validator[ipCidr]: Invalid cidr parameter, missing or invalid mask');
        }
		//cidr version
		$cidr_version = (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))? 4 : 6;
		if ($cidr_version == 4 && ($v4 = $this->V_ipv4cidr($value, $tmp_p))){
			if (!in_array('onlyreturn', $params, true)) $this->filtered = $v4;
			return (in_array('onlyreturn', $params, true))? ($v4) : (!$this->setError(false));
		} elseif ($cidr_version == 6 && ($v6 = $this->V_ipv6cidr($value, $tmp_p))){
			if (!in_array('onlyreturn', $params, true)) $this->filtered = $v6;
			return (in_array('onlyreturn', $params, true))? ($v6) : (!$this->setError(false));
		} else {
			$msg = ((isset($params['error']) )?$params['error']:'No IP cidr match.');
			return (in_array('onlyreturn', $params, true))? (false) : !$this->setError(true, 200, $msg, 'No IP cidr match.');
		}
	}

	/**
	 * ip validator (deprecated)
	 * old implementation, php now provides this function
	 * check if string is a valid ip address (supports ipv4 and ipv6)
	 * @param $value
	 *		error			2	replace whole error message on error case
	 * @param array $params
	 * @return boolean
	 */
	public function V_ipOld($value, $params = NULL){
		if (self::isValidIP($value)){
			$this->filtered = $value;
			return !$this->setError(false);
		} else {
			$msg = ((isset($params['error']) )?$params['error']:'No ip address');
			return !$this->setError(true, 200, $msg, 'No ip address');
		}
	}

	/**
	 * check if string is a valid ip address (supports ipv4 and ipv6)
	 * helper function
	 *
	 * @param string $ipadr
	 * @param boolean $recursive if true also allowes IP address with surrounding brackets []
	 * @return boolean
	 */
	public static function isValidIP( $ipadr, $recursive = true) {
		if ( preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/', $ipadr)) {
			return true;
		} else {
			if ($recursive && strlen($ipadr) > 2 && $ipadr[0] == '[' && $ipadr[strlen($ipadr)] == ']'){
				return self::isValidIP(substr($ipadr, 1, -1), false);
			} else {
				return false;
			}
		}
	}

	/**
	 * check if string is ends with other string
	 * @param string $haystack
	 * @param array|string $needle
	 * @param null|string $needleprefix
	 * @return boolean
	 */
	public static function endsWith($haystack, $needle, $needleprefix = null)
	{
		if (is_array($needle)){
			foreach ($needle as $sub){
				$n=(($needleprefix)?$needleprefix:'').$sub;
				if (substr($haystack, -strlen($n))===$n) {
					return true;
				}
			}
			return false;
		} else if (strlen($needle) == 0){
			return true;
		} else {
			return substr($haystack, -strlen($needle))===$needle;
		}
	}

	/**
	 * domain validator
	 *
	 * $param
	 *  empty	1	allow empty value
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_domain($value, $params = NULL){
		$host = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $host === ''){
			$this->filtered = $host;
			return !$this->setError(false);
		}
		if ($this->V_ip($host)){
			$this->filtered = $host;
			return !$this->setError(false);
		} else if ( preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $host) &&
			( (version_compare(PHP_VERSION, '7.0.0') >= 0) && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)!==false  ||
				(version_compare(PHP_VERSION, '7.0.0') < 0) ) ) {
			$this->filtered = $host;
			return !$this->setError(false);
		} else {
			$value_idn = idn_to_ascii($host);
			if ( preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $value_idn) &&
				( (version_compare(PHP_VERSION, '7.0.0') >= 0) && filter_var($value_idn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)!==false  ||
					(version_compare(PHP_VERSION, '7.0.0') < 0) ) ) {
				$this->filtered = $value_idn;
				return !$this->setError(false);
			} else {
				return !$this->setError(true, 200, 'Kein gültiger Hostname angegeben' );
			}
		}
	}

	/**
	 * regex validator
	 *
	 * $param
	 *  regex			2	match pattern
	 *  strsplit		2	split string into parts and run regex on each part, here specify split length (integer), you may require this on large texts
	 *  errorkey		2	replace 'regex' with errorkey on error case
	 *  error			2	replace whole error message on error case
	 *  upper			1	string to uppercase
	 *  specialchars	1	add htmlspecialchar filter
	 *  addslashes		1	add addslashes filter
	 *  stripslashes	1	add stripslashes filter
	 *  lower			1	string to lower case
	 *  replace			2	touple [search, replace] replace string
	 *  minlength		2	minimum string length
	 *  maxlength		2	maximum string length
	 *  noTagStrip		1	disable tag strip before validation
	 *  noTrim			1	disable trim whitespaces
	 *  trimLeft		2	trim Text on left side, parameter trim characters
	 *  trimRight		2	trim Text on right side, parameter trim characters
	 *  empty			1	allow empty string if not in regex
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_regex($value, $params = ['pattern' => '/.*/']) {
		if (!is_numeric($value)&&!is_string($value)&&!is_bool($value)&&$value!=''){
			$msg = "Invalid 'value' type";
			return !$this->setError(true, 200, (isset($params['error']))? $params['error']: $msg, $msg);
		}
		$v = ''.$value;
		if (in_array('specialchars', $params, true)){
			$v = htmlspecialchars($v);
		}
		if (in_array('addslashes', $params, true)){
			$v = addslashes($v);
		}
		if (in_array('stripslashes', $params, true)){
			$v = stripslashes($v);
		}
		if (!in_array('noTagStrip', $params, true)){
			$v = strip_tags($v);
		}
		if (!in_array('noTrim', $params, true)){
			$v = trim($v);
		}
		if (isset($params['trimLeft'])){
			$v = ltrim ( $v , $params['trimLeft'] );
		}
		if (isset($params['trimRight'])){
			$v = rtrim ( $v , $params['trimRight'] );
		}
		if (in_array('empty', $params, true) && $v === ''){
			$this->filtered = $v;
			return !$this->setError(false);
		}
		if (isset($params['replace'])){
			$v = str_replace($params['replace'][0], $params['replace'][1], $v);
		}
		if (in_array('upper', $params, true)){
			$v = strtoupper($v);
		}
		if (in_array('lower', $params, true)){
			$v = strtolower($v);
		}
		if (isset($params['maxlength']) && strlen($v) > $params['maxlength']){
			$msg = "String is too long (Maximum length: {$params['maxlength']})";
			return !$this->setError(true, 200, (isset($params['error']))? $params['error']: $msg, $msg);
		}
		if (isset($params['minlength']) && strlen($v) < $params['minlength']){
			$msg = "String is too short (Minimum length: {$params['minlength']})";
			return !$this->setError(true, 200, (isset($params['error']))? $params['error']: $msg, $msg);
		}
		$re = $params['pattern'];
		if (!isset($params['strsplit'])){
			if (!preg_match($re, $v)) {
				$msg = ((isset($params['errorkey']) )?$params['errorkey']:'regex').' validation failed';
				return !$this->setError(true, 200, (isset($params['error']))? $params['error']: $msg, $msg);
			} else {
				$this->filtered=$v;
			}
		} else {
			$split = str_split($v, $params['strsplit']);
			foreach($split as $part){
				if (!preg_match($re, $part)) {
					$msg = ((isset($params['errorkey']) )?$params['errorkey']:'regex').' validation failed';
					return !$this->setError(true, 200, (isset($params['error']))? $params['error']: $msg, $msg);
				}
			}
			$this->filtered=$v;
		}
		return !$this->setError(false);
	}

	/**
	 * password validator
	 *
	 * $param
	 *  minlength 2		minimum string length
	 *  maxlength 2		maximum string length
	 *  empty	  1 	allow empty value
	 *  encrypt	  1 	encrypt password - only available if Crypto class is defined
	 *  hash	  1 	hash password	 - only available if Crypto class is defined
	 *	error	   2	replace whole error message on error case
	 *
	 * @param $value
	 * @param $params
	 * @throws \Exception
	 * @return boolean
	 */
	public function V_password($value, $params = []) {
		$p = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $p === ''){
			$this->filtered = $p;
			return !$this->setError(false);
		}
		if (isset($params['maxlength']) && strlen($p) >= $params['maxlength']){
			$msg = "The password is too long (Maximum length: {$params['maxlength']})";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg);
		}
		if (isset($params['minlength']) && strlen($p) < $params['minlength']){
			$msg = "The password is too short (Minimum length: {$params['minlength']})";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg);
		}
		$emsg = NULL;
		if (in_array('hash', $params, true)){
			if (!class_exists('\intbf\Crypto')){
				$emsg = 'Validator: Password: "hash" requires Crypto class to be loaded.';
			} elseif(!defined('AUTH_PW_PEPPER')){
				$emsg = 'Validator: Password: "hash": global constant AUTH_PW_PEPPER required.';
			} else {
				$p = Crypto::hashPassword($p.AUTH_PW_PEPPER);
			}
		} elseif (in_array('encrypt', $params, true)){
			if (!class_exists('\intbf\Crypto')){
				$emsg = 'Validator: Password: "encrypt" requires Crypto class to be loaded.';
			} else {
				$p = Crypto::pad_string($p);
				$p = Crypto::encrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE.'/secret.php'), CRYPTO_SECRET_KEY);
			}
		}
		if (isset($emsg) && $emsg){
			if (class_exists('\intbf\ErrorHandler')){
				ErrorHandler::_errorTraceLog($emsg);
			} else {
				error_log($emsg);
			}
			if (isset($params['error'])) $emsg = $params['error'];
			return !$this->setError(true, 200, $emsg);
		}
		$this->filtered=$p;
		return !$this->setError(false);
	}

	/**
	 * name validator
	 *
	 * @param $value
	 * @param $params
	 * 	empty		1 	allow empty value
	 * 	maxlength	2	maximum string length
	 *  error		2	replace whole error message on error case
	 * @return boolean
	 */
	public function V_path($value, $params = NULL) {
		$path = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $path === ''){
			$this->filtered = '';
			return !$this->setError(false);
		}
		if (isset($params['maxlength']) && strlen($path) >= $params['maxlength']){
			$msg = "The path is too long (Maximum length: {$params['maxlength']})";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg);
		}
		$re = '/^((\w)+((\.|-)(\w)+)*)(\/(\w)+((\.|-)(\w)+)*)*$/';
		if (!preg_match($re, $path)){
			$msg = "path validation failed";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'path validation failed');
		} else {
			$this->filtered=$path;
		}
		return !$this->setError(false);
	}

	/**
	 * color validator
	 *
	 * @param $value
	 * @param $params
	 * 	empty	  1 	allow empty value
	 *  error	   2	replace whole error message on error case
	 * @return boolean
	 */
	public function V_color($value, $params = NULL) {
		$color = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $color === ''){
			$this->filtered = '';
			return !$this->setError(false);
		}
		$re = '/^([a-fA-F0-9]){6}$/';
		if (!preg_match($re, $color) || strlen($color) != 6){
			$msg = "color validation failed";
			if (isset($params['error'])) $msg = $params['error'];
			return !$this->setError(true, 200, $msg, 'color validation failed');
		} else {
			$this->filtered=$color;
		}
		return !$this->setError(false);
	}

	/**
	 * filename validator
	 *
	 * $param
	 *  error	2	overwrite error message
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_filename($value, $params = NULL) {
		$re = '/[^a-zA-Z0-9\-_(). äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]/';
		$fname = trim(preg_replace($re, '' ,strip_tags(''.$value)));
		$fname = str_replace('..', '.', $fname);
		$fname = str_replace('..', '.', $fname);
		if (( strlen($fname) >= 255) || ( $fname === '' )){
			$msg = (isset($params['error']))? $params['error'] : 'filename validation failed';
			return !$this->setError(true, 200, $msg, 'filename validation failed');
		} else {
			$this->filtered=$fname;
		}
		return !$this->setError(false);
	}

	/**
	 * time validator
	 *
	 * $param
	 *  empty		1 	allow empty value
	 *  format		2	datetime-format
	 *  error		2	overwrite error message
	 *  parse		2	parse date to format after validation
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_time($value, $params = NULL) {
		$time = trim(strip_tags(''.$value));
		$fmt = (isset($params['format']))? $params['format'] : 'H:i';
		if (in_array('empty', $params, true) && ($time === '0' || $time === 'false' || $time === ''||$time === false || $time == 0)){
			$this->filtered = false;
			return !$this->setError(false);
		} elseif (!in_array('empty', $params, true) && ($time === '0' || $time === 'false' || $time === ''||$time === false || $time == 0)){
			$msg = (isset($params['error']))? $params['error'] : 'time validation failed, format: "'.$fmt.'"';
			return !$this->setError(true, 200, $msg, 'time validation failed, format: "'.$fmt.'"');
		} else {
			$d = \DateTime::createFromFormat($fmt, $time);
			if($d && $d->format($fmt) == $time){
				$this->filtered = $d->format((isset($params['parse']))?$params['parse']:$fmt);
				return !$this->setError(false);
			} else {
				$msg = (isset($params['error']))? $params['error'] : 'time validation failed, format: "'.$fmt.'"';
				return !$this->setError(true, 200, $msg, 'time validation failed, format: "'.$fmt.'"');
			}
		}
	}

	/**
	 * array validator
	 * test if element is array
	 *
	 *
	 * $param
	 *  key			2	validate array key to -> validatorelelemnt, requires param->validator to be set
	 *  minlength	2	minimum string length
	 *  maxlength	2	maximum string length
	 *  empty		1	allow empty array
	 *  false		1	allow false -> reset to empty array
	 *  validator	2	run this validator on each array element
	 *  error		2	overwrite error message
	 *  pre_json_decode 1 ron json decode on string -> workaround for limited php setting 'max_input_vars'
	 *
	 * @param array $a
	 * @param array $params
	 * @return boolean
	 */
	public function V_array($a, $params){
		if (in_array('pre_json_decode', $params, true)){
			$a = json_decode( $a, true);
		}
		if (!is_array($a)){
			if ($a === '0' && in_array('false', $params, true)){
				$a = [];
			} else {
				$msg = (isset($params['error']))? $params['error'] : 'Value is no array';
				return !$this->setError(true, 200, $msg, 'array validator failed');
			}
		}
		if ((!in_array('empty', $params, true) || count($a) > 0) && isset($params['minlength']) && count($a) < $params['minlength']){
			$msg = (isset($params['error']))? $params['error'] : 'Array to short: require minimal length of "'.$params['minlength'].'" elements';
			return !$this->setError(true, 200, $msg, 'array validator failed: array to short');
		}
		if (isset($params['maxlength']) && count($a) > $params['maxlength']){
			$msg = (isset($params['error']))? $params['error'] : 'Array to long: maximal array length "'.$params['maxlength'].'"';
			return !$this->setError(true, 200, $msg, 'array validator failed: array to long');
		}
		if (!in_array('empty', $params, true) && count($a) == 0){
			$msg = (isset($params['error']))? $params['error'] : 'Array to short: empty array is not permitted.';
			return !$this->setError(true, 200, $msg, 'array validator failed: array is empty');
		}
		if (!isset($params['validator'])){
			$this->filtered=$a;
			return !$this->setError(false);
		}
		$out = [];
		$tmp_last_mapkey = $this->lastMapKey;
		$tmp_last_key = '';
		foreach($a as $key => $entry){
			$tmp_last_key = $key;
			$this->lastMapKey = '';
			//key
			$keyFiltered = NULL;
			if (isset($params['key'])){
				$this->validate($key, $params['key']);
				if ($this->isError) break;
				$keyFiltered = $this->filtered;
			}
			//value
			$this->validate($entry, $params['validator']);
			if ($this->isError) break;
			if ($keyFiltered === NULL){
				$out[] = $this->filtered;
			} else {
				$out[$keyFiltered] = $this->filtered;
			}
		}
		if ($this->isError) {
			$curr = $this->_capsule_lastMapKey();
			$this->lastMapKey = "{$tmp_last_mapkey}[{$tmp_last_key}]{$curr}";
		}
		$this->filtered = $out;
		return !$this->isError;
	}

	/**
	 * arraymap validator
	 * run validator on array and given map
	 *
	 * $param
	 *  map 		2 validation map
	 *  required 	2 boolean, default false
	 *  pre_json_decode 1 ron json decode on string -> workaround for limited php setting 'max_input_vars'
	 *
	 * @param array $a
	 * @param array $params
	 * @return boolean
	 */
	public function V_arraymap($a, $params){
		if (in_array('pre_json_decode', $params, true)){
			$a = json_decode( $a, true);
		}
		if (!isset($params['map'])){
			return !$this->setError(true, 200, 'invalid configuration on arraymap validation', 'arraymap validator failed: wrong configuration: missing parameter map');
		}
		$tmp_last_mapkey = $this->lastMapKey;
		$this->validateMap($a, $params['map'], (!isset($params['map'])? 'required': $params['required'] ) );
		if ($this->isError){
			$curr = $this->_capsule_lastMapKey();
			$this->lastMapKey = "{$tmp_last_mapkey}{$curr}";
		}
		return !$this->isError;
	}

	/**
	 * date validator
	 *
	 * $param
	 *  format		2	datetime-format
	 *  error		2	overwrite error message
	 *  parse		2	parse date to format after validation
	 *  empty		1	allow empty array
	 *
	 * @param $value
	 * @param $params
	 * @return boolean
	 */
	public function V_date($value, $params = NULL) {
		$date = trim(strip_tags(''.$value));
		if (in_array('empty', $params, true) && $date === ''){
			$this->filtered = $date;
			return !$this->setError(false);
		}
		$fmt = (isset($params['format']))? $params['format'] : 'Y-m-d';
		$d = \DateTime::createFromFormat($fmt, $date);
		if($d && $d->format($fmt) == $date){
			$this->filtered = $d->format((isset($params['parse']))?$params['parse']:$fmt);
		} else {
			$msg = (isset($params['error']))? $params['error'] : 'date validation failed, format: "'.$fmt.'"';
			return !$this->setError(true, 200, $msg, 'date validation failed, format: "'.$fmt.'"');
		}
		return !$this->setError(false);
	}

	/**
	 * array validator
	 * test if string is valid iban
	 *
	 *
	 * $param
	 *  empty		1	allow empty array
	 *  error		2	overwrite error message
	 *
	 * @param string $value
	 * @param array $params
	 * @return boolean
	 */
	public function V_iban($value, $params = []){
		$iban = trim(strip_tags(''.$value));
		$iban = strtoupper($iban); // to upper
		$iban = preg_replace('/(\s|\n|\r)/', '', $iban); //remove white spaces
		//empty
		if (in_array('empty', $params, true) && $iban === ''){
			$this->filtered = $iban;
			return !$this->setError(false);
		}
		//check iban
		if (!self::_checkIBAN($iban)){
			$msg = (isset($params['error']))? $params['error'] : 'iban validation failed';
			return !$this->setError(true, 200, $msg, 'iban validation failed');
		} else {
			$this->filtered=$iban;
		}
		return !$this->setError(false);
	}

	/**
	 * check if string is valid iban,
	 *
	 * @param string $iban iban srstring to check
	 *
	 * @return bool
	 * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number#Validating_the_IBAN
	 */
	public static function _checkIBAN($iban){
		if ($iban == '') return false;
		$iban = strtoupper(str_replace(' ', '', $iban));
		$countries = array('AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22, 'BE' => 16, 'BA' => 20, 'BR' => 29, 'BG' => 22, 'CR' => 21, 'HR' => 21, 'CY' => 28, 'CZ' => 24, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'FO' => 18, 'FI' => 18, 'FR' => 27, 'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27, 'GL' => 18, 'GT' => 28, 'HU' => 28, 'IS' => 26, 'IE' => 22, 'IL' => 23, 'IT' => 27, 'JO' => 30, 'KZ' => 20, 'KW' => 30, 'LV' => 21, 'LB' => 28, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'MK' => 19, 'MT' => 31, 'MR' => 27, 'MU' => 30, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'NL' => 18, 'NO' => 15, 'PK' => 24, 'PS' => 29, 'PL' => 28, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'SM' => 27, 'SA' => 24, 'RS' => 22, 'SK' => 24, 'SI' => 19, 'ES' => 24, 'SE' => 24, 'CH' => 21, 'TN' => 24, 'TR' => 26, 'AE' => 23, 'GB' => 22, 'VG' => 24);

		//1. check country code exists + iban has valid length
		if( !array_key_exists(substr($iban,0,2), $countries)
			|| strlen($iban) != $countries[substr($iban,0,2)]){
			return false;
		}

		//2. Rearrange countrycode and checksum
		$rearranged = substr($iban, 4) . substr($iban, 0, 4);

		//3. convert to integer
		$iban_letters = str_split($rearranged);
		$iban_int_only = '';
		foreach ($iban_letters as $char){
			if (is_numeric($char)) $iban_int_only .= $char;
			else {
				$ord = ord($char) - 55; // ascii representation - 55, so a => 10, b => 11, ...
				if ($ord >= 10 && $ord <= 35){
					$iban_int_only .= $ord;
				} else {
					return false;
				}
			}
		}

		//4. calculate mod 97 -> have to be 1
		if (self::_bcmod($iban_int_only, '97') === 1){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * bcmod - get modulus (substitute for bcmod)
	 * be careful with big $modulus values
	 *
	 * @param string $left_operand <p>The left operand, as a string.</p>
	 * @param int $modulus <p>The modulus, as a string. </p>
	 *
	 * based on
	 * @see https://stackoverflow.com/questions/10626277/function-bcmod-is-not-available
	 * by Andrius Baranauskas and Laurynas Butkus :) Vilnius, Lithuania
	 *
	 * @return integer
	 **/
	public static function _bcmod($left_operand, $modulus){
		if (function_exists('bcmod')){
			return (int)bcmod($left_operand, $modulus);
		} else {
			$take = 5; // how many numbers to take at once?
			$mod = '';
			do
			{
				$a = (int)$mod.substr( $left_operand, 0, $take );
				$left_operand = substr( $left_operand, $take );
				$mod = $a % $modulus;
			}
			while ( strlen($left_operand) );

			return (int)$mod;
		}
	}

	/**
	 * capsule function for array and arraymap validator
	 * adds [ and ] to $this->lastMapKey and return this string
	 * used on error messages in mentioned functions
	 **/
	private function _capsule_lastMapKey(){
		$capsuled = $this->lastMapKey;
		if ($capsuled != ''){
			if (substr($capsuled, 0, 1) != '[' && substr($capsuled, -1) != ']'){
				$capsuled = "[$capsuled]";
			} elseif (substr($capsuled, 0, 1) != '[' && substr($capsuled, -1) == ']'){
				$pos = strpos($capsuled, '[');
				$capsuled = "[".substr($capsuled, 0, $pos).']'.substr($capsuled, $pos);
			}
		}
		return $capsuled;
	}
}
