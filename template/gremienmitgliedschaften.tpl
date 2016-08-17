<div class="panel panel-default">
<div class="panel-heading">Gremienmitgliedschaften</div>
<div class="panel-body">

<table class="table tablegremiumrolle table-showall">
<tr>
 <?php if ($gremienmitgliedschaften_edit): ?>
  <th>
   <a href="?tab=rel_mitgliedschaft.new&amp;person_id=<?php echo $person["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
  </th>
 <?php endif; ?>
 <th>Tätigkeit</th>
 <th>Zeitraum</th>
 <th class="hidden-xs">
  Beschluss
  <?php if ($gremienmitgliedschaften_edit): ?>
   / Kommentar
  <?php endif; ?>
 </th>
</tr>
<?php
$hasInactiveAssignments = false;
if (count($gremien) == 0):
?>
<tr><td colspan="<?php echo $gremienmitgliedschaften_edit ? 4 : 3; ?>"><i>Keine Gremienmitgliedschaften.</td></tr>
<?php
else:
$iall = 0; $iactive=0;
foreach($gremien as $gremium):
  $cssclass = [];
  $iall++;
  if (!$gremium["active"]) {
    $hasInactiveAssignments = true;
    $cssclass[] = "inactiverow";
  } else {
    $cssclass[] = "activerow";
    $iactive++;
  }
  if ($iall % 2 == 0)
    $cssclass[] = "alleven";
  else
    $cssclass[] = "allodd";
  if ($iactive % 2 == 0)
    $cssclass[] = "activeeven";
  else
    $cssclass[] = "activeodd";
?>
<tr class="<?php echo implode(" ", $cssclass); ?>">
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
  $a = [];
  $a[] = trim($gremium["beschlussAm"]." ".$gremium["beschlussDurch"]);
  if ($gremienmitgliedschaften_edit) {
   $a[] = trim($gremium["kommentar"]);
  }
  // removes all NULL, FALSE and Empty Strings but leaves 0 (zero) values
  $a = array_filter( $a, 'strlen' );
  $a = array_map("htmlspecialchars", $a);
  echo implode("<br>", $a);
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
    $("table.tablegremiumrolle").addClass("table-showall");
    $("table.tablegremiumrolle").removeClass("table-showactive");
  } else {
    $("table.tablegremiumrolle").addClass("table-showactive");
    $("table.tablegremiumrolle").removeClass("table-showall");
  }
});
$("#gremiumrolletoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:

