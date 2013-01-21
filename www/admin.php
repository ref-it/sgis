<?php

global $attributes, $logoutUrl, $ADMINGROUP;
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

$validcaptcha = false;
if (isset($_REQUEST["captcha"]) && !isset($_REQUEST["tab"])) {
 if (empty($_REQUEST["captchaId"])) { die("empty captcha id supplied"); }
 $validcaptcha = Securimage::checkByCaptchaId($_REQUEST["captchaId"], $_REQUEST["captcha"]);
}

if (isset($_REQUEST["captchaId"]) && isset($_REQUEST["tab"])) {
  $captchaId = $_REQUEST["captchaId"];
} else {
  $captchaId = Securimage::getCaptchaId();
}
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
  if (isset($_REQUEST["captcha"]) && isset($_REQUEST["tab"])) {
    $captcha = $_REQUEST["captcha"];
  } else {
    $captcha = "";
  }
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
  case "rolle_gremium.insert":
   $ret = dbGremiumInsertRolle($_POST["gremium_id"],$_POST["name"],$_POST["active"]);
   $msgs[] = "Rolle wurde angelegt.";
  break;
  case "rolle_gremium.update":
   $ret = dbGremiumUpdateRolle($_POST["id"], $_POST["name"],$_POST["active"]);
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
 if ($ret && !isset($_REQUEST["ajax"])) {
  $query = "captchaId=".urlencode($captchaId)."&captcha=".urlencode($captcha);
  foreach ($msgs as $msg) {
   $query .= "&msgs[]=".urlencode($msg);
  }
  header("Location: ".$_SERVER["PHP_SELF"]."?".$query);
  exit;
 }
}

if (isset($_REQUEST["ajax"])) {
  $result = Array();
  $result["id"] = $captchaId;
  $result["captcha"] = $captcha;
  $result["img"] = base64_encode($imgBinary);
  $result["mime"] = "image/png";
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

if (isset($_COOKIE["filter_personen"])) $activefilter = json_decode(base64_decode($_COOKIE["filter_personen"]), true);
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

if (isset($_COOKIE["filter_gremien"])) $activefilter = json_decode(base64_decode($_COOKIE["filter_gremien"]), true);
if (isset($_REQUEST["filter_gremien_name"])) { if (is_array($_REQUEST["filter_gremien_name"])) { $activefilter["name"] = $_REQUEST["filter_gremien_name"]; } else {   $activefilter["name"] = Array(); } }
if (isset($_REQUEST["filter_gremien_fakultaet"])) { if (is_array($_REQUEST["filter_gremien_fakultaet"])) { $activefilter["fakultaet"] = $_REQUEST["filter_gremien_fakultaet"]; } else { $activefilter["fakultaet"] = Array(); } }
if (isset($_REQUEST["filter_gremien_studiengang"])) { if (is_array($_REQUEST["filter_gremien_studiengang"])) { $activefilter["studiengang"] = $_REQUEST["filter_gremien_studiengang"]; } else { $activefilter["studiengang"] = Array(); } }
if (isset($_REQUEST["filter_gremien_studiengangabschluss"])) { if (is_array($_REQUEST["filter_gremien_studiengangabschluss"])) { $activefilter["studiengangabschluss"] = $_REQUEST["filter_gremien_studiengangabschluss"]; } else { $activefilter["studiengangabschluss"] = Array(); } }
if (isset($_REQUEST["filter_gremien_active"])) { if (is_array($_REQUEST["filter_gremien_active"])) { $activefilter["active"] = $_REQUEST["filter_gremien_active"]; } else { $activefilter["active"] = Array(1); } }
setcookie("filter_gremien", base64_encode(json_encode($activefilter)), 0);
$_COOKIE["filter_gremien"] = base64_encode(json_encode($activefilter));

foreach ($msgs as $msg):
  echo "<b class=\"msg\">".htmlspecialchars($msg)."</b>\n";
endforeach;

$script[] = '$( "#tabs" ).tabs();';
$script[] = '$("<a href=\"#\">[Captcha neu laden]</a>").insertAfter($( "img.captcha" )).click(function() {
    $.post("captcha.php", {})
     .success(function (values, status, req) {
       $( "img.captcha" ).attr("src","data:"+values.meta+";base64,"+values.img);
       $( "input[name=captchaId]" ).val(values.id);
       $( "input[name=captcha]" ).val("");
       captcha = "";
       captchaId = values.id;
       captchaImg = "data:"+values.meta+";base64,"+values.img;
      })
     .error(xpAjaxErrorHandler);
     return false;
   });';
