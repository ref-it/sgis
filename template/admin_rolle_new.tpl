<?php

$gremium = getGremiumById($_REQUEST["gremium_id"]);
if ($gremium === false) die("Invalid Id");

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="action" value="rolle_gremium.insert"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
<input type="hidden" name="gremium_id" value="<?php echo htmlspecialchars($gremium["id"]);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Neue Rolle in
  <?php

   echo htmlspecialchars($gremium["name"])." ";

  if (!empty($gremium["studiengang"])) {
   echo htmlspecialchars($gremium["studiengang"])." ";
  }

  if (!empty($gremium["studiengangabschluss"])) {
    echo " (".htmlspecialchars($gremium["studiengangabschluss"]).") ";
  }

  if (!empty($gremium["fakultaet"])) {
   echo " Fak. ".htmlspecialchars($gremium["fakultaet"])." ";
  }

?>
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-3">Gremium</label>
    <div class="col-sm-9">
      <div class="form-control">
  <?php

   echo htmlspecialchars($gremium["name"])." ";

  if (!empty($gremium["studiengang"])) {
   echo htmlspecialchars($gremium["studiengang"])." ";
  }

  if (!empty($gremium["studiengangabschluss"])) {
    echo " (".htmlspecialchars($gremium["studiengangabschluss"]).") ";
  }

  if (!empty($gremium["fakultaet"])) {
   echo " Fak. ".htmlspecialchars($gremium["fakultaet"])." ";
  }

?>
      </div>
    </div>
  </div>

<?php

foreach ([
  "name" => "Name",
  "active" => "Rolle existent/aktiv?",
  "spiGroupId" => "sPi-Gruppen-Id",
  "numPlatz" => "Plätze",
  "wahlDurchWikiSuffix" => "Wähler",
  "wahlPeriodeDays" => "Wahlperiode",
  "wiki_members_roleAsColumnTable" => "Wiki-Seite für Mitglieder (Tabelle mit Spalten für Rollen)",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        switch($key) {
          case "wiki_members_roleAsColumnTable":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="">
           <i>(Wenn gesetzt beginnt immer mit :sgis:mitglieder:)</i>
<?php
           break;
          case "wahlPeriodeDays":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="365"> Tage
<?php
           break;
          case "wahlDurchWikiSuffix":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="">
           <i>(Suffix für Wiki-Seite mit der zu-wählen-Liste.)</i>
<?php
            break;
          case "numPlatz":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="">
           <i>(Anzahl der Vertreter, die laut Ordnung benannt werden können.)</i>
<?php
            break;
          case "spiGroupId":
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="">
           <i>(Personen dieser Rolle werden in der entsprechenden <a href="<?php echo htmlspecialchars($sPiBase)."/group/";?>" target="_blank">sPi-Gruppe</a> dargestellt.)</i>
<?php
            break;
          case"active":
?>         <select name="active" size="1" class="selectpicker" data-width="fit">
              <option value="1" selected="selected">Ja, derzeit existent</option>
              <option value="0" >Nein, derzeit nicht existent</option>
           </select><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
        }
      ?>
    </div>
  </div>

<?php

endforeach;

?>

</div> <!-- form -->

 </div>
 <div class="panel-footer">
     <input type="submit" name="submit" value="Speichern" class="btn btn-primary"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form>

<?php
// vim:set filetype=php:
