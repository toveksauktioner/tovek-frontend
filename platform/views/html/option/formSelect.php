<?php

$oLayout = clRegistry::get( 'clLayoutHtml' );

echo '
	<div>
		' . $oLayout->renderView( '/navigation/formSelect.php' ) . '
	</div>';
