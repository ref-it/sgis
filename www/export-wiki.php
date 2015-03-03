<?php
global $ADMINGROUP;
require_once "../lib/inc.all.php";

if (isset($_REQUEST["autoExportPW"])) {
  requireExportAutoPW();
} else {
  requireGroup($ADMINGROUP);
}

$validnonce = false;
if (isset($_REQUEST["nonce"]) && $_REQUEST["nonce"] === $nonce) {
 $validnonce = true;
}

$rollen = getAlleRolle();
$mapping = Array();
$name_gremien = Array();
$name_rollen = Array();

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach ($rollen as $rolle) {
  $wiki = $rolle["gremium_wiki_members"];
  if (empty($wiki)) continue;
  $gremium_id = $rolle["gremium_id"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping[$wiki][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
}

// generate wiki pages
function person2string($person) {
  $line = "  * [[:person:{$person["name"]}]] ";
  if (!empty($person["von"]) && !empty($person["bis"])) {
    $line .= "{$person["von"]} - {$person["bis"]}";
  } else if (!empty($person["von"])) {
    $line .= "seit {$person["von"]}";
  } else if (!empty($person["bis"])) {
    $line .= "bis {$person["bis"]}";
  }
  return "$line";
}

function getClient() {
  global $wikiUrl, $CA_file;
  static $wikiClient;
  if (!$wikiClient) {
    $request = new HTTP_Request2_SNI();
    $request->setConfig("ssl_cafile", $CA_file);
    $wikiClient = XML_RPC2_Client::create($wikiUrl."/lib/exe/xmlrpc.php", Array("httpRequest" => $request, "backend" => "php"));
  }
  return $wikiClient;
}

function fetchWikiPage($wiki) {
  try {
    $wikiClient = getClient();
    $method="wiki.getPage";
    return $wikiClient->$method($wiki);
  } catch (XML_RPC2_FaultException $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.': ' . $e->getMessage() );
  }
}

function writeWikiPage($wiki, $text) {
  try {
    $wikiClient = getClient();
    $method="wiki.putPage";
    return $wikiClient->$method($wiki, $text, Array());
  } catch (XML_RPC2_FaultException $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.': ' . $e->getMessage() );
  }
}

function cmpPerson($a, $b) {
  if ($a["bis"] < $b["bis"]) return -1;
  if ($a["bis"] > $b["bis"]) return 1;
  if ($a["von"] < $b["von"]) return -1;
  if ($a["von"] > $b["von"]) return 1;
  if ($a["email"] < $b["email"]) return -1;
  if ($a["email"] > $b["email"]) return 1;
  return 0;
}

foreach ($mapping as $wiki => $data) {
  $text = Array();
  foreach ($data as $gremium_id => $data2) {
    $g = $name_gremien[$gremium_id];
    $gname = preg_replace("/\s+/"," ",trim("{$g["gremium_name"]} {$g["gremium_fakultaet"]} {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]}"));
    $text[] = "====== $gname (studentische Mitglieder) (Ilmenau) ======";
    foreach ($data2 as $rolle_id => $personen) {
      $text[] = "";
      $r = $name_rollen[$gremium_id][$rolle_id];
      $text[] = "===== {$r["rolle_name"]} =====";
      if (!empty($personen["active"])) {
        $text[] = "==== aktuelle in {$r["rolle_name"]} in $gname ====";
        uasort($personen["active"], 'cmpPerson');
        foreach($personen["active"] as $person) {
          $text[] = person2string($person);
        }
        $text[] = "";
      }
      if (!empty($personen["inactive"])) {
        uasort($personen["inactive"], 'cmpPerson');
        $text[] = "==== ehemalige/zukünftige in {$r["rolle_name"]} in $gname ====";
        foreach($personen["inactive"] as $person) {
          $text[] = person2string($person);
        }
        $text[] = "";
      }
    }
  }
  $mapping[$wiki] = Array();
  $mapping[$wiki]["new"] = $text;
  if (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($wiki, $_POST["commit"]) && $validnonce) {
    writeWikiPage($wiki, base64_decode($_POST["text"][$wiki]));
  } elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && isset($_POST["commit"][$wiki])) {
    echo "<b class=\"msg\">CSRF Schutz.</b>";
  } elseif (!isset($_POST["commit"])) {
    $mapping[$wiki]["old"] = explode("\n",fetchWikiPage($wiki));
    $x = new Text_Diff('auto',Array($mapping[$wiki]["old"],$mapping[$wiki]["new"]));
    $y = new Text_Diff_Renderer_unified();
    $mapping[$wiki]["diff"] = $y->render($x);
  }
}

if (isset($_POST["commit"])) {
  header("Location: ${_SERVER["PHP_SELF"]}");
  die();
}

require_once "../template/header.tpl";


?>
<h2>Gremienmitgliedschaften im Wiki aktualisieren</h2>

<style type="text/css">
 td {vertical-align: top; }
 textarea {display: none; }
</style>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">
<table>
<tr><th></th><th>Seite</th><th>Änderung</th></tr>
<?php

global $wikiUrl;
$url = parse_url($wikiUrl);
$openUrl = http_build_url($url, Array(), HTTP_URL_STRIP_AUTH);
foreach ($mapping as $wiki => $data):
  echo "<tr>";
  echo " <td><input ".(($data["diff"] != "") ? "class=\"mls\"" : "")." type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($wiki)."\"></td>";
  echo " <td><a href=\"".htmlspecialchars($openUrl.str_replace(":","/",$wiki))."\">".htmlspecialchars($wiki)."</a></td>\n";
  echo " <td><pre>{$data["diff"]}</pre><input type=\"hidden\" readonly=readonly name=\"text[".htmlspecialchars($wiki)."]\" value=\"".base64_encode(implode("\n",$data["new"]))."\"></td>\n";
  echo "</tr>";
endforeach;

?></table>

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<a href="#" onClick="$('.mls').attr('checked',true); return false;">alle Änderungen auswählen</a>
<a href="#" onClick="$('.mls').attr('checked',false); return false;">keine Änderungen auswählen</a>
<input type="submit" value="Anwenden" name="submit"/>
<input type="reset" value="Zurücksetzen" name="reset"/>
<?php
if (isset($_REQUEST["autoExportPW"]))
  echo "<input type=\"hidden\" name=\"autoExportPW\" value=\"".htmlspecialchars($_REQUEST["autoExportPW"])."\">"
?>

</form>
<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull;
<a href="index.php">Selbstauskunft</a> &bull;
<a href="admin.php">Verwaltung</a>
<?php
require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
