<?php

function load_ldap2() {
  require_once 'Net/LDAP2.php';
}

function verify_tui_mail($email) {
  global $unimail, $unildaphost, $unildapbase;
  static $ds = false;

  $found = false;
  foreach ($unimail as $domain) {
    $found |= substr(strtolower($email),-strlen($domain)-1) == strtolower("@$domain");
  }

  if (!$found) return false;

  if ($ds === false) {
    load_ldap2();
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

# uni returns only 10 entries per query
function verify_tui_mail_many($emails) {
  global $unimail, $unildaphost, $unildapbase;

  load_ldap2();
  $ds = Net_LDAP2::connect(array("host" => $unildaphost));
  if (Net_LDAP2::isError($ds)) {
    die($ds->getMessage()); // this will tell you what went wrong!
    return false;
  } else {
    $filters = [];
    $rl = [];
    $emails = array_values(array_unique($emails));
    foreach ($emails as $i => $email) {
      $found = false;
      foreach ($unimail as $domain) {
        $found |= substr(strtolower($email),-strlen($domain)-1) == strtolower("@$domain");
      }
      if (!$found) continue;
      $filters[] = Net_LDAP2_Filter::create('mail', 'equals', $email);

      if (count($filters) == 10 || (count($emails) == $i + 1)) {
        $filter = (count($filters) == 1) ? $filters[0] : Net_LDAP2_Filter::combine('or',$filters);
        $info = $ds->search($unildapbase, $filter);
        while ($rr = $info->shiftEntry()) {
          $r = $rr->getValues();
          if (is_array($r) && isset($r["mail"]) && !is_array($r["mail"]))
            $r["mail"] = [$r["mail"]];
          $rl[] = $r;
        }
        $filters = [];
      }
    }
    return $rl;
  }
  return false;
}

