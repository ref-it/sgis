<?php

global $attributes, $logoutUrl, $AUTHGROUP, $ADMINGROUP, $nonce;

require_once "../lib/inc.all.php";

$mail = getUserMail();
$somebodyElse = false;
if (isset($_REQUEST["mail"]) && ($mail != $_REQUEST["mail"])) {
  requireGroup($ADMINGROUP);
  $mail = $_REQUEST["mail"];
  $somebodyElse = true;
} else {
  requireGroup($AUTHGROUP);
}
$person= getPersonDetailsByMail($mail);
if($person['image']){
	require_once "../lib/database/abstract.Database.php"; //load helper function set
	require_once "../lib/database/class.DatabasePdo.php";
	require_once "../lib/database/class.DatabaseFileModel.php";
	$ldb = \intbf\database\DatabaseFileModel::getInstance();
	require_once "../lib/class.FileHandler.php";
	$fh = new \intbf\FileHandler($ldb, [
		'UPLOAD_TARGET_DATABASE' => false,
		'UPLOAD_USE_DISK_CACHE' => false,
		'UPLOAD_MULTIFILE_BREAOK_ON_ERROR' => true,
		'UPLOAD_MAX_MULTIPLE_FILES' => 1,
		'UPLOAD_MAX_SIZE' => 5242880,
		'UPLOAD_WHITELIST' => 'png,jpg,jpeg,jpe',
		'UPLOAD_DISK_PATH' => realpath(dirname(__FILE__).'/../www/pimages'),
		'MOD_XSENDFILE' => 0,
	]);

	$pimage = $ldb->getFileInfoById($person['image']);
} else $pimage = NULL; 
$gremien = getPersonRolle($person["id"]);
$gruppen = getPersonGruppe($person["id"]);
$mailinglisten = getPersonMailingliste($person["id"]);
$contactDetails = getPersonContactDetails($person["id"]);

if (isset($_POST["action"]) && ($_POST["action"] == "pwchange")) {
  if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
    echo "<b class=\"msg\">Formular nicht frisch - CSRF Schutz.</b><br>\n";
  } else {
    if (empty($person["username"]) && isset($_POST["username"]) && ($_POST["username"] !== $person["username"])) {
      setPersonUsername($person["id"], $_POST["username"]);
    }
    if (isset($_POST["password"]) && ($_POST["password"] == $_POST["password2"]) && !empty($_POST["password"])) {
      setPersonPassword($person["id"], $_POST["password"]);
      $success = true;
    } else {
      $success = false;
    }
    header("Location: ".$_SERVER["PHP_SELF"]."?src=pwchange&success=".($success ? 1 : 0));
    exit;
  }
} else if (isset($_POST["action"]) && ($_POST["action"] == "pimage.upload")) {
	if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
		http_response_code(403);
		echo "<b class=\"msg\">Formular nicht frisch - CSRF Schutz.</b><br>\n";
		die();
	} else {
		if (!$person['image']) {
			// check result image file set + no error
			if (!isset($_FILES)
				|| count($_FILES) == 0
				|| !isset($_FILES['file'])
				|| !isset($_FILES['file']['error'])
				|| !isset($_FILES['file']['name'])
				|| count($_FILES['file']['name']) == 0){
				http_response_code(403);
				echo "<b class=\"msg\">Invalid file.</b><br>\n";
				die();
			}
			// load image lib and store to database
			require_once "../lib/database/abstract.Database.php"; //load helper function set
			require_once "../lib/database/class.DatabasePdo.php";
			require_once "../lib/database/class.DatabaseFileModel.php";
			$ldb = \intbf\database\DatabaseFileModel::getInstance();
			require_once "../lib/class.FileHandler.php";
			$fh = new \intbf\FileHandler($ldb, [
				'UPLOAD_TARGET_DATABASE' => false,
				'UPLOAD_USE_DISK_CACHE' => false,
				'UPLOAD_MULTIFILE_BREAOK_ON_ERROR' => true,
				'UPLOAD_MAX_MULTIPLE_FILES' => 1,
				'UPLOAD_MAX_SIZE' => 2621440,
				'UPLOAD_WHITELIST' => 'png,jpg,jpeg,jpe',
				'UPLOAD_DISK_PATH' => realpath(dirname(__FILE__).'/../www/pimages'),
				'MOD_XSENDFILE' => 0,
			]);
			// handle upload
			$upload_result = $fh->upload(intval($person['id']));
			if ($upload_result && $upload_result['success'] && count($upload_result['error']) == 0 && count($upload_result['fileinfo']) > 0){
				$files = array_values($upload_result['fileinfo']);
				$json_result = [
					'success' => true,
					'name' => $files[0]->filename.(($files[0]->fileextension)?'.'.$files[0]->fileextension:''),
				];
				
				// update db: person: image
				setPersonImageId($person['id'], $files[0]->id);
				
				// create small thumb image
				$img_path = realpath(dirname(__FILE__).'/../www/pimages').'/'.$files[0]->hashname;
				$w = 200;
				
				$info = getimagesize($img_path);
				$mime = $files[0]->mime;
				
				switch ($mime) {
					case 'image/jpeg':
						$image_create_func = 'imagecreatefromjpeg';
						$image_save_func = 'imagejpeg';
						$new_image_ext = 'jpg';
						break;
					case 'image/png':
						$image_create_func = 'imagecreatefrompng';
						$image_save_func = 'imagepng';
						$new_image_ext = 'png';
						break;
					default: 
						;
				}
				$img = $image_create_func($img_path);
				list($width, $height) = getimagesize($img_path);
				$h = ($height / $width) * $w;
				
				$tmp = imagecreatetruecolor($w, $h);
				imagecopyresampled($tmp, $img, 0, 0, 0, 0, $w, $h, $width, $height);

				if (file_exists($img_path.'_thumb')) {
					unlink($img_path.'_thumb');
				}
				$image_save_func($tmp, $img_path.'_thumb');
				
				
				http_response_code(200);
				header("Content-Type: application/json; charset=UTF-8");
				echo json_encode($json_result, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
				die();
			} else {
				http_response_code(400);
				echo "<b class=\"msg\">".implode('<br>', $upload_result['error'])."</b><br>\n";
				die();
			}
			
			http_response_code(200);
			echo "<b class=\"msg\">Ok</b><br>\n";
			die();
		} else {
			http_response_code(403);
			echo "<b class=\"msg\">Image already set.</b><br>\n";
			die();
		}
	}
} else if (isset($_POST["action"]) && ($_POST["action"] == "pimage.remove")) {
	if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
		http_response_code(403);
		echo "<b class=\"msg\">Formular nicht frisch - CSRF Schutz.</b><br>\n";
		die();
	} else {
		if ($person['image']) {
			$img_path = realpath(dirname(__FILE__).'/../www/pimages').'/'.$pimage->hashname;
			// remove thumb file
			if (file_exists($img_path.'_thumb')) {
				unlink($img_path.'_thumb');
			}
			// remove file + remove db fileinfo + remove db filedata
			$fh->deleteFileById($pimage->id);
			
			//update user image
			setPersonImageId($person['id'], NULL);
			
			http_response_code(200);
			echo "<b class=\"msg\">Ok</b><br>\n";
			die();
		} else {
			http_response_code(403);
			echo "<b class=\"msg\">Image not set.</b><br>\n";
			die();
		}
	}
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	http_response_code(404);
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL /sgis/index.php was not found on this server.</p>
<p>Additionally, a 404 Not Found
error was encountered while trying to use an ErrorDocument to handle the request.</p>
<hr>
<address>Apache Server at helfer.stura.tu-ilmenau.de Port 443</address>
</body></html>";
	die();
}

require "../template/selbstauskunft.tpl";
