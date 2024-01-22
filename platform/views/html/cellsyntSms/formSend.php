<?php

$aErr = array();

$oUserManager = clRegistry::get( 'clUserManager' );
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

/**
 * Send SMS message
 */
if( !empty($_POST['frmSend']) ) {
	$oCellsyntSms = clRegistry::get( 'clCellsyntSms', PATH_MODULE . '/cellsyntSms/models' );
	$oCellsyntSms->send( $_POST['numbers'], $_POST['message'] );
		
	$oNotification = clRegistry::get( 'clNotificationHandler' );
	$oNotification->set( array(
		'dataSaved' => _( 'Your message has been sent' )
	) );
}

/**
 * Search for users
 */
if( !empty($_GET['searchUser']) ) {
	$oUserManager = clRegistry::get( 'clUserManager' );
	
	$oUserManager->oDao->setCriterias( array(
		'username' => array(
			'type' => 'like',				
			'fields' => 'username',
			'value' => $_GET['searchUser']
		)
	) );

	$aReadFields = array(
		'username'
	);
	$aUsers = arrayToSingle( $oUserManager->read( $aReadFields ), null, 'username' );
	
	// Submitters
	$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );
	
	$oSubmitter->oDao->setCriterias( array(
		'submitterSearch' => array(
			'type' => 'like',
			'value' => $_GET['searchUser'],
			'fields' => array(
				'submitterCompanyName',
				'submitterFirstname',
				'submitterSurname'
			)
		)
	) );

	$aSubmitters = $oSubmitter->read( array(
		'submitterId',
		'submitterCompanyName',
		'submitterFirstname',
		'submitterSurname'
	) );
	foreach( $aSubmitters as $aSubmitter ) {
		$aUsers[] = 'INL ' . $aSubmitter['submitterId'] . ': ' . $aSubmitter['submitterCompanyName'] . ' ' . $aSubmitter['submitterFirstname'] . ' ' . $aSubmitter['submitterSurname'];
	}
	
	echo json_encode( $aUsers );
	exit;
}

/**
 * More info/data about user
 */
if( !empty($_GET['userInfo']) ) {
	$oUserManager = clRegistry::get( 'clUserManager' );
	
	$oUserManager->oDao->setCriterias( array(
		'username' => array(
			'type' => '=',				
			'fields' => 'username',
			'value' => $_GET['userInfo']
		)
	) );

	$aReadFields = array(
		'username',
		'infoCellPhone'
	);
	$aUsers = $oUserManager->read( $aReadFields );
	
	if( empty($aUsers) ) {
		$oSubmitter = clRegistry::get( 'clSubmitter', PATH_MODULE . '/submitter/models' );
		
		$_GET['userInfo'] = explode( ':', $_GET['userInfo'] );
		$_GET['userInfo'] = substr( $_GET['userInfo'][0], 4, strlen($_GET['userInfo'][0] - 4) );
		
		$oSubmitter->oDao->setCriterias( array(
			'username' => array(
				'type' => '=',				
				'fields' => 'submitterId',
				'value' => $_GET['userInfo']
			)
		) );
		
		$aReadFields = array(
			'submitterId AS username',
			'submitterCellPhone AS infoCellPhone'
		);
		$aUsers = $oSubmitter->read( $aReadFields );
	}
	
	echo json_encode( $aUsers );
	exit;
}

/**
 * User search form
 */
$oOutputHtmlForm->init( $oUserManager->oDao->getDataDict(), array(
	'data' => $_POST,
	'errors' => $aErr,
	'labelSuffix' => '',
	'labelRequiredSuffix' => '',
	'method' => 'post',
	'buttons' => array()
) );
$oOutputHtmlForm->setFormDataDict( array(
	'username' => array()
) );
$sUserForm = $oOutputHtmlForm->render();

/**
 * Message form
 */
$aFormDataDict = array(
	'sendSms' => array(
		'users' => array(
			'type' => 'hidden'
		),
		'numbers' => array(
			'type' => 'hidden'
		),
		'message' => array(
			'type' => 'string',
			'appearance' => 'full',
			'title' => _( 'Message' )
		)		
	)
);
$oOutputHtmlForm->init( $aFormDataDict, array(
	'data' => $_POST,
	'errors' => $aErr,
	'labelSuffix' => '',
	'labelRequiredSuffix' => '',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Send' )
	)
) );
$oOutputHtmlForm->setFormDataDict( array(
	'users' => array(),
	'numbers' => array(),
	'message' => array(),
	'frmSend' => array(
		'type' => 'hidden',
		'value' => 1
	)
	
) );
$sMessageForm = $oOutputHtmlForm->render();

