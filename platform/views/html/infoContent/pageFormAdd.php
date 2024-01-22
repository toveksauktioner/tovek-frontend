<?php

$sOutput = '';

$oTemplate->addBottom( array(
	'key' => 'jsToggleNavigationAdd',
	'content' => '
	<script>
		if( !$(".fieldGroup.navigation .boolean #navigationFormAdd").is(":checked") ) {
			$(".fieldGroup.navigation .field:not(.boolean)").hide();
		}

		$(".fieldGroup.navigation .boolean #navigationFormAdd").on( "click", function() {
			if( $(this).is(":checked") ) {
				$(".fieldGroup.navigation .field:not(.boolean)").slideDown();
			} else {
				$(".fieldGroup.navigation .field:not(.boolean)").slideUp();
			}

			var layoutTitleText = $("#layoutTitleTextId").val();
			var $navigationTitle = $("input#navigationTitle");

			if( $navigationTitle.val() == "" ) {
				$navigationTitle.val( layoutTitleText );
			}
		} );
	</script>'
) );

$oTemplate->addScript( array(
	'key' => 'jsTinyMce',
	'src' => '/modules/tinymce/tiny_mce.js'
) );
$oTemplate->addScript( array(
	'key' => 'jsTinyMceConfig',
	'src' => '/modules/tinymce/config/basic.js.php' . (!empty($_GET['layoutKey']) ? '?layoutKey=' . $_GET['layoutKey'] : '')
) );
$oTemplate->addScript( array(
	'key' => 'jsAutoGrowInput',
	'src' => '/js/jquery.autoGrowInput.js'
) );
$oTemplate->addBottom( array(
	'key' => 'autoGrowInputInit',
	'content' => '
	<script>
		$(document).ready( function () {
			$("input#routePath").autoGrowInput( {
				maxWidth: 940,
				minWidth: 100,
				comfortZone: 20
			} ).trigger("update");
		} );
	</script>'
) );
if( empty($_GET['layoutKey']) && empty($_GET['useNav']) ) {
	$oTemplate->addBottom( array(
		'key' => 'autoRoutePath1',
		'content' => '
			<script>
				$("#layoutTitleTextId").keyup(function() {
					var sValue = $("input#routePath").val();
						if( $("input#routePath").attr("readonly") == "readonly" && sValue != "/" ) {
						var sContent = $(this).val();
						sContent = strToUrl( sContent );
						$("input#routePath").val(sContent);
						$("input#routePath").trigger("update");
					}
				});
			</script>'
	) );
}
$oTemplate->addBottom( array(
	'key' => 'autoRoutePath2',
	'content' => '
		<script>
			$("#changeRoutePath").click(function() {
				if( $("input#routePath").attr("readonly") == "readonly" ) {
					$("input#routePath").removeAttr("readonly");

					$("input#routePath").removeClass("autoFill");

					$("#changeRoutePath").html("' . _( 'Auto fill path' ) . '");
					$("input#routePath").trigger("update");

				} else {
					var sContent = $("#layoutTitleTextId").val().toLowerCase().replace(/ /g,"-");
					$("input#routePath").val(sContent);

					$("input#routePath").addClass("autoFill");

					$("input#routePath").attr("readonly", "readonly");
					$("#changeRoutePath").html("' . _( 'Enter the path manually' ) . '");
					$("input#routePath").trigger("update");
				}
			});
		</script>'
) );

$aErr = array();
$sNavigationDeletion = '';
$sUrlRevisionShow = $oRouter->getPath( 'adminInfoContentRevisionShow' );

$oAcl = new clAcl();
$oAcl->setAcl( array(
	'writeLayout' => 'allow'
) + $oUser->oAcl->aAcl );

require_once PATH_FUNCTION . '/fData.php';
$oInfoContent = clRegistry::get( 'clInfoContent', PATH_MODULE . '/infoContent/models' );
$oInfoContent->oDao->setLang( $GLOBALS['langIdEdit'] );
$oLayout = clRegistry::get( 'clLayoutHtml' );
$oLayout->setAcl( $oAcl );
$oLayout->oDao->setLang( $GLOBALS['langIdEdit'] );
$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
$oNavigation->setGroupKey( 'guest' );
$oNavigation->oDao->setLang( $GLOBALS['langIdEdit'] );

// Remove all revisions
if( !empty($_GET['truncateInfoContentRevision']) && $_GET['truncateInfoContentRevision'] == true && $oUser->oAclGroups->isAllowed('superuser') ) {
	$aViews = arrayToSingle( $oLayout->readSectionsAndViews($_GET['layoutKey']), 'viewId', null );
	$aInfoContent = $oInfoContent->readByView( array_keys($aViews), array(
		'contentId'
	) );
	if( !empty($aInfoContent) ) {
		$aRevisions = $oInfoContent->readRevisionByContentId( array(
			'revisionId'
		), arrayToSingle( $aInfoContent, null, 'contentId') );
		if( !empty($aRevisions) ) {
			foreach( $aRevisions as $aRevision ) {
				$oInfoContent->deleteRevision( $aRevision['revisionId'] );
			}
		}
	}

	$oRouter->redirect( $oRouter->sPath . '?' . stripGetStr( array('deleteInfoContentRevision', 'infoContentRevisonImport', 'truncateInfoContentRevision') )  );
}

