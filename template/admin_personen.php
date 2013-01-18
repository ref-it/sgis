<div id="person">
<a name="person"></a>
<noscript><h3>Personen</h3></noscript>

<table style="min-width:100%;">
<tr id="rowPhead">
 <th>
  <a href="#" onClick="$('#insertP').dialog('open'); return false;" title="Person anlegen">[NEU]</a>
  <div id="insertP" title="neue Person anlegen" class="editmldialog">
    <noscript><h4>Neue Person anlegen</h4></noscript>
    <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
     <ul>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value=""/></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value=""/><br/>
         Die e-Mail-Adresse wird zur Identifikation des Uni-Logins benutzt. Daher bitte die @tu-ilmenau.de-Adresse nutzen!</li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value=""/> (optional)</li>
     <li><label for="password"  >Passwort:              </label><input type="text" name="password"   value=""/> (optional)</li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value=""/><br/>
         (optional, wird ggf. automatisch ergänzt)</li>
     <li><label for="canlogin"  >Login erlaubt?:        </label>
         <select name="canlogin" size="1"><option value="1" selected="selected">erlaubt</option><option value="0">nicht erlaubt</option></select><br/>
	 (nicht erlaubt für Dummy-eMail-Adressen auf Mailinglisten nutzen)</li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="action" value="person.insert"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertP').dialog('close');"/>
    </form>
  </div>
  <?php $script[] = "\$('#insertP').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowPhead') } });"; ?>
 </th>
 <th>Name</th><th>eMail</th><th>UniRZ-Login</th><th>Benutzername</th><th>letztes Login</th><th>Login erlaubt?</th></tr>
<?php
foreach ($alle_personen as $i => $person):
 if (($_COOKIE["person_start"] >= 0) && ($i < $_COOKIE["person_start"])) continue;
 if (($_COOKIE["person_length"] >= 0) && ($i >= $_COOKIE["person_length"] + $_COOKIE["person_start"])) break;
?>
<tr id="rowP<?=$person["id"];?>">
 <td>
   <?=$i;?>.
   <a href="#" onClick="$('#deleteP<?=$person["id"];?>').dialog('open'); return false;" titel="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> löschen" >[X]</a>
   <div id="deleteP<?=$person["id"];?>" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> entfernen" class="editmldialog">
     <noscript><h4>Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> entfernen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
     <ul>
     <li>ID: <?php echo $person["id"];?></li>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value="<?php echo htmlspecialchars($person["name"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value="<?php echo htmlspecialchars($person["email"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value="<?php echo htmlspecialchars($person["unirzlogin"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value="<?php echo htmlspecialchars($person["username"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="action"    >Aktion:                </label><select name="action" size="1"><option value="person.disable" selected="selected">Person deaktivieren</option><option value="person.delete">Datensatz löschen</option></select></li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Löschen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteP<?=$person["id"];?>').dialog('close');"/>
     </form>
     <h4>Zugeordnete Gremien und Rollen</h4>
     <table>
     <tr><th>Rolle</th><th>Gremium</th><th>Fakultät</th><th>Studiengang</th><th>Zeitraum</th><th>Beschluss</th><th>Kommentar</th></tr>
<?php
$gremien = getPersonRolle($person["id"]);
if (count($gremien) == 0):
?>
     <tr><td colspan="7"><i>Keine Gremienmitgliedschaften.</i></td></tr>
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
      <td><?php echo str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></td>
     </tr>
<?php
endforeach;
endif;
?>
     </table>
   </div>
   <a href="#" onClick="$('#editP<?=$person["id"];?>').dialog('open'); return false;" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten">[E]</a>
   <div id="editP<?=$person["id"];?>" title="Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten" class="editmldialog">
     <noscript><h4>Person <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?> bearbeiten</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
     <ul>
     <li>ID: <?php echo $person["id"];?></li>
     <li><label for="name"      >Name:                  </label><input type="text" name="name"       value="<?php echo htmlspecialchars($person["name"],ENT_QUOTES);?>" /></li>
     <li><label for="email"     >E-Mail:                </label><input type="text" name="email"      value="<?php echo htmlspecialchars($person["email"],ENT_QUOTES);?>" /></li>
     <li><label for="unirzlogin">UniRZ-Login:           </label><input type="text" name="unirzlogin" value="<?php echo htmlspecialchars($person["unirzlogin"],ENT_QUOTES);?>" /></li>
     <li><label for="username"  >Benutzername:          </label><input type="text" name="username"   value="<?php echo htmlspecialchars($person["username"],ENT_QUOTES);?>" /></li>
     <li><label for="password"  >Passwort:              </label><input type="text" name="password"   value=""/></li>
     <li><label for="canlogin"  >Login erlaubt?:        </label>
         <select name="canlogin" size="1"><option value="1" <? if ($person["canLogin"]) echo "selected=\"selected\""; ?>>erlaubt</option><option value="0" <? if (!$person["canLogin"]) echo "selected=\"selected\""; ?>>nicht erlaubt</option></select>
     </li>
     <li>letztes Login: <?php echo $person["lastLogin"];?></li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="action" value="person.update"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#editP<?=$person["id"];?>').dialog('close');"/>
     </form>

     <h4>Zugeordnete Gremien und Rollen</h4>
     <table>
     <tr><th>
   <a href="#" onClick="$('#insertP<?=$person["id"];?>R').dialog('open'); return false;" titel="Rollenzuordnung einfügen" >[NEU]</a>
   <div id="insertP<?=$person["id"];?>R" title="Rollenzuordnung einfügen" class="editpersonrole">
     <noscript><h4>Rollenzuordnung einfügen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
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
       <optgroup label="<?php echo htmlspecialchars($agremium["gremium_name"]." ".$agremium["gremium_fakultaet"]." ".$agremium["gremium_studiengang"]." ".$agremium["gremium_studiengangabschluss"],ENT_QUOTES);?>">
<?php
	endif;
?>
        <option value="<?=$agremium["rolle_id"];?>"><?php echo htmlspecialchars($agremium["rolle_name"],ENT_QUOTES);?></option>
<?php
      endforeach;
      if ($last_gremium_id != -1) { echo "</optgroup>"; }
?>
         </select>
       <span class="rolle_gremienname"></span>
     </li>
     <li><label for="von">von:</label> <input type="text" name="von" value="<?=date("Y-m-d");?>" class="datepicker"/></li>
     <li><label for="bis">bis:</label> <input type="text" name="bis" value="" class="datepicker"/></li>
<?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
     <li><label for="beschlussAm">beschlussen am:</label> <input type="text" name="beschlussAm" value=""/></li>
     <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value=""/></li>
     <li><label for="kommentar">Kommentar:</label> <textarea name="kommentar"></textarea></li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="action" value="rolle_person.insert"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Zuordnung eintragen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertP<?=$person["id"];?>R').dialog('close');"/>
     </form>
   </div>
   <?php $script[]="\$('#insertP{$person['id']}R').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person['id']}') } });"; ?>
      </th><th>ID</th><th>Rolle</th><th>Gremium</th><th>Fakultät</th><th>Studiengang</th><th>Zeitraum</th><th>Beschluss</th><th>Kommentar</th></tr>
<?php
$gremien = getPersonRolle($person["id"]);
if (count($gremien) == 0):
?>
     <tr><td colspan="8"><i>Keine Gremienmitgliedschaften.</i></td></tr>
<?php
else:
foreach($gremien as $gremium):
?>
     <tr class="<?=($gremium["active"] ? "gremiumactive" : "gremiuminactive");?> forperson<?=$person["id"];?>">
      <td>
   <a href="#" onClick="$('#deleteP<?=$person["id"];?>R<?=$gremium["id"];?>').dialog('open'); return false;" titel="Rollenzuordnung aufheben" >[X]</a>
   <div id="deleteP<?=$person["id"];?>R<?=$gremium["id"];?>" title="Rollenzuordnung aufheben" class="editpersonrole">
     <noscript><h4>Rollenzuordnung aufheben</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
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
     <li><span class="label">Kommentar:</span> <div class="kommentar"><?=str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></div></li>
     <li><label for="action">Aktion:</label><select name="action" size="1"><option value="rolle_person.disable" selected="selected">Zuordnung terminieren</option><option value="rolle_person.delete">Datensatz löschen</option></select></li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Zuordnung aufheben"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteP<?=$person["id"];?>R<?=$gremium["id"];?>').dialog('close');"/>
     </form>
   </div>
   <?php $script[] = "\$('#deleteP{$person["id"]}R{$gremium["id"]}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person["id"]}') } });"; ?>
   <a href="#" onClick="$('#editP<?=$person["id"];?>R<?=$gremium["id"];?>').dialog('open'); return false;" titel="Rollenzuordnung bearbeiten" >[E]</a>
   <div id="editP<?=$person["id"];?>R<?=$gremium["id"];?>" title="Rollenzuordnung bearbeiten" class="editpersonrole">
     <noscript><h4>Rollenzuordnung bearbeiten</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#person" method="POST">
     <ul>
     <li>ID: <?php echo htmlspecialchars($gremium["id"],ENT_QUOTES);?></li>
     <li>Person: <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
     <li>Rolle/Gremium: <?php echo htmlspecialchars($gremium["rolle_name"]." / ".$gremium["gremium_name"]." ".$gremium["gremium_fakultaet"]." ".$gremium["gremium_studiengang"]." ".$gremium["gremium_studiengangabschluss"],ENT_QUOTES);?></li>
     <li><label for="von">von:</label> <input type="text" name="von" value="<?=htmlspecialchars($gremium["von"]);?>" class="datepicker"/></li>
     <li><label for="bis">bis:</label> <input type="text" name="bis" value="<?=htmlspecialchars($gremium["bis"]);?>" class="datepicker"/></li>
<?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
     <li><label for="beschlussAm"   >beschlossen am:</label> <input type="text" name="beschlussAm" value="<?=htmlspecialchars($gremium["beschlussAm"]);?>"/></li>
     <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value="<?=htmlspecialchars($gremium["beschlussDurch"]);?>"/></li>
     <li><label for="kommentar"     >Kommentar:</label> <textarea name="kommentar"><?=htmlspecialchars($gremium["kommentar"]);?></textarea></li>
     <li><img src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha"/></li>
     </ul>
     <input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
     <input type="hidden" name="rolle_id" value="<?php echo $gremium["rolle_id"];?>"/>
     <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
     <input type="hidden" name="action" value="rolle_person.update"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Zuordnung eintragen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#editP<?=$person["id"];?>R<?=$gremium["id"];?>').dialog('close');"/>
     </form>
   </div>
   <?php $script[]="\$('#editP{$person['id']}R{$gremium["id"]}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editP{$person['id']}') } });"; ?>
      </td>
      <td><?php echo htmlspecialchars($gremium["id"]);?></td>
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
      <td><?php echo str_replace("\n","<br/>",htmlspecialchars($gremium["kommentar"]));?></td>
     </tr>
<?php
endforeach;
endif;
?>
     </table>
     <?php $script[] = "\$('tr.gremiuminactive.forperson{$person["id"]}').hide()"; ?>
     <a href="#" onClick="$('tr.gremiuminactive.forperson<?=$person["id"];?>').toggle(); return false;">[inaktive Zuordnungen anzeigen/ausblenden]</a>
   </div>
   <?php
     $script[] = "\$('#editP{$person['id']}').dialog({ autoOpen: false, width: 1400, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowP{$person['id']}') } });";
     $script[] = "\$('#deleteP{$person['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#rowP{$person['id']}') } });";
   ?>
   <a href="index.php?mail=<?php echo htmlspecialchars($person["email"]);?>" title="Selbstauskunft für <?php echo htmlspecialchars($person["email"]);?>">[D]</a></td>
 </td>
 <td><?php echo htmlspecialchars($person["name"]);?></td>
 <td><a href="mailto:<?php echo htmlspecialchars($person["email"]);?>"><?php echo htmlspecialchars($person["email"]);?></a></td>
 <td><?php echo htmlspecialchars($person["unirzlogin"]);?></td>
 <td><?php echo htmlspecialchars($person["username"]);?></td>
 <td><?php echo htmlspecialchars($person["lastLogin"]);?></td>
 <td><?php if (htmlspecialchars($person["canLogin"])) { echo "ja"; } else { echo "nein"; } ;?></td>
</tr>
<?php
endforeach;
?>
</table>
<hr/>
<ul class="pageselect">
<?php if ($_COOKIE["person_start"] > 0): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?person_start=0#person">&lt;&lt;</a></li><? endif; ?>
<?php
if ($_COOKIE["person_start"] > $_COOKIE["person_length"]) {
  $prev = $_COOKIE["person_start"] - $_COOKIE["person_length"];
} else {
  $prev = -1;
}
if ((count($alle_personen) > $_COOKIE["person_start"] + 2 * $_COOKIE["person_length"])) {
  $next = $_COOKIE["person_start"] + $_COOKIE["person_length"];
} else {
  $next = -1;
}
if ($_COOKIE["person_length"] > 0):
 for($i = $_COOKIE["person_length"] ; $i < count($alle_personen); $i = $i +  $_COOKIE["person_length"]):
  if ($i < $_COOKIE["person_start"] || $i > $_COOKIE["person_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?=$i;?>#person" title="<?=htmlspecialchars($alle_personen[$i]["email"]);?>"><?=$i;?></a></li><?
  endif;
  if ($i < $prev && $i + $_COOKIE["person_length"] > $prev): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?=$prev;?>#person" title="<?=htmlspecialchars($alle_personen[$prev]["email"]);?>">&lt;</a></li><?
  endif;
  if ($i <= $_COOKIE["person_start"] && $i + $_COOKIE["person_length"] > $_COOKIE["person_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?=$_COOKIE["person_start"];?>#person">[<?=$_COOKIE["person_start"];?>]</a></li><?
  endif;
  if ($i < $next && $i + $_COOKIE["person_length"] > $next): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?person_start=<?=$next;?>#person" title="<?=htmlspecialchars($alle_personen[$next]["email"]);?>">&gt;</a></li><?
  endif;
 endfor;
endif; ?>
<?php if ($_COOKIE["person_start"] + $_COOKIE["person_length"] < count($alle_personen)): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"])?>?person_start=<?=count($alle_personen) - $_COOKIE["person_length"];?>#person">&gt;&gt;</a></li><? endif; ?>
</ul>
<form action="<?=htmlentities($_SERVER["PHP_SELF"]);?>#person" method="POST">
Einträge je Seite: <input type="text" name="person_length" value="<?=htmlentities($_COOKIE["person_length"]);?>"/>
<input type="submit" name="submit" value="Auswählen"/><input type="reset" name="reset" value="Zurücksetzen"/>
</form>
</div>
