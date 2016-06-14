<div class="panel panel-default">
<div class="panel-heading">Personen</div>
<div class="panel-body">

<table class="table table-striped">
<tr>
<?php if ($gremienpersonen_edit): ?>
  <th>
   <a href="?tab=rel_mitgliedschaft.new&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
  </th>
<?php endif; ?>
  <th>Name</th><th>eMail</th><th class="hidden-xs">Zeitraum</th><th class="hidden-xs">Beschluss</th><th class="hidden-xs">Kommentar</th>
</tr>
<?php
$hasInactive = false;
if (count($personen) == 0):
?>
<tr><td colspan="<?php echo $gremienpersonen_edit ? 6 : 5; ?>"><i>Keine Personen.</td></tr>
<?php
else:
foreach($personen as $person):
?>
<tr
<?php
if (!$person["active"]):
  $hasInactive = true;
?>
  class="rollepersoninactive"
<?php
endif;
?>
>
<?php if ($gremienpersonen_edit): ?>
<td class="nobr">
  <a target="_blank" href="?tab=rel_mitgliedschaft.edit&amp;rel_id=<?php echo $person["rel_id"]; ?>">
  <i class="fa fa-pencil fa-fw"></i>
 </a>
  <a target="_blank" href="?tab=rel_mitgliedschaft.delete&amp;rel_id=<?php echo $person["rel_id"]; ?>">
  <i class="fa fa-trash fa-fw"></i>
 </a>
</td>
<?php endif; ?>
 <td>
  <a target="_blank" href="?tab=person.edit&amp;person_id=<?php echo $person["id"]; ?>">
  <?php echo htmlspecialchars($person["name"]);?>
 </a>
</td>
 <td>
<?php
  $emails = explode(",", $person["email"]);
?>
  <a href="mailto:<?php echo htmlspecialchars($emails[0]); ?>" title="<?php echo htmlspecialchars($person["email"]); ?>">
  <?php echo htmlspecialchars($emails[0]);?>
 </a>
</td>
 <td class="hidden-xs"><?php
            if (empty($person["von"]) && empty($person["bis"])) {
             echo "keine Angabe";
            } elseif (empty($person["von"])) {
             echo "bis ".$person["bis"];
            } elseif (empty($person["bis"])) {
             echo "seit ".$person["von"];
            } else {
             echo htmlspecialchars($person["von"])." - ".$person["bis"];
            }
            ?></td>
 <td class="hidden-xs"><?php echo htmlspecialchars($person["beschlussAm"])." ".htmlspecialchars($person["beschlussDurch"]); ?></td>
 <td class="hidden-xs"><div class="kommentar"><?php echo str_replace("\n","<br/>",htmlspecialchars($person["kommentar"]));?></div></td>
</tr>
<?php
endforeach;
endif;
?>
</table>

<?php if ($hasInactive): ?>
<label class="checkbox">
  <input data-toggle="toggle" type="checkbox" id="rollepersontoggle"> Inaktive Einträge anzeigen
</label>
<?php endif; ?>
<?php if ($gremienpersonen_edit): ?>
   <a class="btn btn-default" title="Mehrfacheintragung" href="?tab=rel_mitgliedschaft_multiple.new&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus-square"></i> Mehrfacheintragung</i></a>
   <a class="btn btn-default" title="Mehrfachaustragung" href="?tab=rel_mitgliedschaft_multiple.delete&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-trash-o"></i> Mehrfachaustragung</a>
<?php endif; ?>

  </div> </div> <!--panel -->


<script>
$("#rollepersontoggle").on("change.rolleperson", function() {
  if ($(this).is(":checked")) {
    $("tr.rollepersoninactive").show();
  } else {
    $("tr.rollepersoninactive").hide();
  }
});
$("#rollepersontoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:

