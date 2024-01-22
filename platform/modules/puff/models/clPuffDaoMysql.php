<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clRouterHelperDaoSql.php';
require_once PATH_HELPER . '/clJournalHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clPuffDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entPuff' => array(
				'puffId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'puffLayoutKey' => array(
					'type' => 'string',
					'title' => _( 'Layout' ),
					'required' => true
				),
				'puffTitleTextId' => array(
					'type' => 'string',
					'title' => _( 'Title' ),
					'required' => true
				),
				'puffContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Content' )
				),
				'puffShortContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Short content' )
				),
				'puffUrlTextId' => array(
					'type' => 'string',
					'title' => _( 'URL' )
				),
				'puffClass' => array(
					'type' => 'string',
					'title' => _( 'CSS classes' )
				),
				'puffStatus' => array(
					'type' => 'array',
					'values' => array(
						'inactive' => _('Inactive'),
						'active' => _('Active')
					),
					'title' => _( 'Status' )
				),
				'puffPublishStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'puffPublishEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				'puffSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'puffUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				),
				'puffCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'puffUserType' => array(
					'type' => 'array',
					'values' => array(
						'all' => _( 'Alla' ),
						'foreign' => _( 'Utländska' ),
						'domestic' => _( 'Svenska' ),
						'user' => _( 'Kund' ),
						'guest' => _( 'Gäst' )
					),
					'title' => _( 'Visa för' )
				)
			)
		);

		$this->sPrimaryField = 'puffId';
		$this->sPrimaryEntity = 'entPuff';
		$this->aFieldsDefault = '*';

		$this->init();

		$this->aHelpers = array(
			'oRouterHelper' => new clRouterHelperDaoSql( $this, array(
				'parentEntity' => $this->sPrimaryEntity,
				'parentPrimaryField' => $this->sPrimaryField,
				'parentType' => 'Puff'
			) ),
			'oJournalHelperDao' => new clJournalHelperDaoSql( $this, array(
				'aJournalFields' => array(
					'status' => 'puffStatus',
					'publishStart' => 'puffPublishStart',
					'publishEnd' => 'puffPublishEnd'
				)
			) ),
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'puffTitleTextId',
					'puffContentTextId',
					'puffShortContentTextId',
					'puffUrlTextId'
				),
				'sTextEntity' => 'entPuffText'
			) )
		);

		$this->aDataFilters['output'] = array(
			'puffPublishStart' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			),
			'puffPublishEnd' => array(
				'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
				'aParams' => array( '_self_' )
			)
		);
	}

	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );

		$aParams = array(
			'entities' => 'entPuff',
			'primaryField' => 'puffId'
		);
		if( !empty($_GET['puffLayoutKey']) ) {
			$aParams['criterias'] = 'puffLayoutKey = ' . $this->oDb->escapeStr( $_GET['puffLayoutKey'] );
		}

		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'puffSort', $aParams );
	}

	public function readData( $aParams = array() ) {
		// User type filtering before normal reading
		global $oUser;

		$aUserType = array(
			'all',
			'guest'
		);
		if( !empty($_SESSION['userId']) && !empty($oUser) ) {
			$aUserType[] = 'user';

			if( $oUser->readData('infoCountry') != $GLOBALS['defaultCountryId'] ) {
				$aUserType[] = 'foreign';
			} else {
				$aUserType[] = 'domestic';
			}
		}

		if( empty($oUser->aGroups) || (!array_key_exists('super', $oUser->aGroups) && !array_key_exists('admin', $oUser->aGroups)) ) {
			$this->setCriterias( array(
			  'userType' => array(
			    'type' => 'in',
			    'value' => $aUserType,
			    'fields' => 'puffUserType'
			  )
			) );
		}

		return parent::readData( $aParams );
	}

}
