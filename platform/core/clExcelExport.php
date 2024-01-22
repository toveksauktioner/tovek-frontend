<?php

class clExcelExport {
	
	public function create( $sFilename, $aData, $aColumns = null ) {
		// Send Header
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");;
    header("Content-Disposition: attachment;filename=$sFilename.xls "); // à¹à¸¥à¹‰à¸§à¸™à¸µà¹ˆà¸à¹‡à¸Šà¸·à¹ˆà¸ à¹„à¸Ÿà¸¥à¹Œ
    header("Content-Transfer-Encoding: binary ");
    
    echo self::xlsBOF(); 
    
    $xlsRow = 0;
    
    if( is_array($aColumns) ) {
			$iCol = 0;
			
	    foreach( $aColumns as $sValue) {
	    	echo self::xlsWriteLabel($xlsRow, $iCol, $sValue);
	    	
	    	$iCol++;
	    }    	
    	$xlsRow++;
    }
    
    foreach( $aData as $aRow ) {
			$iCol = 0;
				
    	foreach( $aRow as $value ) {    	
	    	if( is_numeric($value) ) {
	      	echo self::xlsWriteNumber($xlsRow, $iCol, $value);
	      }
	      else {
	      	echo self::xlsWriteLabel($xlsRow, $iCol, $value);
	      }
	    	
	    	$iCol++;
	    }
	    $xlsRow++;
    }
    echo self::xlsEOF();
		
		return;
	}
	
	private static function xlsBOF() { 
	  return pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0); 
	} 
	
	private static function xlsEOF() { 
    return pack("ss", 0x0A, 0x00); 
	} 
	
	private static function xlsWriteNumber($Row, $Col, $Value) { 
		$sReturn = '';
		
    $sReturn .= pack("sssss", 0x203, 14, $Row, $Col, 0x0); 
    $sReturn .= pack("d", $Value); 
    
    return $sReturn; 
	} 
	
	private static function xlsWriteLabel($Row, $Col, $Value ) { 
		$sReturn = '';
		
		$Value = utf8_decode( $Value );
		
    $L = strlen($Value); 
    $sReturn .= pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L); 
    $sReturn .= $Value; 
    
    return $sReturn; 
	}
	
}
