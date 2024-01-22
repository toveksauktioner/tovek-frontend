<?php

$sOutput = '';
$aErr = array();

require_once PATH_FUNCTION . '/fData.php';
$oUserManager = clRegistry::get( 'clUserManager' );
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$oCustomer = clRegistry::get( 'clCustomer', PATH_MODULE . '/customer/models' );

if( !empty($_GET['customerId']) ) {
	$aCustomer = current( $oCustomer->read( 'customerUserId', $_GET['customerId'] ) );
	if( !empty($aCustomer) ) $_GET['userId'] = $aCustomer['customerUserId'];
}

if( !empty($_GET['userId']) ) {
	$aUserData = $oUserManager->read( array(
		'username',
		'infoFirstname',
		'infoSurname',
		'infoUserPin',
		'infoAddress',
		'infoZipCode',
		'infoCity',
		'infoCountry',
		'userEmail',
		'infoPhone',
		'userLastActive'
	), $_GET['userId'] );

	if( !empty($aUserData) ) {
		$aUserData = current($aUserData);
		$aUserDataDict = $oUserManager->oDao->getDataDict();

		$oDiscount = clRegistry::get( 'clDiscount', PATH_MODULE . '/discount/models' );
		$oDiscount->setParentType( 'User' );

		$aDiscountData = $oDiscount->readByParent( $_GET['userId'], array(
			'discountValue'
		) );
		$iDiscount = ( !empty($aDiscountData) ? current(current($aDiscountData)) : 0 );

		if( $oUserManager->isUserOnline( $_GET['userId'] ) ) {
			$sOutput .= '<p>' . _( 'This user is' ) . ' <span class="active">' . _( 'online' ) . '</span>. ';
		} else {
			$sOutput .= '<p>' . _( 'This user is' ) . ' <span class="inactive">' . _( 'offline' ) . '</span>. ';
		}
		$sOutput .= _( 'Last active' ) . ' ' . $aUserData['userLastActive'] . '</p>';

		$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
			'countryId',
			'countryName'
		) );
		$aCountries = arrayToSingle( $aCountries, 'countryId', 'countryName' );

		// Check if you have permission to view this user
		if( !$oUser->oAclGroups->isAllowed('superuser') ) {
			$aAllUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupTitle' );

			// Your usergroups
			$aYourUserGroups = array_intersect_key( $aAllUserGroups, $oUser->oAclGroups->aAcl );

			// User groups
			$aUserGroups = arrayToSingle( $oUserManager->oDao->readUserGroup( $_GET['userId'] ), 'groupKey', 'groupTitle' );

			$aDiffGroups = array_diff_key($aUserGroups, $aYourUserGroups);
			if( !empty($aDiffGroups) ) throw new Exception( 'noUserAccess - ' . $_GET['userId'] );
		}

		$sOutput .= '
		<dl class="marginal">
			<dt>' . $aUserDataDict['entUser']['username']['title'] . '</dt>
			<dd>' . $aUserData['username'] . '</dd>

			<dt>' . _( 'Discount' ) . ' (%)' . '</dt>
			<dd>' . $iDiscount . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoFirstname']['title'] . '</dt>
			<dd>' . $aUserData['infoFirstname'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoSurname']['title'] . '</dt>
			<dd>' . $aUserData['infoSurname'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoUserPin']['title'] . '</dt>
			<dd>' . $aUserData['infoUserPin'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoAddress']['title'] . '</dt>
			<dd>' . $aUserData['infoAddress'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoZipCode']['title'] . '</dt>
			<dd>' . $aUserData['infoZipCode'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoCity']['title'] . '</dt>
			<dd>' . $aUserData['infoCity'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoCountry']['title'] . '</dt>
			<dd>' . $aCountries[ $aUserData['infoCountry'] ] . '</dd>

			<dt>' . $aUserDataDict['entUser']['userEmail']['title'] . '</dt>
			<dd>' . $aUserData['userEmail'] . '</dd>

			<dt>' . $aUserDataDict['entUserInfo']['infoPhone']['title'] . '</dt>
			<dd>' . $aUserData['infoPhone'] . '</dd>
		</dl>';

		// Orders
		$oProduct = clRegistry::get( 'clProduct', PATH_MODULE . '/product/models' );
		$oOrder = clRegistry::get( 'clOrder', PATH_MODULE . '/order/models' );
		$aOrderDataDict = $oOrder->oDao->getDataDict();

		clFactory::loadClassFile( 'clOutputHtmlSorting' );
		$oSorting = new clOutputHtmlSorting( $oOrder->oDao, array(
			'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('orderId' => 'DESC') )
		) );
		$oSorting->setSortingDataDict( array(
			'orderId' => array(),
			'orderTotal' => array(),
			'orderStatus' => array(),
			'orderPaymentStatus' => array(),
			'orderCreated' => array()
		) );

		clFactory::loadClassFile( 'clOutputHtmlPagination' );
		$oPagination = new clOutputHtmlPagination( $oOrder->oDao, array(
			'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null )
		) );

		$aOrderData = $oOrder->readByUser( $_GET['userId'], array(
			'orderId',
			'orderTotal',
			'orderCurrency',
			'orderStatus',
			'orderPaymentStatus',
			'orderCreated'
		) );

		$sOutput .= '
		<div class="orderList">
			<h2>' . _( 'Orders' ) . '</h2>';

		if( !empty($aOrderData) ) {
			clFactory::loadClassFile( 'clOutputHtmlTable' );

			$oOutputHtmlTable = new clOutputHtmlTable( $aOrderDataDict );
			$oOutputHtmlTable->setTableDataDict( $oSorting->render() );

			$sUrlOrderShow = $oRouter->getPath( 'adminOrderShow' );
			foreach( $aOrderData as $entry ) {
				$row['orderId'] = '<a href="' . $sUrlOrderShow . '/?orderId=' . $entry['orderId'] . '" class="ajax">' . $entry['orderId'] . '</a>';
				$row['orderTotal'] = calculatePrice($entry['orderTotal'], array(
					'profile' => 'humanWithoutVat',
					'additional' => array(
						'currency' => $entry['orderCurrency']
					)
				) );
				$row['orderStatus'] = $aOrderDataDict['entOrder']['orderStatus']['values'][$entry['orderStatus']];
				$row['orderPaymentStatus'] = $aOrderDataDict['entOrder']['orderPaymentStatus']['values'][$entry['orderPaymentStatus']];
				$row['orderCreated'] = substr( $entry['orderCreated'], 0, 16 );

				$oOutputHtmlTable->addBodyEntry( $row );
			}

			$sOutput .= '
					' . $oOutputHtmlTable->render() . '
					' . $oPagination->render();
		} else {
			$sOutput .= '
			<p>
				<strong>' . _( 'There are no items to show' ) . '</strong>
			</p>';
		}

		$sOutput .= '
		</div>';


		// Comments
		$sOutput .= '
		<div class="commentList">
			<h2>' . _( 'Comments' ) . '</h2>';

		$oComment = clRegistry::get( 'clComment', PATH_MODULE . '/comment/models' );
		$oComment->setParent( $_GET['userId'], $oUserManager->sModuleName );

		if( !empty($_POST['frmCommentAdd']) && !empty($_SESSION['userId']) ) {
			$_POST['commentUserId'] = $_SESSION['userId'];
			$oComment->create( $_POST );
			$aErr = clErrorHandler::getValidationError( 'createComment' );
			if( empty($aErr) ) {
				$oNotification = clRegistry::get( 'clNotificationHandler' );
				$oNotification->set( array(
					'createComment' => _( 'The data has been saved' )
				) );
			}
		}

		$aFormDataDict = array(
			'entComment' => array(
				'commentContent' => array(
					'type' => 'string',
					'appearance' => 'full',
					'title' => 'Comment'
				),
				'frmCommentAdd' => array(
					'type' => 'hidden',
					'value' => true
				)
			)
		);

		$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
		$oOutputHtmlForm->init( $aFormDataDict, array(
			'attributes' => array(
				'class' => 'vertical',
				'id' => 'frmCommentAdd'
			),
			'method' => 'post',
			'errors' => $aErr,
			'buttons' => array(
				'submit' => _( 'Save' )
			),
		) );

		$sOutput .= '
			<p><a href="#frmAddComment" class="toggleShow">&#9660; ' . _( 'Create new comment' ) . '</a></p>
			<div id="frmAddComment">
				' . $oOutputHtmlForm->render() . '
			</div>';

		$oComment->oDao->aSorting = array(
			'commentCreated' => 'DESC'
		);
		$aComments = $oComment->readByParent( array(
			'commentId',
			'commentUserId',
			'commentContent',
			'commentCreated'
		) );

		if( !empty($aComments) && count($aComments) > 0 ) {
			$oRouter = clRegistry::get( 'clRouter' );

			$sOutput .= '
			<ul id="commentList">';

			$aUserNames = arrayToSingle( $oUserManager->read( array(
				'userId',
				'username'
			), arrayToSingle( $aComments, null, 'commentUserId' ) ), 'userId', 'username');

			foreach( $aComments as $entry ) {
				$sOutput .= '
				<li class="comment">
					<div class="commentDatetime">' . _( 'Created' ) . ' ' . mb_substr( $entry['commentCreated'], 0, 16 ) . ' ' . _( 'by' ) . ' ' . ( array_key_exists($entry['commentUserId'], $aUserNames) ? $aUserNames[ $entry['commentUserId'] ] : $entry['commentUserId'] ) . ' <a href="' . $oRouter->sPath . '?event=deleteComment&amp;deleteComment=' . $entry['commentId'] . '&amp;' . stripGetStr( array('action', 'invoiceId', 'event', 'deleteComment') ) . '" class="icon iconDelete linkConfirm" title="' . _( 'Do you really want to delete this item?' ) . '" title="' . _( 'Delete' ) . '">' . _( 'Delete' ) . '</a></div>
					<div class="commentContent">' . $entry['commentContent'] . '</div>
				</li>';
			}

			$sOutput .= '
			</ul>';
		}

		$sOutput .= '</div>';

	} else {
		$sOutput .= _( 'There are no user with this ID' );
	}

} else {
	$oRouter->redirect( $oRouter->getPath('adminCustomers') );
}
echo '
	<div class="view customerShow">
		<h1>' . _( 'Customer' ) . '</h1>
		<section>' . $sOutput . '</section>
	</div>';