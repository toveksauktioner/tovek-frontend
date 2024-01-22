<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/unifaun/config/cfUnifaun.php';

class clUnifaun extends clModuleBase {

	private $sSession = null;

	public function __construct() {
		$this->sModulePrefix = 'unifaun';

		$this->oDao = clRegistry::get( 'clUnifaunDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/unifaun/models' );
		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	/**
	 * Fetches the closest distribution points for a specific postcode
	 * @param string $sType Distributer code
	 * @param string $sZip The postcode for the distribution point. The postcode of the recipient address is normally used.
	 * @param string $sCountryCode ISO 3166-1 alpha 2 country code, indicated using two capital letters
	 * @return array
	 */
	public function agentLookup( $sType = '', $sZip = '', $sCountryCode = '' ) {
		if( empty($this->sSession) ) {
			$this->auth();
			if( empty($this->sSession) ) {
				return false;
			}
		}

		$oSoapClient = new SoapClient('https://www.unifaunonline.com/ws-extapi2/AgentLookup2?wsdl', array(
			'exceptions' => false
		) );
		$mResponse = $oSoapClient->LookupAgents1( array(
			'session' => $this->sSession,
			'type' => $sType,
			//'street' => '' // Currently not in use by Unifaun
			'zip' => $sZip,
			'countryCode' => $sCountryCode
		) );

		if( is_soap_fault($mResponse) ) {
			// SOAP returned a fault
			$this->createLog( array(
				'unifaunLogTitle' => 'Agent Lookup - ' . $mResponse->getMessage(),
				'unifaunLogMessage' => $mResponse->__toString(),
				'unifaunLogType' => 'failiure'
			) );
			return false;
		} else {
			// SOAP call is ok, parse response
			$aDistributionPoints = array();
			foreach( $mResponse->return as $aDistributionPoint ) {
				$aDistributionPoints[] = (array) $aDistributionPoint;
			}
			return $aDistributionPoints;
		}
	}

	/**
	 * Creates a session for usage in other Unifaun methods
	 * @return boolean
	 */
	public function auth() {
		$oSoapClient = new SoapClient( 'https://www.unifaunonline.com/ws-extapi2/Authentication2?wsdl', array(
			'exceptions' => false
		) );
		$mResponse = $oSoapClient->Login1( array(
			'userId' => UNIFAUN_ID,
			'pass' => UNIFAUN_PASSWORD,
			'developerId' => UNIFAUN_DEVELOPER_ID
		) );

		if( is_soap_fault($mResponse) ) {
			// SOAP returned a fault
			$this->createLog( array(
				'unifaunLogTitle' => 'Auth - ' . $mResponse->getMessage(),
				'unifaunLogMessage' => $mResponse->__toString(),
				'unifaunLogType' => 'failiure'
			) );
			return false;
		} else {
			// SOAP call is ok, parse response
			$this->sSession = $mResponse->return;
			return true;
		}
	}

	private function createLog( $aData = array() ) {
		if( empty($aData) ) return false;

		if( array_key_exists('unifaunLogType', $aData) && $aData['unifaunLogType'] == 'failiure' ) {
			// Send mail to administrator about this error
			$oMail = clFactory::create( 'clMail' );
			$oMail->setFrom( SITE_MAIL_FROM )
				  ->addTo( array('renfors@argonova.se') )
				  ->setSubject( _('Unifaun error') )
				  ->setBodyHtml( '<pre>' . var_export($aData, true) . '</pre><br />' . date('Y-m-d H:i:s') . ' / ' . SITE_DOMAIN )
				  ->send();

			echo '<pre>';
			var_dump($aData);
			die();
		}


		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'LogCreated'] = date( 'Y-m-d H:i:s' );
		if( $this->oDao->createLog($aData, $aParams) ) {
			return $this->oDao->oDb->lastId();
		}
		return false;
	}

	/**
	 * Creates XML for usage in Unifaun Online
	 * @param $aUnifaunOrderData
	 * @param $aInvoiceData
	 *
	 * @return string Returns Unifaun order XML
	 */
	public function createOrderXml( $aUnifaunOrderData = array(), $aInvoiceData = array(), $aParams = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($aUnifaunOrderData) || empty($aInvoiceData) ) {
			return false;
		}

		if( !empty($aUnifaunOrderData['email']) ) {
			$aParams['enot'] = 'YES';
		} else {
			$aParams['enot'] = 'NO';
		}

