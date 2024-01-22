<?php

// For test:
//unset( $_SESSION['unifaun'] );

$aErrPartner = array();
$aErrParcel = array();

$aPartnerSelectForm = '';
$sParcelTable = '';

/**
 * Unifaun session
 */
if( empty($_SESSION['unifaun']) ) {
	$_SESSION['unifaun'] = array(
		'parcels' => array(),
		'orderId' => !empty($_GET['orderId']) ? $_GET['orderId'] : null
	);
} elseif( !empty($_GET['orderId']) && $_GET['orderId'] != $_SESSION['unifaun']['orderId'] ) {
	$_SESSION['unifaun']['orderId'] = $_GET['orderId'];
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oNotification = clRegistry::get( 'clNotificationHandler' );

$oUnifaun = clRegistry::get( 'clUnifaun', PATH_MODULE . '/unifaun/models' );
$oUnifaunPartner = clRegistry::get( 'clUnifaunPartner', PATH_MODULE . '/unifaun/models' );

/**
 * Get country list
 */
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aCountryList = arrayToSingle( $oContinent->aHelpers['oParentChildHelper']->readChildren( null ), 'countryIsoCode2', 'countryName' );

/**
 * Determ partner & service & receiver
 */
$_SESSION['unifaun']['partnerId'] = !empty($_GET['partnerId']) ? $_GET['partnerId'] : UNIFAUN_DEFAULT_PARTNER;
$_SESSION['unifaun']['serviceId'] = !empty($_GET['serviceId']) ? $_GET['serviceId'] : UNIFAUN_DEFAULT_SERVICE;
if( !empty($_POST['frmReceiver']) ) {
	$_SESSION['unifaun']['receiver'] = $_POST;
}


/**
 * Unifaun data by order
 */
if( !empty($_SESSION['unifaun']['orderId']) ) {
	$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
	$oOrderLine = clRegistry::get( 'clOrderLine', PATH_MODULE . '/order/models' );

	// Order data
	$aOrderData = current( $oOrder->read( '*', $_SESSION['unifaun']['orderId'] ) );

	if( !empty($aOrderData) ) {
		// Order line data
		$aOrderLineData = $oOrderLine->readByOrder( $_SESSION['unifaun']['orderId'], '*' );

		if( !empty($aOrderLineData) ) {
			// Additional product data
			$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
			$aProductIds = arrayToSingle( $aOrderLineData, null, 'lineProductId' );
			$aProducts = valueToKey( 'productId', $oProduct->read('*', $aProductIds) );

			if( empty($_SESSION['unifaun']['parcels']) ) {

				// Reset parcels
				unset( $_SESSION['unifaun']['parcels'] );

				foreach( $aOrderLineData as $aLine ) {
					if( $aLine['lineProductWeight'] == 0 && array_key_exists($aLine['lineProductId'], $aProducts) ) {
						// Try to fetch product weight from fresch product data
						$aLine['lineProductWeight'] = ( $aProducts[ $aLine['lineProductId'] ]['productWeight'] * $aLine['lineProductQuantity'] );
					}
					$_SESSION['unifaun']['parcels'][ $aLine['lineId'] ] = array(
						'productId' => $aLine['lineProductId'],
						'quantity' => $aLine['lineProductQuantity'],
						'weight' => $aLine['lineProductWeight'],
						'contents' => $aLine['lineProductTitle'],
						'orderLineId' => $aLine['lineId']
					);
				}

			}
		} else {
			$aErrParcel[] = _( 'No order lines' );
		}
	}
}

/**
 * Sender and receiver data
 */
$aSender = $GLOBALS['UNIFAUN_DEFAULT_SENDER'];
if( !empty($aOrderData) ) {
	$_SESSION['unifaun']['receiver'] = array(
		'phone' => $aOrderData['orderDeliveryPhone'],
		'email' => $aOrderData['orderEmail'],
		'zipcode' => $aOrderData['orderDeliveryZipCode'],
		'name' => $aOrderData['orderDeliveryName'],
		'address1' => $aOrderData['orderDeliveryAddress'],
		'country' => 'SE',
		'city' => $aOrderData['orderDeliveryCity']
	);
}


/**
 * Partner data
 */
$sReceiverCountry = null;
if( !empty($_SESSION['unifaun']['receiver']['country']) ) {
	$sReceiverCountry = $_SESSION['unifaun']['receiver']['country'];
}
$aPartners = $oUnifaunPartner->readByCountry( null, $GLOBALS['UNIFAUN_DEFAULT_SENDER']['country'], $sReceiverCountry );

/**
 * Sync partners from CSV-file
 */
if( !empty($_GET['syncPartnerData']) ) {
    $oUnifaunPartner->syncFromCsv();
	$oNotification->setSessionNotifications( array(
		'dataSaved' => _( 'Agents has been imported' )
	) );
	$oRouter->redirect( $oRouter->sPath );

} elseif( empty($aPartners) ) {
	echo '
    <div class="view unifaun fromSend">
        <h1 class="icon iconText iconFreight">' . _( 'Unifaun export' ) . '</h1>
        <section>
			<strong>' . _( 'No agents found!' ) . '</strong> ' . _( 'Import from file' ) . ', <a href="?syncPartnerData=true">' . _( 'by clicking here' ) . '</a>.
		<section>
	</div>';
	return;
}

/**
 * Partner service data
 */
if( !empty($_SESSION['unifaun']['partnerId']) ) {
	$aPartnerServices = $oUnifaunPartner->readServiceByPartner( $_SESSION['unifaun']['partnerId'], $GLOBALS['UNIFAUN_DEFAULT_SENDER']['country'], $sReceiverCountry );
	$aPartnerServiceList = arrayToSingle( $aPartnerServices, 'serviceId', 'serviceName' );
	$aServiceIdToCode = arrayToSingle( $aPartnerServices, 'serviceId', 'serviceCode' );

	if( empty($aPartnerServiceList) ) {
		unset( $aPartners[ $_SESSION['unifaun']['partnerId'] ] );
	}

	if( !empty($_GET['serviceId']) && !array_key_exists($_GET['serviceId'], $aServiceIdToCode) ) {
		$oRouter->redirect( $oRouter->sPath . '?partnerId=' . $_SESSION['unifaun']['partnerId'] . '&serviceId=' . key($aServiceIdToCode) );
	}
}
// Alphabetical partner list
$aPartnerList = arrayToSingle( $aPartners, 'partnerId', 'partnerName' );
asort( $aPartnerList );


/**
 * Send data to Unifaun
 */
if( !empty($_POST['frmSendToUnifaun']) && !empty($_SESSION['unifaun']['receiver']) ) {
	if( empty($_SESSION['unifaun']['parcels']) ) {
		$aErrParcel[] = _( 'Missing mandatory parcels' );
	}

	if( empty($_SESSION['unifaun']['serviceId']) ) {
		$aErrParcel[] = _( 'Missing mandatory selection of service' );
	}

	foreach( $_SESSION['unifaun']['parcels'] as $aParcel ) {
		if( $aParcel['weight'] <= 0 ) {
			$aErrParcel[] = _( 'All parcels need to have a weight' );
		}
	}

	if( empty($aErrParcel) ) {
		$aShipmentData = array(
			'pdfConfig' => $GLOBALS['UNIFAUN_DEFAULT_PDF_SETTINGS'],
			'shipment' => array(
				'service' => array(
					'id' => $aServiceIdToCode[ $_SESSION['unifaun']['serviceId'] ]
				),
				'orderNo' => !empty($_SESSION['unifaun']['orderId']) ? $_SESSION['unifaun']['orderId'] : '0',
				'sender' => $aSender,
				'senderReference' => $GLOBALS['UNIFAUN_DEFAULT_SENDER_REFERENCE']
			)
		);

		if( !empty($aOrderData) ) {
			if( substr($aOrderData['orderDeliveryPhone'], 0, 1) != '+' ) {
				switch( $aOrderData['orderDeliveryCountry'] ) {
					case '210':
						$aOrderData['orderDeliveryPhone'] = '+46' . substr( $aOrderData['orderDeliveryPhone'], 1, strlen($aOrderData['orderDeliveryPhone']) );
						break;
					default:
						// Do nothing
						break;
				}
			}

			$aShipmentData['shipment'] += array(
				'receiver' => $_SESSION['unifaun']['receiver'],
				'receiverReference' => '', # receiver ref 345
				'options' => array(
					0 => array(
						'message' => $aOrderData['orderId'],
						'to' => $aOrderData['orderEmail'],
						'id' => 'ENOT',
						'languageCode' => 'SE',
						'from' => UNIFAUN_SENDER_EMAIL
					)
				)
			);
		}



		$aShipments = array();

		if( (count($_SESSION['unifaun']['parcels']) > 1) && in_array($aShipmentData['shipment']['service']['id'], $GLOBALS['UNIFAUN_SINGLE_PARCEL_SERVICES']) ) {
			// Some services only allow one parcel per shipment

			foreach( $_SESSION['unifaun']['parcels'] as $aParcel ) {
				$aThisShipment = $aShipmentData;

				$aThisShipment['shipment']['parcels'][] = array(
					'copies' => $aParcel['quantity'],
					'weight' => ( $aParcel['weight'] / 1000 ),
					'contents' => $aParcel['contents'],
					'valuePerParcel' => true
				);
				$aThisShipment['shipment']['totalWeight'] = ( $aParcel['weight'] / 1000 );

				$aShipments[] = $aThisShipment;
			}

		} else {
			// Sevices that allow multiple parcels per shipment

			$fTotalWeight = 0;
			foreach( $_SESSION['unifaun']['parcels'] as $aParcel ) {
				$aShipmentData['shipment']['parcels'][] = array(
					'copies' => $aParcel['quantity'],
					'weight' => ( $aParcel['weight'] / 1000 ),
					'contents' => $aParcel['contents'],
					'valuePerParcel' => true
				);

				$fTotalWeight += ( $aParcel['weight'] / 1000 );
			}
			$aShipmentData['shipment']['totalWeight'] = $fTotalWeight;

			$aShipments[] = $aShipmentData;

		}

		// if( !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '213.88.134.199' ) {
		// 	echo '<pre>';
		// 	var_dump( $aShipmentData );
		// 	die;
		// }

		foreach( $aShipments as $aShipmentData ) {

			// Check if there are mandatory fields for this service and populate data
			if( !empty($GLOBALS['UNIFAUN_SERVICE_MANDATORY'][ $aShipmentData['shipment']['service']['id'] ]) ) {
				foreach( $GLOBALS['UNIFAUN_SERVICE_MANDATORY'][ $aShipmentData['shipment']['service']['id'] ] as $sRef => $aMandatory ) {
					$aRef = explode( '.', $sRef );

					$aThisData = &$aShipmentData;
					foreach( $aRef as $sRefName ) {
						$aThisData = &$aThisData[ $sRefName ];
					}

					if( $aMandatory['scope'] == 'multi' ) {

						foreach( $aThisData as $key => $aSubData ) {
							foreach( $aMandatory['fields'] as $sMandatoryField => $aMandatoryFieldData ) {
								switch( $aMandatoryFieldData['type'] ) {
									case 'fixedValue':
										$aThisData[ $key ][ $sMandatoryField ] = $aMandatoryFieldData['value'];
										break;
								}
							}
						}

					} else {

						foreach( $aMandatory['fields'] as $sMandatoryField => $aMandatoryFieldData ) {
							switch( $aMandatoryFieldData['type'] ) {
								case 'fixedValue':
									$aThisData[ $sMandatoryField ] = $aMandatoryFieldData['value'];
									break;
							}
						}

					}

				}
			}

			if( !empty($GLOBALS['UNIFAUN_SERVICE_ADDONS'][ $aShipmentData['shipment']['service']['id'] ]) ) {
				foreach( $GLOBALS['UNIFAUN_SERVICE_ADDONS'][ $aShipmentData['shipment']['service']['id'] ] as $sAddonId => $aAddonData ) {
					$aAddonParams = array(
						'id' => $sAddonId
					);

					foreach( $aAddonData as $sAddonFieldName => $sAddonFieldReference ) {
						$aAddonParams[ $sAddonFieldName ] = $aOrderData[ $sAddonFieldReference ];
					}

					$aShipmentData['shipment']['service']['addons'] = $aAddonParams;
				}
			}

			$aResult = $oUnifaun->createShipment( $aShipmentData );
		}
	}
}

/**
 * Add parcel
 */
if( !empty($_POST['frmAddParcel']) ) {
	if( empty($_SESSION['unifaun']['parcels']) ) {
		$_SESSION['unifaun']['parcels'] = array();
	}
	if( !empty($_GET['parcelKey']) ) {
		// Update
		$_SESSION['unifaun']['parcels'][ $_GET['parcelKey'] ] = array(
			'quantity' => $_POST['quantity'],
			'weight' => $_POST['weight'],
			'contents' => $_POST['contents'],
			'orderLineId' => null
		);
	} else {
		// Create
		$_SESSION['unifaun']['parcels'][] = array(
			'quantity' => $_POST['quantity'],
			'weight' => $_POST['weight'],
			'contents' => $_POST['contents'],
			'orderLineId' => null
		);
	}
	$_POST = array();
	unset( $_GET['parcelKey'] );
}

/**
 * Remove parcel
 */
if( isset($_GET['removeParcel']) && ctype_digit($_GET['removeParcel']) ) {
	unset( $_SESSION['unifaun']['parcels'][ $_GET['removeParcel'] ] );
}

/**
 * Partner select form
 */
$aDataDict = array(
    'entUnifaunSend' => array(
        'partnerId' => array(
            'type' => 'array',
            'values' => array( '' => _( 'Select' ) ) + $aPartnerList,
            'title' => _( 'Agent' ),
            'attributes' => array(
				'onchange' => 'this.form.submit();'
			)
        ),
        'serviceId' => array(
            'type' => 'array',
            'values' => !empty($aPartnerServiceList) ? $aPartnerServiceList : array( '' => _( 'Select agent first' ) ),
            'title' => _( 'Service' ),
            'attributes' => array(
				'onchange' => 'this.form.submit();'
			)
        )
    )
);
$oOutputHtmlForm->init( $aDataDict, array(
	'data' => $_SESSION['unifaun'],
	'errors' => $aErrPartner,
	'labelSuffix' => ':',
	'buttons' => array(
		'submitSelect' => array(
			'content' => empty($_SESSION['unifaun']['serviceId']) ? _( 'Continue' ) : _( 'Update' ),
			'attributes' => array(
				'name' => 'submitSelect',
				'type' => 'submit'
			)
		)
	)
) );
$oOutputHtmlForm->setFormDataDict( current($aDataDict) );

$sPartnerSelectForm = $oOutputHtmlForm->createForm( 'get', $oRouter->sPath,
	$oOutputHtmlForm->renderErrors() .
	$oOutputHtmlForm->renderFields() .
	$oOutputHtmlForm->renderButtons(),
	array( 'class' => 'marginal' )
);

/**
 * Parcel table
 */
if( !empty($_SESSION['unifaun']['serviceId']) ) {
	if( isset($_GET['parcelKey']) && !empty($_SESSION['unifaun']['parcels'][ $_GET['parcelKey'] ]) ) {
		$_POST = $_SESSION['unifaun']['parcels'][ $_GET['parcelKey'] ];
	}

    // Form init
    $aDataDict = array(
        'entUnifaunParcel' => array(
            'quantity' => array(
                'type' => 'string',
                'title' => _( 'Quantity' )
            ),
            'weight' => array(
                'type' => 'string',
                'title' => _( 'Weight' )
            ),
            'contents' => array(
                'type' => 'string',
                'title' => _( 'Contents' )
            )
        )
    );
    $oOutputHtmlForm->init( $aDataDict, array(
        'action' => '',
        'attributes' => array( 'class' => 'inTable' ),
        'data' => $_POST,
        'errors' => $aErrParcel,
        'method' => 'post'
    ) );
    $oOutputHtmlForm->setFormDataDict( current($aDataDict) + array(
        'frmAddParcel' => array(
            'type' => 'hidden',
            'value' => true
        )
    ) );

    // Table init
    clFactory::loadClassFile( 'clOutputHtmlTable' );
    $oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
    $oOutputHtmlTable->setTableDataDict( current($aDataDict) + array(
        'tableRowControls' => array(
            'title' => ''
        )
    ) );

    $aAddForm = array(
        'quantity' => $oOutputHtmlForm->renderFields( 'quantity' ),
        'weight' => $oOutputHtmlForm->renderFields( 'weight' ),
        'contents' => $oOutputHtmlForm->renderFields( 'contents' ),
        'tableRowControls' => $oOutputHtmlForm->renderFields( 'frmAddParcel' ) . $oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
    );

    if( !empty($_SESSION['unifaun']['parcels']) ) {
        foreach( $_SESSION['unifaun']['parcels'] as $iParcelKey => $aEntry ) {
			if( isset($_GET['parcelKey']) && $_GET['parcelKey'] == $iParcelKey ) {
				// Edit
				$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array( 'removeParcel', 'event', 'parcelKey' ) ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
				$oOutputHtmlTable->addBodyEntry( $aAddForm );
			} else {
				// Data row
				$aEntry['weight'] .= ' gram';
				$aEntry['tableRowControls'] = '
					<a href="?parcelKey=' . $iParcelKey . '&' . stripGetStr( array( 'removeParcel', 'event', 'parcelKey' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
					<a href="?removeParcel=' . $iParcelKey . '&' . stripGetStr( array( 'removeParcel', 'event' ) ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>';
				$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aEntry ) );
			}
        }
    }

	if( empty($_GET['parcelKey']) ) {
        // New
        $oOutputHtmlTable->addBodyEntry( $aAddForm, array(
            'id' => 'frmAddParcel',
            'style' => 'display: table-row;'
        ) );
    }

    $sParcelTable = $oOutputHtmlForm->renderForm(
        $oOutputHtmlForm->renderErrors() .
        $oOutputHtmlTable->render()
    );
}

