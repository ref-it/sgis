<?php
/**
 * This is a a library that offers support for handling spreadsheets in OpenDocument format
 * Copyright (C) 2008-20112 Alexandru Szasz <alexxed@gmail.com>
 * http://code.google.com/p/open-document-spreadsheet-php/
 */

class PostgreSQL_OpenDocument_Spreadsheet_Writer extends OpenDocument_Spreadsheet_Writer {
    protected $hndConnection;
    public $blnShowProgress = true;
	
	public function __construct($strFile, $strServer, $strName, $strUsername, $strPassword, $strPort) {
	    parent::__construct($strFile);
	    
	    $this->hndConnection = pg_connect(sprintf('host=%s dbname=%s user=%s password=%s port=%s',$strServer, $strName, $strUsername, $strPassword, $strPort));
	    if (!$this->hndConnection)
	        throw new Exception('Failed to connect to the database');
	    
	    $this->startDoc();
	    
	    
	        
	}
	
	public function Run($strQuery, $strSheetName = 'Sheet 0') {
	        $this->startSheet($strSheetName);
	        
	        $result = pg_query($this->hndConnection, $strQuery);
	        if (!$result) {
	            throw new Exception('Failed to execute query');
	        }
	        
	        $intRowCount = pg_num_rows($result);
	        
	        $row = pg_fetch_assoc($result);
	        
	        // write the column names in the first row
	        $intColumnId = 0;
	        foreach($row as $strColumnName=>$strValue) {
	            $this->addCell($intColumnId, $strColumnName, 'string');
	            $intColumnId++;
	        }
	        $this->saveRow();
	         
	        do {
	            $intColumnId = 0;
	            foreach($row as $mixValue) {
	                if (is_float($mixValue))
	                    $this->addCell($intColumnId, $mixValue, 'float');
	                else
	                    $this->addCell($intColumnId, $mixValue, 'string');
	                $intColumnId++;
	            }
	            $this->saveRow();
	            
	            $intCurrentRow++;
	            $strProgress = '';
	            for($i=1;$i<11;$i++) {
	                if (($intCurrentRow * 10)/$intRowCount <= $i)
	                $strProgress .= '-';
	                else
	                $strProgress .= '+';
	            }
	            
	            printf("\rProgress: [%s], %s", $strProgress, sprintf('%d%% done %d exported', intval(($intCurrentRow * 100)/$intRowCount), $intCurrentRow));
	            @ob_flush();
	        }
	        while ($row = pg_fetch_assoc($result));

	        $this->endSheet();
	}
	
	public function Save() {
	    $this->endDoc();
	    $this->saveOds();
	}
}




?>