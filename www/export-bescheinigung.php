<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 08.08.18
 * Time: 00:17
 */

global $ADMINGROUP, $pdo, $DB_PREFIX;

//https://secure.php.net/manual/en/function.strftime.php

require_once "../lib/inc.all.php";
requireGroup("konsul");
$konsul = getUsername();
//include "../template/header.tpl";
setlocale(LC_TIME, 'de_DE.UTF8', 'de_DE.utf-8');

/**
 * do post request
 * uses curl
 *
 * @param string  $url
 * @param array   $data
 * @param string  $auth
 * @param boolean $auth_encode
 *
 * @return array
 */
function do_post_request($url, $data = null, $auth = null, $auth_encode = false){
    $result = [
        'success' => false,
        'code' => (-1),
        'data' => '',
    ];
    
    //connection
    $ch = curl_init();
    
    $header = [
        "Content-type: application/x-www-form-urlencoded; charset=UTF-8"
    ];
    if ($auth){
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, (($auth_encode) ? $auth : base64_decode($auth)));
    }
    
    //set curl options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    if ($data){
        $tmp_data = http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $tmp_data);
    }
    
    //run post
    $postresult = curl_exec($ch);
    
    //handle result
    $result['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    //close connection
    curl_close($ch);
    
    if ($result['code'] === 200 && $postresult){
        $result['data'] = json_decode($postresult, true);
        if ($result['data'] === null){
            $result['data'] = $postresult;
        }
        $result['success'] = true;
    }else if ($postresult){
        $result['data'] = strip_tags($postresult);
    }
    
    return $result;
}

function mergeDuplicates($arbeit){
    $arbeit_merged = [];
    if (!isset($arbeit) || empty($arbeit))
        return $arbeit_merged;
    $lastrow = ["rolle_id" => null];
    foreach ($arbeit as $idx => $row){
        if ($row["rolle_id"] === $lastrow["rolle_id"]){
            $last_von = date_create($lastrow["von"]);
            $last_bis = date_create($lastrow["bis"]);
            $von = date_create($row["von"]);
            $bis = date_create($row["bis"]);
            $diff_bis_von = $last_bis->diff($von);
            if (abs($diff_bis_von->days) < 14){
                array_pop($arbeit_merged);
                $lastrow["bis"] = $row["bis"];
                $arbeit_merged[] = $lastrow;
            }else{
                $arbeit_merged[] = $row;
            }
        }else{
            $arbeit_merged[] = $row;
        }
        $lastrow = $row;
    }
    return $arbeit_merged;
}

function checkSkillRegex(array $regexArray, array $arbeit){
    foreach ($regexArray as $regex){
        foreach ($arbeit as $work_row){
            if (preg_match("~" . $regex["position"] . "~", $work_row["position"])
                && preg_match("~" . $regex["gremium"] . "~", $work_row["gremium"])){
                return true;
            }
        }
    }
    return false;
}

const SKILLSET = [
    [
        "text" => "Arbeiten im Team",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => ".*",
            ],
        ],
    ],
    [
        "text" => "Leiten von Teams bis 10 Personen",
        "regex" => [
            [
                "position" => "Leit|Haushalt",
                "gremium" => ".*",
            ],
        ],
    ],
    [
        "text" => "Leiten von Teams über 10 Personen",
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "Selbstständiges arbeiten",
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Referat",
            ],
        ],
    ],
    [
        "text" => "Ausarbeitung und Plaung von Budgets verschiedener Strukturen",
        "regex" => [
            [
                "position" => "Haushalt",
                "gremium" => "Referat Finanzen",
            ],
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "Erfahrung mit kameraler Buchhaltung",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat Finanzen",
            ],
        ],
    ],
    [
        "text" => "Rechnungsprüfung",
        "regex" => [
            [
                "position" => "Kassen",
                "gremium" => "Referat Finanzen",
            ],
        ],
    ],
    [
        "text" => "Beantragen von Fördermitteln",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat (Finanzen|Kultur|Poli)",
            ],
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "Leitung und Organisation von Meetings",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Studierendenrat",
            ],
            [
                "position" => "Leit",
                "gremium" => ".*",
            ],
        ],
    ],
    [
        "text" => "Moderation und Meditation",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Konsul|Schlichtung",
            ],
        ],
    ],
    [
        "text" => "Eventleitung und Planung von mehrtägigen Veranstaltungen mit mehr als 200 Teilnehmer/innen",
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
            [
                "position" => ".*",
                "gremium" => "Wahlkommission",
            ],
        ],
    ],
    [
        "text" => "Eventleitung und Planung von Veranstaltungen",
        "regex" => [
            [
                "position" => "Sommerklausurtagung",
                "gremium" => "Studierendenrat",
            ],
        ],
    ],
    [
        "text" => "Durchführen von Events",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Ersiwoche|Fachschaftsrat|Referat ((?!IT|Finanzen).)+",
            ],
        ],
    ],
    [
        "text" => "Netzwerken und Knüpfen von Kontakten",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Hochschulpolitik|KTS|Konsul",
            ],
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "Verhandlungsgeschick",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Senat|Studienausschuss|Forschungsausschuss",
            ],
            [
                "position" => "Haushalt",
                "gremium" => "Referat Finanzen",
            ],
            [
                "position" => "Semesterticket",
                "gremium" => ".*",
            ],
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "Präsentieren und freie Rede",
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche|Ehrenamt",
            ],
        ],
    ],
    [
        "text" => "Beratungstätigkeiten und Sozialarbeit",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat (Soziales|Internationales)",
            ],
        ],
    ],
    [
        "text" => "Arbeiten mit Gesetzestexten",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat Hochschulpolitik|KTS|Senat|Studienaussschuss|Forschungsausschuss|Konsul",
            ],
        ],
    ],
    [
        "text" => "Belastungsfähigkeit",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => ".*",
            ],
        ],
    ],
    [
        "text" => "Politische Debattenkultur",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Studierendenrat|Senat|Fakultätsrat|Studierendenbeirat",
            ],
        ],
    ],
    [
        "text" => "Anleitung von Gruppen",
        "regex" => [
            [
                "position" => "Tutor",
                "gremium" => ".*",
            ],
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
    ],
    [
        "text" => "interkulturelle Kommunikation",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat Internationales",
            ],
        ],
    ],
    [
        "text" => "Planen und Entwickeln einer Social-Media Strategie",
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Referat Öffentlichkeitsarbeit",
            ],
        ],
    ],
    [
        "text" => "Betreuung von Social-Media Kanälen",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat Öffentlichkeitsarbeit",
            ],
        ],
    ],
    [
        "text" => "Flyer- und Bildbearbeitung",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Ersiwoche|Fachschaftsrat|Referat ((?!IT|Finanzen).)+",
            ],
        ],
    ],
    [
        "text" => "Pflege von Websiten und deren Inhalten",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat Öffentlichkeitsarbeit",
            ],
        ],
    ],
    [
        "text" => "Softwareentwicklung",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat IT",
            ],
        ],
    ],
    [
        "text" => "Wartung der IT-Infrastruktur",
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Referat IT",
            ],
        ],
    ],
    [
        "text" => "IT-Administration (Server und Client)",
        "regex" => [
            [
                "position" => "Admin",
                "gremium" => ".*",
            ],
        ],
    ],
    [
        "text" => "Umgang mit Datenschutz Anforderungen (DS-GVO)",
        "regex" => [],
    ],


];