// Preview
if( isset($_POST['preview']) ) {
	$_SESSION['infoContentPreview'] = array();
	foreach( (array) $_POST['contentTextId'] as $key => $value ) {
		if( empty($_GET['layoutKey']) ) {
			$_SESSION['infoContentPreview'][] = $value;
		} else {
			$_SESSION['infoContentPreview'][ $_POST['contentKey'][$key] ] = $value;
		}
	}

	$oTemplate->addBottom( array(
		'key' => 'jsPreview',
		'content' => '
		<script>
			var WindowObjectReference;
			function openPreviewWindow() {
				if( WindowObjectReference == null || WindowObjectReference.closed ) {
					WindowObjectReference = window.open("' . $oRouter->getPath( 'guestInfoContentPreview' ) . '", "Preview", "resizable=yes,scrollbars=yes,status=yes");
				} else {
					if( WindowObjectReference.focus ) {
						WindowObjectReference.focus();
					};
				};
			}
			openPreviewWindow();
		</script>'
	) );
	$oNotification->set( array(
		'dataInformation' => _( 'A popup window with a preview has been opened. If not then enable/allow popups and try again.' )
	) );

} elseif( !empty($_POST['frmInfoContentAdd']) ) {
	if( empty($_POST['layoutTitleTextId']) ) {
		$aErr += array(
			'layoutTitleTextId' => _( 'Your page must have a title' )
		);
	}
	
	if( empty($aErr) ) {
		// Delete autosave
		$oTinyMceAutoSave = clRegistry::get( 'clTinyMceAutoSave', PATH_MODULE . '/tinyMceAutoSave/models' );
		$oTinyMceAutoSave->deleteByChkSum( $_POST['frmAutosaveData'] );
	
		if( empty($_POST['routePath']) ) {
			// Generate route from title and notify user
			$_POST['routePath'] = $_POST['layoutTitleTextId'];
	
			$oNotification->set( array(
				'dataInformation' => _( 'You didn´t enter a route so the system automatically generated one.' )
			) );
		}
	
		// Sanitize routepath
		$_POST['routePath'] = ( empty($_POST['routePath']) && mb_substr($_POST['routePath'], 0, 1) != '/' ? '/' : '' ) . strToUrl(trim($_POST['routePath']));
		if( $_POST['routePath'][0] != '/' ) $_POST['routePath'] = '/' . $_POST['routePath'];
	
		// Update
		if( !empty($_GET['layoutKey']) ) {
			$aRouteDupeData = $oRouter->oDao->readRouteByPath( $_POST['routePath'] );
			if( !empty($aRouteDupeData) && $aRouteDupeData['layoutKey'] != $_GET['layoutKey'] ) {
				$aErr += array(
					'routePath' => _( 'The route you entered is already in use' )
				);
			} else {
				$oLayout->update( $_GET['layoutKey'], $_POST );
				$aErr = clErrorHandler::getValidationError( 'updateLayout' );
				$oRouter->updateByLayout( $_GET['layoutKey'], $_POST );
				$aErr += clErrorHandler::getValidationError( 'updateRoute' );
	
				$aView = current( $oLayout->readSectionsAndViews($_GET['layoutKey']) );
				$iViewId = $aView['viewId'];
	
				foreach( (array) $_POST['contentTextId'] as $key => $value ) {
					$aData = array(
						'contentKey' => $_POST['contentKey'][$key],
						'contentTextId' => $value,
						'contentStatus' => $_POST['contentStatus']
					);
	
					// Handle status changes. Only change status it it affect a single layout.
					// If occuring in mulitple places, the status for that particular infocontent won't change.
					if( isset( $aData['contentStatus'] ) ) {
						$aInfoContentView = current( $oInfoContent->read( 'contentViewId', $key ) ); // Get the view ID for this infocontent
						$results = $oLayout->readByViewId( $aInfoContentView ); // Get occurances of viewId in entviewtosection
						if( count( $results ) > 1 ) {
							unset( $aData['contentStatus'] ); // If occuring more then once, do not change this status
						}
					}
	
					$oInfoContent->update( $key, $aData );
	
					/**
					 * Do not save revision as 'superUser'
					 */
					if( !empty($_SESSION['user']['acl']['superuser']) && $_SESSION['user']['acl']['superuser'] == 'allow' ) {
						$aUserData = current( $oUser->oDao->read( array(
							'username' => 'admin',
							'fields' => array( 'userId', 'username' )
						) ) );
						if( !empty($aUserData) ) {
							$sRevisionUserId = $aUserData['userId'];
							$sRevisionUsername = $aUserData['username'];
						} else {
							// Could not find a admin user
							$sRevisionUserId = $_SESSION['userId'];
							$sRevisionUsername = $oUser->readData( 'username' );
						}
					} else {
						$sRevisionUserId = $_SESSION['userId'];
						$sRevisionUsername = $oUser->readData( 'username' );
					}
	
					// Create revision data
					$oInfoContent->createRevision( array(
						'contentId' => $key,
						'userId' => $sRevisionUserId,
						'username' => $sRevisionUsername,
						'revisionContent' => $value
					) );
				}
				$aErr += clErrorHandler::getValidationError( 'updateInfoContent' );
	
				// Navigation creation
				if( !empty($_POST['navigationFormAdd']) && $_POST['navigationFormAdd'] == true ) {
					$_POST['navigationGroupKey'] = 'guest';
					$_POST['navigationUrl'] = $_POST['routePath'];
					if( $iNavigationId = $oNavigation->create($_POST) ) {
	
					} else {
						$aErr += clErrorHandler::getValidationError( 'createNavigation' );
					}
				}
	
				if( isset($_POST['submitAndGoToList']) ) {
					$oRouter->redirect( $oRouter->getPath( 'adminInfoContentPages' ) );
				}
			}
	
		// Create
		} else {
			//$_POST['contentTitle'] = $_POST['layoutTitle'];
	
			// Auto generate contentKey
			if( empty($_POST['contentKey']) ) $_POST['contentKey'] = 'contentKey-' . strToUrl(trim($_POST['layoutTitleTextId'])) . '-' . md5(time());
			
			// Set route path also as canonical url
			if( empty($_POST['layoutCanonicalUrlTextId']) ) $_POST['layoutCanonicalUrlTextId'] = $_POST['routePath'];
			
			$aRouteDupeData = $oRouter->oDao->readRouteByPath( $_POST['routePath'], $_SESSION['langIdEdit'] );
			if( !empty($aRouteDupeData) ) {
				$aErr += array(
					'routePath' => _( 'The route you entered is already in use' )
				);
			} else {
				$iInfoContentId = $oInfoContent->create( $_POST );
				$aErr = clErrorHandler::getValidationError( 'createInfoContent' );
	
				if( empty($aErr) ) {
					$_POST['layoutKey'] = 'guestInfo-' . md5(time());
					$_POST['layoutFile'] = INFOCONTENT_DEFAULT_LAYOUT_FILE;
					$_POST['layoutTemplateFile'] = INFOCONTENT_DEFAULT_TEMPLATE_FILE;
					$oLayout->create( $_POST );
					$aErr += clErrorHandler::getValidationError( 'createLayout' );
	
					if( empty($aErr) ) {
						$iViewId = current( current($oInfoContent->read('contentViewId', $iInfoContentId)) );
						$iSectionId = $oLayout->readSectionId( $_POST['layoutKey'], INFOCONTENT_DEFAULT_LAYOUT_SECTION );
						$oLayout->createViewToSection( $iViewId, $iSectionId );
	
						// Additional included views
						if( !empty($GLOBALS['infocontent_additional_views']) ) {
							foreach( $GLOBALS['infocontent_additional_views'] as $sSectionKey => $aViews ) {
								if( !empty($aViews) ) {
									$iAdditionalSectionId = $oLayout->readSectionId( $_POST['layoutKey'], $sSectionKey );
									if( $iAdditionalSectionId !== false ) {
										$aViewPositions = array();
										$iCounter = 1;
										$bCurrViewPositioned = false;
										foreach( $aViews as $iAdditionalViewId => $sView ) {
											if( $iAdditionalViewId != 0 ) {
												$oLayout->createViewToSection( $iAdditionalViewId, $iAdditionalSectionId );
												$aViewPositions[$iAdditionalViewId] = $iCounter;
											} else {
												$aViewPositions[$iViewId] = $iCounter;
												$bCurrViewPositioned = true;
											}
											++$iCounter;
										}
										if( $bCurrViewPositioned === false ) {
											$aViewPositions[$iViewId] = $iCounter;
										}
										$oLayout->updateViewPosition( $iAdditionalSectionId, $aViewPositions );
									}
								}
							}
						}
	
						// ACL
						$oAcl = clRegistry::get( 'clAcl' );
						$oAcl->aroId = array();
						$aAroIds = array(
							'guest',
							'user',
							'admin'
						);
						$oAcl->createByAco( $_POST['layoutKey'], 'layout', $aAroIds, 'userGroup' );
						if( !empty($iViewId) ) $oAcl->createByAco( $iViewId, 'view', $aAroIds, 'userGroup' );
	
						$_POST['routeLayoutKey'] = $_POST['layoutKey'];
						$_POST['routePathLangId'] = $GLOBALS['langId'];
						$iRouteId = $oRouter->create( $_POST );
						$aErr += clErrorHandler::getValidationError( 'createRoute' );
	
						if( empty($aErr) ) {
							// Navigation creation
							if( !empty($_POST['navigationFormAdd']) && $_POST['navigationFormAdd'] == true ) {
								$_POST['navigationGroupKey'] = 'guest';
								$_POST['navigationUrl'] = $_POST['routePath'];
								if( $iNavigationId = $oNavigation->create($_POST) ) {
	
								} else {
									$aErr += clErrorHandler::getValidationError( 'createNavigation' );
								}
							}
						}
					}
	
					if( empty($aErr) ) {
						// Create revision data
						if( !empty($iInfoContentId) ) {
							/**
							 * Do not save revision as 'superUser'
							 */
							if( !empty($_SESSION['user']['acl']['superuser']) && $_SESSION['user']['acl']['superuser'] == 'allow' ) {
								$aUserData = current( $oUser->oDao->read( array(
									'username' => 'admin',
									'fields' => array( 'userId', 'username' )
								) ) );
								if( !empty($aUserData) ) {
									$sRevisionUserId = $aUserData['userId'];
									$sRevisionUsername = $aUserData['username'];
								} else {
									// Could not find a admin user
									$sRevisionUserId = $_SESSION['userId'];
									$sRevisionUsername = $oUser->readData( 'username' );
								}
							} else {
								$sRevisionUserId = $_SESSION['userId'];
								$sRevisionUsername = $oUser->readData( 'username' );
							}
							
							$oInfoContent->createRevision( array(
								'contentId' => $iInfoContentId,
								'userId' => $sRevisionUserId,
								'username' => $sRevisionUsername,
								'revisionContent' => $_POST['contentTextId']
							) );
						}
	
						if( isset($_POST['submitAndGoToList']) ) {
							$oRouter->redirect( $oRouter->getPath( 'adminInfoContentPages' ) );
						} else {
							$oRouter->redirect( $oRouter->sPath . '?layoutKey=' . $_POST['layoutKey'] );
						}
					} else {
						// Rollback
						if( !empty($iInfoContentId) ) $oInfoContent->delete( $iInfoContentId );
						$oLayout->delete( $_POST['layoutKey'] );
						if( !empty($iViewId) && !empty($iSectionId) ) $oLayout->deleteViewToSection( $iViewId, $iSectionId );
						if( !empty($iRouteId) ) $oRouter->delete( $iRouteId );
						if( !empty($iNavigationId) ) $oNavigation->delete( $iNavigationId, 'guest' );
					}
				}
			}
		}
	}
}

