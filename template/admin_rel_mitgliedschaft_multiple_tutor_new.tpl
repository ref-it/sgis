<?php

define('TUTOR_GREMIUM_ID_MIN', 410);
define('TUTOR_GREMIUM_ID_MAX', 437);
define('TUTOR_ROLLE_ID_MIN', 1030);
define('TUTOR_ROLLE_ID_MAX', 1069);
define('OLD_TUTOR_ROLE_ID', 1071);

$gremium_list = [];
$rolle_list = [];
$gremium_list_js = [];
$rolle_list_js = [];

for ($i = TUTOR_ROLLE_ID_MIN; $i <= TUTOR_ROLLE_ID_MAX; $i++ ){
	$rolle_list[$i] = getRolleById($i);
	if ($rolle_list[$i]){
		$rolle_list_js[$i] = ['name' => $rolle_list[$i]['name'], 'gid' => $rolle_list[$i]['gremium_id']];
	};
}
for ($i = TUTOR_GREMIUM_ID_MIN; $i <= TUTOR_GREMIUM_ID_MAX; $i++ ){
	$gremium_list[$i] = getGremiumById($i);
	if ($gremium_list[$i]){
		$gremium_list_js[$i] = ['name' => $gremium_list[$i]['name'], 'f' => $gremium_list[$i]['fakultaet'], 'stg' => $gremium_list[$i]['studiengang'], 'type' => $gremium_list[$i]['studiengangabschluss'], ];
	};
}
 ?>


<style>@import url("/sgis/css/admin_multiple_role_tutor.css");</style>
<div class="panel panel-default">
	<div class="panel-heading"><strong>Tutoren Rollenzuordnung</strong></div>
	<div class="panel-body">
		<div class="panel panel-primary">
			<div class="panel-heading"><strong>Checkliste</strong></div>
			<div class="panel-body">
				<p><strong>Dieses Tool erstellt für die neuen Tutoren einen SGIS Account und trägt die entsprechende Rollen ein.</strong></p>
				<p><strong>Tutoren werden wie folgt im SGIS angelegt:<br>
					<span style="display: inline-block; width: 60px;"></span>* 6+ Monate als 'Tutor'<br>
					<span style="display: inline-block; width: 60px;"></span>* nach 6 Monaten für ein Jahr als 'ehemalige Tutoren'<br>
					Beispiel: Tutor im WS <i>(= 10. Monat)</i><br>
					<span style="display: inline-block; width: 60px;"></span><span style="display: inline-block; width: 130px;">Tutor:</span></strong><i> von </i><span style="display: inline-block; width: 70px; padding: 0 5px;">10/Jahr</span><i> bis </i><span style="display: inline-block; width: 70px; padding: 0 5px;">04/Jahr+1</span><br>
					<span style="display: inline-block; width: 60px;"></span><span style="display: inline-block; width: 130px;"><strong>ehemalig Tutor:</strong></span><i> von </i><span style="display: inline-block; width: 70px; padding: 0 5px;">04/Jahr+1</span><i> bis </i><span style="display: inline-block; width: 70px; padding: 0 5px;">04/Jahr+2</span>
				</p>
				<p><b>1.</b> Alte Tutoren entfernt?<br> <span class="small muted">(Geschiet automatisch, wenn ein Enddatum [bis] gesetzt wurde.)</span></p>
				<p><b>2.</b> Neue Liste mit dieser Vorlage <a href="https://wiki.stura.tu-ilmenau.de/_media/stura/referat/erstiwoche/ewo_tutoren_vorlage.xlsx">(xlsx</a>|<a href="https://wiki.stura.tu-ilmenau.de/_media/stura/referat/erstiwoche/ewo_tutoren_vorlage.ods">ods)</a> erstellt?<br> <span class="small muted">(XLSX wurde in Libreoffice (German) erstellt, keine Garantie für Microsoft Office Kompatibilität.)</span></p>
				<p><strong> Die Tabellendaten können unten eingefügt werden.</strong></p>
			</div>
		</div>
		
		
		<div class="panel panel-info">
			<div class="panel-heading"><strong>Optionen</strong></div>
			<div class="panel-body options_data form-horizontal">
			
				<input type="hidden" id="url" value="/sgis/admin.php" id="rolle_id"/>
				<input type="hidden" name="rolle_id" value="-1" id="rolle_id"/>
				<input type="hidden" name="action" value="rolle_person.bulkinsert"/>
				<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>
				
				<input type="hidden" class="old_tutoren_id" value="<?= OLD_TUTOR_ROLE_ID ?>"/>
				
				<div class="form-group">
					<label class="control-label col-sm-3">Rollen ID Minimum</label>
					<div class="col-sm-9">
						<div class="form-control role_min"><?= TUTOR_ROLLE_ID_MIN ?></div>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3">Rollen ID Maximum</label>
					<div class="col-sm-9">
						<div class="form-control role_max"><?= TUTOR_ROLLE_ID_MAX ?></div>
					</div>
				</div>
				
				<hr>
				
				<div class="form-group">
					<label class="control-label col-sm-3">Semester</label>
					<div class="col-sm-9">
						<select size="1" class="selectpicker tutor_semester" data-width="fit">
							<option value="ss">SS</option>
							<option value="ws" selected="selected">WS</option>
						</select>
					</div>
				</div>
				
				<div class="form-group">
					<label class="control-label col-sm-3">Jahr</label>
					<div class="col-sm-9">
						<input class="form-control tutor_year" type="number" min="<?= (date_create()->format('Y') - 10) ?>" max="<?= (date_create()->format('Y') + 10) ?>" step="1" value="<?= date_create()->format('Y') ?>" />
					</div>
				</div>
				
				<div class="form-group">
					<label class="control-label col-sm-3">ignoriere 'von', berechne automatisch (Semesterbegin)</label>
					<div class="col-sm-9">
						<input class="form-control ignore_from_use_auto" type="checkbox">
					</div>
				</div>
				
				<div class="form-group">
					<label class="control-label col-sm-3">ignoriere 'von', nutze aktuelles Datum</label>
					<div class="col-sm-9">
						<input class="form-control ignore_from_use_current_date" type="checkbox" checked>
					</div>
				</div>
				
				<div class="form-group">
					<label class="control-label col-sm-3">ignoriere 'bis', berechne automatisch (Semesterende)</label>
					<div class="col-sm-9">
						<input class="form-control ignore_until_use_auto" type="checkbox" checked>
					</div>
				</div>
				
				<div class="form-group">
					<label class="control-label col-sm-3">extra, zeitversetzten Eintrag auf ehemaliger Tutoren Mailingliste</label>
					<div class="col-sm-9">
						<input class="form-control create_old_tutor" type="checkbox" checked>
					</div>
				</div>

				<hr>
				
				<?php
					foreach ([
						"von" => "von",
						"bis" => "bis",
						"beschlussAm" => "beschlossen am",
						"beschlussDurch" => "beschlossen durch",
						"lastCheck" => "zuletzt überprüft am",
						"kommentar" => "Kommentar",
						"duplicate" => "Bei bestehender aktiver Zuordnung",
						"personfromuni" => "Person bei Bedarf mit Daten aus Uni-LDAP anlegen",
					//	"infomail" => "Personen per E-Mail informieren",
						] as $key => $desc):
				?>

				<div class="form-group">
					<label for="<?php echo htmlspecialchars($key); ?>" class="control-label col-sm-3"><?php echo htmlspecialchars($desc); ?></label>
					<div class="col-sm-9">
					<?php
						$val = "";
						switch($key) {
							case "personfromuni": ?>
								<select name="<?php echo htmlspecialchars($key); ?>" size="1" class="selectpicker" data-width="fit">
									<option value="1" selected="selected">Ja</option>
									<option value="0">Nein</option>
								</select><?php
							break;
							case "infomail": ?>
								<select name="<?php echo htmlspecialchars($key); ?>" size="1" class="selectpicker" data-width="fit">
									<option value="1">Ja</option>
									<option value="0" selected="selected">Nein</option>
								</select><?php
							break;
							case "duplicate": ?>
								<select name="<?php echo htmlspecialchars($key); ?>" size="1" class="selectpicker" data-width="fit">
									<option value="skip" selected="selected">Person nicht hinzufügen</option>
									<option value="ignore" >Person dennoch hinzufügen</option>
								</select><?php
							break;
							case"von":
								$val = date("Y-m-d");
							case"bis":
							case"lastCheck": ?>
								<input class="form-control datepicker" type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>"><?php
							break;
							case"kommentar": ?>
								<textarea class="form-control" name="<?php echo htmlspecialchars($key); ?>"></textarea><?php
							break;
							default: ?>
								<input class="form-control" type="text" name="<?php echo htmlspecialchars($key); ?>" value=""><?php
						}
					?>
					</div>
				</div>

				<?php
					endforeach;
				?>
				
			</div>
		</div>
		
		<div class="panel panel-warning">
			<div class="panel-heading"><strong>Daten</strong></div>
			<div class="panel-body">
				<div class="data_list form-horizontal"></div>
				<hr>
				<div class="data_add form-horizontal">
					<div class="form-group">
						<label class="control-label col-sm-3">Hinzufügen</label>
						<div class="col-sm-4">
							<input class="form-control i_add_role_id" type="text" value="" placeholder="Rollen ID">
						</div>
						<div class="col-sm-4">
							<input class="form-control i_add_usermail" type="text" value="" placeholder="UNI E-Mail Adresse">
						</div>
						<div class="col-sm-1">
							<button type="button" class="btn_add_usermail btn btn-success form-control"><i class="fa fa-fw fa-plus"></i></button>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">Tabelle hinzufügen</label>
						<div class="col-sm-9">
							<textarea class="form-control paste_area" placeholder="Tabelle markieren, kopieren und hier einfügen"></textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="panel panel-danger">
			<div class="panel-body">
				<div class="form-horizontal">
					<button type="button" class="btn_data_check btn btn-success">Überprüfen</button>
					<button type="button" class="btn_data_empty btn btn-warning">Daten leeren</button>
					<button type="button" style="display: none;" class="btn_data_submit btn btn-primary">Speichern</button>
				</div>
			</div>
		</div>
		
	</div>
</div>
<script type="text/javascript">
	const gremuim_data = <?= json_encode($gremium_list_js, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);  ?>;
	const rolle_data = <?= json_encode($rolle_list_js, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);  ?>;
</script>
<script type="text/javascript" src="/sgis/js/admin_multiple_role_tutor.js"></script>

<?php

// vim:set filetype=php:
