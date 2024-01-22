<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oUser->oAcl );

$aLayoutOutputDataDict = array(
	'layoutKey' => array(),
	'layoutTitleTextId' => array(),
	'layoutFile' => array(),
	'layoutTemplateFile' => array(),
	'layoutCreated' => array(),
	'layoutUpdated' => array()
);

/**
 * Rename template filename
 */
if( !empty($_POST['frmRename']) ) {
	$oLayout->oDao->oDb->write( "UPDATE entLayout SET layoutTemplateFile = '" . $_POST['newFilename'] . "' WHERE layoutTemplateFile = '" . $_POST['layoutTemplateFile'] . "'" );
	$oRouter->redirect( $oRouter->sPath . '?layoutTemplateFile=' . $_POST['newFilename'] . '&frmFilter=1' );
}

/**
 * Delete multiple layouts at once
 */
if( !empty($_POST['frmDeleteLayout']) && !empty($_POST['layoutKey']) ) {
	foreach( $_POST['layoutKey'] as $sLayoutKey ) {
		$oLayout->delete( $sLayoutKey );
	}
	$oNotification = clRegistry::get( 'clNotificationHandler' );
	$oNotification->set( array(
		'dataDeleted' => _( 'The data has been deleted' )
	) );
}

/**
 * Filter form
 */
if( !empty($_GET['frmFilter']) && !empty($_GET['layoutTemplateFile']) && $_GET['layoutTemplateFile'] != '*' ) {
	$oLayout->oDao->setCriterias( array(
		'layoutTemplateFile' => array(
			'type' => '=',				
			'fields' => 'layoutTemplateFile',
			'value' => $_GET['layoutTemplateFile']
		)
	) );
}

// Set sort
$_GET['sort'] = !empty($_GET['sort']) ? $_GET['sort'] : 'layoutKey';

