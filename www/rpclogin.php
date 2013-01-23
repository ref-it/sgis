<?php

require_once "../lib/inc.all.php";
global $rpcKey;

if (!isset($_POST["login"])) die ("missing login");

function handleLogin() {
	global $rpcKey, $pwObj;
	$ret = Array();

	$login = decrypt((string)$_POST["login"], $rpcKey);
	if ($login === false) {
		$ret["status"] = "err";
		$ret["msg"] = "bad login string";
		return $ret;
	}
	$login = json_decode($login, true);
	$ret["nonce"] = $login["nonce"];
	$person = getPersonDetailsByUsername($login["username"]);
	if ($person === false) {
		$ret["status"] = "badlogin";
		$ret["msg"]    = "user does not exist";
		$ret["grps"]   = Array();
		return $ret;
	}
	$ret["person"] = $person;
	unset($ret["person"]["password"]);
	$grps = Array();
	foreach (getPersonGruppe($person["id"]) as $grp) {
		$grps[] = $grp["name"];
	}
	$ret["grps"] = $grps;
	if ($person["canLogin"]) {
		$canLogin = !in_array("cannotLogin", $grps);
	} else {
		$canLogin = in_array("canLogin", $grps);
	}
	$ret["canLogin"] = $canLogin;
    	if (!@$pwObj->verifyPasswordHash($login["password"], $person["password"])) {
		$ret["status"] = "badlogin";
		$ret["msg"]    = "wrong password";
		return $ret;
	}
	if (!$canLogin) {
		$ret["status"] = "badlogin";
		$ret["msg"]    = "user not permitted to login";
		return $ret;
	}
	$ret["status"] = "oklogin";
	$ret["msg"]    = "Login successfull";
	return $ret;
}

$ret = handleLogin();

header("Content-Type: application/octet-stream");
echo encrypt(json_encode($ret), $rpcKey);

exit;

