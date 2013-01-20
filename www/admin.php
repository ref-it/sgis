<?php

global $attributes, $logoutUrl, $ADMINGROUP;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

if (!isset($_REQUEST["tab"])) {
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

$validcaptcha = false;
if (isset($_REQUEST["captcha"])) {
 if (empty($_REQUEST["captchaId"])) { die("empty captcha id supplied"); }
 $validcaptcha = Securimage::checkByCaptchaId($_REQUEST["captchaId"], $_REQUEST["captcha"]);
}

$captchaId = Securimage::getCaptchaId();
$options = array('captchaId'  => $captchaId, 'no_session' => true, 'no_exit' => true, 'send_headers' => false);
$captcha = new Securimage($options);
ob_start();   // start the output buffer
$captcha->show();
$imgBinary = ob_get_contents(); // get contents of the buffer
ob_end_clean(); // turn off buffering and clear the buffer
if ($validcaptcha) {
  $captcha = $captcha->getCode();
  $captcha = $captcha["code_disp"];
} else {
  $captcha = "";
}

if (isset($_POST["action"])) {
 $ret = false;
 if (!$validcaptcha) {
  $msgs[] = "Falsches Captcha!";
 } else {
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
   $ret = dbPersonUpdate($_POST["id"],$_POST["name"],$_POST["email"],$_POST["unirzlogin"],$_POST["username"],$_POST["password"],$_POST["canlogin"]);
   $msgs[] = "Person wurde aktualisiert.";
  break;
  case "person.insert":
   $ret = dbPersonInsert($_POST["name"],$_POST["email"],$_POST["unirzlogin"],$_POST["username"],$_POST["password"],$_POST["canlogin"]);
   $msgs[] = "Person wurde angelegt.";
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
   $ret = dbGremiumInsert($_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"]);
   $msgs[] = "Gremium wurde angelegt.";
  break;
  case "gremium.update":
   $ret = dbGremiumUpdate($_POST["id"], $_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"]);
   $msgs[] = "Gremium wurde geändert.";
  break;
  case "gremium.delete":
   $ret = dbGremiumDelete($_POST["id"]);
   $msgs[] = "Gremium wurde entfernt.";
  break;
  case "rolle_gremium.insert":
   $ret = dbGremiumInsertRolle($_POST["gremium_id"],$_POST["name"]);
   $msgs[] = "Rolle wurde angelegt.";
  break;
  case "rolle_gremium.update":
   $ret = dbGremiumUpdateRolle($_POST["id"], $_POST["name"]);
   $msgs[] = "Rolle wurde umbenannt.";
  break;
  case "rolle_gremium.delete":
   $ret = dbGremiumDeleteRolle($_POST["id"]);
   $msgs[] = "Rolle wurde entfernt.";
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
   die("Aktion nicht bekannt.");
  endswitch;
 }
 if ($ret) {
  $query = "captchaId=".urlencode($captchaId)."&captcha=".urlencode($captcha);
  foreach ($msgs as $msg) {
   $query .= "&msgs[]=".urlencode($msg);
  }
  header("Location: ".$_SERVER["PHP_SELF"]."?".$query);
  exit;
 }
}

foreach ($msgs as $msg):
  echo "<b class=\"msg\">".htmlspecialchars($msg)."</b>\n";
endforeach;

$script[] = '$( "#tabs" ).tabs();';

global $scripting;
$scripting = true;

function addTabHead($name, $titel) {
 global $scripting;
 ?> <li aria-controls="<?=htmlspecialchars($name);?>"><a href="<?=htmlspecialchars($scripting ? $_SERVER["PHP_SELF"]."?tab=".urlencode($name) : "#$name"); ?>"><?=htmlspecialchars($titel);?></a></li> <?
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
    <? echo implode("\n", array_unique($script)); ?>
  </script>
<?
  exit;
}

?>

<h2>Verwaltung studentisches Gremieninformationssystem (sGIS)</h2>

<div id="tabs">
 <ul>
  <? addTabHead("person", "Personen"); ?>
  <? addTabHead("gremium", "Gremien und Rollen"); ?>
  <? addTabHead("gruppe", "Gruppen"); ?>
  <? addTabHead("mailingliste", "Mailinglisten"); ?>
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
 <li>Kalender (DAViCal) &rArr; wird beim Login automatisch synchronisiert, zu übernehmende Gruppen müssen manuell im DAViCal angelegt (eingerichtet) werden</li>
 <li>Mitgliederlisten (Wiki)</li>
 <li>OwnCloud</li>
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
<? echo implode("\n", array_unique($script)); ?>
</script>

<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull;
<a href="index.php">Selbstauskunft</a>

<?php
require "../template/footer.tpl";
