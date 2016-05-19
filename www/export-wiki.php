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
$mapping_table = Array();
$mapping_fulltable = Array();
$mapping_nachbesetzung = Array();
$name_gremien = Array();
$name_rollen = Array();

$wikiprefix = "sgis:mitglieder:";

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle["gremium_wiki_members"]);
  if (empty($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
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

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle["gremium_wiki_members_table"]);
  if (empty($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id]["active"] = [];
  $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id]["inactive"] = [];
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach (["gremium_wiki_members_fulltable","gremium_wiki_members_fulltable2"] as $key) {
 foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle[$key]);
  if (empty($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id]["active"] = [];
  $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id]["inactive"] = [];
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
 }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
$gremium_isempty = [];
foreach ($rollen as $rolle) {
  if (!$rolle["rolle_active"]) continue;
  if (!$rolle["gremium_active"]) continue;

  $rolle_id = $rolle["rolle_id"];
  $gremium_id = $rolle["gremium_id"];
  $personen = getRollePersonen($rolle_id);

  if (!isset($gremium_isempty[$gremium_id]))
    $gremium_isempty[$gremium_id] = true;

  foreach ($personen as $person) {
    if ($person["active"] != 1)
      continue;
    $gremium_isempty[$gremium_id] = false;
  }
}

foreach ($rollen as $rolle) {
  if (!$rolle["rolle_active"]) continue;
  if (!$rolle["gremium_active"]) continue;

  $wiki = cleanID(":sgis:nachbesetzung:".$rolle["rolle_wahlDurchWikiSuffix"]);
  $wiki2 = cleanID(":sgis:nachbesetzung");

  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $rolle_id = $rolle["rolle_id"];
  $wahlPeriodeDays = $rolle["rolle_wahlPeriodeDays"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $gremium_sort = $gremium_fak . "|" . $gremium_name;

  $isempty2 = $rolle["rolle_numPlatz"];
  $lastUpdate = NULL;
  foreach ($personen as $person) {
    if ($person["active"] != 1)
      continue;
    $isempty2--;
    $lastCheck = $person["lastCheck"];
    if ($lastCheck === NULL) $lastCheck = $person["von"];
    if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
    $lastUpdate = $lastCheck;
  }

  /* wenn Plätze leer sind ODER das Gremium länger nicht angefasst wurde, soll es gelistet werden */
  if (($isempty2 <= 0) && (!$gremium_isempty[$gremium_id]) && ($lastUpdate !== NULL) && ($lastUpdate > date("Y-m-d", time() - $wahlPeriodeDays * 24 * 3600)))
    continue;
  if (($rolle["rolle_numPlatz"] == 0) && (!$gremium_isempty[$gremium_id]))
    continue;

  if ($wiki !== ":sgis:nachbesetzung:")
    $mapping_nachbesetzung[$wiki][$gremium_sort][$gremium_id][$rolle_id]["active"] = [];
  $mapping_nachbesetzung[$wiki2][$gremium_sort][$gremium_id][$rolle_id]["active"] = [];

  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    if ($person["active"] != 1)
      continue;
    if ($wiki !== ":sgis:nachbesetzung:")
      $mapping_nachbesetzung[$wiki][$gremium_sort][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
    $mapping_nachbesetzung[$wiki2][$gremium_sort][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
  }
}

// generate wiki pages
function person2string($person) {
  $line = "[[:person:{$person["name"]}]] ";
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

$pages = Array();

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
          $text[] = "  * ".person2string($person);
        }
        $text[] = "";
      }
      if (!empty($personen["inactive"])) {
        uasort($personen["inactive"], 'cmpPerson');
        $text[] = "==== ehemalige/zukünftige in {$r["rolle_name"]} in $gname ====";
        foreach($personen["inactive"] as $person) {
          $text[] = "  * ".person2string($person);
        }
        $text[] = "";
      }
    }
  }
  $pages[$wiki]["new"] = $text;
}

foreach ($mapping_table as $wiki => $data) {
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

  ksort($data);
  foreach ($data as $gremium_name => $data1) {

    $gname = trim($gremium_name);
    $text[] = "====== $gname (studentische Mitglieder) (Ilmenau) ======";
    $text[] = "";
    $text[] = "^ Fak ^ Studiengang ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| {$g["gremium_fakultaet"]} | {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }

        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

    $text[] = "";

  } /* $gremium_name */
  $pages[$wiki]["new"] = $text;
}

foreach ($mapping_fulltable as $wiki => $data) {
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $text[] = "^ Gremium ^ Fak ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

  ksort($data);
  foreach ($data as $gremium_fak => $data1) {
    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];
      $gname = trim($g["gremium_name"]);

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| $gname {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$g["gremium_fakultaet"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }
        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if ($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

  } /* $gremium_name */

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

foreach ($mapping_nachbesetzung as $wiki => $data) {
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $text[] = "^ Gremium ^ Fak ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

  ksort($data);
  foreach ($data as $gremium_sort => $data1) {
    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];
      $gname = trim($g["gremium_name"]);

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| $gname {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$g["gremium_fakultaet"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }
        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if ($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

  } /* $gremium_name */

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

foreach (array_keys($pages) as $wiki) {
  if (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($wiki, $_POST["commit"]) && $validnonce) {
    writeWikiPage($wiki, base64_decode($_POST["text"][$wiki]));
  } elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && isset($_POST["commit"][$wiki])) {
    echo "<b class=\"msg\">CSRF Schutz.</b>";
  } elseif (!isset($_POST["commit"])) {
    $pages[$wiki]["old"] = explode("\n",fetchWikiPage($wiki));
    $x = new Text_Diff('auto',Array($pages[$wiki]["old"],$pages[$wiki]["new"]));
    $y = new Text_Diff_Renderer_unified();
    $pages[$wiki]["diff"] = $y->render($x);
  }
}

if (isset($_POST["commit"])) {
  header("Location: ${_SERVER["PHP_SELF"]}");
  die();
}

require_once "../template/header-old.tpl";


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
foreach ($pages as $wiki => $data):
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
