<?php

$metadata = [
#  "id" => "ID",
  "name" => "Name",
  "email" => "eMail",
  "unirzlogin" => "Uni",
  "username" => "sGIS",
  "lastLogin" => "Login",
  "canLogin" => "<small>Sperre</small>",
  "active" => "<small>aktiv</small>",
  "hasUniMail" => false,
  "wikiPage" => false,
 ];

?>

<div class="panel panel-default">
 <div class="panel-heading">Filter <span class="visible-xs-inline">: Personen anzeigen</span></div>
 <div class="panel-body">
  <div class="hidden-xs col-sm-4">
    Personen anzeigen:
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="canLogin:name">
    <option value="0">Nur gesperrte</option>
    <option value="1" selected>Nur ungesperrte</option>
    <option value="">Alle (Sperre)</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-4">
   <select class="selectpicker tablefilter" data-column="active:name">
    <option value="1">Nur aktive</option>
    <option value="0">Nur inaktive</option>
    <option value="" selected>Alle (aktiv)</option>
   </select>
  </div>
  <div class="col-xs-12 col-sm-12">
   <select class="selectpicker tablefilter" data-column="hasUniMail:name">
    <option value="1">Nur mit Uni-Mail</option>
    <option value="0">Nur ohne Uni-Mail</option>
    <option value="" selected>Alle (Uni-Mail)</option>
   </select>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<?php
 $obj = "person";
 $obj_editable = true;
 $obj_smallpageinate = false;
 $obj_selectable = false;

 require dirname(__FILE__)."/admin_table.tpl";

?>
<div class="panel panel-default">
 <div class="panel-heading">Informationen</div>
 <div class="panel-body">
  <div class="col-cs-12 col-sm-4">
   <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST" class="form-horizontal" role="form">
    <input type="hidden" name="action" value="person.duplicate"/>
    <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
    <input type="submit" name="submit" value="Personen-Merge-Vorschläge anzeigen" class="btn btn-primary"/>
   </form>
  </div>
 </div> <!-- panel-body -->
</div> <!-- panel -->

<?php
// vim: set filetype=php:
