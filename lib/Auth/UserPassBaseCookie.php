<?php

/**
 * Helper class for username/password authentication.
 *
 * This helper class allows for implementations of username/password authentication by
 * implementing a single function: login($username, $password)
 *
 * SGIS: Password can be saved as cookie to skip auth.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class sspmod_sgis_Auth_UserPassBaseCookie extends sspmod_core_Auth_UserPassBase {

	/**
	 * Storage for authsource config option remember.password.enabled
	 * loginuserpass.php and loginuserpassorg.php pages/templates use this option to
	 * present users with a checkbox to save their password for the next login request.
	 * This contains the key to encrypt the password.
	 * @var string
	 */
	protected $rememberPasswordEnabled = FALSE;

	/**
	 * Storage for authsource config option remember.password.checked
	 * loginuserpass.php and loginuserpassorg.php pages/templates use this option
	 * to default the remember password checkbox to checked or not.
	 * @var bool
	 */
	protected $rememberPasswordChecked = FALSE;


	/**
	 * Constructor for this authentication source.
	 *
	 * All subclasses who implement their own constructor must call this constructor before
	 * using $config for anything.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array &$config  Configuration for this authentication source.
	 */
	public function __construct($info, &$config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		// Get the remember password config options
		if (isset($config['remember.password.enabled'])) {
			$this->rememberPasswordEnabled = (string) $config['remember.password.enabled'];
			unset($config['remember.password.enabled']);
		}
		if (isset($config['remember.password.checked'])) {
			$this->rememberPasswordChecked = (bool) $config['remember.password.checked'];
			unset($config['remember.password.checked']);
		}
	}


	/**
	 * Getter for the authsource config option remember.password.enabled
	 * @return string
	 */
	public function getRememberPasswordEnabled() {
		return $this->rememberPasswordEnabled;
	}

	/**
	 * Getter for the authsource config option remember.password.checked
	 * @return bool
	 */
	public function getRememberPasswordChecked() {
		return $this->rememberPasswordChecked;
	}

	/**
	 * Initialize login.
	 *
	 * This function saves the information about the login, and redirects to a
	 * login page.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/*
		 * Save the identifier of this authentication source, so that we can
		 * retrieve it later. This allows us to call the login()-function on
		 * the current object.
		 */
		$state[self::AUTHID] = $this->authId;

// disable this as forcedUsername is private and has no getter
//		/* What username we should force, if any. */
//		if ($this->forcedUsername !== NULL) {
//			/*
//			 * This is accessed by the login form, to determine if the user
//			 * is allowed to change the username.
//			 */
//			$state['forcedUsername'] = $this->forcedUsername;
//		}

		/* Save the $state-array, so that we can restore it after a redirect. */
		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

		/*
		 * Redirect to the login form. We include the identifier of the saved
		 * state array as a parameter to the login form.
		 */
		$url = SimpleSAML_Module::getModuleURL('sgis/loginuserpass.php');
		$params = array('AuthState' => $id);
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $params);

		/* The previous function never returns, so this code is never executed. */
		assert('FALSE');
	}

	public function logout(&$state) {
		if ($this->getRememberPasswordEnabled()) {
	                $sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();
        	        $params = $sessionHandler->getCookieParams();
                	$params['expire'] = time();
                	$params['expire'] += -300;
                	setcookie($this->getAuthId() . '-password', "", $params['expire'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
	}

	public static function encryptCookie($authStateId, $username, $password) {
		assert('is_string($authStateId)');
		assert('is_string($username)');
		assert('is_string($password)');

		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

		assert('array_key_exists(self::AUTHID, $state)');
		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

		$value = base64_encode($username).":".base64_encode($password).":".time();

		return self::encrypt($value, $source->rememberPasswordEnabled);
	}

	public static function decryptCookie($authStateId, $value) {
		assert('is_string($authStateId)');
		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);
		assert('array_key_exists(self::AUTHID, $state)');
		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}
		$value = self::decrypt($value, $source->rememberPasswordEnabled);
		if ($value === false) { return false; }
		$values = explode(":", $value, 3);
		if (time() > $values[2] + 90 * 24 * 60 * 60) { return false; }
		return Array("username" => base64_decode($values[0]), "password" => base64_decode($values[1]));
	}

	private static function encrypt( $msg, $k, $base64 = true ) {

		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;

		$msg = serialize($msg);						 # serialize
		$iv  = mcrypt_create_iv(32, MCRYPT_RAND);	       # create iv

		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
			return false;

		$msg  = mcrypt_generic($td, $msg);			      # encrypt
		$msg  = $iv . $msg;							     # prepend iv
		$mac  = self::pbkdf2($msg, $k, 1000, 32);	     # create mac
		$msg .= $mac;								   # append mac

		mcrypt_generic_deinit($td);					     # clear buffers
		mcrypt_module_close($td);					       # close cipher module

		if ( $base64 ) $msg = base64_encode($msg);	      # base64 encode?

		return $msg;								    # return iv+ciphertext+mac
	}

	private static function decrypt( $msg, $k, $base64 = true ) {
		if ( $base64 ) $msg = base64_decode($msg);		      # base64 decode?

		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;

		$iv  = substr($msg, 0, 32);						     # extract iv
		$mo  = strlen($msg) - 32;						       # mac offset
		$em  = substr($msg, $mo);						       # extract mac
		$msg = substr($msg, 32, strlen($msg)-64);		       # extract ciphertext
		$mac = self::pbkdf2($iv . $msg, $k, 1000, 32);		# create mac

		if ( $em !== $mac )								     # authenticate mac
			return false;

		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )	  # initialize buffers
			return false;

		$msg = mdecrypt_generic($td, $msg);				     # decrypt
		$msg = unserialize($msg);						       # unserialize

		mcrypt_generic_deinit($td);						     # clear buffers
		mcrypt_module_close($td);						       # close cipher module
		return $msg;									    # return original msg
	}

	private static function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {

		$hl = strlen(hash($a, null, true));     # Hash length
		$kb = ceil($kl / $hl);			  # Key blocks to compute
		$dk = '';						       # Derived key

		# Create key
		for ( $block = 1; $block <= $kb; $block ++ ) {

			# Initial hash for this block
			$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

			# Perform block iterations
			for ( $i = 1; $i < $c; $i ++ )

				# XOR each iterate
				$ib ^= ($b = hash_hmac($a, $b, $p, true));

			$dk .= $ib; # Append iterated block
		}

		# Return derived key of correct length
		return substr($dk, 0, $kl);
	}

}

?>
