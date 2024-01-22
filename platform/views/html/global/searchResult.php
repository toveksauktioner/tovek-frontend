<?php

$bDisableArchive = true;

// Disable preview result when in limited mode
if( empty($_POST['searchResult']) ) return;
if( (DEFCON_LEVEL <= 4) && empty($_POST['searchResult']) ) return;

// if( !empty($_GET['ajax']) && (empty($_POST['frmSearch']) || empty($_POST['searchQuery'])) ) return;
$oAuctionEngine = clRegistry::get( 'clAuctionEngine', PATH_MODULE . '/auction/models' );

if( isset($_GET['showEnded']) ) {
  $_SESSION['auctionSearch']['showEnded'] = !empty( $_GET['showEnded'] );
} else if ( !isset($_SESSION['auctionSearch']['showEnded']) ) {
  $_SESSION['auctionSearch']['showEnded'] = false;
}


// Functions used from several points
function getImagesByParent( $sParentTable, $aParentId, $bSecondary = false ) {
  $aImages = false;

    $oObjectStorage = clRegistry::get( 'clObjectStorage', PATH_MODULE . '/objectStorage/models' );
    $oObjectStorage->oDao->aSorting = [
    	'entObjectStorageToDbObject.parentSort' => 'ASC',
    	'entObjectStorage.objectId' => 'ASC'
    ];
    $aObjectStorageImageData = $oObjectStorage->readWithParams( [
    	'type' => 'image',
    	'parentTable' => $sParentTable,
    	'parentId' => $aParentId,
    	'includeVariants' => true
    	]
    );
    $oObjectStorage->oDao->aSorting = [];
    if( !empty($aObjectStorageImageData) ) {
    	// Make a structured list of the images
    	foreach( $aObjectStorageImageData as $aObjectStorageImage ) {
    		$aImages[ $aObjectStorageImage['parentId'] ][ $aObjectStorageImage['objectId'] ][ $aObjectStorageImage['objectVariant'] ] = $aObjectStorageImage;
    	}
    }

    return $aImages;
}

function formatListOutput( $sType, $sTitle, $sPath, $sAttention = '', $sInformation = '', $aImages = null, $aToolData = [] ) {
  $sImage = '<i class="' . GLOBAL_SEARCH_MODULES[ $sType ]['icon'] . '"></i>';
  $aTools = [];

  if( !empty($aImages) ) {

    $aImage = current( $aImages );

    if( !empty($aImage['tn']) ) $sImage = '<img src="' . $aImage['tn']['objectUrl'] . '">';
  }

  foreach( $aToolData as $sToolType => $aToolData ) {
    foreach( $aToolData as $iId => $bActive ) {
      switch( $sToolType ) {
        case 'favorite':
          $sStatusClass = ( $bActive ? 'selected' : '' );
          $aTools[] = '<a href="?ajax=true&event=favoriteItem&favoriteItem=' . $iId . '" class="ajax favLink ' . $sStatusClass . '" data-status="' . ( $bActive ? "false" : "true" ) . '"><i class="fas fa-star"></i></a>';
          break;
      }
    }
  }

  $sListOutput = '
    <li class="linkToMain">
      ' . $sImage . '
      <h3><a href="' . $sPath . '" class="mainLink">' . $sTitle . '</a></h3>
      <div class="tools">' . implode( '', $aTools ) . '</div>
      <div class="attention">' . $sAttention . '</div>
      <div class="information">' . $sInformation . '</div>
    </li>';

  return $sListOutput;
}

$sOutput = '';
$sResultUrl = $oRouter->getPath( 'guestInfo-d4a2e826542000eb39e648ea3dafdc84' );
$iHitLimit = 1000;
$bPreviewResult = ( empty($_POST['searchResult']) || ($_POST['searchResult'] == 'preview') );

