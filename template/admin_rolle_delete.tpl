<?php

$rolle = getRolleById($_REQUEST["rolle_id"]);
if ($rolle === false) die("Invalid Id");
$gremium = getGremiumById($rolle["gremium_id"]);
$personen = getRollePersonen($rolle["id"]);
$gruppen = getRolleGruppen($rolle["id"]);
$mailinglisten = getRolleMailinglisten($rolle["id"]);


?>

<form method="POST" action="<?php echo $_SERVER["PHP_SELF"];?>" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $rolle["id"];?>"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  <?php echo htmlspecialchars($rolle["name"]); ?> in
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

<?php

foreach ([
  "id" => "ID",
  "gremium_id" => "Gremium",
  "name" => "Name",
  "active" => "Rolle existent/aktiv?",
  "spiGroupId" => "sPi-Gruppen-Id",
  "numPlatz" => "Plätze",
  "wahlDurchWikiSuffix" => "Wähler",
  "wahlPeriodeDays" => "Wahlperiode",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">
      <div class="form-control">
      <?php
        switch($key) {
          case"gremium_id":

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

            break;
          case "active":
            echo htmlspecialchars($rolle["$key"] ? "ja" : "nein");
            break;
          case "wahlPeriodeDays":
            echo htmlspecialchars($rolle["$key"])." Tage";
            break;
          default:
            echo htmlspecialchars($rolle["$key"]);
            break;
        }
      ?>
      </div>
    </div>
  </div>

<?php

endforeach;

?>

</div> <!-- form -->

 </div>
 <div class="panel-footer">
<div class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-2" for="action">Aktion</label>
    <div class="col-sm-10">
      <select class="form-control" name="action" size="1">
        <option value="rolle_gremium.disable" selected="selected">Rolle deaktivieren</option>
        <option value="rolle_gremium.delete">Rolle löschen</option>
      </select>
    </div>
  </div>
</div> <!-- form -->
     <input type="submit" name="submit" value="Löschen" class="btn btn-danger"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form> <!-- form -->

<!-- Personen -->
<?php
$gremienpersonen_edit = false;
require ("../template/gremienpersonenliste.tpl");
?>

<!-- Gruppen -->
<?php
$gremiengruppen_edit = false;
require ("../template/gremiengruppenliste.tpl");
?>

<!-- Mailinglisten -->
<?php
$gremienmailinglisten_edit = false;
require ("../template/gremienmailinglistenliste.tpl");
?>

<?php
// vim:set filetype=php:

// vim:set filetype=php:
