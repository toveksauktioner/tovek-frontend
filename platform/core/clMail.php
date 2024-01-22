<?php

class clMail {
	
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
			'content-type' => null
		);

		$this->sBoundary = $aParams['boundary'];
		$this->sCharset = $aParams['charset'];
		if( $aParams['content-type'] !== null ) $this->sContentType = $aParams['content-type'];
	}

	public function send() {
		foreach( $this->aTo as $value ) {
			if( !mail($value, $this->sSubject, $this->getMessage(), $this->getHeaders()) ) return false;
		}
		return true;
	}

	public function addTo( $address ) {
		$this->aTo = array_merge( $this->aTo, (array) $address );
		return $this;
	}

	public function setFrom( $sFrom, $sName = '' ) {
		if( !empty($sName) ) {
			$this->addHeader( 'From', '"' . (string) $sName . '" <' . (string) $sFrom .  '>');
		} else {
			$this->addHeader( 'From', (string) $sFrom );
		}		
		return $this;
	}
	
	public function addCC( $sFrom, $sName = '' ) {
		$this->addHeader( 'Cc', (string) $sFrom );
		return $this;
	}
	
	public function addBCC( $sFrom, $sName = '' ) {
		$this->addHeader( 'Bcc', (string) $sFrom );
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

	public function addAttachment( $sName = 'attachment.txt', $sType = 'application/octet-stream', $sContent = '' ) {
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

		$this->addHeaders( array(
			'MIME-Version' => '1.0',
			'Content-Type' => $this->sContentType . '; boundary="' . $this->sBoundary . '"'
		) );

		foreach( $this->aHeaders as $key => $value ) {
			$sHeaders .= "{$key}: {$value}\n";
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
