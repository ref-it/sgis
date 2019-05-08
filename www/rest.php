<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 26.06.18
 * Time: 23:51
 */

// =======================================================================================================


define('SGISBASE', dirname(dirname(__FILE__)));
require_once SGISBASE.'/config/config.php';

ini_set("log_errors", 				1);
ini_set("error_log", 			    realpath (dirname(__FILE__).'/../')."/logs/rest.log");
ini_set('display_errors', 			1);
ini_set('display_startup_errors',   1);

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX;

try {
    $pdo = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE';"));
} catch (Exception $e) {
    die("Datenbankverbindung fehlgeschlagen. Server überlastet? Ursache: ".$e->getMessage());
}

//LIKE Syntax
const GREMIEN_WHITELIST = [
    "studierendenrat%",
    "fachschaftsrat%",
    "referat%",
    "ag%",
    "konsul%",
    "kts%"
];

const GREMIEN_TUTOR_WHITELIST = [
    "tutor%",
];
//equals
const ROLLEN_WHITELIST = [
    "Leiter",
    "Mitglied",
    "stellv. Leiter",
    "Aktiv",
    "Entsandt %",
    "Interclub%",
    "Schlüssel%",
    "Hauptdelegiert",
    "%Kassenverantwortliche%",
    "%Haushaltsverantwortliche%",
    "Angestellt",
    "Seminargruppe%"
];

