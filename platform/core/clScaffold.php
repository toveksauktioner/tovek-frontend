<?php

class clScaffold {

	public $oModuleDao;
	private $oAcl;

	private $sModuleName;
	private $sModulePrefix;

	public function __construct( clDaoBaseSql &$oModuleDao ) {
		$this->sModulePrefix = 'scaffold';
		$this->sModuleName = 'Scaffold';
		
		$oUser = clRegistry::get( 'clUser' );
		if( !array_key_exists('super', $oUser->aGroups) ) throw new Exception( _( 'No access' ) . ' clScaffold' );
		$this->oAcl = $oUser->oAcl;
		
		if( !is_object($oModuleDao) ) throw new Exception( _( 'Argument needs to be a object' ) );
		if( !property_exists($oModuleDao, 'aDataDict') ) throw new Exception( _( 'Module object is missing a DataDict' ) );
		if( empty( $oModuleDao->sPrimaryField ) ) throw new Exception( _( 'Module object DataDict is missing a primary column' ) );
				
		$this->oModuleDao =& $oModuleDao;
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;

		if( $this->oModuleDao->createData($aData, $aParams) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
			return $this->oModuleDao->oDb->lastId();
		}
		return false;
	}
	
	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$result = $this->oModuleDao->deleteDataByPrimary( $primaryId );
		if( !empty($result) ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataDeleted' => _( 'The data has been deleted' )
			) );
		}
		return $result;
	}

	public function render( $aParams = array() ) {
		$aParams += array(
			'pagination' => true,
			'sorting' => true,
			'edit' => true,
			'delete' => true,
			'search' => true
		);
		
		$sOutput = '';

		$aModuleDataDict = current($this->oModuleDao->getDataDict());
		
		// Rewrite so everything got a title
		foreach( $aModuleDataDict as $key => $value ) {
			if( empty($value['title']) ) $aModuleDataDict[$key]['title'] = $key;
		}

		// Form add
		if( $aParams['edit'] === true ) {
			$aErrFormAdd = array();
			
			$oOutputHtmlFormAdd = clRegistry::get( 'clOutputHtmlForm' );
			$oOutputHtmlFormAdd->init( $aModuleDataDict, array(
				'action' => '',
				'attributes' => array( 'class' => 'marginal' ),
				'data' => $_POST,
				'errors' => $aErrFormAdd,
				'method' => 'post',
				'buttons' => array(
					'submit' => _( 'Save' )
				),
			) );
			$oOutputHtmlFormAdd->setFormDataDict( $aModuleDataDict + array(
				'frmAddScaffold' => array(
					'type' => 'hidden',
					'value' => true
				)
			) );
			
			$oTemplate = clRegistry::get('clTemplateHtml');
			$oTemplate->addBottom( array(
				'key' => 'jsToggleScaffoldFormAdd',
				'content' => '
				<script>
					$("#formScaffoldAdd").hide();
				</script>'
			) );
			
			$sOutput .= '
			<a href="#formScaffoldAdd" class="toggleShow icon iconText iconAdd">' . _( 'Add' ) .'</a>
			<div id="formScaffoldAdd">
				' . $oOutputHtmlFormAdd->render() . '
			</div>';
		}

		// Search form
		if( $aParams['search'] === true ) {
			if( !empty($_GET['searchQuery']) ) {
				$this->oModuleDao->setCriterias( array(
					'search' => array(
						'type' => 'like',
						'value' => $_GET['searchQuery'],
						'fields' => array_keys($aModuleDataDict)
					)
				) );
			}
			$oOutputHtmlFormSearch = clRegistry::get( 'clOutputHtmlForm' );
			$oOutputHtmlFormSearch->init( $aModuleDataDict, array(
				'action' => '',
				'attributes' => array( 'class' => 'searchForm' ),
				'data' => $_GET,
				'buttons' => array(
					'submit' => _( 'Search' ),
				)
			) );
			$oOutputHtmlFormSearch->setFormDataDict( array(
				'searchQuery' => array(
					'title' => _( 'Search' )
				)
			), array_diff_key($_GET, array('searchQuery' => '', 'page' => '')) );
			$sOutput .= '
			' . $oOutputHtmlFormSearch->render();
		}

		// Sorting
		if( $aParams['sorting'] === true ) {
			clFactory::loadClassFile( 'clOutputHtmlSorting' );
			$oSorting = new clOutputHtmlSorting( $this->oModuleDao, array(
				'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array($this->oModuleDao->sPrimaryField => 'DESC') )
			) );
			$oSorting->setSortingDataDict( $aModuleDataDict );
		}
		
		// Pagination
		if( $aParams['pagination'] === true ) {
			clFactory::loadClassFile( 'clOutputHtmlPagination' );
			$oPagination = new clOutputHtmlPagination( $this->oModuleDao, array(
				'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
				'selectEntries' => true,
				'entries' => ( !empty($_GET['entries']) ? $_GET['entries'] : 30 )
			) );
		}
		
		$aData = $this->oModuleDao->readData( array('fields' => '*') );
		if( !empty($aData) ) {
			// Output html table
			clFactory::loadClassFile( 'clOutputHtmlTable' );
			$oOutputHtmlTable = new clOutputHtmlTable( $aModuleDataDict );
			$oOutputHtmlTable->setTableDataDict( ($aParams['sorting'] === true ? $oSorting->render() : $aModuleDataDict )
			    + ( $aParams['edit'] === true ? array( 'scaffoldButtons' => array('title' => '') ) : array() )
				+ array(				
				'scaffoldControls' => array(
					'title' => ''
				)
			) );
			
			$bFormInit = false;
			$iCount = 1;
			foreach( $aData as $aValues ) {
				$row = $aValues;
				
				$aAttributes = array();
				if( $iCount % 2 === 0 ) $aAttributes['class'] = 'odd';
				
				// Edit form
				if( $aParams['edit'] === true && !empty($_GET['edit']) && $_GET['edit'] == $aValues[$this->oModuleDao->sPrimaryField] ) {
					$bFormInit = true; # Keep track if a form is loaded and output below
					$aErr = array();
					
					$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
					$oOutputHtmlForm->init( $aModuleDataDict, array(
						'action' => '',
						'attributes' => array( 'class' => 'inTable' ),
						'data' => $aValues,
						'errors' => $aErr,
						'method' => 'post',
						'buttons' => array(
							'submit' => _( 'Save' )
						),
					) );
					$oOutputHtmlForm->setFormDataDict( $aModuleDataDict + array(
						'frmEditScaffold' => array(
							'type' => 'hidden',
							'value' => true
						)
					) );
					foreach( $aValues as $key => $value ) {
						$row[$key] = $oOutputHtmlForm->renderFields($key);
					}
				}
				
				if( $aParams['edit'] === true ) $aExtraColumns['scaffoldButtons'] = '
					' . ( $bFormInit === true && $_GET['edit'] == $aValues[$this->oModuleDao->sPrimaryField] ? $oOutputHtmlForm->renderFields('frmEditScaffold') . $oOutputHtmlForm->renderButtons() : '' );
				
				$aExtraColumns['scaffoldControls'] = '										
					' . ( $aParams['edit'] === true ? '<a href="?edit=' . $aValues[$this->oModuleDao->sPrimaryField] . '&amp;' . stripGetStr( array('edit', 'deleteScaffold') ) . '#edit-' .$aValues[$this->oModuleDao->sPrimaryField] . '" id="edit-' . $aValues[$this->oModuleDao->sPrimaryField] . '" class="icon iconText iconEdit">' . _( 'Edit' ) . '</a>' : '' ) . '
					' . ( $aParams['delete'] === true ? '<a href="?deleteScaffold=' . $aValues[$this->oModuleDao->sPrimaryField] . '&amp;' . stripGetStr( array('edit', 'deleteScaffold') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>' : '' );
				
				$oOutputHtmlTable->addBodyEntry( $row + $aExtraColumns, $aAttributes );
				++$iCount;
			}
		
			if( $aParams['edit'] === true && $bFormInit === true ) {
				$sOutput .= $oOutputHtmlForm->renderForm(
					$oOutputHtmlForm->renderErrors() . '
					' . $oOutputHtmlTable->render()
				);
			} else {
				$sOutput .= $oOutputHtmlTable->render();
			}
			
			if( $aParams['pagination'] === true ) $sOutput .= $oPagination->render();
		} else {
			$sOutput .= '
			<strong>' . _( 'There are no items to show' ) . '</strong>';
		}
		
		return $sOutput;
	}

	public function update( $primaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		
		$result = $this->oModuleDao->updateDataByPrimary( $primaryId, $aData, $aParams );
		if( $result !== false ) {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataSaved' => _( 'The data has been saved' )
			) );
		}
		return $result;
	}

}
