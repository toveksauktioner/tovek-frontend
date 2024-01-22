<?php

/**
 * $Id: clBarcodeCode39.php 1405 2014-04-03 10:00:51Z alu $
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * This class generates Code 39 format 2 barcodes with GD, to a gif file or as a raw output
 * Supports 0-9, A-Z [SPACE] $ % * + - . 
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: alu $
 * @version		Subversion: $Revision: 1405 $, $Date: 2014-04-03 12:00:51 +0200 (to, 03 apr 2014) $
 */

/**
* Class for generate Code 39 barcodes
*/
class clBarcodeCode39 {
	/**
	 * Code 39 format 2 specifications
	 */
	const format2B = '11'; // Wide - Black
	const format2W = '00'; // Wide - White
	const format2b = '10'; // Narrow - Black
	const format2w = '01'; // Narrow - White
	
	/**
	 * Barcode code
	 *
	 * @var array $aCode
	 */
	private $_aCode = array();
	
	/**
	 * Code 39 matrix
	 *
	 * @var array $aCodes39
	 */
	private $_aCodes39 = array(
		32 => '100011011001110110',
		36 => '100010001000100110',
		37 => '100110001000100010',
		42 => '100010011101110110',
		43 => '100010011000100010',
		45 => '100010011001110111',
		46 => '110010011001110110',
		47 => '100010001001100010',
		48 => '100110001101110110',
		49 => '110110001001100111',
		50 => '100111001001100111',
		51 => '110111001001100110',
		52 => '100110001101100111',
		53 => '110110001101100110',
		54 => '100111001101100110',
		55 => '100110001001110111',
		56 => '110110001001110110',
		57 => '100111001001110110',
		65 => '110110011000100111',
		66 => '100111011000100111',
		67 => '110111011000100110',
		68 => '100110011100100111',
		69 => '110110011100100110',
		70 => '100111011100100110',
		71 => '100110011000110111',
		72 => '110110011000110110',
		73 => '100111011000110110',
		74 => '100110011100110110',
		75 => '110110011001100011',
		76 => '100111011001100011',
		77 => '110111011001100010',
		78 => '100110011101100011',
		79 => '110110011101100010',
		80 => '100111011101100010',
		81 => '100110011001110011',
		82 => '110110011001110010',
		83 => '100111011001110010',
		84 => '100110011101110010',
		85 => '110010011001100111',
		86 => '100011011001100111',
		87 => '110011011001100110',
		88 => '100010011101100111',
		89 => '110010011101100110',
		90 => '100011011101100110'
	);

	/**
	 * Width of wide bars in barcode (should be 3:1)
	 *
	 * @var int $iBarcodeBarThickWidth
	 */
	public $iBarcodeBarThickWidth = 3;

	/**
	 * Width of thin bars in barcode (should be 3:1)
	 *
	 * @var int $iBarcodeBarThinWidth
	 */
	public $iBarcodeBarThinWidth = 1;

	/**
	 * Barcode background color (RGB)
	 *
	 * @var array $aBarcodeBackgroundRgb
	 */
	public $aBarcodeBackgroundRgb = array(255, 255, 255);

	/**
	 * Barcode height
	 *
	 * @var int $iBarcodeHeight
	 */
	public $iBarcodeHeight = 80;

	/**
	 * Barcode padding
	 *
	 * @var int $iBarcodePadding
	 */
	public $iBarcodePadding = 5;

	/**
	 * Use barcode text flag
	 *
	 * @var bool $bBarcodeText
	 */
	public $bBarcodeText = true;

	/**
	 * Barcode text size
	 *
	 * @var int $iBarcodeTextSize
	 */
	public $iBarcodeTextSize = 3;

	/**
	 * Use dynamic barcode width (will auto set width)
	 *
	 * @var bool $bBarcodeDynamicWidth
	 */
	public $bBarcodeDynamicWidth = true;

	/**
	 * Barcode width (if not using dynamic width)
	 *
	 * @var int $iBarcodeWidth
	 */
	public $iBarcodeWidth = 400;

	/**
	 * Set and format params
	 *
	 * @param string $sCode
	 */
	public function  __construct($sCode = null) {
		// Reformat
		$sCode = (string) strtoupper($sCode);
		
		// Convert code string to array with characters
		$this->_aCode = str_split($sCode);
		
		// Add start and stop symbols
		array_unshift($this->_aCode, '*');
		array_push($this->_aCode, '*');
	}

