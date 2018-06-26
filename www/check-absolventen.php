<?php
/**
 * Created by PhpStorm.
 * User: lu_kors
 * Date: 12.04.18
 * Time: 21:13
 */

global $ADMINGROUP,$pdo;

//https://secure.php.net/manual/en/function.strftime.php
$datePattern = "%B %G";
setlocale(LC_ALL, 'de_DE', 'deu_deu');

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);
include "../template/header.tpl";


?>
<div class="container">
<form action="admin.php?tab=export">
<button type="submit" class="btn btn-secondary" style="margin: 10px 0px"><i class="fa fa-fw fa-arrow-left"></i>Zurück</button>
</form>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>"/>

    <div class="panel panel-default">
        <div class="panel-heading">
            Prüfe Liste von Absolventen
        </div>
        <div class="panel-body">

            <div class="alert alert-info"> Jede Zeile wird als neue zu suchende Person Interpretiert. Vornamen und Nachnamen werden an Leerzeichen getrennt. Es wird jeder Treffer angezeigt in dem wenigstens ein Vor- und ein Nachname vorkommt.</div>
            <div class="form-group">
                <div class="col-sm-6">
                    <label for="vornamen" class="control-label">Vornamen</label>
                    <textarea rows="10" name="vornamen" style="max-width: 100%;min-width: 100%" class="form-control"><?= isset($_POST["vornamen"])? htmlspecialchars(trimMe($_POST["vornamen"])):""?></textarea>
                </div>
                <div class="col-sm-6">
                    <label for="namen" class="control-label">Nachnamen</label>
                    <textarea rows="10" name="namen" style="max-width: 100%;min-width: 100%" class="form-control"><?= isset($_POST["namen"])? htmlspecialchars(trimMe($_POST["namen"])):""?></textarea>
                </div>
                
            </div> <!-- form -->
            
        </div>

        <div class="panel-footer">
            <input type="submit" name="submit" value="Prüfen" class="btn btn-primary"/>
        </div>
    </div>

<?php

foreach ( [ "namen", "vornamen" ] as $key ) {
  if (!isset($_POST[$key])) continue;
  $_POST[$key] = trimMe(explode(PHP_EOL, trim($_POST[$key])));
}

$res = [];
if (isset($_POST["nonce"]) && $_POST["nonce"] == $nonce &&
    isset($_POST["namen"]) && isset($_POST["vornamen"]) &&
    !empty($_POST["namen"]) && !empty($_POST["vornamen"]) &&
    count($_POST["namen"]) == count($_POST["vornamen"])){
    $names = $_POST["namen"];
    $vornamen = $_POST["vornamen"];
    $res = [];
    foreach ($names as $idx => $name){
        $vorname = $vornamen[$idx];
        $vorname_split = explode(" ", $vorname);
        $name_split = explode(" ", $name);
        $sql = "SELECT
        ?,
        p.name,
        p.id,
        g.id,
        concat(g.name,IF (g.fakultaet IS NULL OR g.fakultaet = '','',concat(' ',g.fakultaet)),
                      IF (g.studiengang IS NULL OR g.studiengang = '','',concat(' ',g.studiengang)),
                      IF (g.studiengangabschluss IS NULL OR g.studiengangabschluss = '','',concat(' ',g.studiengangabschluss))),
        m.von,
        m.bis
        FROM sgis__person AS p
        INNER JOIN sgis__rel_mitgliedschaft AS m ON p.id = m.person_id
        INNER JOIN sgis__gremium AS g ON g.id = m.gremium_id
        WHERE
        (" . implode(" OR ", array_fill(0, count($name_split), "p.name LIKE ?")) . ")
        AND
        (" . implode(" OR ", array_fill(0, count($vorname_split), "p.name LIKE ?")) . ");";
        $s = $pdo->prepare($sql);
        $values = [$vorname . " " . $name];
        foreach ($name_split as $nn){
            $values[] = "%" . $nn;
        }
        foreach ($vorname_split as $vn){
            $values[] = "%" . $vn . "%";
        }
        $s->execute($values) or var_dump($s->errorInfo());
        //build result
        
        while (($row = $s->fetch(PDO::FETCH_NUM)) !== false){
            //  [eingabe][pers_id]
            $res[$row[0]][$row[2]][] = [
                "person-name" => $row[1],
                "gremien-id" => $row[3],
                "gremien-name" => $row[4],
                "von" => empty($row[5]) ? "":strftime($datePattern,strtotime($row[5])),
                "bis" => empty($row[6]) ? "":strftime($datePattern,strtotime($row[6])),
            ];
        }
    } // foreach names
    //var_dump($res);
    if (empty($res)){
        echo "<div class='alert alert-warning'>Keine Treffer!</div>";
    }else{ ?>
        <table class="table">
            <thead>
            <tr>
                <th class="col-xs-2">Eingabe</th>
                <th class="col-xs-2">Treffer</th>
                <th>Gremium</th>
                <th>Zeitraum</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($res as $inputName => $hit){
                $countAllRows = 0;
                foreach ($hit as $rows) {
                    $countAllRows+= count($rows);
                }
                //echo($countAllRows);
                ?>
                <tr>
                    <?php
                    echo "<td rowspan=" . $countAllRows . ">" . htmlspecialchars($inputName) . "</td>";
                    foreach ($hit as $foundPID => $rows){
                        ?>
                        
                        <td rowspan="<?= $countAllRows ?>">
                            <a target="_blank"
                               href="admin.php?tab=person.edit&person_id=<?= htmlspecialchars($foundPID) ?>"><?= htmlspecialchars($rows[0]["person-name"]); ?></a>
                        </td>
                        <?php
                        $i = 0;
                        foreach ($rows as $row){
                            //person-name
                            //gremien-id
                            //gremien-name
                            //von
                            //bis
                            $i++ !== 0 ? "<tr>" : ""; ?>
                                <td>
                                    <a target='_blank' href='admin.php?tab=gremium.edit&gremium_id=<?= htmlspecialchars($row['gremien-id'])?>'>
                                        <?= htmlspecialchars($row["gremien-name"]) ?>
                                    </a>
                                </td>
                                <td><?= empty($row["bis"]) ? ("seit " . $row["von"]) :  ($row["von"] . " bis " . $row["bis"]) ?></td>
                            </tr>
                            <?php
                            $i++;
                        }
                        
                    }
                
            }?>
            </tbody>
        </table>
        <?php
    }
}


if (isset($_POST["namen"]) && isset($_POST["vornamen"]) &&
    count($_POST["namen"]) != count($_POST["vornamen"])) {
  echo "<div class='alert alert-danger'>Vor- und Nachnamen hatten nicht gleich viele Zeilen. Bitte erneut eingeben!</div>";
}

if (isset($_POST["nonce"]) && $_POST["nonce"] != $nonce) {
  echo "<div class='alert alert-danger'>CSFR-Schutz aktiviert. Bitte versuche es erneut, und stelle sicher das du auf keinem anderen Gerät gerade angemeldet bist.</div>";
}

?>
</div> <!-- close container bla-->
<?php


include "../template/admin_footer.tpl";
?>
