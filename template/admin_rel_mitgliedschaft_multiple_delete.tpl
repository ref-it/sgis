<?php

if (isset($_REQUEST["rolle_id"])) {
  $rolle = getRolleById($_REQUEST["rolle_id"]);
  if ($rolle === false) die("Invalid Id");
  $gremium = getGremiumById($rolle["gremium_id"]);
} else {
  $rolle = false;
  $gremium = false;
}

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<?php if ($rolle !== false): ?>
<input type="hidden" name="rolle_id" value="<?php echo $rolle["id"];?>"/>
<?php else: ?>
<input type="hidden" name="rolle_id" value="-1" id="rolle_id"/>
<?php endif; ?>
<input type="hidden" name="action" value="rolle_person.bulkdisable"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Rollenzuordnung deaktivieren
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<!-- select gremium -->

  <div class="form-group">
    <label class="control-label col-sm-3">Rolle / Gremium</label>
    <div class="col-sm-9">
<?php
if ($rolle !== false) {

?>
      <div class="form-control">
<?php

   echo htmlspecialchars($rolle["name"])." in ";

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
      </div> <!-- form-control -->
<?php

} else {

 $metadata = [
  "rolle_name" => "Rolle",
  "gremium_name" => "Gremium",
  "gremium_fakultaet" => "Fak.",
  "gremium_studiengang" => "Fach",
  "gremium_studiengangabschluss" => "Abschluss",
  "active" => "Aktiv",
 ];

?>
<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Gremien anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-3">
    Gremien anzeigen:
  </div>
  <div class="col-xs-12 col-sm-9">
   <select class="selectpicker tablefilter" data-column="active:name" data-table="rolle">
    <option value="1" selected>Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="">Alle (aktiv)</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->
<?php

 $obj = "rolle";
 $obj_editable = false;
 $obj_smallpageinate = true;
 $obj_selectable = "rolle_id";
 $obj_order = '[[1, "asc"], [0, "asc"]]';
 require dirname(__FILE__)."/admin_table.tpl";

}

?>
    </div>
  </div>

  <div class="form-group">
    <label class="control-label col-sm-3">Personen (eMail)</label>
    <div class="col-sm-9">
      <textarea name="email" class="form-control" placeholder="eine Adresse je Zeile"></textarea>
    </div>
  </div>

<?php

foreach ([
  "bis" => "bis",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        switch($key) {
          case"bis":
?>         <input class="form-control datepicker" type="text" name="<?php echo htmlspecialchars($key); ?>" value="" placeholder="optional"><?php
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
     <input type="submit" name="submit" value="Löschen" class="btn btn-danger"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form>

<?php
// vim:set filetype=php:
