<?php

require_once PATH_CORE . '/clDaoBaseSql.php';


class clEventHandlerDaoMysql extends clDaoBaseSql {
	
	public $aDataDict = array();
	
	public function __construct() {
		$this->aDataDict = array(
			'entAppEvent' => array(
				'eventId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
				),
				'eventKey' => array(
					'type' => 'string',
					'index' => true,
					'required' => true
				),
				'eventType' => array(
					'type' => 'array',
					'values' => array(
						'internal' => _( 'Internal' ),
						'external' => _( 'External' )
					)
				),
				'eventListener' => array(
					'type' => 'string',
					'required' => true,
				),
				'eventListenerPath' => array(
					'type' => 'string',
				),
				'eventListenerFunction' => array(
					'type' => 'string',
					'required' => true,
				)
			)
		);
		$this->sPrimaryField = 'eventId';
		$this->aFields = array( 
			'eventListener',
			'eventListenerFunction'
		);
		
		$this->init();
	}
	
	public function readListeners( $sEvent, $sEventType = 'internal' ) {
		$aParams = array(
			'criterias' => 'eventKey = ' . $this->oDb->escapeStr( $sEvent ) . ' AND eventType = ' . $this->oDb->escapeStr( $sEventType )
		);
		
		return $this->readData( $aParams );
	}
}
