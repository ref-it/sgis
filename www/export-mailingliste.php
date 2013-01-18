<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

require_once "../template/header.tpl";

$validcaptcha = false;
if (isset($_REQUEST["captcha"])) {
 if (empty($_REQUEST["captchaId"])) { die("empty captcha id supplied"); }
 $validcaptcha = Securimage::checkByCaptchaId($_REQUEST["captchaId"], $_REQUEST["captcha"]);
}

if (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0 && $validcaptcha) {
  $dummy = Array();
  $alle_mailinglisten = getMailinglisten();
  foreach ($alle_mailinglisten as $mailingliste) {
    $list = $mailingliste["address"];
    if (!in_array($list, $_POST["commit"])) continue;
    $url = $mailingliste["url"]; $url = str_replace("mailman/listinfo", "mailman/admin", $url)."/members";
    $password = $mailingliste["password"];
    if (isset($_POST["addmember"][$list])) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["subscribe_or_invite"] = 0; # abbonieren
      $postFields["send_welcome_msg_to_this_batch"] = 1; # send welcome
      $postFields["send_notifications_to_list_owner"] = 1; # send notify
      $postFields["subscribees"] = join("\n", $_POST["addmember"][$list]);
      $postFields["invitation"] = "";
      fetchMembersParsePage($url."/add", $postFields, $getFields, $dummy, $dummy, $dummy);
    }
    if (isset($_POST["delmember"][$list])) {
      $getFields = Array();
      $postFields = Array();
      $postFields["adminpw"] = $password;
      $postFields["send_unsub_ack_to_this_batch"] = 1; # tell unsubscriber
      $postFields["send_unsub_notifications_to_list_owner"] = 1; # tell owner
      $postFields["unsubscribees"] = join("\n", $_POST["delmember"][$list]);
      fetchMembersParsePage($url."/remove", $postFields, $getFields, $dummy, $dummy, $dummy);
    }
  }
} elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && count($_POST["commit"]) > 0) {
  echo "<b class=\"msg\">Captcha falsch/fehlt</b>";
}

?>
<h2>Mailinglistenmitgliedschaften aktualisieren</h2>
<?php

function fetchMembers($url, $password) {
  $url = str_replace("mailman/listinfo", "mailman/admin", $url)."/members";
  $members = Array(); $dummy = Array(); $letters = Array();

  fetchMembersParsePage($url, Array("adminpw" => $password), Array(), $members, $letters, $dummy);
  foreach ($letters as $letter) {
    $chunks = Array();
    fetchMembersParsePage($url, Array("adminpw" => $password), Array("letter" => $letter), $members, $dummy, $chunks);
    foreach($chunks as $chunk) {
      fetchMembersParsePage($url, Array("adminpw" => $password), Array("letter" => $letter, "chunk" => $chunk), $members, $dummy, $dummy);
    }
  }

  $members = array_unique($members);
  sort($members);

  return $members;

}

function fetchMembersParsePage($url, $postFields, $getFields, &$members, &$letters, &$chunks) {

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

	// letters required
	$match = preg_quote("a href=\"$url?letter=","/")."([a-z])".preg_quote("\"","/");
	$matches = Array();
	preg_match_all( "/$match/", $output, $matches);
	$letters = $matches[1];

	// chunks required
	$match = preg_quote("a href=\"$url?letter=.&chunk=","/")."([0-9]*)".preg_quote("\"","/");
	$matches = Array();
	preg_match_all( "/$match/", $output, $matches);
	$chunks = $matches[1];

        // password ok check
        if (strpos($output, '<INPUT TYPE="password" NAME="adminpw" SIZE="30">') !== FALSE)
          die("Fehler beim Abruf von $url - falsches Passwort.");
}

$captchaId = Securimage::getCaptchaId();
$options = array('captchaId'  => $captchaId, 'no_session' => true, 'no_exit' => true, 'send_headers' => false);
$captcha = new Securimage($options);
ob_start();   // start the output buffer
$captcha->show();
$imgBinary = ob_get_contents(); // get contents of the buffer
ob_end_clean(); // turn off buffering and clear the buffer

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

<img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value=""/>
<input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>

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
