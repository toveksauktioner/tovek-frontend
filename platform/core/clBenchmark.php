<?php

class clBenchmark {
	private $aTimer = array();
	
	public function __construct() {}
	
	public function timer( $sAction, $sName ) {
		// Default
		if( $sAction == null ) {
			if( in_array( $sName, $this->aTimer ) ) {
				$sAction = "stop";
			} else {
				$sAction = "start";
			}
		}
		
		// Start timer
		if( $sAction == "start" ) {
			$fTime = microtime(true);				
			
			$this->aTimer[ $sName ][] = array(					
				'start' => $fTime,
				'stop' => null,
				'difference' => null
			);
			
			return true;
		}
		
		// Stop timer
		if( $sAction == "stop" ) {
			$fTime = microtime(true);
						
			// No name needed if there's only one timer
			if( $sName === null && count( $this->aTimer ) == 1 ) {
				$sName = $this->aTimer[0];
			} elseif( $sName === null ) {				
				return false;
			}

			// Check if timer exists
			if( !array_key_exists($sName, $this->aTimer) ) {				
				return false;
			}
			
			// Insert stop & diff time
			$aBackwardsTimer = array_reverse($this->aTimer[ $sName ]);
			foreach( $aBackwardsTimer as $key => $values) {				
				if( $values['stop'] === null ) {			
					$this->aTimer[ $sName ][ $key ][ 'stop' ] = $fTime;										
					$fTime = $this->aTimer[ $sName ][ $key ][ 'stop' ] - $this->aTimer[ $sName ][ $key ][ 'start' ];
					$this->aTimer[ $sName ][ $key ][ 'difference' ] = $fTime;					
				} 
			}		
			return true;
		}
					
		if( $sAction == "show" ) {
			
			// Show specifik timer
			if( $sName != "*" && $sName != null ) {
				foreach( $this->aTimer[ $sName ] as $aTimers => $aValues ) {
					$sOutput = $sName . ': ' . $aValues[ 'difference' ];
				}
				unset($this->aTimer[ $sName ]);
			} elseif( $sName == "*" || $sName === null ) {
				// Show all timers
				$sOutput = 'Timers: ';			
			
				foreach( $this->aTimer as $aTimers => $aValues ) {				
					
					$sOutput .= $aTimers . ': ';
	
					foreach( $aValues as $aValue ) {								
						if( empty($aValue['difference']) ) {
							return false;
						}
						$sOutput .= $aValue['difference'] . ' ; ';
					}
				}
				
				if( empty( $this->aTimer ) ) {
					$sOutput .= 'no timers!';
				}
			}
			
			return $sOutput;
		}
	} 
	
	public function averageSystemLoad() {
		if( !function_exists('sys_getloadavg') ) return false;
		
		$aLoad = sys_getloadavg();
		return implode( '; ', $aLoad );
	}
	
	public function memoryUsage() {
		return memory_get_usage() . ' / ' . memory_get_peak_usage();
	}
	
}