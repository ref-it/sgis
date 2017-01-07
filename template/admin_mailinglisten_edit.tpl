<?php

$mailingliste = getMailinglisteById($_REQUEST["mailingliste_id"]);
if ($mailingliste === false) die("Invalid Id");
$personen = getMailinglistePersonDetails($mailingliste["id"]);
$gremien = getMailinglisteRolle($mailingliste["id"]);
$mailman = getMailinglisteMailmanByMailinglisteId($mailingliste["id"]);

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
  "adminurl" => "Webseite (Admin)",
 ] as $key => $desc):

?>

  <div class="form-group">
    <label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-2"><?php echo htmlspecialchars($desc); ?></label>
    <div class="col-sm-10">

      <?php
        switch($key) {
          case "id":
?>         <div class="form-control"><?php echo htmlspecialchars($mailingliste[$key]); ?></div><?php
            break;
          case "adminurl":
           $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"]);
?>         <div class="form-control"><a href="<?php echo htmlspecialchars($url); ?>" target="_blank"><?php echo htmlspecialchars($url); ?></a></div><?php
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
     <a href="export-mailingliste.php?mailingliste_id=<?php echo $mailingliste["id"];?>" class="btn btn-default" target="_blank">Mailman aktualisieren</a>
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
      <th>
        <a href="?tab=rel_rolle_mailingliste.new&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
      </th>
      <th>Rolle</th><th>Gremium</th>
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
        echo htmlspecialchars($gremium["gremium_name"])." ";
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
  Mailman-Einstellungen
 </div>
 <div class="panel-body">
  <table class="table table-striped">
    <tr>
      <th>
        <a href="?tab=mailingliste_mailman.new&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
      </th>
      <th>Feld</th><th>Priorität</th><th>Änderungsmodus</th><th>Wert</th>
    </tr>
<?php
    if (count($mailman) == 0):
?>
    <tr><td colspan="5"><i>Es sind keine Einstellungen der Mailingliste zugeordnet.</td></tr>
<?php
    else:
    foreach($mailman as $setting):
?>
    <tr>
     <td class="nobr">
<?php if($setting["mailingliste_id"] === NULL) { ?>
      <i class="fa fa-fw fa-globe" aria-hidden="true" title="Globale Einstellung für alle Mailinglisten"></i>
<?php } ?>
      <a target="_blank" href="?tab=mailingliste_mailman.delete&amp;mailingliste_mailman_id=<?php echo $setting["id"]; ?>&amp;mailingliste_id=<?php echo $mailingliste["id"];?>">
       <i class="fa fa-trash fa-fw"></i>
      </a>
      <a target="_blank" href="?tab=mailingliste_mailman.edit&amp;mailingliste_mailman_id=<?php echo $setting["id"]; ?>&amp;mailingliste_id=<?php echo $mailingliste["id"];?>">
       <i class="fa fa-pencil fa-fw"></i>
      </a>
     </td>

     <td>
<?php
       $url = str_replace("mailman/listinfo", "mailman/admin", $mailingliste["url"]);
       $url .= "?VARHELP=".trim($setting["url"],"/")."/".$setting["field"];
       echo "<a href=\"".htmlspecialchars($url)."\" target=\"_blank\">";
       echo htmlspecialchars($setting["field"]);
       echo "</a>";
?>
     </td>
     <td>
       <?php echo htmlspecialchars($setting["priority"]); ?>
     </td>
     <td>
<?php
       if (isset($mailmanSettingModes[$setting["mode"]])) {
         echo htmlspecialchars($mailmanSettingModes[$setting["mode"]]);
       } else {
         echo htmlspecialchars($setting["mode"]);
       }
?>
     </td>
     <td><span class="nowrap">
       <?php echo implode("<br>\n", explode("\n", htmlspecialchars($setting["value"]))); ?>
     </span></td>
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
?>
    <tr><td colspan="2"><i>Anzahl der Personen: <?php echo count($personen); ?></i></td></tr>
<?php
    endif;
?>
  </table>
 </div>
</div>

<?php


// vim:set filetype=php:
