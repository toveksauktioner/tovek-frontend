<?php

// Connects and sends mail with SMTP
class clMailSmtp {
	// Debug
	private $bDebug = false;
	public $aDebug = array();
	
	// Server settings
	private $sServer;
	private $sUsername;
	private $sPassword;
	private $iTimeout;
	
	// Additional variables
	private $sLastReply;
	private $aServerExtensions;
	
	private $sBoundary;
	private $sCharset;
	private $sContentType;
	
	private $aHeaders = array();
	private $sSubject = '';
	private $sBodyText = '';
	private $sBodyHtml = '';
	private $aTo = array();
	private $aAttachments = array();

	public function __construct( $aParams = array() ) {
		$aParams += array(
			'boundary' => 'frontier' . uniqid(),
			'charset' => 'utf-8',
			'content-type' => null,
			# Server settings
			'server' => '',
			'username' => '',
			'password' => ''
		);
		
		$this->sServer = $aParams['server'];
		$this->sUsername = $aParams['username'];
		$this->sPassword = $aParams['password'];
		$this->iTimeout = ( array_key_exists('timeout', $aParams) ? $aParams['timeout'] : 30 );
		
		$this->sBoundary = $aParams['boundary'];
		$this->sCharset = $aParams['charset'];
		if( $aParams['content-type'] !== null ) $this->sContentType = $aParams['content-type'];
	}

	/**
	 * Set server params
	 */
	public function setServerParams( $aParams = array() ) {
		$aParams += array(
			'server' => '',
			'username' => '',
			'password' => ''
		);
		
		$this->sServer = $aParams['server'];
		$this->sUsername = $aParams['username'];
		$this->sPassword = $aParams['password'];
		
		return $this;
	}
	
	/**
	 * Read buffer as respons
	 */
	private function getResponse( &$rConnection ) {
		if( !is_resource($rConnection) ) {
			return false;
		}
		
		$iEndTime = 0;
		@stream_set_timeout( $rConnection, $this->iTimeout );
		if( $this->iTimeout > 0 ) {
			$iEndTime = time() + $this->iTimeout;
		}
		
		$sBuffer = '';
		while( is_resource($rConnection) && !feof($rConnection) ) {
			$sTmpBuffer = @fgets($rConnection, 515);
			
			$sBuffer .= $sTmpBuffer;
			
			if( isset($sTmpBuffer[3]) && $sTmpBuffer[3] == ' ' ) {
				break;
			}
			
			$aStreamInfo = stream_get_meta_data($rConnection);
			if( $aStreamInfo['timed_out'] ) {
				// Stream timed out
				break;
			}
			
			if( $iEndTime && time() > $iEndTime ) {
				// Timelimit reached
				break;
			}
			
		}
		
		return $sBuffer;
	}

