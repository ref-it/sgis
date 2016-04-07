<div id="person">
<a name="person"></a>
<noscript><h3>Personen</h3></noscript>

<div class="table" style="min-width:100%;">
<?php
$struct_personen = Array();
$last_person_id = -1; $last_struct_id = -1;
$filter = Array();
$filter["name"] = Array();
$filter["email"] = Array();
$filter["unirzlogin"] = Array();
$filter["username"] = Array();
$filter["lastLogin"] = Array();
$activefilter = json_decode(base64_decode($_COOKIE["filter_personen"]), true);

foreach ($alle_personen as $i => $person):
  if (count($activefilter["name"]) > 0 && !in_array($person["name"], $activefilter["name"])) continue;
  if (count($activefilter["email"]) > 0 && !in_array($person["email"], $activefilter["email"])) continue;
  if (count($activefilter["unirzlogin"]) > 0 && !in_array($person["unirzlogin"], $activefilter["unirzlogin"])) continue;
  if (count($activefilter["username"]) > 0 && !in_array($person["username"], $activefilter["username"])) continue;
  if (count($activefilter["lastLogin"]) > 0 && !in_array($person["lastLogin"], $activefilter["lastLogin"])) continue;
  if (count($activefilter["canLogin"]) > 0 && !in_array($person["canLogin"], $activefilter["canLogin"])) continue;
  if (count($activefilter["active"]) > 0 && !in_array($person["active"], $activefilter["active"])) continue;
  $struct_personen[] = $person;
  $filter["name"][] = $person["name"];
  $filter["email"][] = $person["email"];
  $filter["unirzlogin"][] = $person["unirzlogin"];
  $filter["username"][] = $person["username"];
  $filter["lastLogin"][] = $person["lastLogin"];
endforeach;

$filter["name"] = array_unique($filter["name"]);
asort($filter["name"]);
$filter["email"] = array_unique($filter["email"]);
asort($filter["email"]);
$filter["unirzlogin"] = array_unique($filter["unirzlogin"]);
asort($filter["unirzlogin"]);
$filter["username"] = array_unique($filter["username"]);
asort($filter["username"]);
$filter["lastLogin"] = array_unique($filter["lastLogin"]);
asort($filter["lastLogin"]);
$filter["canLogin"] = Array(0 => "Nein", 1 => "Ja");
asort($filter["canLogin"]);
$filter["active"] = Array(0 => "Nein", 1 => "Ja");
asort($filter["active"]);

?>
<form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" class="tr" style="background-color: lightyellow;" enctype="multipart/form-data">
 <div class="td">Filter: <input type="submit" name="submit" value="filtern"/>
             <input type="hidden" name="filter_personen_set" value=""/>
             <input type="submit" name="submit" value="zurücksetzen"/>
     <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"].'?filter_personen_name=&filter_personen_email=&filter_personen_unirzlogin=&filter_personen_username=&filter_personen_lastLogin=&filter_personen_canLogin=&filter_personen_active=#person');?>">kein Filter</a>
 </div>
 <div class="td"><select name="filter_personen_name[]" multiple="multiple"><?php foreach ($filter["name"] as $name): ?><option <?php if (in_array($name, $activefilter["name"])):?> selected="selected"<?php  endif;?>><?php echo $name;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_personen_email[]" multiple="multiple"><?php foreach ($filter["email"] as $email): ?><option <?php if (in_array($email, $activefilter["email"])):?> selected="selected"<?php  endif;?>><?php echo $email;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_personen_unirzlogin[]" multiple="multiple"><?php foreach ($filter["unirzlogin"] as $unirzlogin): ?><option <?php if (in_array($unirzlogin, $activefilter["unirzlogin"])):?> selected="selected"<?php  endif;?>><?php echo $unirzlogin;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_personen_username[]" multiple="multiple"><?php foreach ($filter["username"] as $username): ?><option <?php if (in_array($username, $activefilter["username"])):?> selected="selected"<?php  endif;?>><?php echo $username;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_personen_lastLogin[]" multiple="multiple"><?php foreach ($filter["lastLogin"] as $lastLogin): ?><option <?php if (in_array($lastLogin, $activefilter["lastLogin"])):?> selected="selected"<?php  endif;?>><?php echo $lastLogin;?></option><?php endforeach;?></select></div>
<div class="td"><select name="filter_personen_canLogin[]" multiple="multiple"><?php foreach ($filter["canLogin"] as $v => $canLogin): ?><option <?php if (in_array($v, $activefilter["canLogin"])):?> selected="selected"<?php  endif;?> value="<?php echo htmlspecialchars($v);?>"><?php echo $canLogin;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_personen_active[]" multiple="multiple"><?php foreach ($filter["active"] as $v => $active): ?><option <?php if (in_array($v, $activefilter["active"])):?> selected="selected"<?php  endif;?> value="<?php echo htmlspecialchars($v);?>"><?php echo $active;?></option><?php endforeach;?></select></div>
</form>
<div class="tr" id="rowPhead">
 <div class="th">
  <a href="#" onClick="$('#insertP').dialog('open'); return false;" title="Person anlegen">[NEU]</a>
  <div id="insertP" title="neue Person anlegen" class="editmldialog">
    <noscript><h4>Neue Person anlegen</h4></noscript>
    <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value=""/></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value=""/><br/>
         Die e-Mail-Adresse wird zur Identifikation des Uni-Logins benutzt. Daher bitte die @tu-ilmenau.de-Adresse nutzen!</li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value=""/> (optional)</li>
     <li><label for="password"  >Passwort:              </label><input type="text" name="password"   value=""/> (optional)</li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value=""/><br/>
         (optional, wird ggf. automatisch ergänzt)</li>
     <li><label for="canlogin"  >Login erlaubt?:        </label>
         <select name="canlogin" size="1"><option value="1" selected="selected">erlaubt, außer während zur Gruppe cannotLogin zugehörig</option><option value="0">nicht erlaubt, außer während zur Gruppe canLogin zugehörig</option></select><br/>
	 (nicht erlaubt für Dummy-eMail-Adressen auf Mailinglisten und sonstige Ausnahmen nutzen)</li>
     <li><label for="csv"       >Datei importieren:        </label>
         <input type="file" name="csv" accept="text/comma-separated-values"><br/>
         (optional, Trennung durch Komma, Texttrenner: ", Spalten: Name, eMail, RZ-Login erste Zeile ist Kopfzeile, "Login-Erlaubt"-Wert wird aus Eingabe übernommen.)</li>
     </ul>
     <input type="hidden" name="action" value="person.insert"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertP').dialog('close');"/>
    </form>
  </div>
  <?php $script[] = "\$('#insertP').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowPhead') } });"; ?>
 </div>
 <div class="th">Name</div><div class="th">eMail</div><div class="th">UniRZ-Login</div><div class="th">Benutzername</div><div class="th">letztes Login</div><div class="th">Login erlaubt?</div><div class="th">aktuell Gremienaktiv</div></div>
<?php
foreach ($struct_personen as $i => $person):
 if (($_COOKIE["person_start"] >= 0) && ($i < $_COOKIE["person_start"])) continue;
 if (($_COOKIE["person_length"] >= 0) && ($i >= $_COOKIE["person_length"] + $_COOKIE["person_start"])) break;
?>
<div class="tr" id="rowP<?php echo $person["id"];?>">
 <div class="td">
   <?php echo $i;?>.
   <a href="#" onClick="$('#deleteP<?php echo $person["id"];?>').dialog('open'); return false;" titel="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> löschen" >[X]</a>
   <div id="deleteP<?php echo $person["id"];?>" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> entfernen" class="editmldialog">
     <noscript><h4>Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> entfernen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li>ID: <?php echo $person["id"];?></li>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value="<?php echo htmlspecialchars($person["name"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value="<?php echo htmlspecialchars($person["email"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value="<?php echo htmlspecialchars($person["unirzlogin"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value="<?php echo htmlspecialchars($person["username"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="lastlogin" >letztes Login:         </label><?php echo htmlspecialchars($person["lastLogin"],ENT_QUOTES);?></li>
     <li><label for="canlogin"  >Login erlaubt?:        </label><?php  if ($person["canLogin"]): ?>erlaubt, außer während zur Gruppe cannotLogin zugehörig<?php  else: ?>nicht erlaubt, außer während zur Gruppe canLogin zugehörig<?php  endif; ?></li>
     <li><label for="action"    >Aktion:                </label><select name="action" size="1"><option value="person.disable" selected="selected">Person deaktivieren</option><option value="person.delete">Datensatz löschen</option></select></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Löschen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteP<?php echo $person["id"];?>').dialog('close');"/>
     </form>
     <h4>Zugeordnete Gremien und Rollen</h4>
     <div class="table">
     <div class="tr"><div class="th">Rolle</div><div class="th">Gremium</div><div class="th">Fakultät</div><div class="th">Studiengang</div><div class="th">Zeitraum</div><div class="th">Beschluss</div><div class="th">Kommentar</div></div>
<?php
$gremien = getPersonRolle($person["id"]);
if (count($gremien) == 0):
?>
     <div class="tr"><div class="td" colspan="7"><i>Keine Gremienmitgliedschaften.</i></div></div>
<?php
else:
foreach($gremien as $gremium):
?>
     <div class="tr">
      <div class="td"><?php echo htmlspecialchars($gremium["rolle_name"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_name"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_fakultaet"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_studiengang"]);
            if (!empty($gremium["gremium_studiengangabschluss"])) {
              echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).")";
            }
      ?></div>
      <div class="td">
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
      </div>
      <div class="td">
<?php
   echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]);
?>
      </div>
      <div class="td"><?php echo str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></div>
     </div>
<?php
endforeach;
endif;
?>
     </div>
   </div>
   <a href="#" onClick="$('#editP<?php echo $person["id"];?>').dialog('open'); return false;" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten">[E]</a>
   <div id="editP<?php echo $person["id"];?>" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten" class="editmldialog">
     <noscript><h4>Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li>ID: <?php echo $person["id"];?></li>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value="<?php echo htmlspecialchars($person["name"],ENT_QUOTES);?>" /></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value="<?php echo htmlspecialchars($person["email"],ENT_QUOTES);?>" /></li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value="<?php echo htmlspecialchars($person["unirzlogin"],ENT_QUOTES);?>" /></li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value="<?php echo htmlspecialchars($person["username"],ENT_QUOTES);?>" /></li>
     <li><label for="password"  >Passwort:              </label><input type="text" name="password"   value=""/></li>
     <li><label for="canlogin"  >Login erlaubt?:        </label>
         <select name="canlogin" size="1"><option value="1" <?php  if ($person["canLogin"]) echo "selected=\"selected\""; ?>>erlaubt, außer während zur Gruppe cannotLogin zugehörig</option><option value="0" <?php  if (!$person["canLogin"]) echo "selected=\"selected\""; ?>>nicht erlaubt, außer während zur Gruppe canLogin zugehörig</option></select>
     </li>
     <li>letztes Login: <?php echo $person["lastLogin"];?></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="action" value="person.update"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#editP<?php echo $person["id"];?>').dialog('close');"/>
     </form>

     <h4>Zugeordnete Gremien und Rollen</h4>
     <div class="table">
     <div class="tr"><div class="th">
   <a href="#" onClick="$('#insertP<?php echo $person["id"];?>R').dialog('open'); return false;" titel="Rollenzuordnung einfügen" >[NEU]</a>
   <div id="insertP<?php echo $person["id"];?>R" title="Rollenzuordnung einfügen" class="editpersonrole">
     <noscript><h4>Rollenzuordnung einfügen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li>Person: <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
     <li>Rolle/Gremium:
      <select name="rolle_id" size="1" class="gremienauswahl" onChange="$(this).siblings('span').text($(this).find('option:selected').parent().attr('label'));">
<?php
      $last_gremium_id = -1;
      foreach ($alle_gremien as $agremium):
        if ($last_gremium_id != $agremium["gremium_id"]):
         if ($last_gremium_id != -1) { echo "</optgroup>"; }
         $last_gremium_id = $agremium["gremium_id"];
?>
       <optgroup class="forinsertP<?php echo $person["id"];?>R <?php echo ($agremium["gremium_active"] ? "gremiumactive" : "gremiuminactive");?>" label="<?php echo htmlspecialchars($agremium["gremium_name"]." ".$agremium["gremium_fakultaet"]." ".$agremium["gremium_studiengang"]." ".$agremium["gremium_studiengangabschluss"],ENT_QUOTES);?>">
<?php
	endif;
?>
        <option  class="forinsertP<?php echo $person["id"];?>R <?php echo ($agremium["rolle_active"] ? "rolleactive" : "rolleinactive");?>" value="<?php echo $agremium["rolle_id"];?>"><?php echo htmlspecialchars($agremium["rolle_name"],ENT_QUOTES);?></option>
<?php
      endforeach;
      if ($last_gremium_id != -1) { echo "</optgroup>"; }
?>
         </select>
         <a href="#" onClick="$('option.rolleinactive.forinsertP<?php echo $person["id"];?>R,optgroup.gremiuminactive.forinsertP<?php echo $person["id"];?>R').toggle(); return false;" titel="inaktive Gremien/Rolle anzeigen/ausblenden" >[inaktive Gremien/Rollen anzeigen/ausblenden]</a>
         <?php $script[] = "\$('option.rolleinactive.forinsertP{$person["id"]}R,optgroup.gremiuminactive.forinsertP{$person["id"]}R').hide();"; ?>
       <span class="rolle_gremienname"></span>
     </li>
     <li><label for="von">von:</label> <input type="text" name="von" value="<?php echo date("Y-m-d");?>" class="datepicker"/></li>
     <li><label for="bis">bis:</label> <input type="text" name="bis" value="" class="datepicker"/></li>
<?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
     <li><label for="beschlussAm">beschlussen am:</label> <input type="text" name="beschlussAm" value=""/></li>
     <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value=""/></li>
     <li><label for="lastCheck">zuletzt überprüft am:</label> <input type="text" name="lastCheck" value="" class="datepicker"/></li>
     <li><label for="kommentar">Kommentar:</label> <textarea name="kommentar"></textarea></li>
     </ul>
     <input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="action" value="rolle_person.insert"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Zuordnung eintragen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertP<?php echo $person["id"];?>R').dialog('close');"/>
     </form>
   </div>
   <?php $script[]="\$('#insertP{$person['id']}R').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person['id']}') } });"; ?>
      </div><div class="th">ID</div><div class="th">Rolle</div><div class="th">Gremium</div><div class="th">Fakultät</div><div class="th">Studiengang</div><div class="th">Zeitraum</div><div class="th">Beschluss</div><div class="th">Kommentar</div></div>
<?php
$gremien = getPersonRolle($person["id"]);
if (count($gremien) == 0):
?>
     <div class="tr"><div class="td" colspan="8"><i>Keine Gremienmitgliedschaften.</i></div></div>
<?php
else:
foreach($gremien as $gremium):
?>
     <div class="tr <?php echo ($gremium["active"] ? "gremiumactive" : "gremiuminactive");?> forperson<?php echo $person["id"];?>">
      <div class="td">
   <a href="#" onClick="$('#deleteP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>').dialog('open'); return false;" titel="Rollenzuordnung aufheben" >[X]</a>
   <div id="deleteP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>" title="Rollenzuordnung aufheben" class="editpersonrole">
     <noscript><h4>Rollenzuordnung aufheben</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li><span class="label">ID:</span> <?php echo htmlspecialchars($gremium["id"],ENT_QUOTES);?></li>
     <li><span class="label">Person:</span> <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
     <li><span class="label">Rolle/Gremium:</span> <?php echo htmlspecialchars($gremium["rolle_name"]." / ".$gremium["gremium_name"]." ".$gremium["gremium_fakultaet"]." ".$gremium["gremium_studiengang"]." ".$gremium["gremium_studiengangabschluss"],ENT_QUOTES);?></li>
     <li><span class="label">Zeitraum:</span> <?php
if (empty($gremium["von"]) && empty($gremium["bis"])) {
    echo "keine Angabe";
  } elseif (empty($gremium["von"])) {
    echo "bis ".$gremium["bis"];
  } elseif (empty($gremium["bis"])) {
    echo "seit ".$gremium["von"];
  } else {
    echo htmlspecialchars($gremium["von"])." - ".$gremium["bis"];
  }
?></li>
     <li><span class="label">Beschluss:</span> <?php echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]); ?></li>
     <li><span class="label">Kommentar:</span> <div class="kommentar"><?php echo str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></div></li>
     <li><label for="action">Aktion:</label><select name="action" size="1"><option value="rolle_person.disable" selected="selected">Zuordnung terminieren</option><option value="rolle_person.delete">Datensatz löschen</option></select></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Zuordnung aufheben"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>').dialog('close');"/>
     </form>
   </div>
   <?php $script[] = "\$('#deleteP{$person["id"]}R{$gremium["id"]}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person["id"]}') } });"; ?>
   <a href="#" onClick="$('#editP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>').dialog('open'); return false;" titel="Rollenzuordnung bearbeiten" >[E]</a>
   <div id="editP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>" title="Rollenzuordnung bearbeiten" class="editpersonrole">
     <noscript><h4>Rollenzuordnung bearbeiten</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST" enctype="multipart/form-data">
     <ul>
     <li>ID: <?php echo htmlspecialchars($gremium["id"],ENT_QUOTES);?></li>
     <li>Person: <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
     <li>Rolle/Gremium: <?php echo htmlspecialchars($gremium["rolle_name"]." / ".$gremium["gremium_name"]." ".$gremium["gremium_fakultaet"]." ".$gremium["gremium_studiengang"]." ".$gremium["gremium_studiengangabschluss"],ENT_QUOTES);?></li>
     <li><label for="von">von:</label> <input type="text" name="von" value="<?php echo htmlspecialchars($gremium["von"]);?>" class="datepicker"/></li>
     <li><label for="bis">bis:</label> <input type="text" name="bis" value="<?php echo htmlspecialchars($gremium["bis"]);?>" class="datepicker"/></li>
<?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
     <li><label for="beschlussAm"   >beschlossen am:</label> <input type="text" name="beschlussAm" value="<?php echo htmlspecialchars($gremium["beschlussAm"]);?>"/></li>
     <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value="<?php echo htmlspecialchars($gremium["beschlussDurch"]);?>"/></li>
     <li><label for="lastCheck">zuletzt überprüft am:</label> <input type="text" name="lastCheck" value="<?php echo htmlspecialchars($gremium["lastCheck"]);?>" class="datepicker"/></li>
     <li><label for="kommentar"     >Kommentar:</label> <textarea name="kommentar"><?php echo htmlspecialchars($gremium["kommentar"]);?></textarea></li>
     </ul>
     <input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="rolle_id" value="<?php echo $gremium["rolle_id"];?>"/>
     <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
     <input type="hidden" name="action" value="rolle_person.update"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Zuordnung eintragen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#editP<?php echo $person["id"];?>R<?php echo $gremium["id"];?>').dialog('close');"/>
     </form>
   </div>
   <?php $script[]="\$('#editP{$person['id']}R{$gremium["id"]}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person['id']}') } });"; ?>
      </div>
      <div class="td"><?php echo htmlspecialchars($gremium["id"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["rolle_name"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_name"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_fakultaet"]);?></div>
      <div class="td"><?php echo htmlspecialchars($gremium["gremium_studiengang"]);
            if (!empty($gremium["gremium_studiengangabschluss"])) {
              echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).")";
            }
      ?></div>
      <div class="td">
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
      </div>
      <div class="td">
<?php
   echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]);