		// Default sender id is 1 (Toveks Auktioner) - In freight request another sender can be set
		$sSenderId = (string)( !empty($aUnifaunOrderData['requestSenderId']) ? $aUnifaunOrderData['requestSenderId'] : 1 );

		// Get invoice data
		$oInvoiceEngine = clRegistry::get( 'clInvoiceEngine', PATH_MODULE . '/invoice/models' );
		$aInvoicesData = $oInvoiceEngine->readByFreightRequest_in_Invoice( $aInvoiceData['invoiceFreightRequestId'], array(
			'invoiceId',
			'invoiceNo'
		) );
		$aInvoices = arrayToSingle( $aInvoicesData, null, 'invoiceId' );
		$aInvoiceNo = arrayToSingle( $aInvoicesData, null, 'invoiceNo' );

		if( isset($aUnifaunOrderData['requestTransportService'])) {
			$aUnifaunOrderData['service'] = $aUnifaunOrderData['requestTransportService'];
			unset( $aUnifaunOrderData['requestTransportService'] );
		}

		$aUnifaunOrderData += array(
			'autoprint' => 'YES',
			'sms' => '',
			'service' => null,
			'parcels' => array()
		);

		if(isset($aUnifaunOrderData['requestParcelSize']) &&  isset($aUnifaunOrderData['requestParcelCount']) && isset($aUnifaunOrderData['requestParcelWeight']) && isset($aUnifaunOrderData['requestParcelContents']) ) {

			switch( $aUnifaunOrderData['requestParcelSize'] ) {
				case 'parcel':
					$sPackageCode = 'PC';
					break;

				case 'pallet':
					$sPackageCode = 'PE';
					break;

				case 'halfpallet':
					$sPackageCode = 'AF';
					break;

				case 'specialpallet':
					$sPackageCode = 'OF';
					break;

				default:
					$sPackageCode = '';
			}

			$aUnifaunOrderData['parcels'] = array(
				array(
					'packagecode' => $sPackageCode,
					'copies' => $aUnifaunOrderData['requestParcelCount'],
					'weight' => $aUnifaunOrderData['requestParcelWeight'],
					'contents' => $aUnifaunOrderData['requestParcelContents']
				)
			);
		}

		// Some validation
		if( empty($aUnifaunOrderData['service']) ) {
			// TODO Show errors
			return false;
		}

		// Add addons for notification
		if( !empty($aUnifaunOrderData['notification']) ) {

			$aServiceNotifications = $GLOBALS['unifaun']['serviceToNotification'][ $aUnifaunOrderData['service'] ];

			switch( $aUnifaunOrderData['notification'] ) {
				case 'enot':
					if( !empty($aServiceNotifications['enot']) ) {
						foreach( $aServiceNotifications['enot'] as $sAdnId => $sAddonKey ) {
							$aUnifaunOrderData['addon'][$sAdnId] = array();

							if( !empty($sAddonKey) ) {
								$aUnifaunOrderData['addon'][$sAdnId][$sAddonKey] = $aUnifaunOrderData['email'];
							}
						}
					}
				break;

				case 'letter':
					if( !empty($aServiceNotifications['letter']) ) {
						foreach( $aServiceNotifications['letter'] as $sAdnId => $sAddonKey ) {
							$aUnifaunOrderData['addon'][$sAdnId] = array();
						}
					}
				break;

				case 'sms':
				default:
					if( !empty($aServiceNotifications['sms']) ) {
						foreach( $aServiceNotifications['sms'] as $sAdnId => $sAddonKey ) {
							$aUnifaunOrderData['addon'][$sAdnId] = array();

							if( !empty($sAddonKey) ) {
								$aUnifaunOrderData['addon'][$sAdnId][$sAddonKey] = $aUnifaunOrderData['phone'];
							}
						}
					}
			}
		}


		// Read the correct country flag
		$oCountry = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models/' );
		$aCountryData = $oCountry->aHelpers['oParentChildHelper']->readChildren( null, array('countryIsoCode2'), $aUnifaunOrderData['country'] );

		$oXml = new DOMDocument( '1.0', 'UTF-8' );

		// For debugging
		$oXml->formatOutput = true;

		$oElementUnifaunOnline = $oXml->createElement('unifaunonline');
		$oXml->appendChild($oElementUnifaunOnline);

		/**
		 * Use DOMDocument::createTextNode to create entries with escaping support
		 */


		/*
		 * Start Meta (one block)
		 *
		 */
		$oElementMeta = $oXml->createElement('meta');

