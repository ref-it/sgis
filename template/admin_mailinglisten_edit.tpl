<?php

$mailingliste = getMailinglisteById($_REQUEST["mailingliste_id"]);
if ($mailingliste === false) die("Invalid Id");
$personen = getMailinglistePersonDetails($mailingliste["id"]);
$gremien = getMailinglisteRolle($mailingliste["id"]);

?>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST" enctype="multipart/form-data" class="ajax">
<input type="hidden" name="id" value="<?php echo $mailingliste["id"];?>"/>
<input type="hidden" name="action" value="mailingliste.update"/>
<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<div class="panel panel-default">
 <div class="panel-heading">
  Mailingliste <?php echo htmlspecialchars($mailingliste["address"]); ?>
 </div>
 <div class="panel-body">

<div class="form-horizontal" role="form">

<?php

foreach ([
  "id" => "ID",
  "address" => "Adresse",
  "password" => "Passwort",
  "url" => "Webseite (listinfo)",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">

      <?php
        switch($key) {
          case "id":
?>         <div class="form-control"><?php echo htmlspecialchars($mailingliste[$key]); ?></div><?php
            break;
          default:
?>         <input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($mailingliste[$key]); ?>"><?php
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
     <a href="?tab=mailingliste.delete&amp;mailingliste_id=<?php echo $mailingliste["id"];?>" class="btn btn-default pull-right">Löschen</a>
 </div>
</div>

</form>

<div class="panel panel-default">
 <div class="panel-heading">
  Zugeordnete Gremien und Rollen
 </div>
 <div class="panel-body">
  <table class="table table-striped">
    <tr>
      <th></th><th>Rolle</th><th>Gremium</th>
    </tr>
<?php
    if (count($gremien) == 0):
?>
    <tr><td colspan="3"><i>Es sind keine Rollen der Gruppe zugeordnet.</td></tr>
<?php
    else:
    foreach($gremien as $gremium):
?>
    <tr>
     <td class="nobr">
      <a target="_blank" href="?tab=rel_rolle_mailingliste.delete&amp;rolle_id=<?php echo $gremium["rolle_id"]; ?>&amp;mailingliste_id=<?php echo $mailingliste["id"];?>">
       <i class="fa fa-trash fa-fw"></i>
      </a>
     </td>

     <td>
      <a href="?tab=rolle.edit&amp;rolle_id=<?php echo $gremium["rolle_id"]; ?>" target="_blank">
       <?php echo htmlspecialchars($gremium["rolle_name"]); ?>
      </a>
     </td>
     <td>
      <nobr>
       <a href="?tab=gremium.edit&amp;gremium_id=<?php echo $gremium["gremium_id"]; ?>" target="_blank">
<?php
        echo htmlspecialchars($gremium["gremium_name"]);
        if (!empty($gremium["gremium_studiengang"])) {
         echo htmlspecialchars($gremium["gremium_studiengang"])." ";
        }

        if (!empty($gremium["gremium_studiengangabschluss"])) {
          echo " (".htmlspecialchars($gremium["gremium_studiengangabschluss"]).") ";
        }

        if (!empty($gremium["gremium_fakultaet"])) {
          echo " Fak. ".htmlspecialchars($gremium["gremium_fakultaet"])." ";
        }
?>
       </a>
      </nobr>
     </td>
    </tr>
    <?php
    endforeach;
    endif;
    ?>
  </table>
 </div>
</div>

<div class="panel panel-default">
 <div class="panel-heading">
  Personen (abgeleitet)
 </div>
 <div class="panel-body">
  <table class="table table-striped">
    <tr>
    <th>Name</th><th>eMail</th>
    </tr>
<?php
    if (count($personen) == 0):
?>
    <tr><td colspan="2"><i>Es ist keine Person Mitglied in dieser Gruppe.</td></tr>
<?php
    else:
    foreach($personen as $person):
?>
     <td>
      <a href="?tab=person.edit&amp;person_id=<?php echo $person["id"]; ?>" target="_blank">
       <?php echo htmlspecialchars($person["name"]); ?>
      </a>
     </td>
     <td>
       <a href="mailto:<?php echo htmlspecialchars($person["email"]); ?>" target="_blank">
         <?php echo htmlspecialchars($person["email"]); ?>
       </a>
      </nobr>
     </td>
    </tr>
    <?php
    endforeach;
    endif;
    ?>
  </table>
 </div>
</div>

<?php


// vim:set filetype=php:
