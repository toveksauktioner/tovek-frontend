<?php

$oAdminMessage = clRegistry::get( 'clAdminMessage', PATH_MODULE . '/adminMessage/models' );
$oAdminMessage->oDao->setLang( $GLOBALS['langIdEdit'] );

/**
 * Sort
 */
clFactory::loadClassFile( 'clOutputHtmlSorting' );
$oSorting = new clOutputHtmlSorting( $oAdminMessage->oDao, array(
	'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('messageCreated' => 'ASC') )
) );
$oSorting->setSortingDataDict( array(
	'messageLabel' => array(),
	'messageTitleTextId' => array(),
	'messageStatus' => array(),
	'messageCreated' => array(),
) );

/**
 * Data
 */
$aMessages = $oAdminMessage->readAll();

$sEditUrl = $oRouter->getPath( 'superAdminMessageAdd' );

$sOutput = '';

if( !empty($aMessages) ) {
	clFactory::loadClassFile( 'clOutputHtmlTable' );
	$oOutputHtmlTable = new clOutputHtmlTable( $oAdminMessage->oDao->getDataDict() );
	$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
		'messageControls' => array(
			'title' => ''
		)
	) );
	
	foreach( $aMessages as $aMessage ) {
		$aRow = array(
			'messageLabel' => '<a href="' . $sEditUrl . '?messageId=' . $aMessage['messageId'] . '" class="ajax">' . htmlspecialchars( $aMessage['messageLabel'] ) . '</a>',			
			'messageTitleTextId' => '<a href="' . $sEditUrl . '?messageId=' . $aMessage['messageId'] . '" class="ajax">' . htmlspecialchars( $aMessage['messageTitleTextId'] ) . '</a>',
			'messageStatus' => '<span class="' . $aMessage['messageStatus'] . '">' . $oAdminMessage->oDao->aDataDict['entAdminMessage']['messageStatus']['values'][ $aMessage['messageStatus'] ] . '</span>',
			'messageCreated' => substr( $aMessage['messageCreated'], 0, 16 ),
			'messageControls' => '
				<a href="' . $sEditUrl . '?messageId=' . $aMessage['messageId'] . '" class="ajax icon iconEdit iconText">' . _( 'Edit' ) . '</a>
				<a href="' . $oRouter->sPath . '?event=deleteAdminMessage&amp;deleteAdminMessage=' . $aMessage['messageId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '">' . _( 'Delete' ) . '</a>'
		);
		$oOutputHtmlTable->addBodyEntry( $aRow );
	}

	$sOutput = $oOutputHtmlTable->render();
	
} else {
	$sOutput = '<strong>' . _( 'There are no items to show' ) . '</strong>';
}

echo '
	<div class="view adminMessage tableEdit">
		<h1>' . _( 'Admin messages' ) . '</h1>
		<section class="tools">
			<div class="tool">
				<a href="' . $sEditUrl . '" class="icon iconText iconAdd">' . _( 'New message' ) . '</a>
			</div>
		</section>
		<section>
			' . $sOutput . '
		</section>
	</div>';

$oAdminMessage->oDao->setLang( $GLOBALS['langId'] );