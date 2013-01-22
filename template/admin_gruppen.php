<div id="gruppe">
<a name="gruppe"></a>
<noscript><h3>Gruppen</h3></noscript>

<table style="min-width:100%;">
<tr id="rowGRPhead">
 <th>
  <a href="#" onClick="$('#insertGRP').dialog('open'); return false;" title="Gruppe anlegen">[NEU]</a>
  <div id="insertGRP" title="neue Gruppe anlegen" class="editmldialog">
    <noscript><h4>Neue Gruppe anlegen</h4></noscript>
    <form action="<?php echo $_SERVER["PHP_SELF"];?>#gruppe" method="POST">
     <ul>
     <li><label for="name">Name:</label><input type="text" name="name" value=""/></li>
     <li><label for="beschreibung">Beschreibung:</label><input type="text" name="beschreibung" value=""/></li>
     </ul>
     <input type="hidden" name="action" value="gruppe.insert"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertGRP').dialog('close');"/>
    </form>
  </div>
  <?php $script[] = "\$('#insertGRP').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowGRPhead') } });"; ?>
 </th>
 <th>Name</th><th>Beschreibung</th></tr>
<?php
foreach ($alle_gruppen as $i => $gruppe):
 if (($_COOKIE["gruppe_start"] >= 0) && ($i < $_COOKIE["gruppe_start"])) continue;
 if (($_COOKIE["gruppe_length"] >= 0) && ($i >= $_COOKIE["gruppe_length"] + $_COOKIE["gruppe_start"])) break;
?>
<tr id="rowGRP<?=$gruppe["id"];?>">
 <td>
   <?=$i;?>.
   <a href="#" onClick="$('#deleteGRP<?=$gruppe["id"];?>').dialog('open'); return false;" titel="Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> löschen" >[X]</a>
   <div id="deleteGRP<?=$gruppe["id"];?>" title="Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> entfernen" class="editmldialog">
     <noscript><h4>Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> entfernen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#gruppe" method="POST">
     <ul>
     <li>ID: <?php echo $gruppe["id"];?></li>
     <li><label for="name">Name:</label><input type="text" name="name" value="<?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="beschreibung">Beschreibung:</label><input type="text" name="beschreibung" value="<?php echo htmlspecialchars($gruppe["beschreibung"],ENT_QUOTES);?>" readonly="readonly"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $gruppe["id"];?>"/>
     <input type="hidden" name="action" value="gruppe.delete"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Löschen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteGRP<?=$gruppe["id"];?>').dialog('close');"/>
     </form>
   </div>
   <a href="#" onClick="$('#editGRP<?=$gruppe["id"];?>').dialog('open'); return false;" title="Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> bearbeiten">[E]</a>
   <div id="editGRP<?=$gruppe["id"];?>" title="Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> bearbeiten" class="editmldialog">
     <noscript><h4>Gruppe <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?> bearbeiten</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#gruppe" method="POST">
     <ul>
     <li>ID: <?php echo $gruppe["id"];?></li>
     <li><label for="name">Name:</label><input type="text" name="name" value="<?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?>"/></li>
     <li><label for="beschreibung">Beschreibung:</label><input type="text" name="beschreibung" value="<?php echo htmlspecialchars($gruppe["beschreibung"],ENT_QUOTES);?>"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $gruppe["id"];?>"/>
     <input type="hidden" name="action" value="gruppe.update"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#editGRP<?=$gruppe["id"];?>').dialog('close');"/>
     </form>

     <h4>Zugeordnete Gremien und Rollen</h4>
     <table>
     <tr><th>
   <a href="#" onClick="$('#insertGRP<?=$gruppe["id"];?>R').dialog('open'); return false;" titel="Rollenzuordnung einfügen" >[NEU]</a>
   <div id="insertGRP<?=$gruppe["id"];?>R" title="Rollenzuordnung einfügen">
     <noscript><h4>Rollenzuordnung einfügen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#gruppe" method="POST">
     <ul>
     <li>Gruppe: <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?></li>
     <li>Rolle/Gremium:
      <select name="rolle_id" size="1" class="gremienauswahl" onChange="$(this).siblings('span').text($(this).find('option:selected').parent().attr('label'));">
