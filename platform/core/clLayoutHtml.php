<?php

require_once PATH_CORE . '/clModuleBase.php';

class clLayoutHtml extends clModuleBase {

	private $aIncludedViewIds = array();
	private $iCurrentViewId;
	private $sOutput;

	public $oLayoutDao;
	public $sLayoutKey;
	public $sLayoutFile;
	public $aRenderedViews = array();

	public function __construct() {
		$this->sModulePrefix = 'layout';
		$this->sModuleName = 'Layout';
		$this->oDao = clRegistry::get( 'clLayoutDao' . DAO_TYPE_DEFAULT_ENGINE );

		$this->initBase();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );
		$this->oDao->createData($aData, $aParams);

		$aErr = clErrorHandler::getValidationError( 'createLayout' );

		if( empty($aErr) ) {
			if( file_exists(PATH_LAYOUT . '/' . $aData['layoutFile']) ) {
				$oRouter = clRegistry::get( 'clRouter' );
				$oTemplate = clRegistry::get( 'clTemplateHtml' );
				$oNotification = clRegistry::get( 'clNotificationHandler' );

				ob_start();
				require PATH_LAYOUT . '/' . $aData['layoutFile'];
				ob_end_clean();

				if( !empty($aTplSections) ) {
					$this->createSection( $aData['layoutKey'], $aTplSections );
				}
			}
			return true;
		}

