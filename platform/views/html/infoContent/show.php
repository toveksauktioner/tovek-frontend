<?php

// Preview
if( !empty($_SESSION['infoContentPreview']) ) {	
	foreach( $_SESSION['infoContentPreview'] as $sPreview ) {
		echo '
				<div class="infoContent">
					' . $sPreview . '
				</div>';
	}
	unset($_SESSION['infoContentPreview']);
	return;
}

$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$sInfoContent = $oInfoContent->readByView( $this->iCurrentViewId, array(
	'contentTextId',
	'contentStatus'
) );

if( !empty($sInfoContent) ) {
	// Handle status and preview
	switch( $sInfoContent[0]['contentStatus'] ) {
		case 'active':
		default:
			$sInfoContent = $sInfoContent[0]['contentTextId'];
			break;
		
		case 'preview':
			$oUser = clRegistry::get( 'clUser' );
			if( array_key_exists('admin', $oUser->aGroups) ) {
				$oTemplate = clRegistry::get( 'clTemplateHtml' );
				$oTemplate->setTitle( SITE_TITLE . ': ' . _( 'Preview of page' ) );
				$sInfoContent = $sInfoContent[0]['contentTextId'];
			} else {
				$oRouter = clRegistry::get( 'clRouter' );
				$oRouter->redirect( '/' );
			}
			
			break;
		
		case 'inactive':
			$oRouter = clRegistry::get( 'clRouter' );
			if( $oRouter->sPath != '/' ) {				
				$oRouter->redirect( '/' );			
			} else {
				header('HTTP/1.0 404 Not Found');
				header('Status: 404 Not Found');
				exit( '<h1>404 Not Found</h1><p>The page that you have requested could not be found.</p>' );
			}
			break;			
	}
}

echo '
		<div class="view infoContent infoContent' . $this->iCurrentViewId . '">
			' . $sInfoContent . '
		</div>';