<?php

# WANT: http://bootsnipp.com/snippets/featured/panel-table-with-filters-per-column
# + pagination
# + fast on smartphone
# + responsive

# see https://datatables.net/examples/styling/bootstrap.html

$alle_personen = getAllePerson();

$filter = Array();
$filter["name"] = Array();
$filter["email"] = Array();
$filter["unirzlogin"] = Array();
$filter["username"] = Array();
$filter["lastLogin"] = Array();

function addFilter($group, $value) {
  $filter[$group][$value] = $value;
}

foreach ($alle_personen as $i => $person):
  foreach (array_keys($filter) as $field):
    $filter[$field][$person[$field]] = $person[$field];
  endforeach;
endforeach;

asort($filter["name"]);
asort($filter["email"]);
asort($filter["unirzlogin"]);
asort($filter["username"]);
asort($filter["lastLogin"]);
$filter["canLogin"] = Array(0 => "Nein", 1 => "Ja");
asort($filter["canLogin"]);
$filter["active"] = Array(0 => "Nein", 1 => "Ja");
asort($filter["active"]);

?>

<div class="table" style="min-width:100%;">
<form action="#person" method="POST" class="tr" style="background-color: lightyellow;" enctype="multipart/form-data">
 <div class="td">Filter: <input type="submit" name="submit" value="filtern"/>
             <input type="hidden" name="filter_personen_set" value=""/>
             <input type="submit" name="submit" value="zurücksetzen"/>
     <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"].'?filter_personen_name=&filter_personen_email=&filter_personen_unirzlogin=&filter_personen_username=&filter_personen_lastLogin=&filter_personen_canLogin=&filter_personen_active=#person');?>">kein Filter</a>
 </div>
<?php

foreach (array_keys($filter) as $field):
?>
 <div class="td">
   <select name="filter_personen_<?php echo $field; ?>[]" multiple="multiple" class="selectpicker" data-live-search="true">
   <?php foreach ($filter[$field] as $key => $value): ?>
     <option value="<?php echo htmlentities($key); ?>"><?php echo htmlentities($value);?></option>
   <?php endforeach;?>
   </select>
 </div>
<?php
endforeach;
?>
</form>

<div class="tr" id="rowPhead">
 <div class="th"></div>
 <div class="th">Name</div><div class="th">eMail</div><div class="th">UniRZ-Login</div><div class="th">Benutzername</div><div class="th">letztes Login</div><div class="th">Login erlaubt?</div><div class="th">aktuell Gremienaktiv</div></div>
</div> <!-- row -->
</div>

Content

<select class="selectpicker" multiple="multiple" data-live-search="true">
  <option>Mustard</option>
  <option>Ketchup</option>
  <option>Relish</option>
</select>

<!-- vim: set filetype=php: -->
