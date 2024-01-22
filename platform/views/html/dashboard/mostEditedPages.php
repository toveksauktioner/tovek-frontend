<?php

$iEntries = 3;

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oUser->oAcl );

$sUrlLayoutEdit = $oRouter->getPath( 'adminInfoContentPageAdd' );
$sUrlLayoutAclUserGroup = $oRouter->getPath( 'adminInfoContentUserGroup' );
$sUrlLayoutCss = $oRouter->getPath( 'adminLayoutCss' );

$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$oInfoContent->oDao->setLang( $GLOBALS['langIdEdit'] );

// Language support
$oLayout->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );

$aInfoContentDataDict = $oInfoContent->oDao->getDataDict('entInfoContent');
$aLayoutOutputDataDict = array(
	'layoutTitleTextId' => array(),
	'routePath' => array( 'title' => _( 'Address' ), 'notSortable' => true ),
	'contentStatus' => array( 'title' => _( 'Status' ), 'notSortable' => true ),
	'contentUpdated' => array( 'title' => _( 'Last edited' ), 'notSortable' => true ),
);

clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oLayout->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('layoutUpdated' => 'DESC') )
) );
$oSorting->setSortingDataDict( $aLayoutOutputDataDict );

$oLayout->oDao->setEntries( $iEntries );

$aLayouts = $oLayout->readCustom();

// Default lang for Title when missing
$bSecondaryLang = false;
$aLayoutsViewLang = array();
foreach( $aLayouts as $aLayout ) {
	if( empty($aLayout['layoutTitleTextId']) ) {
		$bSecondaryLang = true;

		$oLayout->oDao->setLang( (!empty($_GET['viewLang']) ? $_GET['viewLang'] : $GLOBALS['langId']) );
		$oRouter->oDao->setLang( (!empty($_GET['viewLang']) ? $_GET['viewLang'] : $GLOBALS['langId']) );

		$aLayoutsViewLang[$aLayout['layoutKey']] = current( $oLayout->readCustom( 'layoutTitleTextId', $aLayout['layoutKey'] ) );

		$oLayout->oDao->setLang( $GLOBALS['langIdEdit'] );
		$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
	}
}

if( $bSecondaryLang === true ) {
	// Selection of view language
	$oLocale = clRegistry::get( 'clLocale' );
	$aLocales = $oLocale->read();
	$aLocales = array('null' => !empty($_GET['viewLang']) && ctype_digit($_GET['viewLang']) ? _( 'None' ) : _( 'Select' )) + arrayToSingle( $aLocales, 'localeId', 'localeTitle' );

	$aFormDataDict = array(
		'entLocaleSelect' => array(
			'viewLang' => array(
				'type' => 'array',
				'values' => $aLocales,
				'title' => _( 'Secondary language' ),
				'attributes' => array(
					'onchange' => 'this.form.submit();'
				)
			)
		)
	);
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $aFormDataDict, array(
		'labelSuffix' => ':',
		'data' => array(
			'viewLang' => !empty($_GET['viewLang']) ? $_GET['viewLang'] : $GLOBALS['langId']
		),
		'buttons' => array()
	) );
	$sLocalesForm = $oOutputHtmlForm->render();
}

echo '
	<div class="view dashboard mostEditedPages">
		<h3>' . sprintf( _( '%s most frequently edited pages' ), $iEntries ) . '</h3>';
		
