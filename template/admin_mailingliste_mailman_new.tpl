<?php

$mailingliste = getMailinglisteById($_REQUEST["mailingliste_id"]);
if ($mailingliste === false) die("Invalid Id");

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="action" value="mailingliste_mailman.insert"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Neue Mailinglisteneinstellung
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "mailingliste_id" => "Anwenden auf",
  "url" => "Adresse",
  "field" => "Feld",
  "mode" => "Änderungsmodus",
  "priority" => "Priorität",
  "value" => "Wert",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">

      <?php
        switch($key) {
          case "mailingliste_id":
?>         <select class="form-control" name="<?php echo htmlspecialchars($key); ?>">
             <option value="">alle Mailinglisten</option>
             <option value="<?php echo htmlspecialchars($mailingliste["id"]); ?>" selected="selected">Mailingliste <?php echo htmlspecialchars($mailingliste["address"]); ?></option>
           </select>
<?php
            break;
          case "mode":
?>         <select class="form-control" name="<?php echo htmlspecialchars($key); ?>"><?php
           foreach ($mailmanSettingModes as $mk => $mv) {
?>           <option value="<?php echo htmlspecialchars($mk); ?>"><?php echo htmlspecialchars($mv); ?></option><?php
           }
?>
           </select><?php
            break;
          case "value":
?>         <textarea class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>"></textarea><?php
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
