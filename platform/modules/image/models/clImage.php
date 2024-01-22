<?php

require_once PATH_CORE . '/clModuleBase.php';
require_once PATH_MODULE . '/image/config/cfImage.php';

class clImage extends clModuleBase {

	public $aLoaded = array();

	private $aParams = array();

	public function __construct( $aParams = array() ) {
		if( !empty($aParams) ) $this->setParams($aParams);

		$this->sModulePrefix = 'image';

		$this->oDao = clRegistry::get( 'clImageDao' . DAO_TYPE_DEFAULT_ENGINE, PATH_MODULE . '/image/models' );

		$this->initBase();
	}

	public function setParams( $aParams = array() ) {
		$aParams += array(
			'imageKey' => null,
			'parentType' => null,
			'aspectRatio' => null, # Experimental
			'scaleOnUpload' => true,
			// Main image
			'crop' => IMAGE_CROP,
			'maxWidth' => IMAGE_MAX_WIDTH,
			'maxHeight' => IMAGE_MAX_HEIGHT,
			'watermark' => !empty($GLOBALS['imageWatermark']) ? $GLOBALS['imageWatermark'] : null,
			// Thumbnail
			'tnCrop' => null,
			'tnMaxWidth' => IMAGE_TN_MAX_WIDTH,
			'tnMaxHeight' => IMAGE_TN_MAX_HEIGHT,
			'tnPrefix' => IMAGE_TN_PREFIX,
			'tnWatermark' => IMAGE_TN_WATERMARK, # Boolen value
			// Additional thumbnails
			'additionalThumbnails' => array()
		);
		if( $aParams['parentType'] === null )  return false;
		if( !is_dir(PATH_CUSTOM_IMAGE . '/' . $aParams['parentType']) ) {
            if( !mkdir(PATH_CUSTOM_IMAGE . '/' . $aParams['parentType'], IMAGE_CHMOD_DEFAULT) ) throw new Exception( sprintf(_('Could not create directory %s'), PATH_CUSTOM_IMAGE . '/' . $aParams['parentType']) );
        }
		if( !is_dir(PATH_CUSTOM_IMAGE . '/' . $aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY) ) {
            if( !mkdir(PATH_CUSTOM_IMAGE . '/' . $aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY, IMAGE_CHMOD_DEFAULT) ) throw new Exception( sprintf(_('Could not create directory %s'), PATH_CUSTOM_IMAGE . '/' . $aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY) );
        }

		$this->aParams = $aParams;

		$this->aEvents = array(
			'external' => array(
				'imageCreateThumbnail' => 'createThumbnailByPrimary'
			)
		);
		$this->oEventHandler->addListener( $this, $this->aEvents );

		return true;
	}

	public function copyByParent( $sParentType, $iSourceParentId, $iDestinationParentId ) {
		$sDir = PATH_CUSTOM_IMAGE . '/' . $sParentType . '/';
		$sTnDir = $sDir . IMAGE_TN_DIRECTORY . '/';

		// Read images from parent
		$this->setParams( array(
			'parentType' => $sParentType
		) );
		$aData = $this->readByParent( $iSourceParentId, '*' );

		// Preload scandir so it don't have to be done serveral times
		$aTnDirContents = scandir( $sTnDir );
		$aTnsToImageId = array();
		foreach( $aTnDirContents as $sTnFile ) {
			if( !in_array($sTnFile, array('.', '..')) ) {
				preg_match( '/[[:alpha:]]*([[:digit:]]+)\.[[:alpha:]]{3,4}/', $sTnFile, $aMatches );

				if( !empty($aMatches[1]) ) {
					$aTnsToImageId[ $aMatches[1] ][$sTnFile] = $sTnFile;
				}
			}
		}

		// Create images for copy
		foreach( $aData as $aImageData ) {
			$iSourceImageId = $aImageData['imageId'];

			unset(
				$aImageData['imageId'],
				$aImageData['imageParentId']
			);
			$aImageData['imageParentId'] = $iDestinationParentId;

			if( $iDestinationImageId = $this->create($aImageData) ) {
				// Copy files

				$sSourceFile = $sDir . $iSourceImageId . '.' . $aImageData['imageFileExtension'];
				$sDestinationFile = $sDir . $iDestinationImageId . '.' . $aImageData['imageFileExtension'];

				// Copy main file
				if( file_exists($sSourceFile) ) {
					copy( $sSourceFile, $sDestinationFile );
				}

				// Copy thumbnails
				if( !empty($aTnsToImageId[$iSourceImageId]) ) {
					foreach( $aTnsToImageId[$iSourceImageId] as $sSourceTnFileName ) {
						$sSourceTnFile = $sTnDir . $sSourceTnFileName;
						$sDestinationTnFile = $sTnDir . str_replace( $iSourceImageId, $iDestinationImageId, $sSourceTnFileName );

						if( file_exists($sSourceTnFile) ) {
							copy( $sSourceTnFile, $sDestinationTnFile );
						}
					}
				}

			}
		}
	}