<?php
      $last_gremium_id = -1;
      foreach ($alle_gremien as $agremium):
        if ($last_gremium_id != $agremium["gremium_id"]):
         if ($last_gremium_id != -1) { echo "</optgroup>"; }
         $last_gremium_id = $agremium["gremium_id"];
?>
       <optgroup class="forinsertGRP<?=$gruppe["id"];?>R <?=($agremium["gremium_active"] ? "gremiumactive" : "gremiuminactive");?>" label="<?php echo htmlspecialchars($agremium["gremium_name"]." ".$agremium["gremium_fakultaet"]." ".$agremium["gremium_studiengang"]." ".$agremium["gremium_studiengangabschluss"],ENT_QUOTES);?>">
<?php
	endif;
?>
        <option  class="forinsertGRP<?=$gruppe["id"];?>R <?=($agremium["rolle_active"] ? "rolleactive" : "rolleinactive");?>" value="<?=$agremium["rolle_id"];?>"><?php echo htmlspecialchars($agremium["rolle_name"],ENT_QUOTES);?></option>
<?php
      endforeach;
      if ($last_gremium_id != -1) { echo "</optgroup>"; }
?>
         </select>
         <a href="#" onClick="$('option.rolleinactive.forinsertGRP<?=$gruppe["id"];?>R,optgroup.gremiuminactive.forinsertGRP<?=$gruppe["id"];?>R').toggle(); return false;" titel="inaktive Gremien/Rolle anzeigen/ausblenden" >[inaktive Gremien/Rollen anzeigen/ausblenden]</a>
         <?php $script[] = "\$('option.rolleinactive.forinsertGRP{$gruppe["id"]}R,optgroup.gremiuminactive.forinsertGRP{$gruppe["id"]}R').hide();"; ?>
       <br/><span></span></li>
     </ul>
     <input type="hidden" name="gruppe_id" value="<?php echo $gruppe["id"];?>"/>
     <input type="hidden" name="action" value="rolle_gruppe.insert"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Zuordnung eintragen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertGRP<?=$gruppe["id"];?>R').dialog('close');"/>
     </form>
   </div>
   <?php $script[] = "\$('#insertGRP{$gruppe['id']}R').dialog({ autoOpen: false, width: 700, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editGRP{$gruppe['id']}') } });"; ?>
         </th><th>Rolle</th><th>Gremium</th><th>Fakultät</th><th>Studiengang</th></tr>
<?php
$gremien = getGruppeRolle($gruppe["id"]);
if (count($gremien) == 0):
?>
     <tr><td colspan="5"><i>Keine Gremienmitgliedschaften.</i></td></tr>
<?php
else:
foreach($gremien as $gremium):
?>
     <tr>
      <td>
   <a href="#" onClick="$('#deleteGRP<?=$gruppe["id"];?>R<?=$gremium["rolle_id"];?>').dialog('open'); return false;" titel="Rollenzuordnung aufheben" >[X]</a>
   <div id="deleteGRP<?=$gruppe["id"];?>R<?=$gremium["rolle_id"];?>" title="Rollenzuordnung aufheben">
     <noscript><h4>Rollenzuordnung aufheben</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#gruppe" method="POST">
     <ul>
     <li>Gruppe: <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?></li>
     <li>Gremium/Rolle: <?php echo htmlspecialchars($gremium["rolle_name"]." ".$gremium["gremium_name"]." ".$gremium["gremium_fakultaet"]." ".$gremium["gremium_studiengang"]." ".$gremium["gremium_studiengangabschluss"],ENT_QUOTES);?></li>
     </ul>
     <input type="hidden" name="gruppe_id" value="<?php echo $gruppe["id"];?>"/>
     <input type="hidden" name="rolle_id" value="<?php echo $gremium["rolle_id"];?>"/>
     <input type="hidden" name="action" value="rolle_gruppe.delete"/>
     <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
     <input type="submit" name="submit" value="Zuordnung aufheben"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteGRP<?=$gruppe["id"];?>R<?=$gremium["rolle_id"];?>').dialog('close');"/>
     </form>
   </div>
  <?php $script[] = "\$('#deleteGRP{$gruppe['id']}R{$gremium['rolle_id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editGRP{$gruppe['id']}') } });"; ?>
      </td>
      <td><?php echo htmlspecialchars($gremium["rolle_name"]);?></td>
      <td><?php echo htmlspecialchars($gremium["gremium_name"]);?></td>
      <td><?php echo htmlspecialchars($gremium["gremium_fakultaet"]);?></td>
      <td><?php echo htmlspecialchars($gremium["gremium_studiengang"]);
            if (!empty($gremium["gremium_studiengangabschluss"])) {
              echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).")";
            }
      ?></td>
     </tr>
