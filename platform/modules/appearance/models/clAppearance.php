<?php

require_once PATH_MODULE . '/appearance/config/cfAppearance.php';

class clAppearance {

	/**
	 * List of predefined types. Only these and quoted options are valid types
	 */
	private $aPredefinedTypeRegexes = array(
		'boolean' => '/bool(ean)?/i',
		'color' => '/colou?rs?/i',
		'image' => '/images?/i',
		'integer' => '/integers?|numbers?/i'
	);
	private $sTemplate; // The template name
	private $mCache;

	public function __construct() {
		$this->sModuleName = 'Appearance';
		$this->sModulePrefix = 'appearance';

		$this->sTemplate = APPEARANCE_DEFAULT_TEMPLATE;
	}

	public function fetchTemplateNames() {
		$aItems = scandir( APPEARANCE_TEMPLATE_PATH );

		$aTemplates = array();
		foreach( $aItems as $key => &$sItem ) {
			if( !empty( $sItem ) && $sItem[0] != '.' && is_dir( APPEARANCE_TEMPLATE_PATH . '/' . $sItem ) ) {
				$aTemplates[ $sItem ] = $sItem;
			}
		}

		return $aTemplates;
	}

	public function generateCustomFile( $sContent ) {
		$sFilePath = APPEARANCE_TEMPLATE_PATH . '/' . $this->sTemplate . '/_custom.scss';

		file_put_contents( $sFilePath, $sContent );
	}

	public function getVariable( $sVariableKey ) {
		$aCache = $this->getVariables();

		if( empty( $aCache['variables'] ) || empty( $aCache['variables'][ $sVariableKey ] ) ) return false;
		return $aCache['variables'][ $sVariableKey ]['value'];
	}

	public function getVariables() {
		if( !$this->mCache ) {
			$this->mCache = $this->parseVariables();
		}

		return $this->mCache;
	}

	public function parseFile( $sFilename, $bExtractDefaults = false ) {
		$sPath = APPEARANCE_TEMPLATE_PATH . '/' . $this->sTemplate . '/' . $sFilename;

		if( !is_file( $sPath ) ) throw new Exception( sprintf( _( 'The bootstrap file "%s" was not found' ), $sPath ) );

		$sContent = file_get_contents( $sPath );

		return $this->parseContent( $sContent, $bExtractDefaults );
	}

	public function parseVariables() {
		$aVariables = $this->parseFile( '_bootstrap.scss', true );

		// Try parsing the custom file and use these variables
		if( $aCustom = $this->parseFile( '_custom.scss' ) ) {
			foreach( $aVariables['variables'] as $variableKey => &$aVariable ) {
				if( isset( $aCustom['variables'][ $variableKey ] ) ) $aVariables['variables'][ $variableKey ]['value'] = $aCustom['variables'][ $variableKey ]['value']; // Use the custom value if set
			}
		}

		return $aVariables;
	}

	/**
	 * Parse variables and extract 4 variables and group together in the following format (uppercase characters)
	 * // GROUP
	 * $VARIABLE: VALUE; // COMMENT
	 *
	 * The variables will be added to an array titled with the previous group name
	 */
	public function parseContent( $sContent, $bExtractDefaults = false ) {
		$aPattern = array(
			'comment' => '\/\/\h*(.*)', 		// A line comment
			'heading' => '(?:\/\/\h*(.*)\s)?', 	// Alone comment above variable
			'newlineOrEnd' => '(?:\n|$)', 		// End of a line or file
			'value' => '\s*\:\s*(.*)', 			// Variable value
			'valueEnding' => '\h*;\h*', 		// End of a value
			'variable' => '(\$[\w\-]+)' 		// Variable
		);
		
		$sPattern = '/' . $aPattern['heading'] . $aPattern['variable'] . $aPattern['value'] . '(?:' . $aPattern['valueEnding'] . $aPattern['newlineOrEnd'] . '|(?:' . $aPattern['valueEnding'] . $aPattern['comment'] . '))/i';
		preg_match_all( $sPattern, $sContent, $aMatches,  PREG_SET_ORDER );

		$iCurrentGroup = 0;
		$aReturn = array(
			'groups' => array(),
			'variables' => array(),
		);
		foreach( $aMatches as &$aMatch ) {
			$sGroup	= &$aMatch[1];
			$sVariable = &$aMatch[2];
			$sValue	= &$aMatch[3];

			// Comment parsing
			$sComment = !empty( $aMatch[4] ) ? $aMatch[4] : null;
			$aComment = $this->parseComment( $sComment ); // Parse comment and extract title and type

			// If new group found
			if( !empty( $sGroup ) ) {
				if( empty( $aReturn['groups'][ $iCurrentGroup ]['variables'] ) ) unset( $aReturn['groups'][ $iCurrentGroup ] ); // Remove previous group if it was empty

				$iCurrentGroup++;
				$aReturn['groups'][ $iCurrentGroup ] = array(
					'title' => $sGroup,
					'variables' => array()
				);
			}

			if( $bExtractDefaults ) {
				if( substr( $sValue, -8) !== '!default' ) continue; // Skip if value doesn't end with !default
				$sValue = trim( substr( $sValue, 0, -8 ) ); // Strip !default
			}

			// If image, normalize url to path
			$aReturn['variables'][ $sVariable ] = array(
				'title' => $aComment['title'],
				'types' => $aComment['types'],
				'value' => $sValue
			);
			
			$aReturn['groups'][ $iCurrentGroup ]['variables'][ $sVariable ] = $sVariable; // Add variable to group
		}
		
		if( empty( $aReturn['groups'][ $iCurrentGroup ]['variables'] ) ) unset( $aReturn['groups'][ $iCurrentGroup ] ); // Remove previous group if it was empty

		return $aReturn;
	}

