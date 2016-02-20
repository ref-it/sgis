<?php

$mailingliste = getMailinglisteById($_REQUEST["mailingliste_id"]);
if ($mailingliste === false) die("Invalid Id");

$rolle = getRolleById($_REQUEST["rolle_id"]);
if ($rolle === false) die("Invalid Id");
$gremium = getGremiumById($rolle["gremium_id"]);

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="mailingliste_id" value="<?php echo $mailingliste["id"];?>"/>
<input type="hidden" name="rolle_id" value="<?php echo $rolle["id"];?>"/>
<input type="hidden" name="action" value="rolle_mailingliste.delete"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Mailinglisten - Rollen - Zuordnung löschen
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<!-- select gremium -->

  <div class="form-group">
    <label class="control-label col-sm-3">Rolle / Gremium</label>
    <div class="col-sm-9">
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
    </div>
  </div>

  <div class="form-group">
    <label class="control-label col-sm-3">Mailingliste</label>
    <div class="col-sm-9">
      <div class="form-control">
<?php
        echo htmlspecialchars($mailingliste["address"]);
?>
      </div> <!-- form-control -->
    </div>
  </div>

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
