<?php

	$oLocale = clRegistry::get( 'clLocale' );
	$aLocales = $oLocale->read();
	$aLocales = arrayToSingle( $aLocales, 'localeId', 'localeTitle' );

	$aLocaleItems	= array();
	foreach( $aLocales as $iLocaleId => &$sTitle ) {
		$aLocaleItems[] = '<a href="?' . http_build_query( array( 'langIdEdit' => $iLocaleId ) + $_GET ) . '">' . $sTitle . '</a>';
	}

	echo '<div class="view locale listSelect"><ul><li>' . implode('</li><li>', $aLocaleItems) . '</li></ul></div>';