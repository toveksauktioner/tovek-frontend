<?php

/**
 * Read config values
 */
$aConfigs = array(
	'SITE_STATUS',
	'SITE_MAIL_FROM',
	'SITE_MAIL_TO'
);
$oConfig = clFactory::create( 'clConfig' );
$aConfigData = arrayToSingle( $oConfig->oDao->readData( array(
	'fields' => '*',
	'criterias' => 'configKey IN(' . implode( ', ', array_map(array($oConfig->oDao->oDb, 'escapeStr'), $aConfigs) ) . ')'
) ), 'configKey', 'configValue' );

/**
 * Site status
 */
$aConfigData['SITE_STATUS'] = '<span class="' . $aConfigData['SITE_STATUS'] . '">' . ucfirst($aConfigData['SITE_STATUS']) . '</span>';

/**
 * If site mail not given
 */
if( empty($aConfigData['SITE_MAIL_FROM']) ) {
	$aConfigData['SITE_MAIL_FROM'] = '<span class="error">' . _( 'Not set' ) . '</span>';
}
if( empty($aConfigData['SITE_MAIL_TO']) ) {
	$aConfigData['SITE_MAIL_TO'] = '<span class="error">' . _( 'Not set' ) . '</span>';
}

if( !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '213.88.134.199' ) {
    $bWriteProtected = USER_ADMIN_READ_ONLY;
	$bStatus = $bWriteProtected == true ? '<span class="online">' . _('Yes') . '</span>' : '<span class="error">' . _('No') . '</span>';
	$sWriteProtected = '<dt>' . _( 'Write-protected' ) . 		':</dt> <dd>' . $bStatus . '</dd>';
} else {
	$sWriteProtected = '';
}

echo '
	<div class="view dashboard listStatusCheck">
		<h3>' . _( 'Site status' ) . '</h3>
		<section>
			<dl>			
				<dt>' . _( 'Site is' ) . 		':</dt> <dd>' . $aConfigData['SITE_STATUS'] . '</dd>
				<dt>' . _( 'Site mail from' ) . ':</dt> <dd>' . $aConfigData['SITE_MAIL_FROM'] . '</dd>
				<dt>' . _( 'Site mail to' ) . 	':</dt> <dd>' . $aConfigData['SITE_MAIL_TO'] . '</dd>
				' . $sWriteProtected . '
			</dl>
		</section>
	</div>';