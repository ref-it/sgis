<?php
/**
 * This is a a library that offers support for handling spreadsheets in OpenDocument format
 * Copyright (C) 2008-20112 Alexandru Szasz <alexxed@gmail.com>
 * http://code.google.com/p/open-document-spreadsheet-php/
 */

require_once 'OpenDocument_Spreadsheet_Writer.class.php';
require_once 'PostgreSQL_OpenDocument_Spreadsheet_Writer.class.php';

if (
    !array_search('--file', $argv) ||
    !array_search('--server', $argv) ||
    !array_search('--database', $argv) ||
    !array_search('--user', $argv) ||
    !array_search('--password', $argv) ||
    !array_search('--port', $argv) ||
    !array_search('--query', $argv)
)
    exit(
    	sprintf(
    		"Usage: %s --file [filename] --server [server] --database [database] --user [user] --password [password] --port [port] --query [query]\n",
    		basename(__FILE__)
		)
	);

$objPgsqlWriter = new PostgreSQL_OpenDocument_Spreadsheet_Writer(
    $argv[array_search('--file', $argv)+1],
    $argv[array_search('--server', $argv)+1],
    $argv[array_search('--database', $argv)+1],
    $argv[array_search('--user', $argv)+1],
    $argv[array_search('--password', $argv)+1],
    $argv[array_search('--port', $argv)+1]
);

$objPgsqlWriter->Run($argv[array_search('--query', $argv)+1]);
$objPgsqlWriter->Save();
?>