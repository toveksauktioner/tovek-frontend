<?php

require_once PATH_FUNCTION . '/fOutputHtml.php';
require_once PATH_FUNCTION . '/fRoute.php';

class clTemplateHtml {

	private $aTopElements = array();
	private $aBottomElements = array();
	private $sContent;
	private $sTemplate;

	public function __construct( $sTemplate = 'default.php' ) {
		$this->sTemplate = $sTemplate;
	}

	public function addBottom( $aParams = array() ) {
		if( isAjaxRequest() ) return true;

		$aParams += array(
			'key' => null,
			'content' => null
		);

		$this->aBottomElements[$aParams['key']] = $aParams['content'];
	}

	public function addLink( $aParams = array() ) {
		if( isAjaxRequest() ) return true;

		$aParams += array(
			'key' => null,
			'href' => null,
			'rel' => 'stylesheet',
			'media' => null
		);
		$sKey = $aParams['key'];
		$aParams['id'] = $aParams['key'];
		unset( $aParams['key'] );

		$this->addTop( array(
			'key' => $sKey,
			'content' => ( !empty($_GET['ajax']) ? '
				<script>
					if( $("#' . $aParams['id'] . '").length > 0 ) {
						$("#' . $aParams['id'] . '").attr({href : "' . $aParams['href'] . '"});
					} else {
						$("head").append(' . "'<link" . createAttributes( $aParams ) . ">'" . ');
					}
				</script>' : '<link' . createAttributes( $aParams ) . ' />' )
		) );
	}

	public function addMeta( $aParams = array() ) {
		if( isAjaxRequest() ) return true;
		
		$aParams += array(
			'key' => null,
			'name' => null,
			'content' => null
		);
		$sKey = $aParams['key'];
		unset( $aParams['key'] );
		$aParams['content'] = htmlspecialchars( str_replace(array("\n", "\r", "\r\n"), '', $aParams['content']) );
		if( !empty($_GET['ajax']) ) $aParams['content'] = htmlentities($aParams['content'], ENT_QUOTES);
		
		$this->addTop( array(
			'key' => $sKey,
			'content' => ( !empty($_GET['ajax']) ? '
				<script>
					if( $("meta[name=' . "'" . $aParams['name'] . "'" . ']").length > 0 ) {
						$("meta[name=' . "'" . $aParams['name'] . "'" . ']").attr({content : "' . $aParams['content'] . '"});
					} else {
						$("head").append(' . "'<meta" . createAttributes( $aParams ) . ">'" . ');
					}
				</script>' : '<meta' . createAttributes( $aParams ) . ' />' )
		) );
	}
	
	public function addOgTag( $sProperty, $sContent, $aExtraMeta = array() ) {
		$this->addMeta( array(
			'key' => sprintf( 'og:%s:%s', $sProperty, rand(100000, 999999) ),
			'property' => 'og:' . $sProperty,
			'content' => $sContent
		) );
		
		if( !empty($aExtraMeta) ) {
			foreach( $aExtraMeta as $sExtraProperty => $sExtraContent ) {
				$this->addMeta( array(
					'key' => sprintf( 'og:%s:%s', $sProperty, $sExtraProperty ),
					'property' => sprintf( 'og:%s:%s', $sProperty, $sExtraProperty ),
					'content' => $sExtraContent
				) );
			}
		}
	}
	
	public function addScript( $aParams = array() ) {
		if( isAjaxRequest() ) return true;

		$aParams += array(
			'key' => null,
			'src' => null
		);
		$sKey = $aParams['key'];
		unset( $aParams['key'] );

		$this->addBottom( array(
			'key' => $sKey,
			'content' => '<script' . createAttributes( $aParams ) . '></script>'
		) );
	}

	public function addStyle( $aParams = array() ) {
		if( isAjaxRequest() ) return true;

		$aParams += array(
			'key' => null,
			'content' => null
		);

		$aParams['content'] = '<style>' . $aParams['content'] . '</style>';

		$this->addTop( $aParams );
	}

	public function addTop( $aParams = array() ) {
		if( isAjaxRequest() ) return true;

		$aParams += array(
			'key' => null,
			'content' => null
		);

		$this->aTopElements[$aParams['key']] = $aParams['content'];
	}

	public function render() {
		$sTop = implode( "\n", $this->aTopElements );
		$sBottom = implode( "\n", $this->aBottomElements );
		if( !empty($_GET['ajax']) ) return $sTop . "\n" . $sBottom;
		$sContent = $this->sContent;

		// Maybe modify rendered content afterwards
		$sContent = $this->modifyContent( $sContent );

		$oRouter = clRegistry::get( 'clRouter' );
		$oTemplate = clRegistry::get( 'clTemplateHtml' );
		$oLayout = clRegistry::get( 'clLayoutHtml' );
		$oNotification = clRegistry::get( 'clNotificationHandler' );

		ob_start();
		require PATH_TEMPLATE . '/' . $this->sTemplate;
		return ob_get_clean();
	}

	/**
	 * Modify rendered content afterwards
	 */
	public function modifyContent( $sContent ) {
		if( SITE_SEO_ADJUSTED === true ) {
			/**
			 * Search and add alt-texts for images
			 */

			// Do we have any loaded images?
			$aImageIds = &clRegistry::$aEntries['clImage']->aLoaded;

			if( !empty($aImageIds) ) {
				// Do we have any entered text for images to current page?
				$oImageAltRoute	= clRegistry::get( 'clImageAltRoute', PATH_MODULE . '/image/models/' );
				$oRouter = clRegistry::get( 'clRouter' );
				$aAltByRoute = valueToKey( 'entryImageId', $oImageAltRoute->readByImageRoute( $aImageIds, $oRouter->iCurrentRouteId ) );

				preg_match_all( '/<img[^>]+>/i', $sContent, $aContentImage );

				if( !empty($aAltByRoute) && !empty($aContentImage) ) {
					$oImage	= clFactory::create( 'clImage', PATH_MODULE . '/image/models/' );
					$aImageData = $oImage->read( '*', arrayToSingle($aAltByRoute, null, 'entryImageId') );

					if( !empty($aImageData) ) {
						foreach( current($aContentImage) as $sImage ) {
							foreach( $aImageData as $aImage ) {
								if( strpos($sImage, $aImage['imageId'] . '.' . $aImage['imageFileExtension']) !== false ) {
									if( strpos($sImage, 'alt=""') !== false ) {
										$sContent = str_replace( $sImage, str_replace( 'alt=""', 'alt="' . $aAltByRoute[ $aImage['imageId'] ]['entryImageAlternativeTextTextId'] . '"', $sImage ), $sContent );
									}
								}
							}
						}
					}
				}
			}
		}
		return $sContent;
	}

	public function setContent( $sContent ) {
		$this->sContent = $sContent;
	}

	public function setKeywords( $sKeywords ) {
		$this->addMeta( array(
			'key' => 'metaKeywords',
			'name' => 'keywords',
			'content' => $sKeywords
		) );
		$this->addOgTag( 'keywords', $sKeywords );
	}

	public function setDescription( $sDescription ) {
		$this->addMeta( array(
			'key' => 'metaDescription',
			'name' => 'description',
			'content' => htmlspecialchars( $sDescription )
		) );
		$this->addOgTag( 'description', $sDescription );
	}

	public function setCanonicalUrl( $sCanonical ) {
		$this->addTop( array(
			'key' => 'metaCanonicalUrl',
			'name' => 'canonical',
			'content' => '<link rel="canonical" href="' . htmlspecialchars( $sCanonical, ENT_QUOTES ) . '" />'
		) );
	}

	public function setTemplate( $sTemplate ) {
		if( file_exists(PATH_TEMPLATE . '/' . $sTemplate) ) $this->sTemplate = $sTemplate;
	}

	public function setTitle( $sTitle = null ) {
		if( $sTitle === null ) $sTitle = SITE_TITLE;
		$this->addTop( array(
			'key' => 'title',
			'content' => ( !empty($_GET['ajax']) ? '
				<script>
					document.title = ' . "'" . htmlentities( $sTitle, ENT_QUOTES ) . "'" . ';
				</script>' : '<title>' . $sTitle . '</title>' )
		) );		
		$this->addOgTag( 'title', $sTitle );
	}

}
