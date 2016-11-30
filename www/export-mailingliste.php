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
    if (isset($_POST["new_accept_these_nonmembers"][$list])) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["accept_these_nonmembers"] = $_POST["new_accept_these_nonmembers"][$list];
      $writeRequests[] = Array("url" => $url."/privacy/sender", "post" => $postFields);
    }
    if (isset($_POST["new_max_num_recipients"][$list])) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["max_num_recipients"] = $_POST["new_max_num_recipients"][$list];
      $writeRequests[] = Array("url" => $url."/privacy/recipient", "post" => $postFields);
    }
    if (isset($_POST["new_max_message_size"][$list])) {
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["max_message_size"] = $_POST["new_max_message_size"][$list];
      $writeRequests[] = Array("url" => $url."/general", "post" => $postFields);
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
  if (isset($_REQUEST["mailingliste_id"]) && ($mailingliste["id"] != (int) $_REQUEST["mailingliste_id"])) {
    unset($alle_mailinglisten[$id]);
    continue;
  }
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/members";
  $fetchRequests[] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "members");
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/privacy/sender";
  $fetchRequests[] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "privacy.sender");
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/privacy/recipient";
  $fetchRequests[] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "privacy.recipient");
  $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"])."/general";
  $fetchRequests[] = Array("url" => $url, "post" => Array("adminpw" => $mailingliste["password"]), "mailingliste" => $id, "parser" => "general");
  $mailingliste["members"] = Array();
  $mailingliste["accept_these_nonmembers"] = Array();
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

    if ($fetchRequests[$id]["parser"] == "privacy.sender") {
      $mailingliste["accept_these_nonmembers"] = parsePrivacySenderPage($result, $fetchRequests[$id]["url"]);
    }

    if ($fetchRequests[$id]["parser"] == "privacy.recipient") {
      $mailingliste["max_num_recipients"] = parsePrivacyRecipientPage($result, $fetchRequests[$id]["url"]);
    }

    if ($fetchRequests[$id]["parser"] == "general") {
      $mailingliste["max_message_size"] = parseGeneralPage($result, $fetchRequests[$id]["url"]);
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

function parseGeneralPage($output, $url) {
  $matches = Array();

  $doc = new DOMDocument();
  @$doc->loadHTML($output);
  $nodes = $doc->getElementsByTagName('input');
  for ($i=0; $i<$nodes->length; $i++) {
    $node = $nodes->item($i);
    if ($node->getAttribute("name") !== "max_message_size")
      continue;
    $value = $node->getAttribute("value");
    return (int) $value;
  }
  die("Missing max_message_size in $url");
}

function parsePrivacyRecipientPage($output, $url) {
  $matches = Array();

  $doc = new DOMDocument();
  @$doc->loadHTML($output);
  $nodes = $doc->getElementsByTagName('input');
  for ($i=0; $i<$nodes->length; $i++) {
    $node = $nodes->item($i);
    if ($node->getAttribute("name") !== "max_num_recipients")
      continue;
    $value = $node->getAttribute("value");
    return (int) $value;
  }
  die("Missing max_num_recipients in $url");
}

function parsePrivacySenderPage($output, $url) {
  $matches = Array();

  $doc = new DOMDocument();
  @$doc->loadHTML($output);
  $nodes = $doc->getElementsByTagName('textarea');
  for ($i=0; $i<$nodes->length; $i++) {
    $node = $nodes->item($i);
    if ($node->getAttribute("name") !== "accept_these_nonmembers")
      continue;
    $lines = explode("\n",$node->nodeValue);
    return $lines;
  }
  die("Missing accept_these_nonmembers in $url");
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

  $old_accept_these_nonmembers = array_unique($mailingliste["accept_these_nonmembers"]);
  $new_accept_these_nonmembers = array_unique(array_merge($old_accept_these_nonmembers, ['^.*@tu-ilmenau\.de','^.*@.*\.tu-ilmenau\.de']));
  sort($old_accept_these_nonmembers);
  ;sort($new_accept_these_nonmembers);
  $isdiff_accept_these_nonmembers = $old_accept_these_nonmembers != $new_accept_these_nonmembers;
  $rows = 10; $cols = 10;
  foreach ($old_accept_these_nonmembers as $line) $cols = max($cols, strlen($line)+5);
  $rows = max($rows, count($old_accept_these_nonmembers)+5);
  foreach ($new_accept_these_nonmembers as $line) $cols = max($cols, strlen($line)+5);
  $rows = max($rows, count($new_accept_these_nonmembers)+5);

  echo "<td valign=\"top\">";

  echo "<table class=\"table table-striped\">";
  if ($isdiff_accept_these_nonmembers) {
    echo "<tr><td>old accept these nonmembers</td><td valign=\"top\">";
    echo "<textarea readonly=\"readonly\" name=\"old_accept_these_nonmembers[".htmlspecialchars($mailingliste["address"])."]\" rows=$rows cols=$cols>";
    echo implode("\n", $old_accept_these_nonmembers);
    echo "</textarea>";
    echo "</td></tr>";
    echo "<tr><td>new accept these nonmembers</td><td valign=\"top\">";
    echo "<textarea name=\"new_accept_these_nonmembers[".htmlspecialchars($mailingliste["address"])."]\" rows=$rows cols=$cols>";
    echo implode("\n", $new_accept_these_nonmembers);
    echo "</textarea>";
    echo "</td></tr>";
  }

  $old_max_num_recipients = $mailingliste["max_num_recipients"];
  $new_max_num_recipients = ($old_max_num_recipients > 0) ? max(1000, $old_max_num_recipients) : $old_max_num_recipients;
  $isdiff_max_num_recipients = $old_max_num_recipients != $new_max_num_recipients;

  if ($isdiff_max_num_recipients) {
    echo "<tr><td>old max num recipients</td><td valign=\"top\">";
    echo "<input readonly=\"readonly\" name=\"old_max_num_recipients[".htmlspecialchars($mailingliste["address"])."]\" value=\"".htmlspecialchars($old_max_num_recipients)."\">";
    echo "</td></tr>";
    echo "<tr><td>new max num recipients</td><td valign=\"top\">";
    echo "<input readonly=\"readonly\" name=\"new_max_num_recipients[".htmlspecialchars($mailingliste["address"])."]\" value=\"".htmlspecialchars($new_max_num_recipients)."\">";
    echo "</td></tr>";
  }

  $old_max_message_size = $mailingliste["max_message_size"];
  $new_max_message_size = ($old_max_message_size > 0) ? max(10000, $old_max_message_size) : $old_max_message_size;
  $isdiff_max_message_size = $old_max_message_size != $new_max_message_size;

  if ($isdiff_max_message_size) {
    echo "<tr><td>old max message size</td><td valign=\"top\">";
    echo "<input readonly=\"readonly\" name=\"old_max_message_size[".htmlspecialchars($mailingliste["address"])."]\" value=\"".htmlspecialchars($old_max_message_size)."\">";
    echo "</td></tr>";
    echo "<tr><td>new max message size</td><td valign=\"top\">";
    echo "<input readonly=\"readonly\" name=\"new_max_message_size[".htmlspecialchars($mailingliste["address"])."]\" value=\"".htmlspecialchars($new_max_message_size)."\">";
    echo "</td></tr>";
  }

  echo "</table>";
  echo "</td>";

  echo "</tr>";
}

?></table>

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<a href="#" onClick="$('.mls').attr('checked',true); return false;">alle auswählen</a>
<a href="#" onClick="$('.mls').attr('checked',false); return false;">keine auswählen</a>
<input type="submit" value="Anwenden" name="submit"/>
<input type="reset" value="Zurücksetzen" name="reset"/>
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