	/**
	 * Send mail
	 */
	public function send() {
		if( empty($this->sServer) ) return false;
		
		// Open smtp connection
		$rConnection = @fsockopen( $this->sServer, 25, $errno, $errstr, 10 );
		
		if( $rConnection ) {
			$sDomain = ( !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '127.0.0.1' );
			
			// Server operating system check
			if( mb_substr(PHP_OS, 0, 3) != 'WIN' ) {
				// Not Windows
				$iMaxExecutionTime = ini_get('max_execution_time');
				if( $iMaxExecutionTime != 0 && $this->iTimeout > $iMaxExecutionTime ) {
					@set_time_limit( $this->iTimeout );
				}
				@stream_set_timeout( $rConnection, $this->iTimeout, 0 );
			}
			
			// Response
			$sBuffer = $this->getResponse( $rConnection );			
			if( $this->bDebug === true ) {
				$this->aDebug[] = trim( $sBuffer );
			}
			
			// Check for IP address and encapsulate, RFC 2821.
			$domain = explode( '.', $sDomain );
			if( is_numeric($domain[ (count($domain) - 1) ]) ) {
				$sDomain = '[' . $sDomain . ']';
			}
			
			// Send the RFC2554 specified EHLO.
			if( !$this->sendHello( $rConnection, 'EHLO', $sDomain ) ) {
				// Say regular HELO ;)
				$this->sendHello( $rConnection, 'HELO', $sDomain );				
			}
			
			// Auth with server if required
			if( !empty($this->sUsername) ) {
				// Request Auth Login
				$this->sendCommand( $rConnection, "AUTH LOGIN");
				
				// Send username
				$this->sendCommand( $rConnection, base64_encode($this->sUsername) );
				
				// Send password
				$this->sendCommand( $rConnection, base64_encode($this->sPassword) );
			}
			
			// MAIL FROM
			$this->sendCommand( $rConnection, 'MAIL FROM:<' . $this->aHeaders['From'] . '>', 250 );
			
			/**
			 * Loop thru and send emails
			 */
			$this->aHeaders['To'] = '';
			$iLastKey = null;
			foreach( $this->aTo as $key => $value ) {
				// Find last key
				$iLastKey = $key;
			}
			foreach( $this->aTo as $key => $value ) {
				// RCPT TO
				$this->sendCommand( $rConnection, 'RCPT TO:<' . $value . '>', 250 );
				// Header to
				$this->aHeaders['To'] .= $value . ($key != $iLastKey ? ',' : '');
			}		
			
			// Send message data
			$this->sendData( $rConnection, $this->getHeaders() . $this->getMessage() );
			
			// Close connection
			$this->sendCommand( $rConnection, 'QUIT' );
			fclose( $rConnection );
			
		} else {
			// Failed to open connection
			if( $this->bDebug === true ) {
				$this->aDebug[] = $errstr . ' ' . $errno;
			}
			return false;
		}		
		return true;
	}
	
	/**
	 * Send command
	 */
	private function sendCommand( &$rConnection, $sCommand = '', $sExpectedResponse = null ) {
		fwrite( $rConnection, $sCommand . "\r\n" );
		$this->sLastReply = $this->getResponse($rConnection);
		
		if( $this->bDebug ) {
			$this->aDebug[] = trim( $this->sLastReply );
		}
		
		// Fetch SMTP code and possible error code explanation
		if( preg_match("/^([0-9]{3})[ -](?:([0-9]\\.[0-9]\\.[0-9]) )?/", $this->sLastReply, $aMatches) ) {
			$code = $aMatches[1];
			$code_ex = (count($aMatches) > 2 ? $aMatches[2] : null);
			// Cut off error code from each response line
			$detail = preg_replace( "/{$code}[ -]".($code_ex ? str_replace(".", "\\.", $code_ex)." " : "")."/m", '', $this->sLastReply );
		} else {
			// Fallback to simple parsing if regex fails for some unexpected reason
			$code = mb_substr($this->sLastReply, 0, 3);
			$code_ex = null;
			$detail = mb_substr($this->sLastReply, 4);
		}
		
		if( !empty($sExpectedResponse) ) {
			if( !in_array($code, (array) $sExpectedResponse) ) {
				return false;
			}
		}
		
		return true;
	}
	
	private function sendData( &$rConnection, $sData ) {
		if( !$this->sendCommand($rConnection, 'DATA', 354) ) {
			return false;
		}		
		fwrite( $rConnection, $this->getHeaders() );
		fwrite( $rConnection, $this->getMessage() );
		fwrite( $rConnection, "\r\n.\r\n" ); // End message
		return true;
	}
	
	private function sendHello( &$rConnection, $sHello, $sHost ) {
		// TODO: Store server extensions
		return $this->sendCommand( $rConnection, $sHello . ' ' . $sHost, 250 );
	}

	public function addTo( $address ) {
		$this->aTo = array_merge( $this->aTo, (array) $address );
		return $this;
	}

	public function setFrom( $sFrom, $sName = '' ) {
		$this->addHeader( 'From', (string) $sFrom );
		return $this;
	}

	public function setReplyTo( $sReplyTo, $sName = '' ) {
		$this->addHeader( 'Reply-to', (string) $sReplyTo );
		return $this;
	}
	
