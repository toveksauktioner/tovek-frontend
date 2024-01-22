<?php

require_once PATH_CORE . '/clDaoBaseSql.php';

class clFileAccessDaoMysql extends clDaoBaseSql {
	
    public $aLastStatus;
    
	public function __construct() {
		$this->aDataDict = array(
			'entFileAccess' => array(
				'accessId' => array(
					'type' => 'integer',
					'primary' => true,
					'autoincrement' => true,
					'title' => _( 'ID' )
				),
                'accessFileId' => array(
					'type' => 'integer',
					'title' => _( 'File ID' )
				),
                'accessUserId' => array(
					'type' => 'integer',
					'title' => _( 'User ID' )
				),
                'accessStatus' => array(
					'type' => 'array',
					'title' => _( 'Status' ),
                    'values' => array(
                        'allow' => _( 'Allow' ),
                        'disallow' => _( 'Disallow' )
                    )
				),
                // Misc
				'accessCreated' => array(
					'type' => 'datetime',
					'title' => _( 'Created' )
				),
                'accessUpdated' => array(
					'type' => 'datetime',
					'title' => _( 'Updated' )
				),
                'accessCreatorUserId' => array(
					'type' => 'integer',
					'title' => _( 'Creator' )
				),
                'accessUpdaterUserId' => array(
					'type' => 'integer',
					'title' => _( 'Updater' )
				)
			)
		);
		
		$this->sPrimaryField = 'accessId';
		$this->sPrimaryEntity = 'entFileAccess';
		$this->aFieldsDefault = '*';
		
		$this->init();		
	}
    
    /**
     * Grant access to given files for all given users
     */
    public function grantAccess( $aUsers, $aFiles ) {    
        // Existing data
        $aDataByUser = groupByValue( 'accessUserId', $this->readData( array(
            'criteras' => 'accessUserId IN(' . implode( ', ', array_map('intval', $aUsers ) ) . ')'
        ) ) );
        
        $this->aLastStatus = array(
            'grantAccess' => array(
                'updated' => 0,
                'created' => 0
            )
        );
        
        foreach( $aUsers as $iUserId ) {
            foreach( $aFiles as $iFileId ) {
                if( !empty($aDataByUser[ $iUserId ]) && !empty($aDataByUser[ $iUserId ][ $iFileId ]) ) {
                    // Update
                    $this->updateDataByPrimary( $aDataByUser[ $iUserId ][ $iFileId ]['accessId'], array(
                        'accessStatus' => 'allow',
                        'accessUpdated' => date( 'Y-m-d H:i:s' ),
                        'accessUpdaterUserId' => $_SESSION['userId']
                    ) );
                    
                    $this->aLastStatus['updated']++;
                    
                } else {
                    // Create
                    $this->createData( array(
                        'accessFileId' => $iFileId,
                        'accessUserId' => $iUserId,
                        'accessStatus' => 'allow',
                        'accessCreated' => date( 'Y-m-d H:i:s' ),
                        'accessCreatorUserId' => $_SESSION['userId']
                    ) );
                    
                    $this->aLastStatus['created']++;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Revoke access to given files for all given users
     */
    public function revokeAccess( $aUsers, $aFiles ) {
        // Existing data
        $aDataByUser = groupByValue( 'accessUserId', $this->readData( array(
            'criteras' => 'accessUserId IN(' . implode( ', ', array_map('intval', $aUsers ) ) . ')'
        ) ) );
        
        $this->aLastStatus = array(
            'revokeAccess' => array(
                'updated' => 0,
                'missing' => 0
            )
        );
        
        foreach( $aUsers as $iUserId ) {
            foreach( $aFiles as $iFileId ) {
                if( !empty($aDataByUser[ $iUserId ]) && !empty($aDataByUser[ $iUserId ][ $iFileId ]) ) {
                    // Update
                    $this->updateDataByPrimary( $aDataByUser[ $iUserId ][ $iFileId ]['accessId'], array(
                        'accessStatus' => 'disallow',
                        'accessUpdated' => date( 'Y-m-d H:i:s' ),
                        'accessUpdaterUserId' => $_SESSION['userId']
                    ) );
                    
                    $this->aLastStatus['updated']++;
                    
                } else {
                    // Missing, access does not exists..
                    $this->aLastStatus['missing']++;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Custom read method
     */
    public function read( $aParams = array() ) {
        $aParams += array(
            'user' => null
        );
        
        $aCriterias = array();
        $aDaoParams = array();
        
        if( $aParams['user'] !== null ) {
			if( is_array($aParams['user']) ) {
				$aCriterias[] = 'accessUserId IN(' . implode( ', ', array_map('intval', $aParams['user']) ) . ')';
			} else {
				$aCriterias[] = 'accessUserId = ' . (int) $aParams['user'];
			}
		}
        
        if( !empty($aCriterias) ) $aDaoParams['criterias'] = implode( ' AND ', $aCriterias );
        
        return $this->readData( $aDaoParams );
    }
    
}