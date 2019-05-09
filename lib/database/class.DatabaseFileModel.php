<?php
/**
 * class DatabaseFileModel
 * framework
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @category        framework
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
namespace intbf\database;

use intbf\file\File;
/**
 * DatabaseFileModel
 * framework
 * provides db constants
 *
 * REFIT STURA ILMENAU BASE FRAMEWORK
 * @package         intbf
 * @namespace		intbf\database
 * @category        framework
 * @author 			Michael Gnehr
 * @author 			Referat IT Stura TU Ilmenau
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 *
 */
class DatabaseFileModel extends DatabaseProvider
{
	/**
	 * class constructor
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * create filedata entry, set datablob null, set diskpath to file
	 * @param string $filepath
	 * @return false|int new inserted id or false
	 */
	public function createFileDataPath($filepath){
		if ($filepath == '') return false;
		$this->fileCloseLastGet();
		if ($this->dbInsert('filedata', ['diskpath' => $filepath], 's')){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}

	/**
	 * create fileentry on table fileinfo
	 * @param File $f
	 * @return false|int new inserted id or false
	 */
	public function createFile($f){
		$this->fileCloseLastGet();
		$this->dbInsert('fileinfo',[
			'link' => $f->link,
			'hashname' => $f->hashname,
			'filename' => $f->filename,
			'size' => $f->size,
			'fileextension' => $f->fileextension,
			'mime' => $f->mime,
			'encoding' => $f->encoding,
			'data' => $f->data,
			'added_on' => ($f->added_on)? $f->added_on : date_create()->format('Y-m-d H:i:s')
		]);
		return $this->lastInsertId();
	}

	/**
	 * update file column 'data' of fileinfo entry
	 * @param File $f
	 * @return boolean success
	 */
	public function updateFile_DataId($f){
		$this->_isError = false;
		$this->fileCloseLastGet();
		$this->dbUpdate(
			'fileinfo',
			['id' => $f->id],
			['data' => $f->data]
		);
		return !$this->isError();
	}

	/**
	 * prevent duplicate files for one link/directory
	 * @param string $linkId link or directory name - hier beleg name/id
	 * @param string $filename
	 * @param string $extension
	 * @return boolean
	 */
	public function checkFileExists($linkId, $filename, $extension){
		$res = NULL;
		$this->_isError = false;
		$res = $this->dbFetch(
			['fileinfo' => 'fileinfo'],
			[],
			["link" => $linkId, "filename" => $filename, "fileextension" => $extension]);
		if ($res && !empty($res)){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * return list of all existing links
	 * @return array
	 */
	public function getAllFileLinkIds(){
		$res = $this->dbFetch(['F' => 'fileinfo'], ['F.link'], NULL, NULL, NULL, NULL, NULL, 'DISTINCT');
		if (!$this->isError()){
			$return = [];
			foreach ($res as $line){
				$return[] = $line['link'];
			}
			return $return;
		} else {
			return [];
		}
	}

	/**
	 * returns fileinfo by id
	 * @param integer $id fileinfo id
	 * @return File|NULL
	 */
	public function getFileInfoById($id){
		$result = NULL;
		$this->_isError = false;
		$result = $this->dbFetch(
			['fileinfo' => 'fileinfo'],
			[],
			["fileinfo.id" => $id]);
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			break;
		}
		return $f;
	}

	/**
	 * returns fileinfo by id
	 * @param integer $id filelink id
	 * @return array <File>
	 */
	public function getFilesByLinkId($id){
		$result = NULL;
		$this->_isError = false;
		$result = $this->dbFetch(
			['fileinfo' => 'fileinfo'],
			[],
			["link" => $id]);
		$return = [];
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			$return[$line['id']] = $f;
		}
		return $return;
	}

	/**
	 * returns fileinfo by filehash
	 * @param integer $hash fileinfo hash
	 * @return File|NULL
	 */
	public function getFileInfoByHash($hash){
		$result = NULL;
		$this->_isError = false;
		$result = $this->dbFetch(
			['fileinfo' => 'fileinfo'],
			[],
			["fileinfo.hashname" => $hash]);
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			break;
		}
		return $f;
	}

	/**
	 * delete filedata by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFiledataById($id){
		$this->_isError = false;
		$this->dbDelete(
			"filedata",
			["id" => $id]);
		return !$this->isError();
	}

	/**
	 * delete filedata by link id
	 * @param integer $linkid
	 * @return integer affected rows
	 */
	public function deleteFiledataByLinkId($linkid){
		return $this->protectedInsert(
			"DELETE FROM `".$this->TABLE_PREFIX."filedata` WHERE `id` IN ( SELECT F.data FROM `".$this->TABLE_PREFIX."fileinfo` F WHERE F.link = ? );",
			'i', $linkid);
	}

	/**
	 * delete fileinfo by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFileinfoById($id){
		$this->_isError = false;
		$this->dbDelete(
			"fileinfo",
			["id" => $id]);
		return !$this->isError();
	}

	/**
	 * delete fileinfo by link id
	 * @param integer $linkid
	 * @return integer affected rows
	 */
	public function deleteFileinfoByLinkId($linkid){
		$this->_isError = false;
		$this->dbDelete(
			"fileinfo",
			["link" => $linkid]);
		return !$this->isError();
	}

	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @return false|int error -> false, last inserted id or
	 */
	public function storeFile2Filedata($filename, $filesize = null){
		return $this->_storeFile2Filedata($filename, $filesize, 'filedata', 'data');
	}

	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @return false|binary error -> false, binary data
	 */
	public function getFiledataBinary($id){
		return $this->_getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data');
	}
}

?>
