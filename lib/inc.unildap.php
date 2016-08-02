<?php

require_once 'Net/LDAP2.php';

function verify_tui_mail($email, $unimail, $unildaphost, $unildapbase) {
  static $ds = false;

  $found = false;
  foreach ($unimail as $domain) {
    $found |= substr(strtolower($email),-strlen($domain)-1) == strtolower("@$domain");
  }

  if (!$found) return false;

  if ($ds === false) {
    $ds = Net_LDAP2::connect(array("host" => $unildaphost));
  }
  if (Net_LDAP2::isError($ds)) {
    die($ds->getMessage()); // this will tell you what went wrong!
    $ds = false;
    return false;
  } else {
    $filter = Net_LDAP2_Filter::create('mail', 'equals', $email);
    $info = $ds->search($unildapbase, $filter);
    if ($info->count() == 1) {
      $r = $info->shiftEntry()->getValues();
      if (is_array($r) && isset($r["mail"]) && !is_array($r["mail"]))
        $r["mail"] = [$r["mail"]];
      return $r;
    }
  }
  return false;
}