$aFormDataDict = array(
	'layoutTitleTextId' => array(
		'labelAttributes' => array(
			'title' => _( 'Page title that will be shown in the browsers toolbar, in bookmark and in search engine results' )
		),
		'attributes' => array(
			'class' => 'text charCounter'
		),
		'required' => true
	),
	'layoutKeywordsTextId' => array(
		'labelAttributes' => array(
			'title' => _( 'Meta keywords are used to provide additional text for some crawler-based search engines to index along with your body copy. A example (without quotes): &quot;Computers, hardware, bicycles&quot;.' )
		),
		'attributes' => array(
			'class' => 'text charCounter'
		)
	),
	'layoutDescriptionTextId' => array(
		'type' => 'string',
		'appearance' => 'full',
		'labelAttributes' => array(
			'title' => _( 'The meta description tag allows you to influence the description of your page in search engine crawlers that support the tag.' )
		),
		'attributes' => array(
			'class' => 'text charCounter'
		)
	),
	'layoutCanonicalUrlTextId' => array(
		'labelAttributes' => array(
			'title' => _( 'Meta Canonical URL determs parent URL if same content exists on multiple URLs.' )
		)
	),
	'layoutSuffixContent' => array(
		'appearance' => 'full',
		'title' => _( 'Additional code, e.g. for tracking or analytic' ),
		'suffixContent' => '<em>(' . _( 'If JavaScript, so should the script-tag also be added' ) . ')</em>'
	),
	'routePath' => array(
		'title' => _( 'Path' ),
		'labelSuffixContent' => SITE_DOMAIN . (empty($_GET['layoutKey']) ? '/' : null),
		'labelAttributes' => array(
			'title' => _( 'The address of the page. Omit your domain name and only use what comes after it. If left empty a address will be generated from your page title. Example (without quotes): &quot;/startpage&quot;' )
		),
		'attributes' => array(
			'readonly' => 'readonly',
			'class' => 'text autoFill'
		),
		'fieldAttributes' => array(
			'class' => 'fieldRoutePath'
		),
		'suffixContent' => '<a href="#" id="changeRoutePath">' . _( 'Enter the path manually' ) . '</a>'
	),
	'contentStatus' => array(),
	// Necessary data for autosave function
	'frmAutosaveData' => array(
		'type' => 'hidden',
		'value' => md5(time() . $_SESSION['userId']) . '|' . 'infoContent'
	),
	'frmInfoContentAdd' => array(
		'type' => 'hidden',
		'value' => true
	)
);

if( SITE_ADDITIONAL_SCRIPT == false ) {
	unset( $aFormDataDict['layoutSuffixContent'] );
}

if( isset($_POST['preview']) ) {
	// Edit after preview
	$aInfoContentPageData = $_POST;

	if( !empty($_GET['layoutKey']) ) {
		$aViews = arrayToSingle( $oLayout->readSectionsAndViews($_GET['layoutKey']), 'viewId', null );

		$aInfoContent = $oInfoContent->readByView( array_keys($aViews), array(
			'contentViewId',
			'contentId',
			'contentTextId',
			'contentStatus',
			'contentKey'
		) );
		foreach( $aInfoContent as $entry ) {
			$iViewId = array_shift( $entry );
			$aViews[$iViewId] = $entry;
			if( !isset( $aInfoContentPageData['contentStatus'] ) ) $aInfoContentPageData['contentStatus'] = $entry['contentStatus'];
		}

		foreach( $aViews as $entry ) {
			if( empty($entry) ) continue;
			$aFormDataDict['contentKey[' . $entry['contentId'] . ']'] = array(
				'title' => _( 'Key' ),
				'type' => 'hidden' // This can be changed if need be
			);
			$aFormDataDict['contentTextId[' . $entry['contentId'] . ']'] = array(
				'appearance' => 'full',
				'attributes' => array(
					'class' => 'editor autosave' # autosave class enables autosave function
				),
				'type' => 'string',
				'title' => _( 'Content' )
			);

			$aInfoContentPageData['contentKey[' . $entry['contentId'] . ']'] = $entry['contentKey'];
			$aInfoContentPageData['contentTextId[' . $entry['contentId'] . ']'] = $_POST['contentTextId'][ $entry['contentId'] ];
			$aContentKeys[] = 'contentTextId[' . $entry['contentId'] . ']';
		}


	} else {

		// Add to navigation
		$aData = $oNavigation->read( array(
			'navigationId',
			'navigationTitle',
			'navigationLeft',
			'navigationRight'
		) );
		$aNavigationItems = array(
			'' => '[' . _( 'Root' ) . ']'
		);

		foreach( $aData as $entry ) {
			$aNavigationItems[$entry['navigationId']] = str_repeat('&emsp;', $entry['depth'] + 1) . $entry['navigationTitle'];
		}

		$aFormDataDict += array(
			'navigationFormAdd' => array(
				'fieldAttributes' => array(
					'class' => 'boolean'
				),
				'type' => 'boolean',
				'values' => array(
					1 => ''
				),
				'title' => _( 'Add in navigation' )
			),
			'navigationTitle' => array(
				'labelAttributes' => array(
					'title' => _( 'Title your page will have in the menu' )
				)
			),
			'navigationRelation' => array(
				'title' => 'Placering',
				'type' => 'array',
				'values' => array(
					'firstChild' => _( 'First beneath target' ),
					'lastChild' => _( 'Last beneath target' ),
					'prevSibling' => _( 'Before target' ),
					'nextSibling' => _( 'After target' )
				)
			),
			'navigationTarget' => array(
				'title' => _( 'Target' ),
				'type' => 'array',
				'values' => $aNavigationItems
			)
		);

		// Explanation text to navigation
		$oTemplate->addBottom( array(
			'key' => 'jsNavigationExplanation',
			'content' => '
			<script>
				$(".fieldGroup.navigation input:not(.checkbox), .fieldGroup.navigation select").on( "change keyup", function() {
					if( $(".fieldGroup.navigation p#navigationExplanation").length == 0 ) {
						$(".fieldGroup.navigation").append("<p id=\"navigationExplanation\">&nbsp;</p>");
					}

					var sLinkName = $("#navigationTitle").val();
					var sRelation = $("#navigationRelation option:selected").text();
					var sTarget = $("#navigationTarget option:selected").text();

					sTarget = sTarget.replace(/^\s+|\s+$/g, "") ;

					var emsp = new RegExp(String.fromCharCode(8195), "g");
					sTarget.replace(emsp, "testa");
					console.log(sTarget);

					$(".fieldGroup.navigation p#navigationExplanation").html( "' . _( 'A link to this page with the name' ) . ' <strong>\"" + sLinkName + "\"</strong> ' . _( 'is going to be added to the navigation' ) . ' <strong>\"" + sRelation + "\"</strong>, <strong>\"" + sTarget + "\"</strong>"  );
				} );
			</script>'
		) );

		$aFormDataDict['contentKey'] = array(
			'type' => 'hidden' // This can be changed if need be
		);
		$aFormDataDict['contentTextId'] = array(
			'appearance' => 'full',
			'attributes' => array(
				'class' => 'editor autosave' # autosave class enables autosave function
			),
			'type' => 'string'
		);

	}

	$sTitle = ( !empty($_GET['layoutKey']) ? _( 'Edit page' ) : _( 'Add page' ) );

} elseif( !empty($_GET['layoutKey']) ) {
	// Edit
	$aInfoContentPageData = current( $oLayout->read('*', $_GET['layoutKey']) );
	$aInfoContentPageData += (array) current( $oRouter->readByLayout($_GET['layoutKey'], 'routePath') );
	$aViews = arrayToSingle( $oLayout->readSectionsAndViews($_GET['layoutKey']), 'viewId', null );

	$aInfoContent = $oInfoContent->readByView( array_keys($aViews), array(
		'contentViewId',
		'contentId',
		'contentTextId',
		'contentStatus',
		'contentKey'
	) );
	foreach( $aInfoContent as $entry ) {
		$iViewId = array_shift( $entry );
		$aViews[$iViewId] = $entry;
		if( !isset( $aInfoContentPageData['contentStatus'] ) ) $aInfoContentPageData['contentStatus'] = $entry['contentStatus'];
	}

	foreach( $aViews as $entry ) {
		if( empty($entry) ) continue;
		$aFormDataDict['contentKey[' . $entry['contentId'] . ']'] = array(
			'title' => _( 'Key' ),
			'type' => 'hidden' // This can be changed if need be
		);
		$aFormDataDict['contentTextId[' . $entry['contentId'] . ']'] = array(
			'appearance' => 'full',
			'attributes' => array(
				'class' => 'editor autosave' # autosave class enables autosave function
			),
			'type' => 'string',
			'title' => _( 'Content' )
		);
		$aInfoContentPageData['contentKey[' . $entry['contentId'] . ']'] = $entry['contentKey'];
		$aInfoContentPageData['contentTextId[' . $entry['contentId'] . ']'] = $entry['contentTextId'];
		$aContentKeys[] = 'contentTextId[' . $entry['contentId'] . ']';
	}

	$sTitle = _( 'Edit page' );
} else {
	// InfoContent
	$aFormDataDict['contentKey'] = array(
		'type' => 'hidden' // This can be changed if need be
	);
	$aFormDataDict['contentTextId'] = array(
		'appearance' => 'full',
		'attributes' => array(
			'class' => 'editor autosave' # autosave class enables autosave function
		),
		'type' => 'string'
	);
	$aInfoContentPageData = $_POST;
	$sTitle = _( 'Add page' );
}

// Add to navigation
if( !empty($aInfoContentPageData['routePath']) ) {
	$aNavigationMatches = $oNavigation->readByUrl($aInfoContentPageData['routePath'], array(
		'navigationId'
	) );
}
if( empty($_GET['layoutKey']) || empty($aNavigationMatches) ) {
	$aData = $oNavigation->read( array(
		'navigationId',
		'navigationTitle',
		'navigationLeft',
		'navigationRight'
	) );
	$aNavigationItems = array(
		'' => '[' . _( 'Root' ) . ']'
	);

	foreach( $aData as $entry ) {
		$aNavigationItems[$entry['navigationId']] = str_repeat('&emsp;', $entry['depth'] + 1) . $entry['navigationTitle'];
	}

	$aFormDataDict += array(
		'navigationFormAdd' => array(
			'fieldAttributes' => array(
				'class' => 'boolean'
			),
			'type' => 'boolean',
			'values' => array(
				1 => ''
			),
			'title' => _( 'Add in navigation' )
		),
		'navigationTitle' => array(
			'labelAttributes' => array(
				'title' => _( 'Title your page will have in the site navigation' )
			)
		),
		'navigationRelation' => array(
			'title' => _( 'Relation' ),
			'labelAttributes' => array(
				'title' => _( 'This combined with the &quot;target&quot; input will define the position of the link' )
			),
			'type' => 'array',
			'values' => array(
				'firstChild' => _( 'First beneath target' ),
				'lastChild' => _( 'Last beneath target' ),
				'prevSibling' => _( 'Before target' ),
				'nextSibling' => _( 'After target' )
			)
		),
		'navigationTarget' => array(
			'title' => _( 'Target' ),
			'labelAttributes' => array(
				'title' => _( 'This combined with the &quot;relation&quot; input will define the position of the link' )
			),
			'type' => 'array',
			'values' => $aNavigationItems
		)
	);

	// Explanation text to navigation
	$oTemplate->addBottom( array(
		'key' => 'jsNavigationExplanation',
		'content' => '
		<script>
			$(".fieldGroup.navigation input:not(.checkbox), .fieldGroup.navigation select").on( "change keyup", function() {
				if( $(".fieldGroup.navigation p#navigationExplanation").length == 0 ) {
					$(".fieldGroup.navigation").append("<p id=\"navigationExplanation\">&nbsp;</p>");
				}

				var sLinkName = $("#navigationTitle").val();
				var sRelation = $("#navigationRelation option:selected").text();
				var sTarget = $("#navigationTarget option:selected").text();

				sTarget = sTarget.replace(/^\s+|\s+$/g, "") ;

				var emsp = new RegExp(String.fromCharCode(8195), "g");
				sTarget.replace(emsp, "testa");
				console.log(sTarget);

				$(".fieldGroup.navigation p#navigationExplanation").html( "' . _( 'A link to this page with the name' ) . ' <strong>\"" + sLinkName + "\"</strong> ' . _( 'is going to be added to the navigation' ) . ' <strong>\"" + sRelation + "\"</strong>, <strong>\"" + sTarget + "\"</strong>"  );
			} );
		</script>'
	) );
} elseif( !empty($aNavigationMatches) ) {

	// Ajax removal of navigation nodes
	$oTemplate->addBottom( array(
		'key' => 'ajaxRemoveNavigationNode',
		'content' => '
			<script>
				$(".fieldGroup.navigation a.iconDelete").on("click", function() {
					if(confirm(this.title) == false) return false;

					var container = $(this).parents(".fieldGroup");
					var url = this.href;
					$.ajax( {
						url: url + (this.href.match(/[?]/) ? "&" : "?") + "ajax=true&view=/infoContent/layoutNavigationList.php",
						beforeSend: function() {
							container.addClass("disabled loading");
						},
						success: function( data, textStatus, jqXHR ) {
							container.replaceWith( data );
						},
						error: function( jqXHR, textStatus, errorThrown ) {
							alert("' . $GLOBALS['errorMsg']['generic']['errorTryAgain'] . ': \n" + textStatus + errorThrown);
							container.removeClass("disabled loading");
						}
					} );

					return false;
				});
			</script>'
	) );

	$sNavigationDeletion = $oLayout->renderView( '/infoContent/layoutNavigationList.php' );
}