if( !empty($aLayouts) ) {
	// Read layout routes
	foreach( $aLayouts as $key => $aValues ) {
		$aLayouts[$key]['routePath'] = $oRouter->getPath( $aValues['layoutKey'] );
	}

	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oLayout->oDao->getDataDict(), array('attributes' => array('cellspacing' => '0')) );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'layoutControls' => array(
			'title' => ''
		)
	) );

	if( !empty($_GET['grouping']) && $_GET['grouping'] == 'menu' ) {
		$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
		$oNavigation->setGroupKey( 'guest' );
		$oNavigation->oDao->setLang( $GLOBALS['langIdEdit'] );

		$aTree = $oNavigation->read( array(
			'navigationUrl',
			'navigationLeft',
			'navigationRight'
		) );
		$aTreeRoutes = arrayToSingle( $aTree, null, 'navigationUrl' );

		// Remove pages that are in a menu
		$aPagesByMenu = array();
		foreach( $aLayouts as $key => $aValues ) {
			if( in_array($aValues['routePath'], $aTreeRoutes ) ) {
				$aPagesByMenu[] = $aValues;
				unset( $aLayouts[$key] );
			}
		}

		echo '
		<div>
			<h3>' . _( 'Pages grouped by menu' ) . '</h3>';

		if( !empty($aPagesByMenu) ) {
			// Reorder to match menu
			$aPagesByMenuTmp = array();
			foreach( $aPagesByMenu as $key => $aValues ) {
				// Find key in menu and insert into temp array
				foreach( $aTree as $treeKey => $treeValues ) {
					if( $treeValues['navigationUrl'] == $aValues['routePath'] ) {
						$aPagesByMenuTmp[ $treeKey ] = $aValues + array( 'depth' => $treeValues['depth'] );
					}
				}
			}
			ksort($aPagesByMenuTmp);
			$aPagesByMenu = $aPagesByMenuTmp;
			unset($aPagesByMenuTmp);

			$oOutputHtmlTableMenu = new clOutputHtmlTable( $oLayout->oDao->getDataDict(), array('attributes' => array('cellspacing' => '0')) );
			$oOutputHtmlTableMenu->setTableDataDict( array(
				#'layoutKey' => array(),
				'layoutTitleTextId' => array(),
				'routePath' => array(
					'title' => _( 'Address' )
				),
				'contentStatus' => array(
					'title' => _( 'Status' )
				),
				'contentUpdated' => array(
					'title' => _( 'Updated' )
				),
				'layoutControls' => array(
					'title' => ''
				)
			) );

			$iCount = 1;
			foreach( $aPagesByMenu as $entry ) {
				// Read view data and filter out views that is not infoContent
				$oLayout->oDao->setCriterias( array() );
				$aViewData = $oLayout->readSectionsAndViews($entry['layoutKey']);
				if( count($aViewData) == 1 ) {
					$iViewId = $aViewData[0]['viewId'];
				} else {
					foreach( $aViewData as $key => $value ) {
						if( $value['viewModuleKey'] == 'infoContent' && $value['viewFile'] == 'show.php' ) {
							$iViewId = $value['viewId'];
							break;
						}
					}
				}

				$aInfoContent = current( $oInfoContent->readByView( $iViewId, array(
					'contentId',
					'contentUpdated',
					'contentStatus'
				) ) );
				$sContentUpdated = substr( $aInfoContent['contentUpdated'], 0, 16 );

				$aAttributes = array();
				if( $iCount % 2 === 0 ) $aAttributes['class'] = 'odd';
				if( empty($entry['layoutTitleTextId']) ) {
					if( empty($aAttributes['class']) ) $aAttributes['class'] = 'defaultLang';
					else $aAttributes['class'] .= ' defaultLang';
				}

				#$row['layoutKey'] = $entry['layoutKey'];
				$row['layoutTitleTextId'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $entry['depth']) . '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . (!empty($entry['layoutTitleTextId']) ? $entry['layoutTitleTextId'] : $aLayoutsViewLang[$entry['layoutKey']]) . '</a>';
				$row['layoutFile'] = $entry['layoutFile'];
				$row['routePath'] = ( !empty($entry['routePath']) ? '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . $entry['routePath'] . '</a>' : '' );
				$row['contentStatus'] = '<span class="' . $aInfoContent['contentStatus'] . '">' . $aInfoContentDataDict['entInfoContent']['contentStatus']['values'][ $aInfoContent['contentStatus'] ] . '</span>';
				$row['contentUpdated'] = $sContentUpdated;
				$row['layoutControls'] = '
				<a href="' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '" class="icon iconText iconEdit"><span>' . _( 'Edit' ) . '</span></a>';
				
				// Is the layout protected or not?
				if( $entry['layoutProtected'] == 'no' ) {
					$row['layoutControls'] .= '<a href="?event=deleteCustomLayout&amp;deleteCustomLayout=' . $entry['layoutKey'] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>';
				} else {
					$row['layoutControls'] .= '<span class="icon iconText iconDelete disabled linkConfirm" title="' . _( 'This page is protected and cannot be deleted. Inactivate if it should not be publicly visible.' ) . '">' . _( 'Delete' ) . '</span>';
				}

				$oOutputHtmlTableMenu->addBodyEntry( $row, $aAttributes );
				++$iCount;
			}

			echo $oOutputHtmlTableMenu->render();
		} else {
			echo '
				<strong>' . _('There are no items to show') . '</strong>';
		}

		echo '
		</div>';

		// Output items that are not in a menu
		echo '
		<h3>' . _( 'Pages outside of menu' ) . '</h3>';
		$iCount = 1;
		foreach( $aLayouts as $entry ) {
			// Read view data and filter out views that is not infoContent
			$oLayout->oDao->setCriterias( array() );
			$aViewData = $oLayout->readSectionsAndViews($entry['layoutKey']);
			if( count($aViewData) == 1 ) {
				$iViewId = $aViewData[0]['viewId'];
			} else {
				foreach( $aViewData as $key => $value ) {
					if( $value['viewModuleKey'] == 'infoContent' && $value['viewFile'] == 'show.php' ) {
						$iViewId = $value['viewId'];
						break;
					}
				}
			}

			$aInfoContent = current( $oInfoContent->readByView( $iViewId, array( 'contentId', 'contentStatus', 'contentUpdated' ) ) );
			$sContentUpdated = substr( $aInfoContent['contentUpdated'], 0, 16 );

			$aAttributes = array();
			if( $iCount % 2 === 0 ) $aAttributes['class'] = 'odd';
			if( empty($entry['layoutTitleTextId']) ) {
				if( empty($aAttributes['class']) ) $aAttributes['class'] = 'defaultLang';
				else $aAttributes['class'] .= ' defaultLang';
			}

			$row['layoutKey'] = $entry['layoutKey'];
			$row['layoutTitleTextId'] = '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . (!empty($entry['layoutTitleTextId']) ? $entry['layoutTitleTextId'] : $aLayoutsViewLang[$entry['layoutKey']]) . '</a>';
			$row['layoutFile'] = $entry['layoutFile'];
			$row['routePath'] = ( !empty($entry['routePath']) ? '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . $entry['routePath'] . '</a>' : '' );
			$row['contentStatus'] = '<span class="' . $aInfoContent['contentStatus'] . '">' . $aInfoContentDataDict['entInfoContent']['contentStatus']['values'][ $aInfoContent['contentStatus'] ] . '</span>';
			$row['contentUpdated'] = $sContentUpdated;
			$row['layoutControls'] = '
			<a href="' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '" class="icon iconText iconEdit"><span>' . _( 'Edit' ) . '</span></a>';

			// Is the layout protected or not?
			if( $entry['layoutProtected'] == 'no' ) {
				$row['layoutControls'] .= '<a href="?event=deleteCustomLayout&amp;deleteCustomLayout=' . $entry['layoutKey'] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>';
			} else {
				$row['layoutControls'] .= '<span class="icon iconText iconDelete disabled linkConfirm" title="' . _( 'This page is protected and cannot be deleted. Inactivate if it should not be publicly visible.' ) . '">' . _( 'Delete' ) . '</span>';
			}

			$oOutputHtmlTable->addBodyEntry( $row, $aAttributes );
			++$iCount;

		}

		echo $oOutputHtmlTable->render();

	} else {
		$iCount = 1;
		foreach( $aLayouts as $entry ) {
			// Read view data and filter out views that is not infoContent
			$oLayout->oDao->setCriterias( array() );
			$oLayout->oDao->setEntries( 0 );
			$aViewData = $oLayout->readSectionsAndViews($entry['layoutKey']);

			if( count($aViewData) == 1 ) {
				$iViewId = $aViewData[0]['viewId'];
			} else {
				foreach( $aViewData as $key => $value ) {
					if( $value['viewModuleKey'] == 'infoContent' && $value['viewFile'] == 'show.php' ) {
						$iViewId = $value['viewId'];
						break;
					}
				}
			}

			$aInfoContent = current( $oInfoContent->readByView( $iViewId, array(
				'contentId',
				'contentStatus',
				'contentUpdated'
			) ) );
			$sContentUpdated = substr( $aInfoContent['contentUpdated'], 0, 16 );

			$aAttributes = array();
			if( $iCount % 2 === 0 ) $aAttributes['class'] = 'odd';
			if( empty($entry['layoutTitleTextId']) ) {
				if( empty($aAttributes['class']) ) $aAttributes['class'] = 'defaultLang';
				else $aAttributes['class'] .= ' defaultLang';
			}

			$row['layoutKey'] = $entry['layoutKey'];
			$row['layoutTitleTextId'] = '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . (!empty($entry['layoutTitleTextId']) ? $entry['layoutTitleTextId'] : $aLayoutsViewLang[$entry['layoutKey']]) . '</a>';
			$row['layoutFile'] = $entry['layoutFile'];
			$row['routePath'] = ( !empty($entry['routePath']) ? '<a href=" ' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '">' . $entry['routePath'] . '</a>' : '' );
			$row['contentStatus'] = '<span class="' . $aInfoContent['contentStatus'] . '">' . $aInfoContentDataDict['entInfoContent']['contentStatus']['values'][ $aInfoContent['contentStatus'] ] . '</span>';
			$row['contentUpdated'] = $sContentUpdated;
			$row['layoutControls'] = '
			<a href="' . $sUrlLayoutEdit . '?layoutKey=' . $entry['layoutKey'] . '" class="icon iconText iconEdit"><span>' . _( 'Edit' ) . '</span></a>';

			// Is the layout protected or not?
			if( $entry['layoutProtected'] == 'no' ) {
				$row['layoutControls'] .= '<a href="?event=deleteCustomLayout&amp;deleteCustomLayout=' . $entry['layoutKey'] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>';
			} else {
				$row['layoutControls'] .= '<span class="icon iconText iconDelete disabled linkConfirm" title="' . _( 'This page is protected and cannot be deleted. Inactivate if it should not be publicly visible.' ) . '">' . _( 'Delete' ) . '</span>';
			}

			$oOutputHtmlTable->addBodyEntry( $row, $aAttributes );
			++$iCount;
		}

		echo '<section>' . $oOutputHtmlTable->render() . '</section>';
	}

} else {
	echo '
		<strong>' . _('There are no items to show') . '</strong>';
}

echo '
	</div>';

$oLayout->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );