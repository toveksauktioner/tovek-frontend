<?php

if( !empty($_GET['readMessage']) && !empty($_GET['messageId']) && !empty($_GET['userId']) ) {
	if( $_GET['userId'] == $_SESSION['userId'] ) {
		$oAdminMessage = clRegistry::get( 'clAdminMessage', PATH_MODULE . '/adminMessage/models' );
		$oAdminMessage->createMessageToUser( array(
			'userId' => $_SESSION['userId'],
			'messageId' => $_GET['messageId'],
			'messageRead' => 'yes',
			'userAccept' => 'yes',
			'created' => date( 'Y-m-d H:i:s' )
		) );
		$aErr = clErrorHandler::getValidationError( 'createAdminMessage' );
		if( empty($aErr) ) {
			return;
		}
	}
}

if( empty($GLOBALS['viewParams']['adminMessage']['show.php']['messages']) ) return;

$aMessageList = array();

foreach( $GLOBALS['viewParams']['adminMessage']['show.php']['messages'] as $aMessage ) {
	$aMessageList[] = '
		<li class="message">
			<h1>' . $aMessage['messageTitleTextId'] . '</h1>
			<div class="content">
				' . $aMessage['messageContentTextId'] . '
			</div>
			<div class="link">
				<a href="?ajax=true&view=adminMessage/show.php&readMessage=true&messageId=' . $aMessage['messageId'] . '&userId=' . $_SESSION['userId'] . '" class="icon iconText iconDone">' . _( 'I have read and understand this' ) . '</a>
			</div>
		</li>';
}

echo '
	<div id="adminMessage">
		<div class="background"></div>
		<div class="container">
			<ul class="messageList">
				' . implode( "", $aMessageList ) . '
			</ul>
		</div>
	</div>';