		$oMetaAutoprint = $oXml->createElement('val');
		$oMetaAutoprint->setAttribute( 'n', 'autoprint' );
		$oMetaAutoprint->appendChild($oXml->createTextNode($aUnifaunOrderData['autoprint']));
		$oElementMeta->appendChild( $oMetaAutoprint );

		$oMetaCreated = $oXml->createElement('val');
		$oMetaCreated->setAttribute( 'n', 'created' );
		$oMetaCreated->appendChild($oXml->createTextNode(date('Y-m-d H:i')));
		$oElementMeta->appendChild( $oMetaCreated );

		$oElementUnifaunOnline->appendChild($oElementMeta);
		/*
		 * End Meta
		 *
		 */


		/*
		 * Start Sender (zero, one or more blocks)
		 *
		 */
		/*$oElementSender = $oXml->createElement('sender');
		$oElementSender->setAttribute( 'sndid', "1" );

		$oSenderName = $oXml->createElement('val');
		$oSenderName->setAttribute( 'n', 'name' );
		$oSenderName->appendChild($oXml->createTextNode('Visiondesk i Sverige AB (Argonova Systems)'));
		$oElementSender->appendChild( $oSenderName );

		$oSenderAddress1 = $oXml->createElement('val');
		$oSenderAddress1->setAttribute( 'n', 'address1' );
		$oSenderAddress1->appendChild($oXml->createTextNode('Albanoliden 5'));
		$oElementSender->appendChild( $oSenderAddress1 );

		$oSenderZipcode = $oXml->createElement('val');
		$oSenderZipcode->setAttribute( 'n', 'zipcode' );
		$oSenderZipcode->appendChild($oXml->createTextNode('50630'));
		$oElementSender->appendChild( $oSenderZipcode );

		$oSenderCity = $oXml->createElement('val');
		$oSenderCity->setAttribute( 'n', 'city' );
		$oSenderCity->appendChild($oXml->createTextNode('BORÅS'));
		$oElementSender->appendChild( $oSenderCity );

		$oSenderCountry= $oXml->createElement('val');
		$oSenderCountry->setAttribute( 'n', 'country' );
		$oSenderCountry->appendChild($oXml->createTextNode('SE'));
		$oElementSender->appendChild( $oSenderCountry );

		$oElementUnifaunOnline->appendChild($oElementSender);*/
		/*
		 * End Sender
		 *
		 */

		/*
		 * Start Receiver (zero, one or more blocks)
		 *
		 */
		$oElementReceiver = $oXml->createElement('receiver');
		$oElementReceiver->setAttribute( 'rcvid', $aInvoiceData['invoiceUserId'] );

		$oRecieverName = $oXml->createElement('val');
		$oRecieverName->setAttribute( 'n', 'name' );
		$oRecieverName->appendChild($oXml->createTextNode($aUnifaunOrderData['name']));
		$oElementReceiver->appendChild( $oRecieverName );

		$oRecieverAddress1 = $oXml->createElement('val');
		$oRecieverAddress1->setAttribute( 'n', 'address1' );
		$oRecieverAddress1->appendChild($oXml->createTextNode($aUnifaunOrderData['address1']));
		$oElementReceiver->appendChild( $oRecieverAddress1 );

		//$oRecieverAddress2 = $oXml->createElement('val');
		//$oRecieverAddress2->setAttribute( 'n', 'address2' );
		//$oRecieverAddress2->appendChild($oXml->createTextNode('address2'));
		//$oElementReceiver->appendChild( $oRecieverAddress2 );

		$oRecieverZipcode = $oXml->createElement('val');
		$oRecieverZipcode->setAttribute( 'n', 'zipcode' );
		$oRecieverZipcode->appendChild($oXml->createTextNode( str_replace( array( ' ', '-', '.' ), '', $aUnifaunOrderData['zipcode'])) );
		$oElementReceiver->appendChild( $oRecieverZipcode );

		$oRecieverCity = $oXml->createElement('val');
		$oRecieverCity->setAttribute( 'n', 'city' );
		$oRecieverCity->appendChild($oXml->createTextNode($aUnifaunOrderData['city']));
		$oElementReceiver->appendChild( $oRecieverCity );

		$oRecieverCountry= $oXml->createElement('val');
		$oRecieverCountry->setAttribute( 'n', 'country' );
		$oRecieverCountry->appendChild($oXml->createTextNode($aUnifaunOrderData['country']));
		$oElementReceiver->appendChild( $oRecieverCountry );

