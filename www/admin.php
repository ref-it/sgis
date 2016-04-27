<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

function escapeMe($d, $row) {
  return htmlspecialchars($d);
}
function trimMe($d) {
  if (is_array($d)) {
    return array_map("trimMe", $d);
  } else {
    return trim($d);
  }
}

if (isset($_POST["action"])) {
 $msgs = Array();
 $ret = false;
 $target = false;
 if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
  $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
 } else {
  $logId = logThisAction();
  if (strpos($_POST["action"],"insert") !== false ||
      strpos($_POST["action"],"update") !== false ||
      strpos($_POST["action"],"delete") !== false) {
    foreach ($_REQUEST as $k => $v) {
      $_REQUEST[$k] = trimMe($v);
    }
  }

  switch ($_POST["action"]):
  case "person.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                 'dt' => 'id' ),
     array( 'db' => 'email',              'dt' => 'email', 'formatter' => 'escapeMe' ),
     array( 'db' => 'name',               'dt' => 'name', 'formatter' => 'escapeMe' ),
     array( 'db' => 'username',           'dt' => 'username', 'formatter' => 'escapeMe' ),
//     array( 'db' => 'password', 'dt' => 3 ),
     array( 'db' => 'unirzlogin',         'dt' => 'unirzlogin',
       'formatter' => function( $d, $row ) {
         return str_replace("@tu-ilmenau.de","",$d);
       }
     ),
     array( 'db' => 'lastLogin',          'dt' => 'lastLogin',
       'formatter' => function( $d, $row ) {
         return $d ? date( 'Y-m-d', strtotime($d)) : "";
       }
     ),
     array( 'db'    => 'canLoginCurrent', 'dt'    => 'canLogin',
       'formatter' => function( $d, $row ) {
         return (!$d) ? "ja" : "nein";
       }
     ),
     array( 'db'    => 'active',          'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}person_current", /* primary key */ "id", $columns )
   );
  exit;
  case "mailingliste.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'address',               'dt' => 'address', 'formatter' => 'escapeMe' ),
     array( 'db' => 'url',                   'dt' => 'url', 'formatter' => 'escapeMe' ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}mailingliste", /* primary key */ "id", $columns )
   );
  exit;
  case "gruppe.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'name',                  'dt' => 'name', 'formatter' => 'escapeMe' ),
     array( 'db' => 'beschreibung',          'dt' => 'beschreibung', 'formatter' => 'escapeMe' ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}gruppe", /* primary key */ "id", $columns )
   );
  exit;
  case "gremium.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'name',                  'dt' => 'name', 'formatter' => 'escapeMe' ),
     array( 'db' => 'fullname',              'dt' => 'fullname', 'formatter' => 'escapeMe' ),
     array( 'db' => 'fakultaet',             'dt' => 'fakultaet', 'formatter' => 'escapeMe' ),
     array( 'db' => 'studiengang',           'dt' => 'studiengang', 'formatter' => 'escapeMe' ),
     array( 'db' => 'studiengangabschluss',  'dt' => 'studiengangabschluss', 'formatter' => 'escapeMe' ),
     array( 'db' => 'has_members',           'dt' => 'has_members',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
     array( 'db' => 'has_members_in_inactive_roles', 'dt' => 'has_members_in_inactive_roles',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
     array( 'db'    => 'active',          'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}gremium_current", /* primary key */ "id", $columns )
   );
  exit;
  case "rolle.table":
   header("Content-Type: text/json; charset=UTF-8");

   $columns = array(
     array( 'db' => 'id',                            'dt' => 'id' ),
     array( 'db' => 'rolle_name',                    'dt' => 'rolle_name', 'formatter' => 'escapeMe' ),
     array( 'db' => 'gremium_name',                  'dt' => 'gremium_name', 'formatter' => 'escapeMe' ),
     array( 'db' => 'gremium_fakultaet',             'dt' => 'gremium_fakultaet', 'formatter' => 'escapeMe' ),
     array( 'db' => 'gremium_studiengang',           'dt' => 'gremium_studiengang', 'formatter' => 'escapeMe' ),
     array( 'db' => 'gremium_studiengangabschluss',  'dt' => 'gremium_studiengangabschluss', 'formatter' => 'escapeMe' ),
     array( 'db'    => 'active',                     'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}rolle_searchable", /* primary key */ "id", $columns )
   );
  exit;
  case "mailingliste.insert":
   $ret = dbMailinglisteInsert($_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde erstellt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=mailingliste.edit&mailingliste_id=".$ret;
  break;
  case "mailingliste.update":
   $ret = dbMailinglisteUpdate($_POST["id"], $_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde aktualisiert.";
  break;
  case "mailingliste.delete":
   $ret = dbMailinglisteDelete($_POST["id"]);
   $msgs[] = "Mailingliste wurde entfernt.";
  break;
  case "rolle_mailingliste.delete":
   $ret = dbMailinglisteDropRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde entfernt.";
  break;
  case "rolle_mailingliste.insert":
   $ret = dbMailinglisteInsertRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde eingetragen.";
  break;
  case "person.delete":
   $ret = dbPersonDelete($_POST["id"]);
   $msgs[] = "Person wurde entfernt.";
  break;
  case "person.disable":
   $ret = dbPersonDisable($_POST["id"]);
   $msgs[] = "Person wurde deaktiviert.";
  break;
  case "person.update":
   $ret = dbPersonUpdate($_POST["id"],trim($_POST["name"]),trim($_POST["email"]),trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"]);
   $msgs[] = "Person wurde aktualisiert.";
  break;
  case "person.insert":
   $quiet = isset($_FILES["csv"]) && !empty($_FILES["csv"]["tmp_name"]);
   $ret = true;
   if (!empty($_POST["email"]) || !$quiet) {
     $ret = dbPersonInsert(trim($_POST["name"]),trim($_POST["email"]),trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"], $quiet);
     if ($ret !== false)
       $target = $_SERVER["PHP_SELF"]."?tab=person.edit&person_id=".$ret;
     $msgs[] = "Person {$_POST["name"]} wurde ".(($ret !== false) ? "": "nicht ")."angelegt.";
   }
   if ($quiet) {
     if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
       fgetcsv($handle, 1000, ",");
       while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
         $ret2 = dbPersonInsert(trim($data[0]),trim($data[1]),trim((string)$data[2]),"","",$_POST["canlogin"], $quiet);
         $msgs[] = "Person {$data[0]} <{$data[1]}> wurde ".(($ret2 !== false) ? "": "nicht ")."angelegt.";
         $ret = $ret && $ret2;
       }
       fclose($handle);
     }
   }
  break;
  case "rolle_person.insert":
   if ($_POST["person_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Person ausgewählt.";
   } else if ($_POST["rolle_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Rolle ausgewählt.";
   } else {
     $ret = dbPersonInsertRolle($_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"], $_POST["kommentar"]);
     $msgs[] = "Person-Rollen-Zuordnung wurde angelegt.";
   }
  break;
  case "rolle_person.update":
   $ret = dbPersonUpdateRolle($_POST["id"], $_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"],$_POST["kommentar"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde aktualisiert.";
  break;
  case "rolle_person.delete":
   $ret = dbPersonDeleteRolle($_POST["id"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde gelöscht.";
  break;
  case "rolle_person.disable":
   $ret = dbPersonDisableRolle($_POST["id"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde beendet.";
  break;
  case "gruppe.insert":
   $ret = dbGruppeInsert($_POST["name"], $_POST["beschreibung"]);
   $msgs[] = "Gruppe wurde erstellt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=gruppe.edit&gruppe_id=".$ret;
  break;
  case "gruppe.update":
   $ret = dbGruppeUpdate($_POST["id"], $_POST["name"], $_POST["beschreibung"]);
   $msgs[] = "Gruppe wurde aktualisiert.";
  break;
  case "gruppe.delete":
   $ret = dbGruppeDelete($_POST["id"]);
   $msgs[] = "Gruppe wurde entfernt.";
  break;
  case "rolle_gruppe.delete":
   $ret = dbGruppeDropRolle($_POST["gruppe_id"], $_POST["rolle_id"]);
   $msgs[] = "Gruppen-Rollenzuordnung wurde entfernt.";
  break;
  case "rolle_gruppe.insert":
   $ret = dbGruppeInsertRolle($_POST["gruppe_id"], $_POST["rolle_id"]);
   $msgs[] = "Gruppen-Rollenzuordnung wurde eingetragen.";
  break;
  case "gremium.insert":
   $ret = dbGremiumInsert($_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["wiki_members_table"], $_POST["wiki_members_fulltable"], $_POST["active"]);
   $msgs[] = "Gremium wurde angelegt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=gremium.edit&gremium_id=".$ret;
  break;
  case "gremium.update":
   $ret = dbGremiumUpdate($_POST["id"], $_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["wiki_members_table"], $_POST["wiki_members_fulltable"], $_POST["active"]);
   $msgs[] = "Gremium wurde geändert.";
  break;
  case "gremium.delete":
   $ret = dbGremiumDelete($_POST["id"]);
   $msgs[] = "Gremium wurde entfernt.";
  break;
  case "gremium.disable":
   $ret = dbGremiumDisable($_POST["id"]);
   $msgs[] = "Gremium wurde deaktiviert.";
  break;
  case "rolle_gremium.insert":
   $spiGroupId = $_POST["spiGroupId"];
   if ($spiGroupId === "") $spiGroupId = NULL;
   $ret = dbGremiumInsertRolle($_POST["gremium_id"],$_POST["name"],$_POST["active"],$spiGroupId);
   $msgs[] = "Rolle wurde angelegt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=rolle.edit&rolle_id=".$ret;
  break;
  case "rolle_gremium.update":
   $spiGroupId = $_POST["spiGroupId"];
   if ($spiGroupId === "") $spiGroupId = NULL;
   $ret = dbGremiumUpdateRolle($_POST["id"], $_POST["name"],$_POST["active"],$spiGroupId);
   $msgs[] = "Rolle wurde umbenannt.";
  break;
  case "rolle_gremium.delete":
   $ret = dbGremiumDeleteRolle($_POST["id"]);
   $msgs[] = "Rolle wurde entfernt.";
  break;
  case "rolle_gremium.disable":
   $ret = dbGremiumDisableRolle($_POST["id"]);
   $msgs[] = "Rolle wurde deaktiviert.";
  break;
  case "rolle_person.bulkinsert":
   if ($_POST["rolle_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Rolle ausgewählt.";
   } else {
     $emails = explode("\n", $_REQUEST["email"]);
     foreach ($emails as $email) {
       $email = trim($email);
       if (empty($email)) continue;
       $person = getPersonDetailsByMail($email);
       if ($person === false) {
         $msgs[] = "Personen-Rollenzuordnung: $email wurde nicht gefunden.";
         continue;
       }
       $rel_mems = getActiveMitgliedschaftByMail(trim($email), $_POST["rolle_id"]);
       if ($rel_mems === false || count($rel_mems) == 0 || $_POST["duplicate"] == "ignore") {
         $ret2 = dbPersonInsertRolle($person["id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"],$_POST["kommentar"]);
         $ret = $ret && $ret2;
         $msgs[] = "Person-Rollen-Zuordnung für $email wurde erstellt.";
       } else {
         $msgs[] = "Person-Rollen-Zuordnung für $email wurde übersprungen.";
       }
     }
     $ret = true;
   }
  break;
  case "rolle_person.bulkdisable":
   $emails = explode("\n", $_REQUEST["email"]);
   $ret = true;
   foreach ($emails as $email) {
     $email = trim($email);
     if (empty($email)) continue;
     $rel_mems = getActiveMitgliedschaftByMail($email, $_POST["rolle_id"]);
     if ($rel_mems === false) {
       $msgs[] = "Personen-Rollenzuordnung: $email wurde nicht gefunden.";
     } else {
       foreach ($rel_mems as $rel_mem) {
         $ret2 = dbPersonDisableRolle($rel_mem["id"], $_POST["bis"]);
         $ret = $ret && $ret2;
       }
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde beendet.";
     }
   }
  break;
  default:
   logAppend($logId, "__result", "invalid action");
   die("Aktion nicht bekannt.");
  endswitch;
 } /* switch */

 logAppend($logId, "__result", ($ret !== false) ? "ok" : "failed");
 logAppend($logId, "__result_msg", $msgs);

 $result = Array();
 $result["msgs"] = $msgs;
 $result["ret"] = ($ret !== false);
 if ($target !== false)
   $result["target"] = $target;

 header("Content-Type: text/json; charset=UTF-8");
 echo json_encode($result);
 exit;
}

require "../template/header.tpl";
require "../template/admin.tpl";

if (!isset($_REQUEST["tab"])) {
  $_REQUEST["tab"] = "person";
}

switch($_REQUEST["tab"]) {
  case "person":
  require "../template/admin_personen.tpl";
  break;
  case "person.new":
  require "../template/admin_personen_new.tpl";
  break;
  case "person.edit":
  require "../template/admin_personen_edit.tpl";
  break;
  case "person.delete":
  require "../template/admin_personen_delete.tpl";
  break;
  case "gremium":
  require "../template/admin_gremien.tpl";
  break;
  case "gremium.new":
  require "../template/admin_gremium_new.tpl";
  break;
  case "gremium.edit":
  require "../template/admin_gremium_edit.tpl";
  break;
  case "gremium.delete":
  require "../template/admin_gremium_delete.tpl";
  break;
  case "rolle.new":
  require "../template/admin_rolle_new.tpl";
  break;
  case "rolle.edit":
  require "../template/admin_rolle_edit.tpl";
  break;
  case "rolle.delete":
  require "../template/admin_rolle_delete.tpl";
  break;
  case "rel_mitgliedschaft.new":
  require "../template/admin_rel_mitgliedschaft_new.tpl";
  break;
  case "rel_mitgliedschaft.edit":
  require "../template/admin_rel_mitgliedschaft_edit.tpl";
  break;
  case "rel_mitgliedschaft.delete":
  require "../template/admin_rel_mitgliedschaft_delete.tpl";
  break;
  case "rel_mitgliedschaft_multiple.new":
  require "../template/admin_rel_mitgliedschaft_multiple_new.tpl";
  break;
  case "rel_mitgliedschaft_multiple.delete":
  require "../template/admin_rel_mitgliedschaft_multiple_delete.tpl";
  break;
  case "rel_rolle_gruppe.new":
  require "../template/admin_rel_rolle_gruppe_new.tpl";
  break;
  case "rel_rolle_gruppe.delete":
  require "../template/admin_rel_rolle_gruppe_delete.tpl";
  break;
  case "rel_rolle_mailingliste.new":
  require "../template/admin_rel_rolle_mailingliste_new.tpl";
  break;
  case "rel_rolle_mailingliste.delete":
  require "../template/admin_rel_rolle_mailingliste_delete.tpl";
  break;
  case "gruppe":
  require "../template/admin_gruppen.tpl";
  break;
  case "gruppe.new":
  require "../template/admin_gruppen_new.tpl";
  break;
  case "gruppe.edit":
  require "../template/admin_gruppen_edit.tpl";
  break;
  case "gruppe.delete":
  require "../template/admin_gruppen_delete.tpl";
  break;
  case "mailingliste":
  require "../template/admin_mailinglisten.tpl";
  break;
  case "mailingliste.new":
  require "../template/admin_mailinglisten_new.tpl";
  break;
  case "mailingliste.edit":
  require "../template/admin_mailinglisten_edit.tpl";
  break;
  case "mailingliste.delete":
  require "../template/admin_mailinglisten_delete.tpl";
  break;
  case "export":
  require "../template/admin_export.tpl";
  break;
  case "help":
  require "../template/admin_help.tpl";
  break;
  default:
  die("invalid tab name");
}

require "../template/admin_footer.tpl";
require "../template/footer.tpl";

exit;

