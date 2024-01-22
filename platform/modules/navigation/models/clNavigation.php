<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_FUNCTION . '/fData.php';
require_once PATH_MODULE . '/navigation/config/cfNavigation.php';

class clNavigation extends clModuleBase {

	public function __construct( $sGroupKey = null ) {
		$this->sModulePrefix = 'navigation';

		$this->oDao = clRegistry::get( 'clNavigationDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/navigation/models' );
		if( $sGroupKey !== null ) $this->setGroupKey( $sGroupKey );

		$this->initBase();
	}

	public function create( $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'create' . $this->sModuleName;
		$aData[$this->sModulePrefix . 'Created'] = date( 'Y-m-d H:i:s' );
		$aData['navigationLangId'] = $this->oDao->iLangId;

		$targetNode = !empty( $aData['navigationTarget'] ) ? (int) $aData['navigationTarget'] : null;
		if( !empty($aData['navigationRelation']) ) $aParams['relation'] = $aData['navigationRelation'];

		$aParams['criterias'] = 'navigationLangId = ' . (int) $this->oDao->iLangId;
		$aParams['criterias'] .= " AND navigationGroupKey = '" . $aData['navigationGroupKey'] . "'";

		$aParams['position'] = $this->oDao->oTreeDao->readNodePosition( $targetNode, $aParams );
		if( $this->oDao->oTreeDao->createNode($aData, $aParams) ) return $this->oDao->oDb->lastId();
		return false;
	}

	public function delete( $iPrimaryId, $sGroupKey = null ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		// Check allowed groups
		$oUser = clRegistry::get( 'clUser' );
		if( $sGroupKey !== null && !$oUser->oAclGroups->isAllowed('superuser') ) {
			
			// Check if this is a pseudo usergroups only for navigation			
			$oUserManager = clRegistry::get( 'clUserManager' );
			$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupTitle' );
			
			$aNavigationGroups = array();
			foreach( array_keys( $oUser->oAclGroups->aAcl ) as $groupKey ) {
				$aNavigationGroups[ $groupKey ] = $aUserGroups[ $groupKey ];
			}
			
			// Inject navigation groups that aren't real usergroups
			$aCustomNavigationGroups = (array) $this->readGroup();
			foreach( $aCustomNavigationGroups as $iKey => $sCustomGroupKey ) {
				if( array_key_exists( $sCustomGroupKey, $aUserGroups ) ) {
					// Real usergroup
					unset( $aCustomNavigationGroups[ $iKey ] );
				} else {
					// Pseudo usergroup
				}
			}
			
			if( !empty($aCustomNavigationGroups) && in_array($sGroupKey, $aCustomNavigationGroups) ) {
				// groupKey was a pseudo usergroup
			} else {
				// Check regular usergroups
				$aAllowedGroups = array_keys( $oUser->oAclGroups->aAcl );
				if( !in_array($sGroupKey, $aAllowedGroups) ) return false;
			}
		
		}
		if( $sGroupKey !== null ) $this->oDao->setGroupKey( $sGroupKey );
		return $this->oDao->delete( $iPrimaryId );
	}

	public function move( $iPrimaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		return $this->oDao->move( $iPrimaryId, $aData );
	}

	public function read( $aFields = array(), $navigationId = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'navigationId' => $navigationId
		);
		return $this->oDao->read( $aParams );
	}

	public function readByUrl( $sUrl, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		return $this->oDao->readByUrl( array(
			'navigationUrl' => $sUrl,
			'fields' => $aFields
		) );
	}

	public function readSubtree( $rootId, $aFields = array(), $navigationId = null, $iMaxDepth = null ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'navigationId' => $navigationId,
			'rootId' => $rootId,
			'maxDepth' => $iMaxDepth
		);
		return $this->oDao->read( $aParams );
	}

	public function readTreeByNode( $iNodeId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		if( empty($iNodeId) ) return false;
		
		return $this->oDao->readTreeByNode( array(
			'navigationId' => $iNodeId,
			'fields' => $aFields
		) );
	}

	public function readGroup() {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aGroups = array();
		$aData = $this->oDao->readGroup();
		foreach( $aData as $entry ) {
			$aGroups[] = $entry['navigationGroupKey'];
		}
		return $aGroups;
	}

	public function readWithParentsByUrl( $sUrl, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields
		);
		return $this->oDao->readWithParentsByUrl( $sUrl, $aParams );
	}

	/**
	 * Reads the siblings of a node
	 * @param integer $nodeId
	 * @param $aFields 
	 * 
	 * @return array
	 */	
	public function readSiblings( $nodeId, $aFields = array() ) {
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'nodeId' => $nodeId			
		);
		return $this->oDao->readSiblings($aParams);
	}
	
	/**
	 * Resets a navigation group with specified lang to a "flat" menu with no depths
	 *
	 * @return boolean
	 */
	public function resetDepth( $iLangId, $sGroupKey ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($iLangId) || empty($sGroupKey) ) return false;

		$aParams = array(
			'fields' => array(
				'navigationId',
				'navigationLeft',
				'navigationRight'
			)
		);
		
		// General criterias
		$aParams += array(
			'criterias' => 'navigationLangId = ' . $this->oDao->oDb->escapeStr( $iLangId ) . ' AND navigationGroupKey = ' . $this->oDao->oDb->escapeStr( $sGroupKey )
		);
		
		// Reset everything to zero		
		$this->oDao->updateData( array(
			'navigationLeft' => 0,
			'navigationRight' => 0
		), $aParams );
		
		$this->oDao->sGroupKey = $this->oDao->oDb->escapeStr($sGroupKey);
		$aTree = $this->oDao->readData( $aParams );
		
		$iOldLangId = $this->oDao->iLangId;
		$this->oDao->iLangId = $iLangId;
		if( !empty($aTree) ) {
			$iLeftCount = 1;
			$iRightCount = 2;
			foreach( $aTree as $entry ) {	
				// Run update on individual entries
				$this->oDao->update( $entry['navigationId'], array(
					'navigationLeft' => $iLeftCount,
					'navigationRight' => $iRightCount
				) );
				
				$iLeftCount += 2;
				$iRightCount += 2;
			}
			$this->iLangId = $iOldLangId;
			return true;
		}
		$this->oDao->iLangId = $iOldLangId;
		return false;
	}

	public function setGroupKey( $sGroupKey ) {
		$this->oDao->setGroupKey( $sGroupKey );
	}

	public function update( $iPrimaryId, $aData ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		$aParams['groupKey'] = 'update' . $this->sModuleName;
		if( $this->read('navigationId', $iPrimaryId) ) {
			return $this->oDao->update( $iPrimaryId, $aData, $aParams );
		}
		$aData['navigationId'] = $iPrimaryId;

		# Read left and right values of original
		$aPrimaryData = $this->oDao->readDataByPrimary( $iPrimaryId, array(
			'fields' => array(
				'navigationLeft',
				'navigationRight'
			)
		) );
		if( !empty($aPrimaryData) ) {
			$aData += current( $aPrimaryData );
			$aData['navigationLangId'] = $this->oDao->iLangId;
			return $this->oDao->createData( $aData, $aParams );
		}

		return $this->create( $aData );
	}

}