		$oRecieverContact = $oXml->createElement('val');
		$oRecieverContact->setAttribute( 'n', 'contact' );
		$oRecieverContact->appendChild($oXml->createTextNode($aUnifaunOrderData['contact']));
		$oElementReceiver->appendChild( $oRecieverContact );

		$oRecieverPhone = $oXml->createElement('val');
		$oRecieverPhone->setAttribute( 'n', 'phone' );
		$oRecieverPhone->appendChild($oXml->createTextNode($aUnifaunOrderData['phone']));
		$oElementReceiver->appendChild( $oRecieverPhone );

		//$oRecieverFax = $oXml->createElement('val');
		//$oRecieverFax->setAttribute( 'n', 'fax' );
		//$oRecieverFax->appendChild($oXml->createTextNode('fax'));
		//$oElementReceiver->appendChild( $oRecieverFax );

		$oRecieverEmail = $oXml->createElement('val');
		$oRecieverEmail->setAttribute( 'n', 'email' );
		$oRecieverEmail->appendChild($oXml->createTextNode($aUnifaunOrderData['email']));
		$oElementReceiver->appendChild( $oRecieverEmail );

		/*if( !empty($aUnifaunOrderData['sms']) ) {
			$oRecieverSms = $oXml->createElement('val');
			$oRecieverSms->setAttribute( 'n', 'sms' );
			$oRecieverSms->appendChild($oXml->createTextNode($aUnifaunOrderData['sms']));
			$oElementReceiver->appendChild( $oRecieverSms );
		}*/

		$oElementUnifaunOnline->appendChild($oElementReceiver);
		/*
		 * End Receiver
		 *
		 */


		/*
		 * Start Shipment (one or more blocks)
		 *
		 */
		$oElementShipment = $oXml->createElement('shipment');
		$oElementShipment->setAttribute( 'orderno', 'req-' . $aUnifaunOrderData['requestId'] );

		$oShipmentFrom = $oXml->createElement('val');
		$oShipmentFrom->setAttribute( 'n', 'from' );
		$oShipmentFrom->appendChild($oXml->createTextNode($sSenderId));	// 1 - standard sender...
		$oElementShipment->appendChild( $oShipmentFrom );

		$oShipmentTo = $oXml->createElement('val');
		$oShipmentTo->setAttribute( 'n', 'to' );
		$oShipmentTo->appendChild( $oXml->createTextNode($aInvoiceData['invoiceUserId']) );
		$oElementShipment->appendChild( $oShipmentTo );

		$oShipmentTo = $oXml->createElement('val');
		$oShipmentTo->setAttribute( 'n', 'reference' );
		$oShipmentTo->appendChild( $oXml->createTextNode(implode('/', $aInvoiceNo)) );
		$oElementShipment->appendChild( $oShipmentTo );

		/*
		 * Start service
		 *
		 */
		$oShipmentService = $oXml->createElement('service');
		$oShipmentService->setAttribute( 'srvid', $aUnifaunOrderData['service'] );

		if( !empty($aUnifaunOrderData['addon']) && is_array($aUnifaunOrderData['addon']) ) {
			foreach( $aUnifaunOrderData['addon'] as $sAdnId => $aAddonData ) {
				$oShipmentAddon = $oXml->createElement('addon');
				$oShipmentAddon->setAttribute( 'adnid', $sAdnId );

				if( !empty($aAddonData) ) {
					foreach( $aAddonData as $sAddonKey => $sAddonValue ) {
						$oShipmentAddonValue = $oXml->createElement('val');
						$oShipmentAddonValue->setAttribute( 'n', $sAddonKey );
						$oShipmentAddonValue->appendChild($oXml->createTextNode($sAddonValue));
						$oShipmentAddon->appendChild( $oShipmentAddonValue );
					}
				}

				$oShipmentService->appendChild( $oShipmentAddon );
			}
		}

		$oElementShipment->appendChild( $oShipmentService );
		/*
		 * End service
		 *
		 */


		/*
		 * Start container
		 *
		 */
		foreach( $aUnifaunOrderData['parcels'] as $iKey => $aValues ) {
			$oShipmentContainer = $oXml->createElement('container');
			$oShipmentContainer->setAttribute( 'type', 'parcel' );
			foreach( $aValues as $sKey => $sValue ) {
				$oShipmentContainerTmp = $oXml->createElement('val');
				$oShipmentContainerTmp->setAttribute( 'n', $sKey );
				$oShipmentContainerTmp->appendChild( $oXml->createTextNode($sValue) );
				$oShipmentContainer->appendChild( $oShipmentContainerTmp );
			}
			$oElementShipment->appendChild( $oShipmentContainer );
		}
		/*
		 * End container
		 *
		 */

