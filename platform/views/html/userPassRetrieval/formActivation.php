<?php

/**
 * guestUserPassRetrieval
 * /glömt-lösenord
 * userPassRetrieval - formRetrieval.php

 * guestUserPassRetrievalActivation
 * /glömt-lösenord/aktivering
 * userPassRetrieval - formActivation.php
 *
 * http://front.tovek.se/glömt-lösenord/aktivering?key=7c022f216c54c9ea14fdf61ac227028f
 */

$aErr = array();

$oUserPassRetrieval = clRegistry::get( 'clUserPassRetrieval', PATH_MODULE . '/userPassRetrieval/models' );
$oUserManager = clRegistry::get( 'clUserManager' );

$aUserDataDict = $oUserManager->oDao->getDataDict();

/**
 * Key validation
 */
if( !empty($_GET['key']) ) {
	/**
	 * Temp access
	 */
	$oAcl = new clAcl();
	$oAcl->setAcl( array(
		'readUserPassRetrieval' => 'allow',
		'activateUserPassRetrieval' => 'allow'
	) + $oUser->oAcl->aAcl );
	$oUserPassRetrieval->setAcl( $oAcl );
	
	$aData = current( $oUserPassRetrieval->readCustom( array(
		'activationKey' => $_GET['key']
	) ) );
	
	if( !empty($aData) ) {
		$_SESSION['userPassRetrieval']['keyValidation'] = true;
		$_SESSION['userPassRetrieval']['key'] = $_GET['key'];
	}
}

/**
 * POST
 */
if( !empty($_POST['frmChangePass']) ) {
	if( $_POST['userPass'] != $_POST['userPassConfirm'] ) {
		$aErr['updateUserPass'][] = _( 'The passwords do not match' );
	}
	
	if( empty($aErr) ) {
		/**
		 * Temp access
		 */
		$oAcl = new clAcl();
		$oAcl->setAcl( array(
			'readUserPassRetrieval' => 'allow',
			'activateUserPassRetrieval' => 'allow'
		) + $oUser->oAcl->aAcl );
		$oUserPassRetrieval->setAcl( $oAcl );
		
		$aData = current( $oUserPassRetrieval->readCustom( array(
			'activationKey' => $_POST['key']
		) ) );
		
		if( !empty($aData) ) {
			$sUserEmail = current(current( $oUserManager->read( 'userEmail', $aData['retrievalUserId'] ) ));
			
			if( $oUserPassRetrieval->update( $aData['retrievalId'], array(
				'retrievalPass' => hashUserPass( $_POST['userPass'], $sUserEmail )				
			) ) ) {
				if( $oUserPassRetrieval->activateByKey( $_GET['key'] ) ) {
					$oNotification->set( array('userPassRetrievalActivation' => _( 'The new password has been set')) );
					unset( $_SESSION['userPassRetrieval'] );
					return;
					
				} else {
					$aErr['key'] = _( 'Could not activate the new password' );
				}
			}
		}
	}
}

/**
 * New pass form
 */
if( !empty($_SESSION['userPassRetrieval']['keyValidation']) && $_SESSION['userPassRetrieval']['keyValidation'] === true ) {
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( array(
		'entUserPassRetrieval' => array(
			'key' => array(
				'type' => 'hidden',
				'title' => _( 'Activation key' ),
				'value' => $_SESSION['userPassRetrieval']['key']
			),
			'userPass' => array(
				'type' => 'string',
				'title' => _( 'New password' ),
				'appearance' => 'secret',
				'min' => $aUserDataDict['entUser']['userPass']['min'],
				'max' => $aUserDataDict['entUser']['userPass']['max']
			),
			'userPassConfirm' => array(
				'title' => _( 'Confirm password' ),
				'appearance' => 'secret'
			),
			'frmChangePass' => array(
				'type' => 'hidden',
				'value' => true
			)
		)
	), array(
		'method' => 'post',
		'attributes' => array( 'class' => 'marginal' ),		
		'errors' => $aErr,
		'labelSuffix' => ':',
		'buttons' => array(
			'submit' => _( 'Activate' )
		)
	) );
	
	$sOutput = $oOutputHtmlForm->render();
	
} else {
	$sOutput = _( 'No key given or valid' );
	
}

echo '
	<div class="view userPassRetrieval formActivation">
		<h1>' . _( 'Activate new password' ) . '</h1>
		' . $sOutput . '
	</div>';