const TIME_INVESTMENT = [
    [
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Studiengangkommission",
            ],
            [
                "position" => ".*",
                "gremium" => "Hochschulrat",
            ],
        ],
        "type" => "h/S",
        "amount" => 3,
    ],
    [
        "regex" => [
            [
                "position" => "Aktiv",
                "gremium" => "Erstiwoche",
            ],
        ],
        "type" => "h",
        "amount" => 120,
    ],
    [
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Erstiwoche",
            ],
        ],
        "type" => "h",
        "amount" => 400,
    ],
    [
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Prüfungsausschuss",
            ],
            [
                "position" => ".*",
                "gremium" => "Studienkommission",
            ],
        ],
        "type" => "h/S",
        "amount" => 5,
    ],
    [
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Fakultätsrat",
            ],
            [
                "position" => ".*",
                "gremium" => "QMB",
            ],
            [
                "position" => "Nebendelegiert",
                "gremium" => "KTS",
            ],
            [
                "position" => ".*",
                "gremium" => "Gleichstellungsrat",
            ],
            [
                "position" => ".*",
                "gremium" => "Forschungsausschuss",
            ],
            [
                "position" => ".*",
                "gremium" => "HEQS",
            ],
        ],
        "type" => "h/W",
        "amount" => 1,
    ],
    [
        "regex" => [
            [
                "position" => "Aktiv",
                "gremium" => "Referat",
            ],
            [
                "position" => ".*",
                "gremium" => "Senat",
            ],
            [
                "position" => ".*",
                "gremium" => "Studienausschuss",
            ],
            
            [
                "position" => "(Hauptd|D)elegiert",
                "gremium" => "KTS",
            ],
            [
                "position" => ".*",
                "gremium" => "Studierendenbeirat",
            ],
        ],
        "type" => "h/W",
        "amount" => 2,
    ],
    [
        "regex" => [
            [
                "position" => "Leit",
                "gremium" => "Referat",
            ],
        
        ],
        "type" => "h/W",
        "amount" => 3,
    ],
    [
        "regex" => [
            [
                "position" => "Aktiv|Mitglied",
                "gremium" => "Fachschaftsrat",
            ],
        ],
        "type" => "h/W",
        "amount" => 4,
    ],
    [
        "regex" => [
            [
                "position" => "Mitglied|Entsandt",
                "gremium" => "Studierendenrat",
            ],
        ],
        "type" => "h/W",
        "amount" => 8,
    ],
    [
        "regex" => [
            [
                "position" => "Kassen|Haushalt",
                "gremium" => "Referat Finanzen",
            ],
        ],
        "type" => "h/W",
        "amount" => 10,
    ],
    [
        "regex" => [
            [
                "position" => "Tutor",
                "gremium" => ".*",
            ],
            [
                "position" => "Semesterticket",
                "gremium" => "Studierendenrat",
            ],
            [
                "position" => ".*",
                "gremium" => "Berufungskommission",
            ],
            [
                "position" => "Sommerklausurtagung",
                "gremium" => "Studierendenrat",
            ],
        ],
        "type" => "h",
        "amount" => 30,
    ],
    [
        "regex" => [
            [
                "position" => ".*",
                "gremium" => "Wahlkommission",
            ],
            
        ],
        "type" => "h",
        "amount" => 50,
    ],

];

function getTimeInvestment($row){
    foreach (TIME_INVESTMENT as $item){
        $regexArray = $item["regex"];
        foreach ($regexArray as $regex){
            if (preg_match("~" . $regex["position"] . "~", $row["position"])
                && preg_match("~" . $regex["gremium"] . "~", $row["gremium"])){
                return [$item["type"], $item["amount"]];
            }
        }
    }
    return ["h/W", 0];
}

