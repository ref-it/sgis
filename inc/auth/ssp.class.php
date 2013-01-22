<?php
/**
 * SSP. SimpleSAMLphp authentication backend
 * auth/ssp.class.php
 *
 * @author  Jorge Hervás <jordihv@gmail.com>, Lukas Slansky <lukas.slansky@upce.cz>
 * @license GPL2 http://www.gnu.org/licenses/gpl.html
 * @version 0.2
 * @date    April 2012
 */
 
class auth_ssp extends auth_basic {
  var $users = null;
  // declaration of the auth_simple object 
  var $as;
 
  /**
   * Constructor.
   * Sets additional capabilities and config strings
   */
  function auth_ssp() {
    // we set the features of our authentication backend to TRUE, the base class defaults to FALSE the rest
    $this->cando['external'] = true;
    $this->cando['logoff']   = true;
    $this->success = true;
  }
 
  /**
   * Return user info (copy from plain.class.php)
   *
   * Returns info about the given user needs to contain
   * at least these fields:
   *
   * name string  full name of the user
   * mail string  email addres of the user
   * grps array   list of groups the user is in
   *
   * @author  Lukas Slansky <lukas.slansky@upce.cz>
   */
  function getUserData($user){

    if($this->users === null) $this->_loadUserData();
    return isset($this->users[$user]) ? $this->users[$user] : false;
  }

  /**
   * Load all user data (modified copy from plain.class.php)
   *
   * loads the user file into a datastructure
   *
   * @author  Lukas Slansky <lukas.slansky@upce.cz>
   */
  function _loadUserData(){
    global $conf;

    $this->users = array();

    if(!@file_exists($conf['ssp_usersfile'])) return;

    $lines = file($conf['ssp_usersfile']);
    foreach($lines as $line){
      $line = preg_replace('/#.*$/','',$line); //ignore comments
      $line = trim($line);
      if(empty($line)) continue;

      $row    = explode(":",$line,5);
      $groups = array_values(array_filter(explode(",",$row[3])));

      $this->users[$row[0]]['name'] = urldecode($row[1]);
      $this->users[$row[0]]['mail'] = $row[2];
      $this->users[$row[0]]['grps'] = $groups;
    }
  }
  
  /**
   * Save user data
   *
   * saves the user file into a datastructure
   *
   * @author  Lukas Slansky <lukas.slansky@upce.cz>
   */
  function _saveUserData($username, $userinfo) {
    global $conf;

    if ($this->users === null) $this->_loadUserData();
    $pattern = '/^' . $username . ':/';
    
    // Delete old line from users file
    if (!io_deleteFromFile($conf['ssp_usersfile'], $pattern, true)) {
      msg('Error saving user data (1)', -1);
      return false;
    }
    $groups = join(',',$userinfo['grps']);
    $userline = join(':',array($username, $userinfo['name'], $userinfo['mail'], $groups))."\n";
    // Save new line into users file
    if (!io_saveFile($conf['ssp_usersfile'], $userline, true)) {
      msg('Error saving user data (2)', -1);
      return false;
    }
    $this->users[$username] = $userinfo;
    return true;
  }

  /**
   * Do external authentication (SSO)
   * Params are not used
   */
  function trustExternal($user,$pass,$sticky=false){
    global $USERINFO;
    global $conf;
 
    $sticky ? $sticky = true : $sticky = false; //sanity check

    // loading of simplesamlphp library
    require_once($conf['ssp_path'] . '/lib/_autoload.php');
 
    // create auth object and use api to require authentication and get attributes
    $this->as = new SimpleSAML_Auth_Simple($conf['ssp_auth_source']);

    if (!empty($_SESSION[DOKU_COOKIE]['auth']['info'])) {
      $USERINFO['name'] = $_SESSION[DOKU_COOKIE]['auth']['info']['name'];
      $USERINFO['mail'] = $_SESSION[DOKU_COOKIE]['auth']['info']['mail'];
      $USERINFO['grps'] = $_SESSION[DOKU_COOKIE]['auth']['info']['grps'];
      $_SERVER['REMOTE_USER'] = $_SESSION[DOKU_COOKIE]['auth']['user'];
      return true;
    }
 
    // the next line should be discommented to enable guest users (not authenticated) enter DokuWiki, see also documentation
    if (($_REQUEST["do"] == "login") || !empty($user)) {

	    $this->as->requireAuth();
	    $attrs = $this->as->getAttributes();
	 
	    // check for valid attributes (not empty) and update USERINFO var from dokuwiki
	    if (!isset($attrs[$conf['ssp_attr_name']][0])) {
	      $this->exitMissingAttribute('Name');
            }
	    $USERINFO['name'] = $attrs[$conf['ssp_attr_name']][0];
	 
	    if (!isset($attrs[$conf['ssp_attr_mail']][0])) {
	      $this->exitMissingAttribute('Mail');
	    }
	    $USERINFO['mail'] = $attrs[$conf['ssp_attr_mail']][0];
	 
	    // groups may be empty (by default any user belongs to the user group) don't perform empty check
	    $USERINFO['grps'] = array_map('strtolower', $attrs[$conf['ssp_attr_grps']]);
	 
	    if (!isset($attrs[$conf['ssp_attr_user']][0])) {
	      $this->exitMissingAttribute('User');
	    }
	 
	    // save user info
	    if (!$this->_saveUserData($attrs[$conf['ssp_attr_user']][0], $USERINFO)) {
	      return false;
	    }
	 
	    // assign user id to the user global information
	    $_SERVER['REMOTE_USER'] = $attrs[$conf['ssp_attr_user']][0];
	 
	    // assign user id and the data from USERINFO to the DokuWiki session cookie
	    $_SESSION[DOKU_COOKIE]['auth']['user'] = $attrs[$conf['ssp_attr_user']][0];
	    $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;

            return true;

    } // end if_isAuthenticated()

    return false;
  }
 
  /**
   * exit printing info and logout link
   *
   */
  function exitMissingAttribute( $attribute ){
    // get logout link
    $url = $this->as->getLogoutURL();
    $logoutlink = '<a href="' . htmlspecialchars($url) . '">logout</a>';
    die( $attribute . ' attribute missing from IdP. Please ' . $logoutlink . ' to return to login form');
  }
 
  /**
   * Log off the current user from DokuWiki and IdP
   *
   */
  function logOff(){
    // use the simpleSAMLphp authentication object created in trustExternal to logout
    if ($this->as->isAuthenticated())
      $this->as->logout('/');
  }
 
}
 
//Setup VIM: ex: et ts=2 enc=utf-8 :