		if( $aParams['enot'] == 'YES' ) {
			$oShipmentUfonline = $oXml->createElement('ufonline');
			$oShipmentUfonlineOptionEnot = $oXml->createElement('option');
			$oShipmentUfonlineOptionEnot->setAttribute( 'optid', 'enot' );

			$oShipmentUfonlineOptionEnotFrom = $oXml->createElement('val');
			$oShipmentUfonlineOptionEnotFrom->setAttribute( 'n', 'from' );
			$oShipmentUfonlineOptionEnotFrom->appendChild($oXml->createTextNode(SITE_MAIL_FROM));
			$oShipmentUfonlineOptionEnot->appendChild($oShipmentUfonlineOptionEnotFrom);

			$oShipmentUfonlineOptionEnotTo = $oXml->createElement('val');
			$oShipmentUfonlineOptionEnotTo->setAttribute( 'n', 'to' );
			$oShipmentUfonlineOptionEnotTo->appendChild($oXml->createTextNode($aUnifaunOrderData['email']));
			$oShipmentUfonlineOptionEnot->appendChild($oShipmentUfonlineOptionEnotTo);

			$oShipmentUfonlineOptionEnotErrorto = $oXml->createElement('val');
			$oShipmentUfonlineOptionEnotErrorto->setAttribute( 'n', 'errorto' );
			$oShipmentUfonlineOptionEnotErrorto->appendChild($oXml->createTextNode(SITE_MAIL_TO));
			$oShipmentUfonlineOptionEnot->appendChild($oShipmentUfonlineOptionEnotErrorto);

			$oShipmentUfonline->appendChild($oShipmentUfonlineOptionEnot);
			$oElementShipment->appendChild( $oShipmentUfonline );
		}

		$oElementUnifaunOnline->appendChild($oElementShipment);
		/*
		 * End Shipment
		 *
		 */

		//header("Content-Type: text/xml");
		//echo $oXml->saveXML();
		//die;

		// return $oXml->saveXML();

		// Create dir for XML files if it doesn't exist
		if( !is_dir(UNIFAUN_EXPORT_PATH) ) {
			if( !mkdir(UNIFAUN_EXPORT_PATH, UNIFAUN_XML_CHMOD_DEFAULT) ) throw new Exception( sprintf(_('Could not create directory %s'), UNIFAUN_EXPORT_PATH) );
		}

