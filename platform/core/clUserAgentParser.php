<?php

class clUserAgentParser {
	
    /**
     * Extracts information from the user agent string.
     *
     * @param string $sString The user agent string
     * @return array Returns the user agent information.
     */
    public function parse( $sString ) {
        $aUserAgent = array(
            'string' => $this->cleanUserAgentString( $sString ),
            'browser_name' => null,
            'browser_version' => null,
            'os' => null
        );
		
        if( empty($aUserAgent['string']) ) {
            return $aUserAgent;
        }
		
        // Find the right name/version phrase (or return empty array if none found)
        foreach( $this->getKnownBrowsers() as $browser => $regex ) {
            // Build regex that matches phrases for known browsers (e.g. "Firefox/2.0" or "MSIE 6.0").
            // This only matches the major and minor version numbers (e.g. "2.0.0.6" is parsed as simply "2.0").
            $pattern = '#'.$regex.'[/ ]+([0-9]+(?:\.[0-9]+)?)#';
			
            if( preg_match($pattern, $aUserAgent['string'], $matches) ) {
                $aUserAgent['browser_name'] = $browser;	
				
                if( isset($matches[1]) ) {
                    $aUserAgent['browser_version'] = $matches[1];
                }
				
                break;
            }
        }
		
		foreach( $this->getKnownOs() as $sOsKey => $sOsTitle ) {
			if(
				strpos($sString, $sOsKey) !== false ||
				strpos($sString, strtolower($sOsKey)) !== false ||
				strpos($sString, ucfirst($sOsKey)) !== false
			) {
				$aUserAgent['os'] = $sOsTitle;
				break;
			}
			
		}
		
        return $aUserAgent;
    }

	/**
     * Gets known operating systems.
     *
     * @return array
     */
    protected function getKnownOs() {
        return array(
            'windows' => 'Windows',
            'android' => 'Android',
			'linux' => 'Linux',
            'iPad' => 'iPad',
            'iPhone' => 'iPhone',
            'mac' => 'Mac',
			'apple' => 'Apple'
            // ...
        );
    }
	
    /**
     * Gets known browsers. Since some UAs have more than one phrase we use an ordered array to define the precedence.
     *
     * @return array
     */
    protected function getKnownBrowsers() {
        return array(
            'firefox' => 'firefox',
            'opera' => 'opera',
            'edge' => 'edge',
            'msie' => 'msie',
            'chrome' => 'chrome',
            'safari' => 'safari',
            // ...
        );
    }
	
    /**
     * Gets known browser aliases.
     *
     * @return array
     */
    protected function getKnownBrowserAliases() {
        return array(
            'opr' => 'opera',
            'iceweasel' => 'firefox',
            // ...
        );
    }

    /**
     * Make user agent string lowercase, and replace browser aliases.
     *
     * @param string $sString The dirty user agent string
     * @return string Returns the clean user agent string.
     */
    protected function cleanUserAgentString( $sString ) {
        // clean up the string
        $sString = trim( strtolower( $sString ) );

        // replace browser names with their aliases
        $sString = strtr( $sString, $this->getKnownBrowserAliases() );

        return $sString;
    }
	
}