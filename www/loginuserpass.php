<?php

/**
 * This page shows a username/password login form, and passes information from it
 * to the sspmod_sgis_Auth_UserPassBaseCookie class, which is a generic class for
 * username/password authentication with storing login as cookie.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

// Retrieve the authentication state
if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_sgis_Auth_UserPassBaseCookie::STAGEID);

$source = SimpleSAML_Auth_Source::getById($state[sspmod_sgis_Auth_UserPassBaseCookie::AUTHID]);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $state[sspmod_sgis_Auth_UserPassBaseCookie::AUTHID]);
}


if (array_key_exists('username', $_REQUEST)) {
	$username = $_REQUEST['username'];
} elseif ($source->getRememberUsernameEnabled() && array_key_exists($source->getAuthId() . '-username', $_COOKIE)) {
	$username = $_COOKIE[$source->getAuthId() . '-username'];
} elseif (isset($state['core:username'])) {
	$username = (string)$state['core:username'];
} else {
	$username = '';
}

if (array_key_exists('password', $_REQUEST)) {
	$password = $_REQUEST['password'];
} else {
	$password = '';
}

$errorCode = NULL;
$errorParams = NULL;

if (!empty($_REQUEST['username']) || !empty($password)) {
	// Either username or password set - attempt to log in

	if (array_key_exists('forcedUsername', $state)) {
		$username = $state['forcedUsername'];
	}

	if ($source->getRememberUsernameEnabled()) {
		$sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();
		$params = $sessionHandler->getCookieParams();
		$params['expire'] = time();
		$params['expire'] += (isset($_REQUEST['remember_username']) && $_REQUEST['remember_username'] == 'Yes' ? 31536000 : -300);
        \SimpleSAML\Utils\HTTP::setCookie($source->getAuthId() . '-username', $username, $params, FALSE);
	}

    if ($source->isRememberMeEnabled()) {
        if (array_key_exists('remember_me', $_REQUEST) && $_REQUEST['remember_me'] === 'Yes') {
            $state['RememberMe'] = TRUE;
            $authStateId = SimpleSAML_Auth_State::saveState($state, sspmod_sgis_Auth_UserPassBaseCookie::STAGEID);
        }
    }

    if ($source->getRememberPasswordEnabled()) {
        $sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();
        $params = $sessionHandler->getCookieParams();
        $params['expire'] = time();
        $params['expire'] += (isset($_REQUEST['remember_password']) && $_REQUEST['remember_password'] == 'Yes' ? 31536000 : -300);
        $passwordCookie = sspmod_sgis_Auth_UserPassBaseCookie::encryptCookie($authStateId, $username, $password);
        \SimpleSAML\Utils\HTTP::setCookie($source->getAuthId() . '-password', $passwordCookie, $params, FALSE);
    }

	try {
		sspmod_sgis_Auth_UserPassBaseCookie::handleLogin($authStateId, $username, $password);
	} catch (SimpleSAML_Error_Error $e) {
		/* Login failed. Extract error code and parameters, to display the error. */
		$errorCode = $e->getErrorCode();
		$errorParams = $e->getParameters();
	}
} else {
	if ($source->getRememberPasswordEnabled()) {
		$credentials = false;
		if (isset($_COOKIE[$source->getAuthId() . '-password'])) {
			$credentials = sspmod_sgis_Auth_UserPassBaseCookie::decryptCookie($authStateId, $_COOKIE[$source->getAuthId() . '-password']);
		}
		if ($credentials !== false) {
	    try {
			  sspmod_sgis_Auth_UserPassBaseCookie::handleLogin($authStateId, $credentials["username"], $credentials["password"]);
	    } catch (SimpleSAML_Error_Error $e) {
				/* Login failed. Extract error code and parameters, to display the error. */
				$errorCode = $e->getErrorCode();
				$errorParams = $e->getParameters();
			}
		}
	}
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'sgis:loginuserpass.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
if (array_key_exists('forcedUsername', $state)) {
	$t->data['username'] = $state['forcedUsername'];
	$t->data['forceUsername'] = TRUE;
	$t->data['rememberUsernameEnabled'] = FALSE;
	$t->data['rememberUsernameChecked'] = FALSE;
    $t->data['rememberMeEnabled'] = $source->isRememberMeEnabled();
    $t->data['rememberMeChecked'] = $source->isRememberMeChecked();
} else {
	$t->data['username'] = $username;
	$t->data['forceUsername'] = FALSE;
	$t->data['rememberUsernameEnabled'] = $source->getRememberUsernameEnabled();
	$t->data['rememberUsernameChecked'] = $source->getRememberUsernameChecked();
    $t->data['rememberMeEnabled'] = $source->isRememberMeEnabled();
    $t->data['rememberMeChecked'] = $source->isRememberMeChecked();
	if (isset($_COOKIE[$source->getAuthId() . '-username'])) $t->data['rememberUsernameChecked'] = TRUE;
}
$t->data['rememberPasswordEnabled'] = $source->getRememberUsernameEnabled();
$t->data['rememberPasswordChecked'] = $source->getRememberUsernameChecked();
$t->data['links'] = $source->getLoginLinks();
$t->data['errorcode'] = $errorCode;
$t->data['errorparams'] = $errorParams;

if (isset($state['SPMetadata'])) {
	$t->data['SPMetadata'] = $state['SPMetadata'];
} else {
	$t->data['SPMetadata'] = NULL;
}

$t->show();
exit();