$sSendForm = $oOutputHtmlForm->createForm( 'post', $oRouter->sPath . '?' . stripGetStr( array( 'removeParcel', 'event', 'parcelKey' ) ),
	'<div class="hidden">' . $oOutputHtmlForm->createInput( 'hidden', 'frmSendToUnifaun', array('value' => true) ) . '</div>' .
	$oOutputHtmlForm->createButton( 'submit', _( 'Send to Unifaun' ) ) .
	'<p>(' . sprintf( _( '%s parcels added' ), count($_SESSION['unifaun']['parcels']) ) . ')</p>'
);


/**
 * Receiver form (or just presentation of the receiver)
 */
if( !empty($_SESSION['unifaun']['orderId']) && !empty($_SESSION['unifaun']['receiver']) ) {
	$sReceiverForm = '
		<h4>' . $_SESSION['unifaun']['receiver']['name'] . '</h4>
		<p class="firstInGroup">' . $_SESSION['unifaun']['receiver']['address1'] . '</p>
		<p class="lastInGroup">' . $_SESSION['unifaun']['receiver']['zipcode'] . ' ' . $_SESSION['unifaun']['receiver']['city'] . ' (' . $_SESSION['unifaun']['receiver']['country'] . ')</p>
		' . ( !empty($_SESSION['unifaun']['receiver']['email']) ? '<p>' . $_SESSION['unifaun']['receiver']['email'] . '</p>' : '' ) . '
		' . ( !empty($_SESSION['unifaun']['receiver']['phone']) ? '<p>' . $_SESSION['unifaun']['receiver']['phone'] . '</p>' : '' );

} else {
	$aReceiverDataDict = array(
	  'entReceiver' => array(
      'name' => array(
        'type' => 'string',
        'title' => _( 'Name' ),
				'required' => true
      ),
      'address1' => array(
        'type' => 'string',
        'title' => _( 'Address' ),
				'required' => true
      ),
      'zipcode' => array(
        'type' => 'string',
        'title' => _( 'Zip code' ),
				'required' => true
      ),
      'city' => array(
        'type' => 'string',
        'title' => _( 'City' ),
				'required' => true
      ),
      'country' => array(
        'type' => 'array',
				'values' => $aCountryList,
				'defaultValue' => 'SE',
        'title' => _( 'Country' ),
				'required' => true
      ),
      'email' => array(
        'type' => 'string',
        'title' => _( 'Email' )
      ),
      'phone' => array(
        'type' => 'string',
        'title' => _( 'Phone' )
      ),
			'frmReceiver' => array(
				'type' => 'hidden',
				'value' => true
			)
	  )
	);
	$oOutputHtmlForm->init( $aReceiverDataDict, array(
		'method' => 'post',
		'data' => ( !empty($_SESSION['unifaun']['receiver']) ? $_SESSION['unifaun']['receiver'] : null ),
		'labelSuffix' => ':',
		'attributes' => array(
			'class' => 'marginal'
		),
		'jsValidation' => true,
		'buttons' => array(
			'submit' => _( 'Save' )
		)
	) );
	$oOutputHtmlForm->setFormDataDict( current($aReceiverDataDict) );

	$sReceiverForm = $oOutputHtmlForm->render();
}


