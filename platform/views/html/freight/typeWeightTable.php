<?php

if( empty($_GET['freightTypeId']) || !is_numeric($_GET['freightTypeId']) ) {
	$oRouter->redirect( $oRouter->getPath('adminFreightTypes') );
}

$oFreightWeight = clRegistry::get( 'clFreightWeight', PATH_MODULE . '/freight/models' );
$aFreightDataDict = $oFreightWeight->oDao->getDataDict();
$aErr = array();

if( !empty($_POST['frmAddFreightWeight']) ) {

	$_POST += array(
		'freightWeightFrom' => 0,
		'freightWeightFromUnit' => 1,
		'freightWeightTo' => 0,
		'freightWeightToUnit' => 1
	);

	// Wash input a little
	$_POST['freightWeightFrom'] = str_replace(',', '.', $_POST['freightWeightFrom']);
	$_POST['freightWeightTo'] = str_replace(',', '.', $_POST['freightWeightTo']);
	$_POST['freightWeightFrom'] = str_replace(' ', '', $_POST['freightWeightFrom']);
	$_POST['freightWeightTo'] = str_replace(' ', '', $_POST['freightWeightTo']);
	
	// Recalculate values posted to grams
	$_POST['freightWeightFrom'] = $_POST['freightWeightFrom'] * $_POST['freightWeightFromUnit'];
	$_POST['freightWeightTo'] = $_POST['freightWeightTo'] * $_POST['freightWeightToUnit'];

	if( $_POST['freightWeightFrom'] > $_POST['freightWeightTo'] ) {
		$aErr = array( 'freightWeightFrom' => _( "Weight from can't be higher than weight to" ) );
	}

	if( $_POST['freightWeightFrom'] < 0 ) {
		$aErr = array(
			'freightWeightFrom' => _( 'Weight from can\'t be less than zero' )
		);
	}
	if( $_POST['freightWeightTo'] < 0 ) {
		$aErr = array(
			'freightWeightTo' => _( 'Weight to can\'t be less than zero' )
		);
	}

	// Validate the sanity of the input
	$aFreightWeightToValidateAgainst = $oFreightWeight->readByFreightType( $_GET['freightTypeId'], array(
		'freightWeightId',
		'freightWeightFrom',
		'freightWeightTo'
	) );
	if( !empty($aFreightWeightToValidateAgainst) ) {
		
		foreach( $aFreightWeightToValidateAgainst as $entry ) {
			
			// Don't check against current weight if updating
			if( isset($_GET['freightWeightId']) && $_GET['freightWeightId'] == $entry['freightWeightId'] ) continue;
			
			// Check if from is between old values
			if( $_POST['freightWeightFrom'] > $entry['freightWeightFrom']
			   && $_POST['freightWeightFrom'] < $entry['freightWeightTo'] ) {
				$aErr = array(
					'freightWeightFrom' => _( 'Weight from is between existing values' )
				);				
			}
			
			// Check if to is between old values
			if( $_POST['freightWeightTo'] > $entry['freightWeightFrom']
			   && $_POST['freightWeightTo'] < $entry['freightWeightTo'] ) {
				$aErr = array(
					'freightWeightTo' => _( 'Weight to is between existing values' )
				);
			}
			
			// Check from for same values
			if( $_POST['freightWeightFrom'] == $entry['freightWeightFrom']
			   || $_POST['freightWeightFrom'] == $entry['freightWeightTo']
			) {
				$aErr = array(
					'freightWeightFrom' => _( 'The same from value is already in the table' )
				);
			}
			
			// Check to for same values
			if( $_POST['freightWeightTo'] == $entry['freightWeightTo']
			   || $_POST['freightWeightTo'] == $entry['freightWeightFrom']
			) {
				$aErr = array(
					'freightWeightTo' => _( 'The same to value is already in the table' )
				);
			}
			
		}
		
	}
	
	if( empty($aErr) ) {

		if( !empty($_GET['freightWeightId']) ) {
			// Update
			$oFreightWeight->update( $_GET['freightWeightId'], $_POST );
			$aErr = clErrorHandler::getValidationError( 'updateFreightWeight' );
			if( empty($aErr) ) {
				unset( $_GET['freightWeightId'] );
			}
		} else {
			// Create
			
			if( $iFreightWeightId = $oFreightWeight->create( array(
				'freightTypeId' => $_GET['freightTypeId'],
				'freightWeightFrom' => $_POST['freightWeightFrom'],
				'freightWeightTo' => $_POST['freightWeightTo'],
				'freightWeightAddition' => $_POST['freightWeightAddition'],
				'freightWeightStatus' => $_POST['freightWeightStatus']
			) ) ) {
				
			} else {
				$aErr = clErrorHandler::getValidationError( 'createFreightWeight' );
			}
		}
	} else {
		// Show input again
		
		$oTemplate->addBottom( array(
			'key' => 'jsShowFrmAddFreightWeight',
			'content' => '
			<script>
				$(document).ready( function() {
					$("tr#frmAddFreightWeight").show();
				} );				
			</script>'
		) );
	}
	
}

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$aFreightWeightData = $oFreightWeight->readByFreightType( $_GET['freightTypeId'], '*' );

