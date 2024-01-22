<?php

if( !isset($_SESSION['userId']) ) {
	return;
}

$oParser = clRegistry::get( 'clUserAgentParser' );
$oGeoIP2 = clRegistry::get( 'clGeoIP2', PATH_CORE . '/geoIp2' );
$oSessionTool = clRegistry::get( 'clSessionTool', PATH_MODULE . '/sessionTool/models' );

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

// Data
$aSessions = $oSessionTool->readByUser( $_SESSION['userId'] );

if( !empty($aSessions) ) {
	
	$aEntries = array();
	$sActiveEntry = '';
	foreach( $aSessions as $iKey => $aSession ) {
		$aClass = array( 'row' );
		
		if( $aSession['sessionId'] == session_id() ) {
			$aClass[] = 'active';
		}
		
		$sImage = '/images/icons/device/device-unkown.png';
		
		$aUserAgent = $oParser->parse( $aSession['sessionUserAgent'] );
		//$aGeoData = $oGeoIP2->getInformation( long2ip($aSession['sessionLastIp']) );
		
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
		
		//$sGeo = '<span class="grey">(Okänd plats / skyddad uppkoppling)</span>';
		//if( !empty($aGeoData) ) {
		//	$sGeo = sprintf( '(%s %s, %s)', $aGeoData['cityName'], $aGeoData['postalCode'], $aGeoData['mostSpecificSubdivision'] );
		//}
		
		$sControls = '<span class="current">Aktiv nu</span>';
		if( $aSession['sessionId'] != session_id() ) {
			$sControls = '<a href="?removeSession=' . $aSession['sessionId'] . '" class="linkConfirm" data-session-id="' . $aSession['sessionId'] . '" title="' . _( 'Är du säker på detta?' ) . '">' . _( 'Remove' ) . '</a>';
		}
		
		$sEntry = '
			<li class="' . (implode(' ', $aClass)) . '" id="' . $aSession['sessionId'] . '">
				<div class="image">
					' /*<img src="' . $sImage . '" alt="" />*/ . '
				</div>
				<div class="information">
					<p>' . $sBrowser . '</p>
					' . /* <p>' . long2ip( $aSession['sessionLastIp'] ) . ' <em>' . $sGeo . '</em></p> */ '
				</div>
				<div class="controls">
					' . $sControls . '
				</div>
			</li>';
			
		if( $aSession['sessionId'] == session_id() ) $sActiveEntry = $sEntry;
		else $aEntries[] = $sEntry;
	}
	
	$aEntries = array( $sActiveEntry ) + $aEntries;
	
	$sOutput = '<ul>' . implode( '', $aEntries ) . '</ul>';
	
} else {
	$sOutput = '<strong>' . _( 'There are no sessions to show' ) . '</strong>';
}

echo '
	<div class="view sessionTool userList">
		<h1>' . _( 'Your sessions' ) . '</h1>
		<section>
			' . $sOutput . '
		</section>
	</div>';
	
$oTemplate->addStyle( array(
	'key' => 'customViewStylesheet',
	'content' => '
		.view.sessionTool.userList { margin: 3em 0; }
			.view.sessionTool.userList h1 { margin-bottom: .5em; }
			.view.sessionTool.userList section {}
				.view.sessionTool.userList section ul { list-style: none; }
					.view.sessionTool.userList section ul li { padding: 1.5em 1em; position: relative; border-bottom: 1px solid #e5e3e3; }
					.view.sessionTool.userList section ul li:hover { background: #e5e3e3; }
					.view.sessionTool.userList section ul li.active { background: #e5e3e3; }
						.view.sessionTool.userList section ul li .image { display: inline-block; width: 3em; position: relative; top: .55em; }
						.view.sessionTool.userList section ul li .information { display: inline-block; width: 35em; }
							.view.sessionTool.userList section ul li .information .grey { opacity: .4; }
							.view.sessionTool.userList section ul li .information time { font-size: .8em; font-weight: 400; }
						.view.sessionTool.userList section ul li .controls { display: inline-block; width: 15em; vertical-align: top; padding: .35em 0; }
							.view.sessionTool.userList section ul li .controls a { display: inline-block; width: 8.6em; padding: .3em 0 .45em 0; text-align: center; color: #fff; background: url("/images/templates/tovek2014/bg-button-halt.png") no-repeat; }
							.view.sessionTool.userList section ul li .controls .current { display: inline-block; width: 8.6em; padding: .3em 0 .45em 0; text-align: center; color: #4F8A10; font-weight: 700; }
						.view.sessionTool.userList section ul li .endMessage { position: absolute; top: 0; left: 0; width: 100%; height: 100%; padding: 1.7em 0 0 3.6em; background: url("/images/templates/tovek2014/bg-white-70.png") repeat; font-weight: 700; font-size: 1.2em; box-sizing: border-box; }
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