if (!isset($_GET["pid"]) && empty($_POST)){
    die("fehlende Informationen");
}else if (empty($_POST)){
    $stmnt_arbeit = $pdo->prepare("
        SELECT r.id as rolle_id, rm.id , rm.von, rm.bis,  r.name as position,
        concat(g.name,IF (g.fakultaet IS NULL OR g.fakultaet = '','',concat(' ',g.fakultaet)),
                      IF (g.studiengang IS NULL OR g.studiengang = '','',concat(' ',g.studiengang)),
                      IF (g.studiengangabschluss IS NULL OR g.studiengangabschluss = '','',concat(' ',g.studiengangabschluss))
        ) as gremium
        FROM " . $DB_PREFIX . "person as p
        LEFT JOIN " . $DB_PREFIX . "rel_mitgliedschaft as rm ON rm.person_id = p.id
        LEFT JOIN " . $DB_PREFIX . "rolle as r ON r.id = rm.rolle_id
        LEFT JOIN " . $DB_PREFIX . "gremium as g ON rm.gremium_id = g.id
        WHERE
            p.id = ?
        ORDER BY g.id, r.id, rm.von
    ");
    $stmnt_arbeit->execute([$_GET["pid"]]);
    $arbeit = mergeDuplicates($stmnt_arbeit->fetchAll(PDO::FETCH_ASSOC));
    $arbeit[] = ["id" => 0,"von" => "", "bis" => "", "position" => "", "gremium" => ""];
    
    $stmnt_person = $pdo->prepare("SELECT *  FROM " . $DB_PREFIX . "person as p WHERE p.id = ?");
    $stmnt_person->execute([$_GET["pid"]]);
    $person = reset($stmnt_person->fetchAll(PDO::FETCH_ASSOC));
    
    $name_array = explode(" ", $person["name"]);
    $name = array_pop($name_array);
    $vorname = implode(" ", $name_array);
    
    
    include "../template/header.tpl";
    include "../template/admin.tpl" ?>
    <h2>Gremienbescheinigung erstellen</h2>
    <label for="person-well">Allgemeine Informationen</label>
    <form action="export-bescheinigung.php" method="POST">
        <div id="person-well" class="well">
            <div class="form-group col-xs-6">
                <label for="person-male">Anrede</label>
                <div id="person-male" class="col-xs-12">
                    <input name="person-male" type="radio" value="false"> Frau
                    <input name="person-male" type="radio" value="true"> Herr
                </div>
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="date">Ausstellungsdatum</label>
                <input name="date" id="date" class="form-control" type="date">
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="person-vorname">Vorname</label>
                <input name="person-vorname" id="person-vorname" class="form-control col-md-6" type="text"
                       value="<?= $vorname ?>">
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="person-name">Name</label>
                <input name="person-name" id="person-name" class="form-control" type="text" value="<?= $name ?>">
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="person-adresse">Adresse</label>
                <input name="person-adresse" id="person-adresse" class="form-control" type="text">
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="person-ort">PLZ / Ort</label>
                <input name="person-ort" id="person-ort" class="form-control" type="text">
            </div>
            <div class="form-group col-md-6 col-xs-12">
                <label for="person-bday">Geburtstag</label>
                <input name="person-bday" id="person-bday" class="form-control" type="date">
            </div>
            <div class="clearfix"></div>
        </div>

        Wähle zum Erstellen der Bescheinigung, die Gremientätigkeiten aus, für die tatsächlich Arbeit geleistet wurde
        (achte v.a. darauf, dass bei "Aktiv" und "Gewählt" keine Überlappungen sind).
        <table class="table" id="gremien-arbeit">
            <thead>
            <tr>
                <th></th>
                <th>von</th>
                <th>bis</th>
                <th>Position</th>
                <th>Gremium</th>
                <th>Arbeitsaufwand</th>
                <th class="col-xs-3"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($arbeit as $row){
                $checkPatterns = ["Mitglied", "Leit", "Tutor", "Konsul", "Haushaltsverantwortlich", "Kassenverantwortlich", "Entsandt", "(Hauptd|Nebend|D)elegiert", "Semesterticket","Wahl"];
                $checkedBlacklist = ['ehemaliger Tutor'];
                $checked = '';
                foreach ($checkPatterns as $checkPattern){
                    if (preg_match("~" . $checkPattern . "~", $row["position"]) && !in_array($row["position"], $checkedBlacklist, true)){
                        $checked = 'checked';
                        break;
                    }
                }
                echo "<tr>";
                echo "<td><label>
                        <input name='work[{$row['id']}][checked]' class='bescheinigung__checkbox-show-row' type='checkbox' value='{$row["id"]}' $checked>
                      </label></td>";
                echo "<td><input name='work[{$row['id']}][von]' type='date' class='form-control' value='{$row["von"]}'></td>";
                echo "<td><input name='work[{$row['id']}][bis]' type='date' class='form-control' value='{$row["bis"]}'></td>";
                if($row["id"] === 0){ //is last row
                    echo "<td><input class='form-control' type='text' name='work[{$row['id']}][position]'></td>";
                    echo "<td><input class='form-control' type='text' name='work[{$row['id']}][gremium]'></td>";
                }else{
                    echo "<td>{$row["position"]}&nbsp;<a target='_blank' href='https://helfer.stura.tu-ilmenau.de/sgis/admin.php?tab=rel_mitgliedschaft.edit&rel_id={$row["id"]}'><i class='fa fa-fw fa-pencil'></i></a><input type='hidden' name='work[{$row['id']}][position]' value='{$row['position']}'></td>";
                    echo "<td>{$row["gremium"]}<input type='hidden' name='work[{$row['id']}][gremium]' value='{$row['gremium']}'></td>";
                }
                
                list($type, $amount) = getTimeInvestment($row);
                echo "<td><input name='work[{$row['id']}][h]' type='number' class='form-control' value='$amount'></td>";
                echo "<td>";
                echo "<input type='radio' name='work[{$row['id']}][type]' value='h' " . (($type === "h") ? "checked" : "") . ">&nbsp;h&nbsp;";
                echo "<input type='radio' name='work[{$row['id']}][type]' value='h/W' " . (($type === "h/W") ? "checked" : "") . ">&nbsp;h&nbsp;/&nbsp;Woche&nbsp;";
                echo "<input type='radio' name='work[{$row['id']}][type]' value='h/S' " . (($type === "h/S") ? "checked" : "") . ">&nbsp;h&nbsp;/&nbsp;Semester";
                echo "</td>";
                echo "</tr>";
            } ?>
            </tbody>
        </table>
        <label for="skill-box">Dort wurden folgende Fähigkeiten erworben bzw. eingebracht:</label>
        <div id="skill-box" class="col-xs-12">
            <?php foreach (SKILLSET as $skill){
                $checked = checkSkillRegex($skill["regex"], $arbeit) ? "checked" : ""; ?>
                <div class="checkbox">
                    <label><input name="skills[]" type="checkbox"
                                  value="<?= $skill["text"] ?>" <?= $checked ?>><?= $skill["text"] ?>
                    </label>
                </div>
            <?php } ?>

        </div>
        <div class="clearfix"></div>
        <div class="form-group">
            <label for="additional-text">Zusätzlicher Text</label>
            <textarea class="form-control" id="additional-text" name="additional-text" rows="3"></textarea>
        </div>
        <div class="form-group">
            <button class="btn btn-primary" type="submit">Gremienbescheinigung erzeugen</button>
        </div>
    </form>
    <?php
    include "../template/admin_footer.tpl";
}else{
    $date = isset($_POST["date"]) ? $_POST["date"] : date("Y-m-d");
    $name = isset($_POST["person-name"]) ? $_POST["person-name"] : "";
    $vorname = isset($_POST["person-vorname"]) ? $_POST["person-vorname"] : "";
    $adresse = isset($_POST["person-adresse"]) ? $_POST["person-adresse"] : "";
    $ort = isset($_POST["person-ort"]) ? $_POST["person-ort"] : "";
    $geburtsdatum = isset($_POST["person-bday"]) ? $_POST["person-bday"] : "";
    $add_text = isset($_POST["additional-text"]) ? $_POST["additional-text"] : "";
    $skills = isset($_POST["skills"]) && is_array($_POST["skills"]) ? $_POST["skills"] : [];
    $male = isset($_POST["person-male"]) ? $_POST["person-male"] : true;
    
    $sum = 0;
    $smallest = null;
    $biggest = null;
    $work = [];
    if (isset($_POST["work"])){
        foreach ($_POST["work"] as $row){
            if (!isset($row["checked"])){
                continue;
            }
            $vonTime = strtotime($row["von"]);
            $bisTime = date_create($row["bis"])->getTimestamp();
            if (!isset($smallest) || $smallest > $vonTime){
                $smallest = $vonTime;
            }
            if (!isset($biggest) || $biggest < $bisTime){
                $biggest = $bisTime;
            }
            if ($row["type"] === "h/S"){
                $sum += ($bisTime - $vonTime) / (60 * 60 * 24 * 7 * 26) * intval($row["h"]);
            }
            if ($row["type"] === "h/W"){
                $sum += ($bisTime - $vonTime) / (60 * 60 * 24 * 7) * intval($row["h"]);
            }
            if ($row["type"] === "h"){
                $sum += intval($row["h"]);
            }
            $work[] = $row;
        }
        $biggest = strftime("%B %G", $biggest);
        $smallest = strftime("%B %G", $smallest);
        $sum = round($sum, 0);
    }
    
    $out = [
        'APIKEY' => PDF_CREATOR_APIKEY,
        'action' => 'gremienbescheinigung',
        'date' => date("d.m.Y", strtotime($date)), //ausstellungsdatum
        "biggest" => $biggest,
        "smallest" => $smallest,
        "sum" => $sum,
        "geburtsdatum" => date("d.m.Y", strtotime($geburtsdatum)),
        "ort" => $ort,
        "adresse" => $adresse,
        "male" => $male,
        "name" => $name,
        "vorname" => $vorname,
        "arbeit" => $work,
        "skills" => $skills,
        "konsul" => getUserFullName(),
        "additional-text" => $add_text,
    ];
    
    $result = do_post_request(PDF_CREATOR_URL, $out, PDF_CREATOR_AUTH);
    if ($result["data"]["success"]){
        header("Content-type: application/pdf");
        header("Content-Disposition:attachment;filename=" . date("Y-m-d") . "-$name-$vorname.pdf");
        echo base64_decode($result["data"]["data"]);
    }else{
        header("Content-type: application/json");
        echo json_encode(["result" => $result, "input" => $out]) . PHP_EOL;
        //echo $result["data"]["error"]["tex"];
    }
    
    
}





