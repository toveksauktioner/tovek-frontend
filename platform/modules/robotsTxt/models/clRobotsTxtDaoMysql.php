<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clRobotsTxtDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entRobotsTxt' => array(
				'ruleId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'ruleType' => array(
					'type' => 'array',
					'values' => array(
						'user-agent' => 'User-agent',
						'disallow' => 'Disallow',
						'allow' => 'Allow',
						'sitemap' => 'Sitemap'
					),
					'title' => _( 'Type' ),
					'required' => true
				),
				'ruleVariable' => array(
					'type' => 'string',
					'title' => _( 'Variable' ),
					'required' => true
				),
				'ruleSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort' )
				),
				'ruleActivation' => array(
					'type' => 'array',
					'values' => array(
						'always' => _( 'Always' ),
						'never' => _( 'Never' ),
						'on-not-released' => _( 'Not released' ),
						'on-released' => _( 'Released' )
					),
					'title' => _( 'Activation' ),
					'required' => true
				),
				'ruleCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				)
			)
		);

		$this->sPrimaryField = 'ruleId';
		$this->sPrimaryEntity = 'entRobotsTxt';
		$this->aFieldsDefault = '*';

		$this->init();
	}

	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'ruleSort', array(
			'entities' => 'entRobotsTxt',
			'primaryField' => 'ruleId'
		) );
	}

}
