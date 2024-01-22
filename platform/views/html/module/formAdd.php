<?php

$aErr = array();

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );

/**
 * Post
 */
if( !empty($_POST['frmModuleAdd']) ) {
    // Post data
    $_POST['moduleName'] = ucfirst( $_POST['moduleName'] );
    $_POST['modulePrefix'] = !empty($_POST['modulePrefix']) ? $_POST['modulePrefix'] : strtolower( $_POST['moduleName'] );
    $_POST['moduleFolder'] = !empty($_POST['moduleFolder']) ? $_POST['moduleFolder'] : strtolower( $_POST['moduleName'] );
    $_POST['moduleEntity'] = !empty($_POST['moduleEntity']) ? $_POST['moduleEntity'] : 'ent' . ucfirst( $_POST['moduleName'] );
        
    if( is_dir(PATH_MODULE . '/' . $_POST['moduleFolder']) ) {
        $aErr[] = _( 'Directory already exists' );
    }
    
    if( empty($aErr) ) {
        /**
         * Directory creation
         */
        $aDirectory = array(
            PATH_MODULE . '/' . $_POST['moduleFolder'],
            PATH_MODULE . '/' . $_POST['moduleFolder'] . '/models',
            PATH_MODULE . '/' . $_POST['moduleFolder'] . '/config',
            PATH_VIEW_HTML . '/' . $_POST['moduleFolder'],
        );        
        foreach( $aDirectory as $sDirectory ) {
            if( !mkdir($sDirectory) ) {
                throw new Exception( sprintf(_('Could not create directory %s'), $sDirectory) );
            }
        }
        
        // Fetch template code
        $sModuleTemplate = $sModuleFile = file_get_contents( PATH_MODULE . '/module/config/cfModule.php' );
        $sModuleDaoTemplate = $sModuleDaoFile = file_get_contents( PATH_MODULE . '/module/config/cfModuleDao.php' );
        
		// Config file
		if( $_POST['configFile'] == 'yes' ) {
			file_put_contents( PATH_MODULE . '/' . $_POST['moduleFolder'] . '/config/cf' . $_POST['moduleName'] . '.php', '<?php' );			
			$_POST['configFile'] = 'require_once PATH_MODULE . \'/' . $_POST['moduleFolder'] . '/config/cf' . $_POST['moduleName'] . '.php\';';
		} else {
			$_POST['configFile'] = '';
		}
		
        // Update code
        foreach( $_POST as $sKey => $sValue ) {           
            $sModuleFile = str_replace( '{' . $sKey . '}', $sValue, $sModuleFile );             
            $sModuleDaoFile = str_replace( '{' . $sKey . '}', $sValue, $sModuleDaoFile );
        }       
        
        // Create the files
		file_put_contents( PATH_MODULE . '/' . $_POST['moduleFolder'] . '/models/cl' . $_POST['moduleName'] . '.php', $sModuleFile );
        file_put_contents( PATH_MODULE . '/' . $_POST['moduleFolder'] . '/models/cl' . $_POST['moduleName'] . 'DaoMysql.php', $sModuleDaoFile );
        
        /**
         * ACL
         */
        $oAco = clRegistry::get( 'clAco' );
        $oAco->create( array(
            'acoKey' => 'write' . $_POST['moduleName'],
            'acoType' => 'dao',
            'acoGroup' => strtolower( $_POST['moduleName'] )
        ) );
        $oAco->create( array(
            'acoKey' => 'read' . $_POST['moduleName'],
            'acoType' => 'dao',
            'acoGroup' => strtolower( $_POST['moduleName'] )
        ) );
        $aErr = clErrorHandler::getValidationError( 'createAcl' );
        
        if( empty($aErr) ) {
            /**
             * Add admin permission
             */
            $oAcl = clRegistry::get( 'clAcl' );
            $oAcl->aroId = array();
            $oAcl->updateByAco( 'write' . $_POST['moduleName'], 'dao', 'admin', 'userGroup' );
            $oAcl->updateByAco( 'read' . $_POST['moduleName'], 'dao', 'admin', 'userGroup' );
            $aErr = clErrorHandler::getValidationError( 'updateAcl' );
            
            if( empty($aErr) ) {
                $oNotification = clRegistry::get( 'clNotificationHandler' );
                $oNotification->set( array(
                    'dataSaved' => _( 'The data has been saved' )
                ) );
				
				$_POST = array();
            }
        }
    }
}

$aFormDict = array(
    'entFormDict' => array(
        'moduleName' => array(
            'type' => 'string',
            'title' => _( 'Name' ),
            'required' => true
        ),
        'modulePrefix' => array(
            'type' => 'string',
            'title' => _( 'Prefix' )
        ),
        'moduleFolder' => array(
            'type' => 'string',
            'title' => _( 'Folder' )
        ),
        'moduleEntity' => array(
            'type' => 'string',
            'title' => _( 'Entity' )
        ),
		'configFile' => array(
			'type' => 'array',
            'title' => _( 'Config file?' ),
			'appearance' => 'full',
			'values' => array(
				'yes' => _( 'Yes' ),
				'no' => _( 'No' )
			)
		)
    )
);

// Form
$oOutputHtmlForm->init( $aFormDict, array(
	'attributes'	=> array(
		'class'	=> 'marginal'
	),
	'data' => $_POST,
	'errors' => $aErr,
	'labelSuffix' => ':',
	'method' => 'post',
	'buttons' => array(
		'submit' => _( 'Create' )
	)
) );
$oOutputHtmlForm->setFormDataDict( current($aFormDict) + array(
    'frmModuleAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
) );

echo '
	<div class="view module formAdd">
		<h1>' . _( 'Create module' ) . '</h1>
		' . $oOutputHtmlForm->render() . '
	</div>';