<div class="panel panel-default">
<div class="panel-heading">Gremienmitgliedschaften</div>
<div class="panel-body">

<table class="table table-striped">
<tr>
 <?php if ($gremienmitgliedschaften_edit): ?>
  <th></th>
 <?php endif; ?>
<th>Tätigkeit</th><th>Zeitraum</th><th class="hidden-xs">Beschluss</th></tr>
<?php
$hasInactiveAssignments = false;
if (count($gremien) == 0):
?>
<tr><td colspan="<?php echo $gremienmitgliedschaften_edit ? 4 : 3; ?>"><i>Keine Gremienmitgliedschaften.</td></tr>
<?php
else:
foreach($gremien as $gremium):
?>
<tr
<?php
if (!$gremium["active"]):
  $hasInactiveAssignments = true;
?>
  class="gremiumrolleinactive"
<?php
endif;
?>
>
<?php if ($gremienmitgliedschaften_edit): ?>
 <td class="nobr">
  <a target="_blank" href="?tab=rel_mitgliedschaft.edit&amp;rel_id=<?php echo $gremium["id"]; ?>">
  <i class="fa fa-pencil fa-fw"></i>
 </a>
  <a target="_blank" href="?tab=rel_mitgliedschaft.delete&amp;rel_id=<?php echo $gremium["id"]; ?>">
  <i class="fa fa-trash fa-fw"></i>
 </a>
 </td>
<?php endif; ?>

 <td><?php

   if ($gremienmitgliedschaften_link)
    echo "<a href=\"?tab=rolle.edit&amp;rolle_id=".$gremium["rolle_id"]."\" target=\"_blank\">";

   echo htmlspecialchars($gremium["rolle_name"]);

   if ($gremienmitgliedschaften_link)
    echo "</a>";

?>
 in
 <nobr><?php

  if ($gremienmitgliedschaften_link)
    echo "<a href=\"?tab=gremium.edit&amp;gremium_id=".$gremium["gremium_id"]."\" target=\"_blank\">";

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

  if ($gremienmitgliedschaften_link)
    echo "</a>";

?></nobr></td>
 <td>
<?php
  if (empty($gremium["von"]) && empty($gremium["bis"])) {
    echo "keine Angabe";
  } elseif (empty($gremium["von"])) {
    echo "bis ".$gremium["bis"];
  } elseif (empty($gremium["bis"])) {
    echo "seit ".$gremium["von"];
  } else {
    echo htmlspecialchars($gremium["von"])." - ".$gremium["bis"];
  }
?>
 </td>
 <td class="hidden-xs">
<?php
   echo htmlspecialchars($gremium["beschlussAm"])." ".htmlspecialchars($gremium["beschlussDurch"]);
?>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>

<?php if ($hasInactiveAssignments): ?>
<label class="checkbox">
  <input <?php if ($gremienmitgliedschaften_allByDefault) { ?>checked<?php } ?> data-toggle="toggle" type="checkbox" id="gremiumrolletoggle"> Inaktive Zuordnungen anzeigen
</label>
<?php endif; ?>

  </div> </div> <!--panel -->


<script>
$("#gremiumrolletoggle").on("change.gremiumrolle", function() {
  if ($(this).is(":checked")) {
    $("tr.gremiumrolleinactive").show();
  } else {
    $("tr.gremiumrolleinactive").hide();
  }
});
$("#gremiumrolletoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:

