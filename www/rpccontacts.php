<?php

# wird benutzt, um die Konktaktdaten für die Fritz!Box abzufragen

require_once "../lib/inc.all.php";
global $rpcKey2;

if (!isset($_POST["login"])) die ("missing login");

function getData() {
  global $rpcKey3;
  $ret = Array();


  $login = decrypt((string)$_POST["login"], $rpcKey3);
  if ($login === false) {
    $ret["status"] = "err";
    $ret["msg"] = "bad login string";
    return $ret;
  }
  $login = json_decode($login, true);
  $ret["nonce"] = $login["nonce"];
  $pp = getAllePersonCurrent();
  $contactPersonen = [];
  foreach ($pp as $p) {
    if (!$p["canLoginCurrent"]) continue;
    $p["_contact"] = getPersonContactDetails($p["id"]);
    $contactPersonen[] = $p;
  }
  $ret["persons"] = $contactPersonen;
  $ret["status"] = "ok";
  return $ret;
}

$ret = getData();

header("Content-Type: application/octet-stream");
echo encrypt(json_encode($ret), $rpcKey3);

exit;

