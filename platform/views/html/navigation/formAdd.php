<?php

// Custom translation
$sToprowNav = _( 'Toprow' );

$aErr = array();

require_once PATH_FUNCTION . '/fData.php';
$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->oDao->setLang( $GLOBALS['langIdEdit'] );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oNavigation->oDao->setLang( $GLOBALS['langIdEdit'] );


if( $oUser->oAclGroups->isAllowed('superuser') ) {
	$aNavigationGroups = (array) $oNavigation->readGroup();
	if( !empty($aNavigationGroups) ) $aNavigationGroups += array_combine( $aNavigationGroups, $aNavigationGroups );

	// Add usergroups without existing entries
	$oUserManager = clRegistry::get( 'clUserManager' );
	$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupTitle' );

	// Combined usergroups and navigationgroups
	$aGroups = $aUserGroups;
	foreach( $aNavigationGroups as $sGroup ) {
		if( !array_key_exists( $sGroup, $aUserGroups ) ) {
			$aGroups[$sGroup] = ucfirst( $sGroup );
		}
	}
	$aNavigationGroups = $aGroups;

	if( !empty($_GET['groupKey']) && !array_key_exists($_GET['groupKey'], $aNavigationGroups) ) {
		$aNavigationGroups[$_GET['groupKey']] = $_GET['groupKey'];
	}

	// Form add group
	$oOutputHtmlForm->init( array(
			'entAddGroup' => array(
				'groupKey' => array(
					'type' => 'string',
					'title' => _( 'Group' )
				)
			)
		), array(
			'action' => '',
			'attributes' => array( 'class' => 'inline' ),
			'data' => array(),
			'errors' => $aErr,
			'method' => 'get',
			'buttons' => array(
				'submit' => _( 'Create' )
		)
	) );
	$sGroupAddForm = $oOutputHtmlForm->render();

	asort($aNavigationGroups);
} else {
	$sGroupAddForm = '';

	$oUserManager = clRegistry::get( 'clUserManager' );
	$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupTitle' );

	$aNavigationGroups = array();
	foreach( array_keys( $oUser->oAclGroups->aAcl ) as $groupKey ) {
		$aNavigationGroups[ $groupKey ] = $aUserGroups[ $groupKey ];
	}

	// Inject navigation groups that aren't real usergroups
	$aCustomNavigationGroups = (array) $oNavigation->readGroup();
	foreach( $aCustomNavigationGroups as $iKey => $sGroupKey ) {
		if( array_key_exists( $sGroupKey, $aUserGroups ) ) {
			// Real usergroup
			unset( $aCustomNavigationGroups[ $iKey] );
		} else {
			// Pseudo usergroup
		}
	}

	if( !empty($aCustomNavigationGroups) ) {
		// Use names as array keys and merge with navigationGroups
		$aCustomNavigationGroups = array_combine(
			array_values($aCustomNavigationGroups),
			array_values($aCustomNavigationGroups)
		);
		$aNavigationGroups = array_merge( $aNavigationGroups, $aCustomNavigationGroups );
	}

	asort($aNavigationGroups);
}

if( empty($_GET['groupKey']) || !array_key_exists($_GET['groupKey'], $aNavigationGroups) ) {
	$_GET['groupKey'] = key( $aNavigationGroups );
}

echo '
<div class="view navigation formAdd">
	<h1>' . _( 'Navigation' ) . '</h1>

	' . $sGroupAddForm . '

	<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">';

if( !empty($aNavigationGroups) ) {	
	echo '
		<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">';
	foreach( $aNavigationGroups as $key => $value ) {		
		echo '
			<li class="ui-state-default ui-corner-top' . ( $_GET['groupKey'] == $key ? ' ui-tabs-selected ui-state-active' : '' ) . '"><a href="?groupKey=' . $key . '">' . _( $value ) . '</a></li>';
	}
	echo '
		</ul>';
}

echo '
		<div id="navigationContent" class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">';

