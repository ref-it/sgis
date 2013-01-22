<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce;

require "../template/header.tpl";

if (isset($_REQUEST["src"]) && $_REQUEST["src"] == "pwchange") {
  echo "<b>Das Passwort und/oder Nutzername wurde(n) erfolgreich geändert.<br>\n";
}

?>
<script>
$(function() {
$( "#tabs" ).tabs();
});
</script>

<h2>Selbstauskunft studentisches Gremieninformationssystem (sGIS)</h2>

<div id="tabs">
 <ul>
  <li><a href="#person">Person</a></li>
  <li><a href="#gremium">Gremienmitgliedschaften</a></li>
  <li><a href="#gruppe">Gruppenrechte</a></li>
  <li><a href="#mailingliste">Mailinglisten</a></li>
  <li><a href="#pwaendern">Nutzername und Passwort ändern</a></li>
 </ul>

<div id="person">
<a name="person"></a>
<noscript><h3>Person</h3></noscript>
<table>
  <tr>
   <th align="left">ID</th>
   <td><?php echo htmlspecialchars($person["id"]); ?></td>
  </tr>
  <tr>
   <th align="left">Name</th>
   <td><?php echo htmlspecialchars($person["name"]); ?></td>
  </tr>
  <tr>
   <th align="left">eMail</th>
   <td><?php echo htmlspecialchars($person["email"]); ?></td>
  </tr>
  <tr>
   <th align="left">Login-Name</th>
   <td><?php echo htmlspecialchars($person["username"]); ?></td>
  </tr>
  <tr>
   <th align="left">Login-Passwort</th>
   <td><?php echo (empty($person["password"]) ? "nicht gesetzt" : "gesetzt"); ?></td>
  </tr>
  <tr>
   <th align="left">UniRZ-Login</th>
   <td><?php echo htmlspecialchars($person["unirzlogin"]); ?></td>
  </tr>
  <tr>
   <th align="left">letztes Login</th>
   <td><?php echo htmlspecialchars($person["lastLogin"]); ?></td>
  </tr>
  <tr>
   <th align="left">Login erlaubt?</th>
   <td><?php echo htmlspecialchars($person["canLogin"] ? "ja" : "nein"); ?></td>
 </tr>
</table>

Angehörige der TU Ilmenau können E-Mail-Weiterleitungen auf <a href="https://webmail.tu-ilmenau.de/smartsieve/">Webmail der TU Ilmenau</a> konfigurieren.

</div>

<div id="gremium">
<a name="gremium"></a>
<noscript><h3>Gremienmitgliedschaften</h3></noscript>
<table>
<tr><th>Rolle</th><th>Gremium</th><th>Fakultät</th><th>Studiengang</th><th>Zeitraum</th><th>Beschluss</th></tr>
<?php
if (count($gremien) == 0):
?>
<tr><td colspan="6"><i>Keine Gremienmitgliedschaften.</td></tr>
<?php
else:
foreach($gremien as $gremium):
?>
<tr>
 <td><?php echo htmlspecialchars($gremium["rolle_name"]);?></td>
 <td><?php echo htmlspecialchars($gremium["gremium_name"]);?></td>
 <td><?php echo htmlspecialchars($gremium["gremium_fakultaet"]);?></td>
 <td><?php echo htmlspecialchars($gremium["gremium_studiengang"]);
  if (!empty($gremium["gremium_studiengangabschluss"])) {
    echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).")";
  }
?></td>
 <td>
<?php
  if (empty($gremium["von"]) && empty($gremium["bis"])) {
    echo "keine Angabe";
  } elseif (empty($gremium["von"])) {
    echo "bis ".$gremium["bis"];
  } elseif (empty($gremium["bis"])) {
    echo "seit ".$gremium["von"];
  } else {
    echo htmlspecialchars($gremium["von"])." - ".$gremium["bis"];
  }
?>
 </td>
 <td>
<?php
   echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]);
?>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>
</div>

<div id="gruppe">
<a name="gruppe"></a>
<noscript><h3>Gruppenrechte</h3></noscript>

<?php
if (count($gruppen) == 0):
?><i>Keine Gruppen.</i><?php
else:
?><ul><?php
foreach($gruppen as $gruppe):
?> <li><?php echo htmlspecialchars($gruppe["name"]); ?></li>
<?php
endforeach;
?></ul><?php
endif;
?>
</div>

<div id="mailingliste">
<a name="mailingliste"></a>
<noscript><h3>Mailinglisten</h3></noscript>

<?php
if (count($mailinglisten) == 0):
?><i>Keine Mailinglisten.</i><?php
else:
?><ul><?php
foreach($mailinglisten as $mailingliste):
?> <li> <?php
if (!empty($mailingliste["url"])) echo "<a href=\"".htmlspecialchars($mailingliste["url"])."\">";
echo htmlspecialchars($mailingliste["address"]); 
if (!empty($mailingliste["url"])) echo "</a>";
?></li>
<?php
endforeach;
?></ul><?php
endif;
?>
</div>

<div id="pwaendern">
<a name="pwaendern"></a>
<noscript><h3>Nutzername und Passwort ändern</h3></noscript>

Bitte geben deine neuen Zugangsdaten für das sGIS ein:

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">

<ul>
 <li> <label for="username"  style="width:225px; display: inline-block;">Nutzername</label> <input type="text" name="username" value="<?php echo htmlspecialchars($person["username"]);?>" <?php if (!empty($person["username"]))echo " readonly=readonly "; ?>/>
   <br/><i>Der Nutzername kann nur einmalig eingestellt werden (d.h. wenn noch nicht gesetzt).</i>
 </li>
 <li> <label for="password"  style="width:225px; display: inline-block;">Passwort</label> <input type="password" name="password" value=""/> </li>
 <li> <label for="password2" style="width:225px; display: inline-block;">Passwort (Wiederholung)</label> <input type="password" name="password2" value=""/> </li>
</ul>

<input type="hidden" name="action" value="pwchange"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<input type="submit" name="submit" value="Speichern"/>
<input type="reset" name="reset" value="Abbruch"/>


</form>

</div>
</div>

<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull; <a href="admin.php">Verwaltung</a>

<?php
require "../template/footer.tpl";
