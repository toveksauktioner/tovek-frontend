<?php

$oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );
$oPuff->oDao->setLang( $GLOBALS['langIdEdit'] );

if( !empty($_GET['puffLayoutKey']) && $_GET['puffLayoutKey'] != '*' ) {
	$oPuff->oDao->setCriterias( array(
		'routePath' => array(
			'type' => '=',				
			'fields' => 'puffLayoutKey',
			'value' => $_GET['puffLayoutKey']
		)
	) );
}

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oPuff->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('puffSort' => 'ASC') )
) );
$aPuffDataDict = $oPuff->oDao->getDataDict( 'entPuff' );

$aTableDataDict = array(
	'puffSort' => array(),
	'puffTitleTextId' => array(),
	'puffPublishStart' => array(),
	'puffPublishEnd' => array(),
	'puffLayoutKey' => array(),
	'puffStatus' => array(),	
	'puffCreated' => array()
);

if( PUFF_LAYOUT_EDIT === false || PUFF_LAYOUT_EDIT === true && count( $GLOBALS['puffLayout'] ) <= 1 ) {
	unset( $aTableDataDict['puffLayoutKey'] );
}

$oSorting->setSortingDataDict( $aTableDataDict );

// Data
$aPuffs = $oPuff->read( array(
	'puffId',
	'puffTitleTextId',
	'puffPublishStart',
	'puffPublishEnd',
	'puffLayoutKey',
	'puffStatus',
	'puffSort',
	'puffCreated',	
) );

$sEditUrl = $oRouter->getPath( 'adminPuffAdd' );

$sOutput = '';
$aSortGroup = array();

if( !empty($aPuffs) ) {
	if( PUFF_LAYOUT_EDIT === true ) {
		/**
		 * Group puffs by layout
		 */
		$aDataByGroup = $GLOBALS['puffLayout'];		
		foreach( $aPuffs as $aPuff ) {
			if( empty($aDataByGroup[ $aPuff['puffLayoutKey'] ]['puffs']) ) {
				$aDataByGroup[ $aPuff['puffLayoutKey'] ]['puffs'] = array();
			}
			$aDataByGroup[ $aPuff['puffLayoutKey'] ]['puffs'][] = $aPuff;
		}
		
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		foreach( $aDataByGroup as $sKey => $aGroup ) {
			$oOutputHtmlTable = new clOutputHtmlTable( $oPuff->oDao->getDataDict() );
			$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
				'puffControls' => array(
					'title' => ''
				)
			) );
			
			if( !empty($aGroup['puffs']) ) {
				foreach( $aGroup['puffs'] as $aEntry ) {
					if( $aEntry['puffPublishStart'] === '0000-00-00 00:00:00' ) $aEntry['puffPublishStart'] = '-';
					if( $aEntry['puffPublishEnd'] === '0000-00-00 00:00:00' ) $aEntry['puffPublishEnd'] = '-';
					
					$aRow = array(
						'puffSort' => $aEntry['puffSort'],
						'puffTitleTextId' => '<a href="' . $sEditUrl . '?puffId=' . $aEntry['puffId'] . '" class="ajax">' . htmlspecialchars( $aEntry['puffTitleTextId'] ) . '</a>',
						'puffPublishStart' => substr( $aEntry['puffPublishStart'], 0, 10 ),
						'puffPublishEnd' => substr( $aEntry['puffPublishEnd'], 0, 10 ),
						'puffLayoutKey' => '<span class="' . $aEntry['puffLayoutKey'] . '">' . $GLOBALS['puffLayout'][ $aEntry['puffLayoutKey'] ]['name'] . '</span>',
						'puffStatus' => '<span class="' . $aEntry['puffStatus'] . '">' . $aPuffDataDict['entPuff']['puffStatus']['values'][ $aEntry['puffStatus'] ] . '</span>',
						'puffCreated' => substr( $aEntry['puffCreated'], 0, 16 ),						
						'puffControls' => '
							<a href="' . $sEditUrl . '?puffId=' . $aEntry['puffId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
							<a href="' . $sEditUrl . '?useContent=true&usePuffId=' . $aEntry['puffId'] . '" class="icon iconText iconOverlays"><span>' . _( 'Use as template' ) . '</span></a>
							<a href="' . $oRouter->sPath . '?event=deletePuff&amp;deletePuff=' . $aEntry['puffId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
					);
					
					if( PUFF_LAYOUT_EDIT === false || PUFF_LAYOUT_EDIT === true && count( $GLOBALS['puffLayout'] ) <= 1 ) {
						unset( $aRow['puffLayoutKey'] );
					}
					
					$oOutputHtmlTable->addBodyEntry( $aRow, array('id' => 'sortPuff_' . $aEntry['puffId']) );
					
					$aSortGroup[] = $sKey;
				}
			} else {
				$oOutputHtmlTable->addBodyEntry( array(
						'puffSort' => array(
							'value' => '<strong>' . _( 'There are no items to show' ) . '</strong>',
							'attributes' => array(
								'colspan' => 8
							)
						)
				) );
			}
			
			$sOutput .= '
				<h3>' . $aGroup['name'] . '</h3>
				' . $oOutputHtmlTable->render( array( 'class' => $sKey ) );
		}
		
	} else {
		/**
		 * Normal
		 */
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $oPuff->oDao->getDataDict() );
		$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
			'puffControls' => array(
				'title' => ''
			)
		) );
		
		foreach( $aPuffs as $aEntry ) {
			if( $aEntry['puffPublishStart'] === '0000-00-00 00:00:00' ) $aEntry['puffPublishStart'] = '-';
			if( $aEntry['puffPublishEnd'] === '0000-00-00 00:00:00' ) $aEntry['puffPublishEnd'] = '-';
			
			$aRow = array(
				'puffSort' => $aEntry['puffSort'],
				'puffTitleTextId' => '<a href="' . $sEditUrl . '?puffId=' . $aEntry['puffId'] . '" class="ajax">' . htmlspecialchars( $aEntry['puffTitleTextId'] ) . '</a>',
				'puffPublishStart' => substr( $aEntry['puffPublishStart'], 0, 10 ),
				'puffPublishEnd' => substr( $aEntry['puffPublishEnd'], 0, 10 ),
				'puffLayoutKey' => '<span class="' . $aEntry['puffLayoutKey'] . '">' . $GLOBALS['puffLayout'][ $aEntry['puffLayoutKey'] ]['name'] . '</span>',
				'puffStatus' => '<span class="' . $aEntry['puffStatus'] . '">' . $aPuffDataDict['entPuff']['puffStatus']['values'][ $aEntry['puffStatus'] ] . '</span>',
				'puffCreated' => substr( $aEntry['puffCreated'], 0, 16 ),						
				'puffControls' => '
					<a href="' . $sEditUrl . '?puffId=' . $aEntry['puffId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
					<a href="' . $sEditUrl . '?useContent=true&usePuffId=' . $aEntry['puffId'] . '" class="icon iconText iconOverlays"><span>' . _( 'Use as template' ) . '</span></a>
					<a href="' . $oRouter->sPath . '?event=deletePuff&amp;deletePuff=' . $aEntry['puffId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
			);
			
			if( PUFF_LAYOUT_EDIT === false || PUFF_LAYOUT_EDIT === true && count( $GLOBALS['puffLayout'] ) <= 1 ) {
				unset( $aRow['puffLayoutKey'] );
			}
			
			$oOutputHtmlTable->addBodyEntry( $aRow, array('id' => 'sortPuff_' . $aEntry['puffId']) );
			
			$aSortGroup[] = $aEntry['puffLayoutKey'];
		}
		
		$sOutput = $oOutputHtmlTable->render( array( 'class' => $aEntry['puffLayoutKey'] ) );
	}
	
	foreach( $aSortGroup as $sKey ) {
		// Sortable
		$oTemplate->addBottom( array(
			'key' => 'puff' . $sKey . 'Sortable',
			'content' => '
			<script>
				$(".puffTable table.' . $sKey . ' tbody").sortable( {
					update : function () {
						$.get("' . $oRouter->sPath . '", "puffLayoutKey=' . $sKey . '&ajax=true&event=sortPuff&sortPuff=1&" + $(this).sortable("serialize"));
					}
				} );
			</script>'
		) );
	}
	
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
	
}

