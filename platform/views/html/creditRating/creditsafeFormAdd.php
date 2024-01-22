<?php

/* * * *
 * Filename: creditsafeFormAdd.php
 * Created: 16/09/2014 by Renfors
 * Reference:
 * Description: View file for making a credit rating checkup with Creditsafe
 * * * */

$oCreditRating = clRegistry::get( 'clCreditRatingCreditsafe', PATH_MODULE . '/creditRating/models' );

$aErr = array();

$aDataDict = array(
	'entCreditsafe' => array(
		'creditsafeType' => array(
			'title' => _( 'Type' ),
			'type' => 'array',
			'required' => true,
			'values' => array(
				'privatePerson' => _( 'Private person' ),
				'company' => _( 'Company' )
			)
		),
		'creditsafePin' => array(
			'title' => _( 'Company/Person pin' ),
			'type' => 'string',
			'required' => true
		),
		'frmCreditsafe' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
);

// Post
if( !empty($_POST['frmCreditsafe']) && ($_POST['frmCreditsafe'] == 'true') ) {
	$_POST = array_intersect_key( $_POST, $aDataDict['entCreditsafe']  );
	
	$aErr = clDataValidation::validate( $_POST, $aDataDict );
	if( empty($aErr) ) {
		
		if( $_POST['creditsafeType'] == 'privatePerson' ) {
			// Private person checkup
				
			$aData = array(
				'SearchNumber' 	=> $_POST['creditsafePin'],
				'Block_Name' 		=> 'TOVEK_P_BASIC'
			);	
			$oReturn = $oCreditRating->getDataBySecure( $aData );
			$_GET['ratingId'] = $oCreditRating->iRatingId;
		} else {
			
			if( substr($_POST['creditsafePin'], 0, 3) == '556' ) { 	
				// Non joint-stock companies checkup
					
				$aData = array(
					'SearchNumber' 	=> $_POST['creditsafePin'],
					'Templates' 		=> 'TOVEKC2'
				);
						
				$oReturn = $oCreditRating->casCompanyService( $aData );
				$_GET['ratingId'] = $oCreditRating->iRatingId;
			} else {
				// Non joint-stock companies checup
				
				$aData = array(
					'SearchNumber' 	=> $_POST['creditsafePin'],
					'Block_Name' 		=> 'TOVEK_C_BASIC'
				);	
				$oReturn = $oCreditRating->getDataBySecure( $aData );
				$_GET['ratingId'] = $oCreditRating->iRatingId;
			}
		}
	}
	
	if( empty($aErr) ) {
		$oNotification->set( array( 'requestMade' => _('The Creditsafe request was made') ) );
		
		#$_POST = array();	
	} else {
		clErrorHandler::setValidationError( $aErr );
		$aErr = clErrorHandler::getValidationError( 'entCreditsafe' );
	}
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
	'action' => '',
	'errors' => $aErr,	
	'attributes' => array(
		'class' => 'marginal'
	),
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Checkup' )
	),
	'labelSuffix' => ':',
	'labelRequiredSuffix' => '*'
) );

echo '
	<div class="view formCreditsafe">
		' . $oOutputHtmlForm->render() . '
	</div>';