if( !empty($_GET['groupKey']) ) {
	$oNavigation->setGroupKey( $_GET['groupKey'] );

	if( !empty($_POST['frmNavigationAdd']) ) {
		$_POST['navigationGroupKey'] = $_GET['groupKey'];

		// Update
		if( !empty($_GET['navigationId']) && ctype_digit($_GET['navigationId']) ) {
			$iNavigationId = $_GET['navigationId'];
			
			if( $oNavigation->update( $_GET['navigationId'], $_POST ) !== false ) {
				$oNotification->set( array(
					'dataSaved' => _( 'The data has been saved' )
				) );
			}
			$aErr = clErrorHandler::getValidationError( 'updateNavigation' );
		// Create
		} else {
			if( $iNavigationId = $oNavigation->create($_POST) ) {
				$sNavigationRelation = $_POST['navigationRelation'];
				$sNavigationTarget = $_POST['navigationTarget'];
				$_POST = null; //reset post data

				$oNotification->set( array(
					'dataSaved' => _( 'The data has been saved' )
				) );
				$oTemplate->addBottom( array(
					'key' => 'autoFocus',
					'content' => '
					<script type="text/javascript">
						$( function() {
							$("#navigationTitle").focus();
						} );
					</script>'
				) );
			} else {
				$aErr = clErrorHandler::getValidationError( 'createNavigation' );
			}
		}
		
		/**
		 * Image
		 */
		if( !empty($_FILES) && $_FILES['imageUpload']['error'] == UPLOAD_ERR_OK ) {
			$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
			$oImage->setParams( array(
				'parentType' => $oNavigation->sModuleName
			) );
			$oImage->deleteByParent( $iNavigationId, $oNavigation->sModuleName );
			$aErr = $oImage->createWithUpload( array(
				'allowedMime' => array(
					'image/jpeg' => 'jpg',
					'image/pjpeg' => 'jpg',
					'image/gif' => 'gif',
					'image/png' => 'png',
					'image/x-png' => 'png'
				),
				'key' => 'imageUpload'
			), $iNavigationId );
			if( empty($aErr) ) {
				$aImage = current( $oImage->readByParent( $iNavigationId, '*' ) );
				
				if( $oNavigation->update( $iNavigationId, array(
					'navigationImageSrc' => '/images/custom/' . $oNavigation->sModuleName . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension']
				) ) !== false ) {
					
				}
			}
		}

	}

	// Move
	if( !empty($_GET['navigationId']) && ctype_digit($_GET['navigationId']) && !empty($_POST['frmNavigationMove']) ) {
		$oNavigation->move( $_GET['navigationId'], $_POST );
		$oRouter->redirect( $oRouter->sPath . '?groupKey=' . $_GET['groupKey'] );
	}

	// List
	$aData = $oNavigation->read( array(
		'navigationId',
		'navigationTitle',
		'navigationLeft',
		'navigationRight'
	) );
	$aNavigationItems = array(
		'' => '[' . _( 'Root' ) . ']'
	);

	foreach( $aData as $entry ) {
		$aNavigationItems[$entry['navigationId']] = str_repeat('&emsp;', $entry['depth'] + 1) . $entry['navigationTitle'];
	}


	// Edit
	if( !empty($_GET['navigationId']) && ctype_digit($_GET['navigationId']) ) {
		$aNavigationData = $oNavigation->read( array(
			'navigationId',
			'navigationTitle',
			'navigationUrl',
			'navigationOpenIn',
			'navigationLeft',
			'navigationRight',
			'navigationImageSrc'
		), $_GET['navigationId'] );
		$aNavigationData = !empty($aNavigationData) ? current( $aNavigationData ) : array();
		$sTitle = _( 'Edit menu' );
		
		// Image
		$oImage = clRegistry::get( 'clImage', PATH_MODULE . '/image/models' );
		$oImage->setParams( array('parentType' => $oNavigation->sModuleName) );
		$aImage = current( $oImage->readByParent( $aNavigationData['navigationId'], '*' ) );
		
		// Add form
		$oOutputHtmlForm->init( $oNavigation->oDao->getDataDict(), array(
			'attributes' => array('class' => 'marginal'),
			'action' => '',
			'data' => $aNavigationData,
			'errors' => $aErr,
			'labelSuffix' => ':',
			'method' => 'post',
			'buttons' => array(
				'submit' => _( 'Save' )
			),
		) );
		$aFormDict = array(
			'navigationTitle' => array(
				'labelAttributes' => array(
					'title' => _( 'The title your new link will have' )
				)
			),
			'navigationUrl' => array(
				'labelAttributes' => array(
					'title' => _( 'Where the link leads to. This can be a website (e.g. http://www.example.com) or a page on your website (/startpage).' )
				)
			),
			'navigationOpenIn' => array(),
			'navigationImageSrc' => array(),
			'imageUpload' => array(
				'type' => 'upload',
				'attributes' => array(
					'class' => 'multi',
					'accept' => 'jpg|jpeg|gif|png',
					'id' => 'templateImageUploader'
				),
				'title' => _( 'Picture' ),
				'suffixContent' => !empty($aImage) ? '
					<div class="image">
						<img src="' . sprintf( '/images/custom/Navigation/%s.%s', $aImage['imageId'], $aImage['imageFileExtension'] ) . '" alt="" width="30" />
						<p><a href="#" class="editableImage icon iconImage" data-module-name="' . $aImage['imageParentType'] . '" data-image-id="' . $aImage['imageId'] . '" data-image-extension="' . $aImage['imageFileExtension'] . '">' . _( 'Edit image' ) . '</a></p>
						<p><a href="' . $oRouter->sPath . '?event=deleteImage&amp;deleteImage=' . $aImage['imageId'] . '&amp;' . stripGetStr( array('event', 'imageCreateThumbnail', 'deleteImage') ) . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '"><span>' . _( 'Delete' ) . '</span></a></p>
					</div>
				' : ''
			),
			'frmNavigationAdd' => array(
				'type' => 'hidden',
				'value' => true
			)
		);
		if( NAVIGATION_IMAGE !== true ) {
			unset( $aFormDict['imageUpload'], $aFormDict['navigationImageSrc'] );
		}
		$oOutputHtmlForm->setFormDataDict( $aFormDict );
		$sNavigationForm = $oOutputHtmlForm->render();

		// Move form
		$oOutputHtmlForm->init( $oNavigation->oDao->getDataDict(), array(
			'attributes' => array('class' => 'marginal'),
			'action' => '',
			'data' => $aNavigationData,
			'errors' => $aErr,
			'labelSuffix' => ':',
			'method' => 'post',
			'buttons' => array(
				'submit' => _( 'Move' )
			),
		) );
		$oOutputHtmlForm->setFormDataDict( array(
			'navigationRelation' => array(
				'title' => _( 'Relation' ),
				'labelAttributes' => array(
					'title' => _( 'This combined with the &quot;target&quot; input will define the position of the link' )
				),
				'type' => 'array',
				'values' => array(
					'firstChild' => _( 'First beneath target' ),
					'lastChild' => _( 'Last beneath target' ),
					'prevSibling' => _( 'Before target' ),
					'nextSibling' => _( 'After target' )
				),
				'defaultValue' => (!empty($sNavigationRelation) ? $sNavigationRelation : 'lastChild')
			),
			'navigationTarget' => array(
				'title' => _( 'Target' ),
				'labelAttributes' => array(
					'title' => _( 'This combined with the &quot;relation&quot; input will define the position of the link' )
				),
				'type' => 'array',
				'values' => $aNavigationItems,
				'defaultValue' => (!empty($sNavigationTarget) ? $sNavigationTarget : '')
			),
			'frmNavigationMove' => array(
				'type' => 'hidden',
				'value' => true
			)
		) );
		$sMoveNavigationForm = $oOutputHtmlForm->render();

	} else {
		$aNavigationData = $_POST;
		$sTitle = _( 'Add menu' );

		// Add form
		$oOutputHtmlForm->init( $oNavigation->oDao->getDataDict(), array(
			'attributes' => array('class' => 'marginal'),
			'action' => '',
			'data' => $aNavigationData,
			'errors' => $aErr,
			'labelSuffix' => ':',
			'method' => 'post',
			'buttons' => array(
				'submit' => _( 'Save' )
			),
		) );
		$aFormDict = array(
			'navigationRelation' => array(
				'title' => _( 'Relation' ),
				'labelAttributes' => array(
					'title' => _( 'This combined with the &quot;target&quot; input will define the position of the link' )
				),
				'type' => 'array',
				'values' => array(
					'firstChild' => _( 'First beneath target' ),
					'lastChild' => _( 'Last beneath target' ),
					'prevSibling' => _( 'Before target' ),
					'nextSibling' => _( 'After target' )
				),
				'defaultValue' => (!empty($sNavigationRelation) ? $sNavigationRelation : 'lastChild')
			),
			'navigationTarget' => array(
				'title' => _( 'Target' ),
				'labelAttributes' => array(
					'title' => _( 'This combined with the &quot;relation&quot; input will define the position of the link' )
				),
				'type' => 'array',
				'values' => $aNavigationItems,
				'defaultValue' => (!empty($sNavigationTarget) ? $sNavigationTarget : '')
			),
			'navigationTitle' => array(
				'labelAttributes' => array(
					'title' => _( 'The title your new link will have' )
				)
			),
			'navigationUrl' => array(
				'labelAttributes' => array(
					'title' => _( 'Where the link leads to. This can be a website (e.g. http://www.example.com) or a page on your website (/startpage).' )
				)
			),
			'navigationOpenIn' => array(),
			'navigationImageSrc' => array(),
			'imageUpload' => array(
				'type' => 'upload',
				'attributes' => array(
					'class' => 'multi',
					'accept' => 'jpg|jpeg|gif|png',
					'id' => 'templateImageUploader'
				),
				'title' => _( 'Picture' )
			),
			'frmNavigationAdd' => array(
				'type' => 'hidden',
				'value' => true
			)
		);
		if( NAVIGATION_IMAGE !== true ) {
			unset( $aFormDict['imageUpload'], $aFormDict['navigationImageSrc'] );
		}
		$oOutputHtmlForm->setFormDataDict( $aFormDict );
		$sNavigationForm = $oOutputHtmlForm->render();
	}

	/**
	 * Todo: Need ajax autocomplete for large route table
	 */
	$oRouter->oDao->setCriterias( array(
		'routePathLangId' => array(
			'type' => '=',
			'fields' => 'routePathLangId',
			'value' => $GLOBALS['langIdEdit']
		)
	) );
	$aData = $oRouter->read( 'routePath' );
	$aRoutes = array();
	foreach( $aData as $entry ) {
		$aRoutes[] = $entry['routePath'];
	}
	$oRouter->oDao->sCriterias = '';

	$oTemplate->addBottom( array(
		'key' => 'leakageAutoComplete',
		'content' => '
		<script type="text/javascript">
			$("#navigationUrl").autocomplete({
				source: ["' . implode( '", "', $aRoutes ) . '"],
				minLength: 0
			});
		</script>'
	) );

	echo '<div id="navigationAdd" class="col col-two col-first">';

		echo '
			<div class="data form">
				<h2>' . $sTitle . '</h2>
				' . $sNavigationForm . '
			</div>
		';

		if(!empty($sMoveNavigationForm)) {
			echo '
				<hr />
				<div class="move form">
					<h2>' . _('Move') . '</h2>
					' . $sMoveNavigationForm . '
				</div>
			';
		}

	echo '</div>';

	echo '
		<div id="navigationList" class="col col-two col-last">
			' . $oLayout->renderView( 'navigation/listEdit.php' ) . '
		</div>
	';

}

echo '
		</div>
	</div>
</div>';

$oLayout->oDao->setLang( $GLOBALS['langId'] );
$oNavigation->oDao->setLang( $GLOBALS['langId'] );
