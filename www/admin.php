<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);
if (!isset($_REQUEST["tab"]) && !isset($_REQUEST["ajax"])) {
  require "../template/header.tpl";
}

$alle_mailinglisten = getMailinglisten();
$alle_gremien = getAlleRolle();
$alle_personen = getAllePerson();
$alle_gruppen = getAlleGruppe();
$script = Array();

if (isset($_GET["msgs"])) {
 $msgs = $_GET["msgs"];
} else {
 $msgs = Array();
}

if (isset($_POST["action"])) {
 $ret = false;
 if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
  $msgs[] = "Formular veraltet - CRSP Schutz aktiviert.";
 } else {
  $logId = logThisAction();
  switch ($_POST["action"]):
  case "mailingliste.insert":
   $ret = dbMailinglisteInsert($_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde erstellt.";
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
   if (!empty($_POST["email"])) {
     $ret = dbPersonInsert(trim($_POST["name"]),trim($_POST["email"]),trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"], $quiet);
     $msgs[] = "Person {$_POST["name"]} wurde ".($ret ? "": "nicht ")."angelegt.";
   }
   if ($quiet) {
     if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
       fgetcsv($handle, 1000, ",");
       while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
         $ret2 = dbPersonInsert(trim($data[0]),trim($data[1]),trim((string)$data[2]),"","",$_POST["canlogin"], $quiet);
         $msgs[] = "Person {$data[0]} <{$data[1]}> wurde ".($ret2 ? "": "nicht ")."angelegt.";
         $ret = $ret && $ret2;
       }
       fclose($handle);
     }
   }
  break;
  case "rolle_person.insert":
   $ret = dbPersonInsertRolle($_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde angelegt.";
  break;
  case "rolle_person.update":
   $ret = dbPersonUpdateRolle($_POST["id"], $_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
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
   $ret = dbGremiumInsert($_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["active"]);
   $msgs[] = "Gremium wurde angelegt.";
  break;
  case "gremium.update":
   $ret = dbGremiumUpdate($_POST["id"], $_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["active"]);
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
       $ret2 = dbPersonInsertRolle($person["id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
       $ret = $ret && $ret2;
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde erstellt.";
     } else {
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde übersprungen.";
     }
   }
   $ret = true;
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
 }
 logAppend($logId, "__result", $ret ? "ok" : "failed");
 logAppend($logId, "__result_msg", $msgs);
 if ($ret && !isset($_REQUEST["ajax"])) {
  $query = "";
  foreach ($msgs as $msg) {
   $query .= "&msgs[]=".urlencode($msg);
  }
  header("Location: ".$_SERVER["PHP_SELF"]."?".$query);
  exit;
 }
}

if (isset($_REQUEST["ajax"])) {
  $result = Array();
  $result["msgs"] = $msgs;
  $result["ret"] = $ret;

  header("Content-Type: text/json; charset=UTF-8");
  echo json_encode($result);
  exit;
}

// Person filter
$activefilter = Array();
$activefilter["name"] = Array();
$activefilter["email"] = Array();
$activefilter["unirzlogin"] = Array();
$activefilter["username"] = Array();
$activefilter["lastLogin"] = Array();
$activefilter["canLogin"] = Array(1);
$activefilter["active"] = Array();

if (isset($_COOKIE["filter_personen"]) && !isset($_REQUEST["filter_personen_set"])) $activefilter = json_decode(base64_decode($_COOKIE["filter_personen"]), true);
if (isset($_REQUEST["filter_personen"])) { if (is_array($_REQUEST["filter_personen_name"])) { $activefilter["name"] = $_REQUEST["filter_personen_name"]; } else {   $activefilter["name"] = Array(); } }
if (isset($_REQUEST["filter_personen_name"])) { if (is_array($_REQUEST["filter_personen_name"])) { $activefilter["name"] = $_REQUEST["filter_personen_name"]; } else {   $activefilter["name"] = Array(); } }
if (isset($_REQUEST["filter_personen_email"])) { if (is_array($_REQUEST["filter_personen_email"])) { $activefilter["email"] = $_REQUEST["filter_personen_email"]; } else { $activefilter["email"] = Array(); } }
if (isset($_REQUEST["filter_personen_unirzlogin"])) { if (is_array($_REQUEST["filter_personen_unirzlogin"])) { $activefilter["unirzlogin"] = $_REQUEST["filter_personen_unirzlogin"]; } else { $activefilter["unirzlogin"] = Array(); } }
if (isset($_REQUEST["filter_personen_username"])) { if (is_array($_REQUEST["filter_personen_username"])) { $activefilter["username"] = $_REQUEST["filter_personen_username"]; } else { $activefilter["username"] = Array(); } }
if (isset($_REQUEST["filter_personen_lastLogin"])) { if (is_array($_REQUEST["filter_personen_lastLogin"])) { $activefilter["lastLogin"] = $_REQUEST["filter_personen_lastLogin"]; } else { $activefilter["lastLogin"] = Array(); } }
if (isset($_REQUEST["filter_personen_canLogin"])) { if (is_array($_REQUEST["filter_personen_canLogin"])) { $activefilter["canLogin"] = $_REQUEST["filter_personen_canLogin"]; } else { $activefilter["canLogin"] = Array(1); } }
if (isset($_REQUEST["filter_personen_active"])) { if (is_array($_REQUEST["filter_personen_active"])) { $activefilter["active"] = $_REQUEST["filter_personen_active"]; } else { $activefilter["active"] = Array(); } }
setcookie("filter_personen", base64_encode(json_encode($activefilter)), 0);
$_COOKIE["filter_personen"] = base64_encode(json_encode($activefilter));

// Gremium filter
$activefilter = Array();
$activefilter["name"] = Array();
$activefilter["fakultaet"] = Array();
$activefilter["studiengang"] = Array();
$activefilter["studiengangabschluss"] = Array();
$activefilter["active"] = Array(1);
$activefilter["mitglieder"] = Array();
$activefilter["problem"] = Array();

if (isset($_COOKIE["filter_gremien"]) && !isset($_REQUEST["filter_gremien_set"])) $activefilter = json_decode(base64_decode($_COOKIE["filter_gremien"]), true);
if (isset($_REQUEST["filter_gremien_name"])) { if (is_array($_REQUEST["filter_gremien_name"])) { $activefilter["name"] = $_REQUEST["filter_gremien_name"]; } else {   $activefilter["name"] = Array(); } }
if (isset($_REQUEST["filter_gremien_fakultaet"])) { if (is_array($_REQUEST["filter_gremien_fakultaet"])) { $activefilter["fakultaet"] = $_REQUEST["filter_gremien_fakultaet"]; } else { $activefilter["fakultaet"] = Array(); } }
if (isset($_REQUEST["filter_gremien_studiengang"])) { if (is_array($_REQUEST["filter_gremien_studiengang"])) { $activefilter["studiengang"] = $_REQUEST["filter_gremien_studiengang"]; } else { $activefilter["studiengang"] = Array(); } }
if (isset($_REQUEST["filter_gremien_studiengangabschluss"])) { if (is_array($_REQUEST["filter_gremien_studiengangabschluss"])) { $activefilter["studiengangabschluss"] = $_REQUEST["filter_gremien_studiengangabschluss"]; } else { $activefilter["studiengangabschluss"] = Array(); } }
if (isset($_REQUEST["filter_gremien_active"])) { if (is_array($_REQUEST["filter_gremien_active"])) { $activefilter["active"] = $_REQUEST["filter_gremien_active"]; } else { $activefilter["active"] = Array(1); } }
if (isset($_REQUEST["filter_gremien_mitglieder"])) { if (is_array($_REQUEST["filter_gremien_mitglieder"])) { $activefilter["mitglieder"] = $_REQUEST["filter_gremien_mitglieder"]; } else {   $activefilter["mitglieder"] = Array(); } }
if (isset($_REQUEST["filter_gremien_problem"])) { if (is_array($_REQUEST["filter_gremien_problem"])) { $activefilter["problem"] = $_REQUEST["filter_gremien_problem"]; } else {   $activefilter["problem"] = Array(); } }
setcookie("filter_gremien", base64_encode(json_encode($activefilter)), 0);
$_COOKIE["filter_gremien"] = base64_encode(json_encode($activefilter));

foreach ($msgs as $msg):
  echo "<b class=\"msg\">".htmlspecialchars($msg)."</b>\n";
endforeach;

$script[] = '$( "#tabs" ).tabs();';
$script[] = 'function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      $("#waitDialog").dialog("close");
      alert(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
};';
$script[] = '
$(function() {
  dlg = $("<div id=\"waitDialog\" title=\"Bitte warten\">Bitte warten, die Daten werden verarbeitet. Dies kann einen Moment dauern.</div>");
  dlg.appendTo("body");
  dlg.dialog({ autoOpen: false, height: "auto", modal: true, width: "auto", closeOnEscape: false });
});';
$script[] = '
$( "form" ).submit(function (ev) {
    var action = $(this).attr("action");
    if ($(this).find("input[name=action]").length + $(this).find("select[name=action]").length == 0) { return true; }
    var close = $(this).find("input[type=reset]");
    var data = new FormData(this);
    data.append("ajax", 1);
    $("#waitDialog").dialog("open");
    $.ajax({
      url: action,
      data: data,
      cache: false,
      contentType: false,
      processData: false,
      type: "POST"
    })
    .success(function (values, status, req) {
       $("#waitDialog").dialog("close");
       if (typeof(values) == "string") {
         alert(values);
         return;
       }
       var txt = "Die Daten wurden erfolgreich gespeichert.\nSoll die Seite mit den geänderten Daten neu geladen werden?";
       if (values.msgs && values.msgs.length > 0) {
         if (values.ret) {
           txt = values.msgs.join("\n")+"\n"+txt;
         } else {
           alert(values.msgs.join("\n"));
         }
       }
       if (values.ret && confirm(txt)) {
         var q = "&x=" + Math.random();
         if (action.indexOf("?") != -1) {
           var actions = action.split("?", 2);
           action = actions[0] + "?" + q + "&" + actions[1];
         } else if (action.indexOf("#") != -1) {
           var actions = action.split("#", 2);
           action = actions[0] + "?" + q + "#" + actions[1];
         } else {
           action = action + "?" + q;
         }
         self.location.replace(action);
       } else {
         if (values.ret && close.length == 1) {
           close.click();
         }
       }
     })
    .error(xpAjaxErrorHandler);
    return false;
   });';

global $scripting;
if (isset($_REQUEST["javascript"])) {
  setcookie("javascript",$_REQUEST["javascript"]);
  $_COOKIE["javascript"] = $_REQUEST["javascript"];
}
$scripting = (isset($_COOKIE["javascript"]) && ($_COOKIE["javascript"] == 1));

function addTabHead($name, $titel) {
 global $scripting;
 ?> <li aria-controls="<?php echo htmlspecialchars($name);?>"><a href="<?php echo htmlspecialchars($scripting ? $_SERVER["PHP_SELF"]."?tab=".urlencode($name) : "#$name"); ?>"><?php echo htmlspecialchars($titel);?></a></li> <?php
}

if (isset($_REQUEST["tab"])) {
  switch($_REQUEST["tab"]) {
  case "person":
  require "../template/admin_personen.php";
  break;
  case "gremium":
  require "../template/admin_gremien.php";
  break;
  case "gruppe":
  require "../template/admin_gruppen.php";
  break;
  case "mailingliste":
  require "../template/admin_mailinglisten.php";
  break;
  default:
  die("invalid tab name");
  }
?>
  <script type="text/javascript">
    <?php  echo implode("\n", array_unique($script)); ?>
  </script>
<?php
  exit;
}

if (!$scripting) {
?><script type="text/javascript">
   self.location.replace("<?php echo $_SERVER["PHP_SELF"];?>?javascript=1");
  </script>
<?php
}


?>

<h2>Verwaltung studentisches Gremieninformationssystem (sGIS)</h2>

<div id="tabs">
 <ul>
  <?php  addTabHead("person", "Personen"); ?>
  <?php  addTabHead("gremium", "Gremien und Rollen"); ?>
  <?php  addTabHead("gruppe", "Gruppen"); ?>
  <?php  addTabHead("mailingliste", "Mailinglisten"); ?>
  <li><a href="#export">Export</a></li>
  <li><a href="#hilfe">Hilfe</a></li>
 </ul>

<?php
if (!$scripting) {
  require "../template/admin_personen.php";
  require "../template/admin_gremien.php";
  require "../template/admin_gruppen.php";
  require "../template/admin_mailinglisten.php";
}
?>

<div id="export">
<a name="export"></a>
<noscript><h3>Export</h3></noscript>
<ul>
 <li><a href="export-mailingliste.php">Mailinglisten</a></li>
 <li><a href="export-wiki.php">Mitgliederlisten im Wiki</a></li>
 <li><a href="export-spi.php">Mitgliederlisten im sPi</a></li>
 <li>Kalender (DAViCal) &rArr; wird beim Login automatisch synchronisiert, zu übernehmende Gruppen müssen manuell im DAViCal angelegt (eingerichtet) werden</li>
 <li>OwnCloud &rArr; wird beim Login automatisch synchronisiert, Gruppen werden automatisch angelegt, Gruppenordner (und deren Eigentümer für Quota) müssen jedoch manuell erstellt und frei gegeben werden.</li>
 <li><a href="export-ods.php">Download von Personen/Mitgliedschaften als ODS</a></li>
 <li>Gremienbescheinigung als PDF</li>
</ul>
</div>

<div id="hilfe">
<a name="hilfe"></a>
<noscript><h3>Hilfe</h3></noscript>

<ul>
 <li>Datenschema: Person &rArr; Rolle in Gremium während Zeitraum &rArr; Gruppen und Mailinglisten
 <li>Erfasst wird, wer wann in welchem Gremium aktiv war. Daher sollen Zeiten vergangener Gremienaktivität nicht gelöscht, sonder für etwaige spätere Gremienbescheinigungen vorgehalten werden.</li>
 <li>UniRZ-Login vs. eMail: Die Koppelung Uni-Account und Person im sGIS erfolgt wahlweise über die eMail-Adresse oder das Uni-Login. Das Uni-Login wird bevorzugt verwendet, um sicherzustellen, dass auch nach einer erneuten Vergabe der eMail-Adresse an eine andere Person diese nicht auf die alten Daten zugreifen kann.
 <li>Nutzername/Passwort: für Login unabhängig vom Uni-Login (sGIS-Login), beispw. auch im Kalender oder OwnCloud
 <li>Login-erlaubt: alte Datensätze (von nicht-mehr-Studenten) und Dummy-Datensätze (beispw. für stura@tu-ilmenau.de) können darüber gesperrt werden. Dies bewirkt, dass die jeweiligen Inhaber nicht über die sGIS-Zugangsdaten Zugriffs aufs Wiki usw. bekommen. Wenn (nicht) erlaubt, kann über die Gruppe (canLogin) cannotLogin eine zeitlich beschränkte Ausnahme definiert werden.
</ul>

</div>
</div>

<script type="text/javascript">
<?php echo implode("\n", array_unique($script)); ?>
</script>

<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull;
<a href="index.php">Selbstauskunft</a>

<?php
if ($scripting):
?>
<noscript>
  &bull; <a href="<?php echo $_SERVER["PHP_SELF"];?>?javascript=0">JavaScript deaktivieren.</a>
</noscript>
<?php
endif;
?>

<?php
require "../template/footer.tpl";
