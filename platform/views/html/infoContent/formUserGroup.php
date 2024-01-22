<?php

$oAcl = clRegistry::get( 'clAcl' );
$oAcl->aroId = array();
$aAclDataDict = $oAcl->oDao->getDataDict();

if( !empty($_GET['layoutKey']) ) {
	
	// Verify that this is a infoContent layout
	if( mb_substr($_GET['layoutKey'], 0, 10) != 'guestInfo-' ) {
		throw new Exception( _( 'No access. Layout does not belong to infoContent' ) );
	}
	
	// Check if layout exists
	$oLayoutHtml = clRegistry::get( 'clLayoutHtml' );
	$aLayoutData = $oLayoutHtml->read( 'layoutTitleTextId', $_GET['layoutKey'] );
	if( empty($aLayoutData) ) {
		$oRouter->redirect( $oRouter->getPath('adminInfoContentPages') );
	}
	
	$aAclData = array(
		'aroId' => array()
	);
	
	if( !$oUser->oAclGroups->isAllowed('superuser') ) {
		$aUserGroups = array_keys($oUser->oAclGroups->aAcl);
		$aUserGroups = array_combine( $aUserGroups, $aUserGroups );
		// Add the admin group, as infopages should be visible for admins if needed		
		if( !array_key_exists('admin', $aUserGroups) ) {
			$aUserGroups['admin'] = 'admin';
		}
	} else {
		require_once PATH_FUNCTION . '/fData.php';
		$oUserManager = clRegistry::get( 'clUserManager' );
		$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupKey' );
	}
	if( isset($aUserGroups['super']) ) unset( $aUserGroups['super'] );
	ksort( $aUserGroups );
	
	$oAcl->setAro( $aUserGroups, 'userGroup' );
	
	if( !empty($_POST['frmAclUserGroupAdd']) ) {
		$_POST['aroId'] = !empty( $_POST['aroId'] ) ? array_intersect( $_POST['aroId'], $aUserGroups ) : array();
		$oAcl->updateByAco( $_GET['layoutKey'], 'layout', $_POST['aroId'], 'userGroup' );
		
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
	
	$aAcl = $oAcl->readByAco( $_GET['layoutKey'], array(
		'aclAroId',
		'aclAccess'
	), 'layout', 'userGroup' );
	foreach( $aAcl as $entry ) {
		if( in_array($entry['aclAroId'], $aUserGroups) && $entry['aclAccess'] == 'allow' ) $aAclData['aroId'][] = $entry['aclAroId'];
	}
	
	// Format userGroups a little nicer
	foreach( $aUserGroups as &$aValue ) {
		$aValue = _( ucfirst($aValue) );
	} 
	
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $aAclDataDict, array(
		'action' => '',
		'attributes' => array( 'class' => 'marginal' ),
		'data' => $aAclData,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'aroId' => array(
			'type' => 'arraySet',
			'appearance' => 'full',
			'values' => $aUserGroups,
			'title' => _( 'User groups' )
		),
		'frmAclUserGroupAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	
	echo '
		<div class="view adminAclAcoAdd">
			<h1>' . sprintf( _( 'Permissions for "%s"' ), $aLayoutData[0]['layoutTitleTextId'] ) . '</h1>
			' . $oOutputHtmlForm->render() . '
		</div>';
}