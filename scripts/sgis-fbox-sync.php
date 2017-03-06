#! /usr/bin/php
<?php

global $debug;

# CallMonitor mit #96*5* aktiviert

# Heimnetz => Übersicht => Netzwerkeinstellungen => "Zugriff für Anwendungen zulassen" aktivieren (TR-064)

$fb_user="FIXME";
$fb_pass="FIXME";
$fb_phonebook_name = "Telefonbuch";
$rpcKey3 = "FIXME";
$url = "https://helfer.stura.tu-ilmenau.de/sgis/rpccontacts.php";
$debug = false;

$options = getopt("", Array("debug"));
if (isset($options["debug"])) $debug = true;

# 1. detect phonebook on fbox
$opts = array(
   # http://php.net/manual/de/context.ssl.php
   'ssl' => array('verify_peer'=>false, 'verify_peer_name'=>false)
);

$sopts =  array(
        'location'   => "https://141.24.44.135:49443/upnp/control/x_contact",
#        'location'   => "http://141.24.44.135:49000/upnp/control/x_contact",
        'uri'        => "urn:dslforum-org:service:X_AVM-DE_OnTel:1",
        'noroot'     => True,
        'login'      => $fb_user,
        'password'   => $fb_pass,
        'trace'      => True,
        'exceptions' => 0,
        'stream_context' => stream_context_create($opts),
    );

$client = new SoapClient( null, $sopts );
$result = $client->GetPhonebookList();
if(is_soap_fault($result)) {  print(" Fehlercode: $result->faultcode | Fehlerstring:\n       $result->faultstring\n");  exit; }

$phoneBookIds = explode(",", $result);
$sgisPhoneBookId = -1;
$fboxContacts = Array();

# Problem: PhonebookEntryID kann nicht ausgelesen werden, ist nicht fortlaufend.
foreach ($phoneBookIds as $phoneBookId) {
  $result = $client->GetPhonebook(new SoapParam($phoneBookId, 'NewPhonebookID'));
  if(is_soap_fault($result)) {  print(" Fehlercode: $result->faultcode | Fehlerstring:\n       $result->faultstring\n");  exit; }
  if ($result["NewPhonebookName"] != $fb_phonebook_name) continue;

  $sgisPhoneBookId = $phoneBookId;

  # detect number of contact entries
  $xml = file_get_contents($result['NewPhonebookURL'], false, stream_context_create($opts));
  try {
    $xml = @new SimpleXMLElement($xml);
  } catch (Exception $e) {
    $xml = new SimpleXMLElement(iconv("ISO-8859-15","UTF-8",$xml));
  }
  $pb = $xml->phonebook;
  $numContact = 0;
  foreach ($pb->children() as $e) {
    if ($e->getName() != "contact") continue;
#    $fboxContacts[] = $e;
    $numContact++;
  }
  if ($debug) echo "find PhonebookEntryID for $numContact entries: ";
  $skipped = 0;
  for ($i=0; count($fboxContacts) < $numContact && $skipped < 100; $i++) {
    if ($i % 100 == 0 && $debug)
      echo " ".count($fboxContacts)."/".$numContact." ";

    $result = $client->GetPhonebookEntry(new SoapParam($phoneBookId, 'NewPhonebookID'), new SoapParam($i, 'NewPhonebookEntryID'));
    if (is_soap_fault($result)) {
      $skipped++;
      #print(" Fehlercode: $result->faultcode | Fehlerstring:\n       $result->faultstring\n");
      continue; 
    }
    $xml = $result;
    try {
      $xml = @new SimpleXMLElement($xml);
    } catch (Exception $e) {
      $xml = new SimpleXMLElement(iconv("ISO-8859-15","UTF-8",$xml));
    }

    $fboxContacts[$i] = $xml;
    $skipped = 0;
#print_r($xml);
    if ($debug) echo ".";
  }
  if ($debug) echo " done\n";

  break;
}

if ($sgisPhoneBookId == -1) die("PhoneBook not found\n");
if ($debug) echo "Found ".(count($fboxContacts))." on Fritz!Box\n";