	public function createThumbnailByPrimary( $primaryId ) {
		if( empty($this->aParams) ) return false;
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		$aImages = $this->read( array(
			'imageId',
			'imageFileExtension',
			'imageParentType'
		), $primaryId );

		if( empty($this->aParams['tnCrop']) ) {
			$this->aParams['tnCrop'] = $this->aParams['crop'];
		}

		$oImageHandler = clRegistry::get( 'clImageHandler' );

		foreach( $aImages as $entry ) {
			if( $oImageHandler->setImageFile(PATH_CUSTOM_IMAGE . '/' . $entry['imageParentType'] . '/' . $entry['imageId'] . '.' . $entry['imageFileExtension']) ) {
				$oImageHandler->createThumbNail( $this->aParams['tnMaxWidth'], $this->aParams['tnMaxHeight'], array(
					'crop' => $this->aParams['tnCrop'],
					'directory' => PATH_CUSTOM_IMAGE . '/' . $entry['imageParentType'] . '/' . IMAGE_TN_DIRECTORY . '/',
					'prefix' => $this->aParams['tnPrefix'],
					'watermark' => $this->aParams['tnWatermark'] ? $this->aParams['watermark'] : null,
					'aspectRatio' => $this->aParams['aspectRatio']
				) );

				if( !empty($this->aParams['additionalThumbnails']) ) {
					foreach( $this->aParams['additionalThumbnails'] as $sPrefix => $aResolution ) {
						$oImageHandler->createThumbNail( $aResolution['width'], $aResolution['height'], array(
							'crop' => ( array_key_exists('tnCrop', $aResolution) ? $aResolution['crop'] : false ),
							'directory' => PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY . '/',
							'prefix' => $this->aParams['tnPrefix'] . $sPrefix,
							'watermark' => $this->aParams['tnWatermark'] ? $this->aParams['watermark'] : null,
							'aspectRatio' => $this->aParams['aspectRatio']
						) );
					}
				}
			}
		}
	}

