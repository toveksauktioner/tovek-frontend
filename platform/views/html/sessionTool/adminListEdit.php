<?php

$oParser = clRegistry::get( 'clUserAgentParser' );
$oGeoIP2 = clRegistry::get( 'clGeoIP2', PATH_CORE . '/geoIp2' );
$oSessionTool = clRegistry::get( 'clSessionTool', PATH_MODULE . '/sessionTool/models' );
$oUserManager = clRegistry::get( 'clUserManager' ); 

/**
 * Remove an session
 */
if( !empty($_GET['removeSession']) ) {
	// Data
	$aSessions = valueToKey( 'sessionId', $oSessionTool->readByUser( $_SESSION['userId'] ) );
	
	if( !empty($aSessions) && array_key_exists($_GET['removeSession'], $aSessions) ) {
		if( $_GET['removeSession'] != session_id() ) {
			$oSessionTool->delete( $_GET['removeSession'] );
		} else {
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oNotification->set( array(
				'dataError' => _( 'Du kan inte ta bort din nuvarande session' )
			) );
		}
	}
}
	
/**
 * Search
 */
if( !empty($_GET['frmSearch']) ) {
	if( !empty($_GET['searchQuery']) ) {
		// Search for sessions
		$oSessionTool->oDao->setCriterias( array(
			'searchSession' => array(
				'fields' => array_keys( current( $oSessionTool->oDao->getDataDict() ) ),
				'value' => $_GET['searchQuery'],
				'type' => 'like'
			)		
		) );
	}
	if( !empty($_GET['searchUser']) ) {
		$aSearchCriterias = array(
			'customerSearch' => array(
				'type' => 'like',
				'value' => $_GET['searchUser'],
				'fields' => array(
					'entUser.userId',
					'entUser.userPin',
					'entUser.username',
					'userEmail',
					'infoName',
					'infoPhone',
					'infoCellPhone'
				)
			)
		);
		$oUserManager->oDao->setCriterias( $aSearchCriterias );
		
		$aUsers = $oUserManager->read( array(
			'entUser.userId',
			'username',
			'infoName',
			'userGrantedStatus'
		) );
		$oUserManager->oDao->sCriterias = null;
		
		if( !empty($aUsers) ) {
			$aUserShown = arrayToSingle( $aUsers, 'userId', 'username' );
			
			// Search for sessions
			$oSessionTool->oDao->setCriterias( array(
				'searchSession' => array(
					'fields' => 'sessionUserId',
					'value' => arrayToSingle( $aUsers, null, 'userId' ),
					'type' => 'in'
				)		
			) );
		}
	}
}

// Pagination
clFactory::loadClassFile( 'clOutputHtmlPagination' );
$oPagination = new clOutputHtmlPagination( $oSessionTool->oDao, array(
	'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
	'entries' => 100
) );

// Data
if( !empty($_GET['userId']) ) {
	$aSessions = $oSessionTool->readByUser( $_GET['userId'] );
} else {
	$aSessions = $oSessionTool->read();
}

// Render pagination
$sPagination = $oPagination->render();

