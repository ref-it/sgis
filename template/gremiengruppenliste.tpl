<div class="panel panel-default">
<div class="panel-heading">Gruppen</div>
<div class="panel-body">

<table class="table table-striped">
<tr>
<?php if($gremiengruppen_edit): ?>
  <th>
   <a href="?tab=rel_rolle_gruppe.new&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a>
  </th>
<?php endif;?>
  <th>Name</th><th>Beschreibung</th>
</tr>
<?php
if (count($gruppen) == 0):
?>
<tr><td colspan="<?php echo $gremiengruppen_edit ? 3 : 2; ?>"><i>Keine Gruppen.</td></tr>
<?php
else:
foreach($gruppen as $gruppe):
?>
<tr>
<?php if($gremiengruppen_edit): ?>
  <td>
   <a href="?tab=rel_rolle_gruppe.delete&amp;rolle_id=<?php echo $rolle["id"]; ?>&amp;gruppe_id=<?php echo $gruppe["id"]; ?>" target="_blank"><i class="fa fa-fw fa-trash"></i></a>
  </td>
<?php endif;?>
 <td>
  <a target="_blank" href="?tab=gruppe.edit&amp;gruppe_id=<?php echo $gruppe["id"]; ?>">
  <?php echo htmlspecialchars($gruppe["name"]);?>
 </a>
</td>
 <td><?php echo htmlspecialchars($gruppe["beschreibung"]);?></td>
</tr>
<?php
endforeach;
endif;
?>
</table>

  </div> </div> <!--panel -->


<?php

// vim:set filetype=php:

