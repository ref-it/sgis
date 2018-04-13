<?php
/**
 * Created by PhpStorm.
 * User: lu_kors
 * Date: 12.04.18
 * Time: 21:13
 */

global $ADMINGROUP,$pdo;

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);
include "../template/header.tpl";


function trimMe($d) {
  if (is_array($d)) {
    return array_map("trimMe", $d);
  } else {
    return trim($d);
  }
}
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
if(isset($_POST["nonce"])){
    if($_POST["nonce"] == $nonce){
        if (isset($_POST["namen"]) && isset($_POST["vornamen"])){
            $names = trim($_POST["namen"]);
            $vornamen = trim($_POST["vornamen"]);
            if(!empty($names)){
                $names = trimMe(explode(PHP_EOL,$names));
                $vornamen = trimMe(explode(PHP_EOL,$vornamen));
                if(count($names) === count($vornamen)){
                    
                    $res = [];
                    foreach ($names as $idx => $name){
                        $vorname = $vornamen[$idx];
                        $vorname_split = explode(" ",$vorname);
                        $name_split = explode(" ",$name);
                        $sql = "SELECT ?,p.name,p.id,group_concat(DISTINCT g.id),
                          group_concat(DISTINCT
                            concat(g.name,IF (g.fakultaet IS NULL OR g.fakultaet = '','',concat(' ',g.fakultaet)),
                                          IF (g.studiengang IS NULL OR g.studiengang = '','',concat(' ',g.studiengang)),
                                          IF (g.studiengangabschluss IS NULL OR g.studiengangabschluss = '','',concat(' ',g.studiengangabschluss))))
                          FROM sgis__person as p
                          INNER JOIN sgis__rel_mitgliedschaft as m ON p.id = m.person_id
                          INNER JOIN sgis__gremium as g ON g.id = m.gremium_id
                          WHERE
                          (".implode(" OR ",array_fill(0,count($name_split),"p.name LIKE ?")).")
                          AND
                          (".implode(" OR ",array_fill(0,count($vorname_split),"p.name LIKE ?")).")
                          GROUP BY p.id";
                        $s = $pdo->prepare($sql);
                        $values = [$vorname." ".$name];
                        foreach ($name_split as $nn){
                            $values[] = "%".$nn;
                        }
                        foreach ($vorname_split as $vn){
                            $values[] = "%".$vn."%";
                        }
                        $s->execute($values) or var_dump($s->errorInfo());
                        $res[] = $s->fetchAll(PDO::FETCH_NUM);
                    }
                }else{
                    echo "<div class='alert alert-danger'>Vor- und Nachnamen hatten nicht gleich viele Zeilen. Bitte erneut eingeben!</div>";
                }
            }
        }
        if(!empty($res)){
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th class="col-xs-2">Eingabe</th>
                    <th class="col-xs-2">Treffer</th>
                    <th>Gremien</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($res as $hit){
                    $i = 0;
                    foreach ($hit as $row){
                        ?>
                        <tr>
                            <?= $i === 0? "<td rowspan=".count($hit).">".$row[0]."</td>":""?>
                            <td><a target="_blank" href="admin.php?tab=person.edit&person_id=<?= $row[2]?>"><?=$row[1];?></a></td>
                            <td><?php
                                //var_dump($row[3]);
                                //var_dump($row[4]);
                                $g_names = explode(",",$row[4]);
                                $g_string = [];
                              foreach (explode(",",$row[3]) as $idx => $id){
                                    $g_string[]= "<a target='_blank' href='admin.php?tab=gremium.edit&gremium_id=$id'>{$g_names[$idx]}</a>";
                              }
                              echo  implode(", ",$g_string);
                            ?></td>
                        </tr>
                <?php
                        $i++;
                    }
                }
                ?>
            </tbody>
        </table>
        <?php
        }else{
             echo "<div class='alert alert-warning'>Keine Treffer!</div>";
        }
    }else{
        echo "<div class='alert alert-danger'>CSFR-Schutz aktiviert. Bitte versuche es erneut, und stelle sicher das du auf keinem anderen Gerät gerade angemeldet bist.</div>";
    }
}

?>
</div> <!-- close container -->
<?php

include "../template/admin_footer.tpl";
?>