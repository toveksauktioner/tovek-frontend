<?php

$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );

$aDataDict = $oCustomer->oDao->getDataDict();

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oCustomer->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('infoName' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'customerNumber' => array(),
	'customerDescription' => array(),
	'groupNameTextId' => array(
		'title' => _( 'Customer type' )
	),
	'userStatus' => array()
) );

// Search form
if( !empty($_GET['searchQuery']) ) {
	$aSearchCriterias = array(
		'customerSearch' => array(
			'type' => 'like',
			'value' => $_GET['searchQuery'],
			'fields' => array(
				'entUser.userId',
				'userEmail',
				'infoName'
			)
		)
	);
	$oCustomer->oDao->setCriterias( $aSearchCriterias );
}
if( !empty($_GET['groupId']) ) {
	$aSearchCriterias = array(
		'groupId' => array(
			'type' => '=',
			'value' => $_GET['groupId'],
			'fields' => 'groupId'
		)
	);
	$oCustomer->oDao->setCriterias( $aSearchCriterias );
}

$aReadFields = array(
	'customerId',
	'customerNumber',
	'customerDescription',
	'customerBlacklisted',
	'customerUserId',
	'customerLastOrderId',
	'customerCreated',
	'userId',
	'userEmail',
	'userStatus',
	'infoName',
	'groupId',
	'groupNameTextId'
);

// Customers
$aCustomers = $oCustomer->read( $aReadFields );

$oCustomer->oDao->sCriterias = null;

// Export
if( !empty($_GET['export']) && $_GET['export'] == 'csv' ) {
	$aFileHead = array();
	$sFileContent = '';
	foreach( $aCustomers as $entry ) {
		$aFileContent = array();
		foreach( $entry as $sLabel => $sValue ) {
			if( count($aFileHead) < count($aReadFields) ) {
				$sTable = substr($sLabel, 0, 4) == 'info' ? 'entUserInfo' : 'entUser';
				if( !empty($aDataDict[$sTable][$sLabel]['title']) ) {
					$aFileHead[] = $aDataDict[$sTable][$sLabel]['title'];
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

if( !empty($aCustomers) && count($aCustomers) > 0 ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'userDiscount' => array(
			'title' => _( 'Discount' )
		),
		'userControls' => array(
			'title' => ''
		)
	) );

	$oDiscount = clRegistry::get( 'clDiscount', PATH_MODULE . '/discount/models' );
	$oDiscount->setParentType( 'User' );
	$aDiscounts = $oDiscount->readByParent( arrayToSingle($aCustomers, null, 'userId'), array(
		'discountParentId',
		'discountValue'
	) );
	$aDiscounts = arrayToSingle( $aDiscounts, 'discountParentId', 'discountValue' );

	$sUrlShow = $oRouter->getPath( 'adminCustomerShow' );
	$sUrlEdit = $oRouter->getPath( 'adminCustomerAdd' );

	foreach( $aCustomers as $entry ) {
		if( !empty($entry['userId']) ) {
			// Check online status
			$entry['userId'] = $oCustomer->oUser->isUserOnline( $entry['userId'] );
			$sDescription = '<a href="' . $sUrlShow . '?userId=' . $entry['userId'] . '">' . $entry['infoName'] . '</a>';

		} else {
			$sCustomerGroup = '';
			$sDescription = $entry['customerDescription'];
		}

		$sUrlDelete = !empty($entry['customerUserId']) ? 'event=deleteUser&amp;deleteUser=' . $entry['customerUserId'] : 'event=deleteCustomer&amp;deleteCustomer=' . $entry['customerId'];

		$aRow = array(
			'customerNumber' => $entry['customerNumber'],
			'customerDescription' => $sDescription,
			'groupNameTextId' => !empty($entry['groupNameTextId']) ? _( $entry['groupNameTextId'] ) : '',
			'userStatus' => '<span class="' . $entry['userStatus'] . '">' . ucfirst($entry['userStatus']) . '</span>',
			'userDiscount' => !empty($aDiscounts[$entry['customerUserId']]) ? $aDiscounts[$entry['customerUserId']] : 0,
			'userControls' => '
			<a href="' . $sUrlShow . '?customerId=' . $entry['customerId'] . '" class="icon iconText iconUser">' . _( 'Show' ) . '</a>
			<a href="' . $sUrlEdit . '?customerId=' . $entry['customerId'] . '" class="icon iconText iconEdit"><span>' . _( 'Edit' ) . '</span></a>
			<a href="' . $oRouter->sPath . '?' . $sUrlDelete . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this user' ) . '?"><span>' . _( 'Delete' ) . '</span></a>'
		);

		$oOutputHtmlTable->addBodyEntry( $aRow );
	}

	$sOutput = $oOutputHtmlTable->render();

} else {
	$sOutput = '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
}

$aCustomerGroups = arrayToSingle( $oCustomer->readCustomerGroup(), 'groupId', 'groupNameTextId' );

/**
 * Search form
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aDataDict, array(
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
	'groupId' => array(
		'type' => 'array',
		'title' => _( 'Customer type' ),
		'values' => array( '' => _( 'Select' ) ) + $aCustomerGroups
	),
	'page' => array(
		'type' => 'hidden',
		'value' => 0
	)
) );

echo '
	<div class="customerTable view">
		<h1>' . _( 'Customers' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminCustomerAdd' ) . '" class="icon iconText iconAdd">' . _( 'Add customer' ) . '</a>
			</div>
			<div class="tool">
				' . $oOutputHtmlForm->render() . '
			</div>
			<div class="tool">
				<a href="' . $oRouter->sPath . '?export=csv" class="icon iconText iconDbImport">' . _( 'Export users as CSV-file' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oCustomer->oDao->setCriterias( array() );