?>
      </div>
      <div class="td"><?php echo str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></div>
     </div>
<?php
endforeach;
endif;
?>
     </div>
     <?php $script[] = "\$('div.tr.gremiuminactive.forperson{$person["id"]}').hide()"; ?>
     <a href="#" onClick="$('div.tr.gremiuminactive.forperson<?php echo $person["id"];?>').toggle(); return false;">[inaktive Zuordnungen anzeigen/ausblenden]</a>
   </div>
   <?php
     $script[] = "\$('#editP{$person['id']}').dialog({ autoOpen: false, width: 1400, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowP{$person['id']}') } });";
     $script[] = "\$('#deleteP{$person['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowP{$person['id']}') } });";
   ?>
   <a href="index.php?mail=<?php echo htmlspecialchars($person["email"]);?>" title="Selbstauskunft für <?php echo htmlspecialchars($person["email"]);?>">[D]</a>
 </div>
 <div class="td"><?php echo htmlspecialchars($person["name"]);?></div>
 <div class="td"><a href="mailto:<?php echo htmlspecialchars($person["email"]);?>"><?php echo htmlspecialchars($person["email"]);?></a></div>
 <div class="td"><?php echo htmlspecialchars($person["unirzlogin"]);?></div>
 <div class="td"><?php echo htmlspecialchars($person["username"]);?></div>
 <div class="td"><?php echo htmlspecialchars($person["lastLogin"]);?></div>
 <div class="td"><?php if (htmlspecialchars($person["canLogin"])) { echo "ja"; } else { echo "nein"; } ;?></div>
 <div class="td"><?php if (htmlspecialchars($person["active"])) { echo "ja"; } else { echo "nein"; } ;?></div>
</div>
<?php
endforeach;
?>
</div>
<hr/>
<ul class="pageselect">
<?php if ($_COOKIE["person_start"] > 0): ?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>?person_start=0#person">&lt;&lt;</a></li><?php  endif; ?>
<?php
if ($_COOKIE["person_start"] > $_COOKIE["person_length"]) {
  $prev = $_COOKIE["person_start"] - $_COOKIE["person_length"];
} else {
  $prev = -1;
}
if ((count($struct_personen) > $_COOKIE["person_start"] + 2 * $_COOKIE["person_length"])) {
  $next = $_COOKIE["person_start"] + $_COOKIE["person_length"];
} else {
  $next = -1;
}
if ($_COOKIE["person_length"] > 0):
 for($i = $_COOKIE["person_length"] ; $i < count($struct_personen); $i = $i +  $_COOKIE["person_length"]):
  if ($i < $_COOKIE["person_start"] || $i > $_COOKIE["person_start"]): 
?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?php echo $i;?>#person" title="<?php echo htmlspecialchars($struct_personen[$i]["email"]);?>"><?php echo $i;?></a></li><?php
  endif;
  if ($i < $prev && $i + $_COOKIE["person_length"] > $prev): 
?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?php echo $prev;?>#person" title="<?php echo htmlspecialchars($struct_personen[$prev]["email"]);?>">&lt;</a></li><?php
  endif;
  if ($i <= $_COOKIE["person_start"] && $i + $_COOKIE["person_length"] > $_COOKIE["person_start"]): 
?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?php echo $_COOKIE["person_start"];?>#person">[<?php echo $_COOKIE["person_start"];?>]</a></li><?php
  endif;
  if ($i < $next && $i + $_COOKIE["person_length"] > $next): 
?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?php echo $next;?>#person" title="<?php echo htmlspecialchars($struct_personen[$next]["email"]);?>">&gt;</a></li><?php
  endif;
 endfor;
endif; ?>
<?php if ($_COOKIE["person_start"] + $_COOKIE["person_length"] < count($struct_personen)): ?><li><a href="<?php echo htmlentities($_SERVER["PHP_SELF"])?>?person_start=<?php echo count($struct_personen) - $_COOKIE["person_length"];?>#person">&gt;&gt;</a></li><?php  endif; ?>
</ul>
<form action="<?php echo htmlentities($_SERVER["PHP_SELF"]);?>#person" method="POST" enctype="multipart/form-data">
Einträge je Seite: <input type="text" name="person_length" value="<?php echo htmlentities($_COOKIE["person_length"]);?>"/>
<input type="submit" name="submit" value="Auswählen"/><input type="reset" name="reset" value="Zurücksetzen"/>
</form>
</div>