#print_r($fboxContacts);die();

# 2. fetch new contacts from SGIS
$nonce = md5(rand());
$reply = sgisRequest(Array("nonce" => $nonce));
if ($reply === false) throw new Exception("SGIS failure for user");
if ($reply["nonce"] !== $nonce) throw new Exception("SGIS failure");
if ($reply["status"] !== "ok") throw new Exception("SGIS failure");

$sgisContacts = $reply["persons"];

foreach (array_keys($sgisContacts) as $i) {
  $sgisContacts[$i]["__fboxId"] = "";
}

if ($debug) echo "got ".(count($sgisContacts))." from SGIS\n";

# 3. generate XML content
$newXML = Array();
foreach ($sgisContacts as $i => $p) {
  $tels = Array();
  foreach ($p["_contact"] as $c) {
    if (!$c["active"]) continue;
    if ($c["type"] != "tel") continue;
    $tels[] = $c["details"];
  }
  if (count($tels) == 0) { continue; }

  $data = '<contact>
		<category>0</category>
		<person>
			<realName>'.escapeForXML($p["name"]).'</realName>
		</person>
		<telephony>
                        <services>
                           <email classifier="private" id="0">'.escapeForXML($p["email"]).'</email> 
                           <email classifier="private" id="1">'.escapeForXML($p["id"]."@sgis").'</email> 
                        </services>
';
  foreach ($tels as $i => $tel) {
    $data .= '                  <number type="home" prio="1" id="'.$i.'">'.escapeForXML($tel).'</number>'."\n";
  }
  $data .= '		</telephony>
	</contact>';
  # print_r($p);
#  $doc = new DOMDocument();
#  $doc->loadXML($data);
#  $data = $doc->saveXML();

  $newXML[$p["id"]] = $data;
}

# 3. find, update and delete persons
$fids = array_keys($fboxContacts);
sort($fids);
$fids = array_reverse($fids);
foreach ($fids as $idx) {
  $c = $fboxContacts[$idx];
  $person_id = -1;
#  if ($debug) print_r($c);

#  if (!$c->telephony->services && $debug) echo "skip on line ".__LINE__."\n";
  if (!$c->telephony->services) continue;
  foreach ($c->telephony->services->children() as $e) {
#    if ($e->getName() != "email" && $debug) echo "skip on line ".__LINE__."\n";
    if ($e->getName() != "email") continue;
#var_dump($e);
#var_dump($e->{0});
#var_dump((string) $e);
    $mail = (string) $e;
#    if ($debug) echo "read mail=$mail\n";
#    if ($debug && substr($mail, -5) != "@sgis") echo "skip on line ".__LINE__."\n";
    if (substr($mail, -5) != "@sgis") continue;
    if ($person_id != -1) {
      echo "error: multiple sgis identifier in contact\n";
      print_r($c);
      $person_id = -1;
      break;
    }

    $person_id = (int) substr($mail, 0, -5);
  }
#  if ($debug) echo "person_id = $person_id\n";

  if ($person_id == -1) continue;

  # update
#  $fboxId = (string) $c->uniqueid;
  $fboxId = $idx;

# SetPhonebookEntry seems broken for update but always creates new
#  if (isset($newXML[$person_id])) {
#    if ($debug) echo "update entry for $person_id\n";
#    $result = $client->SetPhonebookEntry(new SoapParam($phoneBookId, 'NewPhonebookID'), new SoapParam((int) $fboxId, 'NewPhonebookEntryID'), new SoapParam($newXML[$person_id], 'NewPhonebookEntryData'));
#    unset($newXML[$person_id]);
#  } else {
     if ($debug) echo "delete entry for $person_id ($fboxId)\n";
     $result = $client->DeletePhonebookEntry(new SoapParam($phoneBookId, 'NewPhonebookID'), new SoapParam((int) $fboxId, 'NewPhonebookEntryID'));
#  }
  if(is_soap_fault($result)) {
    print(" Fehlercode: $result->faultcode | Fehlerstring:\n       $result->faultstring\n");
    exit;
  }
}

