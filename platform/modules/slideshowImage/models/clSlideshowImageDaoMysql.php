<?php

require_once PATH_CORE . '/clDaoBaseSql.php';
require_once PATH_HELPER . '/clJournalHelperDaoSql.php';
require_once PATH_HELPER . '/clTextHelperDaoSql.php';

class clSlideshowImageDaoMysql extends clDaoBaseSql {

	public function __construct() {
		$this->aDataDict = array(
			'entSlideshowImage' => array(
				'slideshowImageId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
				'slideshowImageSort' => array(
					'type' => 'integer',
					'title' => _( 'Sort flag' )
				),
				'slideshowImageStatus' => array(
					'type' => 'array',
					'values' => array(
						'active' => _( 'Active' ),
						'inactive' => _( 'Inactive' )
					),
					'title' => _( 'Status' )
				),
				'slideshowImageStart' => array(
					'type' => 'datetime',
					'title' => _( 'Start' )
				),
				'slideshowImageEnd' => array(
					'type' => 'datetime',
					'title' => _( 'End' )
				),
				'slideshowImageContentTextId' => array(
					'type' => 'string',
					'title' => _( 'Content' )
				),
				'slideshowImageUrlTextId' => array(
					'type' => 'string',
					'title' => _( 'Path' )
				),
				/**
				 * Color
				 */
				'slideshowImageTextColor' => array(
					'type' => 'string',
					'title' => _( 'Text color' )
				),
				'slideshowImageBackgroundColor' => array(
					'type' => 'string',
					'title' => _( 'Background color' )
				),
				'slideshowImageGradientColor' => array(
					'type' => 'string',
					'title' => _( 'Gradient color' )
				),
				/**
				 * Transformation
				 */
				'slideshowImageSpeed' => array(
					'type' => 'array',
					'title' => _( 'Speed' ),
					'values' => array(
						'500' => '0.5 ' . _( 'seconds' ),
						'1000' => '1 ' . _( 'seconds' ),
						'1500' => '1.5 ' . _( 'seconds' ),
						'2000' => '2 ' . _( 'seconds' ),
						'2500' => '2.5 ' . _( 'seconds' ),
						'3000' => '3 ' . _( 'seconds' )
					)
				),
				'slideshowImageTimeout' => array(
					'type' => 'array',
					'title' => _( 'Timeout' ),
					'values' => array(
						'1000' => '1 ' . _( 'second' ),
						'1500' => '1.5 ' . _( 'seconds' ),
						'2000' => '2 ' . _( 'seconds' ),
						'2500' => '2.5 ' . _( 'seconds' ),
						'3000' => '3 ' . _( 'seconds' ),
						'3500' => '3.5 ' . _( 'seconds' ),
						'4000' => '4 ' . _( 'seconds' ),
						'4500' => '4.5 ' . _( 'seconds' ),
						'5000' => '5 ' . _( 'seconds' ),
						'5500' => '5.5 ' . _( 'seconds' ),
						'6000' => '6 ' . _( 'seconds' )
					)
				),
				'slideshowImageFx' => array(
					'type' => 'array',
					'title' => _( 'Effect' ),
					'values' => array(
						'fade' => _( 'fade' ),
						'fadeout' => _( 'fadeout' ),
						'scrollHorz' => _( 'Scroll horizontal' ),
						'none' => _( 'None' )
					)
				),
				/**
				 * Misc
				 */
				'slideshowImageCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
				'slideshowImageUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				)
			)
		);

		$this->sPrimaryField = 'slideshowImageId';
		$this->sPrimaryEntity = 'entSlideshowImage';
		$this->aFieldsDefault = '*';

		$this->aDataFilters = array(
			'input' => array(
				'slideshowImageStart' => array(
					'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
					'aParams' => array( '_self_' )
				),
				'slideshowImageEnd' => array(
					'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
					'aParams' => array( '_self_' )
				)
			),
			'output' => array(
				'slideshowImageStart' => array(
					'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
					'aParams' => array( '_self_' )
				),
				'slideshowImageEnd' => array(
					'sFunction' => function($sDate) { return ( $sDate == "0000-00-00 00:00:00" ? "" : $sDate ); },
					'aParams' => array( '_self_' )
				)
			)
		);
		$this->aHelpers = array(
			'oJournalHelperDao' => new clJournalHelperDaoSql( $this, array(
				'aJournalFields' => array(
					'status' => 'slideshowImageStatus',
					'publishStart' => 'slideshowImageStart',
					'publishEnd' => 'slideshowImageEnd'
				)
			) ),
			'oTextHelperDao' => new clTextHelperDaoSql( $this, array(
				'aTextFields' => array(
					'slideshowImageContentTextId',
					'slideshowImageUrlTextId'
				),
				'sTextEntity' => 'entSlideshowImageText'
			) )
		);

		$this->init();
	}

	public function updateSort( $aPrimaryIds ) {
		$aPrimaryIds = array_map( 'intval', (array) $aPrimaryIds );

		$oDaoMysql = clRegistry::get( 'clDaoMysql' );
		$oDaoMysql->setDao( $this );
		return $oDaoMysql->updateSortOrder( $aPrimaryIds, 'slideshowImageSort', array(
			'entities' => 'entSlideshowImage',
			'primaryField' => 'slideshowImageId'
		) );
	}

}