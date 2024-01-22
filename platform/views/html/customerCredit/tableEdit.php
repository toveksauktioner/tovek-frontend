<?php

$aErr = array();

clFactory::loadClassFile( 'clOutputHtmlForm' );
clFactory::loadClassFile( 'clOutputHtmlTable' );

$oConfig = clFactory::create( 'clConfig' );
$oCustomerCredit = clRegistry::get( 'clCustomerCredit', PATH_MODULE . '/customer/models' );
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );

$aTransactionDataDict = $oCustomerCredit->oDao->aDataDict['entCustomerCreditTransaction'];

/**
 * Read config values
 */
$aConfigData = arrayToSingle( $oConfig->oDao->readData( array(
	'fields' => '*',
	'criterias' => 'configGroupKey = ' . $oConfig->oDao->oDb->escapeStr( 'CustomerCredit' )
) ), 'configKey', 'configValue' );

if( !empty($_POST['frmCreditAdd']) ) {
	$aData = current( $oCustomerCredit->readByCustomerId( $_POST['creditCustomerId'] ) );
	if( !empty($aData) ) {
		$aErr[] = _( 'Credit for the selected customer already exist' );
	}
	
	if( empty($aErr) ) {
		if( !empty($_GET['creditId']) ) {
			// Update
			$_POST['creditUpdated'] = date( 'Y-m-d H:i:s' );
			$oCustomerCredit->update( $_GET['creditId'], $_POST );
			$aErr = clErrorHandler::getValidationError( 'updateCredit' );
			$iCreditId = $_GET['creditId'];
			
		} else {
			// Create		
			$_POST['creditCreated'] = date( 'Y-m-d H:i:s' );
			$iCreditId = $oCustomerCredit->create( $_POST );
			$aErr = clErrorHandler::getValidationError( 'createCredit' );
			
		}
	}
	
	if( empty($aErr) ) {
		$oRouter->redirect( $oRouter->sPath );
	}
}

// All credits
$aAllData = valueToKey( 'creditId', $oCustomerCredit->read() );

if( !empty($_GET['creditId']) ) {
	// Edit
	$aData = $aAllData[ $_GET['creditId'] ];
	$sTitle = '';
} else {
	// New
	$aData = $_POST;
	$sTitle = '<a href="#frmCreditAdd" class="toggleShow icon iconText iconAdd">' . _( 'Create new credit' ) . '</a>';
}

$aReadFields = array(
	'customerId',
	'customerNumber',
	'customerDescription',
	'customerUserId',
	'infoName'
);

$aCustomerData = $oCustomer->read( $aReadFields );
$aCustomers = array();
if( !empty($aCustomerData) ) {
	foreach( $aCustomerData as $aCustomer ) {
		$aCustomers[ $aCustomer['customerId'] ] = !empty($aCustomer['infoName']) ? $aCustomer['infoName'] : $aCustomer['customerDescription'];
	}
}

// Datadict
$aDataDict = array(
	'creditCustomerId' => array(
		'type' => 'array',
		'values' => $aCustomers
	),
	'creditValue' => array(),
	'creditValueType' => array(),
	'creditStatus' => array()
);

