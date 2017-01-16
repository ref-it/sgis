<?php

# wird benutzt, um zu gegebenen Nutzernamen die Gruppen abzufragen sowie die Information, ob er sich noch einloggen darf

require_once "../lib/inc.all.php";
global $rpcKey2;

if (!isset($_POST["login"])) die ("missing login");

function getData() {
  global $rpcKey2;
  $ret = Array();


  $login = decrypt((string)$_POST["login"], $rpcKey2);
  if ($login === false) {
    $ret["status"] = "err";
    $ret["msg"] = "bad login string";
    return $ret;
  }
  $login = json_decode($login, true);
  $ret["nonce"] = $login["nonce"];
  $ret["replies"] = Array();
  foreach ($login["username"] as $username) {
    $ret["replies"][$username] = getSinglePersonData($username);
  }
  $ret["status"] = "ok";
  return $ret;
}

function getSinglePersonData($username) {
  $ret = Array();
  $person = getPersonDetailsByUsername($username);
  if ($person === false) {
    $ret["status"] = "badlogin";
    $ret["msg"]    = "user does not exist";
    $ret["grps"]   = Array();
    return $ret;
  }
  $emails = explode(",", $person["email"]);
  $person["email"] = $emails[0];
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
  if (!$canLogin) {
    $ret["status"] = "badlogin";
    $ret["msg"]    = "user not permitted to login";
    return $ret;
  }
  $ret["status"] = "oklogin";
  $ret["msg"]    = "Login permitted";
  return $ret;
}

$ret = getData();

header("Content-Type: application/octet-stream");
echo encrypt(json_encode($ret), $rpcKey2);

exit;