echo '
	<div class="view smsSendForm">
		<h1>' . _( 'Send SMS' ) . '</h1>
		', $sUserForm, '
		<div class="users">
			<h3>' . _( 'Receivers' ) . ':</h3>
			<div id="hits">
			</div>
		</div>
		<div class="sendMessage">
			', $sMessageForm, '
		</div>
		<div class="priceInfo">
			<table style="width: 100%;">
				<thead>
					<tr>
						<th></th>
						<th>Beskrivning</th>
						<th>Pris per SMS</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<img alt="" src="/images/locale/se.png" />
						</td>
						<td>SMS till svenska mobilnummer</td>
						<td>0,50 kr</td>
					</tr>
					<tr>
						<td>
							<img alt="" src="/images/locale/europeanunion.png" />
						</td>
						<td>SMS till resten av världen</td>
						<td>0,80 kr</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>';
	
// For label in input
$oTemplate->addScript( array(
	'key' => 'jsDefaultValue',
	'src' => '/js/jquery.defaultValue.js'
) );

// Word counter for text fields
$oTemplate->addScript( array(
	'key' => 'jsCounter',
	'src' => '/js/jquery.counter-2.1.js'
) );
$oTemplate->addBottom( array(
	'key' => 'customViewScript',
	'content' => '
		<script>
			$(document).delegate("#username", "keydown", function(event) {
				$("#username").autocomplete( {
					source: "http://185.39.144.69' . $oRouter->sPath . '?searchUser=" + $(this).val(),
					minLength: 3,
					select: function( event, ui ) {
						$.ajax( {
							url: "http://185.39.144.69' . $oRouter->sPath . '?userInfo=" + ui.item.value,
							type: "GET",
							data: "noCss=true",
							async: true,
							dataType: "html"
						} ).fail( function() {
							// Failed
							
						} ).done( function( data, textStatus, jqXHR ) {
							// Done
							var sJSON = data.replace("[", "");
							var sJSON = sJSON.replace("]", "");
							var oUser = jQuery.parseJSON( sJSON );
							
							if( oUser.infoCellPhone.length > 0 ) {
								$( "#hits" ).append( "<span class=\"entry\">" + ui.item.value + " (" + oUser.infoCellPhone + ")<span class=\"delete\">X</span></span>" );
								if( !$( "#users" ).val() ) {
									$( "#users" ).val( ui.item.value );
								} else {
									$( "#users" ).val( $( "#users" ).val() + "," + ui.item.value );
								}
								if( !$( "#numbers" ).val() ) {
									$( "#numbers" ).val( oUser.infoCellPhone );
								} else {
									$( "#numbers" ).val( $( "#numbers" ).val() + "," + oUser.infoCellPhone );
								}
							} else {
								$( "#hits" ).append( "<span class=\"entry bad\">" + ui.item.value + " (' . _( 'No number' ) . ')<span class=\"delete\">X</span></span>" );
							}																
						} );
						
						this.value = "";
						return false;
					}
				} );
			} );
			$(document).delegate("#hits .entry .delete", "click", function(event) {
				$(this).parent("span").remove();
			} );
			
			$(".smsSendForm form .field label").each( function() {
				if( $( ".smsSendForm form  #" + $(this).attr("for") ).val() == "" ) {				
					$( ".smsSendForm form  #" + $(this).attr("for") ).defaultvalue( $(this).text() );
				}
			} );
			$(".smsSendForm form").submit( function() {
				$(".smsSendForm form .field label").each( function() {
					if( $(this).text() == $( ".smsSendForm form #" + $(this).attr("for") ).val() ) {
						$( ".smsSendForm form  #" + $(this).attr("for") ).val("");
					}
				} );
			} );
			
			$("#message").counter( {
				type: "char",
				goal: 160,
				count: "up",
				msg : "' . _( 'characters' ) . ' (max 160)",
			} );
		</script>'
) );