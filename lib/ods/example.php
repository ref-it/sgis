<?php
/**
 * This is a a library that offers support for handling spreadsheets in OpenDocument format
 * Copyright (C) 2008-20112 Alexandru Szasz <alexxed@gmail.com>
 * http://code.google.com/p/open-document-spreadsheet-php/
 */

include("OpenDocument_Spreadsheet_Writer.class.php");
$objWriter = new OpenDocument_Spreadsheet_Writer('test.ods');



/**
 * Build a spreadsheet with three sheets, named Sheet 0, Sheet 1, Sheet 2, 5000 rows and 10 columns
 */
$objWriter->startDoc();
for($s=0;$s<3;$s++) {
    $objWriter->startSheet('Sheet ' . $s);
    for($i=0;$i<5000;$i++) {
        for($j=0;$j<9;$j++) {
            $objWriter->addCell($j, (float) $s . $i . $j, 'float');
        }
        $objWriter->addCell($j, "Two\nlines", 'string');
        $objWriter->saveRow();
    }
    $objWriter->endSheet();
}
$objWriter->endDoc();
$objWriter->saveOds();
?>