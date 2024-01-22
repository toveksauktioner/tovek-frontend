<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );

echo '
	<div class="view auction userHomepage">
		<h1>' . _( 'Welcome to your own auction page' ) . '</h1>
		<p>' . _( 'From this page can you follow up on your won, favorit & watched bids' ) . '.</p>
	</div>';

echo $oLayout->renderView( 'auction/userItemFavList.php' );
echo $oLayout->renderView( 'auction/userItemBidList.php' );
echo $oLayout->renderView( 'auction/userWonItemList.php' );

echo '
	<div class="view auction userHomepage">
        <div class="wonBid message">
            <p>- ' . _( 'A won bid can take 1 minute or 2 to be registered after end time' ) . ' -</p>
        </div>
    </div>';

$oTemplate->addBottom( array(
	'key' => 'jsHideShowViews',
	'content' => '
		<script>
			$(".userWonItemList .listWrapper").before( "<a href=\"#\" class=\"showHideView button\">' . _( 'Visa listan' ) . '</a>" );
			$(".userWonItemList .listWrapper").hide();
			$(".userWonItemList .listToolbar .sorting").hide();
			$(".userWonItemList .listToolbar .viewmodes").hide();

			$(document).delegate( "a.showHideView", "click", function(event) {
				event.preventDefault();
				if( $(this).next().is(":visible") ) {
					$(this).html("' . _( 'Visa listan' ) . '");
				} else {
					$(this).html("' . _( 'Dölj listan' ) . '");
				}
				$(this).next().toggle();
				$(".userWonItemList .listToolbar .sorting").toggle();
				$(".userWonItemList .listToolbar .viewmodes").toggle();
			} );
		</script>
	'
) );

$oTemplate->addStyle( array(
	'key' => 'cssHideShowViews',
	'content' => '
		a.showHideView.button {
			display: block;
			width: 137px;
			padding: 7px 0;
			margin-bottom: 18px;
			text-align: center;
			font-size: 1.125em;
			font-weight: 700;
			color: #6f6f6f;
			background: url(/images/templates/tovek2014/bg-button-showhide.png) no-repeat;
		}
	'
) );