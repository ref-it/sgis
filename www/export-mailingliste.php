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
  $members = Array();

echo "$password<br>\n";
  #$new_members = fetchMembersParsePage($url, Array("adminpw" => $password, "letter" => "a", "chunk" => 0), $members);
  $new_members = fetchMembersParsePage($url, Array("adminpw" => $password), $members);

  return $members;

}

function fetchMembersParsePage($url, $fields, &$members) {

	$encoded = '';
	foreach($fields as $name => $value) {
		$encoded .= urlencode($name).'='.urlencode($value).'&';
	}
	if (strlen($encoded) > 0) { $encoded = substr($encoded, 0, strlen($encoded)-1); }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  $encoded);
	curl_setopt($ch, CURLOPT_POST, 1);
        $output = curl_exec($ch);
        curl_close($ch);     

	$matches = Array();
	preg_match_all( '/<INPUT name="user" type="HIDDEN" value="([^"]*)" >/', $output, $matches);
	foreach ($matches[1] as $member) {
		$members[] = urldecode($member);
	}
	// letters required
#	$match = preg_quote("a href=\"$url?letter=","/")."([a-z])".preg_quote("\"","/");
	$match = preg_quote("a href=");
	$matches = Array();
echo htmlspecialchars("/$match/")."<br>\n";
	preg_match_all( "/$match/", $output, $matches);
echo count($matches[0]);
#	var_dump($matches);
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

}

$alle_mailinglisten = getMailinglisten();
foreach ($alle_mailinglisten as $mailingliste):
if ($mailingliste["address"] != "stud-gewaehlt@tu-ilmenau.de") continue;
  echo "<h3>".htmlspecialchars($mailingliste["address"])."</h3>\n";
  $members = fetchMembers($mailingliste["url"], $mailingliste["password"]);
  echo "<ul>";
foreach ($members as $member):
  echo "<li>$member</li>";
endforeach;
  echo "</ul>";
  

endforeach;

require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
