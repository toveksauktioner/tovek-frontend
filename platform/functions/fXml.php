<?php

function xml2array( $sString, $aValues = array(), $aIndex = array() ) {
	$rParser = xml_parser_create();
	
	xml_parser_set_option( $rParser, XML_OPTION_CASE_FOLDING, 0 );
	xml_parse_into_struct( $rParser, $sString, $aValues, $aIndex );
	xml_parser_free( $rParser );
		
	$aData = array();
	$aRefData = &$aData;
	foreach( $aValues as $key => $entry ) {		
		$sTagName = $entry['tag'];
		
		if( $entry['type'] == 'open' ) {
            if( isset($aRefData[$sTagName]) ) {
                if( isset($aRefData[$sTagName][0]) ) {
					$aRefData[$sTagName][] = array(); 
				} else {
					$aRefData[$sTagName] = array( $aRefData[$sTagName], array() );
				}
				
                $aCarryValue = &$aRefData[$sTagName][ count( $aRefData[$sTagName] ) - 1 ];				
            } else {
				$aCarryValue = &$aRefData[$sTagName];
			}
			
            if( isset($entry['attributes']) ) {
				foreach( $entry['attributes'] as $key => $value ) {
					$aCarryValue['_attributes'][$key] = $value;
				}
			}
			
            $aCarryValue['_data'] = array();
            $aCarryValue['_data']['_param'] = &$aRefData;
            $aRefData = &$aCarryValue['_data'];
			
        } elseif( $entry['type'] == 'complete' ) {
            if( isset($aRefData[$sTagName]) ) { 				// same as open
                if( isset($aRefData[$sTagName][0]) ) {
					$aRefData[$sTagName][] = array();
				} else {
					$aRefData[$sTagName] = array( $aRefData[$sTagName], array() );
				}
                
				$aCarryValue = &$aRefData[$sTagName][ count( $aRefData[$sTagName] ) - 1 ];
				
			} else {
				$aCarryValue = &$aRefData[$sTagName];
			}
			
            if( isset($entry['attributes']) ) {
				foreach( $entry['attributes'] as $key => $value ) {
					$aCarryValue['_attributes'][$key] = $value;
				}
			}
			
            $aCarryValue['_value'] = ( isset($entry['value']) ? $entry['value'] : '' );
			
        } elseif( $entry['type'] == 'close' ) {
            $aRefData = &$aRefData['_param'];
        }
	}    

	xml2array_removeRecursion( $aData );
	
	return $aData;
}

function xml2array_removeRecursion( &$aData ) {
	foreach( $aData as $key => $value ) {
        if( $key === '_param' ) {
			unset( $aData[$key] );
		} elseif( is_array($aData[$key]) ) {
			xml2array_removeRecursion( $aData[$key] );
		}
    }
}