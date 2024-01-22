<?php

ini_set( 'memory_limit', '2048M' );
ini_set( 'max_execution_time', 300 );
set_time_limit( 300 );

require_once PATH_CONFIG . '/cfComposer.php';

$oNotification = clRegistry::get( 'clNotificationHandler' );

$sTools = '';
$sOutput = '';

/**
 * Handle commands
 */
$GLOBALS['aCommands'] = array();
function run( $sCommand ) {
	$GLOBALS['aCommands'][] = $sCommand; # 2>&1
	
	try {
		ob_start();
		$sReturn = system( $sCommand );
		ob_clean();
		return $sReturn;
		
	} catch( Exception $oException ) {
		throw new Exception( sprintf( _('Exception: "%s" on line %s in file %s at code %s'), $oException->getMessage(), $oException->getLine(), $oException->getFile(), $oException->getCode() ) );
		
	}
}

/**
 * Handle json file
 */
if( !empty($_POST['composerJson']) ) {
	if( file_exists(COMPOSER_JSON) ) {
		// Remove old json file
		unlink( COMPOSER_JSON );
	}
	
	$oFile = fopen( COMPOSER_JSON, "w" );
	$sReturn = fwrite( $oFile, $_POST['composerJson'] );
	fclose( $oFile );
	
	if( is_file(COMPOSER_JSON) && is_readable(COMPOSER_JSON) ) {
		$oNotification->setSessionNotifications( array(
			'dataSaved' => _( 'The "composer.json" has been saved' )
		) );
		$oRouter->redirect( $oRouter->sPath );
	}
}

/**
 * Actions
 */
if( !empty($_GET['action']) ) {
	switch( $_GET['action'] ) {
		/**
		 * Init Composer on platform
		 */
		case 'setup':
			/**
			 * Directory check
			 */
			if( !is_dir(PATH_COMPOSER) ) {
				if( !mkdir(PATH_COMPOSER) ) {
					throw new Exception( sprintf(_('Could not create directory %s'), PATH_COMPOSER) );
				}
				if( !mkdir(PATH_COMPOSER_APP) ) {
					throw new Exception( sprintf(_('Could not create directory %s'), PATH_COMPOSER_APP) );
				}
			} elseif( is_dir(PATH_COMPOSER) && is_file(COMPOSER_JSON) ) {
				/**
				 * Remove everything (except app dir) and start fresch
				 */
				rmdir( PATH_COMPOSER_VENDOR );
				rmdir( PATH_COMPOSER_CACHE );
				unlink( COMPOSER_PHAR );
				unlink( COMPOSER_JSON );
				unlink( COMPOSER_LOCK );
				// Additional files
				unlink( PATH_COMPOSER . '/keys.tags.pub' );
				unlink( PATH_COMPOSER . '/keys.dev.pub' );
				unlink( PATH_COMPOSER . '/.htaccess' );
			}
			
			/**
			 * Setup file
			 */
			$sSetupFile = PATH_COMPOSER . '/composer-setup.php';	
			if( file_exists($sSetupFile) ) {
				// Remove old setup file
				run( 'php -r "unlink(\'' . $sSetupFile . '\');"' );
			}
			
			// Download setup file
			run( 'php -r "copy(\'https://getcomposer.org/installer\', \'' . $sSetupFile . '\');"' );
			
			/**
			 * Verify installer
			 */
			$sResult = run( 'php -r "if (hash_file(\'SHA384\', \'' . $sSetupFile . '\') === \'' . COMPOSER_INSTALL_HASH . '\') { echo \'Installer verified\'; } else { echo \'Installer corrupt\'; unlink(\'composer-setup.php\'); } echo PHP_EOL;"' );
			
			if( $sResult == 'Installer verified' ) {
				/**
				 * Install verified
				 */
				run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" php ' . $sSetupFile . ' --install-dir=' . PATH_COMPOSER );	
				// Remove setup file
				run( 'php -r "unlink(\'' . $sSetupFile . '\');"' );
				
				// Force https
				run( 'php ' . PATH_COMPOSER . '/composer.phar config --global repos.packagist composer https://packagist.org' );
				
				$oNotification->set( array(
					'dataSaved' => _( 'Created "/Composer" direcotry in platform and downloaded setup file' )
				) );
			} else {
				/**
				 * Installer corrupt
				 */
				$oNotification->set( array(
					'dataResult' => 'Installer could not be verified, please check if install file hash in cfComposer.php is out dated.'
				) );
			}
			
			break;
		
		/**
		 * Install Composer
		 */
		case 'install':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' install' );
			$oNotification->set( array(
				'dataResult' => $sResult
			) );
			break;
		
		/**
		 * Update Composer
		 */
		case 'update':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' update' );
			$oNotification->set( array(
				'dataResult' => $sResult
			) );
			break;
		
		/**
		 * Require new requirement
		 */
		case 'require':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' require "' . $_GET['addRequirement'] . '"' );
			$oNotification->setSessionNotifications( array(
				'dataResult' => $sResult
			) );
			$oRouter->redirect( $oRouter->sPath );
			break;
		
		case 'updatePackage':
			if( !empty($_GET['package']) ) {
				$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' update "' . $_GET['package'] . '"' );
			} elseif( !empty($_GET['vendor']) ) {
				$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' update vendor/' . $_GET['vendor'] );
			}			
			$oNotification->setSessionNotifications( array(
				'dataResult' => $sResult
			) );
			$oRouter->redirect( $oRouter->sPath );
			break;
		
		case 'removePackage':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' remove vendor/' . $_GET['vendor'] );
			$oNotification->setSessionNotifications( array(
				'dataResult' => $sResult
			) );
			$oRouter->redirect( $oRouter->sPath );
			break;
		
		case 'showInstalled':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' show -i' );
			$oNotification->setSessionNotifications( array(
				'dataResult' => $sResult
			) );
			$oRouter->redirect( $oRouter->sPath );
			break;
		
		case 'createProject':
			$sResult = run( 'COMPOSER_HOME="' . PATH_COMPOSER . '" COMPOSER="' . COMPOSER_JSON . '" php ' . COMPOSER_PHAR . ' create-project' );
			$oNotification->setSessionNotifications( array(
				'dataResult' => $sResult
			) );
			$oRouter->redirect( $oRouter->sPath );
			break;
	}
}

/**
 * Application list
 */
if( file_exists(COMPOSER_JSON) ) {
	$oComposer = json_decode( file_get_contents( COMPOSER_JSON ) );
	$aApplications = array();
	foreach( $oComposer->autoload as $sType => $oApps ) {	
		foreach( $oApps as $sName => $sDir ) {
			$aApplications[] = array(
				'name' => ucfirst( $sName ),
				'standard' => $sType,			
				'source' => '/composer/' . $sDir . $sName
			);
			
			if( !is_dir( PATH_COMPOSER . '/' . $sDir . ucfirst($sName) ) ) {
				if( !mkdir( PATH_COMPOSER . '/' . $sDir . ucfirst($sName) ) ) {
					throw new Exception( sprintf(_('Could not create directory %s'), PATH_COMPOSER . '/' . $sDir . $sName) );
				}
			}
		}
	}
	
	if( !empty($aApplications) ) {
		$aDataDict = array(
			'entComposer' => array(
				'name' => array(
					'type' => 'string',
					'title' => _( 'Name' )
				),
				'standard' => array(
					'type' => 'string',
					'title' => _( 'Coding Standard' )
				),		
				'source' => array(
					'type' => 'string',
					'title' => _( 'Source' )
				)
			)
		);
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
		$oOutputHtmlTable->setTableDataDict( current($aDataDict) + array(
			'controls' => array(
				'title' => ''
			)
		) );
		
		foreach( $aApplications as $aApplication ) {
			$aApplication['controls'] = '';
			$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aApplication ) );
		}
		
		$sOutput = '
			<h3>' . _( 'Application' ) . '</h3>
			' . $oOutputHtmlTable->render();		
	}		
} else {
	$sOutput = '
		<h3>' . _( 'Application' ) . '</h3>
		<p><strong>' . _( 'No application exists yet' ) . '</strong></p>';
}

/**
 * Vendor list
 */
if( is_dir(PATH_COMPOSER_VENDOR) ) {
	$aVendors = scandir( PATH_COMPOSER_VENDOR );
	unset( $aVendors[0], $aVendors[1] );
	
	if( !empty($aVendors) ) {
		$oComposer = json_decode( file_get_contents( COMPOSER_JSON ) );
		
		$aVendorData = array();
		foreach( $aVendors as $sVendor ) {
			if( !is_dir(PATH_COMPOSER_VENDOR . '/' . $sVendor) ) {
				continue;
			}
			$aData = array(
				'name' => $sVendor,
				'version' => '',
				'inJson' => 'no',
				'package' => ''
			);			
			foreach( $oComposer->require as $sName => $sRequire ) {				
				if( strpos($sName, $sVendor) !== false ) {					
					$aData['version'] = $sRequire;					
					$aData['inJson'] = 'yes';
					$aData['package'] = $sName;
				}
			}
			$aVendorData[] = $aData;
		}
		
		$aDataDict = array(
			'entComposer' => array(
				'name' => array(
					'type' => 'string',
					'title' => _( 'Name' )
				),		
				'version' => array(
					'type' => 'string',
					'title' => _( 'Required version' )
				),		
				'inJson' => array(
					'type' => 'string',
					'title' => _( 'Added in json' )
				),
				'package' => array(
					'type' => 'string',
					'title' => _( 'Package' )
				)
			)
		);
		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $aDataDict );
		$oOutputHtmlTable->setTableDataDict( current($aDataDict) + array(
			'controls' => array(
				'title' => ''
			)
		) );
		
		foreach( $aVendorData as $aVendor ) {
			$aVendor['controls'] = '';
			if( !empty($aVendor['package']) ) {
				$aVendor['controls'] .= '
					<a href="?action=updatePackage&package=' . $aVendor['package'] . '" class="icon iconText iconRefresh linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Update' ) . '</a>';
			} else {
				$aVendor['controls'] .= '
					<a href="?action=updatePackage&vendor=' . $aVendor['name'] . '" class="icon iconText iconRefresh linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Update' ) . '</a>';
			}
			$aVendor['controls'] .= '
				<a href="?action=removePackage&vendor=' . $aVendor['name'] . '" class="icon iconText iconDelete linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Remove' ) . '</a>';
					
			$aVendor['inJson'] = '<span class="' . $aVendor['inJson'] . '">' . _( ucfirst($aVendor['inJson']) ) . '</span>';
			$oOutputHtmlTable->addBodyEntry( $oOutputHtmlTable->createDataRowByDataKey( $aVendor ) );
		}
		
		$sOutput .= '
			<h3>' . _( 'Vendors' ) . '</h3>
			' . $oOutputHtmlTable->render();
	}
} else {
	$sOutput .= '
		<p>&nbsp;</p>
		<h3>' . _( 'Vendors' ) . '</h3>
		<p><strong>' . _( 'No vendors exists yet' ) . '</strong></p>';
}

/**
 * Create and/or Edit the "composer.json" file
 */
