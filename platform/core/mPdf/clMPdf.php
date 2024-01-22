<?php
// Simply a construct to use mPdf as normal object
// Manual at https://mpdf.github.io/

require_once PATH_PLATFORM . '/composer/vendor/autoload.php';

class clMPdf {

	public $oMPdf;

	public function __construct( $aParams = array() ) {
		// Try to raise max execution time and memory limit
		set_time_limit( 600 );
		ini_set( 'memory_limit', '1024M' );

		error_reporting(0);

		$aParams += array(
			'mode' => 'utf-8',
			'format' => 'A4',
			'fontDir' => __DIR__ . '/font',
			'fontdata' => [
				'open_sans' => [
					'R' => 'OpenSans-Regular.ttf',
					'I' => 'OpenSans-Italic.ttf'
				]
			],
			'default-font' => 'open_sans',
			'default_font_size' => 10,
			'orientation' => 'P',
			'margin_top' => 60,
			'margin_bottom' => 40,
		);

		$this->oMPdf = new \Mpdf\Mpdf( $aParams );

		// Tweak below to turn on optimizations
		$this->oMPdf->simpleTables = false; // if you do not need complex table borders
		$this->oMPdf->useSubstitutions = true; // Specify whether to substitute missing characters in UTF-8 (multibyte) documents with fonts from adobe core fonts
	}

	public function loadCss( $sCss ) {
		$this->oMPdf->WriteHTML( $sCss, 1 );
	}

	public function loadHtml( $sHtml, $iSub = 0 ) {
		$this->oMPdf->WriteHTML( $sHtml, $iSub );
	}

	public function loadUrl( $sUrl ) {
		if( $sData = file_get_contents( $sUrl ) ) {
			$this->loadHtml( $sData );
			return true;
		}

		return false;
	}

	public function setHtmlHeader( $sHtml = '' ) {
		$this->oMPdf->SetHTMLHeader( $sHtml );
	}

	public function setHtmlFooter( $sHtml = '' ) {
		$this->oMPdf->SetHTMLFooter( $sHtml );
	}

	public function output( $sFilename = null, $sMethod = '' ) {
		return $this->oMPdf->Output( $sFilename, $sMethod );
		exit;
	}

}
