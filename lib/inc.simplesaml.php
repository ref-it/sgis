<?php

global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $attributes, $logoutUrl;

require_once($SIMPLESAML.'/lib/_autoload.php');
$as = new SimpleSAML_Auth_Simple($SIMPLESAMLAUTHSOURCE);
$as->requireAuth();

$attributes = $as->getAttributes();
$logoutUrl = $as->getLogoutURL();

function getUserMail() {
  global $attributes;
  return $attributes["mail"][0];
}

function requireGroup($group) {
  global $attributes, $logoutUrl;
  if (count(array_intersect(explode(",",$group), $attributes["groups"])) == 0) {
    header('HTTP/1.0 401 Unauthorized');
    include SGISBASE."/template/permission-denied.tpl";
    die();
  }
}
