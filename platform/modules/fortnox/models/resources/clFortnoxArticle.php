<?php

require_once PATH_MODULE . '/fortnox/models/clFortnoxBase.php';
require_once PATH_MODULE . '/fortnox/models/clFortnoxDaoBaseRest.php';

class clFortnoxArticle extends clFortnoxBase {

	public function __construct() {
		$this->sModuleName = 'FortnoxArticle';
		$this->sModulePrefix = 'fortnoxArticle';

		$this->sResourceName = 'articles';
		$this->sPropertyName = 'article';

		$this->oDao = new clFortnoxArticleDaoRest();

		$this->initBase();
	}

}

class clFortnoxArticleDaoRest extends clFortnoxDaoBaseRest {

	public function __construct() {
		/**
		 * https://developer.fortnox.se/documentation/resources/articles/#Properties
		 */
		$this->aDataDict = array(
			'entFortnoxArticle' => array(
				'@url' => array(
					'type' => 'string',
					'title' => '@url',
					'appearance' => 'readonly'
				),
				'ArticleNumber' => array(
					'type' => 'string',
					'title' => 'ArticleNumber'
				),
				'Bulky' => array(
					'type' => 'boolean',
					'title' => 'Bulky',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'ConstructionAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'ConstructionAccount'
				),
				'Depth' => array(
					'type' => 'integer',
					'max' => 8,
					'title' => 'Depth'
				),
				'Description' => array(
					'type' => 'string',
					'max' => 200,
					'title' => 'Description'
				),
				'DisposableQuantity' => array(
					'type' => 'float',
					'title' => 'DisposableQuantity',
					'appearance' => 'readonly'
				),
				'EAN' => array(
					'type' => 'string',
					'max' => 30,
					'title' => 'EAN'
				),
				'EUAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'EUAccount'
				),
				'EUVATAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'EUVATAccount'
				),
				'ExportAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'ExportAccount'
				),
				'Height' => array(
					'type' => 'integer',
					'max' => 8,
					'title' => 'Height'
				),
				'Housework' => array(
					'type' => 'boolean',
					'title' => 'Housework',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'HouseworkType' => array(
					'type' => 'array', # (string)
					'title' => 'HouseworkType',
					'values' => array(
						'',
						'CONSTRUCTION',
						'ELECTRICITY',
						'GLASSMETALWORK',
						'GROUNDDRAINAGEWORK',
						'MASONRY',
						'PAINTINGWALLPAPERING',
						'HVAC',
						'CLEANING',
						'TEXTILECLOTHING',
						'COOKING',
						'SNOWPLOWING',
						'GARDENING',
						'BABYSITTING',
						'OTHERCARE',
						'TUTORING',
						'OTHERCOSTS'
					)
				),
				'Manufacturer' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'Manufacturer'
				),
				'ManufacturerArticleNumber' => array(
					'type' => 'string',
					'max' => 50,
					'title' => 'ManufacturerArticleNumber'
				),
				'Note' => array(
					'type' => 'string',
					'max' => 10000,
					'title' => 'Note'
				),
				'PurchaseAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'PurchaseAccount'
				),
				'PurchasePrice' => array(
					'type' => 'float',
					'max' => 14,
					'title' => 'PurchasePrice'
				),
				'QuantityInStock' => array(
					'type' => 'float',
					'max' => 14,
					'title' => 'QuantityInStock'
				),
				'ReservedQuantity' => array(
					'type' => 'float',
					'title' => 'ReservedQuantity',
					'appearance' => 'readonly'
				),
				'SalesAccount' => array(
					'type' => 'integer',
					'max' => 4,
					'title' => 'SalesAccount'
				),
				'SalesPrice' => array(
					'type' => 'float',
					'title' => 'SalesPrice',
					'appearance' => 'readonly'
				),
				'StockGoods' => array(
					'type' => 'boolean',
					'title' => 'StockGoods',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'StockPlace' => array(
					'type' => 'string',
					'max' => 100,
					'title' => 'StockPlace'
				),
				'StockValue' => array(
					'type' => 'float',
					'title' => 'StockValue',
					'appearance' => 'readonly'
				),
				'StockWarning' => array(
					'type' => 'float',
					'max' => 14,
					'title' => 'StockWarning'
				),
				'SupplierName' => array(
					'type' => 'string',
					'title' => 'SupplierName',
					'appearance' => 'readonly'
				),
				'SupplierNumber' => array(
					'type' => 'string',
					'title' => 'SupplierNumber'
				),
				'Type' => array(
					'type' => 'array', # (string)
					'title' => 'Type',
					'values' => array(
						'STOCK',
						'SERVICE'
					)
				),
				'Unit' => array(
					'type' => 'string',
					'title' => 'Unit'
				),
				'VAT' => array(
					'type' => 'float',
					'title' => 'VAT'
				),
				'WebshopArticle' => array(
					'type' => 'boolean',
					'title' => 'WebshopArticle',
					'values' =>  array(
						'true',
						'false'
					)
				),
				'Weight' => array(
					'type' => 'integer',
					'max' => 8,
					'title' => 'Weight'
				),
				'Width' => array(
					'type' => 'integer',
					'max' => 8,
					'title' => 'Width'
				),
				'Expired' => array(
					'type' => 'boolean',
					'title' => 'Expired',
					'values' =>  array(
						'true',
						'false'
					)
				)
			)
		);

		$this->sPrimaryField = 'ArticleNumber';
		$this->sPrimaryEntity = 'entFortnoxArticle';
		$this->aFieldsDefault = '*';

		$this->init();
	}

}
