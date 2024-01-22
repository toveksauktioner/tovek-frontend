<?php

require_once PATH_PLATFORM . '/composer/vendor/autoload.php';
require_once PATH_CORE . '/phpMailer/cfPhpMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// require_once PATH_CORE . '/phpMailer/library/src/Exception.php';
// require_once PATH_CORE . '/phpMailer/library/src/PHPMailer.php';
// require_once PATH_CORE . '/phpMailer/library/src/SMTP.php';

class clPhpMailer {

	private $oPhpMailer;

	private $bDebug = PHP_MAILER_DEBUG;
	private $aParams;

	public function __construct( $aParams ) {
		$this->setParams( $aParams );

		// Debug?
		if( !empty($this->aParams['debug']) ) {
			$this->bDebug = $this->aParams['debug'];
		}

		$this->oPhpMailer = new PHPMailer();

		// Set encoding
		$this->oPhpMailer->CharSet = 'UTF-8';

		/**
		 * Type of service
		 */
		switch( PHP_MAILER_SERVICE ) {
			case 'smtp': $this->initSmtp(); break;
			case 'mail': $this->initMail(); break;
		}
	}

	public function setParams( $aParams ) {
		$this->aParams = $aParams;
	}

	public function initSmtp() {
		$this->oPhpMailer->isSMTP();

		if( $this->bDebug === true ) {
			/**
			 * Enable SMTP debugging
			 * 0 = off (for production use)
			 * 1 = client messages
			 * 2 = client and server messages
			 */
			$this->oPhpMailer->SMTPDebug = 2;
			$this->oPhpMailer->Debugoutput = 'html';
		} else {
			$this->oPhpMailer->SMTPDebug = 0;
		}

		$this->oPhpMailer->Host = $this->aParams['smtpHost'];
		$this->oPhpMailer->Port = !empty($this->aParams['smtpPort']) ? $this->aParams['smtpPort'] : PHP_MAILER_PORT;
		$this->oPhpMailer->SMTPAuth = PHP_MAILER_AUTH;
		$this->oPhpMailer->Username = $this->aParams['smtpUsername'];
		$this->oPhpMailer->Password = $this->aParams['smtpPassword'];

		// Secure
		$sSecure = !empty($this->aParams['smtpSecure']) ? $this->aParams['smtpSecure'] : PHP_MAILER_SECURE;
		if( !empty($sSecure) ) {
			$this->oPhpMailer->SMTPSecure = PHP_MAILER_SECURE;

			if( PHP_MAILER_SSL_VERIFY === false ) {
				$this->oPhpMailer->SMTPOptions = array(
					'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
				);
			}
		}
	}

	public function initMail() {
		// Nothing more is needed here at the point
	}

	public function setFrom( $sAddress, $sName = '' ) {
		$this->oPhpMailer->setFrom( $sAddress, $sName );
	}

	public function setReturnPath( $sAddress ) {
		$this->oPhpMailer->ReturnPath = $sAddress;
	}

	public function setReplyTo( $sAddress, $sName = '' ) {
		$this->oPhpMailer->addReplyTo( $sAddress, $sName );
	}

	public function addTo( $sAddress, $sName = '' ) {
		$this->oPhpMailer->addAddress( $sAddress, $sName );
	}

	public function addCC( $sAddress, $sName = '' ) {
		$this->oPhpMailer->addCC( $sAddress, $sName );
	}

	public function addBCC( $sAddress, $sName = '' ) {
		$this->oPhpMailer->addBCC( $sAddress, $sName );
	}

	public function setSubject( $sSubject ) {
		$this->oPhpMailer->Subject = $sSubject;
	}

	public function setBodyHtml( $sBodyHtml ) {
		/**
		 * Read an HTML message body from an external file, convert referenced
		 * images to embedded, convert HTML into a basic plain-text alternative body.
		 */
		$this->oPhpMailer->msgHTML( $sBodyHtml, PATH_PUBLIC . '/images/user' );
	}

	public function setBodyText( $sBodyText ) {
		$this->oPhpMailer->AltBody = $sBodyText;
	}

	public function setBody( $sBodyText, $sBodyHtml = '' ) {
		$this->setBodyText( $sBodyText );
		$this->setBodyHtml( $sBodyHtml );
		return $this;
	}

	public function addAttachment( $sName = '', $sType = 'application/octet-stream', $sPath = '' ) {
		$this->oPhpMailer->addAttachment( $sPath, $sName );
		return true;
	}

	public function sign() {
		/**
		 * Configure message signing
		 * (the actual signing does not occur until sending)
		 */
		$this->oPhpMailer->sign(
			PHP_MAILER_SIGN_CERT,
			PHP_MAILER_SIGN_KEY,
			PHP_MAILER_SIGN_PASSWORD,
			PHP_MAILER_SIGN_PEM
		);
	}

	public function send() {
		// Sign the e-mail
		if( PHP_MAILER_SIGN === true ) $this->sign();

		if( !$this->oPhpMailer->send() ) {
			$this->errorHandling();
			return false;
		}
		return true;
	}

	public function errorHandling() {
		return $this->oPhpMailer->ErrorInfo;
	}

	public function smtpCheck() {
		// Create a new SMTP instance
		$oSMTP = new SMTP;

		// Enable connection-level debug output
		$oSMTP->do_debug = SMTP::DEBUG_CONNECTION;

		try {
			// Connect to an SMTP server
			if( $oSMTP->connect($this->aParams['smtpHost'], 25) ) {
				// Say hello
				if( $oSMTP->hello(SITE_DOMAIN) ) {
					// Authenticate
					if( $oSMTP->authenticate($this->aParams['smtpUsername'], $this->aParams['smtpPassword']) ) {
						// Connection successful
						return true;
					} else {
						throw new Exception( 'Authentication failed: ' . $oSMTP->getLastReply() );
					}
				} else {
					throw new Exception( 'HELO failed: '. $oSMTP->getLastReply() );
				}
			} else {
				throw new Exception( 'Connect failed' );
			}
		} catch( Exception $eException ) {
			return 'SMTP error: ' . $eException->getMessage() . "\n";
		}

		// Whatever happened, close the connection.
		$oSMTP->quit( true );

		return true;
	}

	public function addHeaders( $aHeaders ) {
		/**
		 * Empty dummy function
		 */
	}

	public function addHeader( $sName, $sBody ) {
		/**
		 * Empty dummy function
		 */
	}

}
