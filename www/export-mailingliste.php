<?php

global $ADMINGROUP;

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

require_once "../template/header.tpl";

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
}

$alle_mailinglisten = getMailinglisten();
foreach ($alle_mailinglisten as $mailingliste):
  echo "<h3>".htmlspecialchars($mailingliste["address"])."</h3>\n";
  $members = fetchMembers($mailingliste["url"], $mailingliste["password"]);
  $dbmembers = getMailinglistePerson($mailingliste["id"]);
  $addmembers = array_diff($dbmembers, $members);
  $delmembers = array_diff($members, $dbmembers);
  echo "<!-- <h4>Ist-Mitglieder</h4>\n";
  echo "<ul>";
foreach ($members as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
  echo "<h4>Soll-Mitglieder</h4>\n";
  echo "<ul>";
foreach ($dbmembers as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul> -->";
if (count($addmembers) > 0):
  echo "<h4>Einfügen</h4>\n";
  echo "<ul>";
foreach ($addmembers as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
endif;
if (count($delmembers) > 0):
  echo "<h4>Entfernen</h4>\n";
  echo "<ul>";
foreach ($delmembers as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
endif;

endforeach;

require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