	/**
	 * Output the barcode (or save as file if filename set)
	 * Outputs to http can be with or without headers
	 *
	 * @param string $sFilename (optional)
	 * @param bool $bHeaders (optional)
	 * @return bool|string Returns image as string if no filename and headers are specified, or bool result otherwise
	 */
	public function output($sFilename = null, $bHeaders = true) {
		// Check for valid code
		if(!is_array($this->_aCode) || !count($this->_aCode)) {
			return false;
		}

		// Bars coordinates and params
		$aBars = array();

		// Position pointer
		$iPos = $this->iBarcodePadding;

		// Barcode text
		$sBarcode = null;

		// Set code 39 codes
		$i = 0;
		foreach($this->_aCode as $iKey => $sValue) {
			// Check for valid code
			if(isset($this->_aCodes39[ord($sValue)])) {
				// Valid code add code 39, also add separator between characters if not first character
				$sCode = ( $i ? self::format2w : null ) . $this->_aCodes39[ord($sValue)];

				// Check for valid code 39 code
				if($sCode) {
					// Add to barcode text
					$sBarcode .= ' ' . $sValue;

					// Init params
					$w = 0;
					$sFormat2 = $fill = null;

					// Add each bar coordinates and params
					for($j = 0; $j < strlen($sCode); $j++) {
						// Format 2 code
						$sFormat2 .= (string)$sCode[$j];

						// Valid format 2 code
						if(strlen($sFormat2) == 2) {
							// Set bar fill
							$fill = $sFormat2 == self::format2B || $sFormat2 == self::format2b ? "_000" : "_fff";

							// Set bar width
							$w = $sFormat2 == self::format2B || $sFormat2 == self::format2W ? $this->iBarcodeBarThickWidth : $this->iBarcodeBarThinWidth;

							// Check for valid bar params
							if($w && $fill) {
								// Add bar coordinates and params
								$aBars[] = array($iPos, $this->iBarcodePadding, $iPos - 1 + $w, $this->iBarcodeHeight - $this->iBarcodePadding - 1, $fill);

								// Move position pointer
								$iPos += $w;
							}

							// Reset params
							$sFormat2 = $fill = null;
							$w = 0;
						}
					}
				}
				$i++;
			} else {
				// Invalid code, remove character from code
				unset($this->_aCode[$iKey]);
			}
		}

		// Check for valid bar coordinates and params
		if(!count($aBars)) {
			// No valid bar coordinates and params
			return false;
		}

		// Set barcode width
		$bc_w = $this->bBarcodeDynamicWidth ? $iPos + $this->iBarcodePadding : $this->iBarcodeWidth;

		// If not dynamic width check if barcode wider than barcode image width
		if(!$this->bBarcodeDynamicWidth && $iPos > $this->iBarcodeWidth) {
			return false;
		}

		// Initialize image
		$rImage = imagecreate($bc_w, $this->iBarcodeHeight);
		$_000 = imagecolorallocate($rImage , 0, 0, 0);
		$_fff = imagecolorallocate($rImage, 255, 255, 255);
		$_bg = imagecolorallocate($rImage, $this->aBarcodeBackgroundRgb[0], $this->aBarcodeBackgroundRgb[1], $this->aBarcodeBackgroundRgb[2]);

		// Fill background
		imagefilledrectangle($rImage, 0, 0, $bc_w, $this->iBarcodeHeight, $_bg);

		// Add bars to barcode
		for($i = 0; $i < count($aBars); $i++) {
			imagefilledrectangle($rImage, $aBars[$i][0], $aBars[$i][1], $aBars[$i][2], $aBars[$i][3], $$aBars[$i][4]);
		}

		// Check if using barcode text
		if($this->bBarcodeText) {
			// Set barcode text box
			$bBarcodeText_h = 10 + $this->iBarcodePadding;
			imagefilledrectangle($rImage, $this->iBarcodePadding, $this->iBarcodeHeight - $this->iBarcodePadding - $bBarcodeText_h, $bc_w - $this->iBarcodePadding, $this->iBarcodeHeight - $this->iBarcodePadding, $_fff);

			// Set barcode text font params
			$font_size = $this->iBarcodeTextSize;
			$font_w = imagefontwidth($font_size);
			$font_h = imagefontheight($font_size);

			// Set text position
			$txt_w = $font_w * strlen($sBarcode);
			$pos_center = ceil((($bc_w - $this->iBarcodePadding) - $txt_w) / 2);

			// Set text color
			$txt_color = imagecolorallocate($rImage, 0, 255, 255);

			// Draw barcod text
			imagestring($rImage, $font_size, $pos_center, $this->iBarcodeHeight - $bBarcodeText_h - 2, $sBarcode, $_000);
		}
		
		if($sFilename) {
			// Save image
			imagegif($rImage, $sFilename);
		} else {
			// Display image
			if($bHeaders) {
				header('Content-Type: image/gif');
				imagegif($rImage);
			} else {
				// Return image as string
				ob_start();
				imagegif($rImage);
				return ob_get_clean();
			}
		}
		
		imagedestroy($rImage);

		// Valid barcode
		return true;
	}
}