// Sort
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oLayout->oDao, array(
	'currentSort' => array( $_GET['sort'] => ( !empty($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' ) )
) );
$oSorting->setSortingDataDict( $aLayoutOutputDataDict );

// Data
$aLayouts = $oLayout->read();

// Reset criterias
$oLayout->oDao->sCriterias = null;

$sOutput = '';

if( !empty($aLayouts) && count($aLayouts) > 0 ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oLayout->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( array(
		'layoutSelect' => array(
			'title' => ''
		)
	) + $oSorting->render() + array(
		'layoutControls' => array(
			'title' => ''
		)
	) );

	$sUrlLayoutAdd = $oRouter->getPath( 'superLayoutAdd' );
	$sUrlAclAcoAdd = $oRouter->getPath( 'superAclAcoAdd' );
	$sUrlLayoutCss = $oRouter->getPath( 'superLayoutCss' );

	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	
	$sPrevKeyType = '';
	foreach( $aLayouts as $aEntry ) {
		// Substract first part of the layout key
		preg_match_all( '/[A-Z]/', $aEntry['layoutKey'], $aMatches, PREG_OFFSET_CAPTURE );
		$sKeyType = substr( $aEntry['layoutKey'], 0, $aMatches[0][0][1] );
		
		if( $_GET['sort'] == 'layoutKey' && $sKeyType != $sPrevKeyType ) {
			$aRow = array(
				'layoutSelect' => '',
				'layoutKey' => array(
					'value' => '<span>'. ucfirst( $sKeyType ) . '</span>',
					'attributes' => array(
						'colspan' => 7
					)
				)
			);
			$oOutputHtmlTable->addBodyEntry( $aRow, array('class' => 'typeLabel') );
		}
		$sPrevKeyType = $sKeyType;
		
		
		$iViewCount = 0;
		$aViewData = $oLayout->readSectionsAndViews( $aEntry['layoutKey'] );
		foreach( $aViewData as $aViewEntry ) {
			if( !empty($aViewEntry['viewFile']) ) {
				++$iViewCount;
			}
		}

		$aRowClass = array();

		if( $iViewCount === 0 ) {
			$aRowClass[] = 'noViews';
		}

		$sCreated	= substr( $aEntry['layoutCreated'], 0, 10 );
		if( $sCreated == '0000-00-00' ) $sCreated = '';

		$sUpdated	= substr( $aEntry['layoutUpdated'], 0, 10 );
		if( $sUpdated == '0000-00-00' ) $sUpdated = '';

		$aRow = array(
			'layoutSelect' => $oOutputHtmlForm->createInput( 'checkbox', 'layoutKey[]', array('value' => $aEntry['layoutKey']) ),
			'layoutKey' => '<a href="' . $sUrlLayoutAdd . '?layoutKey=' . $aEntry['layoutKey'] . '">' . $aEntry['layoutKey'] . '</a>',
			'layoutTitleTextId' => wordStr( $aEntry['layoutTitleTextId'], 60, ' [...]' ),
			'layoutFile' => $aEntry['layoutFile'],
			'layoutTemplateFile' => $aEntry['layoutTemplateFile'],
			'layoutCreated' => $sCreated,
			'layoutUpdated' => $sUpdated,
			'layoutControls' => '
				<a href="' . $sUrlAclAcoAdd . '?aclType=layout&amp;acoKey=' . $aEntry['layoutKey'] . '" class="icon iconLock"><span>' . _( 'ACL' ) . '</span></a>
				<a href="' . $sUrlLayoutCss . '?layoutKey=' . $aEntry['layoutKey'] . '" class="icon iconCss"><span>' . _( 'Css' ) . '</span></a>
				<a href="' . $sUrlLayoutAdd . '?layoutKey=' . $aEntry['layoutKey'] . '" class="icon iconEdit"><span>' . _( 'Edit' ) . '</span></a>
				<a href="?event=deleteLayout&amp;deleteLayout=' . $aEntry['layoutKey'] . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a>'
		);

		$oOutputHtmlTable->addBodyEntry( $aRow, array('class' => implode(' ', $aRowClass)) );
	}

	$sForm = $oOutputHtmlTable->render();
	$sForm .= '<div class="hidden">' . $oOutputHtmlForm->createInput( 'hidden', 'frmDeleteLayout', array('value' => 1) ) . '</div>';
	$sForm .= '
		<p class="buttons">
			' . $oOutputHtmlForm->createButton( 'submit', _( 'Delete selected' ) ) . '
			' . $oOutputHtmlForm->createButton( 'submit', _( 'Mark all yellow' ), array('id' => 'markAllYellow') ) . '
		</p>';

	$sOutput = $oOutputHtmlForm->createForm( 'post', '', $sForm );

} else {
	$sOutput = '
		<strong>' . _( 'There are no items to show' ) . '</strong>';
}

$aTemplates = scandir( PATH_TEMPLATE );
unset( $aTemplates[0], $aTemplates[1] );

// Filter form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oLayout->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inline' ),
	'data' => $_GET,
	'buttons' => array()
) );
$oOutputHtmlForm->setFormDataDict( array(
	'layoutTemplateFile' => array(
		'type' => 'array',
		'title' => _( 'Template file' ),
		'values' => array(
			'*' => _( 'All template files' )
		) + array_combine( $aTemplates, $aTemplates ),
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

// Rename form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $oLayout->oDao->getDataDict(), array(
	'action' => '',
	'attributes' => array( 'class' => 'inline' ),
	'method' => 'post',
	'data' => array(),
	'buttons' => array(
		'submit' => _( 'Switch' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'layoutTemplateFile' => array(
		'type' => 'array',
		'title' => _( 'Template file' ),
		'values' => array(
			'*' => _( 'From' )
		) + array_combine( $aTemplates, $aTemplates ),
	),
	'newFilename' => array(
		'type' => 'array',
		'title' => _( 'Template file' ),
		'values' => array(
			'*' => _( 'To' )
		) + array_combine( $aTemplates, $aTemplates ),
	),
	'frmRename' => array(
		'type' => 'hidden',
		'value' => true
	)
) );
$sRenameForm = $oOutputHtmlForm->render();

echo '
	<div class="view layout table">
		<h1>' . _( 'Layouts' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sUrlLayoutAdd . '" class="icon iconText iconAdd">' . _( 'Create' ) . '</a>
			</div>
			<div class="tool">
				' . $sFilterForm . '
			</div>
			<div class="tool">
				<span class="label">' . _( 'Switch template file' ) . ':</span>
				' . $sRenameForm . '
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oTemplate->addBottom( array(
	'key' => 'jsCustomSelectedMarker',
	'content' => '
	<script type="text/javascript">
		$(".checkbox").change( function() {
			var eTr = $(this).parent("td").parent("tr");

			if( $(this).is(":checked") ) {
				$(eTr).css("background", "#fccac3");
			} else {
				$(eTr).css("background", "none");
			}
		} );
		$("#markAllYellow").click( function(event) {
			event.preventDefault();
			$.each( $("tr.noViews td .checkbox"), function( key, value ) {
				if( $(this).is(":checked") ) {
					$(this).attr("checked", "");
					$("#markAllYellow").html( "' . _( 'Mark all yellow' ) . '" );
				} else {
					$(this).attr("checked", "checked");
					$("#markAllYellow").html( "' . _( 'Unmark all yellow' ) . '" );
				}
			} );
		} );
	</script>'
) );