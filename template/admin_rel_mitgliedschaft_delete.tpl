<?php

$rel = getMitgliedschaftById($_REQUEST["rel_id"]);
if ($rel === false) die("Invalid Id");
$rolle = getRolleById($rel["rolle_id"]);
$gremium = getGremiumById($rel["gremium_id"]);
$person = getPersonDetailsById($rel["person_id"]);

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $rel["id"];?>"/>
<input type="hidden" name="person_id" value="<?php echo $rel["person_id"];?>"/>
<input type="hidden" name="rolle_id" value="<?php echo $rel["rolle_id"];?>"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Rollenzuordnung bearbeiten
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

  <div class="form-group">
    <label class="control-label col-sm-3">ID</label>
    <div class="col-sm-9">
      <div class="form-control"><?php echo htmlspecialchars($rel["id"]); ?></div>
    </div>
  </div>

  <div class="form-group">
    <label class="control-label col-sm-3">Gremium</label>
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
    <label class="control-label col-sm-3">Person</label>
    <div class="col-sm-9">
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
    <label class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        switch($key) {
          case"kommentar":
?>         <div class="form-control kommentar"><?php echo implode("<br>",explode("\n",htmlspecialchars($rel[$key]))); ?></div><?php
            break;
          default:
?>         <div class="form-control"><?php echo htmlspecialchars($rel[$key]); ?></div><?php
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
<div class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-2" for="action">Aktion</label>
    <div class="col-sm-10">
      <select class="form-control" name="action" size="1">
        <option value="rolle_person.disable" selected="selected">Zuordnung terminieren</option>
        <option value="rolle_person.delete">Datensatz löschen</option>
      </select>
    </div>
  </div>
</div> <!-- form -->
     <input type="submit" name="submit" value="Löschen" class="btn btn-danger"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="self.close();" class="btn btn-default"/>
 </div>
</div>

</form>

<?php
// vim:set filetype=php:
