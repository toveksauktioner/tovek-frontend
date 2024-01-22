<?php

class clImageHandler {

	private $aImage;
	private $oImage;
	private $sImageFile;

	public function __construct( $sImageFile = null ) {
		$this->aImage = false;
		if( $sImageFile !== null ) $this->setImageFile( $sImageFile );
	}

	public function __destruct() {
		if( is_resource($this->oImage) ) imagedestroy( $this->oImage );
	}

	public function cleanup() {
		if( is_resource($this->oImage) ) imagedestroy( $this->oImage );
	}

	public function createThumbNail( $iWidth, $iHeight, $aParams = array() ) {
		$aParams += array(
			'crop' => false,
			'directory' => dirname( $this->sImageFile ),
			'newFileName' => basename($this->sImageFile),
			'prefix' => 'tn',
			'quality' => 100,
			'watermark' => null,
			'aspectRatio' => null # Experimental!
		);

		if( !copy($this->sImageFile, $aParams['directory'] . $aParams['prefix'] . $aParams['newFileName']) ) return false;

		$oImageHandler = new clImageHandler( $aParams['directory'] . $aParams['prefix'] . $aParams['newFileName'] );
		unset( $aParams['prefix'], $aParams['newFileName'], $aParams['directory'] );
		$oImageHandler->scale( $iWidth, $iHeight, $aParams );
		unset($oImageHandler);
	}

	public function createWatermark( $aParams = array() ) {
		$aParams += array(
			'file' => null,
			'position' => 'center',
			'padding' => 5
		);

		// Add watermark if given
		if ( $aParams['file'] !== null && is_file($aParams['file']) ) {
			$aImage = getimagesize( $aParams['file'] );
			switch ( $aImage[2] ) {
				case IMAGETYPE_JPEG:
					$oImageWatermark = imagecreatefromjpeg( $aParams['file'] );
					break;
				case IMAGETYPE_PNG:
					$oImageWatermark = imagecreatefrompng( $aParams['file'] );
					break;
				case IMAGETYPE_GIF:
					$oImageWatermark = imagecreatefromgif( $aParams['file'] );
					break;
				default:
					return false;
			}

			switch( $aParams['position'] ) {
				case 'topLeft':
					$iSrcX = $aParams['padding'];
					$iSrcY = $aParams['padding'];
					break;
				case 'topRight':
					$iSrcX = $this->aImage[0] - $aImage[0] - $aParams['padding'];
					$iSrcY = $aParams['padding'];
					break;
				case 'bottomLeft':
					$iSrcX = $aParams['padding'];
					$iSrcY = $iImgHeight - $aImage[1] - $aParams['padding'];
					break;
				case 'bottomRight':
					$iSrcX = $this->aImage[0] - $aImage[0] - $aParams['padding'];
					$iSrcY = $this->aImage[1] - $aImage[1] - $aParams['padding'];
					break;
				case 'center':
				default:
					$iSrcX = ( $this->aImage[0] - $aImage[0] ) / 2;
					$iSrcY = ( $this->aImage[1] - $aImage[1] ) / 2;
			}

			$bResult = imagecopy( $this->oImage, $oImageWatermark, $iSrcX, $iSrcY, 0, 0, $aImage[0], $aImage[1] );
			imagedestroy( $oImageWatermark );
			return $bResult;
		}
	}

