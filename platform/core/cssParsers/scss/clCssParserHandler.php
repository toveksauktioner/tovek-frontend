<?php

/**
* $Id: index.php 1498 2015-01-21 09:21:00Z suarez $
* This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
*
* This class acts as a handler for parsing .scss files with scssphp
*
* @package		argoPlatform
* @category		Framework
* @link 		http://www.argonova.se
* @author		$Author: suarez $
* @version		Subversion: $Revision: 1498 $, $Date: 2015-01-21 10:21:00 +0100 (on, 21 jan 2015) $
*
* http://leafo.github.io/scssphp/docs/example.html
*/

require_once CSS_PARSER_PATH . '/parser/scss.inc.php';
require_once CSS_PARSER_PATH . '/parser/clScssParserFormatter.php';
require_once CSS_PARSER_PATH . '/parser/clScssParserServer.php';

class clCssParserHandler {

	public $sBaseDir;
	public $aRenderBuffer = array();

	private $oCompiler;
	
	public function __construct( $sBaseDir ) {
		$this->sBaseDir = $sBaseDir;
		
		//$this->oCompiler = new Leafo\ScssPhp\Compiler();
	}

	/**
	 * Communicative function
	 */
	public function output( $mFile ) {
		if( is_array($mFile) ) {
			foreach( $mFile as $sFile ) {
				$this->render( $sFile );
			}
			return implode( '', $this->aRenderBuffer );
		
		} else {
			return $this->render( $mFile );
		}
	}

	/**
	 * Main render function to determine the required render type usage
	 */
	public function render( $sFile ) {
		$sExtension	= '.' . pathinfo( $sFile, PATHINFO_EXTENSION );
		
		// If parsable file
		if( $sExtension === '.scss' ) {
			return $this->renderScss( $sFile );
		}

		// Regular CSS file
		if( $sExtension === '.css' ) {
			return $this->renderCss( $sFile );
		}
	}
	
	/**
	 * Render normal css
	 */
	public function renderCss( $sFile ) {
		if( !realpath( $sFile ) ) {
			throw new Exception( 'File not found: ' . $sFile );
		}
		$this->aRenderBuffer[ $sFile ] = file_get_contents( $sFile );
		return $this->aRenderBuffer[ $sFile ];
	}
	
	/**
	 * Render compiled scss
	 */
	public function renderScss( $sFile ) {
		$sPathFile = $this->sBaseDir . '/' . $sFile;
		$sFilename = basename( $sPathFile );
		$sDirectory = dirname( $sPathFile );
		
		// Ignore if file name begins with an underscore
		if( !$sFilename || $sFilename[0] === '_' ) {
			return;
		}
		
		$oScss = new Leafo\ScssPhp\Compiler();
		
		/**
		 * Set output format
		 */
		//$oScss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
		//$oScss->setFormatter('Leafo\ScssPhp\Formatter\Expanded');
		//$oScss->setFormatter('Leafo\ScssPhp\Formatter\Nested');
		//$oScss->setFormatter('Leafo\ScssPhp\Formatter\Compact');
		//$oScss->setFormatter('Leafo\ScssPhp\Formatter\Crunched');
		$oScss->setFormatter( 'Leafo\ScssPhp\clScssParserFormatter' );
		
		// Set import path
		$oScss->setImportPaths( array(
			CSS_PARSER_PATH . '/imports',
			$sDirectory
		) );
		
		// Set line comments
		#$oScss->setLineNumberStyle( Leafo\ScssPhp\Compiler::LINE_COMMENTS );
		
		// Set source Maps
		#$oScss->setSourceMap( Leafo\ScssPhp\Compiler::SOURCE_MAP_INLINE );
		
		// Example of "Custom Functions" usage
		//$oScss->registerFunction(
		//	'add-two',
		//	function( $args ) {
		//		list($a, $b) = $args;
		//		return $a[1] + $b[1];
		//	}
		//);
		
		// Init parse server
		$oServer = new Leafo\ScssPhp\clScssParserServer( $sPathFile, CSS_PARSER_CACHE_PATH, $oScss );
		$oServer->showErrorsAsCSS( $GLOBALS['debug'] );
		
		// Parse file
		$this->aRenderBuffer[ $sFile ] = $oServer->serve();
		
		return $this->aRenderBuffer[ $sFile ];
	}
	
}