if( file_exists(COMPOSER_PHAR) ) {
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$sFileContent = file_exists(COMPOSER_JSON) ? file_get_contents(COMPOSER_JSON) : $GLOBALS['COMPOSER_JSON_DEFAULT'];
	$sFrmComposerJson = '
		<section>
			<h3>' . _( 'Config file' ) . '</h3>
			' . $oOutputHtmlForm->createForm( 'post', $oRouter->sPath,
				$oOutputHtmlForm->createField( 'composerJson', _( 'Update a "composer.json" file' ),
					$oOutputHtmlForm->createTextarea( 'composerJson', _( 'Update a "composer.json" file' ), $sFileContent,
						array('style' => 'min-height: 250px;') ) .
					$oOutputHtmlForm->createButton( 'submit', _( 'Save' ) )
				), array( 'class' => 'vertical' )
			) . '
		</section>';
		
	$sAddRequirement = $oOutputHtmlForm->createForm( 'get', $oRouter->sPath,
		$oOutputHtmlForm->createField( 'addRequirement', _( 'Add requirement' ),
			$oOutputHtmlForm->createInput( 'string', 'addRequirement',
				array('placeholder' => _( 'New requirement' ) ) ) .
			$oOutputHtmlForm->createInput( 'hidden', 'action',
				array('value' => 'require' ) ) .
			$oOutputHtmlForm->createButton( 'submit', _( 'Add' ) )
		), array( 'class' => 'inline' )
	);
}

if( !file_exists(COMPOSER_PHAR) ) {
	$sOutput = '
		<p><a href="?action=setup" class="icon iconText iconAdd linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Add Composer to this system' ) . '</a></p>
		<p>&nbsp;</p>
		<p><strong>' . _( 'Composer does not yet exist on this system' ) . '</strong></p>';
}

echo '
	<div class="view composer tableEdit">
		<h1>' . _( 'Composer' ) . '</h1>
		' . (file_exists(COMPOSER_PHAR) ? '
		<section class="tools">
			<div class="tool">
				<a href="?action=setup" class="icon iconText iconGo linkConfirm" title="' . _( 'This will erase everything, are you sure?' ) . '">' . _( 'Reset everything' ) . '</a>
			</div>
			<div class="tool">
				<a href="?action=install" class="icon iconText iconGo linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Run "install"' ) . '</a>
			</div>
			<div class="tool">
				<a href="?action=update" class="icon iconText iconGo linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Run "update"' ) . '</a>
			</div>
			<div class="tool">
				<a href="?action=showInstalled" class="icon iconText iconGo linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Run "show -i"' ) . '</a>
			</div>
			<div class="tool">
				<a href="?action=createProject" class="icon iconText iconGo linkConfirm" title="' . _( 'Are you sure on this?' ) . '">' . _( 'Run "create-project"' ) . '</a>
			</div>
			<div class="tool">
				' . $sAddRequirement . '
			</div>
		</section>
		' : '') . '
		<section>
			' . $sOutput . '
		</section>
		' . (!empty($GLOBALS['aCommands']) ? '
		<section class="commands" style="font-size: .85em; opacity: .6;">
			<h3>' . _( 'Running commands' ) . '</h3>
			' . implode( '<br /><br />', $GLOBALS['aCommands'] ) . '
		</section>
		' : '') . '
		' . (!empty($sFrmComposerJson) ? '
		<section>
			' . $sFrmComposerJson . '
		</section>
		' : '') . '
	</div>';

/**
 * Codemirror
 */
$oTemplate->addScript( array(
	'key' => 'codemirrorJs',
	'src' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/lib/codemirror.js'
) );
$oTemplate->addScript( array(
	'key' => 'codemirrorJsCss',
	'src' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/mode/css/css.js'
) );
$oTemplate->addLink( array(
	'key' => 'codemirrorCss',
	'href' => '/modules/tinymce/plugins/codemirror/codemirror-4.8/lib/codemirror.css'
) );
$oTemplate->addBottom( array(
	'key' => 'codemirrorInit',
	'content' => '
	<script>
		var editor = CodeMirror.fromTextArea( document.getElementById("composerJson"), {
			lineNumbers: true,
			matchBrackets: true,
			autoCloseBrackets: true,
			mode: "application/ld+json",
			lineWrapping: true
		} );
  </script>'
) );