		return false;
	}

	public function createSection( $sLayoutKey, $aSections ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aData = array();
		foreach( (array) $aSections as $value ) {
			$aData[] = array(
				'sectionKey' => $value,
				'sectionLayoutKey' => $sLayoutKey
			);
		}
		return $this->oDao->createMultipleData( $aData, array(
			'fields' => array(
				'sectionKey',
				'sectionLayoutKey'
			),
			'entities' => 'entLayoutSection',
			'groupKey' => 'createSection'
		) );
	}

	public function createViewToSection( $iViewId, $iSectionId, $iPosition = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->createViewToSection( $iViewId, $iSectionId, $iPosition );
	}

	public function delete( $sLayoutKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );


		$this->oEventHandler->triggerEvent( array(
			'preDeleteLayout' => $sLayoutKey
		), 'internal' );

		if( is_file( PATH_LAYOUT_CSS . '/' . $sLayoutKey . '.css') ) unlink( PATH_LAYOUT_CSS . '/' . $sLayoutKey . '.css' );

		return $this->oDao->delete( $sLayoutKey );
	}

	public function deleteCss( $sLayoutKey ) {

	}

	public function deleteCustom( $sLayoutKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( strpos( $sLayoutKey, 'guestInfo-' ) === false ) return false;

		// Check if page is protected
		$aLayoutData = $this->readCustom( array('layoutProtected'), $sLayoutKey );
		if( !empty($aLayoutData['layoutProtected']) && $aLayoutData['layoutProtected'] == 'yes' ) {
			// Prevent protected layout's from being deleted
			return false;
		}

		$aSectionViewData = $this->readSectionsAndViews( $sLayoutKey );
		if( empty($aSectionViewData) ) return false;

		$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );

		foreach( $aSectionViewData as $aEntry ) {
			if( $aEntry['viewId'] === null ) continue;
			if( count($this->readByViewId( array($aEntry['viewId']) )) > 1 ) continue;

			$iContentId = current( $oInfoContent->readByView($aEntry['viewId'], array('contentId')) );

			// Read view data
			$oViewHtml = clRegistry::get( 'clViewHtml' );
			$aCurrentView = $oViewHtml->read( array('viewModuleKey', 'viewFile'), $aEntry['viewId'] );

			if( !empty($aCurrentView) ) {
				if( $aCurrentView['viewModuleKey'] . '/' . $aCurrentView['viewFile'] == 'infoContent/show.php' ) {
					$this->oEventHandler->triggerEvent( array(
						'preDeleteView' => $aEntry['viewId']
					), 'internal' );
				}
			}

			$oInfoContent->delete( $iContentId );
		}

		$this->oEventHandler->triggerEvent( array(
			'preDeleteLayout' => $sLayoutKey
		), 'internal' );

		if( is_file(PATH_LAYOUT_CSS . '/' . $sLayoutKey . '.css') ) unlink( PATH_LAYOUT_CSS . '/' . $sLayoutKey . '.css' );

		return $this->oDao->delete( $sLayoutKey );
	}

	public function deleteViewToSection( $iViewId, $iSectionId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->deleteViewToSection( $iViewId, $iSectionId );
	}

	public function readCustom( $aFields = array(), $iPrimaryId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		if( $iPrimaryId !== null ) return current( $this->oDao->readDataByPrimary($iPrimaryId, $aParams) );
		return $this->oDao->readCustom( $aParams );
	}

	public function readSectionsAndViews( $sLayoutKey, $sSectionKey = null ) {
		return $this->oDao->readSectionsAndViews( $sLayoutKey, $sSectionKey );
	}

	public function readSectionId( $sLayoutKey, $sSectionKey ) {
		return $this->oDao->readSectionId( $sLayoutKey, $sSectionKey );
	}

	public function readByViewId( $iViewId ) {
		return $this->oDao->readByViewId( $iViewId );
	}

	public function renderView( $sFile ) {
		$this->aRenderedViews[ $sFile ] = is_file( PATH_VIEW_HTML . '/' . $sFile );

		if( $this->aRenderedViews[ $sFile ] ) {
			$oRouter = clRegistry::get( 'clRouter' );
			$oTemplate = clRegistry::get( 'clTemplateHtml' );
			$oNotification = clRegistry::get( 'clNotificationHandler' );
			$oUser = clRegistry::get( 'clUser' );
			
			try {
				ob_start();
				require PATH_VIEW_HTML . '/' . $sFile;
				
			} catch( Throwable $oThrowable ) { if( $GLOBALS['debug'] ) outputCriticalException( $oThrowable ); }				
			catch( Exception $oException ) { if( $GLOBALS['debug'] ) outputCriticalException( $oException ); }
			
			return ob_get_clean();
		}
		return;
	}

	public function renderViewById( $iViewId ) {
		$oUser = clRegistry::get( 'clUser' );

		// Check permission
		$oAclView = new clAcl();
		if( $oUser->iId !== null ) $oAclView->setAro( $oUser->iId, 'user' );
		$oAclView->setAro( array_keys($oUser->aGroups), 'userGroup' );

		$oAclView->readToAclByAro( 'view', $iViewId );
		if( $oAclView->isAllowed($iViewId, 'view') ) {
			$oView = clRegistry::get( 'clViewHtml' );
			$aData = $oView->read( array(
				'viewModuleKey',
				'viewFile'
			), $iViewId );
			if( !empty($aData) ) {
				$this->iCurrentViewId = $iViewId;
				return $this->renderView( $aData['viewModuleKey'] . '/' . $aData['viewFile'] );
			}
			return;
		} else {
			return _( 'No access to this view' ) . ' - ' . $iViewId;
		}

	}

	public function isAllowed( $sLayoutKey = null )	{
		$oAclLayout = new clAcl();
		$oUser = clRegistry::get( 'clUser' );

		if( $oUser->iId !== null ) $oAclLayout->setAro( $oUser->iId, 'user' );
		$oAclLayout->setAro( array_keys($oUser->aGroups), 'userGroup' );
		$oAclLayout->readToAclByAro( 'layout', $sLayoutKey );

		if( !$oAclLayout->isAllowed( $sLayoutKey, 'layout' ) )
			return false;

		$aSectionsFromDb = $this->oDao->readSectionsAndViews( $sLayoutKey );
		$aLayoutSections = array();

		$aViewIds = arrayToSingle( $aSectionsFromDb, null, 'viewId' );
		$oAclView = new clAcl();
		$oAclView->setAro( array_keys($oUser->aGroups), 'userGroup' );
		$oAclView->readToAclByAro( 'view', $aViewIds );

		foreach( $aSectionsFromDb as $aSectionViews ) {
			if( !(!empty($aSectionViews['viewFile']) && $oAclView->isAllowed($aSectionViews['viewId'], 'view')) ) {
				return false;
			}
		}

		return true;
	}

	public function render( $sLayoutKey = null, $sLayoutFile = null, $sSection = null ) {
		if( $sLayoutKey !== null ) $this->sLayoutKey = $sLayoutKey;
		if( $sLayoutFile !== null ) $this->sLayoutFile = $sLayoutFile;
		$aIncludedViewIds = array();

		$oUser = clRegistry::get( 'clUser' );

		// Check permission
		$oAclLayout = new clAcl();
		if( $oUser->iId !== null ) $oAclLayout->setAro( $oUser->iId, 'user' );
		$oAclLayout->setAro( array_keys($oUser->aGroups), 'userGroup' );

		$oAclLayout->readToAclByAro( 'layout', $this->sLayoutKey );
		$oAclLayout->hasAccess( $this->sLayoutKey, 'layout' );
		unset( $oAclLayout );

		$oTemplate = clRegistry::get( 'clTemplateHtml' );
		$oNotification = clRegistry::get( 'clNotificationHandler' );

		// Add notifications stored in session
		$oNotification->addSessionNotifications();

		$aLayoutData = current( $this->read(array(
			'layoutTitleTextId',
			'layoutKeywordsTextId',
			'layoutDescriptionTextId',
			'layoutCanonicalUrlTextId',
			'layoutSuffixContent'
		), $this->sLayoutKey) );

		$oTemplate->setTitle( !empty($aLayoutData['layoutTitleTextId']) ? $aLayoutData['layoutTitleTextId'] : SITE_TITLE );
		$oTemplate->setKeywords( $aLayoutData['layoutKeywordsTextId'] );
		$oTemplate->setDescription( $aLayoutData['layoutDescriptionTextId'] );
		$oTemplate->setCanonicalUrl( $aLayoutData['layoutCanonicalUrlTextId'] );

		if( !empty($aLayoutData['layoutSuffixContent']) ) {
			if( $GLOBALS['debug'] === true ) {
				$aLayoutData['layoutSuffixContent'] = '<!--' . $aLayoutData['layoutSuffixContent'] . '-->';
			}
			$oTemplate->addBottom( array(
				'key' => 'layoutSuffixContent',
				'content' => $aLayoutData['layoutSuffixContent']
			) );
		}

		unset( $aLayoutData );

		$aSectionsFromDb = $this->oDao->readSectionsAndViews( $this->sLayoutKey, ($sSection !== null ? $sSection : null) );
		$aLayoutSections = array();

		require_once PATH_FUNCTION . '/fData.php';
		$aViewIds = arrayToSingle( $aSectionsFromDb, null, 'viewId' );
		$oAclView = new clAcl();
		if( $oUser->iId !== null ) $oAclView->setAro( $oUser->iId, 'user' );
		$oAclView->setAro( array_keys($oUser->aGroups), 'userGroup' );
		$oAclView->readToAclByAro( 'view', $aViewIds );

		if( empty($_GET['ajax']) || (!empty($_GET['ajax']) && empty($_GET['layout'])) ) {
			$oRouter = clRegistry::get( 'clRouter' );
			ob_start();
			require_once PATH_LAYOUT . '/' . $this->sLayoutFile;
			$sLayoutOutput = ob_get_clean();
		}

		$aCssFiles	= array();

		foreach( $aSectionsFromDb as $aSectionViews ) {
			if( !isset($aLayoutSections[$aSectionViews['sectionKey']]) ) $aLayoutSections[$aSectionViews['sectionKey']] = '';
			if( !empty($aSectionViews['viewFile']) && $oAclView->isAllowed($aSectionViews['viewId'], 'view') ) {
				$this->iCurrentViewId = $aSectionViews['viewId'];
				$this->aIncludedViewIds[] = $this->iCurrentViewId;

				$aLayoutSections[$aSectionViews['sectionKey']] .= $this->renderView(( !empty($aSectionViews['viewModuleKey']) ? $aSectionViews['viewModuleKey'] . '/' : '' ) . $aSectionViews['viewFile']);
				
				if( !in_array($oRouter->sCurrentTemplateFile, array('tovek.php','tovekClassic.php')) ) {
					// File info
					$sFilename		= pathinfo($aSectionViews['viewFile'], PATHINFO_FILENAME );
					$aCSSFiles[]	= 'views/html/' . $aSectionViews['viewModuleKey'] . '/' . $sFilename;
				}

			} else {
				$aLayoutSections[$aSectionViews['sectionKey']] .= _( 'View not accessible' ) . ' - ' . $aSectionViews['viewId'];
			}
		}

		$aIncludedViewIds = $this->aIncludedViewIds;
		foreach( $aIncludedViewIds as $key => $iViewId ) {
			$aIncludedViewIds[$key] = $iViewId;
		}

		// Add css links
		if( !empty($aCSSFiles) ) {
			$oTemplate->addLink( array(
				'key' => 'viewCss',
				'href' => '/css/index.php?include=' . implode( ';', $aCSSFiles )
			) );
		}

		if( !empty($_GET['ajax']) && !empty($_GET['section']) ) return $aLayoutSections[$_GET['section']];

		$aSectionKeys = array_keys($aLayoutSections);
		if( !empty($aTplSections) ) $aSectionKeys = array_merge( $aSectionKeys, array_diff($aTplSections, $aSectionKeys) );

		// Notifications
		$sNotification = '';
		$aNotifications = $oNotification->get();
		$aNotificationErrors = $oNotification->getError();
		if( !empty($aNotificationErrors) ) {
			if( ctype_digit( substr($key, 0, 1) ) ) $key = 'notification-' . $key;

			$sNotification .= '
			<ul class="notification error">';
			foreach( $aNotificationErrors as $key => $value ) {
				$sNotification .= '
				<li class="notification ' . $key . '">' . $value . '</li>';
			}
			$sNotification .= '
			</ul>';
		}

		if( !empty($aNotifications) ) {
			$sNotification .= '
			<ul class="notification highlight">';
			foreach( $aNotifications as $key => $value ) {
				if( ctype_digit( substr($key, 0, 1) ) ) $key = 'notification-' . $key;

				$sNotification .= '
				<li class="notification ' . $key . '">' . $value . '</li>';
			}
			$sNotification .= '
			</ul>';
		}

		$aLayoutSections['{tplNotification}'] = $sNotification;
		$aSectionKeys[] = '{tplNotification}';

		return str_replace( $aSectionKeys, $aLayoutSections, $sLayoutOutput );
	}

	public function renderCustom( $aSectionContent, $sLayoutFile = null ) {
		if( $sLayoutFile === null ) $sLayoutFile = $this->sLayoutFile;

		$aSectionContent['{tplNotification}'] = '';
		if( !empty($aNotifications) ) {
			$aSectionContent['{tplNotification}'] = '
			<ul class="notification highlight">';
			foreach( $aNotifications as $key => $value ) {
				if( ctype_digit( substr($key, 0, 1) ) ) $key = 'notification-' . $key;

				$aSectionContent['{tplNotification}'] .= '
				<li class="notification ' . $key . '">' . $value . '</li>';
			}
			$aSectionContent['{tplNotification}'] .= '
			</ul>';
		}

		ob_start();
		require_once PATH_LAYOUT . '/' . $sLayoutFile;
		$sLayoutOutput = ob_get_clean();

		if( !isset( $aSectionContent['{tplNotification}'] )) $aSectionContent['{tplNotification}'] = ''; // Make sure the notification placeholder isn't shown even if it's content isn't set
		return str_replace( array_keys($aSectionContent), $aSectionContent, $sLayoutOutput );
	}

	public function update( $sLayoutKey, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Updated'] = date( 'Y-m-d H:i:s' );
		return $this->oDao->update( $sLayoutKey, $aData, $aParams );
	}

	public function updateLayoutFile( $sLayoutKey, $sLayoutFile, $bKeepViews = false ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		
		// Existing section views
		$aOldSectionViews = groupByValue( 'sectionKey', $this->readSectionsAndViews($sLayoutKey) );
		
		$this->oDao->updateLayoutFile( $sLayoutKey, $sLayoutFile );
		
		if( file_exists(PATH_LAYOUT . '/' . $sLayoutFile) ) {
			ob_start();
			require PATH_LAYOUT . '/' . $sLayoutFile;
			ob_end_clean();
			
			if( !empty($aTplSections) ) {
				$this->createSection( $sLayoutKey, $aTplSections );
				
				if( !empty($aOldSectionViews) && $bKeepViews == true ) {
					// New section IDs
					$aSectionIds = arrayToSingle( $this->readSectionsAndViews( $sLayoutKey ), 'sectionKey', 'sectionId' );
					
					foreach( $aOldSectionViews as $sSection => $aViews ) {
						foreach( $aViews as $aView ) {
							if( empty($aSectionIds[ $sSection ]) ) continue;
							
							// Re-add view
							$this->createViewToSection( $aView['viewId'], $aSectionIds[ $sSection ] );
						}
					}
				}
			}
			return true;
		}
		return false;
	}

	public function updateViewPosition( $iSectionId, $aViewPositions ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->updateViewPosition( $iSectionId, $aViewPositions );
	}
}
