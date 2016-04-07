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
<input type="hidden" name="action" value="rolle_person.update"/>
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
    <label for="<?php echo htmlspecialchars($key); ?>"  class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-9">

      <?php
        switch($key) {
          case"von":
          case"bis":
          case"lastCheck":
?>         <input class="form-control datepicker" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($rel[$key]); ?>"><?php
            break;
          case"kommentar":
?>         <textarea class="form-control" name="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($rel[$key]); ?></textarea><?php
            break;
          case "id":
?>         <div class="form-control"><?php echo htmlspecialchars($rel[$key]); ?></div><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($rel[$key]); ?>"><?php
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
     <a href="?tab=rel_mitgliedschaft.delete&amp;rel_id=<?php echo $rel["id"];?>" class="btn btn-default pull-right">Löschen</a>
 </div>
</div>

</form>

<?php
// vim:set filetype=php:
