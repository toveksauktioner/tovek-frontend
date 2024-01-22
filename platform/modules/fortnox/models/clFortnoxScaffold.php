<?php

class clFortnoxScaffold {

	public $oResource;
	public $aParams;

	public $aData;
	public $aDataDict;

	public function __construct() {}

	public function init( clFortnoxBase &$oResource ) {
		$this->oResource = &$oResource;
	}

	public function setData( $aData ) {
		$this->aData = $aData;
	}

	public function assambleDataDict() {
		if( empty($this->aData) ) return false;

		$aDataDict = array();

		foreach( current($this->aData) as $sField => $mValue ) {
			$aDataDict[$sField] = array(
				'type' => gettype( $mValue ),
				'title' => ucfirst( $sField )
			);
			switch( gettype( $mValue ) ) {
				case 'boolean':
					$aDataDict[$sField] += array(
						'values' => array(
							'true',
							'false'
						)
					);
					break;

				case 'datetime':
					$aDataDict[$sField] += array(
						'attributes' => array(
							'class' => 'datetimepicker text'
						)
					);
					break;
			}
		}

		$this->aDataDict = array( 'ent' . ucfirst($this->oResource->sResourceName) => $aDataDict );

		return true;
	}

	public function render( $aParams = array() ) {
		$aParams += array(
			// Todo:
			//'pagination' => true,
			//'sorting' => true,
			//'search' => true
		);

		/**
		 * Search [todo]
		 */
		//if( !empty($_REQUEST['searchQuery']) ) {
		//	$aSearchCriterias = array(
		//		'scaffoldSearch' => array(
		//			'type' => 'like',
		//			'value' => $_REQUEST['searchQuery'],
		//			'fields' => $this->oResource->oDao->getSearchableFields()
		//		)
		//	);
		//	$this->oResource->oDao->setCriterias( $aSearchCriterias );
		//}

		/**
		 * Data
		 */
		if( empty($this->aData) ) $this->aData = $this->oResource->get();

		$sOutput = '';
		$aErr = array();
		$sPrimaryField = $this->oResource->oDao->sPrimaryField;

		if( !empty($_REQUEST[ $sPrimaryField ]) ) {
			// Edit
			$aDataByKey = valueToKey( $sPrimaryField, $this->aData );
			$aData = $aDataByKey[ $_REQUEST[ $sPrimaryField ] ];

			$sAddLink = '<a href="#frmAddFortnoxScaffold" class="toggleShow icon iconText iconAdd disabled">' . _( 'Add row' ) . '</a>';

		} else {
			// New
			$aData = $_REQUEST;

			$sAddLink = '<a href="#frmAddFortnoxScaffold" class="toggleShow icon iconText iconAdd">' . _( 'Add row' ) . '</a>';
		}

		/**
		 * Form init
		 */
		$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( $this->oResource->oDao->getDataDict(), array(
			'action' => '',
			'attributes' => array( 'class' => 'inTable' ),
			'data' => $aData,
			'errors' => $aErr,
			'method' => 'post'
		) );
		$oOutputHtmlForm->setFormDataDict( current( $this->oResource->oDao->getDataDict() ) + array(
			'frmEditFortnoxScaffold' => array(
				'type' => 'hidden',
				'value' => true
			),
			'frmAddFortnoxScaffold' => array(
				'type' => 'hidden',
				'value' => true
			),
			'useResource' => array(
				'type' => 'hidden',
				'value' => $_REQUEST['useResource']
			)
		) );

		/**
		 * Form table row fields
		 */
		$aAddForm = array();
		$aDataTypeFields = array();
		foreach( current( $this->oResource->oDao->getDataDict() ) as $sField => $aAttributes ) {
			$aAddForm[$sField] = $oOutputHtmlForm->renderFields( $sField );
			$aDataTypeFields[] = $oOutputHtmlForm->createInput( 'hidden', 'dataDict[' . $sField . ']', array('value' => $aAttributes['type']) );
		}
		$aAddForm['tableRowControls'] = '
			<div class="hidden">
				' . implode( ' ', $aDataTypeFields ) . '
				' . $oOutputHtmlForm->renderFields( 'useResource' ) . '
			</div>' .
			$oOutputHtmlForm->createButton( 'submit', _( 'Save' ) );

		/**
		 * Table init
		 */
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $this->oResource->oDao->getDataDict() );
		$oOutputHtmlTable->setTableDataDict( current( $this->oResource->oDao->getDataDict() ) + array(
			'tableRowControls' => array(
				'title' => ''
			)
		) );

		if( empty($_REQUEST[ $sPrimaryField ]) ) {
			/**
			 * Add hiden new entry row
			 */
			$aNewForm = $aAddForm;
			$aNewForm['tableRowControls'] .= $oOutputHtmlForm->renderFields( 'frmAddFortnoxScaffold' );
			$oOutputHtmlTable->addBodyEntry( $aNewForm, array(
				'id' => 'frmAddFortnoxScaffold',
				'style' => 'display: table-row;'
			) );
		}

		/**
		 * Table rows
		 */
	 	if( !empty($this->aData) ) {
			foreach( $this->aData as $aEntry ) {
				if( !empty($_REQUEST[ $sPrimaryField ]) && $aEntry[ $sPrimaryField ] == $_REQUEST[ $sPrimaryField ] ) {
					// Edit
					$aAddForm['tableRowControls'] .= '&nbsp;&nbsp;<a href="?' . stripGetStr( array( $sPrimaryField , 'event', 'deleteFortnoxScaffold') ) . '" class="icon iconText iconGoBack">' . _( 'Back' ) . '</a>';
					$aAddForm['tableRowControls'] .= $oOutputHtmlForm->renderFields( 'frmEditFortnoxScaffold' );

					$oOutputHtmlTable->addBodyEntry( $aAddForm );

				} else {
					// Data row
					$aEntry['tableRowControls'] = '
						<a href="?' . $sPrimaryField . '=' . $aEntry[ $sPrimaryField ] . '&' . stripGetStr( array( 'deleteFortnoxScaffold', 'event', 'subscriberId' ) )  . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>
						<a href="?event=deleteFortnoxScaffold&deleteFortnoxScaffold=' . $aEntry[ $sPrimaryField ] . '&' . stripGetStr( array( 'deleteFortnoxScaffold', 'event') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>';

					$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aEntry ) );
				}
			}

			$sOutput = $oOutputHtmlForm->renderForm( $oOutputHtmlForm->renderErrors() . $oOutputHtmlTable->render() ); # $sPagination
		}

		/**
		 * Search form
		 */
		$oRouter = clRegistry::get( 'clRouter' );
		$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( current( $this->oResource->oDao->getDataDict() ), array(
			'action' => $oRouter->sPath . ( !empty($_GET) ? '?' . stripGetStr( array('searchQuery', 'entriesShown', 'page')) : '' ),
			'attributes' => array( 'class' => 'searchForm' ),
			'data' => $_GET,
			'method' => 'get',
			'buttons' => array(
				'submit' => _( 'Search' ),
			)
		) );
		$oOutputHtmlForm->setFormDataDict( array(
			'searchQuery' => array(
				'title' => _( 'Search here..' )
			),
			'entriesShown' => array(
				'title' => _( 'Entries per page' )
			),
			'page' => array(
				'type' => 'hidden',
				'value' => !empty($_GET['page']) ? $_GET['page'] : 0
			)
		) );

		$sOutput = '
			<div class="view fortnox scaffold">
				<h1>' . ucfirst($this->oResource->sResourceName) . '</h1>
				<section class="tools">
					<div class="tool">
						' . $oOutputHtmlForm->render() . '
					</div>
				</section>
				<hr />
				' . $sAddLink . '
				<hr />
				<section class="dataOutput" style="overflow-x: scroll;">
					' . $sOutput . '
				</section>
			</div>';

		return $sOutput;
	}

}