// Use content
if( !empty($_GET['useContent']) && empty($_POST['contentTextId']) ) {
	$aData = current( $oInfoContent->read( 'contentTextId', $_GET['useContent'] ) );
	$aInfoContentPageData['contentTextId'] = $aData['contentTextId'];

	$oNotification->set( array(
		'dataInformation' => _( 'Content has been filled as template!' )
	) );
}

// Use temp as content
if( !empty($_GET['tempId']) && empty($_POST['contentTextId']) ) {
	$oTinyMceAutoSave = clRegistry::get( 'clTinyMceAutoSave', PATH_MODULE . '/tinyMceAutoSave/models' );
	$aTempData = current( $oTinyMceAutoSave->read( 'tempContent', $_GET['tempId'] ) );
	$aInfoContentPageData['contentTextId'] = $aTempData['tempContent'];

	$oNotification->set( array(
		'dataInformation' => _( 'Content has been restored!' )
	) );
}

// Add navigation URL if comming from navigation
if( empty($_GET['layoutKey']) && !empty($_GET['useNav']) ) {
	$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
	$aNavigationData = $oNavigation->read( 'navigationUrl', $_GET['useNav'] );
	if( !empty($aNavigationData) ) {
		$aInfoContentPageData['routePath'] = substr( $aNavigationData[0]['navigationUrl'], 1, strlen($aNavigationData[0]['navigationUrl']) );
	}
}

