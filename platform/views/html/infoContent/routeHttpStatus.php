<?php

if( !empty($_GET['layoutKey']) ) {
	$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
	$sLayoutRoute = current($oRouter->readByLayout( $_GET['layoutKey'], 'routePath' ));
	if( !empty($sLayoutRoute) ) {
		
		if( isset($_POST['frmHttpStatusAdd']) ) {
			die("test");
		}
		
		$aErr = array();
		
		$oOutputHtmlForm = clFactory::create( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( $oRouter->oDao->getDataDict(), array(
			'action' => '',
			'attributes' => array(
				'id' => 'frmHttpStatusAdd'
			),
			'data' => $_POST,
			'errors' => $aErr,
			'labelSuffix' => ':',
			'method' => 'post',
			'buttons' => array(
				'submit' => _( 'Add' )
			)
		) );
		$oOutputHtmlForm->setFormDataDict( array(
			'statusRoutePath' => array(),
			'statusCode' => array(),
			'statusData' => array(),
			'frmHttpStatusAdd' => array(
				'type' => 'hidden',
				'value' => true
			)
		) );
				
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $oRouter->oDao->getDataDict() );
		$oOutputHtmlTable->setTableDataDict( array(
			'routePath' => array(),
			'statusCode' => array(),
			'routeControls' => array()			
		) );
		
		$aStatuses = $oRouter->oDao->readHttpStatusByLayout($_GET['layoutKey'], array(
			'statusRoutePath',
			'statusCode'
		) );
		if( !empty($aStatuses) ) {
			foreach( $aStatuses as &$entry ) {
				$oOutputHtmlTable->addBodyEntry( array(
					'routePath' => $entry['statusRoutePath'],
					'statusCode' => $entry['statusCode'],
					'routeControls' => ''
				) );
			}
		} else {
			$oOutputHtmlTable->addBodyEntry( array(
				'routePath' => _('There are no items to show'),
				'routeControls' => ''
			) );
		}
		$sStatusTable = $oOutputHtmlTable->render();
		
		echo '
			<fieldset class="fieldGroup routeHttpStatus">
				<legend><span>' . _( 'More routes' ) . '</span></legend>
				<a class="toggleShow icon iconText iconAdd" href="#frmHttpStatusAdd">' . _('Add extra route') . '</a>
				' . $oOutputHtmlForm->render() . '
				' . $sStatusTable . '
			</fieldset>';
	}
}