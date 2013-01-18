<?php
class sspmod_sturaauth_Auth_Source_StuRaAuth extends sspmod_core_Auth_UserPassBase {
	public function __construct($info, $config) {
		parent::__construct($info, $config);
		if (!is_string($config['source'])) {
			throw new Exception('Missing or invalid source option in config.');
		}
		$this->source = $config["source"];
		$this->_loadUserData();
	}

	/**
	 * Password hashing method 'mwmd5'
	 *
	 * Uses salted MD5 hashs. Salt is between 1 and 8 bytes long.
	 * This is used by Mediawiki while $wgPasswordSalt is true.
	 * This method uses a different representation, because :$Mode:$Salt:$Hash
	 * does interfere with the internal representation of columns, so it uses
	 * mwB$Salt.$Hash, the old Mode=A is not supported as it is plain md5
	 * without salt
	 *
	 * @link http://www.mediawiki.org/wiki/Manual:User_table
	 * @author Danny Götte <danny.goette@fem.tu-ilmenau.de>
	 * @param string $clear - the clear text to hash
	 * @param string $hash  - the hash to use for salt extraction, null for random
	 * @returns string - hashed password
	 */
	function hash_mwmd5($clear, $hash=null) {
		preg_match('/^mwB(.+)\./',$hash,$m);
		$salt = null;
		if (isset($m[1])) {
			$salt = $m[1];
		}

		return 'mwB'.$salt.'.'.md5($salt.'-'.md5($clear));
	}
	
	function _loadUserData(){
	  $this->users = array();

	  if(!@file_exists($this->source)) return;

	  $lines = file($this->source);
	  foreach($lines as $line){
		$line = preg_replace('/#.*$/','',$line); //ignore comments
		$line = trim($line);
		if(empty($line)) continue;

		$row	= explode(":",$line,5);
		$groups = array_values(array_filter(explode(",",$row[4])));

		$this->users[$row[0]]['pass'] = $row[1];
		$this->users[$row[0]]['name'] = urldecode($row[2]);
		$this->users[$row[0]]['mail'] = $row[3];
		$this->users[$row[0]]['grps'] = $groups;
	  }
	}

	protected function login($username, $password) {
		$username = strtolower($username);
		if (!isset($this->users[$username])) {
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}
		$hash = $this->hash_mwmd5($password, $this->users[$username]['pass']);
		if ($hash !== $this->users[$username]['pass']) {
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}
		return array(
			'uid' => array($username),
			'displayName' => array($this->users[$username]['name']),
			'eduPersonPrincipalName' => array($this->users[$username]['name']),
			'mail' => array($this->users[$username]['mail']),
			'groups' => $this->users[$username]['grps'],
		);
	}
}
