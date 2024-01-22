<?php

class clEventHandler {

	private $aObjects = array();
	private $aTriggeredEvents = array();
	private $oEventHandlerDao;

	public function __construct( $sPath = null ) {
		$this->oEventHandlerDao = clRegistry::get( 'clEventHandlerDao' . DAO_TYPE_DEFAULT_ENGINE );
		$this->aTriggeredEvents = array(
			'external' => array(),
			'internal' => array()
		);
	}

	public function addListener( $oObject, $aListenerEvents ) {
		$aListenerEvents = (array) $aListenerEvents;
		$aResult = array();
		foreach( $aListenerEvents as $sEventType => $aEvents) {
			foreach( $aEvents as $sEvent => $sFunction ) {
				if( !array_key_exists($sEvent, $this->aObjects) || !is_array($this->aObjects[$sEvent]) ) $this->aObjects[$sEvent] = array();
				$this->aObjects[$sEvent]['object'] = $oObject;
				$this->aObjects[$sEvent]['function'] = $sFunction;

				// If event already triggered, call function
				if( array_key_exists($sEvent, $this->aTriggeredEvents[$sEventType]) ) {
					$aResult[$sEvent][get_class( $oObject )] = call_user_func_array( array($oObject, $sFunction), $this->aTriggeredEvents[$sEventType][$sEvent] );
				}

				if( !empty($aResult) ) return $aResult;
			}
		}
	}

	/**
	 * @param $aEvents An array with events as key and parameters as value, can also be passed as a string which will then be converted to the correct format using the string as event.
	 * $oEventHandler->triggerEvent( array(
	 * 	'event' => $aParams
	 * ) );
	 * $oEventHandler->triggerEvent( 'event' );
	 */
	public function triggerEvent( $aEvents, $sEventType = 'external' ) {
		$aResult = array();
		if( !is_array($aEvents) ) $aEvents = array_flip( (array) $aEvents );
		$this->aTriggeredEvents[$sEventType] += $aEvents;
		foreach( $aEvents as $sEvent => $aParams ) {
			// Typecast params to array if not
			if( !is_array($aParams) ) $aParams = (array) $aParams;
			
			// Check if this event has listener objects
			if( array_key_exists($sEvent, $this->aObjects) ) {
				foreach( $this->aObjects[$sEvent] as $aObject ) {
					$aResult[$sEvent][get_class( $aObject['object'] )] = call_user_func_array( array($aObject['object'], $aObject['function']), $aParams);
				}
			}

			// Check if there is any registred listeners in DB
			$aListeners = $this->oEventHandlerDao->readListeners( $sEvent, $sEventType );
			foreach( $aListeners as $entry ) {
				$sPath = !empty($entry['eventListenerPath']) ? PATH_MODULE . $entry['eventListenerPath'] : null;

				$oObject = clRegistry::get( $entry['eventListener'], $sPath );
				if( method_exists($oObject, $entry['eventListenerFunction']) ) {
					$aResult[$sEvent][$entry['eventListener']] = call_user_func_array( array($oObject, $entry['eventListenerFunction']), $aParams);
					continue;
				}

				clFactory::loadClassFile( $entry['eventListener'], $sPath );
				if( is_callable($entry['eventListener'] . '::' . $entry['eventListenerFunction'])) {
					$aResult[$sEvent][$entry['eventListener']] = call_user_func_array( $entry['eventListener'] . '::' . $entry['eventListenerFunction'], $aParams);
					continue;
				}
			}
		}

		if( !empty($aResult) ) return $aResult;
	}

	public function removeEvent( $aEventKeys, $sEventType = 'external' ) {
		$this->aTriggeredEvents[$sEventType] = array_diff_key( $this->aTriggeredEvents[$sEventType], array_flip((array) $aEventKeys) );
	}

}