if( !empty($aSessions) ) {
	/**
	 * Addtional user data
	 */
	$aUserData = valueToKey( 'userId', $oUserManager->read( array(
		'entUser.userId',
		'username',
		'infoName',
		'userGrantedStatus'
	), arrayToSingle( $aSessions, null, 'sessionUserId' ) ) );	
	
	$aEntries = array();
	$sActiveEntry = '';
	foreach( $aSessions as $iKey => $aSession ) {
		$aClass = array( 'row' );
		
		if( $aSession['sessionId'] == session_id() ) {
			$aClass[] = 'active';
		}
		
		$sImage = '/images/icons/device/device-unkown.png';
		$sIp = long2ip( $aSession['sessionLastIp'] );
		
		if( !empty($sIp) && $sIp != '0.0.0.0' ) {
			$aGeoData = $oGeoIP2->getInformation( $sIp );
			if( !empty($aGeoData) ) {
				$sGeo = sprintf( '(%s %s, %s)', $aGeoData['cityName'], $aGeoData['postalCode'], $aGeoData['mostSpecificSubdivision'] );
			} else {
				$sGeo = '<span class="grey">(Okänd plats / skyddad uppkoppling)</span>';
			}
		} else {
			$sGeo = '<span class="grey">(Okänd plats / skyddad uppkoppling)</span>';
		}
		
		if( !empty($aSession['sessionUserAgent']) ) {
			$aUserAgent = $oParser->parse( $aSession['sessionUserAgent'] );
			$sBrowser = '<strong>' . ucfirst( $aUserAgent['browser_name'] ) . ', ' . $aUserAgent['browser_version'] . '</strong>';
			if( !empty($aUserAgent['os']) ) {
				$sBrowser .= sprintf( ' %s <strong>%s</strong> <time>%s %s</time>', _( 'on' ), $aUserAgent['os'], _( '@' ), date('Y-m-d H:i', $aSession['sessionTimestamp']) );
				switch( $aUserAgent['os'] ) {
					case 'Windows': $sImage = '/images/icons/device/device-windows.png'; break;
					case 'Android': $sImage = '/images/icons/device/device-android.png'; break;
					case 'Linux': $sImage = '/images/icons/device/device-android.png'; break;
					case 'iPad': $sImage = '/images/icons/device/device-ipad.png'; break;
					case 'iPhone': $sImage = '/images/icons/device/device-iphone.png'; break;
					case 'Mac': $sImage = '/images/icons/device/device-mac.png'; break;
					case 'Apple': $sImage = '/images/icons/device/device-mac.png'; break;
				}
			}
		} else {
			$sBrowser = '<span class="red">' . _( 'Empty user-agent' ) . '</span>';
		}
		
		$sControls = '<span class="current">Aktiv nu</span>';
		if( $aSession['sessionId'] != session_id() ) {
			$sControls = '<a href="?removeSession=' . $aSession['sessionId'] . '" class="linkConfirm" data-session-id="' . $aSession['sessionId'] . '" title="' . _( 'Är du säker på detta?' ) . '">' . _( 'Remove' ) . '</a>';
		}
		
		if( !empty($aUserData[ $aSession['sessionUserId'] ]) ) {
			$sUser = $aUserData[ $aSession['sessionUserId'] ]['username'];
		} else {
			$sUser = _( 'Guest' );
		}
		
		$sEntry = '
			<li class="' . (implode(' ', $aClass)) . '" id="' . $aSession['sessionId'] . '">
				<div class="image">
					' /*<img src="' . $sImage . '" alt="" />*/ . '
				</div>
				<div class="information">
					<p>' . $sBrowser . '</p>
					<p>' . long2ip( $aSession['sessionLastIp'] ) . ' <em>' . $sGeo . '</em></p>
					<p><strong>' . _( 'User' )  . ':</strong> ' . $sUser . '</p>
				</div>
				<div class="controls">
					' . $sControls . '
				</div>
			</li>';
			
		if( $aSession['sessionId'] == session_id() ) $sActiveEntry = $sEntry;
		else $aEntries[] = $sEntry;
	}
	
	$aEntries = array( $sActiveEntry ) + $aEntries;
	
	$sOutput = '<ul class="list">' . implode( '', $aEntries ) . '</ul>';
	
} else {
	$sOutput = '<strong>' . _( 'There are no sessions to show' ) . '</strong>';
}

/**
 * Search form 
 */
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( array(
	'stringSearch' => array(
		'searchQuery' => array(
			'title' => _( 'Keywords' ),
			'type' => 'string'
		),
		'searchUser' => array(
			'title' => _( 'User' ),
			'type' => 'string'
		),
		'frmSearch' => array(
			'type' => 'hidden',
			'value' => 'true'
		)
	)
), array(
	'action' => $oRouter->sPath,
	'attributes' => array(
		'class' => 'inline'
	),
	'includeQueryStr' => false,
	'buttons' => array(
		'submit' => _( 'Search' )
	)
) );
$sSearchForm = $oOutputHtmlForm->render();

