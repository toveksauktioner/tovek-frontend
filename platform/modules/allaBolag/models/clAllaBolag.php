<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/allaBolag/config/cfAllaBolag.php';

class clAllaBolag {
    
    public $oAcl;
    
    public $sModuleName;
	public $sModulePrefix;
    
    protected $oCurl;
    
    protected $aEvents = array();
	protected $oEventHandler;
	
	protected $aNotifications = array();
	protected $oNotification;
    
	public function __construct() {
		$this->sModuleName = 'AllaBolag';
		$this->sModulePrefix = 'allaBolag';
        
        /**
		 * Acl
		 */
        $oUser = clRegistry::get( 'clUser' );
		$this->setAcl( $oUser->oAcl );
        
        /**
		 * Event & Notification
		 */
		$this->oEventHandler = clRegistry::get( 'clEventHandler' );
		$this->oEventHandler->addListener( $this, $this->aEvents );
		$this->oNotification = clRegistry::get( 'clNotificationHandler' );
		
		/**
		 * cUrl handler
		 */
		$this->oCurl = clRegistry::get( 'clCurl', null, array(
			'header' => true
		) );
		$this->oCurl->setEndpoint( ALLA_BOLAG_ENDPOINT );
	}
    
	/**
	 * ACL method
	 */
    public function setAcl( $oAcl ) {
		$this->oAcl = $oAcl;
	}
    
    /**
	 * Main api call function
	 */
	protected function apiCall( $aParams ) {
		if( ALLA_BOLAG_ACTIVE === false ) {
			return;
		}
		
        $aParams = array(
            'key' => ALLA_BOLAG_BIWSKEY
        ) + $aParams;
        
        $this->oCurl->get( ALLA_BOLAG_ENDPOINT . '?' . http_build_query( $aParams ) );
		
        if( $this->oCurl->aLastRespons['info']['http_code'] == 200 ) {
            require_once PATH_FUNCTION . '/fXml.php';
            $aRawData = current( xml2array( $this->oCurl->aLastRespons['data']['content'] ) );
            
            $aData = array(
                'attributes' => !empty($aRawData['_attributes']) ? $aRawData['_attributes'] : array(),
                'clientdata' => array(),
                'records' => array()
            );
            
            if( !empty($aRawData['_data']['clientdata']) ) {
                foreach( $aRawData['_data']['clientdata']['_data'] as $sDataKey => $aDataEntry ) {
                    $aData['clientdata'][ $sDataKey ] = $aDataEntry['_value'];
                }
            }
			
            if( !empty($aRawData['_data']['records']) && $aRawData['_data']['records']['_attributes']['total'] != '0' ) {
                foreach( $aRawData['_data']['records']['_data']['record']['_data'] as $sDataKey => $aDataEntry ) {
                    if( !isset($aDataEntry['_value']) && !empty($aDataEntry['_data']) ) {
						foreach( $aDataEntry['_data'] as $sDataKey2 => $aDataEntry2 ) {
							if( empty($aData['records'][ $sDataKey2 ]) ) {
								$aData['records'][ $sDataKey2 ] = $aDataEntry2['_value'];
							}
						}
						continue;
						
					} elseif( !empty($aDataEntry['_value']) ) {
						$aData['records'][ $sDataKey ] = $aDataEntry['_value'];
						continue;
					
					} else {
						continue;
					}					
                }
            }
			
			if( !empty($aData['clientdata']) ) {
				// Logger
				clFactory::loadClassFile( 'clLogger' );
				clLogger::log( $aData['clientdata'], 'AllaBolagClient.log' );
				clLogger::logRotate( 'AllaBolagClient.log', '6M' );
			}
			
            return $aData;
        }
        
        return false;
    }
    
	/**
	 * Test method
	 */
    public function testParams( $aParams ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        return $this->apiCall( $aParams );
    }

	/**
	 * Find method
	 */
    public function find( $sSearch, $sField ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        
		$aParams = array(
			'type' => 'find',
			'query' => $sField . ':' . $sSearch
		);
		
        return $this->apiCall( $aParams );
    }
    
	/**
	 * Fetch method
	 */
	public function fetch( $sSearch, $sField ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );
        
		$aParams = array(
			'type' => 'fetch',
			'query' => $sField . ':' . $sSearch
		);
		
        return $this->apiCall( $aParams );
    }
	
	/**
	 * Org no look up by fetch method
	 */
	public function orgNoLookUp( $sOrgNo ) {
        $this->oAcl->hasAccess( 'read' . $this->sModuleName );        
        return $this->fetch( $sOrgNo, 'orgnr' );
    }
	
}