# 4. create new persons
if ($debug) echo "going to create ".(count($newXML))." new persons\n";
foreach ($newXML as $person_id => $data) {
  if ($debug) echo "create entry for $person_id\n";
  $result = $client->SetPhonebookEntry(new SoapParam($phoneBookId, 'NewPhonebookID'), new SoapParam("", 'NewPhonebookEntryID'), new SoapParam($data, 'NewPhonebookEntryData'));
  if(is_soap_fault($result)) {  print(" Fehlercode: $result->faultcode | Fehlerstring:\n       $result->faultstring\n");  exit; }
}

exit;

function escapeForXML($str) {
  #  Fritz!Box requires use of &auml; and alike
  return htmlentities($str, ENT_HTML401 | ENT_COMPAT | ENT_QUOTES, 'utf-8');
}

function sgisRequest($request) {
  global $rpcKey3, $url, $debug;
  if (!function_exists('curl_init')) echo "missing curl\n";
  if (!function_exists('curl_init')) return false;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, Array("login" => encrypt(json_encode($request), $rpcKey3)));
#  curl_setopt($ch, CURLOPT_VERBOSE, true);
  $output = curl_exec($ch);
  curl_close($ch);
  if ($output === false && $debug) echo "curl_exec returned false\n";
  if ($output === false) return false;
  $output = decrypt($output, $rpcKey3);
  if ($output === false && $debug) echo "decrypt returned false\n";
  if ($output === false) return false;
  return json_decode($output, true);
}

function encrypt( $msg, $k, $base64 = true ) { 

  # open cipher module (do not change cipher/mode)
  if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
    return false;

  $msg = serialize($msg);           # serialize
  $iv  = mcrypt_create_iv(32, MCRYPT_RAND);       # create iv

  if ( mcrypt_generic_init($td, $k, $iv) !== 0 )  # initialize buffers
    return false;

  $msg  = mcrypt_generic($td, $msg);          # encrypt
  $msg  = $iv . $msg;               # prepend iv
  $mac  = pbkdf2($msg, $k, 1000, 32);     # create mac
  $msg .= $mac;                   # append mac

  mcrypt_generic_deinit($td);           # clear buffers
  mcrypt_module_close($td);               # close cipher module

  if ( $base64 ) $msg = base64_encode($msg);      # base64 encode?

  return $msg;                # return iv+ciphertext+mac
}

function decrypt( $msg, $k, $base64 = true ) { 
  if ( $base64 ) $msg = base64_decode($msg);        # base64 decode?

  # open cipher module (do not change cipher/mode)
  if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
    return false;

  $iv  = substr($msg, 0, 32);             # extract iv
  $mo  = strlen($msg) - 32;                 # mac offset
  $em  = substr($msg, $mo);                 # extract mac
  $msg = substr($msg, 32, strlen($msg)-64);         # extract ciphertext
  $mac = pbkdf2($iv . $msg, $k, 1000, 32);    # create mac

  if ( $em !== $mac )                 # authenticate mac
    return false;

  if ( mcrypt_generic_init($td, $k, $iv) !== 0 )    # initialize buffers
    return false;

  $msg = mdecrypt_generic($td, $msg);         # decrypt
  $msg = unserialize($msg);                 # unserialize

  mcrypt_generic_deinit($td);             # clear buffers
  mcrypt_module_close($td);                 # close cipher module
  return $msg;                  # return original msg
}

function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) { 

  $hl = strlen(hash($a, null, true));     # Hash length
  $kb = ceil($kl / $hl);        # Key blocks to compute
  $dk = '';                 # Derived key

  # Create key
  for ( $block = 1; $block <= $kb; $block ++ ) { 

    # Initial hash for this block
    $ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

    # Perform block iterations
    for ( $i = 1; $i < $c; $i ++ )

      # XOR each iterate
      $ib ^= ($b = hash_hmac($a, $b, $p, true));

    $dk .= $ib; # Append iterated block
  }

  # Return derived key of correct length
  return substr($dk, 0, $kl);
}

