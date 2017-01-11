<?php

global $ADMINGROUP;

set_time_limit(120);

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

function checkResult($url, $output) {
  if ($output == "") {
    echo "empty reply from $url\n";
    var_dump($output);
    die();
  }
  // password ok check
  if (strpos($output, '<INPUT TYPE="password" NAME="adminpw" SIZE="30">') !== FALSE)
    die("Fehler beim Abruf von $url - falsches Passwort.");
  if (strpos($output, 'Fehler beim Abonnieren:') !== FALSE) {
?><script>
w = window.open('','_blank','dependent=yes');
w.document.open();
w.document.write(unescape('<? echo rawurlencode($output); ?>'));
w.document.close();
</script><?php
    die("Fehler beim Abruf von $url - Person konnte nicht gefügt werden.");
  }
}

if (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0 && $validnonce) {
  $alle_mailinglisten = getMailinglisten();
  $writeRequests = Array();
  foreach ($alle_mailinglisten as $mailingliste) {
    $list = $mailingliste["address"];
    header("X-Progress: $list");
    if (!in_array($list, $_POST["commit"])) continue;
    $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"]);
    $password = $mailingliste["password"];

    if (isset($_POST["write"][$list])) {
      foreach ($_POST["write"][$list] as $settingsUrl => $settings) {
        $postFields = Array();
        $postFields["adminpw"] = $password;
        foreach ($settings as $field => $value) {
          if (is_array($value)) $value = implode("\n", $value);
          $postFields["$field"] = $value;
        }
        if ($settingsUrl == "members/add" && !isset($postFields["subscribees"])) continue;
        if ($settingsUrl == "members/remove" && !isset($postFields["unsubscribees"])) continue;
        $writeRequests[] = Array("url" => $url."/".$settingsUrl, "post" => $postFields);
      }
    }
  }

  $writeResults = multiCurlRequest($writeRequests);
  foreach ($writeResults as $id => $val) {
    // password ok check
    checkResult($writeRequests[$id]["url"], $val);
  }
  if (isset($_REQUEST["mailingliste_id"])) {
    header("Location: ${_SERVER["PHP_SELF"]}?mailingliste_id=".((int) $_REQUEST["mailingliste_id"]));
  } else {
    header("Location: ${_SERVER["PHP_SELF"]}");
  }
  die();
}

require "../template/header.tpl";
require "../template/admin.tpl";

if (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0 && !$validnonce) {
  echo "<b class=\"msg\">CSRF Schutz fehlgeschlagen</b><br/>\n";
}

?>
<h2>Mailinglistenmitgliedschaften aktualisieren</h2>
<?php

// fetch all members
// 1. get all letters
// 2. get all chunks
// 3. check results

$alle_mailinglisten = getMailinglisten();
$fetchRequests = Array();
foreach ($alle_mailinglisten as $id => &$mailingliste) {
  if (isset($_REQUEST["mailingliste_id"]) && ($mailingliste["id"] != (int) $_REQUEST["mailingliste_id"])) {
    unset($alle_mailinglisten[$id]);
    continue;
  }
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/members";
  $fetchRequests[] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "members");

  $config = getMailinglisteMailmanByMailinglisteId($mailingliste["id"]);
  $mailingliste["config"] = $config;
  $needRead = [];
  $canRead = [];

  foreach ($config as $cfg) {
    $url = strtolower(trim(trim($cfg["url"]),"/"));
    $field = strtolower(trim($cfg["field"]));
    $canRead[$url][$field] = true;
    switch ($cfg["mode"]) {
      case "set":
        $needRead[$url][$field] = false;
        break;
      case "increase-to":
      case "add":
        $needRead[$url][$field] = true;
        break;
      case "ignore":
        $needRead[$url][$field] = false;
        break;
      default:
        echo "<b class=\"msg\">Unbekannte Mailinglisten-Konfigurationsmodus: ".htmlspecialchars($cfg["mode"])."</b><br/>\n";
    }
  }

  foreach (array_keys($needRead) as $url) {
    $needRead[$url] = array_filter($needRead[$url]);
  }
  $needRead = array_filter($needRead);

  foreach($needRead as $url => $vars) {
    if ($url == "members/add" || $url == "members/remove") continue;

    $fetchUrl = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/".$url;
    $fetchRequests[] = Array("url" => $fetchUrl, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "settings", "settingsUrl" => $url);
  }

  $mailingliste["members"] = Array();
  $mailingliste["numMembers"] = 0;
  $mailingliste["oldSettings"] = Array();
  $mailingliste["newSettings"] = Array();
  $mailingliste["needRead"] = $needRead;
  $mailingliste["canRead"] = $canRead;
}