<?php
endforeach;
endif;
?>
     </table>
     <h4>Personen (abgeleitet)</h4>
<?php $personen = getGruppePerson($gruppe["id"]);
     if (count($personen) == 0):
?>
       <i>Es stehen keine Personen auf der Mailingliste.</i>
<?php
     else:
?>
     <ul>
<?
     foreach ($personen as $person):
?>
      <li><?=htmlspecialchars($person);?></li>
<?
     endforeach;
?>
     </ul
<?
     endif;
?>
   </div>
   <?php
     $script[] = "\$('#editGRP{$gruppe['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowGRP{$gruppe['id']}') } });";
     $script[] = "\$('#deleteGRP{$gruppe['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowGRP{$gruppe['id']}') } });";
   ?>
 </td>
 <td><?php echo htmlspecialchars($gruppe["name"]);?></td>
 <td><?php echo htmlspecialchars($gruppe["beschreibung"]);?></td>
</tr>
<?php
endforeach;
?>
</table>
<hr/>
<ul class="pageselect">
<?php if ($_COOKIE["gruppe_start"] > 0): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gruppe_start=0#gruppe">&lt;&lt;</a></li><? endif; ?>
<?php
if ($_COOKIE["gruppe_start"] > $_COOKIE["gruppe_length"]) {
  $prev = $_COOKIE["gruppe_start"] - $_COOKIE["gruppe_length"];
} else {
  $prev = -1;
}
if ((count($alle_gruppen) > $_COOKIE["gruppe_start"] + 2 * $_COOKIE["gruppe_length"])) {
  $next = $_COOKIE["gruppe_start"] + $_COOKIE["gruppe_length"];
} else {
  $next = -1;
}
if ($_COOKIE["gruppe_length"] > 0):
 for($i = $_COOKIE["gruppe_length"] ; $i < count($alle_gruppen); $i = $i +  $_COOKIE["gruppe_length"]):
  if ($i < $_COOKIE["gruppe_start"] || $i > $_COOKIE["gruppe_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gruppe_start=<?=$i;?>#gruppe" title="<?=htmlspecialchars($alle_gruppen[$i]["email"]);?>"><?=$i;?></a></li><?
  endif;
  if ($i < $prev && $i + $_COOKIE["gruppe_length"] > $prev): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gruppe_start=<?=$prev;?>#gruppe" title="<?=htmlspecialchars($alle_gruppen[$prev]["email"]);?>">&lt;</a></li><?
  endif;
  if ($i <= $_COOKIE["gruppe_start"] && $i + $_COOKIE["gruppe_length"] > $_COOKIE["gruppe_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gruppe_start=<?=$_COOKIE["gruppe_start"];?>#gruppe">[<?=$_COOKIE["gruppe_start"];?>]</a></li><?
  endif;
  if ($i < $next && $i + $_COOKIE["gruppe_length"] > $next): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gruppe_start=<?=$next;?>#gruppe" title="<?=htmlspecialchars($alle_gruppen[$next]["email"]);?>">&gt;</a></li><?
  endif;
 endfor;
endif; ?>
<?php if ($_COOKIE["gruppe_start"] + $_COOKIE["gruppe_length"] < count($alle_gruppen)): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"])?>?gruppe_start=<?=count($alle_gruppen) - $_COOKIE["gruppe_length"];?>#gruppe">&gt;&gt;</a></li><? endif; ?>
</ul>
<form action="<?=htmlentities($_SERVER["PHP_SELF"]);?>#gruppe" method="POST">
Einträge je Seite: <input type="text" name="gruppe_length" value="<?=htmlentities($_COOKIE["gruppe_length"]);?>"/>
<input type="submit" name="submit" value="Auswählen"/><input type="reset" name="reset" value="Zurücksetzen"/>
</form>
</div>