// Edit
if( !empty($_GET['freightWeightId']) ) {
	$aFreightWeightEditData = current( $oFreightWeight->read('*', $_GET['freightWeightId']) );
	$sTitle = '';
} else {
	$aFreightWeightEditData = $_POST;
	$sTitle = '<a href="#frmAddFreightWeight" class="toggleShow icon iconText iconAdd">' . _( 'Add freight weight' ) . '</a>';
}
$sOutput = '';

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oFreightWeight->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aFreightWeightEditData,
	'errors' => $aErr,
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Save' )
	),
) );
$oOutputHtmlForm->setFormDataDict( array(
	'freightWeightFrom' => array(),
	'freightWeightFromUnit' => array(
		'type' => 'array',
		'values' => array(
			'1' => _( 'Gram' ),
			'1000' => _( 'Kilogram' ),
			'1000000' => _( 'Tonne' )
		),
		'defaultValue' => '1000',
		'title' => _( 'Unit' )
	),
	'freightWeightTo' => array(),
	'freightWeightToUnit' => array(
		'type' => 'array',
		'values' => array(
			'1' => _( 'Gram' ),
			'1000' => _( 'Kilogram' ),
			'1000000' => _( 'Tonne' )
		),
		'defaultValue' => '1000',
		'title' => _( 'Unit' )
	),
	'freightWeightAddition' => array(),
	'freightWeightStatus' => array(),
	'frmAddFreightWeight' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

$oOutputHtmlTable = new clOutputHtmlTable( $oFreightWeight->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'freightWeightFrom' => array(),	
	'freightWeightTo' => array(),	
	'freightWeightAddition' => array(),
	'freightWeightStatus' => array(),
	//'freightWeightUpdated' => array(),
	'freightWeightCreated' => array(),
	
	'tableRowControls' => array(
		'title' => ''
	)
) );

$aAddForm = array(
	'freightWeightFrom' => $oOutputHtmlForm->renderFields( 'freightWeightFrom' ) . $oOutputHtmlForm->renderFields( 'freightWeightFromUnit' ),
	'freightWeightTo' => $oOutputHtmlForm->renderFields( 'freightWeightTo' ) . $oOutputHtmlForm->renderFields( 'freightWeightToUnit' ),
	'freightWeightAddition' => $oOutputHtmlForm->renderFields( 'freightWeightAddition' ),
	'freightWeightStatus' => $oOutputHtmlForm->renderFields( 'freightWeightStatus' ),
	//'freightWeightUpdated' => '',
	'freightWeightCreated' => $oOutputHtmlForm->renderFields( 'frmAddFreightWeight' ) . $oOutputHtmlForm->renderFields( 'acoControls' ) . $oOutputHtmlForm->renderButtons(),
	'tableRowControls' => ''
);

foreach( $aFreightWeightData as $entry ) {
	if( !empty($_GET['freightWeightId']) && $entry['freightWeightId'] == $_GET['freightWeightId'] ) {
		$aAddForm['freightWeightCreated'] .= '<a href="?' . stripGetStr( array('freightWeightId', 'event', 'deleteFreightWeight') ) . '">' . _( 'Back' ) . '</a>';
		$oOutputHtmlTable->addBodyEntry( $aAddForm );	
	} else {
		$oOutputHtmlTable->addBodyEntry( array(
			'freightWeightFrom' => $entry['freightWeightFrom'] . ' ' . _( 'gram' ),
			'freightWeightTo' => $entry['freightWeightTo'] . ' ' . _( 'gram' ),
			'freightWeightAddition' => $entry['freightWeightAddition'],
			'freightWeightStatus' => '<span class="' . $entry['freightWeightStatus'] . '">' . _( $aFreightDataDict['entFreightWeight']['freightWeightStatus']['values'][ $entry['freightWeightStatus'] ] ) . '</span>',
			//'freightWeightUpdated' => $entry['freightWeightUpdated'],
			'freightWeightCreated' => $entry['freightWeightCreated'],
			'tableRowControls' => '
			<a href="?freightWeightId=' . $entry['freightWeightId'] . '&' . stripGetStr( array( 'deleteFreightWeight', 'event', 'freightWeightId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
			<a href="?event=deleteFreightWeight&deleteFreightWeight=' . $entry['freightWeightId'] . '&' . stripGetStr( array( 'deleteFreightWeight', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		) );
	}
	

}

if( empty($_GET['freightWeightId']) ) {
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmAddFreightWeight',
		'style' => 'display: table-row;'
	) );
}

$sOutput .= $oOutputHtmlTable->render();;
echo '
	<div class="view typeWeightTable">
		<h1>', _( 'Freight weight' ), '</h1>
		', $sTitle, '		
		', $oOutputHtmlForm->renderForm( $oOutputHtmlForm->renderErrors() . $sOutput ), '
	</div>';