function no_cache($json){
    $now = date_create();
    // do not cache me
    $json->addExtraHeader("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    $json->addExtraHeader("Last-Modified: " . $now->format("D, d M Y H:i:s") . " GMT");
    $json->addExtraHeader("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    $json->addExtraHeader("Cache-Control: post-check=0, pre-check=0");
    $json->addExtraHeader("Pragma: no-cache");
}
function cache_interval($interval = '+1 day'){
    $now = date_create();
    $tomorrow = date_create();
    $tomorrow->modify($interval);
    $json->addExtraHeader("Last-Modified: " . $now->format("D, d M Y H:i:s") . " GMT");
    $json->addExtraHeader("Expires: "+$tomorrow->format("D, d M Y H:i:s")+" GMT");
    $json->addExtraHeader("Cache-Control: max-age=" + ($tomorrow->getTimestamp() - $now->getTimestamp()));
}

// =======================================================================================================

$json = new JSON_Controller();
$client_ip = $_SERVER['REMOTE_ADDR'];

header_remove("X-Powered-By");
header_remove("Server");
$json->addExtraHeader("X-Frame-Options: DENY");
$json->addExtraHeader('Content-type:application/json;charset=utf-8');

// normal sgis api requests
if (isset($_SERVER['HTTP_SGIS_API_KEY']) && ($_SERVER['HTTP_SGIS_API_KEY'] === $GLOBALS["REST_API_KEY"]) && in_array($client_ip, $GLOBALS['REST_API_IPS'], true)){
    $json->addExtraHeader("Cache-Control: max-age=86400");
    if (isset($_REQUEST["action"])){
        $action = $_REQUEST["action"];
        switch ($action){
            // ==================================================================================
            case "getGremien":
                $whitelist = GREMIEN_WHITELIST;
                if (isset($_REQUEST['filter'])){
                    switch ($_REQUEST['filter']) {
                        case 'tutor':
                            $whitelist = GREMIEN_TUTOR_WHITELIST;
                            break;
                        default:
                            $json->setStatus(400);
                            $json->errorLog("[GET GREMIUM] Invalid Filter", 'Invalid Filter');
                            break;
                    }
                }

                $json->setArraywalk(true);
                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        trim(concat(name,' ',COALESCE(fakultaet, ''),' ',COALESCE(studiengangabschluss, ''))) as name,
                        fakultaet,
                        studiengangabschluss,
                        studiengang_short as studiengang_kurz,
                        studiengang as studiengang_lang,
                        studiengang_english as studiengang_englisch,
                        matrikel as matrikel
                    FROM sgis__gremium
                    WHERE
                            active = 1
                        AND (" .
                        implode(" OR ", array_fill(0, count($whitelist), "LOWER(name) LIKE ?"))
                        . ")
                    ORDER BY name ASC
                ;");
                $db_res = $stmt->execute($whitelist);
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
    			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                } else {
                    $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                break;
            // ==================================================================================
            case "getRollen":
            case "getGremium":
                $json->setArraywalk(true);
                if (!isset($_REQUEST["gid"])){
                    $json->setStatus(400);
                    $json->errorLog("[GET ROLLEN] Gremien ID not submitted", 'Gremien ID not submitted');
                } else if ( !intval($_REQUEST["gid"]) || intval($_REQUEST["gid"]) < 1){
                    $json->setStatus(400);
                    $json->errorLog("[GET ROLLEN] Gremien ID invalid", 'Gremien ID invalid');
                } else {
                    $gremien_id = intval($_REQUEST["gid"]);
                }

                //gremium whitelist
                $whitelist = GREMIEN_WHITELIST;
                $whitelist = array_merge($whitelist, GREMIEN_TUTOR_WHITELIST);
                array_unique($whitelist);

                //look up gremium_id
                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        trim(concat(name,' ',COALESCE(fakultaet, ''),' ',COALESCE(studiengangabschluss, ''))) as name,
                        fakultaet,
                        studiengangabschluss,
                        studiengang_short as studiengang_kurz,
                        studiengang as studiengang_lang,
                        studiengang_english as studiengang_englisch,
                        matrikel as matrikel
                    FROM sgis__gremium
                    WHERE
                        active = 1
                        AND id = ?
                        AND (" .
                        implode(" OR ", array_fill(0, count($whitelist), "LOWER(name) LIKE ?"))
                        . ")
                ;");

                //add $gremien_id to db parameter
                $pdo_params = $whitelist;
                array_unshift ($pdo_params, $gremien_id);
                $ret = [];
                $gremium = NULL;
                $db_res = $stmt->execute($pdo_params);
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
    			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                } else {
                    $gremium = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if (count($gremium)!=1){
                    $json->setStatus(404);
                    $json->errorLog("[GET ROLLEN] Gremien ID invalid - not found", 'Gremien Not Found');
                } else {
                    $gremium = $gremium[0];
                    $gremien_id = $gremium['id'];
                    $ret['gremium'] = $gremium;
                }

                //get rollen ---------------------------------------------------
                $stmt = $pdo->prepare("
                    SELECT
                        R.id,
                        R.name
                    FROM sgis__rolle R
                    LEFT JOIN sgis__gremium AS G ON R.gremium_id = G.id
                    WHERE
                        R.gremium_id = ?
                        AND R.active = 1
                        AND (" .
                        implode(" OR ", array_fill(0, count(ROLLEN_WHITELIST), "LOWER(R.name) LIKE ?"))
                        . ")
                    ORDER BY name ASC
                ;");
                $db_res = $stmt->execute(array_merge([$gremien_id], ROLLEN_WHITELIST));
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
    			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                } else {
                    $rollen = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!$rollen){ $rollen = []; }
                    $ret['rollen'] = $rollen;
                }
                break;
            // ==================================================================================
            case "getPersonen":
                $json->setArraywalk(true);
                if (!isset($_REQUEST["rid"])){
                    $json->setStatus(400);
                    $json->errorLog("[GET PERSON] Rollen ID not submitted", 'Rollen ID not submitted');
                } else if ( !intval($_REQUEST["rid"]) || intval($_REQUEST["rid"]) < 1){
                    $json->setStatus(400);
                    $json->errorLog("[GET PERSON] Rollen ID invalid", 'Rollen ID invalid');
                } else {
                    $rollen_id = intval($_REQUEST["rid"]);
                }

                //gremium whitelist
                $gwhitelist = GREMIEN_WHITELIST;
                $gwhitelist = array_merge($gwhitelist, GREMIEN_TUTOR_WHITELIST);
                array_unique($gwhitelist);

                //rollen $whitelist
                $rwhitelist = ROLLEN_WHITELIST;

                //look up rollen_id
                $stmt = $pdo->prepare("
                    SELECT
                        G.id as gid,
                        trim(concat(G.name,' ',COALESCE(G.fakultaet, ''),' ',COALESCE(G.studiengangabschluss, ''))) as gname,
                        R.id as rid,
                        R.name as rname
                    FROM sgis__rolle R
                    LEFT JOIN sgis__gremium AS G ON R.gremium_id = G.id
                    WHERE
                        G.active = 1
                        AND R.active = 1
                        AND R.id = ?
                        AND (" .
                        implode(" OR ", array_fill(0, count($gwhitelist), "LOWER(G.name) LIKE ?"))
                        . ")
                        AND (" .
                        implode(" OR ", array_fill(0, count($rwhitelist), "LOWER(R.name) LIKE ?"))
                        . ")
                ;");

                $pdo_params = array_merge([$rollen_id],$gwhitelist,$rwhitelist);
                $ret = [];
                $gremium_data = NULL;
                $db_res = $stmt->execute($pdo_params);
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
                    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR' . "[{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]");
                } else {
                    $gremium_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                if (count($gremium_data)!=1){
                    $json->setStatus(404);
                    $json->errorLog("[GET PERSONEN] GREMIUM ID invalid - not found", 'GREMIUM Not Found');
                } else {
                    $gremium_data = $gremium_data[0];
                    $rollen_id = $gremium_data['rid'];
                    $gremien_id = $gremium_data['gid'];
                    $ret['gremium'] = ['id' => $gremium_data['gid'], 'name' => $gremium_data['gname'] ];
                    $ret['rolle'] = ['id' => $gremium_data['rid'], 'name' => $gremium_data['rname'] ];
                }

                //get personen data -------------------------------------------------------
                $now = date_create();
                $stmt = $pdo->prepare("
                    SELECT
                        p.id,
                        p.name,
                        m.von,
                        m.bis
                    FROM sgis__rel_mitgliedschaft AS m
                    INNER JOIN sgis__person AS p ON m.person_id = p.id
                    WHERE
                        m.rolle_id = ?
                        AND (m.bis IS NULL
                            OR m.bis >= '{$now->format('Y-m-d')}' )
                        AND m.von <= '{$now->format('Y-m-d')}'
                    ORDER BY name ASC
                ;");
                $db_res = $stmt->execute([$rollen_id]);
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
    			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                } else {
                    $currentMember = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!$currentMember) $currentMember = [];
                    $ret['currentMembers'] = $currentMember;
                }
                // if $_REQUEST["formerMembersSince"] ---------------------------------------
                // $_REQUEST["formerMembersSince"] = 'Y-m-d'; //DATE
                // leute die seit diesem Zeitpunkt wieder raus sind
                if (isset($_REQUEST["formerMembersSince"])) {
                    //check date format ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                    $value = $_REQUEST["formerMembersSince"];
                    if (!is_string($value)){
                        $json->setStatus(400);
                        $json->errorLog("[GET PERSON] formerMembersSince invalid - no string", 'formerMembersSince invalid');
                    }
                    $date = trim(strip_tags(''.$value));
                    $fmt = 'Y-m-d';
                    $d = \DateTime::createFromFormat($fmt, $date);
                    if(!$d || $d->format($fmt) != $date){
                        $json->setStatus(400);
                        $json->errorLog("[GET PERSON] formerMembersSince invalid - wrong date format", 'formerMembersSince invalid');
            		}
                    //check date format - END ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                    $curr_member_ids = array_column($currentMember, 'id');
                    $stmt = $pdo->prepare("
                        SELECT
                            p.id,
                            p.name,
                            m.von,
                            m.bis
                        FROM sgis__rel_mitgliedschaft AS m
                        INNER JOIN sgis__person AS p ON m.person_id = p.id
                        WHERE
                            m.rolle_id = ?
                            AND m.bis IS NOT NULL
                            AND ( m.bis >= '{$d->format('Y-m-d')}' )
                            AND m.bis <= '{$now->format('Y-m-d')}' ".
                            ((count($curr_member_ids) > 0)?"
                                AND p.id NOT IN (".implode(',',$curr_member_ids).")
                            ":'')
                        ." ORDER BY name ASC
                    ;");
                    $db_res = $stmt->execute([$rollen_id]);
                    if (!$db_res){
                        $json->setStatus(500);
                        $einfo = $stmt->errorInfo();
        			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                    } else {
                        $formerMember = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!$formerMember) $formerMember = [];
                        $ret['formerMembers'] = $formerMember;
                    }
                }
                break;
            default:
                $json->setStatus(404);
                $json->setContent(["status_explanation" => "$action is not a known action"]);
                $json->printResult();
                exit(0);
        }
        if (isset($ret) && $ret !== false && !empty($ret)){
            $ret = [
                'result' => $ret
            ];
            $json->setStatus(200);
            $json->setContent($ret);
        }
        $json->printResult();
        exit(0);
    } else {
        $json->setStatus(404);
        $json->setContent(["status_explanation" => "empty action"]);
    }
} else if (isset($_SERVER['HTTP_SGIS_API_KEY']) && $_SERVER['HTTP_SGIS_API_KEY'] === $GLOBALS["REST_LOGIN_API_KEY"] && isset($_REQUEST["action"]) && in_array($client_ip, $GLOBALS['REST_LOGIN_API_IPS'], true)){
    no_cache($json);

    // do not cache me
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    //check lock file
    $mypid = getmypid();
    $lockfilename = "/".dirname(__FILE__)."/{$GLOBALS['REST_LOGIN_SECRET']}.lock";
    $open = false;
    if (!file_exists($lockfilename)) {
        $fp = fopen($lockfilename, 'w');
        fwrite($fp, ''.$mypid);
        fclose($fp);
        $open = true;
    } else {
        $tmp_pid = file_get_contents($lockfilename);
        if (!posix_kill($tmp_pid,0)){
            unlink($lockfilename);
            $fp = fopen($lockfilename, 'w');
            fwrite($fp, ''.$mypid);
            fclose($fp);
            $open = true;
        }
    }
    if ($open) {
        $action = $_REQUEST["action"];
        switch ($action){
            case 'verify_login': {
                if (!isset($_REQUEST["user"]) || !is_string($_REQUEST["user"]) || empty($_REQUEST["user"]) || !preg_match('/^([-a-z0-9_]+)$/', $_REQUEST["user"])
                || !isset($_REQUEST["pass"]) || !is_string($_REQUEST["pass"]) || empty($_REQUEST["pass"]) || !preg_match('/^(.+)$/', $_REQUEST["pass"])
                || !isset($_REQUEST["service"]) || !is_string($_REQUEST["service"]) || empty($_REQUEST["service"]) || !preg_match('/^(vpn|wlan)$/', $_REQUEST["service"])
                ){
                    $json->setStatus(404);
                    $json->setContent(["status_explanation" => "Invalid credentials"]);
                    break;
                }
                $now = date_create();
                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        P.password,
                        P.id,
                        P.username
                    FROM sgis__person P
                    LEFT JOIN sgis__rel_mitgliedschaft RM ON P.id = RM.person_id
                    LEFT JOIN sgis__rel_rolle_gruppe RG ON RM.rolle_id = RG.rolle_id
                    LEFT JOIN sgis__gruppe G ON RG.gruppe_id = G.id
                    WHERE
                        P.name IS NOT NULL
                        AND P.password IS NOT NULL
                        AND P.username IS NOT NULL
                        AND P.canLogin = 1
                        AND P.username = ?
                        AND RM.von IS NOT NULL
                        AND RM.von <= '".$now->format('Y-m-d')."'
                        AND (RM.bis IS NULL OR RM.bis = '' OR RM.bis >= '".$now->format('Y-m-d')."' )
                        AND G.name = ?
                        ;"
                );
                $db_res = $stmt->execute([$_REQUEST["user"], $_REQUEST["service"]]);
                if (!$db_res){
                    $json->setStatus(500);
                    $einfo = $stmt->errorInfo();
    			    $json->errorLog("Execute failed: [{$einfo[0]}][{$einfo[1]}][{$einfo[2]}]\n", 'DB_ERROR');
                } else {
                    $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                $res = [];
                foreach($ret as $r){
                    $res[] = $r;
                    break;
                }
                if ((count($res) == 1) && password_verify ( $_REQUEST["pass"] , $res[0]["password"] ) ) {
                    $json->setContent(['result' => 1]); //true
                } else {
                    $json->setContent(['result' => 0]); //false
                }
                if(isset($_REQUEST['result_only']) && $_REQUEST['result_only'] == '1'){
                    echo $json->getContent()['result'];
                    unlink($lockfilename);
                    exit();
                }
            } break;
            default:
                unlink($lockfilename);
                $json->setStatus(404);
                $json->setContent(["result" => "unknown action", 'request' => ['action' => $action]]);
                $json->printResult();
                exit(0);
        }
        unlink($lockfilename);
    } else {
        $json->setStatus(403);
    }
} else {
    /* schlecht */
    no_cache($json);
    $json->setStatus(403);
}
$json->printResult();
$pdo = null;
die();

class JSON_Controller{
    private $status = 200;
    private $content = [];
    private $arraywalk = false;
    private $extraHeader = [];

    /**
     * @param int $status
     */
    public function setStatus($status){
        $this->status = $status;
    }

    /**
     * default false
     * @param bool $status
     */
    public function setArraywalk($walk){
        $this->arraywalk = ($walk)? true: false;
    }

    /**
     * default false
     * @param bool $status
     */
    public function addExtraHeader($header){
        if (is_string($header)&& !empty($header) && !in_array($header, $this->extraHeader, true)){
            $this->extraHeader[] = $header;
        }
    }

    /**
     * default false
     * @param bool $status
     */
    public function clearExtraHeader(){
        $this->extraHeader = [];
    }

    /**
     * @param mixed $value
     */
    public function setContent($value){
        $this->content = $value;
    }

    /**
     * @return mixed $value
     */
    public function getContent(){
        return $this->content;
    }

    /*
     *
     */

    public function errorLog($log_msg, $out_message = '', $echo_die = true, $log_request = true){
        $bt = debug_backtrace();
        $caller = array_shift($bt);

        error_log(
            '[REST API][ERROR]'.(($out_message)?'['.$out_message.']':'').' '.$log_msg.
            "\n\t[CALLER]". $caller['file'] . "\t in [LINE]".$caller['line'].
            (($log_request)?
            "\n\t[REQUEST]". $_SERVER['REQUEST_METHOD'] . "\t in [BY]".$_SERVER['REMOTE_ADDR'].
            ((isset($_REQUEST['ACTION']))?
            "\n\t[ACTION]". $_REQUEST['ACTION']:''):'')
        );

        $out = [
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'by' => $_SERVER['REMOTE_ADDR']
            ],
            'status' => ($this->status)? $this->status : 400,
        ];
        if ($out_message){
            $out['result'] = $out_message;
        }

        if ($echo_die){
            foreach ($this->extraHeader as $h) {
                header($h);
            }
            if ($out['status']){
                http_response_code($out['status']);
            }
            echo json_encode($out, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
            die();
        }

        return $out;
    }

    public function printResult(){
        $out = [
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'by' => $_SERVER['REMOTE_ADDR']
            ],
            'status' => ($this->status)? $this->status : 404,
        ];
        if(isset($_REQUEST["action"])) $out['request']['action'] = ''.$_REQUEST['action'];
        $this->content = array_replace_recursive($out, $this->content);

        foreach ($this->extraHeader as $h) {
            header($h);
        }

        switch ($this->status){
            case 200:
                $ret = $this->content;
                if ($this->arraywalk){
                    array_walk_recursive($ret, function(&$val){
                        if (intval($val) && $val === (''.intval($val))){
                            $val = intval($val);
                        }
                    });
                }
                echo json_encode($ret, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
                break;
            case 403:
				http_response_code(403);
                if (!isset($this->content['result']) && !isset($this->content['error']) && !isset($this->content['status_explanation'])) $this->content['result'] = ['access denied'];
                echo json_encode($this->content, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
                break;
            case 404:
            default:
				http_response_code(404);
                if (!isset($this->content['result']) && !isset($this->content['error']) && !isset($this->content['status_explanation'])) $this->content['result'] = ['not found'];
                echo json_encode($this->content, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
                break;
        }
    }
}
