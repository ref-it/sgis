<?php

$mailingliste = getMailinglisteById($_REQUEST["mailingliste_id"]);
if ($mailingliste === false) die("Invalid Id");
$mailman = getMailinglisteMailmanById($_REQUEST["mailingliste_mailman_id"]);

if ($mailingliste["id"] !== $mailman["mailingliste_id"] && $mailman["mailingliste_id"] !== NULL)
  die("Unzulässiger Aufruf. Diesen Fall bildet das Formular nicht ab.");

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $mailman["id"];?>"/>
<input type="hidden" name="action" value="mailingliste_mailman.update"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Mailinglisteneinstellung #<?php echo htmlspecialchars($mailman["id"]); ?>
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "id" => "ID",
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
          case "id":
?>         <div class="form-control"><?php echo htmlspecialchars($mailman[$key]); ?></div><?php
            break;
          case "mailingliste_id":
?>         <select class="form-control" name="<?php echo htmlspecialchars($key); ?>">
             <option value="" <?php if ($mailman[$key] === null) { echo "selected=\"selected\""; }?>>alle Mailinglisten</option>
             <option value="<?php echo htmlspecialchars($mailingliste["id"]); ?>"  <?php if ($mailman[$key] === $mailingliste["id"]) { echo "selected=\"selected\""; }?>>Mailingliste <?php echo htmlspecialchars($mailingliste["address"]); ?></option>
           </select>
<?php
            break;
          case "mode":
?>         <select class="form-control" name="<?php echo htmlspecialchars($key); ?>"><?php
           if (!isset($mailmanSettingModes[$mailman[$key]]))
             $mailmanSettingModes[$mailman[$key]] = $mailman[$key];

           foreach ($mailmanSettingModes as $mk => $mv) {
?>           <option value="<?php echo htmlspecialchars($mk); ?>" <?php if ($mk == $mailman[$key]) { echo "selected=\"selected\""; } ?>><?php echo htmlspecialchars($mv); ?></option><?php
           }
?>
           </select><?php
            break;
          case "value":
?>         <textarea class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($mailman[$key]); ?></textarea><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($mailman[$key]); ?>"><?php
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
     <a href="?tab=mailingliste_mailman.delete&amp;mailingliste_mailman_id=<?php echo $mailman["id"];?>" class="btn btn-default pull-right">Löschen</a>
 </div>
</div>

</form>

<?php


// vim:set filetype=php:
