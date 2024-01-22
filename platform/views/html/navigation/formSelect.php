<?php

$oUser = clRegistry::get( 'clUser' );

$sOptions = '';
foreach( $oUser->aGroups as $key => $value ) {
	if( !in_array($key, array('super', 'admin')) ) continue;

	$sOptions .= '
			<div class="field">
				<label for="userGroupKey' . $key . '">' . $value . '</label>
				<input type="radio" name="userGroupKey" class="radio" id="userGroupKey' . $key . '" title="' . $value . '" value="' . $key . '"' . ( $_SESSION['user']['groupKey'] === $key ? ' checked="checked"' : '' ) . '>
			</div>';
}

$sHidden = '';
foreach( $_GET as $key => $value ) {
	if( $key == 'userGroupKey' ) continue;

	if( is_array($value) ) {
		foreach( $value as $entry ) {
			$sHidden .= '
				<input type="hidden" name="' . $key . '[]" value="' . $entry . '" />';
		}
	} else {
		$sHidden .= '
			<input type="hidden" name="' . $key . '" value="' . $value . '" />';
	}
}

echo '
	<form action="" method="get" class="view navigation formSelect">
		<fieldset onchange="this.form.submit();" class="multiple">
			<legend>' . _( 'Choose menu' ) . '</legend>
			' . $sOptions . '
		</fieldset>
		' . $sHidden . '
	</form>';
