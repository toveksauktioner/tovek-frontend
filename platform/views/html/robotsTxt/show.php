<?php

$oRobotsTxt = clRegistry::get( 'clRobotsTxt', PATH_MODULE . '/robotsTxt/models' );

// Sort
$oRobotsTxt->oDao->aSorting = array(
	'ruleSort' => 'ASC'
);

// Fetch active rules
$aRules = $oRobotsTxt->readActive();

header("Content-type: text/plain");

foreach( $aRules as $aRule ) {
	echo $oRobotsTxt->oDao->aDataDict['entRobotsTxt']['ruleType']['values'][$aRule['ruleType']] . ': ' . $aRule['ruleVariable'] . "\n";
}
