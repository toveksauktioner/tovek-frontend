<?php

class clOutput {
	
	public static function render( $sOutput ) {
		if( defined('COMPRESS_OUTPUT_BUFFER') && COMPRESS_OUTPUT_BUFFER === true && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && ini_get('zlib.output_compression') != 1 && extension_loaded('zlib') === true  ) {
			ob_start( 'ob_gzhandler' );
		} else {
			ob_start();
		}

		clOutput::renderErrors();
		echo $sOutput;
		ob_end_flush();
	}
	
	public static function renderErrors() {
		foreach( clErrorHandler::$aErr as $key => $value ) {
			echo "\n" . $key . ' => ' . $value;
		}
	}

}