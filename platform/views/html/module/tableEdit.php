<?php

require_once PATH_FUNCTION . '/fFileSystem.php';

if( !empty($_GET['removeModule']) ) {	
	foreach( $_GET['removeModule'] as $sModule ) {		
		/**
		 * Drop database tables
		 */
		$aModuleFiles = scandir( PATH_MODULE . '/' . $sModule . '/models' );
		unset( $aModuleFiles[0], $aModuleFiles[1] );
		if( !empty($aModuleFiles) ) {
			// Database object
			$oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
			
			foreach( $aModuleFiles as $sModuleFile ) {
				if( strpos($sModuleFile, 'Dao') !== false ) {
					$oDao = clRegistry::get( substr( $sModuleFile, 0, strpos($sModuleFile, '.') ), PATH_MODULE . '/' . $sModule . '/models' );
					foreach( array_keys( $oDao->aDataDict ) as $sEntity ) {
						$oDb->write( 'DROP TABLE IF EXISTS ' . $sEntity );						
					}				
				}
			}
		}
		
		/**
		 * Delete views
		 */
		$oView = clRegistry::get( 'clViewHtml' );
		$oView->oDao->deleteData( array(
			'criterias' => 'viewModuleKey = ' . $oView->oDao->oDb->escapeStr( $sModule )
		) );
		
		/**
		 * Delete app events
		 */
		$oEventHandlerDao = clRegistry::get( 'clEventHandlerDao' . DAO_TYPE_DEFAULT_ENGINE );
		$oEventHandlerDao->deleteData( array(
			'criterias' => 'eventListenerPath = "/' . $sModule . '/models"'
		) );
		
		/**
		 * Delete directories
		 */
		if( is_dir(PATH_MODULE . '/' . $sModule) ) {
			// Delete module directory
			deleteDir( PATH_MODULE . '/' . $sModule );
		}
		if( is_dir(PATH_VIEW_HTML . '/' . $sModule) ) {
			// Delete view directory
			deleteDir( PATH_VIEW_HTML . '/' . $sModule );
		}
		
		/**
		 * ACL
		 */
		$oAco = clRegistry::get( 'clAco' );
		$oAco->deleteByGroup( strtolower($sModule) );
		
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->setSessionNotifications( array(
			'dataDeleted' => _( 'The module (db, modules, views, appEvent & files) has been deleted' ),
			'dataWarning' => _( 'Remember to also delete layouts manually' )
		) );				
	}
	
	$oRouter->redirect( $oRouter->sPath );
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlTable = clRegistry::get( 'clOutputHtmlTable' );

// Data
$aModules = scandir( PATH_MODULE );
unset( $aModules[0], $aModules[1] );

$sOutput = '';

if( !empty($aModules) ) {
    $aTableDataDict = array(
        'entTable' => array(
            'name' => array(
                'type' => 'string',
                'title' => _( 'Name' )
            )
        )
    );
	
	$oOutputHtmlTable->init( $aTableDataDict );
	$oOutputHtmlTable->setTableDataDict( current($aTableDataDict) + array(
		'moduleFileCount' => array(
			'title' => _( 'Module files' )
		),
		'viewFileCount' => array(
			'title' => _( 'View files' )
		),
		'entityCount' => array(
			'title' => _( 'DB tables' )
		),
		'tableSelect' => array(
			'title' => _( 'Delete multiple' )
		),
		'emptyColumn' => array(
			'title' => ''
		),
		'tableControls' => array(
			'title' => _( 'Delete single' )
		)
	) );
    
	// Database object
	$oDb = clRegistry::get( 'clDb' . DB_DEFAULT_ENGINE );
	
    foreach( $aModules as $sModule ) {
		$aModuleFiles = $aViewFiles = array();
		$iEntityCount = 0;
		
		if( is_dir(PATH_MODULE . '/' . $sModule . '/models') ) {
			$aModuleFiles = scandir( PATH_MODULE . '/' . $sModule . '/models' );
			unset( $aModuleFiles[0], $aModuleFiles[1] );
			
			foreach( $aModuleFiles as $sModuleFile ) {
				if( strpos($sModuleFile, 'Dao') !== false && strpos($sModuleFile, 'Rest') === false ) {
					$oDao = clRegistry::get( substr( $sModuleFile, 0, strpos($sModuleFile, '.') ), PATH_MODULE . '/' . $sModule . '/models' );
					$iEntityCount += count( array_keys( $oDao->aDataDict ) );
				}
			}
		}
		
		if( is_dir(PATH_VIEW_HTML . '/' . $sModule) ) {
			$aViewFiles = scandir( PATH_VIEW_HTML . '/' . $sModule );
			unset( $aViewFiles[0], $aViewFiles[1] );
		}
		
        $aRow = array(			
            'name' => ucfirst( $sModule ),
			'moduleFileCount' => count( $aModuleFiles ),
			'viewFileCount' => count( $aViewFiles ),
			'entityCount' => $iEntityCount,
			'tableSelect' => $oOutputHtmlForm->createInput( 'checkbox', 'removeModule[]', array('value' => $sModule) ),
			'emptyColumn' => '&nbsp;',
			'tableControls' => '
				<a href="?removeModule[]=' . $sModule . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
        );
        $oOutputHtmlTable->addBodyEntry( $aRow );
    }
	
	$oOutputHtmlTable->addFooterEntry( array(
		'name' => '',
		'moduleFileCount' => '',
		'viewFileCount' => '',
		'entityCount' => '',
		'tableSelect' => $oOutputHtmlForm->createButton( 'submit', _( 'Delete selected' ) ),
		'emptyColumn' => '',
		'tableControls' => ''
	) );
	
    $sOutput = $oOutputHtmlForm->createForm( 'get', $oRouter->sPath, $oOutputHtmlTable->render() );
    
} else {
    $sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view module tableEdit">
		<h1>' . _( 'Modules' ) . '</h1>
		<section>
			' . $sOutput . '
		</section>
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'deleteColumn',
	'content' => '
		th.tableSelect { text-align: center; }
		td.tableSelect { width: 8em; text-align: center; background: #FFE8E8; }
		tfoot tr .tableSelect { padding: .6em 0; }
		tfoot tr .tableSelect:hover { background: #F7DCDC; }
		tfoot tr .tableSelect button { font-size: .85em; }
	'
) );