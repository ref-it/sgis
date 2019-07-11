<?php

function dbQuote($string , $parameter_type = NULL ) {
  global $pdo;
  if ($parameter_type === NULL)
    return $pdo->quote($string);
  else
    return $pdo->quote($string, $parameter_type);
}

function logThisAction() {
  global $pdo, $DB_PREFIX;
#$time_start = microtime(true);
  static $query = NULL;
  $pdo->beginTransaction() or httperror(print_r($query->errorInfo(),true));
  if ($query === NULL)
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log (action, responsible) VALUES (?, ?)");
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  $username = getUsername();
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  $query->execute(Array($_REQUEST["action"], $username)) or httperror(print_r($query->errorInfo(),true));
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  $logId = $pdo->lastInsertId();
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  foreach ($_REQUEST as $key => $value) {
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
    $key = "request_$key";
    logAppend($logId, $key, $value);
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  }
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  $pdo->commit() or httperror(print_r($query->errorInfo(),true));
#echo "<!-- ".basename(__FILE__).":".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms -->\n";
  return $logId;
}

function logAppend($logId, $key, $value) {
  global $pdo, $DB_PREFIX;
  static $query = NULL;
  if ($query === NULL)
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log_property (log_id, name, value) VALUES (?, ?, ?)");
  if (is_array($value)) $value = print_r($value, true);
  $query->execute(Array($logId, $key, $value)) or httperror(print_r($query->errorInfo(),true));
}


function dbRefreshPersonCurrent($personId = NULL) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($query->errorInfo(),true));
  _dbRefreshPersonCurrent($personId) or httperror("failure to refresh person_current");
  $pdo->commit() or httperror(print_r($query->errorInfo(),true));
  return true;
}

function _dbRefreshPersonCurrent($personId = NULL) {
  global $pdo, $DB_PREFIX;
  if ($personId === NULL) {
    $r1 = $pdo->exec("TRUNCATE {$DB_PREFIX}person_current_mat") or httperror(print_r($query->errorInfo(),true));
    $r2 = $pdo->exec("INSERT INTO {$DB_PREFIX} SELECT * FROM {$DB_PREFIX}person_current_mat") or httperror(print_r($pdo->errorInfo(),true));
  } else{
    $r1 = $pdo->exec("DELETE FROM {$DB_PREFIX} WHERE person_id = ".((int) $personId)) or httperror(print_r($pdo->errorInfo(),true));
    $r2 = $pdo->exec("INSERT INTO {$DB_PREFIX} SELECT * FROM {$DB_PREFIX}person_current_mat WHERE person_id = ".((int) $personId)) or httperror(print_r($pdo->errorInfo(),true));
  }
  return ($r1 !== false) && ($r2 !== false);
}

