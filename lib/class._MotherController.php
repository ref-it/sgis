<?php
/**
 * CONTROLLER Mother Controller
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

class MotherController extends JsonController {
	/**
	 * contains the database connection
	 * @var \intbf\database\DatabaseModuleModel
	 */
	protected $db;

	/**
	 * contains the AuthHandler
	 * @var \AuthHandler|\AuthBasicHandler
	 */
	protected $auth;

	/**
	 * contains the Template instance
	 * Template
	 */
	protected $t;

	/**
	 * translator
	 * \intbf\language\Translator
	 */
	protected $translator;

	/**
	 *
	 * @param \intbf\database\Database $db
	 * @param \AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		$this->db = $db;
		$this->auth = $auth;
		$this->t = $template;
		//translator ===========
		if (class_exists('\intbf\language\Translator')){
			$this->translator = true;
		}
	}

	/**
	 * return instance controller name
	 * @return string
	 */
	private function getControllerName(){
		return str_replace(['controller', 'intbf\\'], '', strtolower(get_class($this)));
	}

	/**
	 * includes Template File
	 * @param string $group
	 * @param NULL|array $param add parameter to Template
	 * @param string $action
	 */
	function includeTemplate($action, $param = NULL, $group = 'html'){
		include (SYSBASE.'/template/'.TEMPLATE.'/'.$group.'/'.$this->getControllerName().'/'.$action.'.phtml');
	}

	/**
	 * handles and show html error codes
	 * @param integer $code HTML error code
	 */
	function renderErrorPage($code){
		ErrorHandler::_render([ 'controller'=> 'error', 'action' => ''.$code]);
		if ($this->db != NULL) $this->db->close();
		die();
	}

	/**
	 * generate Post challenge
	 * @param boolean $nohtml dont return html, return array
	 * @return string|array
	 */
	public function getChallenge($nohtml = false){
		if (isset($_SESSION['INTBF']['FORM_CHALLENGE'])){
			if (!$nohtml){
			return
				'<input type="hidden" class="fchal" name="'.$_SESSION['INTBF']['FORM_CHALLENGE']['name'].'" value="'.$_SESSION['INTBF']['FORM_CHALLENGE']['value'].'">'.
				'<input type="hidden" class="fchal2" name="'.$_SESSION['INTBF']['FORM_NONONCE']['name'].'" value="'.$_SESSION['INTBF']['FORM_NONONCE']['value'].'">'; // dont commit this value to server
			} else{
				return [
					'0' => $_SESSION['INTBF']['FORM_CHALLENGE'],
					//anti challenge
					'1' => $_SESSION['INTBF']['FORM_NONONCE'],
				];
			}
		}
		return NULL;
	}

	/**
	 * translate text
	 * @param string $in
	 * @return string
	 */
	public function translate($in){
		return ($this->translator)? (language\Translator::translate($in)) : $in;
	}

    /**
     * handle validate result and break on error
     * used to answer invalid post requests
     * @param Validator $vali
     */
    public function breakOnValidateError($vali){
        if ($vali->getIsError()){
            if($vali->getLastErrorCode() == 403){
                http_response_code ($vali->getLastErrorCode());
                $this->json_result = array('success' => false, 'msg' => $vali->getLastErrorMsg() .' - '. $vali->getLastErrorDescription());
                $this->print_json_result();
            } else if($vali->getLastErrorCode() == 404){
                http_response_code ($vali->getLastErrorCode());
                $this->json_result = array('success' => false, 'msg' => $vali->getLastErrorMsg() .' - '. $vali->getLastErrorDescription());
                $this->print_json_result();
            } else {
                http_response_code ($vali->getLastErrorCode());
                $this->json_result = array('success' => false, 'msg' => $vali->getLastErrorMsg().' - '. $vali->getLastErrorDescription());
                $this->print_json_result();
            }
        }
    }
}