		return $oXml->save( UNIFAUN_EXPORT_PATH . '/' . implode( '_', $aInvoiceNo ) . '.xml' );
	}

	public function readByInvoice( $iInvoiceId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( empty($iInvoiceId) ) return false;
		$aParams = array(
			'fields' => $aFields,
			'invoiceId' => $iInvoiceId
		);
		return $this->oDao->readData( $aParams );
	}

	/**
	 * Sends the provided XML to Unifaun
	 * @param string $sXml
	 *
	 * @return boolean
	 */
	public function sendToUnifaun( $sXml = '' ) {
		if( empty($sXml) ) return false;

		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_URL, UNIFAUN_POST_URL
			. '?session=' . UNIFAUN_SESSION
			. '&developerid=' . UNIFAUN_DEVELOPER_ID
			. '&user=' . UNIFAUN_ID
			. '&pin=' . UNIFAUN_PASSWORD
			. '&type=' . 'xml'
		);

		curl_setopt( $oCurl, CURLOPT_POST, true );
		curl_setopt( $oCurl, CURLOPT_HTTPHEADER, array( 'Content-Type: text/xml') );
		curl_setopt( $oCurl, CURLOPT_POSTFIELDS, $sXml );
		curl_setopt( $oCurl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $oCurl, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $oCurl, CURLOPT_TIMEOUT, 20 );

		$sResult = curl_exec( $oCurl );
		if( $sResult === false ) {
			// cURL failure, log
			$this->createLog( array(
				'unifaunLogTitle' => 'Send to Unifaun - cURL error',
				'unifaunLogMessage' => curl_errno( $oCurl ) . ' - ' . curl_error( $oCurl ),
				'unifaunLogType' => 'failiure'
			) );
			return false;
		} else {
			// cURL success
			if( !empty($sResult) ) {
				$oXml = new DOMDocument();
				if( $oXml->loadXml($sResult) ) {
					// Check status of response and message
					$iStatus = null;
					$sMessage = null;

					$oXpath = new DomXpath($oXml);
					foreach ($oXpath->query('//val[@n="status"]') as $oRowNode) {
						$iStatus = $oRowNode->nodeValue;
						break;
					}

					foreach ($oXpath->query('//val[@n="message"]') as $oRowNode) {
						$sMessage = $oRowNode->nodeValue;
						break;
					}

					if( $iStatus !== null ) {
						switch( $iStatus ) {
							case '201': // 201 - Created
									// Created does not mean that the order can be printed, it may need to be completed in Unifaun Online
									$this->createLog( array(
										'unifaunLogTitle' => 'Send to Unifaun - Success',
										'unifaunLogMessage' => $sXml,
										'unifaunLogType' => 'success'
									) );
									return true;
								break;

							case '403': // 403 - Unsuccessful login
									$this->createLog( array(
										'unifaunLogTitle' => 'Send to Unifaun - ' . $sMessage,
										'unifaunLogMessage' => $iStatus . ' - ' . $sMessage,
										'unifaunLogType' => 'failiure'
									) );
									return false;
								break;

							case '500': // 500 - The order file was not interpreted correctly
									$this->createLog( array(
										'unifaunLogTitle' => 'Send to Unifaun - ' . $sMessage,
										'unifaunLogMessage' => $iStatus . ' - ' . $sMessage,
										'unifaunLogType' => 'failiure'
									) );
									return false;
								break;

							default: // Unknown status code, log
									$this->createLog( array(
										'unifaunLogTitle' => 'Send to Unifaun - Unknown status code',
										'unifaunLogMessage' => $iStatus . ' - ' . $sMessage,
										'unifaunLogType' => 'failiure'
									) );
									return false;
								break;
						}
					} else {
						// Unknown response, log
						$this->createLog( array(
							'unifaunLogTitle' => 'Send to Unifaun - Unknown response',
							'unifaunLogMessage' => $oXml->saveXml(),
							'unifaunLogType' => 'failiure'
						) );
						return false;
					}

				} else {
					// Problem loading answer xml, log
					$this->createLog( array(
						'unifaunLogTitle' => 'Send to Unifaun - Problem loading XML answer',
						'unifaunLogMessage' => $sResult,
						'unifaunLogType' => 'failiure'
					) );
					return false;
				}

			} else {
				// Received an empty string, log
				$this->createLog( array(
					'unifaunLogTitle' => 'Send to Unifaun - Received an empty answer',
					'unifaunLogMessage' => $sResult,
					'unifaunLogType' => 'failiure'
				) );
				return false;
			}
		}
	}

	/**
	 * Fetches shipment history and return data on the first up to 100 shipments not yet returned for the current account in Unifaun Online
	 * @param integer $iFetchId The fetch Id, first call needs to be -1 and subsequent calls need to have the value returned from the previous call
	 * @return mixed Returns array with shipment history or false on error
	 */
	public function trackback( $iFetchId = -1 ) {
		if( empty($this->sSession) ) {
			$this->auth();
			if( empty($this->sSession) ) {
				return false;
			}
		}
		clFactory::loadClassFile( 'clLogger' );
		$oRouter = clRegistry::get( 'clRouter' );
		$oFreightRequest = clRegistry::get( 'clFreightRequest', PATH_MODULE . '/freightRequest/models/' );
		$oSoapClient = new SoapClient('https://www.unifaunonline.com/ws-extapi2/History3?wsdl', array(
			'exceptions' => false
		) );
		// Read and parse service to transporter name
		$aServiceToTransporterName = array();
		if( ($rServicesTabbed = fopen( PATH_MODULE . '/unifaun/files/services.tsv', 'r' )) !== false ) {
			while( ($aData = fgetcsv($rServicesTabbed, 0, "\t")) !== false) {
				$aServiceToTransporterName[$aData[2]] = $aData[1];
			}
			fclose($rServicesTabbed);
		}

		while( true ) {
			$mResponse = $oSoapClient->FetchNewShipments1( array(
				'session' => $this->sSession,
				'fetchId' => $iFetchId
			) );

			if( is_soap_fault($mResponse) ) {
				// SOAP returned a fault
				$this->createLog( array(
					'unifaunLogTitle' => 'TrackBack - ' . $mResponse->getMessage(),
					'unifaunLogMessage' => $mResponse->__toString(),
					'unifaunLogType' => 'failiure'
				) );
				return false;
			} else {
				// SOAP call is ok, parse response and log
				$this->createLog( array(
					'unifaunLogTitle' => 'TrackBack - Success',
					'unifaunLogMessage' => var_export($mResponse, true),
					'unifaunLogType' => 'success'
				) );

				if( isset($mResponse->return->shipments) ) {
					if( is_array($mResponse->return->shipments) ) {
						foreach( $mResponse->return->shipments as $oShipment ) {
							// TODO This needs to be verified live

							$aTmpData = get_object_vars($oShipment);
							if( !empty($aTmpData) ) {
								if( is_array($aTmpData['parcelNos']) ) {
									$aTmpData['parcelNos'] = implode( ', ', $aTmpData['parcelNos'] );
								}

								$iUnifaunOrderId = $this->create( array(
									'unifaunOrderNo' 			=> ( isset($aTmpData['orderNo']) ? $aTmpData['orderNo'] : '' ),
									'unifaunParcelCount' 	=> ( isset($aTmpData['parcelCount']) ? $aTmpData['parcelCount'] : '' ),
									'unifaunParcelNo' 		=> ( isset($aTmpData['parcelNos']) ? $aTmpData['parcelNos'] : '' ),
									'unifaunPartnerId' 		=> ( isset($aTmpData['partnerId']) ? $aTmpData['partnerId'] : '' ),
									'unifaunPrintDate' 		=> ( isset($aTmpData['printDate']) ? date('Y-m-d H:i:s', strtotime($aTmpData['printDate'])) : '0000-00-00 00:00:00' ),
									'unifaunReference' 		=> ( isset($aTmpData['reference']) ? $aTmpData['reference'] : '' ),
									'unifaunServiceId' 		=> ( isset($aTmpData['serviceId']) ? $aTmpData['serviceId'] : '' ),
									'unifaunShipDate' 		=> ( isset($aTmpData['shipDate']) ? date('Y-m-d H:i:s', strtotime($aTmpData['shipDate'])) : '0000-00-00 00:00:00' ),
									'unifaunShipmentNo' 	=> ( isset($aTmpData['shipmentNo']) ? $aTmpData['shipmentNo'] : '' )
								) );
								$aErr = clErrorHandler::getValidationError( 'createUnifaun' );
								if( !empty($aErr) ) {
									clLogger::log( $aErr, 'unifaun.log' );
								}

								if( !empty($aTmpData['orderNo']) ) {
									// Get freight request to invoice
									$aRequestId = current( current($oFreightRequest->readByInvoice($aTmpData['orderNo'], 'requestId')) );

									$oFreightRequest->update( $aRequestId, array(
										'requestStatus' => 'shipped',
										'requestUnifaunOrderId' => $iUnifaunOrderId
									) );

									if( !empty($aTmpData['parcelNos']) ) {
										// Send customer information about the shipment
										#$aOrderMailData = current($oOrder->read( array('*'), $aTmpData['orderNo'] ));
										if( !empty($aOrderMailData) ) {
											/*$aPlaceholderVariables = array(
												'orderPaymentName' => $aOrderMailData['orderPaymentName'],
												'orderId' => $aOrderMailData['orderId'],
												'orderLink' => SITE_DEFAULT_PROTOCOL . '://' . SITE_DOMAIN . $oRouter->getPath('userOrderShow') . '?orderId=' . $aOrderMailData['orderId'],
												'parcelNo' => $aTmpData['parcelNos'],
												'parcelNoWithLink' => '<a href="https://www.unifaunonline.com/ext.uo.se.se.track?key=' . UNIFAUN_ID . '&reference=' . $aTmpData['shipmentNo'] . '">' . $aTmpData['parcelNos'] . '</a>',
												'transporterName' => ( array_key_exists($aTmpData['serviceId'], $aServiceToTransporterName) ? $aServiceToTransporterName[$aTmpData['serviceId']] : '-' )
											);

											$sOrderMail = $oOrder->generateOrderReceiptHtmlNoTemplate($aOrderMailData['orderId']);
											$sMail = $oWebshopMail->renderMail( 'orderPackaged', $sOrderMail, $aPlaceholderVariables);

											$oMail = clFactory::create( 'clMail' );
											$oMail->setFrom( ORDER_EMAIL_FROM )
												  ->addTo( (array) $aOrderMailData['orderEmail'] )
												  ->setSubject( _( 'Order is picked och packaged' )  . ' - ' . SITE_TITLE )
												  ->setBodyHtml( $sMail )
												  ->send();*/
										}
									}
								}
							}
						}
					} else {
						$aTmpData = get_object_vars($mResponse->return->shipments);
						if( !empty($aTmpData) ) {
							if( is_array($aTmpData['parcelNos']) ) {
								$aTmpData['parcelNos'] = implode( ', ', $aTmpData['parcelNos'] );
							}

							$iUnifaunOrderId = $this->create( array(
								'unifaunOrderNo' 		=> ( isset($aTmpData['orderNo']) ? $aTmpData['orderNo'] : '' ),
								'unifaunParcelCount' 	=> ( isset($aTmpData['parcelCount']) ? $aTmpData['parcelCount'] : '' ),
								'unifaunParcelNo' 		=> ( isset($aTmpData['parcelNos']) ? $aTmpData['parcelNos'] : '' ),
								'unifaunPartnerId' 		=> ( isset($aTmpData['partnerId']) ? $aTmpData['partnerId'] : '' ),
								'unifaunPrintDate' 		=> ( isset($aTmpData['printDate']) ? date('Y-m-d H:i:s', strtotime($aTmpData['printDate'])) : '0000-00-00 00:00:00' ),
								'unifaunReference' 		=> ( isset($aTmpData['reference']) ? $aTmpData['reference'] : '' ),
								'unifaunServiceId' 		=> ( isset($aTmpData['serviceId']) ? $aTmpData['serviceId'] : '' ),
								'unifaunShipDate' 		=> ( isset($aTmpData['shipDate']) ? date('Y-m-d H:i:s', strtotime($aTmpData['shipDate'])) : '0000-00-00 00:00:00' ),
								'unifaunShipmentNo' 	=> ( isset($aTmpData['shipmentNo']) ? $aTmpData['shipmentNo'] : '' )
							) );
							$aErr = clErrorHandler::getValidationError( 'createUnifaun' );
							if( !empty($aErr) ) {
								clLogger::log( $aErr, 'unifaun.log' );
							}

							if( !empty($aTmpData['orderNo']) ) {
								// Get freight request to invoice
								$aRequestId = current( current($oFreightRequest->read('requestId', $aTmpData['orderNo'])) );

								$oFreightRequest->update( $aRequestId, array(
									'requestStatus' => 'shipped',
									'requestUnifaunOrderId' => $iUnifaunOrderId
								) );

								if( !empty($aTmpData['parcelNos']) ) {
									// Send customer information about the shipment
									#$aOrderMailData = current($oOrder->read( array('*'), $aTmpData['orderNo'] ));
									if( !empty($aOrderMailData) ) {
										/*$aPlaceholderVariables = array(
											'orderPaymentName' => $aOrderMailData['orderPaymentName'],
											'orderId' => $aOrderMailData['orderId'],
											'orderLink' => SITE_DEFAULT_PROTOCOL . '://' . SITE_DOMAIN . $oRouter->getPath('userOrderShow') . '?orderId=' . $aOrderMailData['orderId'],
											'parcelNo' => $aTmpData['parcelNos'],
											'parcelNoWithLink' => '<a href="https://www.unifaunonline.com/ext.uo.se.se.track?key=' . UNIFAUN_ID . '&reference=' . $aTmpData['shipmentNo'] . '">' . $aTmpData['parcelNos'] . '</a>',
											'transporterName' => ( array_key_exists($aTmpData['serviceId'], $aServiceToTransporterName) ? $aServiceToTransporterName[$aTmpData['serviceId']] : '-' )
										);

										$sOrderMail = $oOrder->generateOrderReceiptHtmlNoTemplate($aOrderMailData['orderId']);
										$sMail = $oWebshopMail->renderMail( 'orderPackaged', $sOrderMail, $aPlaceholderVariables);

										$oMail = clFactory::create( 'clMail' );
										$oMail->setFrom( ORDER_EMAIL_FROM )
											  ->addTo( (array) $aOrderMailData['orderEmail'] )
											  ->setSubject( _( 'Order is picked och packaged' )  . ' - ' . SITE_TITLE )
											  ->setBodyHtml( $sMail )
											  ->send();*/
									}
								}
							}
						}
					}
				}

				if( $iFetchId != -1 && $mResponse->return->done === true ) {
					break;
				}
				if( $mResponse->return->minDelay > 0 ) {
					// Grace period sleep
					usleep( $mResponse->return->minDelay * 1000 );
				}
				$iFetchId = $mResponse->return->fetchId;
			}
		}
		return true;
	}

}