unset($mailingliste);
while (count($fetchRequests) > 0) {
  $fetchResults = multiCurlRequest($fetchRequests);
  $newFetchRequests = Array();
  foreach ($fetchResults as $id => $result) {
    checkResult($fetchRequests[$id]["url"], $result);
    $mailingliste_id = $fetchRequests[$id]["mailingliste"];
    unset($mailingliste);
    $mailingliste = &$alle_mailinglisten[$mailingliste_id];

    if ($fetchRequests[$id]["parser"] == "members") {
      $mailingliste["numMembers"] = parseMembersPage($result, $fetchRequests[$id]["url"], $mailingliste["members"]);
      $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/members";
      if (!isset($fetchRequests[$id]["letter"])) {
        // need to fetch per-letter page
        $letters = parseLettersPage($result, $url);
        foreach ($letters as $letter) {
          $newFetchRequests[] = Array("url" => $url.'?'.http_build_query(Array("letter" => $letter)),
                                      "post" => Array("adminpw" => $mailingliste["password"]),
                                      "mailingliste" => $mailingliste_id,
                                      "letter" => $letter,
                                      "parser" => "members");
        }
      } elseif (!isset($fetchRequests[$id]["chunk"])) {
        // need to fetch per-chunk page
        $letter = $fetchRequests[$id]["letter"];
        $chunks = parseChunksPage($result, $url);
        foreach ($chunks as $chunk) {
          $newFetchRequests[] = Array("url" => $url.'?'.http_build_query(Array("letter" => $letter, "chunk" => $chunk)),
                                      "post" => Array("adminpw" => $mailingliste["password"]),
                                      "mailingliste" => $mailingliste_id,
                                      "letter" => $letter,
                                      "chunk" => $chunk,
                                      "parser" => "members");
        }
      }
    }

    if ($fetchRequests[$id]["parser"] == "settings") {
      parseSettingsPage($result, $fetchRequests[$id]["url"], $fetchRequests[$id]["settingsUrl"], $mailingliste);
    }
  }
  $fetchRequests = $newFetchRequests;
}
unset($mailingliste);
foreach ($alle_mailinglisten as $id => &$mailingliste) {
  $mailingliste["members"] = array_unique($mailingliste["members"]);
  sort($mailingliste["members"]);
  if (count($mailingliste["members"]) != $mailingliste["numMembers"]) {
    die("Fehler bei Mailingliste {$mailingliste["address"]} : {$mailingliste["numMembers"]} Mitglieder erwartet, aber nur ".count($mailingliste["members"])." gefunden.");
  }

  $config = $mailingliste["config"];
  $newSettings = $mailingliste["newSettings"];
  $oldSettings = $mailingliste["oldSettings"];

  foreach ($config as $cfg) {
    $url = strtolower(trim(trim($cfg["url"]),"/"));
    $field = strtolower(trim($cfg["field"]));
    switch ($cfg["mode"]) {
      case "set":
        $newSettings[$url][$field] = $cfg["value"];
        break;
      case "increase-to":
        if (!isset($newSettings[$url][$field]))
          $newSettings[$url][$field]= $oldSettings[$url][$field];
        $newSettings[$url][$field] = max((int) $cfg["value"], (int)$oldSettings[$url][$field]);
        break;
      case "add":
        if (!isset($newSettings[$url][$field]))
          $newSettings[$url][$field]= $oldSettings[$url][$field];

        $value = explode("\n", $newSettings[$url][$field]);
        if (!in_array($cfg["value"], $value)) {
          $value[] = $cfg["value"];
        }
        $value = implode("\n", $value);
        $newSettings[$url][$field] = $value;
        unset($value);
        break;
      case "ignore":
        if (isset($newSettings[$url][$field])) {
          unset($newSettings[$url][$field]);
        }
        break;
      default:
        echo "<b class=\"msg\">Unbekannte Mailinglisten-Konfigurationsmodus: ".htmlspecialchars($cfg["mode"])."</b><br/>\n";
    }
  }
  $mailingliste["newSettings"] = $newSettings;
}
unset($mailingliste);

function parseSettingsPage($output, $url, $settingsUrl, &$mailingliste) {
  $matches = Array();

  $needRead = $mailingliste["needRead"];
  $canRead = $mailingliste["canRead"];
  $oldSettings = &$mailingliste["oldSettings"];

  if (!isset($needRead[$settingsUrl])) return;
  $needReadFields = array_keys($needRead[$settingsUrl]);
  $canReadFields = array_keys($canRead[$settingsUrl]);

  $doc = new DOMDocument();
  @$doc->loadHTML($output);
  $nodes = $doc->getElementsByTagName('input');
  for ($i=0; $i < $nodes->length; $i++) {
    $node = $nodes->item($i);
    $name = $node->getAttribute("name");
    if (!in_array($name, $needReadFields) && !in_array($name, $canReadFields))
      continue;
    $type = $node->getAttribute("type");
    if ((strtolower($type) == "radio") && !$node->hasAttribute("checked"))
      continue;
    $value = $node->getAttribute("value");
    $oldSettings[$settingsUrl][$name] = $value;
    unset($needRead[$settingsUrl][$name]);
  }
  $nodes = $doc->getElementsByTagName('textarea');
  for ($i=0; $i < $nodes->length; $i++) {
    $node = $nodes->item($i);
    $name = $node->getAttribute("name");
    if (!in_array($name, $needReadFields) && !in_array($name, $canReadFields))
      continue;
    $value = $node->nodeValue;
    $oldSettings[$settingsUrl][$name] = $value;
    unset($needRead[$settingsUrl][$name]);
  }

  if (count($needRead[$settingsUrl]) > 0) {
    die("Missing ".implode(",", array_keys($needRead[$settingsUrl]))." in $url");
  }
}

function parseMembersPage($output, $url, &$members) {
  $matches = Array();
  preg_match_all( '/<INPUT name="user" type="HIDDEN" value="([^"]*)" >/', $output, $matches);
  foreach ($matches[1] as $member) {
    $members[] = urldecode($member);
  }
  // num total member
  $match = preg_quote("<td COLSPAN=\"11\" BGCOLOR=\"#dddddd\"><center><em>","/")."([0-9]*) Mitglieder insgesamt(, [0-9]* werden angezeigt)?".preg_quote("</em></center></td>","/");
  if (!(preg_match( "/$match/", $output, $matches) === 1)) {
     echo "got \"$output\"\n";
        die("Fehler beim Abruf von $url - keine Mitgliederanzahl - falsche Sprache?");
   }
  return $matches[1]; // numMembers
}

function parseLettersPage($output, $url) {
  $matches = Array();
  $match = preg_quote("a href=\"$url?letter=","/")."(.)".preg_quote("\"","/");
  $matches = Array();
  preg_match_all( "/$match/i", $output, $matches);
  return $matches[1];
}

function parseChunksPage($output, $url) {
  // chunks required
  $match = preg_quote("a href=\"$url?letter=","/").".".preg_quote("&chunk=","/")."([0-9]*)".preg_quote("\"","/");
  $matches = Array();
  preg_match_all( "/$match/i", $output, $matches);
  return $matches[1];
}

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<table class="table table-striped">
<tr><th></th><th>Mailingliste</th>
    <th>Einfügen</th><th>Entfernen</th> 
    <!-- <th>IST</th><th>SOLL</th> -->
    <th>Einstellungen</th>