// Handle search requests
if( !empty($_POST['searchQuery']) ) {

  $_SESSION['auctionSearch']['searchQuery'] = $_POST['searchQuery'];
  $aSearchModules = ( !empty($_POST['searchModules']) ? $_POST['searchModules'] : array_keys(GLOBAL_SEARCH_MODULES) );


  // ---------------------------------------------------------------------------
  // AUCTION SEARCH
  if( in_array('auctionItems', $aSearchModules) ) {
    $oAuctionItemDao = $oAuctionEngine->getDao( 'AuctionItem' );
    $oAuctionItemDao->setEntries( $iHitLimit );

  	// Keep track of user's search strings
  	$oAuctionEngine->create_in_AuctionSearch( array(
  		'searchString' => $_SESSION['auctionSearch']['searchQuery'],
  		'searchUserId' => !empty($_SESSION['userId']) ? $_SESSION['userId'] : '',
  		'searchCreated' => date( 'Y-m-d H:i:s' )
  	) );

    // Remove ended if not requested to show
    if( $_SESSION['auctionSearch']['showEnded'] === false ) {
      $oAuctionItemDao->setCriterias( [
        'showEnded' => [
          'type' => '>=',
          'value' => date( 'Y-m-d H:i:s' ),
          'fields' => 'itemEndTime'
        ]
      ] );
    }

    // Remove items from test auction
    $oAuctionItemDao->setCriterias( [
      'noTestAuction' => [
        'type' => 'notIn',
        'value' => AUCTION_TEST_AUCTION_ID,
        'fields' => 'itemAuctionId'
      ]
    ] );

  	// Item data
    $oAuctionItemDao->aSorting = array( 'itemEndTime' => 'ASC' );
  	$aAutionItemsResult = $oAuctionEngine->readAuctionItem( [
  		'search' => $_SESSION['auctionSearch']['searchQuery'],
  		'fields' => [
         'itemId',
         'itemSortNo',
         'itemTitle',
         'itemEndTime',
         'itemPartId',
         'routePath'
      ],
  		'status' => [
        'active',
        'ended'
      ]
  	] );
    $oAuctionItemDao->aSorting = null;
    $oAuctionItemDao->sCriterias = null;

    $iCountItems = ( !empty($aAutionItemsResult) ? count($aAutionItemsResult) : 0 );
    $sListOutput = '';

    if( !empty($aAutionItemsResult) ) {
      if( $bPreviewResult ) {
        $sListOutput = '
          <h3><a href="' . $sResultUrl . '?searchModules=auctionItems">' . sprintf( _('%s träffar. Klicka för att se träffarna.'), $iCountItems ) . '</a></h3>';

      } else {
        $aAuctionInfo = valueToKey( 'partId', $oAuctionEngine->readAuction([
          'fields' => [
            'auctionId',
            'auctionTitle',
            'partId',
            'partTitle',
            'routePath'
          ],
          'partId' => arrayToSingle($aAutionItemsResult, null, 'itemPartId')
        ]) );

        // Get users favorites
  			if( empty($oAuctionEngine->aUserFavoriteItems) && !empty($_SESSION['userId']) ) {
  				$aUserFavoriteItems = $oAuctionEngine->readFavoritesByUser_in_AuctionItem( $_SESSION['userId'] );
  				if( !empty($aUserFavoriteItems) ) {
  					$oAuctionEngine->aUserFavoriteItems = arrayToSingle( $aUserFavoriteItems, 'itemId', 'itemId' );
  				}
  			}

        // Images
        $aImages = getImagesByParent( 'entAuctionItem', arrayToSingle($aAutionItemsResult, null, 'itemId'), true );

        $sListItemOutput = '';
        foreach( $aAutionItemsResult as $entry ) {
          if( !isset($aImages[ $entry['itemId'] ]) ) $aImages[ $entry['itemId'] ] = null;
          $sTitle = _( 'Rop' ) . ' ' . $entry['itemSortNo'] . ': ' . $entry['itemTitle'];
          $sAttention = '
            <div class="' . ( (strtotime($entry['itemEndTime']) < time()) ? 'red' : 'green' ) . '">
              <h4>' . _( 'Slutar' ) . '</h4>
              ' . convertTime( $entry['itemEndTime'], $entry['itemId'] ) . '
            </div>';
          $sInformation = '';
          if( !empty($aAuctionInfo[ $entry['itemPartId'] ]) ) {
            $aAuction = $aAuctionInfo[ $entry['itemPartId'] ];
            $sInformation .= '
              <a href="' . $aAuctionInfo[ $entry['itemPartId'] ]['routePath'] . '">
                <i class="fas fa-arrow-right"></i>
                ' . $aAuction['auctionTitle'] . ( !empty($aAuction['partTitle']) ? ' - ' . $aAuction['partTitle'] : '' ) . '
              </a>';
          }

          $sListItemOutput .= formatListOutput( 'auctionItems', $sTitle, $entry['routePath'], $sAttention, $sInformation, $aImages[ $entry['itemId'] ], [
            'favorite' => [
              $entry['itemId'] => ( !empty($oAuctionEngine->aUserFavoriteItems) && in_array($entry['itemId'], $oAuctionEngine->aUserFavoriteItems) )
            ]
          ] );
        }

        $sListOutput = '
          <input class="toggleAllCheckbox" type="checkbox" id="toggleAllAuctionItem" style="display: none;">
          <label class="toggleAll" for="toggleAllAuctionItem">
            <span class="all"><i class="fas fa-eye"></i></span>
            <span class="limited"><i class="fas fa-eye-slash"></i></span>
            ' . _( 'Visa' ) . '
            <span class="all">' . _( 'alla') . '</span>
          </label>
          <ul>
            ' . $sListItemOutput . '
          </ul>';
      }
    }

    $sOutput .= '
      <div class="group auctionItems">
      <h2>
        ' . GLOBAL_SEARCH_MODULES['auctionItems']['title'] . '
        <span class="count">' . $iCountItems . '</span><span class="searchingIcon"><i class="fas fa-sync-alt"></i></span>
        ' . ( $bPreviewResult ? '' : '<span class="showEndedItems">
          <input type="checkbox" id="showEndedItems"' . ( $_SESSION['auctionSearch']['showEnded'] ? ' checked="checked"' : '' ) . '>
          <label for="showEndedItems">' . _( 'Visa avslutade' ) . '</label>
        </span>' ) . '
      </h2>
      ' . $sListOutput . '
      </div>';
  }

  // ---------------------------------------------------------------------------
  // AUCTION ARCHIVE SEARCH
  if( !$bDisableArchive && in_array('auctionArchive', $aSearchModules) ) {
    $oBackEnd = clRegistry::get( 'clBackEnd', PATH_MODULE . '/backEnd/models' );
    $oBackEnd->setSource( 'entAuctionItem', 'itemId' );
    $oBackEnd->oDao->setEntries( $iHitLimit );

  	// Item data
  	$oBackEnd->oDao->aSorting = array( 'itemEndTime' => 'DESC' );
		$oBackEnd->oDao->setCriterias( [
			'itemSearch' => [
				'type' => 'like',
				'fields' => 'itemTitle',
				'value' => $_SESSION['auctionSearch']['searchQuery']
			],
      'ended' => [
        'type' => '=',
        'fields' => 'itemStatus',
        'value' => 'ended'
      ]
		] );
  	$aAuctionArchiveResult = $oBackEnd->read( [
     'itemId',
     'itemSortNo',
     'itemTitle',
     'itemEndTime',
     'itemPartId'
    ] );
    $oBackEnd->oDao->aSorting = null;

    $iCountItems = ( !empty($aAuctionArchiveResult) ? count($aAuctionArchiveResult) : 0 );
    $sListOutput = '';

    if( !empty($aAuctionArchiveResult) ) {
      if( $bPreviewResult ) {
        $sListOutput = '
          <h3><a href="' . $sResultUrl . '?searchModules=auctionArchive">' . sprintf( _('%s träffar. Klicka för att se träffarna.'), $iCountItems ) . '</a></h3>';

      } else {
        $oAuctionBackend = clRegistry::get( 'clAuctionBackend', PATH_MODULE . '/auctionTransfer/models' );
        $aAuctionInfo = valueToKey( 'partId', $oAuctionBackend->readAuction([
          'fields' => [
            'auctionId',
            'auctionTitle',
            'partId',
            'partTitle'
          ],
          'partId' => arrayToSingle($aAuctionArchiveResult, null, 'itemPartId')
        ]) );
        $sArchiveUrl = $oRouter->getPath( 'guestAuctionItemsArchived' );
        $sArchiveItemUrl = $oRouter->getPath( 'guestAuctionItemShowArchived' );

        // Images
        $aImages = getImagesByParent( 'entAuctionItem', arrayToSingle($aAuctionArchiveResult, null, 'itemId'), true );

        $sListItemOutput = '';
        foreach( $aAuctionArchiveResult as $entry ) {
          if( !isset($aImages[ $entry['itemId'] ]) ) $aImages[ $entry['itemId'] ] = null;
          $sTitle = _( 'Rop' ) . ' ' . $entry['itemSortNo'] . ': ' . $entry['itemTitle'];
          $sPath = $sArchiveItemUrl . '?itemId=' . $entry['itemId'];
          $sAttention = '
            <div class="' . ( (strtotime($entry['itemEndTime']) < time()) ? 'red' : 'green' ) . '">
              <h4>' . _( 'Slutar' ) . '</h4>
              ' . convertTime( $entry['itemEndTime'], $entry['itemId'] ) . '
            </div>';
          $sInformation = '';
          if( !empty($aAuctionInfo[ $entry['itemPartId'] ]) ) {
            $aAuction = $aAuctionInfo[ $entry['itemPartId'] ];
            $sInformation .= '
              <a href="' . $sArchiveUrl . '?auctionId=' . $aAuction['partId'] . '&partId=' . $aAuction['partId'] . '">
                <i class="fas fa-arrow-right"></i>
                ' . $aAuction['auctionTitle'] . ( !empty($aAuction['partTitle']) ? ' - ' . $aAuction['partTitle'] : '' ) . '
              </a>';
          }

          $sListItemOutput .= formatListOutput( 'auctionArchive', $sTitle, $sPath, $sAttention, $sInformation, $aImages[ $entry['itemId'] ] );
        }

        $sListOutput = '
          <input class="toggleAllCheckbox" type="checkbox" id="toggleAllAuctionArchive" style="display: none;">
          <label class="toggleAll" for="toggleAllAuctionArchive">
            <span class="all"><i class="fas fa-eye"></i></span>
            <span class="limited"><i class="fas fa-eye-slash"></i></span>
            ' . _( 'Visa' ) . '
            <span class="all">' . _( 'alla') . '</span>
          </label>
          <ul>
            ' . $sListItemOutput . '
          </ul>';
      }
    }

    $sOutput .= '
      <div class="group auctionArchive">
        <h2>' . GLOBAL_SEARCH_MODULES['auctionArchive']['title'] . ' <span class="count">' . $iCountItems . '</span><span class="searchingIcon"><i class="fas fa-sync-alt"></i></span></h2>
        ' . $sListOutput . '
      </div>';
  }

  // ---------------------------------------------------------------------------
  // INFORMATION SEARCH
  if( in_array('information', $aSearchModules) ) {

  	$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
  	$oLayout = clRegistry::get( 'clLayoutHtml' );
    $oPuff = clRegistry::get( 'clPuff', PATH_MODULE . '/puff/models' );

    $aRouteData = [];

  	// Search for info contents
  	$oInfoContent->oDao->setCriterias( [
  		'searchInfoContentWithHtmlEntities' => [
  			'fields' => [ 'contentTextId' ],
  			'value' => htmlentities($_SESSION['auctionSearch']['searchQuery']),
  			'type' => 'like'
  		],
      'active' => [
        'fields' => 'contentStatus',
        'value' => 'active',
        'type' => '='
      ]
  	] );
  	$aViewIds = arrayToSingle( $oInfoContent->read([
  		'contentTextId',
  		'contentViewId'
  	]), null, 'contentViewId' );
    $oInfoContent->oDao->setCriterias();

    if( !empty($aViewIds) ) {
      $aViewLayoutKeys = arrayToSingle( $oLayout->readByViewId($aViewIds), 'sectionLayoutKey', 'sectionLayoutKey' );
      if( !empty($aViewLayoutKeys) ) {
        $aRouteData += groupByValue( 'routeLayoutKey', $oRouter->readByLayout($aViewLayoutKeys, [
          'routeLayoutKey',
          'routePath'
        ]) );
      }
    }

    // Search layout blocks (puff)
  	$oPuff->oDao->setCriterias( [
  		'searchPuffWithHtmlEntities' => [
  			'fields' => [
          'text1.textContent',
          'text2.textContent',
          'text3.textContent'
        ],
  			'value' => htmlentities($_SESSION['auctionSearch']['searchQuery']),
  			'type' => 'like'
  		],
      'active' => [
        'fields' => 'puffStatus',
        'value' => 'active',
        'type' => '='
      ]
  	] );
  	$aPuffData = arrayToSingle( $oPuff->read([
  		'puffId',
      'puffTitleTextId',
      'puffContentTextId',
      'puffShortContentTextId',
      'routePath'
  	]), 'puffId', 'routePath' );
    $oPuff->oDao->setCriterias();

    if( !empty($aPuffData) ) {
      $aRouteData += groupByValue( 'routeLayoutKey', $oRouter->readByObject(array_keys($aPuffData), 'Puff', [
        'routeLayoutKey',
        'routePath'
      ]) );
    }


    // Get layout data for the route objects
    if( !empty($aRouteData) ) {
      $oLayout->oDao->setCriterias( [
        'layotKey' => [
          'fields' => 'layoutKey',
          'value' => array_keys( $aRouteData ),
          'type' => 'in'
        ]
      ] );
      $aInformationResult = $oLayout->read( [
        'layoutKey',
        'layoutTitleTextId',
        'layoutDescriptionTextId'
      ] );
    }

    $sListItemOutput = '';
    if( !empty($aInformationResult) ) {
      foreach( $aInformationResult as $entry ) {
        $sTitle = $entry['layoutTitleTextId'];
        $aCurrentRoute = current( $aRouteData[ $entry['layoutKey'] ] );
        $sPath = $aCurrentRoute['routePath'];
        $sInformation = $entry['layoutDescriptionTextId'];

        $sListItemOutput .= formatListOutput( 'information', $sTitle, $sPath, null, $sInformation );
      }
    }


    $iCountItems = ( !empty($aInformationResult) ? count($aInformationResult) : 0 );

    if( $bPreviewResult ) {
      $sListOutput = '
        <h3><a href="' . $sResultUrl . '?searchModules=information">' . sprintf( _('%s träffar. Klicka för att se träffarna.'), $iCountItems ) . '</a></h3>';

    } else {
      $sListOutput = '';

      if( !empty($sListItemOutput) ) {
        $sListOutput = '
          <input class="toggleAllCheckbox" type="checkbox" id="toggleAllInformation" style="display: none;">
          <label class="toggleAll" for="toggleAllInformation">
            <span class="all"><i class="fas fa-eye"></i></span>
            <span class="limited"><i class="fas fa-eye-slash"></i></span>
            ' . _( 'Visa' ) . '
            <span class="all">' . _( 'alla') . '</span>
          </label>
          <ul>
            ' . $sListItemOutput . '
          </ul>';
      }
    }

    $sOutput .= '
      <div class="group information">
        <h2>' . GLOBAL_SEARCH_MODULES['information']['title'] . ' <span class="count">' . $iCountItems . '</span><span class="searchingIcon"><i class="fas fa-sync-alt"></i></span></h2>
        ' . $sListOutput . '
      </div>';
  }


  // ---------------------------------------------------------------------------
  // HELP SEARCH
  if( in_array('help', $aSearchModules) ) {
    $oHelpTopic = clRegistry::get('clHelpTopic', PATH_MODULE . '/help/models' );
    $oHelpCategory = clRegistry::get('clHelpCategory', PATH_MODULE . '/help/models' );
    $oHelpTopic->oDao->setLang( $GLOBALS['langIdEdit'] );
    $oHelpCategory->oDao->setLang( $GLOBALS['langIdEdit'] );

  	$oHelpTopic->oDao->setCriterias( array(
  		'search' => [
  			'type' => 'like',
  			'value' => $_SESSION['auctionSearch']['searchQuery'],
  			'fields' => [
  				'text1.textContent',
  				'text2.textContent'
  			]
  		],
      [
        'type' => '=',
        'value' => 'active',
        'fields' => 'helpTopicStatus'
      ]
  	) );

  	$aHelpResult = $oHelpTopic->readWithCategory([
      'entHelpTopic.helpTopicId',
      'helpTopicTitleTextId',
      'helpTopicDescriptionTextId',
      'entHelpTopicToCategory.helpCategoryId',
      'routePath'
    ] );

    if( !empty($aHelpResult) ) {
      $aCategories = valueToKey( 'helpCategoryId', $oHelpCategory->read([
        'helpCategoryId',
        'helpCategoryTitleTextId',
        'routePath'
      ], arrayToSingle($aHelpResult, null, 'helpCategoryId')) );

      $sListItemOutput = '';
      foreach( $aHelpResult as $entry ) {
        $sTitle = $entry['helpTopicTitleTextId'];
        $sPath = $entry['routePath'];
        $sInformation = strip_tags( $entry['helpTopicDescriptionTextId'] );
        $sAttention = '';
        if( !empty($aCategories[ $entry['helpCategoryId'] ]) ) {
          $sAttention = '
            <div class="blue">
              <h4>' . _( 'Kategori' ) . '</h4>
              <a href="' . $aCategories[ $entry['helpCategoryId'] ]['routePath'] . '">' . $aCategories[ $entry['helpCategoryId'] ]['helpCategoryTitleTextId'] . '</a>
            </div>';
        }

        $sListItemOutput .= formatListOutput( 'help', $sTitle, $sPath, $sAttention, $sInformation );
      }
    }

    $iCountItems = ( !empty($aHelpResult) ? count($aHelpResult) : 0 );

    if( $bPreviewResult ) {
      $sListOutput = '
        <h3><a href="' . $sResultUrl . '?searchModules=help">' . sprintf( _('%s träffar. Klicka för att se träffarna.'), $iCountItems ) . '</a></h3>';

    } else {
      $sListOutput = '';

      if( !empty($sListItemOutput) ) {
        $sListOutput = '
          <input class="toggleAllCheckbox" type="checkbox" id="toggleAllHelp" style="display: none;">
          <label class="toggleAll" for="toggleAllHelp">
            <span class="all"><i class="fas fa-eye"></i></span>
            <span class="limited"><i class="fas fa-eye-slash"></i></span>
            ' . _( 'Visa' ) . '
            <span class="all">' . _( 'alla') . '</span>
          </label>
          <ul>
            ' . $sListItemOutput . '
          </ul>';
      }
    }

    $sOutput .= '
      <div class="group help">
        <h2>' . GLOBAL_SEARCH_MODULES['help']['title'] . ' <span class="count">' . $iCountItems . '</span><span class="searchingIcon"><i class="fas fa-sync-alt"></i></span></h2>
        ' . $sListOutput . '
      </div>';
  }

}

echo '
  <div class="view global searchResult ' . ( $bPreviewResult ? 'preview' : '' ) . '">
    ' . $sOutput . '
    <script>
      $( document ).on( "click", ".linkToMain", function() {
        var link = $( this ).find( "a.mainLink" ).attr( "href" );
        location.href = link;
      } );

      $( document ).on( "change", "#showEndedItems", function() {
        location.href = "' . $oRouter->sPath . '?showEnded=" + ( $(this).prop("checked") ? 1 : 0 );
      } );
    </script>
  </div>';
