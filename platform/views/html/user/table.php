<?php

$oUserManager = clRegistry::get( 'clUserManager' );

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oUser->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('username' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'username' => array(),
	'infoName' => array()
) );

$aReadFields = array(
	'entUser.userId',
	'username',
	'userEmail',
	'infoName'
);

// Search
if( !empty($_GET['searchQuery']) ) {
	$aSearchCriterias = array(
		'customerSearch' => array(
			'type' => 'like',
			'value' => $_GET['searchQuery'],
			'fields' => array(
				'entUser.userId',
				'userEmail',
				'username',
				'infoName'
			)
		)
	);
	$oUserManager->oDao->setCriterias( $aSearchCriterias );
} else {
	$oUserManager->oDao->setEntries( 100 );
}

// User list
if( $oUser->oAclGroups->isAllowed('superuser') ) {	
	$aUsers = $oUserManager->read( $aReadFields );
	$oUserManager->oDao->sCriterias = '';	
	$oSorting->end();
	$oUserManager->oDao->aSorting = array();
	
} else {
	$aUsers = $oUserManager->readByGroup( array_keys( $oUser->oAclGroups->aAcl ), $aReadFields );
	$oUserManager->oDao->sCriterias = '';
	$oSorting->end();
	$oUserManager->oDao->aSorting = array();
	
	// Remove all users with group 'super'
	require_once PATH_FUNCTION . '/fData.php';
	foreach( $aUsers as $key => $user ) {
		$aGroups = arrayToSingle( $oUserManager->oDao->readUserGroup( $user['userId'] ), 'groupKey', 'groupTitle' );
		if( array_key_exists( 'super', $aGroups ) ) {
			unset( $aUsers[$key] );
		}
	}
	
}

$aUsers = valueToKey( 'userId', $aUsers );

if( !empty($_GET['resetPassword']) ) {
	if( !empty($_GET['userId']) && in_array($_GET['userId'], array_keys($aUsers)) ) {		
		$sNewPassword = $oUserManager->updateRandomPass( $_GET['userId'], $aUsers[$_GET['userId']]['userEmail'] );
		$aErr = clErrorHandler::getValidationError( 'updateUser' );
		if( empty($aErr) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => sprintf( _( 'Password has been reset for user "<em><strong>%s</strong></em>". New password is' ) . ': <strong>%s</strong>', $aUsers[$_GET['userId']]['username'], $sNewPassword )
			) );
		}
	}
}

// Export
if( !empty($_GET['export']) && $_GET['export'] == 'csv' ) {
	$aFileHead = array();
	$sFileContent = '';
	foreach( $aUsers as $entry ) {
		$aFileContent = array();
		foreach( $entry as $sLabel => $sValue ) {
			if( count($aFileHead) < count($aReadFields) ) {
				$sTable = substr($sLabel, 0, 4) == 'info' ? 'entUserInfo' : 'entUser';
				if( !empty($aUserDataDict[$sTable][$sLabel]['title']) ) {
					$aFileHead[] = $aUserDataDict[$sTable][$sLabel]['title'];
				} else {
					$aFileHead[] = $sLabel;
				}
			}
			$aFileContent[] = $sValue;
		}
		$sFileContent .= implode(';', $aFileContent) . "\n";
	}
	echo implode(';', $aFileHead) . "\n" . $sFileContent;
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename=users.csv' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	exit;
}

$sOutput = '';

if( !empty($aUsers) && count($aUsers) > 0 ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oUser->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'userControls' => array(
			'title' => ''
		)
	) );

	foreach( $aUsers as $entry ) {
		$aRow = array(
			'username' => '<a href="' . $oRouter->sPath . '?userId=' . $entry['userId'] . '">' . $entry['username'] . '</a>',
			'infoName' => $entry['infoName'],
			'userControls' => '
				<a href="' . $oRouter->getPath( 'superUserAdd' ) . '?userId=' . $entry['userId'] . '" class="icon iconEdit iconText"><span>' . _( 'Edit' ) . '</span></a>
				<a href="' . $oRouter->sPath . '?resetPassword=true&userId=' . $entry['userId'] . '" class="icon iconLock iconText linkConfirm" title="' . _( 'Do you want to reset the password?' ) . '"><span>' . _( 'Reset password' ) . '</span></a>
				<a href="' . $oRouter->sPath . '?event=deleteUser&amp;deleteUser=' . $entry['userId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this user' ) . '?"><span>' . _( 'Delete' ) . '</span></a>'
		);
		$oOutputHtmlTable->addBodyEntry( $aRow );
	}

	/**
	 * Search form
	 */	
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $oUserManager->oDao->getDataDict(), array(
		'attributes' => array( 'class' => 'searchForm' ),
		'data' => $_GET,
		'buttons' => array(
			'submit' => _( 'Search' ),
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'searchQuery' => array(
			'title' => _( 'Search' )
		),
		'page' => array(
			'type' => 'hidden',
			'value' => 0
		)
	) );
	
	$sOutput = '
		<section class="tools">
			<div class="tool">
				' . $oOutputHtmlForm->render() . '
			</div>
			<div class="tool">
				<a href="' . $oRouter->sPath . '?export=csv" class="icon iconText iconDbImport">' . _( 'Export users as CSV-file' ) . '</a>
			</div>
		</section>
		<section>
			' . $oOutputHtmlTable->render() . '
		</section>';
		
} else {
	/**
	 * Search form
	 */	
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $oUserManager->oDao->getDataDict(), array(
		'attributes' => array( 'class' => 'searchForm' ),
		'data' => $_GET,
		'buttons' => array(
			'submit' => _( 'Search' ),
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'searchQuery' => array(
			'title' => _( 'Search' )
		),
		'page' => array(
			'type' => 'hidden',
			'value' => 0
		)
	) );
	
	$sOutput = '
		<section class="tools">
			<div class="tool">
				' . $oOutputHtmlForm->render() . '
			</div>
			<div class="tool">
				<a href="' . $oRouter->sPath . '?export=csv" class="icon iconText iconDbImport">' . _( 'Export users as CSV-file' ) . '</a>
			</div>
		</section>
		<section>
			<strong>' . _( 'There are no items to show' ) . '</strong>
		</section>';
}

echo '
	<div class="view user table">
		<h2>' . _( 'Users' ) . '</h2>
		' . $sOutput . '
	</div>';
