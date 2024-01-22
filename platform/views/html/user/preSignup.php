<?php

$aAllowedCountries = array(
  'AL', 'DZ', 'AD', 'AU', 'AT', 'BE', 'BG', 'CA', 'CL', 'CN', 'CR', 'CI', 'HR',
  'CY', 'CZ', 'DK', 'SV', 'EE', 'FO', 'FI', 'FR', 'GF', 'GE', 'DE', 'GI', 'GR',
  'GL', 'GP', 'GG', 'HK', 'HU', 'IS', 'IN', 'IE', 'IM', 'IL', 'IT', 'JP', 'JE',
  'JO', 'KR', 'XK', 'KW', 'LV', 'LI', 'LT', 'LU', 'MK', 'MT', 'MQ', 'MU', 'YT',
  'MC', 'MN', 'NA', 'NP', 'NL', 'NZ', 'NU', 'NO', 'PE', 'PL', 'PT', 'QA', 'RE',
  'RO', 'RW', 'BL', 'MF', 'PM', 'SM', 'SN', 'SG', 'SK', 'SI', 'ZA', 'ES', 'SJ',
  'SE', 'CH', 'TW', 'TH', 'TO', 'GB', 'US', 'UY', 'VA', 'AX' );

if( !empty($_POST['infoCountry']) ) {
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aCountryData = current( $oContinent->aHelpers['oParentChildHelper']->readChildren( null, array(
	'countryId',
	'countryName',
	'countryIsoCode2'
), $_POST['infoCountry'] ) );

  if( !empty($aCountryData) ) {
    if( !in_array($aCountryData['countryIsoCode2'], $aAllowedCountries) ) {
      $GLOBALS['registrationBlocked'] = true;
      $GLOBALS['registrationBlockedMessage'] = '
        <div class="view user preSignup">
          <h1>Your country is not open for cross-border payments</h1>
          <p>&nbsp;</p>
          <p>Unfortunately we are not able to receive payment from your country and
          therefore we cannot let you register an account.</p>
          <p>&nbsp;</p>
          <p>Basically you need to be able to make a payment from another country
          to be able to purchase from us.</p>
          <p>&nbsp;</p>
          <p>If you are able to make bank transfers from another country you can contact
          us on email: info(a)tovek.se</p>
          <p>&nbsp;</p>
          <p>For more information on which countries are open for cross-border payments,
          check out this document:
          <a href="https://www.handelsbanken.se/tron/public/info/contents/v1/document/35-95720" target="_blank">https://www.handelsbanken.se/tron/public/info/contents/v1/document/35-95720</a></p>
        </div>';
    }
  }
}
