<?php

$sOutput = '';
$aSearchCriterias = array();

/*** Check´s if the request is made by ajax ***/
if( empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest' ) {
	$oRouter->redirect( '/' );
}

if( empty($_POST['searchString']) ) return;

$oUserManager = clRegistry::get( 'clUserManager' );
	$oUserManager->oDao->setEntries( 100 );

// Get search "words" and clean the data
$aSearchWords = explode( ' ', $_POST['searchString'] );
$aSearchInts = array();
$aSearchOther = array();
foreach( $aSearchWords as $sWord ) {
	if( strlen($sWord) > 2 ) {
	  if( ctype_digit($sWord) ) {
	    $aSearchInts[] = (int) $sWord;
	  } else {
	    $aSearchOther[] = $sWord;
	  }
	}
}
$aSearchWords = array_merge( $aSearchInts, $aSearchOther );

// Check that there is something left after cleanup
if( empty($aSearchWords) ) return;

// Search by customer no
if( !empty($aSearchInts) ) {
	$aUsersByCustomerNo = $oUserManager->readByCustomerNo( $aSearchInts, 'userId' );

	if( !empty($aUsersByCustomerNo) ) {
		$aSearchCriterias['customerNo'] = array(
			'type' => 'in',
			'fields' => 'userId',
			'value' => arrayToSingle( $aUsersByCustomerNo, null, 'userId' )
		);
	}
}

// Search by text
foreach( $aSearchOther as $sWord ) {
	$aSearchCriterias[] = array(
		'type' => 'like',
		'fields' => array(
	    'username',
	    'infoName',
	    'infoContactPerson',
	    'infoFirstname',
	    'infoSurname'
	  ),
		'value' => $sWord
	);
}
$oUserManager->oDao->setCriterias( $aSearchCriterias );

// Read users
$aUsers = $oUserManager->read( array(
	'userId',
	'userCustomerNo',
  'username',
  'infoName',
  'infoFirstname',
  'infoSurname'
) );


if( !empty($aUsers) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $GLOBALS['UserInfoDataDict'] );
	$oOutputHtmlTable->setTableDataDict( array(
		'userControls' => array(
			'title' => ''
		),
		'userCustomerNo' => array(
			'title' => _( 'Customer no' )
		),
		'infoName' => array(
			'title' => _( 'Namn' )
		),
		'username' => array(
			'title' => _( 'Username' )
		)
	) );

	foreach( $aUsers as $aUser ) {
		$bChecked = false;
		if( (!empty($_POST['userId']) && ($_POST['userId'] == $aUser['userId'])) || (count($aUsers) == 1) ) $bChecked = true;

		$row = array(
			'userControls' => '<input type="checkbox" name="userId[' . $aUser['userId'] . ']" class="userIdCheckbox"' . ( $bChecked ? ' checked="checked"' : '' ) . '>',
			'userCustomerNo' => $aUser['userCustomerNo'],
			'infoName' => '<strong>' . ( !empty($aUser['infoName']) ? $aUser['infoName'] : $aUser['infoFirstname'] . ' ' . $aUser['infoSurname'] ) . '</strong>',
			'username' => $aUser['username']
		);

		$oOutputHtmlTable->addBodyEntry( $row );
	}

	$sOutput .= $oOutputHtmlTable->render();
}

echo '
	<div class="view user ajaxSearchUser">
	' . $sOutput . '
	</div>
	<script>
		$(".userIdCheckbox").on( "click", function() {
			if( $(this).prop("checked") ) {
				$(".userIdCheckbox").prop( "checked", false );
				$( this ).prop( "checked", true );
			}
		} );
	</script>';
