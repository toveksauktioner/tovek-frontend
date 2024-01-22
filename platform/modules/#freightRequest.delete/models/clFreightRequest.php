<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Reference: database-overview.mwb
 * Created: 10/12/2014 by Renfors
 * Description:
 * The module for customers to request a suggested cost for freight
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */
 
require_once PATH_MODULE . '/freightRequest/config/cfFreightRequest.php';
require_once PATH_CORE . '/clModuleBase.php';

class clFreightRequest extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'FreightRequest';
		$this->sModulePrefix = 'freightRequest';
		
		$this->oDao = clRegistry::get( 'clFreightRequestDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/freightRequest/models' );
		
		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}
	
	public function readByInvoice( $iInvoiceId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		
		$aParams = array(
			'fields' => $aFields,
			'requestInvoiceId' => $iInvoiceId
		);
		
		return $this->oDao->readByForeignKey( $aParams );
	}
	
}