function getPersonDetailsById($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON p.id = pe.person_id WHERE p.id = ? GROUP BY p.id");
  $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonDetailsByMail($mail) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("
SELECT p.*, GROUP_CONCAT(DISTINCT pe1.email ORDER BY pe1.srt) as email
  FROM {$DB_PREFIX}person p
       LEFT JOIN {$DB_PREFIX}person_email pe1 ON p.id = pe1.person_id
       LEFT JOIN {$DB_PREFIX}person_email pe2 ON p.id = pe2.person_id
 WHERE pe2.email LIKE ?
 GROUP BY p.id");
  $query->execute(Array($mail)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonDetailsByUsername($username) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("
SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email
  FROM {$DB_PREFIX}person p
       LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id
 WHERE username LIKE ?
GROUP BY p.id");
  $query->execute(Array($username)) or httperror(print_r($query->errorInfo(),true));
  if ($query->rowCount() == 0) return false;
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getPersonRolle($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT rm.id AS id, g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name, rm.von as von, rm.bis as bis, rm.beschlussAm as beschlussAm, rm.beschlussDurch as beschlussDurch, rm.kommentar as kommentar, rm.lastCheck as lastCheck, ((rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE)) as active FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.id AND rm.gremium_id = g.id WHERE rm.person_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name, IFNULL(rm.bis,'9999-01-01') DESC, rm.von DESC");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonGruppe($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.* FROM {$DB_PREFIX}gruppe g INNER JOIN {$DB_PREFIX}rel_rolle_gruppe r ON g.id = r.gruppe_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON (rm.rolle_id = r.rolle_id) AND ((rm.von IS NULL) OR (rm.von <= CURRENT_DATE)) AND ((rm.bis IS NULL) OR (rm.bis >= CURRENT_DATE)) WHERE rm.person_id = ? ORDER BY g.name");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonMailingliste($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT m.* FROM {$DB_PREFIX}mailingliste m INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste r ON m.id = r.mailingliste_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.rolle_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) WHERE rm.person_id = ? ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getPersonContactDetails($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT t.* FROM {$DB_PREFIX}person_contact t WHERE t.person_id = ? ORDER BY t.type, t.details, t.id");
  $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function setPersonUsername($personId, $username) {
  global $pdo, $DB_PREFIX;
  # username needs to match ^[a-z][-a-z0-9_]*\$
  $username  = preg_replace('/^[^a-z]*/', '', preg_replace('/[^-a-z0-9_]/', '', strtolower($username)));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET username = ? WHERE id = ?");
  $ret = $query->execute(Array($username, $personId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function setPersonPassword($personId, $password) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($password)) {
    $passwordHash = NULL;
  } else {
    $passwordHash = @$pwObj->createPasswordHash($password);
  }
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET password = ? WHERE id = ?");
  $ret = $query->execute(Array($passwordHash, $personId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function setPersonWebinfo($personId, $fakultaet, $stg, $matrikel) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET fakultaet = ?, stg = ?, matrikel = ? WHERE id = ?");
  $ret = $query->execute(Array($fakultaet, $stg, $matrikel, $personId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function getMailinglisten() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste m ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute() or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getMailinglisteById($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste m WHERE id = ?");
  $query->execute([$mlId]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function dbMailinglisteInsert($address, $url, $password) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}mailingliste (address, url, password) VALUES ( ?, ?, ?)");
  $ret = $query->execute(Array($address, $url, $password)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbMailinglisteUpdate($id, $address, $url, $password) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}mailingliste SET address = ?, url = ?, password = ? WHERE id = ?");
  return $query->execute(Array($address, $url, $password, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbMailinglisteDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}mailingliste WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function getMailinglisteMailmanByMailinglisteId($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste_mailman mm WHERE (mm.mailingliste_id IS NULL) OR (mm.mailingliste_id = ?) ORDER BY url, field, priority, mailingliste_id");
  $query->execute([$mlId]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getMailinglisteMailmanById($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}mailingliste_mailman mm WHERE id = ?");
  $query->execute([$id]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function dbMailinglisteMailmanInsert($mailinglisteId, $url, $field, $mode, $priority, $value) {
  global $pdo, $DB_PREFIX;
  if ($mailinglisteId == "") $mailinglisteId = NULL;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}mailingliste_mailman (mailingliste_id, url, field, mode, priority, value) VALUES ( ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($mailinglisteId, $url, $field, $mode, $priority, $value)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbMailinglisteMailmanUpdate($id, $mailinglisteId, $url, $field, $mode, $priority, $value) {
  global $pdo, $DB_PREFIX;
  if ($mailinglisteId == "") $mailinglisteId = NULL;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}mailingliste_mailman SET mailingliste_id = ?, url = ?, field = ?, mode = ?, priority = ?, value = ? WHERE id = ?");
  return $query->execute(Array($mailinglisteId, $url, $field, $mode, $priority, $value, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbMailinglisteMailmanDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}mailingliste_mailman WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function getMailinglisteRolle($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rm ON rm.rolle_id = r.id WHERE rm.mailingliste_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbMailinglisteDropRolle($mlId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_rolle_mailingliste WHERE mailingliste_id = ? AND rolle_id = ?");
  return $query->execute(Array($mlId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function dbMailinglisteInsertRolle($mlId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_rolle_mailingliste (mailingliste_id, rolle_id) VALUES (?, ?)");
  return $query->execute(Array($mlId, $rolleId)) or httperror(print_r($query->errorInfo(),true));
}

function getGremiumById($gremiumId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT g.* FROM {$DB_PREFIX}gremium g WHERE g.id = ?");
  $query->execute(Array($gremiumId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getRolleByGremiumId($gremiumId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT r.* FROM {$DB_PREFIX}rolle r WHERE r.gremium_id = ?");
  $query->execute(Array($gremiumId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRolleById($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT r.* FROM {$DB_PREFIX}rolle r WHERE r.id = ?");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getAlleRolle() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable2 as gremium_wiki_members_fulltable2, g.active as gremium_active, r.id as rolle_id, r.name as rolle_name, r.active as rolle_active, r.spiGroupId as rolle_spiGroupId, r.numPlatz as rolle_numPlatz, r.wahlDurchWikiSuffix as rolle_wahlDurchWikiSuffix, r.wahlPeriodeDays as rolle_wahlPeriodeDays, r.wiki_members_roleAsColumnTable as rolle_wiki_members_roleAsColumnTable, r.wiki_members_roleAsColumnTableExtended as rolle_wiki_members_roleAsColumnTableExtended, r.wiki_members_roleAsMasterTable as rolle_wiki_members_roleAsMasterTable, r.wiki_members_roleAsMasterTableExtended as rolle_wiki_members_roleAsMasterTableExtended, r.wiki_members as rolle_wiki_members, (rm.id IS NOT NULL) as rolle_hat_mitglied FROM {$DB_PREFIX}gremium g LEFT JOIN {$DB_PREFIX}rolle r LEFT JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.rolle_id = r.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) ON g.id = r.gremium_id ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, g.id, r.name, r.id");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getAllePerson() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email, (rm.id IS NOT NULL) AS active FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON p.id = rm.person_id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) LEFT JOIN {$DB_PREFIX}person_email pe ON p.id = pe.person_id GROUP BY p.id ORDER BY p.name");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getAllePersonCurrent() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT p.* FROM {$DB_PREFIX}person_current p ORDER BY p.name");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbPersonMerge($personId, $targetId) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET person_id = ? WHERE person_id = ?");
  $query->execute([$targetId, $personId]) or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person_email SET person_id = ? WHERE person_id = ?");
  $query->execute([$targetId, $personId]) or httperror(print_r($query->errorInfo(),true));

  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person WHERE id = ?");
  $query->execute([$personId]) or httperror(print_r($query->errorInfo(),true));

  _dbRefreshPersonCurrent();

  $pdo->commit() or httperror(print_r($query->errorInfo(),true));

  return true;
}

function dbPersonDelete($personId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person WHERE id = ?");
  $ret = $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonDisable($personId) {
  global $pdo, $DB_PREFIX;
  # disable logins
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET canLogin = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE person_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($personId)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = _dbRefreshPersonCurrent($personId);

  $ret4 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3 && $ret4;
}

function dbPersonUpdate($personId,$name,$emails,$unirzlogin,$username,$password,$canlogin,$wikiPage) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($name)) $name = NULL;
  if (empty($unirzlogin)) $unirzlogin = NULL;
  if (empty($username)) $username = NULL;
  if (empty($wikiPage)) $wikiPage = NULL;

  $numEmail = 0; $tmp = [];
  foreach ($emails as $i => $email) {
    $email = strtolower(trim($email));
    $emails[$i] = $email;

    if ($email == "") continue;

    $numEmail++;
    if (!isValidEmail($email)) {
      httperror("Ungültige eMail-Adresse: ".htmlspecialchars($email));
      return false;
    }

    $tmp[] = $email;
  }
  if ($numEmail == 0) {
    httperror("Ungültige eMail-Adresse");
    return false;
  }
  $emails = array_unique($tmp);

  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET name = ?, unirzlogin = ?, username = ?, canLogin = ?, wikiPage = ? WHERE id = ?");
  $ret1 = $query->execute([$name, $unirzlogin, $username, $canlogin, $wikiPage, $personId]) or httperror(print_r($query->errorInfo(),true));
  if (empty($password)) {
    $ret2 = true;
  } else {
    $passwordHash = @$pwObj->createPasswordHash($password);
    $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET password = ? WHERE id = ?");
    $ret2 = $query->execute(Array($passwordHash, $personId)) or httperror(print_r($query->errorInfo(),true));
  }
  $query = $pdo->prepare("SELECT srt, email FROM {$DB_PREFIX}person_email WHERE person_id = ?");
  $ret3 = $query->execute([$personId]) or httperror(print_r($query->errorInfo(),true));
  if ($ret3 !== false) {
    $tmp = $query->fetchAll(PDO::FETCH_ASSOC);
    $cemails = [];
    foreach ($tmp as $r) {
      $cemails[$r["srt"]] = $r["email"];
    }
  }
  foreach ($cemails as $i => $email) {
    if (!$ret3) continue;
    if (isset($emails[$i]) && ($emails[$i] == $email)) continue;
    $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person_email WHERE person_id = ? AND email = ? AND srt = ?");
    $ret3 = $query->execute([$personId, $email, $i]) or httperror(print_r($query->errorInfo(),true));
  }
  foreach ($emails as $i => $email) {
    if (!$ret3) continue;
    if (isset($cemails[$i]) && ($cemails[$i] == $email)) continue;
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person_email (person_id, srt, email) VALUE (?, ?, ?)");
    $ret3 = $query->execute([$personId, $i, $email]) or httperror(print_r($query->errorInfo(),true));
  }
  if (!($ret1 && $ret2 && $ret3)) {
    $pdo->rollback();
    httperror("Failed");
  }
  $ret4 = _dbRefreshPersonCurrent($personId);
  $ret5 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  return $ret1 && $ret2 && $ret3 && $ret4 && $ret5;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL)
        && preg_match('/@.+\./', $email);
}

function dbPersonInsert($name,$emails,$unirzlogin,$username,$password,$canlogin,$wikiPage, $quiet=false) {
  global $pdo, $DB_PREFIX, $pwObj;
  if (empty($name)) $name = NULL;
  if (empty($unirzlogin)) $unirzlogin = NULL;
  if (empty($username)) $username = NULL;
  if (empty($password)) { $passwordHash = NULL;  } else { $passwordHash = @$pwObj->createPasswordHash($password); }
  if (empty($wikiPage)) $wikiPage = NULL;
  if (!is_array($emails)) $emails = [$emails];
  $numEmail = 0;
  foreach ($emails as $i => $email) {
    $email = strtolower(trim($email));
    $emails[$i] = $email;
    if ($email == "") continue;
    $numEmail++;
    if (!isValidEmail($email)) {
      httperror("Ungültige eMail-Adresse");
      return false;
    }
  }
  if ($numEmail == 0) {
    httperror("Ungültige eMail-Adresse");
    return false;
  }
  $emails = array_unique($emails);

  $ret = $pdo->beginTransaction();
  if (!$ret && !$quiet) { httperror(print_r($pdo->errorInfo(),true)); }
  if ($ret === false) {
    httperror("DB ERROR: CANNOT START TRANSACTION");
    return $ret;
  }

  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person (name, unirzlogin, username, password, canLogin, wikiPage) VALUES (?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($name, $unirzlogin, $username, $passwordHash, $canlogin, $wikiPage));
  if (!$ret && !$quiet) { httperror(print_r($query->errorInfo(),true)); }
  if ($ret === false) {
    $pdo->rollback();
    return $ret;
  }
  $personId = $pdo->lastInsertId();

  $i = 0;
  foreach ($emails as $email) {
    if ($email == "") continue;

    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person_email (person_id, srt, email) VALUES (?, ?, ?)");
    $ret = $query->execute([$personId, $i, $email]);
    if (!$ret && !$quiet) { httperror(print_r($query->errorInfo(),true)); }
    if ($ret === false) {
      $pdo->rollback();
      return $ret;
    }

    $i++;
  }

  $ret = _dbRefreshPersonCurrent($personId);
  if (!$ret && !$quiet) { httperror(print_r($pdo->errorInfo(),true)); }
  if ($ret === false) {
    $pdo->rollback();
    return $ret;
  }

  $ret = $pdo->commit();
  if (!$ret && !$quiet) { httperror(print_r($pdo->errorInfo(),true)); }
  if ($ret === false) {
    return $ret;
  }
  return $personId;
}

function dbPersonInsertRolle($personId,$rolleId,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar) {
  global $pdo, $DB_PREFIX;
  if (empty($von)) $von = NULL;
  if (empty($bis)) $bis = NULL;
  if (empty($beschlussAm)) $beschlussAm = NULL;
  if (empty($beschlussDurch)) $beschlussDurch = NULL;
  if (empty($lastCheck)) $lastCheck = NULL;
  if (empty($kommentar)) $kommentar = NULL;
  if ($von !== NULL && $bis !== NULL) {
    $query = $pdo->prepare("SELECT cast(? AS DATE) <= cast(? AS DATE) AS valid");
    $query->execute(Array($von, $bis)) or httperror (print_r($query->errorInfo(),true));
    $validDates = (bool) $query->fetchColumn();
    if (!$validDates) {
      httperror("Ungültige Bereichsangabe (von > bis)");
      return false;
    }
  }
  $query = $pdo->prepare("SELECT gremium_id FROM {$DB_PREFIX}rolle WHERE id = ?");
  $query->execute(Array($rolleId)) or httperror (print_r($query->errorInfo(),true));
  $gremiumId = $query->fetchColumn();
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_mitgliedschaft (person_id, rolle_id, gremium_id, von, bis, beschlussAm, beschlussDurch, lastCheck, kommentar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($personId,$rolleId,$gremiumId,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonUpdateRolle($id, $personId,$rolleId,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar) {
  global $pdo, $DB_PREFIX;
  if (empty($von)) $von = NULL;
  if (empty($bis)) $bis = NULL;
  if (empty($beschlussAm)) $beschlussAm = NULL;
  if (empty($beschlussDurch)) $beschlussDurch = NULL;
  if (empty($lastCheck)) $lastCheck = NULL;
  if (empty($kommentar)) $kommentar = NULL;
  if ($von !== NULL && $bis !== NULL) {
    $query = $pdo->prepare("SELECT cast(? as DATE) <= cast(? as DATE) AS valid");
    $query->execute(Array($von, $bis)) or httperror (print_r($query->errorInfo(),true));
    $validDates = (bool) $query->fetchColumn();
    if (!$validDates) {
      httperror("Ungültige Bereichsangabe (von > bis)");
      return false;
    }
  }

  $query = $pdo->prepare("SELECT gremium_id FROM {$DB_PREFIX}rolle WHERE id = ?");
  $query->execute(Array($rolleId)) or httperror (print_r($query->errorInfo(),true));
  $gremiumId = $query->fetchColumn();
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET person_id = ?, rolle_id = ?, gremium_id = ?, von = ?, bis = ?, beschlussAm = ?, beschlussDurch = ?, lastCheck = ?, kommentar = ? WHERE id = ?");
  $ret = $query->execute(Array($personId,$rolleId,$gremiumId,$von,$bis,$beschlussAm,$beschlussDurch,$lastCheck,$kommentar,$id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonDeleteRolle($id) {
  global $pdo, $DB_PREFIX;

  $query = $pdo->prepare("SELECT person_id FROM {$DB_PREFIX}rel_mitgliedschaft WHERE id = ?");
  $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $personId = $query->fetchAll();
  if (count($personId) == 0) return true; # does not exist
  $personId = $personId[0]["person_id"];

  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_mitgliedschaft WHERE id = ?");
  $ret = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonDisableRolle($id, $bis = NULL, $grund = "") {
  global $pdo, $DB_PREFIX;
  if (empty($bis)) $bis = date("Y-m-d", strtotime("yesterday"));
  $grund = trim($grund);
  if (empty($grund)) $grund = NULL;

  $query = $pdo->prepare("SELECT person_id FROM {$DB_PREFIX}rel_mitgliedschaft WHERE id = ?");
  $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $personId = $query->fetchAll();
  if (count($personId) == 0) return true; # does not exist
  $personId = $personId[0]["person_id"];

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = STR_TO_DATE(?, '%Y-%m-%d'), kommentar = concat_ws('\n', kommentar, ?) WHERE id = ? AND (bis IS NULL OR bis > STR_TO_DATE(?, '%Y-%m-%d'))");
  $ret = $query->execute(Array($bis,$grund,$id,$bis)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonInsertContact($personId,$type,$details,$fromWiki,$active) {
  global $pdo, $DB_PREFIX;
  $active = ($active || $fromWiki) ? 1 : 0;
  $fromWiki = $fromWiki ? 1 : 0;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}person_contact (person_id, type, details, fromWiki, active) VALUES (?, ?, ?, ?, ?)");
  return $query->execute(Array($personId,$type,$details,$fromWiki,$active)) or httperror(print_r($query->errorInfo(),true));
}

function dbPersonUpdateContact($id, $personId,$type,$details,$fromWiki,$active) {
  global $pdo, $DB_PREFIX;
  $active = ($active || !$fromWiki) ? 1 : 0;
  $fromWiki = $fromWiki ? 1 : 0;

  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person_contact SET person_id = ?, type = ?, details = ?, fromWiki = ?, active = ? WHERE id = ?");
  $ret = $query->execute(Array($personId,$type,$details,$fromWiki,$active,$id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function dbPersonDeleteContact($id) {
  global $pdo, $DB_PREFIX;

  $query = $pdo->prepare("SELECT person_id FROM {$DB_PREFIX}person_contact WHERE id = ?");
  $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $personId = $query->fetchAll();
  if (count($personId) == 0) return true; # does not exist
  $personId = $personId[0]["person_id"];

  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}person_contact WHERE id = ?");
  $ret = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent($personId);
  return $ret;
}

function getGruppeRolle($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT g.id AS gremium_id, g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, g.wiki_members as gremium_wiki_members, g.wiki_members_table as gremium_wiki_members_table, g.wiki_members_fulltable as gremium_wiki_members_fulltable, g.wiki_members_fulltable2 as gremium_wiki_members_fulltable2, r.id as rolle_id, r.name as rolle_name FROM {$DB_PREFIX}gremium g INNER JOIN {$DB_PREFIX}rolle r ON g.id = r.gremium_id INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rg ON rg.rolle_id = r.id WHERE rg.gruppe_id = ? ORDER BY g.name, g.fakultaet, g.studiengang, g.studiengangabschluss, r.name");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getGruppeById($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}gruppe WHERE id = ?");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getAlleGruppe() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}gruppe ORDER BY name");
  $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function dbGruppeInsert($name, $beschreibung) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}gruppe (name, beschreibung) VALUES ( ?, ?)");
  $ret = $query->execute(Array($name, $beschreibung)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbGruppeUpdate($id, $name, $beschreibung) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gruppe SET name = ?, beschreibung = ? WHERE id = ?");
  $ret = $query->execute(Array($name, $beschreibung, $id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGruppeDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}gruppe WHERE id = ?");
  $ret = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGruppeDropRolle($grpId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rel_rolle_gruppe WHERE gruppe_id = ? AND rolle_id = ?");
  $ret = $query->execute(Array($grpId, $rolleId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGruppeInsertRolle($grpId, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rel_rolle_gruppe (gruppe_id, rolle_id) VALUES (?, ?)");
  $ret = $query->execute(Array($grpId, $rolleId)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGremiumInsert($name, $fakultaet, $studiengang, $studiengang_short,$studiengang_english, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT {$DB_PREFIX}gremium (name, fakultaet, studiengang, studiengang_short, studiengang_english, studiengangabschluss, wiki_members, wiki_members_table, wiki_members_fulltable, active, wiki_members_fulltable2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($name, $fakultaet, $studiengang, $studiengang_short, $studiengang_english, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2)) or httperror(__FILE__.":".__LINE__." ".print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  return $pdo->lastInsertId();
}

function dbGremiumUpdate($id, $name, $fakultaet, $studiengang, $studiengang_short,$studiengang_english, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gremium SET name = ?, fakultaet = ?, studiengang = ?, studiengang_short = ?, studiengang_english = ?, studiengangabschluss = ?, wiki_members = ?, wiki_members_table = ?, wiki_members_fulltable = ?, active = ?, wiki_members_fulltable2 = ? WHERE id = ?");
  return $query->execute(Array($name, $fakultaet, $studiengang, $studiengang_short,$studiengang_english, $studiengangabschluss, $wiki_members, $wiki_members_table, $wiki_members_fulltable, $active, $wiki_members_fulltable2, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDelete($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}gremium WHERE id = ?");
  return $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDisable($id) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}gremium SET active = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE gremium_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  $ret = $ret1 && $ret2 && $ret3;;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGremiumInsertRolle($gremiumId, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays, $wiki_members_roleAsColumnTable, $wiki_members_roleAsColumnTableExtended, $wiki_members_roleAsMasterTable, $wiki_members_roleAsMasterTableExtended, $wiki_members) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}rolle (gremium_id, name, active, spiGroupId, numPlatz, wahlDurchWikiSuffix, wahlPeriodeDays, wiki_members_roleAsColumnTable, wiki_members_roleAsColumnTableExtended, wiki_members_roleAsMasterTable, wiki_members_roleAsMasterTableExtended, wiki_members) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ret = $query->execute(Array($gremiumId, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays, $wiki_members_roleAsColumnTable, $wiki_members_roleAsColumnTableExtended, $wiki_members_roleAsMasterTable, $wiki_members_roleAsMasterTableExtended, $wiki_members)) or httperror(print_r($query->errorInfo(),true));
  if ($ret === false)
    return $ret;
  dbRefreshPersonCurrent(NULL);
  return $pdo->lastInsertId();
}

function dbGremiumUpdateRolle($id, $name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays, $wiki_members_roleAsColumnTable, $wiki_members_roleAsColumnTableExtended, $wiki_members_roleAsMasterTable, $wiki_members_roleAsMasterTableExtended, $wiki_members) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rolle SET name = ?, active = ?, spiGroupId = ?, numPlatz = ?, wahlDurchWikiSuffix = ?, wahlPeriodeDays = ?, wiki_members_roleAsColumnTable = ?, wiki_members_roleAsColumnTableExtended = ?, wiki_members_roleAsMasterTable = ?, wiki_members_roleAsMasterTableExtended = ?, wiki_members = ? WHERE id = ?");
  return $query->execute(Array($name, $active, $spiGroupId, $numPlatz, $wahlDurchWikiSuffix, $wahlPeriodeDays, $wiki_members_roleAsColumnTable, $wiki_members_roleAsColumnTableExtended, $wiki_members_roleAsMasterTable, $wiki_members_roleAsMasterTableExtended, $wiki_members, $id)) or httperror(print_r($query->errorInfo(),true));
}

function dbGremiumDeleteRolle($id) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("DELETE FROM {$DB_PREFIX}rolle WHERE id = ?");
  $ret = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function dbGremiumDisableRolle($id) {
  global $pdo, $DB_PREFIX;
  $pdo->beginTransaction() or httperror(print_r($pdo->errorInfo(),true));
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rolle SET active = 0 WHERE id = ?");
  $ret1 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  # terminate memberships
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}rel_mitgliedschaft SET bis = subdate(current_date, 1) WHERE rolle_id = ? AND (bis IS NULL OR bis >= CURRENT_DATE)");
  $ret2 = $query->execute(Array($id)) or httperror(print_r($query->errorInfo(),true));
  $ret3 = $pdo->commit() or httperror(print_r($pdo->errorInfo(),true));
  $ret = $ret1 && $ret2 && $ret3;
  dbRefreshPersonCurrent(NULL);
  return $ret;
}

function getRolleMailinglisten($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT m.* FROM {$DB_PREFIX}mailingliste m INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rm ON rm.mailingliste_id = m.id WHERE rm.rolle_id = ? ORDER BY RIGHT(m.address, LENGTH(m.address) - POSITION( '@' in m.address)), LEFT(m.address, POSITION( '@' in m.address))");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRolleGruppen($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT grp.* FROM {$DB_PREFIX}gruppe grp INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rgrp ON rgrp.gruppe_id = grp.id WHERE rgrp.rolle_id = ? ORDER BY grp.name");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getRollePersonen($rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email, rp.id AS rel_id, rp.von, rp.bis, rp.beschlussAm, rp.beschlussDurch, rp.lastCheck, rp.kommentar, ((rp.von <= CURRENT_DATE OR rp.von IS NULL) AND (rp.bis >= CURRENT_DATE OR rp.bis IS NULL)) AS active FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rp ON rp.person_id = p.id WHERE rp.rolle_id = ? GROUP BY rp.id ORDER BY name");
  $query->execute(Array($rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveMitgliedschaftByMail($email, $rolleId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT rm.* FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id WHERE email = ? AND rm.rolle_id = ? AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) ORDER BY rm.id");
  $query->execute(Array($email, $rolleId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getAllMitgliedschaft() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT g.name as gremium_name, g.fakultaet as gremium_fakultaet, g.studiengang as gremium_studiengang, g.studiengangabschluss as gremium_studiengangabschluss, r.name as rolle_name, email as person_email, p.name as person_name, p.username as person_username, rm.von as von, rm.bis as bis, rm.beschlussAm as beschlussAm, rm.beschlussDurch as beschlussDurch, rm.lastCheck, rm.kommentar as kommentar, ((rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE)) AS aktiv FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id INNER JOIN {$DB_PREFIX}gremium g ON g.id = rm.gremium_id INNER JOIN {$DB_PREFIX}rolle r ON r.id = rm.rolle_id ORDER BY g.name, g.id, r.name, r.id, RIGHT(email, LENGTH(email) - POSITION( '@' in email)), LEFT(email, POSITION( '@' in email))");
  $query->execute() or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getMitgliedschaftById($relId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT rm.* FROM {$DB_PREFIX}rel_mitgliedschaft rm WHERE id = ?");
  $query->execute([$relId]) or httperror(print_r($query->errorInfo(),true));
  return $query->fetch(PDO::FETCH_ASSOC);
}

function getMailinglistePerson($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT pe.email FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email_primary pe ON p.id = pe.person_id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rrm ON rm.rolle_id = rrm.rolle_id AND rrm.mailingliste_id = ? ORDER BY email");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_COLUMN);
}

function getMailinglistePersonDetails($mlId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email_primary pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_mailingliste rrm ON rm.rolle_id = rrm.rolle_id AND rrm.mailingliste_id = ? GROUP BY p.name, p.id");
  $query->execute(Array($mlId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

# deprecated
function getGruppePerson($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT pe.email FROM {$DB_PREFIX}person p INNER JOIN {$DB_PREFIX}person_email_primary pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rrg ON rm.rolle_id = rrg.rolle_id AND rrg.gruppe_id = ? ORDER BY email");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_COLUMN);
}

function getGruppePersonDetails($grpId) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("SELECT DISTINCT p.*, GROUP_CONCAT(DISTINCT pe.email ORDER BY pe.srt) as email FROM {$DB_PREFIX}person p LEFT JOIN {$DB_PREFIX}person_email pe ON pe.person_id = p.id INNER JOIN {$DB_PREFIX}rel_mitgliedschaft rm ON rm.person_id = p.id AND (rm.von IS NULL OR rm.von <= CURRENT_DATE) AND (rm.bis IS NULL OR rm.bis >= CURRENT_DATE) INNER JOIN {$DB_PREFIX}rel_rolle_gruppe rrg ON rm.rolle_id = rrg.rolle_id AND rrg.gruppe_id = ? GROUP BY p.id ORDER BY email");
  $query->execute(Array($grpId)) or httperror(print_r($query->errorInfo(),true));
  return $query->fetchAll(PDO::FETCH_ASSOC);
}

function getDBDump() {
  global $pdo, $DB_PREFIX;
  $tables = Array("person" => "id",
                  "person_email" => "person_id, email",
                  "gruppe" => "id",
                  "gremium" => "id",
                  "log" => "id",
                  "log_property" => "id",
                  "mailingliste" => "id",
                  "rel_mitgliedschaft" => "id",
                  "rel_rolle_gruppe" => "rolle_id, gruppe_id",
                  "rel_rolle_mailingliste" => "rolle_id, mailingliste_id",
                  "rolle" => "id",
                 );
  $ret = Array();
  ksort($tables);
  foreach ($tables as $t => $s) {
    $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}{$t} ORDER BY {$s}");
    $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
    $ret[$t] = $query->fetchAll(PDO::FETCH_ASSOC);
  }
  #ksort($ret);
  return $ret;
}

function printDBDump() {
  global $pdo, $DB_PREFIX;
  $tables = Array("person" => "id",
                  "person_email" => "person_id, email",
                  "gruppe" => "id",
                  "gremium" => "id",
                  "log" => "id",
                  "log_property" => "id",
                  "mailingliste" => "id",
                  "rel_mitgliedschaft" => "id",
                  "rel_rolle_gruppe" => "rolle_id, gruppe_id",
                  "rel_rolle_mailingliste" => "rolle_id, mailingliste_id",
                  "rolle" => "id",
                 );
  ksort($tables);
  echo "{\n";
  foreach ($tables as $t => $s) {
    $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}{$t} ORDER BY {$s}");
    $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
    echo "    \"$t\": [\n";
    while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
      $rows = explode("\n", json_encode($row, JSON_PRETTY_PRINT).",");
      foreach ($rows as $row) {
        echo "        $row\n";
      }
    }
    echo "    ],\n";
  }
  echo "}\n";
}

function setPersonImageId($personId, $imageId) {
  global $pdo, $DB_PREFIX;
  # username needs to match ^[a-z][-a-z0-9_]*\$
  $query = $pdo->prepare("UPDATE {$DB_PREFIX}person SET image = ? WHERE id = ?");
  return $query->execute(Array($imageId, $personId)) or httperror(print_r($query->errorInfo(),true));
}

# vim: set expandtab tabstop=8 shiftwidth=8 :
