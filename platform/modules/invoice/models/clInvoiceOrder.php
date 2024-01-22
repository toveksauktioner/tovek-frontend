<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 22/05/2014 by Renfors
 * Description:
 * Main file for managing payment of user invoices
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

class clInvoiceOrder extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'InvoiceOrder';
		$this->sModulePrefix = 'invoiceOrder';

		$this->oDao = clRegistry::get( 'clInvoiceOrderDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/invoice/models' );

		$this->initBase();

		$this->oDao->switchToSecondary();
	}

	public function getInvoiceOrderStatus( $iInvoiceOrderId ) {
		$aInvoiceOrderData = current( $this->read('invoiceOrderStatus', $iInvoiceOrderId) );
		if( !empty($aInvoiceOrderData) ) {
			return current( $aInvoiceOrderData );
		} else {
			return false;
		}
	}

	public function setInvoiceOrderStatus( $iInvoiceOrderId, $sStatus ) {
		$aData = array(
			'invoiceOrderStatus' => $sStatus
		);

		return $this->update( $iInvoiceOrderId, $aData );
	}

	public function setInvoiceOrderCancelled( $iInvoiceOrderId ) {
		$aData = array(
			'invoiceOrderStatus' => 'cancelled'
		);

		return $this->update( $iInvoiceOrderId, $aData );
	}

}
