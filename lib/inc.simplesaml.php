<?php

global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $attributes, $logoutUrl;

function getUserMail() {
  global $attributes;
  requireAuth();
  return $attributes["mail"][0];
}

function requireAuth() {
  global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
  global $attributes, $logoutUrl;
  global $ADMINGROUP, $AUTHGROUP;

  require_once($SIMPLESAML.'/lib/_autoload.php');
  $as = new SimpleSAML_Auth_Simple($SIMPLESAMLAUTHSOURCE);

  if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && !$as->isAuthenticated()) {
    header('HTTP/1.0 401 Unauthorized');
    die();
  }

  $as->requireAuth();

  $attributes = $as->getAttributes();
  $logoutUrl = $as->getLogoutURL();

  if (isset($attributes["user"]) && count($attributes["user"]) > 0 && $attributes["user"][0] == "admin") {
    $attributes["groups"][] = "backend-admin";
    $attributes["groups"] = array_merge($attributes["groups"], explode(",", $ADMINGROUP));
    $attributes["groups"] = array_merge($attributes["groups"], explode(",", $AUTHGROUP));
  }
}

function requireGroup($group) {
  global $attributes;

  requireAuth();

  if (!hasGroup($group)) {
    header('HTTP/1.0 401 Unauthorized');
    include SGISBASE."/template/permission-denied.tpl";
    die();
  }
}

function getUsername() {
  global $attributes;
  if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0])) 
    return $attributes["eduPersonPrincipalName"][0];
  if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
    return $attributes["mail"][0];
  if (isset($attributes["user"]) && isset($attributes["mail"][0])) 
    return $attributes["user"][0];
  return NULL;
}

function getUserFullName(){
    global $attributes;
    requireAuth();
    return $attributes["displayName"][0];
}

function hasGroup($group) {
  global $attributes;

  if (!isset($attributes["groups"])) {
    return false;
  }

  if (!is_array($attributes["groups"])) {
    return false;
  }

  if (count(array_intersect(explode(",",$group), $attributes["groups"])) == 0) {
    return false;
  }

  return true;
}

