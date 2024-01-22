<?php

$aErr = array();
$bValidContinent = false;

# Get continents
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aContinents = $oContinent->read();

echo '<div class="view freight formAdd">';
echo '<h3>' . _( 'Add freight' ) . '</h3>';

$oTemplate->addBottom( array(
	'key' => 'changeAllFreight',
	'content' => '
	<script>
		$(".frmContinentSelect button").hide();
		$("#continents").change(function() {
			this.form.submit();
		});
		$("#changeAll").keyup(function() {
			$(".freightValue").val($(this).val());
		});
	</script>'
) );

if( empty($aContinents) ) {
	echo '
	<p><strong>' . _( 'There are no items to show' ) . '</strong></p>';
	return;
}

echo '
	<form action="' . $oRouter->sPath . '?' . stripGetStr( array() ) . '" method="get">
		<div class="frmContinentSelect">
			<select name="continent" id="continents">
				<option>' . _( 'Select continent' ) . '</option>';
foreach( $aContinents as $entry ) {
	if( !empty($_GET['continent']) && $_GET['continent'] === $entry['continentCode'] ) $bValidContinent = true;
	echo '
				<option value="' . $entry['continentCode'] . '"' . ( !empty($_GET['continent']) && $_GET['continent'] === $entry['continentCode'] ? ' selected="selected"' : '' ) . '>' . $entry['continentName'] . '</option>';
}
echo '
			</select>
			<button type="submit">' . _( 'Submit' ) . '</button>
		</div>
	</form>';

# Countries & freights
if( empty($_GET['continent']) || !$bValidContinent ) return;

$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( $_GET['continent'] );

if( empty($aCountries) ) return;

$oFreight = clRegistry::get( 'clFreight', PATH_MODULE . '/freight/models' );

$aFreightValues = arrayToSingle( $oFreight->readByCountry(arrayToSingle($aCountries, null, 'countryId'), array(
	'freightCountryId',
	'freightValue'
)), 'freightCountryId', 'freightValue' );

$oFreight->oDao->aSorting = null;
$aFreeFreightLimits = arrayToSingle($oFreight->readFreightFreeLimitToCountry(), 'countryId', 'freightFreeLimit');

if( !empty($_POST['frmCountryFreight']) && !empty($_POST['freightValues']) ) {
	foreach( (array) $_POST['freightValues'] as $iCountryId => $fFreightValue ) {
		if( array_key_exists($iCountryId, $aFreightValues) ) {
			if( $fFreightValue == 0 ) {
				$oFreight->deleteByCountry( $iCountryId );
			} else {
				$oFreight->updateByCountry( $iCountryId, array('freightValue' => $fFreightValue) );
			}
		} else {
			$oFreight->create( array(
				'freightCountryId' => $iCountryId,
				'freightValue' => $fFreightValue
			) );
		}
		$aFreightValues[$iCountryId] = $fFreightValue;
	}

	// Free freight limit
	$aData = array();
	foreach( (array) $_POST['freeFreightLimits'] as $iCountryId => $fFreeFreightLimit ) {
		if( !empty($fFreeFreightLimit) ) {
			$aData[] = array(
				'freightFreeLimit' => $fFreeFreightLimit,
				'countryId' => $iCountryId
			);
		}
	}
	$oFreight->oDao->aSorting = null;
	$oFreight->updateFreightFreeLimitToCountry( $aData );

	$oRouter->redirect( $oRouter->sPath . '?' . stripGetStr(array(), false) );
}

echo '
	<p class="helpText icon iconText iconHelp">' . _( 'Define freight addition per country, which will then be added to the general freight fee. Negative values are accepted for subtraction' ) . '</p>
	<form action="' . $oRouter->sPath . '?' . stripGetStr( array() ) . '" method="post">
		<div class="field"><label for="changeAll">' . _( 'Change all' ) . ':</label> <input type="text" class="text" id="changeAll" value=""></div>';

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oFreight->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'freightCountryId' => array(),
	'freightValue' => array(
		'title' => _( 'Freight addition')
	),
	'freeFreightLimit' => array(
		'title' => _( 'Free freight limit' )
	)
) );

foreach( $aCountries as $entry ) {
	$row = array(
		'freightCountryId' => $entry['countryName'],
		'freightValue' => '<input type="text" class="text freightValue" name="freightValues[' . $entry['countryId'] . ']" value="' . ( !empty($aFreightValues[$entry['countryId']]) ? $aFreightValues[$entry['countryId']] : '' ) . '">',
		'freeFreightLimit' => '<input type="text" class="text freeFreightLimit" name="freeFreightLimits[' . $entry['countryId'] . ']" value="' . ( !empty($aFreeFreightLimits[$entry['countryId']]) ? $aFreeFreightLimits[$entry['countryId']] : '' ) . '">'
	);
	$oOutputHtmlTable->addBodyEntry( $row );
}

echo '
		' . $oOutputHtmlTable->render() . '
		<p class="buttons">
			<input type="hidden" name="frmCountryFreight" value="true">
			<button type="submit">' . _( 'Save' ) . '</button>
		</p>
	</form>';
echo '</div>';