// Add form
$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init(
	$oInfoContent->oDao->getDataDict() +
	$oRouter->oDao->getDataDict() +
	$oLayout->oDao->getDataDict() +
	$oNavigation->oDao->getDataDict(),
	array(
		'action' => '',
		'data' => $aInfoContentPageData,
		'errors' => $aErr,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submitPost' => array(
				'content' => _( 'Save' ),
				'attributes' => array(
					'name' => 'submitPost',
					'type' => 'submit'
				)
			),
			'submitAndGoToList' => array(
				'content' => _( 'Save and go to list' ),
				'attributes' => array(
					'name' => 'submitAndGoToList',
					'type' => 'submit'
				)
			),
			'preview' => array(
				'content' => _( 'Preview' ),
				'attributes' => array(
					'name' => 'preview',
					'type' => 'submit'
				)
			)
		)
	)
);

$oOutputHtmlForm->setFormDataDict( $aFormDataDict );
$oOutputHtmlForm->setGroups( array(
	'layout' => array(
		'attributes' => array(
			'class'	=> 'form marginal'
		),
		'title' => _( 'Settings' ),
		'fields' => array(
			'layoutTitleTextId',
			'routePath'
		)
	),
	'status' => array(
		'attributes' => array(
			'class'	=> 'form marginal'
		),
		'title' => _( 'Status' ),
		'fields' => array(
			'contentStatus'
		)
	),
	'navigation' => array(
		'title' => 'Navigation',
		'fields' => array(
			'navigationFormAdd',
			'navigationTitle',
			'navigationRelation',
			'navigationTarget'
		)
	),
	'editor' => array(
		'title' => '',
		'fields' => (empty($_GET['layoutKey']) ? array('contentTextId') : $aContentKeys)
	),
	'metadata' => array(
		'attributes' => array(
			'class'	=> 'form marginal'
		),
		'title' => 'Metadata',
		'fields' => array(
			'layoutKeywordsTextId',
			'layoutDescriptionTextId',
			'layoutSuffixContent',
			'layoutCanonicalUrlTextId'
		)
	)
) );

$sOutput .= $oOutputHtmlForm->renderForm('
		<section>
			' . $oOutputHtmlForm->renderErrors() . '
			' . $oOutputHtmlForm->renderGroups( array(
				'layout',
				'editor'
			) ) . '
			' . $oOutputHtmlForm->renderButtons() . '
		</section>
		<aside>
			' . $oOutputHtmlForm->renderGroups( array('status' ) ) . '
			' . ( empty($aNavigationMatches) ? $oOutputHtmlForm->renderGroups( array('navigation') ) : $sNavigationDeletion ) . '

			' . $oOutputHtmlForm->renderGroups( array('metadata') ) . '
		</aside>
		' . $oOutputHtmlForm->renderFields() . '
');
	/* . ( !empty($_GET['layoutKey']) ? $oLayout->renderView( '/infoContent/routeHttpStatus.php' ) : '' );*/

// Revisions of page
if( !empty($_GET['layoutKey']) ) {
	clFactory::loadClassFile( 'clOutputHtmlSorting' );
	$oSorting = new clOutputHtmlSorting( $oInfoContent->oDao, array(
		'currentSort' => ( isset($_GET['sort']) ? array($_GET['sort'] => (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'ASC' )) : array('revisionId' => 'DESC') )
	) );
	$oSorting->setSortingDataDict( array(
		'revisionId' => array(),
		'username' => array(),
		'revisionCreated' => array()
	) );

	clFactory::loadClassFile( 'clOutputHtmlPagination' );
	$oPagination = new clOutputHtmlPagination( $oInfoContent->oDao, array(
		'currentPage' => ( isset($_GET['page']) ? $_GET['page'] : null ),
		'entries' => 10
	) );

	//	Pagination needs diffrent primary values
	$oInfoContent->oDao->sPrimaryField = 'revisionId';
	$oInfoContent->oDao->sPrimaryEntity = 'entInfoContentRevision';

	//	Sort out views that are not infoContent
	foreach( $aViews as $iViewKey => $aData ) {
		if( !empty($aData['contentId']) ) $aViewsContentId[] = $aData;
	}
	$aViewsContentId = arrayToSingle( $aViewsContentId, null, 'contentId');
	$aRevisions = $oInfoContent->readRevisionByContentId( array(
		'revisionId',
		'contentId',
		'username',
		'revisionCreated'
	), $aViewsContentId );

	$sOutput .= '
	<section class="infoContentRevisions">
		<h3>' . _( 'Revisions' ) . '</h3>';

	if( !empty($aRevisions) ) {

		if( $oUser->oAclGroups->isAllowed('superuser') ) {
			$sOutput .= '
				<a href="' . $oRouter->sPath . '?' . stripGetStr( array('deleteInfoContentRevision', 'infoContentRevisonImport', 'truncateInfoContentRevision') ) . '&amp;truncateInfoContentRevision=true" class="icon iconText iconDelete">' . _('Delete all revisions') . '</a>';
		}

		clFactory::loadClassFile( 'clOutputHtmlTable' );
		$oOutputHtmlTable = new clOutputHtmlTable( $oInfoContent->oDao->getDataDict('entInfoContentRevision') );
		$oOutputHtmlTable->setTableDataDict( $oSorting->render() + array(
			'revisionControls' => array(
				'title' => ''
			)
		) );

		foreach( $aRevisions as $aRevision ) {
			$row['revisionId'] = $aRevision['revisionId'];
			$row['username'] = $aRevision['username'];
			$row['revisionCreated'] = $aRevision['revisionCreated'];
			$row['revisionControls'] = '
			<a href="' . $sUrlRevisionShow . '?revisionId=' . $aRevision['revisionId'] . '" class="icon iconEdit iconText">' . _( 'Show' ) . '</a>
			<a href="' . $oRouter->sPath . '?' . stripGetStr( array('deleteInfoContentRevision', 'infoContentRevisonImport', 'truncateInfoContentRevision') ) . '&amp;event=infoContentRevisonImport&amp;infoContentRevisonImport=' . $aRevision['revisionId'] . '" class="icon iconHistory iconText linkConfirm" title="' . _( 'Do you really want to restore this revision?' ) . '">' . _( 'Restore' ) . '</a>
			<a href="' . $oRouter->sPath . '?' . stripGetStr( array('deleteInfoContentRevision', 'infoContentRevisonImport', 'truncateInfoContentRevision') ) . '&amp;event=deleteInfoContentRevision&amp;deleteInfoContentRevision=' . $aRevision['revisionId'] . '" class="icon iconDelete iconText linkConfirm" title="' . _( 'Do you really want to delete this revision?' ) . '">' . _( 'Delete' ) . '</a>';

			$oOutputHtmlTable->addBodyEntry( $row );
		}

		$sOutput .= $oOutputHtmlTable->render() . '
		' . $oPagination->render();
	} else {
		$sOutput .= '
		<strong>' . _( 'No revisions found' ) . '</strong>';
	}

	$sOutput .= '
	</section>';
}

