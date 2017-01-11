<?php

$mailman = getMailinglisteMailmanById($_REQUEST["mailingliste_mailman_id"]);
if ($mailman["mailingliste_id"] !== NULL) {
  $mailingliste = getMailinglisteById($mailman["mailingliste_id"]);
  if ($mailingliste === false) die("Invalid Id");
}

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $mailman["id"];?>"/>
<input type="hidden" name="action" value="mailingliste_mailman.delete"/>
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
          case "mailingliste_id":
?>      <div class="form-control nowrap"><?php
            if ($mailman[$key] === null) {
?>            alle Mailinglisten<?php
            } else {
?>            Mailingliste <?php echo htmlspecialchars($mailingliste["address"]);
            }
?>      </div><?php
            break;
          case "mode":
?>      <div class="form-control nowrap"><?php
            if (!isset($mailmanSettingModes[$mailman[$key]]))
              $mailmanSettingModes[$mailman[$key]] = $mailman[$key];

            echo htmlspecialchars( $mailmanSettingModes[$mailman[$key]] );
?>      </div><?php
            break;
          case "value":
            echo implode("<br>\n", explode("\n", htmlspecialchars($mailman[$key])));
            break;
          default:
?>      <div class="form-control nowrap"><?php
            echo htmlspecialchars($mailman[$key]);
?>      </div><?php
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
