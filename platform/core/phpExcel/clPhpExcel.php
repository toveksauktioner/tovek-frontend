<?php

require_once PATH_CORE . '/phpExcel/PHPExcel.php';
require_once PATH_CORE . '/phpExcel/PHPExcel/IOFactory.php';

/**
 * Main class
 */
class clPhpExcel {
	
	protected $oExcel;	
	public $tool;
	
	public function __construct() {
		$this->oExcel = new PHPExcel();
		$this->tool = new clPhpExcelTools();
	}
	
	public function createFile( $aData, $sFilename, $aParams = array() ) {
		$aParams += array(
			'title' => $sFilename,
			'label' => true,
			'download' => true
		);
		
		/**
		 * Set document properties
		 */
		$this->oExcel->getProperties()->setCreator("Solus")
									  ->setLastModifiedBy("Solus")
									  ->setTitle("Offer")
									  ->setSubject("Offer")
									  ->setDescription("")
									  ->setKeywords("")
									  ->setCategory("");
		
		$aAlphabetical = range( 'A', 'Z' );
		$iRowCount = 1;
		
		$oActiveSheet = $this->oExcel->setActiveSheetIndex(0);
		
		if( $aParams['label'] === true ) {
			foreach( array_keys( current($aData) ) as $iKey => $sLabel ) {
				$oActiveSheet->setCellValue( $aAlphabetical[$iKey] . $iRowCount, $sLabel );
			}
			$iRowCount++;			
		}
		
		// Drop array keys
		foreach( $aData as $iKey => $aEntry ) $aData[$iKey] = array_values( $aEntry );
		
		/**
		 * Data
		 */
		foreach( $aData as $aEntry ) {			
			foreach( $aAlphabetical as $iDataKey => $sColumn ) {
				if( empty($aEntry[$iDataKey]) ) continue;
				$oActiveSheet->setCellValue( $sColumn . $iRowCount, $aEntry[$iDataKey] );
			}					
			$iRowCount++;
		}
		
		// Rename worksheet
		$this->oExcel->getActiveSheet()->setTitle( $aParams['title'] );
		
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$this->oExcel->setActiveSheetIndex(0);
		
		if( $aParams['download'] === true ) {
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="offer.xls"');
			header('Cache-Control: max-age=0');
		}
		
		$oWriter = PHPExcel_IOFactory::createWriter( $this->oExcel, 'Excel5' );
		$oWriter->save( 'php://output' );
		exit;		
	}
	
}

/**
 * Excel related tools
 */
class clPhpExcelTools {
	
	/**
	 * Convert a excel file into a csv file
	 */
	function excelFileToCsvFile( $sExcelFile, $sExcelFormat = 'Excel5', $sOutputDirectory = '' ) {
		if( empty($sOutputDirectory) ) {
			$sOutputDirectory = substr( $sExcelFile, 0, strrpos( $sExcelFile, '/' ) );
		}
		
		// Read excel file
		$oReader = PHPExcel_IOFactory::createReader( $sExcelFormat );
		$oPHPExcelReader = $oReader->load( $sExcelFile );
		
		// Write csv file
		$oWriter = PHPExcel_IOFactory::createWriter( $oPHPExcelReader, 'CSV' );		
		foreach( $oPHPExcelReader->getSheetNames() as $sSheetIndex => $sLoadedSheetName ) {
			$oWriter->setSheetIndex( $sSheetIndex );
			$oWriter->save( $sOutputDirectory . '/' . $sLoadedSheetName . '.csv' );		
		}
		
		return true;
	}
	
	/**
	 * The function 'fetch' which is called in this function is custom build by us.
	 */
	function excelToCsv( $sExcelFile, $sExcelFormat = 'Excel5' ) {		
		// Read excel file
		$oReader = PHPExcel_IOFactory::createReader( $sExcelFormat );
		$oPHPExcelReader = $oReader->load( $sExcelFile );
		
		// Write csv file
		$oWriter = PHPExcel_IOFactory::createWriter( $oPHPExcelReader, 'CSV' );
		$aCsvData = array();
		foreach( $oPHPExcelReader->getSheetNames() as $sSheetIndex => $sLoadedSheetName ) {
			$oWriter->setSheetIndex( $sSheetIndex );
			$aCsvData[] = $oWriter->fetch();
		}
		
		return $aCsvData;
	}
	
}