	/**
	 * Extract properties from comment. Properties are defined witin optional first "(" and last ")" witin a comment; Like this: // [comment] ([properties])
	 */
	public function parseComment( $sComment ) {
		if( ($iPropertyStart = strpos( $sComment, '(' )) && ($iPropertyEnd = strrpos( $sComment, ')')) ) {
			$sProperties = trim(substr( $sComment, $iPropertyStart+1, ( $iPropertyEnd - ($iPropertyStart + 1) ) ) );
			$sComment = trim( substr( $sComment, 0, $iPropertyStart ) ); 	// Since props were found, remove them from the "real" comment

			$aTypes = $this->parseProperties( $sProperties );
		}

		if( empty( $aTypes ) ) $aTypes = array('string');

		return array(
			'title' => $sComment,
			'types' => $aTypes,
		);
	}

	public function parseProperties( $sProperties ) {
		if( empty( $sProperties ) ) $aProperties = array( 'string' ); // If empty, return string as default
		
		$aTypes = array(); // Store types here
		
		try {
			$aParts = preg_split('~(?:\'[^\']*\'|"[^"]*"|)\K(,|$)~', $sProperties); // Split by all non quoted commas
			foreach( $aParts as $sProperty ) {
				try {
					$sProperty = trim( $sProperty ); // Trim
					if( empty( $sProperty ) ) continue; // Skip if empty

					if( $sProperty[ 0 ] == '"' && substr($sProperty, -1) == '"' ) { // If quoted, then this is an option
						if( !isset( $aTypes['array'] ) ) $aTypes['array'] = array(); // Define option type
						$sKey = trim( $sProperty, '"' ); // Same option key and name
						$aTypes['array'][ $sKey ] = $sKey; // Add option
					} elseif( $sPredefinedType = $this->parsePredefinedType( $sProperty ) ) { // Check if predefined type
						$aTypes[$sPredefinedType] = $sPredefinedType;
					} else {
						throw new Exception( sprintf( _( '"%s" is not a valid property' ), $sProperty ) );
					}
				} catch( Exception $e ) {
					throw $e;
				}
			}
		} catch( Exception $e ) {
			throw $e;
		}

		return $aTypes;
	}

	public function parsePredefinedType( $sType ) {
		foreach( $this->aPredefinedTypeRegexes as $sKey => &$sRegex ) {
			if( preg_match( $sRegex, $sType ) ) return $sKey;
		}
		return false;
	}

	public function setTemplate( $sTemplate ) {
		if( empty( $sTemplate ) || !ctype_alnum( $sTemplate ) ) throw new Exception( _( 'Invalid template name' ) ); // Only allow alphanumeric characters in template name
		$this->sTemplate = $sTemplate;
	}

	public function getImageParameterKeys( $sVariableKey ) {
		return array(
			'parentType' => $this->sModuleName,
			'crop' => false,
			'imageKey' => $sVariableKey,
			'maxWidth' => 5000,
			'maxHeight' => 5000,
		);
	}

	public function readImagePath( $sVariableKey ) {
		$aImageParams = $this->getImageParameterKeys( $sVariableKey );
		$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
		$oImage->setParams( $aImageParams );
		$aImage = current( $oImage->readByParent(1, '*') );
		if( empty( $aImage ) ) return false;
		return '/images/custom/' . $aImage['imageParentType'] . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
	}

	public function uploadAndReadImagePath( $sVariableKey ) {
		$aImageParams = $this->getImageParameterKeys( $sVariableKey );

		$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
		$oImage->setParams( $aImageParams );

		$oImage->deleteByParent( 1, $this->sModuleName, $sVariableKey ); // Delete previous images
		$oImage->setParams( $aImageParams ); // Reset image params as deleteByParent removes them

		if( $aErr = $oImage->createWithUpload( array(
			'allowedMime' => array(
				'image/jpeg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/gif' => 'gif',
				'image/png' => 'png'
			),
			'key' => $sVariableKey
		), 1 ) ) {
			throw new Exception( implode(', ', $aErr ) );
		}

		return $this->readImagePath( $sVariableKey );
	}
	
}