if( !empty($aUserShown) ) {
	$sUserShown = '
		<div class="userShown">
			<strong>' . _( 'Found sessions for users' ) . ':</strong> ' . implode( ', ', $aUserShown ) . '
		</div>';
}

echo '
	<div class="view sessionTool userList">
		<h1>' . _( 'Sessions' ) . '</h1>
		<section class="tools">
			<div class="tool">
				' . $sSearchForm . '
			</div>
		</section>
		' . (!empty($sUserShown) ? $sUserShown : '') . '
		<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header">
				<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
					<a href="' . $oRouter->getPath('adminSessionList') . '">Sessioner</a>
				</li>
				<li class="ui-state-default ui-corner-top">
					<a href="' . $oRouter->getPath('adminSessionDetail') . '">Detaljerat</a>
				</li>
			</ul>
			<div class="ui-tabs-panel ui-widget-content ui-corner-bottom ui-helper-clearfix">			
				' . $sOutput . '
				' . $sPagination . '
			</div>
		</div>
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'customViewStylesheet',
	'content' => '
		.view.sessionTool.userList {}
			.tools { margin-bottom: 1em; }
			
			.userShown { padding: 1em; margin-bottom: 1em; background: #fff; border: 1px solid #aaaaaa; -moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px; }
			
			.ui-widget-content {}
				.ui-widget-content ul.list { list-style: none; padding: .6em 0 !important; }
					.ui-widget-content ul li { padding: 1.5em 1em; position: relative; border-bottom: 1px solid #e5e3e3; }
					.ui-widget-content ul li:hover { background: #e5e3e3; }
					.ui-widget-content ul li.active { background: #e5e3e3; }
						.ui-widget-content ul li .image { display: inline-block; width: 3em; position: relative; top: -.5em; }
						.ui-widget-content ul li .information { display: inline-block; width: 35em; padding-top: .1em; }
							.ui-widget-content ul li .information p { margin-bottom: 0; }
							.ui-widget-content ul li .information .grey { opacity: .4; }
							.ui-widget-content ul li .information .red { color: #ff0000; font-weight: 700; }
							.ui-widget-content ul li .information time { font-size: .8em; font-weight: 400; }
						.ui-widget-content ul li .controls { display: inline-block; width: 15em; vertical-align: top; padding: .7em 0 0 0; }
							.ui-widget-content ul li .controls a { display: inline-block; width: 10.3em; padding: .7em 0; text-align: center; color: #fff; background: url("/images/templates/tovek2014/bg-button-halt.png") no-repeat; }
							.ui-widget-content ul li .controls .current { display: inline-block; width: 10.3em; padding: .7em 0 .45em 0; text-align: center; color: #4F8A10; font-weight: 700; }
						.ui-widget-content ul li .endMessage { position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding: 2.3em 0 0 3.6em; background: url("/images/templates/tovek2014/bg-white-70.png") repeat; font-weight: 700; font-size: 1.2em; box-sizing: border-box; }
	'
) );

$oTemplate->addBottom( array(
	'key' => 'customViewJs',
	'content' => '
		<script>
			$(document).delegate( ".controls a", "click", function(event) {
				event.preventDefault();
				
				var sSessionId = $(this).data("session-id");
				var eParent = $(this).parents(".controls").parents(".row");
				
				var jqxhr = $.get( $(this).attr("href") + "&ajax=true&view=sessionTool/userList.php", function() {
					//alert( "success" );
				} )
				.done( function() {
					$(eParent).append( "<div class=\"endMessage\">Sessionen avslutas...</div>" );
					setTimeout( function() {
						$("#" + sSessionId).slideUp( "normal", function() {
							$("#" + sSessionId).remove();
						} );
					}, 2000 );
				} )
				.fail( function() {
					//alert( "error" );
				} )
				.always( function() {
					//alert( "finished" );
				} );
			} );
		</script>
	'
) );