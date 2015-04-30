<?php

/**
 * This page shows a list of authentication sources. When the user selects
 * one of them if pass this information to the
 * sspmod_multiauth_Auth_Source_MultiAuth class and call the
 * delegateAuthentication method on it.
 *
 * @author Lorenzo Gil, Yaco Sistemas S.L.
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];

/* Retrieve the authentication state. */
$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_sgis_Auth_Source_MultiAuth::STAGEID);

if (array_key_exists("SimpleSAML_Auth_Default.id", $state)) {
	$authId = $state["SimpleSAML_Auth_Default.id"];
	$as = SimpleSAML_Auth_Source::getById($authId);
} else {
	$as = NULL;
}

$source = NULL;
$isSourceFromReq = false;
if (array_key_exists('source', $_REQUEST)) {
	$source = $_REQUEST['source'];
} else {
	foreach ($_REQUEST as $k => $v) {
		$k = explode('-', $k, 2);
		if (count($k) === 2 && $k[0] === 'src') {
			$source = base64_decode($k[1]);
      $isSourceFromReq = true;
		}
	}
}
if ($as->getRememberSourceEnabled() && $source === NULL && array_key_exists($as->getAuthId() . '-source', $_COOKIE)) {
	$source = $_COOKIE[$as->getAuthId() . '-source'];
}
if ($source !== NULL && $as->getRememberSourceEnabled() && $isSourceFromReq) {
	$sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();
	$params = $sessionHandler->getCookieParams();
	$params['expire'] = time();
	$params['expire'] += (isset($_REQUEST['remember_source']) && $_REQUEST['remember_source'] == 'Yes' ? 31536000 : -300);
	setcookie($as->getAuthId() . '-source', $source, $params['expire'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
if (count($state[sspmod_sgis_Auth_Source_MultiAuth::SOURCESID]) == 1) {
  $source = $state[sspmod_sgis_Auth_Source_MultiAuth::SOURCESID][0]['source'];
}

if ($source !== NULL) {
	if ($as !== NULL) {
		$as->setPreviousSource($source);
	}
	sspmod_sgis_Auth_Source_MultiAuth::delegateAuthentication($source, $state);
}

if (array_key_exists('multiauth:preselect', $state)) {
	$source = $state['multiauth:preselect'];
	sspmod_sgis_Auth_Source_MultiAuth::delegateAuthentication($source, $state);
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'sgis:selectsource.php');
$t->data['authstate'] = $authStateId;
$t->data['sources'] = $state[sspmod_sgis_Auth_Source_MultiAuth::SOURCESID];
if ($as !== NULL) {
	$t->data['preferred'] = $as->getPreviousSource();
} else {
	$t->data['preferred'] = NULL;
}
$t->data['rememberSourceEnabled'] = $as->getRememberSourceEnabled();
$t->data['rememberSourceChecked'] = $as->getRememberSourceChecked();
$t->show();
exit();

?>