	/**
	 * @return boolean
	 * @param integer $iWidth
	 * @param integer $iHeight
	 * @param array $aParams[optional]
	 */
	public function scale( $iWidth, $iHeight, $aParams = array() ) {
		if( $this->aImage === false ) return false;

		$aParams += array(
			'crop' => false,
			'quality' => 95,
			'watermark' => null,
			'aspectRatio' => null
		);

		// Calculate new size
		$iScale = min( $iWidth / (int) $this->aImage[0], $iHeight / (int) $this->aImage[1] );

		if( $aParams['crop'] ) {
			// Calculate width and height ratio
			$fImageRatio = $this->aImage[0] / $this->aImage[1];
			if( $iWidth == 0 ) $iWidth = $iHeight * $fImageRatio;
			if( $iHeight == 0 ) $iHeight = $iWidth / $fImageRatio;
			$fNewRatio = $iWidth / $iHeight;

			if ($this->aImage[0] / $iWidth > $this->aImage[1] / $iHeight) {
				$iSrcX = ( $this->aImage[0] - $iWidth * $this->aImage[1] / $iHeight ) / 2;
				$iSrcY = 0;
				$iSrcWidth = $this->aImage[1] * $fNewRatio;
				$iSrcHeight = $this->aImage[1];
			} else {
				$iSrcX = 0;
				$iSrcY = ( $this->aImage[1] - $iHeight * $this->aImage[0] / $iWidth ) / 2;
				$iSrcWidth = $this->aImage[0];
				$iSrcHeight = $this->aImage[0] / $fNewRatio;
			}
			$iNewWidth = $iWidth;
			$iNewHeight = $iHeight;
			
		} elseif( $aParams['aspectRatio'] !== null ) {			
			$iScale = 0;
			$iNewWidth = 0;
			$iNewHeight = 0;
			$iSrcX = 0;
			$iSrcY = 0;
			$iSrcWidth = $this->aImage[0];
			$iSrcHeight = $this->aImage[1];
			
			// Aspect ratio by X & Y
			list( $iXunit, $iYunit ) = explode( ':', $aParams['aspectRatio'] );
			
			// Calculating height relative to given aspect ratio
			$iNewWidth = $iSrcWidth;
			$iNewHeight = (int) ($iYunit * $iNewWidth / $iXunit);
			
			// If new height is longer then source
			if( $iSrcHeight < $iNewHeight ) {
				// Calculating width relative to given aspect ratio
				$iNewHeight = $iSrcHeight;
				$iNewWidth = (int) ($iXunit * $iNewHeight / $iYunit);
			}
			
		} else {
			$iNewWidth = floor( $iScale * $this->aImage[0] );
			$iNewHeight = floor( $iScale * $this->aImage[1] );
			$iSrcX = 0;
			$iSrcY = 0;
			$iSrcWidth = $this->aImage[0];
			$iSrcHeight = $this->aImage[1];
		}

		if( $iScale < 1 || $aParams['watermark'] !== null ) {
			$oTmpImage = imagecreatetruecolor( $iNewWidth, $iNewHeight );

            if($this->aImage[2] == IMAGETYPE_PNG || $this->aImage[2] == IMAGETYPE_GIF) {
                // For transparent background
                imagealphablending($oTmpImage, false);
                imagesavealpha($oTmpImage, true);
                $iTransparent	= imagecolorallocatealpha( $this->oImage, 255, 255, 255, 127 );
                imagecolortransparent( $oTmpImage, $iTransparent);
            }

			imagecopyresampled( $oTmpImage, $this->oImage, 0, 0, $iSrcX, $iSrcY, $iNewWidth, $iNewHeight, $iSrcWidth, $iSrcHeight );
			$this->oImage = $oTmpImage;
			$this->aImage[0] = $iNewWidth;
			$this->aImage[1] = $iNewHeight;
		}

		if( $aParams['watermark'] !== null ) $this->createWatermark( array('file' => $aParams['watermark']) );

		if( $iScale < 1 || $aParams['watermark'] !== null ) {
			switch ($this->aImage[2]) {
				case IMAGETYPE_JPEG:
					imagejpeg( $this->oImage, $this->sImageFile, $aParams['quality']);
					break;
				case IMAGETYPE_PNG:
					$aParams['quality'] = round($aParams['quality'] / 100) - 1;
					if ($aParams['quality'] < 0) $aParams['quality'] = 0;

					imagepng( $this->oImage, $this->sImageFile, $aParams['quality'] );
					break;
				case IMAGETYPE_GIF:
					imagegif( $this->oImage, $this->sImageFile );
					break;
				default:
					return false;
			}
			return true;
		}

		return false;
	}

	public function setImageFile( $sImageFile ) {
		if( !is_file($sImageFile) ) return false;

		$this->cleanup();

		$this->aImage = getimagesize( $sImageFile );
		if( $this->aImage === false ) return false;

		$this->sImageFile = $sImageFile;

		switch ( $this->aImage[2] ) {
			case IMAGETYPE_JPEG:
				$this->oImage = imagecreatefromjpeg( $this->sImageFile );
				break;
			case IMAGETYPE_PNG:
				$this->oImage = imagecreatefrompng( $this->sImageFile );
				break;
			case IMAGETYPE_GIF:
				$this->oImage = imagecreatefromgif( $this->sImageFile );
				break;
			default:
				return false;
		}

		return true;
	}
}
