<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

require_once "../template/header.tpl";

$validnonce = false;
if (isset($_REQUEST["nonce"]) && $_REQUEST["nonce"] === $nonce) {
 $validnonce = true;
}

if (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0 && $validnonce) {
  $alle_mailinglisten = getMailinglisten();
  foreach ($alle_mailinglisten as $mailingliste) {
    $list = $mailingliste["address"];
    if (!in_array($list, $_POST["commit"])) continue;
    $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"]);
    $password = $mailingliste["password"];
    if (true) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["subscribe_policy"] = 2; // Bestätigung / Genehmigung
      $postFields["private_roster"] = 2; // nur Admin darf Mitglieder auflisten
      commitPage($url."/privacy/subscribing", $postFields, $getFields);
    }
    if (true) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["archive_private"] = 1; // private archive
      commitPage($url."/archive", $postFields, $getFields);
    }
    if (isset($_POST["addmember"][$list])) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["subscribe_or_invite"] = 0; # abbonieren
      $postFields["send_welcome_msg_to_this_batch"] = 0; # don't send welcome
      $postFields["send_notifications_to_list_owner"] = 1; # send notify
      $postFields["subscribees"] = join("\n", $_POST["addmember"][$list])."\n";
      $postFields["invitation"] = "";
      commitPage($url."/members/add", $postFields, $getFields);
    }
    if (isset($_POST["delmember"][$list])) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["send_unsub_ack_to_this_batch"] = 0; # don't tell unsubscriber
      $postFields["send_unsub_notifications_to_list_owner"] = 1; # tell owner
      $postFields["unsubscribees"] = join("\n", $_POST["delmember"][$list])."\n";
      commitPage($url."/members/remove", $postFields, $getFields);
    }
  }
} elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0) {
  echo "<b class=\"msg\">CSRF Schutz fehlgeschlagen</b>";
}

?>
<h2>Mailinglistenmitgliedschaften aktualisieren</h2>
<?php

function fetchMembers($url, $password) {
  $url = str_replace("mailman/listinfo", "mailman/admin", $url)."/members";
  $members = Array(); $dummy = Array(); $letters = Array(); $number = -1;

  fetchMembersParsePage($url, Array("adminpw" => $password), Array(), $members, $letters, $dummy, $number);
  foreach ($letters as $letter) {
    $chunks = Array();
    fetchMembersParsePage($url, Array("adminpw" => $password), Array("letter" => $letter), $members, $dummy, $chunks, $dummy);
    foreach($chunks as $chunk) {
      fetchMembersParsePage($url, Array("adminpw" => $password), Array("letter" => $letter, "chunk" => $chunk), $members, $dummy, $dummy, $dummy);
    }
  }

  $members = array_unique($members);
  sort($members);
  if (count($members) != $number) die("Fehler bei $url : $number Mitglieder erwartet, aber nur ".count($members)." gefunden.");

  return $members;

}

function commitPage($url, $postFields, $getFields) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($getFields));
	curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $postFields);
        $output = curl_exec($ch);
        curl_close($ch);     

        // password ok check
        if (strpos($output, '<INPUT TYPE="password" NAME="adminpw" SIZE="30">') !== FALSE)
          die("Fehler beim Abruf von $url - falsches Passwort.");
}

function fetchMembersParsePage($url, $postFields, $getFields, &$members, &$letters, &$chunks, &$numMember) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($getFields));
	curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $postFields);
        $output = curl_exec($ch);
        curl_close($ch);     

	$matches = Array();
	preg_match_all( '/<INPUT name="user" type="HIDDEN" value="([^"]*)" >/', $output, $matches);
	foreach ($matches[1] as $member) {
		$members[] = urldecode($member);
	}

        // password ok check
        if (strpos($output, '<INPUT TYPE="password" NAME="adminpw" SIZE="30">') !== FALSE)
          die("Fehler beim Abruf von $url - falsches Passwort.");

	// letters required
	$match = preg_quote("a href=\"$url?letter=","/")."(.)".preg_quote("\"","/");
	$matches = Array();
	preg_match_all( "/$match/", $output, $matches);
	$letters = $matches[1];

	// chunks required
	$match = preg_quote("a href=\"$url?letter=","/").".".preg_quote("&chunk=","/")."([0-9]*)".preg_quote("\"","/");
	$matches = Array();
	preg_match_all( "/$match/", $output, $matches);
	$chunks = $matches[1];

	// num total member
	$match = preg_quote("<td COLSPAN=\"11\" BGCOLOR=\"#dddddd\"><center><em>","/")."([0-9]*) Mitglieder insgesamt(, [0-9]* werden angezeigt)?".preg_quote("</em></center></td>","/");
	if (!(preg_match( "/$match/", $output, $matches) === 1)) { die("Fehler beim Abruf von $url - keine Mitgliederanzahl - falsche Sprache?"); }
	$numMember = $matches[1];

}

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">
<table>
<tr><th></th><th>Mailingliste</th><th>Einfügen</th><th>Entfernen</th> <!-- <th>IST</th><th>SOLL</th> --> </tr>
<?

$alle_mailinglisten = getMailinglisten();
foreach ($alle_mailinglisten as $mailingliste):
  echo "<tr>";
  echo "<td><input class=\"mls\" type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($mailingliste["address"])."\"></td>";
  echo "<td><a href=\"".htmlspecialchars($mailingliste["url"])."\">".htmlspecialchars($mailingliste["address"])."</a></td>\n";
  $members = fetchMembers($mailingliste["url"], $mailingliste["password"]);
  $dbmembers = getMailinglistePerson($mailingliste["id"]);
  $addmembers = array_diff($dbmembers, $members);
  $delmembers = array_diff($members, $dbmembers);
  echo "<td>";
if (count($addmembers) > 0):
  echo "<ul>";
foreach ($addmembers as $member):
  echo "<li>$member <input type=\"hidden\" name=\"addmember[".htmlspecialchars($mailingliste["address"])."][]\" value=\"".htmlspecialchars($member)."\"></li>";
endforeach;
  echo "</ul>";
endif;
  echo "</td><td>";
if (count($delmembers) > 0):
  echo "<ul>";
foreach ($delmembers as $member):
  echo "<li>$member <input type=\"hidden\" name=\"delmember[".htmlspecialchars($mailingliste["address"])."][]\" value=\"".htmlspecialchars($member)."\"></li>";
endforeach;
  echo "</ul>";
endif;
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
endforeach;

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
<?
require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
