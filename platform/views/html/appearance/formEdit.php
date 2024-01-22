<?php

$oApperance = clRegistry::get( 'clAppearance', PATH_MODULE . '/appearance/models' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

if( empty( $_GET['template'] ) ) $_GET['template'] = APPEARANCE_DEFAULT_TEMPLATE; // Select default template by default

$aErr = array();

/**
 * Template selection
 */
if( APPEARANCE_SELECTABLE_TEMPLATES ) {
	$aTemplates = $oApperance->fetchTemplateNames();

	$oOutputHtmlForm->init(
		array(
			'templateSelection' => array(
				'template' => array(
					'title'		=> _( 'Select template' ),
					'type'		=> 'array',
					'values'	=> $aTemplates
				)
			)
		), array(
		'attributes' => array(
			'class' => 'marginal selectTemplate',
			'onchange' => 'this.submit()'
		)
	));

	$sSelectTemplate = $oOutputHtmlForm->render();
	$oApperance->setTemplate( $_GET['template'] );
}

/**
 * Update
 */
if( !empty( $_POST['frmUpdateScss'] ) ) {
	/**
	 * Try post
	 */
	try {		
		$oUpload = clRegistry::get( 'clUpload' ); // Trigger clUpload to parse the $_FILES array
		$aVariables = $oApperance->parseVariables(); // Get variables
		
		$sContent = '';
		foreach( $aVariables['groups'] as $iGroupKey => &$aGroup ) {
				$sGroupContent = '';
				foreach ( $aGroup['variables'] as &$sVariableKey ) {
					$aVariable = &$aVariables['variables'][ $sVariableKey ]; // Variable alias
					
					// If this is an image
					if( !empty( $aVariable['types']['image'] ) ) {
						$sFilePath = empty( $_FILES[ $sVariableKey ]['tmp_name'][0] ) ? $aVariable['value'] : '\'' . $oApperance->uploadAndReadImagePath( $sVariableKey ) . '\'';
						$_POST[ $sVariableKey ] = $sFilePath; // Do not use 'url("' . $sFilePath . '")';
					}
					
					if( !isset( $_POST[ $sVariableKey ] ) ) throw new Exception( sprintf( _( '%s is missing' ), $aVariable['title'] ) );
					
					$sGroupContent .= $sVariableKey . ': ' . $_POST[ $sVariableKey ] . '; // ' . $aVariable['title'] . "\n";
				}
				if( !empty( $aGroup['title'] ) && !empty( $sGroupContent ) ) $sGroupContent = '// ' . $aGroup['title'] . "\n" . $sGroupContent; // Add group title if group not empty
				$sContent .= $sGroupContent . "\n";
		}
		
		$oApperance->generateCustomFile( $sContent );

	} catch( Exception $oException ) {
		$sMessage = $oException->getMessage();
		$sMessage[0] = strtolower( $sMessage[0] ); // lcfirst() for < PHP 5.3
		$aErr[] = sprintf( _( 'Appearance not saved beacause %s' ), $sMessage );
		
	}
}

try {
	/**
	 * Try parse
	 */
	try { 
		$aVariables = $oApperance->parseVariables(); // Get variables (again)
		$aGroups = array(); // Field groups in here
		$aScssDataDict = array();
		
		/**
		 * Form dict here
		 */
		foreach( $aVariables['groups'] as $iGroupKey => &$aGroup ) {
			$aGroups[ $iGroupKey ]	= array(
				'title'		=> !empty( $aGroup['title'] ) ? htmlspecialchars( $aGroup['title'] ) : '',
				'fields'	=> array(),
			);
			
			foreach( $aGroup['variables'] as &$sVariableKey ) {
				$aVariable = &$aVariables['variables'][ $sVariableKey ]; // Variable alias
				
				$aScssDataDict[ $sVariableKey ] = array(
					'title'	=>  $aVariable['title'] ? $aVariable['title'] : $sVariableKey ,
					'value'	=>  $aVariable['value'],
				);
				
				// Special types
				if( !empty( $aVariable['types']['array'] ) ) {
					$aScssDataDict[ $sVariableKey ]['type'] = 'array';
					$aScssDataDict[ $sVariableKey ]['values'] = $aVariable['types']['array'];
				} elseif( !empty( $aVariable['types']['boolean'] ) ) { // If boolean, make true/false
					$aScssDataDict[ $sVariableKey ]['type'] = 'array';
					$aScssDataDict[ $sVariableKey ]['values'] = array( 'true' => _('true'), 'false' => _('false') );
				} elseif( !empty( $aVariable['types']['image'] ) ) {
					$aScssDataDict[ $sVariableKey ]['type'] = 'upload';
				}
				
				// Add type classes
				$aClasses = $aVariable['types'];
				if( isset( $aClasses['array'] ) ) $aClasses['array'] = 'array'; // Normalize array into string
				$aScssDataDict[ $sVariableKey ]['attributes'] = array( 'class' => implode(' ', $aClasses ) );
				
				$aGroups[ $iGroupKey ]['fields'][] = $sVariableKey; // Add variable field to current field group
			}
		}
		
	} catch( Exception $e ) {
		$sMessage = $e->getMessage();
		$sMessage[0] = strtolower( $sMessage[0] ); // lcfirst() for < PHP 5.3
		throw new Exception( sprintf( _( 'The file cannot be parsed because %s' ), $sMessage ) );
		
	}
	
	/**
	 * Form
	 */
	$oOutputHtmlForm->init(
		array(
			'scssDict' => $aScssDataDict + array(
				'frmUpdateScss' => array(
					'type' => 'hidden',
					'value' => 1
				)
			)
		),
		array(
			'data' => arrayToSingle( $aScssDataDict, true, 'value' ),
			'method' => 'post',
			'errors' => $aErr,
			'labelSuffix' => ':',
			'attributes' => array(
				'class' => 'marginal',
			)
		)
	);	
	$oOutputHtmlForm->setGroups( $aGroups );
	$sCssEdit = $oOutputHtmlForm->render();
	
} catch( Exception $oException ) {
	$sMessage = $oException->getMessage();
	$sMessage[0] = strtolower( $sMessage[0] ); // lcfirst() for < PHP 5.3

	$sCssEdit = '
		<section>
			<h2>' . _( 'Template not customizable' ) . '</h2>
			' . sprintf( _( 'You cannot customize this template because %s.' ), $sMessage ) . '
		</section>';
}

echo '
	<div class="view appearance formEdit">
		<h1>' . _( 'Appearance' ) . '</h1>
		' . ( !empty( $sSelectTemplate ) ? $sSelectTemplate : '' ) . '
		' . ( !empty( $sCssEdit ) ? $sCssEdit : '' ) . '
	</div>';

/**
 * Scripts
 */
$oTemplate->addBottom( array(
	'key' => 'colorpickerBase',
	'content' => '<script type="text/javascript" src="/js/jquery.colorpicker.min.js"></script>'
) );
$oTemplate->addBottom( array(
	'key' => 'colorpickerSelector',
	'content' => '
		<script type="text/javascript">
			$(".view.appearance.formEdit input.color").colorPicker( {
				animationSpeed: 0, // toggle animation speed,
				doRender: false, // Do not color input,
				renderCallback: function($element, toggled) {
					if( this.color.colors.alpha >= 1 ) {
						$element.val("#" + this.color.colors.HEX.toLowerCase() ); // Make hex if no opacity
					}
				}
			} );
		</script>'
) );