// Layouts
$aLayouts = array_flip(array_keys( $GLOBALS['puffLayout'] ));
foreach( $aLayouts as $sLayout => $value ) {
	$aLayouts[$sLayout] = $GLOBALS['puffLayout'][ $sLayout ]['name'];
}

// Filter form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oPuff->oDao->getDataDict(), array(
	'attributes' => array( 'class' => 'inline' ),
	'action' => '',	
	'labelSuffix' => ' :',
	'labelRequiredSuffix' => '',
	'data' => $_GET,
	'buttons' => array()
) );
$oOutputHtmlForm->setFormDataDict( array(
	'puffLayoutKey' => array(
		'type' => 'array',
		'title' => _( 'Puff layout' ),
		'values' => array(
			'*' => _( 'All layouts' )
		) + $aLayouts,
		'attributes' => array(
			'onchange' => 'this.form.submit();'
		)
	),
	'frmFilter' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sFilterForm = $oOutputHtmlForm->render();

echo '
	<div class="view puffTable">
		<h1>' . _( 'List of existing puffs' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sEditUrl. '" class="icon iconText iconAdd">' . _( 'Create new puff' ) . '</a>
			</div>
			' . (PUFF_LAYOUT_EDIT === true ? '
			<div class="tool">
				' . $sFilterForm . '
			</div>
			' : '') . '
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oPuff->oDao->setLang( $GLOBALS['langId'] );

$oTemplate->addStyle( array(
	'key' => '',
	'content' => '
		.view.puffTable form.inline label { display: inline-block !important; }
		.view.puffTable form.inline .select.input { display: inline-block !important; }
		
		.view.puffTable table tbody tr td.puffSort { width: 5%; }
		.view.puffTable table tbody tr td.puffTitleTextId { width: 15%; }
		.view.puffTable table tbody tr td.puffPublishStart { width: 12.5%; }
		.view.puffTable table tbody tr td.puffPublishEnd { width: 12.5%; }
		.view.puffTable table tbody tr td.puffLayoutKey { width: 14.5%; }
		.view.puffTable table tbody tr td.puffStatus { width: 8%; }
		.view.puffTable table tbody tr td.puffCreated { width: 12.5%; }
		.view.puffTable table tbody tr td.puffControls { width: 20%; }
	'
) );