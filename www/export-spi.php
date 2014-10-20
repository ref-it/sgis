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

// only export current members and active roles
foreach ($rollen as $rolle) {
  $spiGroupId = $rolle["rolle_spiGroupId"];
  if (empty($spiGroupId)) continue;
  if ($rolle["rolle_active"] == 0) continue;
  $gremium_id = $rolle["gremium_id"];
  $rolle_id = $rolle["rolle_id"];
  $personen = getRollePersonen($rolle_id);
  if (!isset($mapping[$spiGroupId]))
    $mapping[$spiGroupId] = Array("member" => Array());
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    if ($person["active"] != 1) continue;
    $email = $person["email"];
    $mapping[$spiGroupId]["member"][] = $email;
  }
}

function getClient($groupId, $write = false) {
  global $sPiBase, $sPiGroupSet, $sPiGroupGet, $sPiUser, $sPiPassword, $sPiCA_file;
  $url = sprintf($sPiBase . ($write ? $sPiGroupSet : $sPiGroupGet), $groupId);

  $request = new HTTP_Request2_SNI();
  $request->setConfig("ssl_cafile", $sPiCA_file);
  $request->setUrl($url);
  $request->setAuth($sPiUser, $sPiPassword, HTTP_Request2::AUTH_BASIC);
  $request->setHeader('Accept-Charset', 'utf-8');
  $request->setMethod(($write ? HTTP_Request2::METHOD_POST : HTTP_Request2::METHOD_GET));

  return $request;
}


function fetchGroupMembers($groupId) {
  try {
    $spi = getClient($groupId);
    $response = $spi->send();

    $error = false;
    if ($response->getStatus() != 200) {
      $error = true;
    } else {
      $ret = json_decode($response->getBody(), true);
      if (!is_array($ret)) {
        $error = true;
      }
    }
    if ($error) {
      echo "<pre>";
      echo "Request URI: " . $spi->getUrl()."\n";
      echo "Request body:\n" . $spi->getBody()."\n";
      echo "Response status: " . $response->getStatus() . "\n";
      echo "Human-readable reason phrase: " . $response->getReasonPhrase() . "\n";
      echo "Response HTTP version: " . $response->getVersion() . "\n";
      echo "Response headers:\n";
      foreach ($response->getHeader() as $k => $v) {
          echo "\t{$k}: {$v}\n";
      }
      echo "Cookies set in response:\n";
      foreach ($response->getCookies() as $c) {
          echo "\tname: {$c['name']}, value: {$c['value']}" .
               (empty($c['expires'])? '': ", expires: {$c['expires']}") .
               (empty($c['domain'])? '': ", domain: {$c['domain']}") .
               (empty($c['path'])? '': ", path: {$c['path']}") .
               ", secure: " . ($c['secure']? 'yes': 'no') . "\n";
      }
      echo "Response body:\n" . $response->getBody();
      var_dump($response->getBody());
      echo "</pre>";
      die(__LINE__."@".__FILE__.': bad reply reading '.$groupId );
    }
    return $ret;
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$groupId.': ' . $e->getMessage() );
  }
}

function setGroupMembers($groupId, $members) {
  assert(is_array($members));
  try {
    $spi = getClient($groupId, true);
    $spi->addPostParameter("users", json_encode($members));
    $response = $spi->send();
    if ($response->getStatus() != 200) {
      echo "<pre>";
      echo "Request body:\n" . $spi->getBody() . "\n";
      echo "Request body:\n" . urldecode($spi->getBody()) . "\n";
      echo "Request url:\n" . urldecode($spi->getUrl()) . "\n";
      echo "Request headers:\n";
      foreach ($spi->getHeaders() as $k => $v) {
          echo "\t{$k}: {$v}\n";
      }
      echo "Response status: " . $response->getStatus() . "\n";
      echo "Human-readable reason phrase: " . $response->getReasonPhrase() . "\n";
      echo "Response HTTP version: " . $response->getVersion() . "\n";
      echo "Response headers:\n";
      foreach ($response->getHeader() as $k => $v) {
          echo "\t{$k}: {$v}\n";
      }
      echo "Cookies set in response:\n";
      foreach ($response->getCookies() as $c) {
          echo "\tname: {$c['name']}, value: {$c['value']}" .
               (empty($c['expires'])? '': ", expires: {$c['expires']}") .
               (empty($c['domain'])? '': ", domain: {$c['domain']}") .
               (empty($c['path'])? '': ", path: {$c['path']}") .
               ", secure: " . ($c['secure']? 'yes': 'no') . "\n";
      }
      echo "Response body:\n" . $response->getBody();
      var_dump($response->getBody());
      echo "</pre>";
      die(__LINE__."@".__FILE__.': bad reply writing '.$groupId );
    }
    return true;
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$groupId.': ' . $e->getMessage() );
  }
}

foreach ($mapping as $group_id => $data) {
  $member = array_unique($data["member"]);
  sort($member);
  if (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($group_id, $_POST["commit"]) && $validnonce) {
    setGroupMembers($group_id, $member);
  } elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($group_id, $_POST["commit"])) {
    die("<b class=\"msg\">CSRF Schutz.</b><br/>");
  }
}

if (isset($_POST["commit"])) {
  header("Location: ${_SERVER["PHP_SELF"]}");
  die();
}

require_once "../template/header.tpl";


?>
<h2>Gremienmitgliedschaften im sPi aktualisieren</h2>

<style type="text/css">
 td {vertical-align: top; }
 textarea {display: none; }
</style>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">
<table>
<tr><th></th><th>Gruppe</th><th>Änderung</th></tr>
<?php

foreach ($mapping as $group_id => $data):
  $data["member"] = array_unique($mapping[$group_id]["member"]);
  $data["oldmember"] = array_unique(fetchGroupMembers($group_id));
  $data["+member"] = array_diff($data["member"], $data["oldmember"]);
  $data["-member"] = array_diff($data["oldmember"], $data["member"]);
  $data["=member"] = array_intersect($data["oldmember"], $data["member"]);
  sort($data["member"]);
  sort($data["oldmember"]);
  sort($data["+member"]);
  sort($data["-member"]);
  sort($data["=member"]);
  echo "<tr>";
  echo " <td><input ".((count($data["+member"]) + count($data["-member"]) != 0) ? "class=\"mls\"" : "")." type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($group_id)."\"></td>";
  echo " <td><a href=\"".htmlspecialchars($sPiBase."/group/".$group_id)."\">".htmlspecialchars($group_id)."</a></td>\n";
  echo " <td>";
  if (count($data["+member"]) + count($data["-member"]) > 0)
   echo "<ul>";
  foreach ($data["+member"] as $email)
   echo "<li><a href=\"mailto:".htmlspecialchars($email)."\">+".htmlspecialchars($email)."</a></li>";
  foreach ($data["-member"] as $email)
   echo "<li><a href=\"mailto:".htmlspecialchars($email)."\">-".htmlspecialchars($email)."</a></li>";
#  foreach ($data["=member"] as $email)
#   echo "<li><a href=\"mailto:".htmlspecialchars($email)."\">".htmlspecialchars($email)."</a></li>";
  echo "</ul>";
  echo " </td>";
  echo "</tr>";
endforeach;

?></table>

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<a href="#" onClick="$('.mls').attr('checked',true); return false;">alle Änderungen auswählen</a>
<a href="#" onClick="$('.mls').attr('checked',false); return false;">keine Änderungen auswählen</a>
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
