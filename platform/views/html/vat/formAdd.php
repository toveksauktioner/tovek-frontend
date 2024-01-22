<?php

$aErr = array();
$bValidContinent = false;

# Get continents
$oContinent = clRegistry::get( 'clContinent', PATH_MODULE . '/continent/models' );
$aContinents = $oContinent->read();

echo '
	<h3>' . _( 'Add countries without VAT' ) . '</h3>';

$oTemplate->addBottom( array(
	'key' => 'changeAllVat',
	'content' => '
	<script>
		$(".frmContinentSelect button").hide();
		$("#continents").change(function() {
			this.form.submit();
		});
		$("#selectAll").click(function() {
			var checked = this.checked;
			$(".vatCountries .checkbox").each(function() {
				this.checked = checked;
			});
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

# Countries & vats
if( empty($_GET['continent']) || !$bValidContinent ) return;

$aCountries = $oContinent->aHelpers['oParentChildHelper']->readChildren( $_GET['continent'] );

if( empty($aCountries) ) return;

$oVat = clRegistry::get( 'clVat', PATH_MODULE . '/vat/models' );

$aVatValues = arrayToSingle( $oVat->readByCountry(arrayToSingle($aCountries, null, 'countryId'), array(
	'vatCountryId',
	'vatValue'
)), 'vatCountryId', 'vatValue' );

# Set VAT to zero for selected countries
if( !empty($_POST['frmCountryVat']) ) {

	# All countries are deselected, delete them from db
	if( empty($_POST['vatValues']) ) {
		foreach( $aVatValues as $iCountryId => $fVatValue ) {
			$oVat->deleteByCountry( $iCountryId );
		}
	} else {
		# Delete countries that are deselected
		$aCountriesToDelete = array_diff_key( $aVatValues, $_POST['vatValues'] );
		if( !empty($aCountriesToDelete) ) $oVat->deleteByCountry( array_keys($aCountriesToDelete) );

		# Add selected countries
		foreach( (array) $_POST['vatValues'] as $iCountryId => $fVatValue ) {
			if( !array_key_exists($iCountryId, $aVatValues) ) {
				$oVat->create( array(
					'vatCountryId' => $iCountryId,
					'vatValue' => 0
				) );
			}
			$aVatValues[$iCountryId] = 0;
		}
	}
	$oRouter->redirect( $oRouter->sPath . '?' . stripGetStr(array(), false) );
}

echo '
	<p class="helpText icon iconText iconHelp">' . _( 'Select countries where billing should be without VAT' ) . '</p>
	<form action="' . $oRouter->sPath . '?' . stripGetStr( array() ) . '" method="post" class="vatCountries">
		<p><label for="selectAll">' . _( 'Select all' ) . '</label>: <input type="checkbox" class="checkbox" id="selectAll"></p>';

clFactory::loadClassFile( 'clOutputHtmlTable' );
$oOutputHtmlTable = new clOutputHtmlTable( $oVat->oDao->getDataDict() );
$oOutputHtmlTable->setTableDataDict( array(
	'vatValue' => array(
		'title' => _( 'Without VAT')
	),
	'vatCountryId' => array(
		'title' => _( 'Country' )
	)
) );

foreach( $aCountries as $entry ) {
	$row = array(
		'vatValue' => '<input type="checkbox" class="checkbox noVat" name="vatValues[' . $entry['countryId'] . ']" value="yes"' . ( array_key_exists($entry['countryId'], $aVatValues) ? ' checked="checked"' : '' ) . '>',
		'vatCountryId' => $entry['countryName']
	);
	$oOutputHtmlTable->addBodyEntry( $row );
}

echo '
		' . $oOutputHtmlTable->render() . '
		<p class="buttons">
			<input type="hidden" name="frmCountryVat" value="true">
			<button type="submit">' . _( 'Save' ) . '</button>
		</p>
	</form>';