<?php

global $attributes, $logoutUrl, $AUTHGROUP, $ADMINGROUP, $nonce;

require_once "../lib/inc.all.php";

$mail = getUserMail();
if (isset($_REQUEST["mail"]) && ($mail != $_REQUEST["mail"])) {
  requireGroup($ADMINGROUP);
  $mail = $_REQUEST["mail"];
} else {
  requireGroup($AUTHGROUP);
}
$person= getPersonDetailsByMail($mail);
$gremien = getPersonRolle($person["id"]);
$gruppen = getPersonGruppe($person["id"]);
$mailinglisten = getPersonMailingliste($person["id"]);

if (isset($_POST["action"]) && ($_POST["action"] == "pwchange")) {
  if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
    echo "<b class=\"msg\">Formular nicht frisch - CSRF Schutz.</b><br>\n";
  } else {
    if (empty($person["username"]) && isset($_POST["username"]) && ($_POST["username"] !== $person["username"])) {
      setPersonUsername($person["id"], $_POST["username"]);
    }
    if (isset($_POST["password"]) && ($_POST["password"] == $_POST["password2"])) {
      setPersonPassword($person["id"], $_POST["password"]);
    }
    header("Location: ".$_SERVER["PHP_SELF"]."?src=pwchange");
    exit;
  }
}

require "../template/selbstauskunft.tpl";