	public function setReturnPath( $sReturnPath ) {
		$this->addHeader( 'Return-path', (string) $sReturnPath );
		return $this;
	}
	
	public function setSubject( $sSubject ) {
		$this->sSubject = mb_encode_mimeheader( (string) $sSubject, 'UTF-8', 'B', "\n" );
		return $this;
	}

	public function setBody( $sBodyText, $sBodyHtml = '' ) {
		$this->setBodyText( $sBodyText );
		$this->setBodyHtml( $sBodyHtml );
		return $this;
	}

	public function setBodyText( $sBody ) {
		$this->sBodyText = (string) $sBody;
		return $this;
	}

	public function setBodyHtml( $sBody ) {
		$this->sBodyHtml = (string) $sBody;
		return $this;
	}
	
	public function addAttachment( $sName = 'Test.txt', $sType = 'application/octet-stream', $sContent = '' ) {
		$aAttachment = array(
			'name' => $sName,
			'type' => $sType,
			'content' => $sContent
		);
		$this->aAttachments[] = $aAttachment;
		return $this;
	}

	public function addHeaders( $aHeaders ) {
		foreach( $aHeaders as $key => $value ) {
			$this->addHeader( $key, $value );
		}
		return $this;
	}

	public function addHeader($sName, $sBody) {
		$this->aHeaders[$sName] = $sBody;
		return $this;
	}

	public function getHeaders() {
		$aHeaders = $this->aHeaders;
		$sHeaders = '';		
			
		if( empty($this->sContentType) ) {
			// Detect which content type to use
			if( !empty($this->sBodyHtml) && empty($this->sBodyText) && empty($this->aAttachments) ) {
				// html
				$this->sContentType = 'multipart/alternative';
			} elseif( !empty($this->sBodyText) && empty($this->sBodyHtml) && empty($this->aAttachments)  ) {
				// plain
				$this->sContentType = 'multipart/alternative';
			} elseif( !empty($this->sBodyText) && !empty($this->sBodyHtml) && empty($this->aAttachments) ) {
				// html + plain
				$this->sContentType = 'multipart/alternative';			
			} elseif( !empty($this->sBodyText) && !empty($this->sBodyHtml) && empty($this->aAttachments) ) {				
				// html + text + attachments
				
				/*
				 * TODO
				 * This does not display the plain and html as alternatives	to
				 * each other. A complete rewrite is needed for the boundaries..
				 */
				
				$this->sContentType = 'multipart/mixed';
			} elseif( !empty($this->aAttachments) ) {
				// attachments
				$this->sContentType = 'multipart/mixed';
			}
		}
		
		$aHeaders += array(
			'MIME-Version' => '1.0',
			'Content-Type' => $this->sContentType . '; boundary="' . $this->sBoundary . '"',
			'Subject' => $this->sSubject
		);
		
		foreach( $aHeaders as $key => $value ) {
			$sHeaders .= $key . ': ' . $value . "\n";
		}
		
		return $sHeaders;
	}

	public function getMessage() {
		$sMessage = 'This is a multi-part message in MIME format.';

		if( !empty($this->sBodyText) ) {
			$sMessage .= '
--' . $this->sBoundary . '
Content-Type: text/plain; charset="' . $this->sCharset . '"

' . $this->sBodyText;
		}

		if( !empty($this->sBodyHtml) ) {
			$sMessage .= '
--' . $this->sBoundary . '
Content-Type: text/html; charset="' . $this->sCharset . '"

' . $this->sBodyHtml;
		}

		foreach( $this->aAttachments as $value ) {

			$sMessage .= '
--' . $this->sBoundary . '
Content-Type: ' . $value['type'] . '; name="' . $value['name'] . '"
Content-Disposition: attachment
Content-Transfer-Encoding: base64

' . chunk_split( base64_encode($value['content']) );
		}
		
		// Add last boundary with ending hyphens
		$sMessage .= '
--' . $this->sBoundary . '--';
		
		return $sMessage;
	}
	
}