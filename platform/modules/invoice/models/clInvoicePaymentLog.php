<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 22/05/2014 by Renfors
 * Description:
 * Main file for managing a log of payment of user invoices
 * This is to be used for part payments as well as full payments
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_MODULE . '/invoice/config/cfInvoice.php';
require_once PATH_FUNCTION . '/fMoney.php';
require_once PATH_CORE . '/clModuleBase.php';

class clInvoicePaymentLog extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoicePaymentLog';
		$this->sModulePrefix = 'invoicePaymentLog';

		$this->oDao = clRegistry::get( 'clInvoicePaymentLogDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	public function inactivate( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$result = $this->oDao->updateDataByPrimary( $primaryId, array(
			'logActive' => 'no'
		) );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'Posten har inaktiverats' )
			) );
		}
		return $result;
	}

	public function read( $aFields = array(), $primaryId = null ) {
		// Override main function to make sure only active are reading

		$aParams = array(
			'logId' => $primaryId,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readAll( $aFields = array(), $primaryId = null, $sActive = null ) {
		// Read function ignoring the "logActive" state

		$aParams = array(
			'logId' => $primaryId,
			'logActive' => $sActive,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByBgFile( $sBgFile, $aFields = array(), $sActive = null ) {
		$aParams = array(
			'logBgFile' => $sBgFile,
			'logActive' => $sActive,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

	public function readByInvoice( $iInvoiceId, $aFields = array() ) {
		$aParams = array(
			'logInvoiceId' => $iInvoiceId,
			'logActive' => null,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

}