	public function createWithUpload( $aUploadParams, $iParentId ) {
		if( empty($this->aParams) ) return false;

		$aUploadParams += array(
			'additionalThumbnails' => null,
			'allowedMime' => array(),
			'key' => null
		);

		$aNewFileNames = array();
		$oUpload = clRegistry::get( 'clUpload' );
		foreach( $_FILES[$aUploadParams['key']]['error'] as $key => $iErr ) {
			if( $iErr == UPLOAD_ERR_OK ) {
				if( !array_key_exists($_FILES[ $aUploadParams['key'] ]['type'][$key], $aUploadParams['allowedMime']) ) {
					$aErr[ $aUploadParams['key'] ][] = sprintf( $GLOBALS['errorMsg']['validation']['filetype'], $_FILES[ $aUploadParams['key'] ]['name'][$key] );
				}
				if( $_FILES[ $aUploadParams['key'] ]['size'][$key] > $GLOBALS['upload_max_filesize'] ) {
					$aErr[ $aUploadParams['key'] ][] = sprintf( $GLOBALS['errorMsg']['validation']['filesize'], $aFiles['name'][ $iKey ], $GLOBALS['uploadMaxSizeLabel'] );
				}
				if( empty($aErr) ) {
					$sFileExtension = $aUploadParams['allowedMime'][$_FILES[$aUploadParams['key']]['type'][$key]];
					if( $iImageId = $this->create(array(
						'imageFileExtension' => $sFileExtension,
						'imageParentType' => $this->aParams['parentType'],
						'imageKey' => $this->aParams['imageKey'],
						'imageParentId' => $iParentId,
						'imageMD5' => md5_file( $_FILES[$aUploadParams['key']]['tmp_name'][$key] )
					)) ) {
						$aNewFileNames[$key] = $iImageId . '.' . $sFileExtension;
					} else {
						$aErr = clErrorHandler::getValidationError( 'createImage' );
					}
				}
			}
		}

		if( empty($aErr) ) {
			// Upload images
			$oUpload->setParams( array(
				'destination' => PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'] . '/',
				'allowedMime' => $aUploadParams['allowedMime'],
				'key' => $aUploadParams['key'],
				'newFileName' => $aNewFileNames
			) );
			$aErr = $oUpload->upload();
		}

		if( empty($aErr) && $this->aParams['scaleOnUpload'] === true ) {
			if( empty($this->aParams['tnCrop']) ) {
				$this->aParams['tnCrop'] = $this->aParams['crop'];
			}

			// Scale pictures
			$oImageHandler = clRegistry::get( 'clImageHandler' );
			foreach( $_FILES[$aUploadParams['key']]['name'] as $key => $sPicFile ) {
				if( empty($aUploadErr[$key]) && $oImageHandler->setImageFile( PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'] . '/' . $sPicFile ) ) {
					// Main thumbnail
					$oImageHandler->createThumbNail( $this->aParams['tnMaxWidth'], $this->aParams['tnMaxHeight'], array(
						'crop' => $this->aParams['tnCrop'],
						'directory' => PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY . '/',
						'prefix' => $this->aParams['tnPrefix'],
						'watermark' => $this->aParams['tnWatermark'] ? $this->aParams['watermark'] : null,
						'aspectRatio' => $this->aParams['aspectRatio']
					) );

					// Handle extra thumbnails
					if( $aUploadParams['additionalThumbnails'] !== null && is_array($aUploadParams['additionalThumbnails']) ) {
						foreach( $aUploadParams['additionalThumbnails'] as $sPrefix => $aResolution ) {
							$oImageHandler->createThumbNail( $aResolution['width'], $aResolution['height'], array(
								'crop' => ( array_key_exists('tnCrop', $aResolution) ? $aResolution['crop'] : false ),
								'directory' => PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'] . '/' . IMAGE_TN_DIRECTORY . '/',
								'prefix' => $this->aParams['tnPrefix'] . $sPrefix,
								'watermark' => $this->aParams['tnWatermark'] ? $this->aParams['watermark'] : null,
								'aspectRatio' => $this->aParams['aspectRatio']
							) );
						}
					}

					$oImageHandler->scale( $this->aParams['maxWidth'], $this->aParams['maxHeight'], array(
						'crop' => $this->aParams['crop'],
						'watermark' => $this->aParams['watermark'],
						'aspectRatio' => $this->aParams['aspectRatio']
					) );
				}
			}
		}

		return $aErr;
	}

	public function delete( $primaryId ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		// Abort if id is empty, otherwise all images will be deleted
		if( empty( $primaryId ) ) {
			throw new Exception( 'Missing primary ID upon deleting image' );
		}

		$aImages = $this->read( array(
			'imageId',
			'imageFileExtension',
			'imageParentType'
		), $primaryId );

		foreach( $aImages as $entry ) {
			$sImageDirectory = PATH_CUSTOM_IMAGE . '/' . $entry['imageParentType'];
			$sImage = $entry['imageId'] . '.' . $entry['imageFileExtension'];

			// Original
			if( is_file($sImageDirectory . '/' . $sImage) ) {
				unlink( $sImageDirectory . '/' . $sImage );
			}
			// Thumbnail
			if( is_file($sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/' . $sImage) ) {
				unlink( $sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/' . $sImage );
			}
			// Additonal thumbnails
			if( is_file($sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/small' . $sImage) ) {
				unlink( $sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/small' . $sImage );
			}
			if( is_file($sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/medium' . $sImage) ) {
				unlink( $sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/medium' . $sImage );
			}
			if( is_file($sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/large' . $sImage) ) {
				unlink( $sImageDirectory . '/' . IMAGE_TN_DIRECTORY . '/large' . $sImage );
			}
		}

		return $this->oDao->deleteDataByPrimary( $primaryId );
	}

	public static function deleteByParent( $iParentId, $sParentType, $sImageKey = null ) {
		$aPrimaryIds = array();
		$oImage = clFactory::create( 'clImage', PATH_MODULE . '/image/models' );
		$oImage->setParams( array(
			'parentType' => $sParentType,
			'imageKey' => $sImageKey
		) );
		$aImages = $oImage->readByParent( $iParentId, array(
			'imageId'
		) );
		foreach( $aImages as $entry ) {
			$aPrimaryIds[] = $entry['imageId'];
		}

		if( !empty($aPrimaryIds) ) return $oImage->delete( $aPrimaryIds );
		return false;
	}

	public function readByParent( $parentId, $aFields = array() ) {
		if( empty($this->aParams) ) return false;
		$this->oAcl->hasAccess( 'read' . $this->sModuleName );
		$aParams = array(
			'fields' => $aFields,
			'parentType' => $this->aParams['parentType'],
			'imageKey' => $this->aParams['imageKey'],
			'parentId' => $parentId
		);
		$result = $this->oDao->read( $aParams );
		$this->aLoaded = array_merge( $this->aLoaded, arrayToSingle($result, 'imageId', 'imageId') );
		return $result;
	}

	public function setParentType( $sParentType ) {
		$this->aParams['parentType'] = $sParentType;

		if( !is_dir(PATH_CUSTOM_IMAGE . '/' . $sParentType) ) {
            if( !mkdir(PATH_CUSTOM_IMAGE . '/' . $sParentType, IMAGE_CHMOD_DEFAULT) ) throw new Exception( sprintf(_('Could not create directory %s'), PATH_CUSTOM_IMAGE . '/' . $sParentType) );
        }
		if( !is_dir(PATH_CUSTOM_IMAGE . '/' . $sParentType . '/' . IMAGE_TN_DIRECTORY) ) {
            if( !mkdir(PATH_CUSTOM_IMAGE . '/' . $sParentType . '/' . IMAGE_TN_DIRECTORY, IMAGE_CHMOD_DEFAULT) ) throw new Exception( sprintf(_('Could not create directory %s'), PATH_CUSTOM_IMAGE . '/' . $sParentType . '/' . IMAGE_TN_DIRECTORY) );
        }
	}

	public function updateSortOrder( $aParams = array()) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );
		if( empty($aParams) ) return false;

		$aImageIds = func_get_args();
		$sParentType = $aImageIds[0];
		$iParentId = $aImageIds[1];

		unset( $aImageIds[0], $aImageIds[1] );

		if( empty($sParentType) || empty($iParentId) || empty($aImageIds) ) return false;

		return $this->oDao->updateSortOrder( $sParentType, $iParentId, $aImageIds );
	}

	/**
	 * Apply watermark to any given image ID's
	 */
	public function applyWatermark( $mImageId, $sWatermarkFile = null, $aParams = array() ) {
		$this->oAcl->hasAccess( 'write' . $this->sModuleName );

		if( empty($this->aParams['parentType']) ) return false;
		if( $sWatermarkFile === null && $GLOBALS['imageWatermark'] === null ) return false;
		if( $sWatermarkFile === null && $GLOBALS['imageWatermark'] !== null ) {
			// Default watermark
			$sWatermarkFile = $GLOBALS['imageWatermark'];
		}

		$aImages = $this->read( '*', $mImageId );
		$sParentDir = PATH_CUSTOM_IMAGE . '/' . $this->aParams['parentType'];

		$aParams += array(
			'repeat' => false,
			'position' => 'center',
			'padding' => 5,
			'quality' => 100,
			'additionalThumbnails' => array(
				'medium' => array(),
				'small' => array()
			)
		);

		/**
		 * Create watermark image object
		 */
		$aWatermarkImage = getimagesize( $sWatermarkFile );
		switch( $aWatermarkImage[2] ) {
			case IMAGETYPE_JPEG:
				$oImageWatermark = imagecreatefromjpeg( $sWatermarkFile );
				break;
			case IMAGETYPE_PNG:
				$oImageWatermark = imagecreatefrompng( $sWatermarkFile );
				break;
			case IMAGETYPE_GIF:
				$oImageWatermark = imagecreatefromgif( $sWatermarkFile );
				break;
			default:
				return false;
		}

		/**
		 * Handle each image
		 */
		foreach( $aImages as $aImage ) {
			$aImageFiles = array();

			// Main image
			$aImageFiles[] = $sParentDir . '/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];

			// Thumbnail
			$aImageFiles[] = $sParentDir . '/tn/' . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];

			// AdditionalThumbnail(s)
			if( !empty($aParams['additionalThumbnails']) ) {
				foreach( $aParams['additionalThumbnails'] as $sPrefix => $aThumbnail ) {
					$aImageFiles[] = $sParentDir . '/tn/' . $sPrefix . $aImage['imageId'] . '.' . $aImage['imageFileExtension'];
				}
			}

			/**
			 * Handle each image file of image
			 */
			foreach( $aImageFiles as $sImageFile ) {
				// Image data & resource
				$aImageParams = getimagesize( $sImageFile );
				switch( $aImageParams[2] ) {
					case IMAGETYPE_JPEG:
						$oImage = imagecreatefromjpeg( $sImageFile );
						break;
					case IMAGETYPE_PNG:
						$oImage = imagecreatefrompng( $sImageFile );
						break;
					case IMAGETYPE_GIF:
						$oImage = imagecreatefromgif( $sImageFile );
						break;
					default:
						return false;
				}

				/**
				 * Position of watermark
				 */
				switch( $aParams['position'] ) {
					case 'topLeft':
						$iSrcX = $aParams['padding'];
						$iSrcY = $aParams['padding'];
						break;
					case 'topRight':
						$iSrcX = $aImageParams[0] - $aWatermarkImage[0] - $aParams['padding'];
						$iSrcY = $aParams['padding'];
						break;
					case 'bottomLeft':
						$iSrcX = $aParams['padding'];
						$iSrcY = $iImgHeight - $aWatermarkImage[1] - $aParams['padding'];
						break;
					case 'bottomRight':
						$iSrcX = $aImageParams[0] - $aWatermarkImage[0] - $aParams['padding'];
						$iSrcY = $aImageParams[1] - $aWatermarkImage[1] - $aParams['padding'];
						break;
					case 'center':
					default:
						$iSrcX = ( $aImageParams[0] - $aWatermarkImage[0] ) / 2;
						$iSrcY = ( $aImageParams[1] - $aWatermarkImage[1] ) / 2;
				}

				if( $aParams['repeat'] === true ) {
					/**
					 * Repeat watermark over hole image
					 */

					$oTmpImage = imagecreatetruecolor( $aWatermarkImage[0], $aWatermarkImage[1] );
					if( $aWatermarkImage[2] == IMAGETYPE_PNG || $aWatermarkImage[2] == IMAGETYPE_GIF) {
						// For transparent background
						imagealphablending( $oTmpImage, false );
						imagesavealpha( $oTmpImage, true );
						$iTransparent = imagecolorallocatealpha( $oImageWatermark, 255, 255, 255, 127 );
						imagecolortransparent( $oTmpImage, $iTransparent);
					}
					imagecopyresampled( $oTmpImage, $oImageWatermark, 0, 0, $aParams['padding'], $aParams['padding'], $aWatermarkImage[0], $aWatermarkImage[1], $aWatermarkImage[0], $aWatermarkImage[1] );
					$oImageWatermark = $oTmpImage;

					$iPasteX = 0;
					while( $iPasteX < $aImageParams[0] ) {
						$iPasteY = 0;
						while( $iPasteY < $aImageParams[0] ){
							$bResult = imagecopy( $oImage, $oImageWatermark, $iPasteX, $iPasteY, 0, 0, $aWatermarkImage[0], $aWatermarkImage[1] );
							$iPasteY += $aWatermarkImage[1];
						}
						$iPasteX += $aWatermarkImage[0];
					}

				} else {
					/**
					 * Apply watermark over part of the image
					 */

					// For transparent background
					imagealphablending( $oImageWatermark, false );
					imagesavealpha( $oImageWatermark, true );
					$iTransparent = imagecolorallocatealpha( $oImageWatermark, 255, 255, 255, 127 );
					imagecolortransparent( $oImageWatermark, $iTransparent);

					$bResult = imagecopy( $oImage, $oImageWatermark, $iSrcX, $iSrcY, 0, 0, $aWatermarkImage[0], $aWatermarkImage[1] );
				}

				// Destroy an watermark image resource
				imagedestroy( $oImageWatermark );

				/**
				 * Overwrite existing image
				 */
				switch( $aImageParams[2] ) {
					case IMAGETYPE_JPEG:
						imagejpeg( $oImage, $sImageFile, $aParams['quality']);
						break;
					case IMAGETYPE_PNG:
						$aParams['quality'] = round($aParams['quality'] / 100) - 1;
						if( $aParams['quality'] < 0 ) $aParams['quality'] = 0;
						imagepng( $oImage, $sImageFile, $aParams['quality'] );
						break;
					case IMAGETYPE_GIF:
						imagegif( $oImage, $sImageFile );
						break;
					default:
						return false;
				}
			}
		}

		return true;
	}

}
