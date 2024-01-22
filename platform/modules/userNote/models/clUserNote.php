<?php

/**
 * $Id$
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * Created: 13/10/2015 by Renfors
 * Description:
 * Main file for managing user notes
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author$
 * @version		Subversion: $Revision$, $Date$
 */

require_once PATH_CORE . '/clModuleBase.php';

class clUserNote extends clModuleBase {

	public function __construct() {
		$this->sModuleName = 'UserNote';
		$this->sModulePrefix = 'userNote';

		$this->oDao = clRegistry::get( 'clUserNoteDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/userNote/models' );

		$this->initBase();
		
		$this->oDao->switchToSecondary();
	}

	/**
	 * Overload of parent function for creating data
	 */
	public function create( $aData ) {
		$aData += array(
			'noteCreated' => date( 'Y-m-d H:i:s' )
		);

		return parent::create( $aData );
	}

	/**
	 * Get users notes
	 */
	public function readByUserId( $iUserId, $aFields = null ) {
		$aParams = array(
			'noteUserId' => $iUserId,
			'fields' => $aFields
		);

		return $this->oDao->readByForeignKey( $aParams );
	}

}
