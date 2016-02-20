<div class="panel panel-default">
<div class="panel-heading">Mailinglisten</div>
<div class="panel-body">

<table class="table table-striped">
<tr>
<?php if($gremienmailinglisten_edit):?>
  <th>
    <a target="_blank" href="?tab=rel_rolle_mailingliste.new&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
  </th>
<?php endif; ?>
  <th>Adresse</th><th class="hidden-xs">Webseite</th>
</tr>
<?php
if (count($mailinglisten) == 0):
?>
<tr><td colspan="<?php echo $gremienmailinglisten_edit ? 3 :2; ?>"><i>Keine Mailinglisten.</td></tr>
<?php
else:
foreach($mailinglisten as $mailingliste):
?>
<tr>
<?php if($gremienmailinglisten_edit):?>
 <td>
    <a target="_blank" href="?tab=rel_rolle_mailingliste.delete&amp;rolle_id=<?php echo $rolle["id"]; ?>&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>" target="_blank"><i class="fa fa-fw fa-trash"></i></a>
 </td>
<?php endif; ?>
 <td>
<!--  <a target="_blank" href="?tab=mailingliste.edit&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>" target="_blank"><i class="fa fa-pencil"></i></a> -->
<!--  <a href="mailto:<?php echo $mailingliste["address"]; ?>">
  <?php echo htmlspecialchars($mailingliste["address"]);?>
 </a> -->
 <a target="_blank" href="?tab=mailingliste.edit&amp;mailingliste_id=<?php echo $mailingliste["id"]; ?>" target="_blank">
  <?php echo htmlspecialchars($mailingliste["address"]);?>
 </a>
</td>
 <td class="hidden-xs">
  <a target="_blank" href="<?php echo $mailingliste["url"]; ?>">
 <?php echo htmlspecialchars($mailingliste["url"]);?>
 </a>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>

  </div> </div> <!--panel -->


<?php

// vim:set filetype=php:

