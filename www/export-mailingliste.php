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

function checkResult($url, $output) {
  if ($output == "") {
    echo "empty reply\n";
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
    if (true) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["subscribe_policy"] = 2; // Bestätigung / Genehmigung
      $postFields["unsubscribe_policy"] = 1; // Genehmigung
      $postFields["private_roster"] = 2; // nur Admin darf Mitglieder auflisten
      $writeRequests[] = Array("url" => $url."/privacy/subscribing", "post" => $postFields);

      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["send_reminders"] = 0; // no password reminder
      $postFields["admin_notify_mchanges"] = 1; // tell konsul about new/lost members
      $postFields["include_rfc2369_headers"] = 1; // add List-Id Header
      $writeRequests[] = Array("url" => $url."/general", "post" => $postFields);

      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["archive_private"] = 1; // private archive
      $writeRequests[] = Array("url" => $url."/archive", "post" => $postFields);
    }
    if (isset($_POST["addmember"][$list])) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["subscribe_or_invite"] = 0; # abbonieren
      $postFields["send_welcome_msg_to_this_batch"] = 0; # don't send welcome
      $postFields["send_notifications_to_list_owner"] = 1; # send notify
      $postFields["subscribees"] = join("\n", $_POST["addmember"][$list])."\n";
      $postFields["invitation"] = "";
      $writeRequests[] = Array("url" => $url."/members/add", "post" => $postFields);
    }
    if (isset($_POST["delmember"][$list])) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["send_unsub_ack_to_this_batch"] = 0; # don't tell unsubscriber
      $postFields["send_unsub_notifications_to_list_owner"] = 1; # tell owner
      $postFields["unsubscribees"] = join("\n", $_POST["delmember"][$list])."\n";
      $writeRequests[] = Array("url" => $url."/members/remove", "post" => $postFields);
    }
  }
  $writeResults = multiCurlRequest($writeRequests);
  foreach ($writeResults as $id => $val) {
    // password ok check
    checkResult($writeRequests[$id]["url"], $val);
  }
  header("Location: ${_SERVER["PHP_SELF"]}");
  die();
}

require_once "../template/header.tpl";

if (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0 && !$validnonce) {
  echo "<b class=\"msg\">CSRF Schutz fehlgeschlagen</b>";
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
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/members";
  $fetchRequests[$id] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id);
  $mailingliste["members"] = Array();
  $mailingliste["numMembers"] = 0;
}
unset($mailingliste);
while (count($fetchRequests) > 0) {
  $fetchResults = multiCurlRequest($fetchRequests);
  $newFetchRequests = Array();
  foreach ($fetchResults as $id => $result) {
    checkResult($fetchRequests[$id]["url"], $result);
    $mailingliste_id = $fetchRequests[$id]["mailingliste"];
    $mailingliste = &$alle_mailinglisten[$mailingliste_id];
    $mailingliste["numMembers"] = parseMembersPage($result, $fetchRequests[$id]["url"], $mailingliste["members"]);
    $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/members";
    if (!isset($fetchRequests[$id]["letter"])) {
      // need to fetch per-letter page
      $letters = parseLettersPage($result, $fetchRequests[$id]["url"]);
      foreach ($letters as $letter) {
        $newFetchRequests[] = Array("url" => $url.'?'.http_build_query(Array("letter" => $letter)),
                                    "post" => Array("adminpw" => $mailingliste["password"]),
                                    "mailingliste" => $mailingliste_id,
                                    "letter" => $letter);
      }
    } elseif (!isset($fetchRequests[$id]["chunk"])) {
      // need to fetch per-chunk page
      $letter = $fetchRequests[$id]["letter"];
      $chunks = parseLettersPage($result, $fetchRequests[$id]["url"]);
      foreach ($chunks as $chunk) {
        $newFetchRequests[] = Array("url" => $url.'?'.http_build_query(Array("letter" => $letter, "chunk" => $chunk)),
                                    "post" => Array("adminpw" => $mailingliste["password"]),
                                    "mailingliste" => $mailingliste_id,
                                    "letter" => $letter,
                                    "chunk" => $chunk);
      }
    }
  }
  $fetchRequests = $newFetchRequests;
}
foreach ($alle_mailinglisten as $id => &$mailingliste) {
  $mailingliste["members"] = array_unique($mailingliste["members"]);
  sort($mailingliste["members"]);
  if (count($mailingliste["members"]) != $mailingliste["numMembers"]) {
    die("Fehler bei Mailingliste {$mailingliste["address"]} : {$mailingliste["numMembers"]} Mitglieder erwartet, aber nur ".count($mailingliste["members"])." gefunden.");
  }
}
unset($mailingliste);

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
<table>
<tr><th></th><th>Mailingliste</th><th>Einfügen</th><th>Entfernen</th> <!-- <th>IST</th><th>SOLL</th> --> </tr>
<?php
foreach($alle_mailinglisten as $mailingliste) {
  echo "<tr>";
  echo "<td valign=\"top\"><input class=\"mls\" type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($mailingliste["address"])."\"></td>";
  echo "<td valign=\"top\"><a href=\"".htmlspecialchars($mailingliste["url"])."\">".htmlspecialchars($mailingliste["address"])."</a></td>\n";
  $members = $mailingliste["members"];
  $dbmembers = getMailinglistePerson($mailingliste["id"]);
  foreach ($dbmembers as $i => $e) { $dbmembers[$i] = strtolower($e); }
  foreach ($members as $i => $e) { $members[$i] = strtolower($e); }
  $addmembers = array_diff($dbmembers, $members);
  $delmembers = array_diff($members, $dbmembers);
  echo "<td valign=\"top\">";
  if (count($addmembers) > 0) {
    echo "<ul>";
    foreach ($addmembers as $member) {
      echo "<li>$member <input type=\"hidden\" name=\"addmember[".htmlspecialchars($mailingliste["address"])."][]\" value=\"".htmlspecialchars($member)."\"></li>";
    }
    echo "</ul>";
  }
  echo "</td><td valign=\"top\">";
  if (count($delmembers) > 0) {
    echo "<ul>";
    foreach ($delmembers as $member) {
      echo "<li>$member <input type=\"hidden\" name=\"delmember[".htmlspecialchars($mailingliste["address"])."][]\" value=\"".htmlspecialchars($member)."\"></li>";
    }
    echo "</ul>";
  }
/*
  echo "</td><td>";
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
*/
  echo "</td></tr>";
}

?></table>

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<a href="#" onClick="$('.mls').attr('checked',true); return false;">alle auswählen</a>
<a href="#" onClick="$('.mls').attr('checked',false); return false;">keine auswählen</a>
<input type="submit" value="Anwenden" name="submit"/>
<input type="reset" value="Zurücksetzen" name="reset"/>

</form>
<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull;
<a href="index.php">Selbstauskunft</a> &bull;
<a href="admin.php">Verwaltung</a>
<?php
require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
