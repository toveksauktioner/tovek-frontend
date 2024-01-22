<?php

$aErr = array();

if( !empty($_GET['layoutKey']) ) {

	echo '
	<div class="view layout section">
		<h1>' . _( 'Sections and views' ) . '</h1>';

	$oLayout = clRegistry::get( 'clLayoutHtml' );
	$oLayout->setAcl( $oUser->oAcl );
	$oView = clRegistry::get( 'clViewHtml' );

	// Add view
	if( !empty($_POST['frmAddView']) ) {
		$oLayout->createViewToSection( $_POST['viewId'], $_POST['sectionId'] );
	}

	// Update positions
	if( !empty($_POST['frmViewPosition']) ) {
		$oLayout->updateViewPosition( $_POST['sectionId'], $_POST['position'] );
	}

	// Delete positions
	if( !empty($_GET['action']) && $_GET['action'] == 'deleteViewToSection' && !empty($_GET['viewId']) && !empty($_GET['sectionId']) ) {
		$oLayout->deleteViewToSection( $_GET['viewId'], $_GET['sectionId'] );
		$oRouter->redirect( $oRouter->sPath . '?layoutKey=' . $_GET['layoutKey'] );
	}

	$aData = $oLayout->readSectionsAndViews( $_GET['layoutKey'] );
	if( !empty($aData) ) {
		$aSections = array();
		$aSectionIds = array();
		$oView = clRegistry::get( 'clViewHtml' );
		$oView->oDao->aSorting = array(
			'viewModuleKey' => 'ASC'
		);
		$aViewData = $oView->read( array(
			'viewId',
			'viewModuleKey',
			'viewFile'
		) );
		$aViews = array();
		$aInfoContentViews = array();

		foreach( $aViewData as $entry ) {
			if( $entry['viewModuleKey'] == 'infoContent' ) $aInfoContentViews[] = $entry['viewId'];
			$aViews[$entry['viewId']] = $entry['viewModuleKey'] . ' - ' . $entry['viewFile'];
		}

		if( !empty($aInfoContentViews) ) {
			$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
			$aInfoContentTitles = $oInfoContent->readByView( $aInfoContentViews, array(
				'contentViewId',
				'contentKey'
			) );

			foreach( $aInfoContentTitles as $entry ) {
				$aViews[$entry['contentViewId']] = $entry['contentKey'];
			}
		}

		foreach( $aData as $entry ) {
			if( empty($aSections[$entry['sectionKey']]) ) $aSections[$entry['sectionKey']] = array();
			$aSections[$entry['sectionKey']][] = $entry;
			$aSectionIds[$entry['sectionKey']] = $entry['sectionId'];
		}

		foreach( $aSections as $sSectionKey => $aData ) {

			$aViewToSectionDataDict = array(
				'entViewToSection' => array(
					'viewId' => array(
						'type' => 'array',
						'values' => $aViews,
						'title' => _( 'Add view' )
					),
					'sectionId' => array(
						'type' => 'hidden',
						'value' => $aSectionIds[$sSectionKey]
					),
					'frmAddView' => array(
						'type' => 'hidden',
						'value' => true
					)
				)
			);

			$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
			$oOutputHtmlForm->init( $aViewToSectionDataDict, array(
				'attributes' => array( 'class' => 'marginal' ),
				'action' => '',
				'errors' => $aErr,
				'method' => 'post',
				'labelSuffix' => ':',
				'buttons' => array(
					'submit' => _( 'Add' )
				),
			) );

			echo '
			<section class="layoutSection">
				<h2>' . $sSectionKey . '</h2>';

			clFactory::loadClassFile( 'clOutputHtmlTable' );
			$oOutputHtmlTable = new clOutputHtmlTable( $aViewToSectionDataDict );
			$oOutputHtmlTable->setTableDataDict( array(
				'position' => array(
					'title' => _( 'Position' )
				),
				'view' => array(
					'title' => _( 'View' )
				),
				'controls' => array(
					'title' => ''
				)
			) );

			$iCount = 0;
			foreach( $aData as $entry ) {
				if( !empty($entry['viewId']) ) {
					$row['position'] = clOutputHtmlForm::createInput( 'text', 'position[' . $entry['viewId'] . ']', array('value' => $entry['position'], 'id' => null) );
					$row['view'] = $aViews[$entry['viewId']];
					$row['controls'] = '<a href="?' . stripGetStr( array('event', 'deleteViewToSection') ) . '&amp;action=deleteViewToSection&amp;viewId=' . $entry['viewId'] . '&amp;sectionId=' . $aSectionIds[$sSectionKey] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>';
					$oOutputHtmlTable->addBodyEntry( $row );
					$iCount++;
				}
			}

			echo '
				' . ( !empty($iCount) ? clOutputHtmlForm::createForm(
					'post',
					'',
						$oOutputHtmlTable->render() .
						clOutputHtmlForm::createInput( 'hidden', 'sectionId', array('value' => $aSectionIds[$sSectionKey], 'id' => null) ) .
						clOutputHtmlForm::createInput( 'hidden', 'frmViewPosition', array('value' => true, 'id' => null) ) .
						clOutputHtmlForm::createButton( 'submit', _('Save') ),
					array( 'class' => 'marginal' )
				) : _('There are no items to show') ) . '
				' . $oOutputHtmlForm->render() . '
			</section>
			';

		}
	} else {
		echo '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
	}

	echo '
	</div>';

}
