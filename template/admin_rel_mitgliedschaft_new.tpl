<?php

if (isset($_REQUEST["person_id"])) {
  $person = getPersonDetailsById($_REQUEST["person_id"]);
  if ($person === false) die("Invalid Id");
} else
  $person = false;

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
<?php if ($person !== false): ?>
<input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
<?php else: ?>
<input type="hidden" name="person_id" value="-1" id="person_id"/>
<?php endif; ?>
<?php if ($rolle !== false): ?>
<input type="hidden" name="rolle_id" value="<?php echo $rolle["id"];?>"/>
<?php else: ?>
<input type="hidden" name="rolle_id" value="-1" id="rolle_id"/>
<?php endif; ?>
<input type="hidden" name="action" value="rolle_person.insert"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Rollenzuordnung anlegen
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
  "fullname" => false,
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
    <label class="control-label col-sm-3">Person</label>
    <div class="col-sm-9">
<?php if ($person !== false) { ?>
      <div class="form-control">
        <a href="mailto:<?php echo htmlspecialchars($person["email"]); ?>">
<?php
        echo htmlspecialchars($person["name"]);
        echo htmlspecialchars(" <");
        echo htmlspecialchars($person["email"]);
        echo htmlspecialchars(">");
?>
        </a>
      </div> <!-- form-control -->
<?php } else {
 // select person
$metadata = [
#  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "unirzlogin" => "Uni",
  "username" => "sGIS",
  "active" => "<small>aktiv</small>",
 ];

?>
<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Personen anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-3">
    Personen anzeigen:
  </div>
  <div class="col-xs-12 col-sm-9">
   <select class="selectpicker tablefilter" data-column="active:name" data-table="person">
    <option value="1" selected>Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="">Alle (aktiv)</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->
<?php

 $obj = "person";
 $obj_editable = false;
 $obj_smallpageinate = true;
 $obj_selectable = "person_id";
 require dirname(__FILE__)."/admin_table.tpl";
} ?>
    </div>
  </div>

<?php

foreach ([
  "von" => "von",
  "bis" => "bis",
  "beschlussAm" => "beschlossen am",
  "beschlussDurch" => "beschlossen durch",
  "lastCheck" => "zuletzt überprüft am",
  "kommentar" => "Kommentar",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        $val = "";
        switch($key) {
          case"von":
           $val = date("Y-m-d");
          case"bis":
          case"lastCheck":
?>         <input class="form-control datepicker" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>"><?php
            break;
          case"kommentar":
?>         <textarea class="form-control" name="<?php echo htmlspecialchars($key); ?>"></textarea><?php
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