// Form init
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oCustomerCredit->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inTable' ),
	'data' => $aData,
	'errors' => $aErr,
	'method' => 'post'
) );
$oOutputHtmlForm->setFormDataDict( $aDataDict + array(	
	'frmCreditAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

// Table init
$oOutputHtmlTable = new clOutputHtmlTable( $oCustomerCredit->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( $aDataDict + array(
	'creditCreated' => array(),
	'tableRowControls' => array(
		'title' => ''
	)
) );

/**
 * Form row
 */
$aAddForm = array(
	'creditCustomerId' => $oOutputHtmlForm->renderFields( 'creditCustomerId' ),
	'creditValue' => '',
	'creditValueType' => '',
	'creditStatus' => $oOutputHtmlForm->renderFields( 'creditStatus' ),
	'creditCreated' => '',
	'tableRowControls' => $oOutputHtmlForm->renderFields( 'frmCreditAdd' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
);

/**
 * Table rows
 */
foreach( $aAllData as $aEntry ) {
	if( !empty($_GET['action']) && !empty($_GET['creditId']) && $_GET['creditId'] == $aEntry['creditId'] ) {
		/**
		 * Edit
		 */
		if( $_GET['action'] == 'edit' ) {			
			$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array('creditId', 'event', 'action', 'deleteCustomerCredit') ) . '" class="icon iconText iconUndo">' . _( 'Back' ) . '</a>';
			$aAddForm['creditValue'] = $aEntry['creditValue'] . ( !empty($aConfigData['creditLabel']) ? ' ' . $aConfigData['creditLabel'] : '' );
			$aAddForm['creditValueType'] = $aEntry['creditValueType'];
			$aAddForm['creditCreated'] = substr( $aEntry['creditCreated'], 0, 16 );
			$oOutputHtmlTable->addBodyEntry( $aAddForm );
		}
		
		/**
		 * Transaction list
		 */
		if( $_GET['action'] == 'transactions' ) {
			// Data row
			$oOutputHtmlTable->addBodyEntry( array(
				'creditCustomerId' => $aCustomers[ $aEntry['creditCustomerId'] ],
				'creditValue' => $aEntry['creditValue'] . ( !empty($aConfigData['creditLabel']) ? ' ' . $aConfigData['creditLabel'] : '' ),
				'creditValueType' => $oCustomerCredit->oDao->aDataDict['entCustomerCredit']['creditValueType']['values'][ $aEntry['creditValueType'] ],			
				'creditStatus' => $oCustomerCredit->oDao->aDataDict['entCustomerCredit']['creditStatus']['values'][ $aEntry['creditStatus'] ],
				'creditCreated' => substr( $aEntry['creditCreated'], 0, 16 ),
				'tableRowControls' => '
					<a href="?action=edit&creditId=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event', 'creditId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
					&nbsp;|&nbsp;
					<a href="?action=transactions&creditId=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event', 'creditId' ) )  . '" class="icon iconText iconBars">' . _( 'Transactions' ) . '</a>
					&nbsp;|&nbsp;
					<a href="?event=deleteCustomerCredit&deleteCustomerCredit=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			), array(
				'class' => 'selected'
			) );
			
			// Transaction data
			$aTransactionData = $oCustomerCredit->readCreditTransactions( $aEntry['creditId'] );
			
			if( !empty($aTransactionData) ) {
				$sList = '';
				$sListHead = '';
				$iFirstKey = key( $aTransactionData );
				foreach( $aTransactionData as $key => $aTransaction ) {
					$sEntry = '';
					foreach( $aTransaction as $sFieldKey => $sValue ) {
						if( empty($aTransactionDataDict[$sFieldKey]['title']) ) {
							continue;
						}
						
						if( $key == $iFirstKey ) {
							$sListHead .= '
							<div class="' . $sFieldKey . '">
								' . $aTransactionDataDict[$sFieldKey]['title'] . '
							</div>';
						}
						
						if( $aTransactionDataDict[$sFieldKey]['type'] == 'array' ) {
							$sValue = $aTransactionDataDict[$sFieldKey]['values'][ $sValue ];
						}
						if( $aTransactionDataDict[$sFieldKey]['type'] == 'datetime' ) {
							$sValue = substr( $sValue, 0, 16 );
						}
						
						$sEntry .= '
							<div class="' . $sFieldKey . '">
								' . $sValue . '
							</div>';
					}
					$sList .= '
						<li>
							' . $sEntry . '
						</li>';
				}
				
				$sRowContent = '
					<ul>
						<li class="head">' . $sListHead . '</li>
						' . $sList . '
					</ul>';
				
			} else {
				$sRowContent = '<span class="empty">' . _( 'No transactions' ) . '</span>';
			}
			
			// Transaction rows
			$oOutputHtmlTable->addBodyEntry( array(
				'creditCustomerId' => array(
					'value' => $sRowContent,
					'attributes' => array(
						'colspan' => '6'
					)
				)
			), array(
				'class' => 'transactionList'
			) );
		}
		
	} else {
		// Data row
		$oOutputHtmlTable->addBodyEntry( array(
			'creditCustomerId' => $aCustomers[ $aEntry['creditCustomerId'] ],
			'creditValue' => $aEntry['creditValue'] . ( !empty($aConfigData['creditLabel']) ? ' ' . $aConfigData['creditLabel'] : '' ),
			'creditValueType' => $oCustomerCredit->oDao->aDataDict['entCustomerCredit']['creditValueType']['values'][ $aEntry['creditValueType'] ],			
			'creditStatus' => $oCustomerCredit->oDao->aDataDict['entCustomerCredit']['creditStatus']['values'][ $aEntry['creditStatus'] ],
			'creditCreated' => substr( $aEntry['creditCreated'], 0, 16 ),
			'tableRowControls' => '
				<a href="?action=edit&creditId=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event', 'creditId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?action=transactions&creditId=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event', 'creditId' ) )  . '" class="icon iconText iconBars">' . _( 'Transactions' ) . '</a>
				&nbsp;|&nbsp;
				<a href="?event=deleteCustomerCredit&deleteCustomerCredit=' . $aEntry['creditId'] . '&' . stripGetStr( array( 'deleteCustomerCredit', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		) );
	}
}

if( empty($_GET['creditId']) ) {
	// New
	$oOutputHtmlTable->addBodyEntry( $aAddForm, array(
		'id' => 'frmCreditAdd',
		'style' => 'display: table-row;'
	) );
}

$sOutput = $oOutputHtmlForm->renderForm(
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlTable->render()
);

echo '
	<div class="view customerCredits">
		<h1>' . _( 'Customer credits' ) . '</h1>
		' . $sTitle . '		
		' . $sOutput . '
	</div>';