$script[] = 'function xpAjaxErrorHandler (jqXHR, textStatus, errorThrown) {
      alert(textStatus + "\n" + errorThrown + "\n" + jqXHR.responseText);
};';
$script[] = '$( "form" ).submit(function (ev) {
    var action = $(this).attr("action");
    if ($(this).find("input[name=action]").length + $(this).find("select[name=action]").length == 0) { return true; }
    var close = $(this).find("input[type=reset]");
    var data = $(this).serializeArray();
    data.push({"name": "ajax", "value" : "1"});
    console.log(data);
    $.post(action, data)
     .success(function (values, status, req) {
       var txt = "Die Daten wurden erfolgreich gespeichert.\nSoll die Seite mit den geänderten Daten neu geladen werden?";
       if (values.msgs && values.msgs.length > 0) {
         if (values.ret) {
           txt = values.msgs.join("\n")+"\n"+txt;
         } else {
           alert(values.msgs.join("\n"));
         }
       }
       if (values.ret && confirm(txt)) {
         var captchaPart = "captcha=" + values.captcha + "&captchaId=" + values.id;
         if (action.indexOf("?") != -1) {
           actions = action.split("?", 2);
           action = actions[0] + "?" + captchaPart + "&" + actions[1];
         } else {
           actions = action.split("#", 2);
           action = actions[0] + "?" + captchaPart + "#" + actions[1];
         }
         self.location.replace(action);
       } else {
         $( "img.captcha" ).attr("src","data:"+values.meta+";base64,"+values.img);
         $( "input[name=captchaId]" ).val(values.id);
         $( "input[name=captcha]" ).val(values.captcha);
         captcha = values.captcha;
         captchaId = values.id;
         captchaImg = "data:"+values.meta+";base64,"+values.img;
         if (close.length == 1) {
           close.click();
         }
       }
     })
     .error(xpAjaxErrorHandler);
    return false;
   });';
if (!isset($_REQUEST["tab"])) {
  $script[] = 'var captcha = "'.$captcha.'";';
  $script[] = 'var captchaId = "'.$captchaId.'";';
  $script[] = 'var captchaImg = "data:image/png;base64,'.base64_encode($imgBinary).'";';
} else {
  $script[] = '$(function() { 
    $( "img.captcha" ).attr("src",captchaImg);
    $( "input[name=captchaId]" ).val(captchaId);
    $( "input[name=captcha]" ).val(captcha);
});';
}

global $scripting, $captcha, $captchaId;
if (isset($_REQUEST["javascript"])) {
  setcookie("javascript",$_REQUEST["javascript"]);
  $_COOKIE["javascript"] = $_REQUEST["javascript"];
}
$scripting = (isset($_COOKIE["javascript"]) && ($_COOKIE["javascript"] == 1));

function addTabHead($name, $titel) {
 global $scripting, $captcha, $captchaId;
 ?> <li aria-controls="<?=htmlspecialchars($name);?>"><a href="<?=htmlspecialchars($scripting ? $_SERVER["PHP_SELF"]."?tab=".urlencode($name)."&captcha=$captcha&captchaId=$captchaId" : "#$name"); ?>"><?=htmlspecialchars($titel);?></a></li> <?
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

if (!$scripting) {
?><script type="text/javascript">
   self.location.replace("<?=$_SERVER["PHP_SELF"];?>?javascript=1");
  </script>
<?
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
if ($scripting):
?>
<noscript>
  &bull; <a href="<?=$_SERVER["PHP_SELF"];?>?javascript=0">JavaScript deaktivieren.</a>
</noscript>
<?
endif;
?>

<?php
require "../template/footer.tpl";