$oInfoContent->oDao->setLang( $GLOBALS['langId'] );
$oLayout->oDao->setLang( $GLOBALS['langId'] );
$oRouter->oDao->setLang( $GLOBALS['langId'] );
$oNavigation->oDao->setLang( $GLOBALS['langId'] );

echo '
	<div class="view infoContentPageFormAdd">
		<h1>' . $sTitle . '</h1>
		' . (!empty($_GET['layoutKey']) ? '
		<section class="tools">
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminInfoContentPageAdd' ). '" class="icon iconText iconAdd">' . _( 'Create new page' ) . '</a>
			</div>
			<div class="tool">
				<a href="' . $oRouter->getPath( 'adminInfoContentPageAdd' ). '?useContent=' . (current($aViewsContentId)) . '" class="icon iconText iconAdd">' . _( 'Use as template' ) . '</a>
			</div>
		</section>
		' : '') . '
		' . $sOutput . '
	</div>';

if( !empty($_GET['layoutKey']) && file_exists(PATH_PUBLIC . '/css/layouts/' . $_GET['layoutKey'] . '.css') ) {
	$sCssEdit = '<a href="' . $oRouter->getPath( 'superLayoutCss' ) . '?layoutKey=' . $_GET['layoutKey'] . '" class="icon iconText iconCss"><span>' . _( 'Custom CSS for this page' ) . '</span></a>';

} elseif( !empty($_GET['layoutKey']) && !file_exists(PATH_PUBLIC . '/css/layouts/' . $_GET['layoutKey'] . '.css') ) {
	$sCssEdit = '<a href="' . $oRouter->getPath( 'superLayoutCss' ) . '?layoutKey=' . $_GET['layoutKey'] . '" class="icon iconText iconAdd"><span>' . _( 'Custom CSS for this page' ) . '</span></a>';

} else {
	$sCssEdit = '';
}

/**
 * Add tools fieldGroup with JS,
 * cuz this only works with JS.
 */
$oTemplate->addBottom( array(
	'key' => 'moxieUploadTools',
	'content' => '
		<script>
			$(document).ready( function() {
				$(".fieldGroup.status").after( \'\
					<fieldset class="fieldGroup tools">\
						<legend><span>' . _( 'Tools' ) . '</span></legend>\
						<ul>\
							<li><a href="#" class="moxieImageUpload">' . _( 'Upload images' ) . '</a></li>\
							<li><a href="#" class="moxieFileUpload">' . _( 'Upload files' ) . '</a></li>\
							<li>' . $sCssEdit . '</li>\
						</ul>\
					</fieldset>\' );
			} );
		</script>
	'
) );

/**
 * Routes for Canonical URL
 */
$oRouter->oDao->setCriterias( array(
	'routePathLangId' => array(
		'type' => '=',
		'fields' => 'routePathLangId',
		'value' => $GLOBALS['langIdEdit']
	)
) );
$aData = $oRouter->read( 'routePath' );
$aRoutes = array();
foreach( $aData as $entry ) {
	$aRoutes[] = $entry['routePath'];
}
$oRouter->oDao->sCriterias = '';
$oTemplate->addBottom( array(
	'key' => 'moxieUpload',
	'content' => '
		<script>
			$("#layoutCanonicalUrlTextId").autocomplete( {
				source: ["' . implode( '", "', $aRoutes ) . '"],
				minLength: 0
			} );
			$(document).delegate( "#layoutCanonicalUrlTextId", "focusout", function() {
				if( $.inArray( $(this).val(), ["' . implode( '", "', $aRoutes ) . '"] ) < 0 ) {
					if( $(this).next("ul").length == 0 ) {
						$(this).attr( "style", "background: #FFEBEB;" );
						$(this).after( "<ul class=\"notification\"><li class=\"dataError\">' . _( 'This path does not exist' ) . '</li></ul>" );
					} else {
						$(this).next("ul").effect( "shake" );
					}
				} else {					
					if( $(this).next("ul") ) {
						$(this).next("ul").remove();
						$(this).removeAttr( "style" );
					}					
				}
			} );
		</script>
	'
) );