/**
 * The partner, service and parcel form is only visible if there is a valid receiver
 */
$sPartnerServiceAndParcelForm = '';
if( !empty($_SESSION['unifaun']['receiver']) ) {
	// Only show the rest of the form if there is receiver data
	$sPartnerServiceAndParcelForm .= '
		<section>
			<h3>' . _( 'Transporter' ) . '</h3>
			' . $sPartnerSelectForm . '
		</section>';

	if( !empty($_SESSION['unifaun']['serviceId']) ) {
		$sPartnerServiceAndParcelForm .= '
			<section>
				<h3>' . _( 'Parcels' ) . '</h3>
				<a href="' . $oRouter->sPath . '' . (!empty($_GET) ? '?' . stripGetStr(array('parcelKey')) : '') . '#frmAddParcel" class="toggleShow icon iconText iconAdd">' . _( 'Add parcel' ) . '</a>
				' . $sParcelTable . '
			</section>';
	}
 	if( !empty($_SESSION['unifaun']['parcels']) ) {
		$sPartnerServiceAndParcelForm .= '
			<section>
				<h3>' . _( 'Send data to Unifaun' ) . '</h3>
				' . $sSendForm . '
			</section>';
	}
}

echo '
    <div class="view unifaun formSend">
      <h1 class="icon iconText iconFreight">' . _( 'Unifaun export' ) . '</h1>
			<section class="col-50">
				<h3>' . _( 'From' ) . '</h3>
				<hr>
				<h4>' . $GLOBALS['UNIFAUN_DEFAULT_SENDER_REFERENCE'] . '</h4>
				<p class="firstInGroup">' . $aSender['address1'] . '</p>
				<p class="lastInGroup">' . $aSender['zipcode'] . ' ' . $aSender['city'] . ' (' . $aSender['country'] . ')</p>
				' . ( !empty($aSender['email']) ? '<p>' . $aSender['email'] . '</p>' : '' ) . '
				' . ( !empty($aSender['phone']) ? '<p>' . $aSender['phone'] . '</p>' : '' ) . '
			</section>
			<section class="col-50">
				<h3>' . _( 'To' ) . '</h3>
				<hr>
				' . $sReceiverForm . '
			</section>
			' . $sPartnerServiceAndParcelForm . '
    </div>';

$oTemplate->addBottom( array(
	'key' => 'unifaunJs',
	'content' => '
		<script>
			$(".buttons button[name=\"submitSelect\"]").hide();
		</script>
	'
) );