</tr>
<?php
foreach($alle_mailinglisten as $mailingliste) {
  $members = $mailingliste["members"];
  $dbmembers = getMailinglistePerson($mailingliste["id"]);
  foreach ($dbmembers as $i => $e) { $dbmembers[$i] = strtolower($e); }
  foreach ($members as $i => $e) { $members[$i] = strtolower($e); }
  $addmembers = array_diff($dbmembers, $members);
  $delmembers = array_diff($members, $dbmembers);

  echo "<tr>";
  echo "<td valign=\"top\"><input class=\"mls\" type=\"checkbox\" checked=\"checked\" name=\"commit[]\" value=\"".htmlspecialchars($mailingliste["address"])."\"></td>";
  echo "<td valign=\"top\"><a href=\"".htmlspecialchars($mailingliste["url"])."\">".htmlspecialchars($mailingliste["address"])."</a></td>\n";
  echo "<td valign=\"top\">";
  if (count($addmembers) > 0) {
    echo "<ul>";
    foreach ($addmembers as $member) {
      echo "<li>$member <input type=\"hidden\" name=\"write[".htmlspecialchars($mailingliste["address"])."][members/add][subscribees][]\" value=\"".htmlspecialchars($member)."\"></li>";
    }
    echo "</ul>";
  }
  echo "</td><td valign=\"top\">";
  if (count($delmembers) > 0) {
    echo "<ul>";
    foreach ($delmembers as $member) {
      echo "<li>$member <input type=\"hidden\" name=\"write[".htmlspecialchars($mailingliste["address"])."][members/remove][unsubscribees][]\" value=\"".htmlspecialchars($member)."\"></li>";
    }
    echo "</ul>";
  }
  echo "</td>";
/*
  echo "<td>";
if (count($members) > 0):
  echo "<ul>";
foreach ($members as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
endif;
  echo "</td><td>";
if (count($dbmembers) > 0):
  echo "<ul>";
foreach ($dbmembers as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
endif;
  echo "</td>"
*/

  echo "<td valign=\"top\">";
  echo "<table class=\"table table-striped\">";
  foreach ($mailingliste["newSettings"] as $url => $settings) {
    foreach ($settings as $field => $newValue) {
      if (isset($mailingliste["oldSettings"][$url][$field])) {
        $oldValue = $mailingliste["oldSettings"][$url][$field];
        if ($newValue == $oldValue) continue;
      } else {
        $oldValue = false;
      }

      $multiline = (strpos($newValue,"\n") !== false);
      if ($oldValue !== false) {
        $multiline |= (strpos($oldValue,"\n") !== false);
      }

      if ($multiline) {
        $rows = 3; $cols = 10;
        $newValue = explode("\n", $newValue);
        foreach ($newValue as $line) $cols = max($cols, strlen($line)+5);
        $rows = max($rows, count($newValue)+1);
        if ($oldValue !== false) {
          $oldValue = explode("\n", $oldValue);
          $rows = max($rows, count($oldValue)+1);
          foreach ($oldValue as $line) $cols = max($cols, strlen($line)+5);
        }
      }

      if ($oldValue !== false) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($url)."</td>";
        echo "<td>".htmlspecialchars($field)."</td><td>(ALT)</td><td valign=\"top\">";
        if ($multiline) {
          echo "<textarea readonly=\"readonly\" name=\"oldSettings[".htmlspecialchars($mailingliste["address"])."][".htmlspecialchars($url)."][".htmlspecialchars($field)."]\" rows=$rows cols=$cols>";
          echo htmlspecialchars(implode("\n", $oldValue));
          echo "</textarea>";
        } else {
          echo "<input readonly=\"readonly\" name=\"oldSettings[".htmlspecialchars($mailingliste["address"])."][".htmlspecialchars($url)."][".htmlspecialchars($field)."]\" value=\"".htmlspecialchars($oldValue)."\">";
        }
        echo "</td></tr>";
      }

      echo "<tr>";
      echo "<td>".htmlspecialchars($url)."</td>";
      echo "<td>".htmlspecialchars($field)."</td><td>(NEU)</td><td valign=\"top\">";
      if ($multiline) {
        echo "<textarea readonly=\"readonly\" name=\"write[".htmlspecialchars($mailingliste["address"])."][".htmlspecialchars($url)."][".htmlspecialchars($field)."]\" rows=$rows cols=$cols>";
        echo htmlspecialchars(implode("\n", $newValue));
        echo "</textarea>";
      } else {
        echo "<input readonly=\"readonly\" name=\"write[".htmlspecialchars($mailingliste["address"])."][".htmlspecialchars($url)."][".htmlspecialchars($field)."]\" value=\"".htmlspecialchars($newValue)."\">";
      }

      echo "</td></tr>";
    }
  }

  echo "</table>";
  echo "</td>";

  echo "</tr>";
}

?></table>

<a class="btn btn-default" href="#" onClick="$('.mls').attr('checked',true); return false;">alle auswählen</a>
<a class="btn btn-default" href="#" onClick="$('.mls').attr('checked',false); return false;">keine auswählen</a>
<input class="btn btn-primary" type="submit" value="Anwenden" name="submit"/>
<input class="btn btn-danger" type="reset" value="Zurücksetzen" name="reset"/>
<br/>
<br/>
<?php
if (isset($_REQUEST["autoExportPW"]))
  echo "<input type=\"hidden\" name=\"autoExportPW\" value=\"".htmlspecialchars($_REQUEST["autoExportPW"])."\">";

if (isset($_REQUEST["mailingliste_id"]))
  echo "<input type=\"hidden\" name=\"mailingliste_id\" value=\"".htmlspecialchars($_REQUEST["mailingliste_id"])."\">";
?>

</form>
<?php
require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
