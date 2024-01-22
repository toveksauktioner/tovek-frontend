<?php

/**
 * $Id: index.php 1398 2014-03-31 14:41:09Z mikael $
 * This file is a part of argoPlatform, a PHP framework & CMS by Argonova Systems
 *
 * @package		argoPlatform
 * @category	Framework
 * @link 		http://www.argonova.se
 * @author		$Author: mikael $
 * @version		Subversion: $Revision: 1398 $, $Date: 2014-03-31 16:41:09 +0200 (må, 31 mar 2014) $
 */

class clMailHandler {
	
	/**
	 * Independent mail object
	 */
	private $oMail;
	
	private $sMailService;
	private $bDebug = false;
	private $bPrepared = false;
	private $aParams = array();
	private $aHeaders = array();
	
	public function __construct( $aOverrideParams = array() ) {
		/**
		 * Config values
		 */
		$oConfig = clFactory::create( 'clConfig' );
		$aConfigs = arrayToSingle( $oConfig->oDao->readData( array(
			'fields' => '*',
			'criterias' => 'configGroupKey = ' . $oConfig->oDao->oDb->escapeStr( 'Mail' )
		) ), 'configKey', 'configValue' );
		
		// If so, override config values with given once 
		$aParams = array_merge( $aConfigs, $aOverrideParams );
		
		// Set params
		$this->setParams( $aParams );
		
		// Debug?
		if( !empty($this->aParams['debug']) ) {
			$this->bDebug = $this->aParams['debug'];
		}
		
		// Determ mail service to use
		$this->setMailService();
		
		// Start
		$this->init();
	}
	
	/**	
	 * Set params and add SMTP params if needed
	 */	
	public function setParams( $aParams ) {		
		$this->aParams = $aParams;		
	}
	
	/**	
	 * Set mail service to use
	 */	
	public function setMailService() {
		$this->sMailService = !empty($this->aParams['mailService']) ? $this->aParams['mailService'] : SITE_MAIL_SERVICE;
	}
	
	/**
	 * This function determ which service to use
	 */
	public function init() {	
		switch( $this->sMailService ) {
			case 'default':
				require_once PATH_CORE . '/clMail.php';
				$this->oMail = clRegistry::get( 'clMail' );
				break;
			case 'smtp':
				require_once PATH_CORE . '/clMailSmtp.php';
				$this->oMail = clRegistry::get( 'clMailSmtp', null, array(
					'server' => $this->aParams['smtpHost'],
					'username' => $this->aParams['smtpUsername'],
					'password' => $this->aParams['smtpPassword']
				) );
				break;
			case 'phpMailer':
				require_once PATH_CORE . '/phpMailer/clPhpMailer.php';
				$this->oMail = new clPhpMailer( array(
					'smtpHost' => $this->aParams['smtpHost'],
					'smtpUsername' => $this->aParams['smtpUsername'],
					'smtpPassword' => $this->aParams['smtpPassword'],
					'debug' => $this->bDebug
				) );
		}
	}
	
	/**
	 * Shortcut function for setting mail headers
	 */
	public function setHeaders( $aHeaders ) {
		$this->aHeaders = $aHeaders;
	}
	
	/**
	 * Prepare mail to send
	 * 
	 * aParams = array(
	 *	  'from' => '',
	 *	  'to' => '',
	 *	  'returnPath' => '',
	 *	  'replyTo' => '',
	 *	  'title' => '',
	 *	  'content' => '',
	 *	  'attachments' => array(
	 *		 0 => array(
	 *			'name' => '',
	 *			'path' => '',
	 *			'content' => '',
	 *			'type' => '' (default to 'application/octet-stream')
	 *		 )
	 *	  ),
	 *	  'template' => ''
	 * );
	 *
	 * @return boolen
	 */
	public function prepare( $aParams = array() ) {		
		$aParams += array(
			'from' => SITE_MAIL_FROM,
			'to' => SITE_MAIL_TO,
			'returnPath' => '',
			'replyTo' => '',
			'attachments' => array(),
			'template' => ''
		);		
		
		// Check if title exists
		if( empty($aParams['title']) ) {
			if( $this->bDebug === true ) throw new Exception( 'Missing title' );
			else return false;
		}
		
		// Check if content exists
		if( empty($aParams['content']) ) {
			if( $this->bDebug === true ) throw new Exception( 'Missing content' );
			else return false;
		}
		
		// Existing headers
		$aHeaders = !empty($this->aHeaders) ? $this->aHeaders : array();
		
		// Set sender
		preg_match( '!(.*?)\s+<\s*(.*?)\s*>!', $aParams['from'], $aMatches );
		if( !empty($aMatches[1]) && !empty($aMatches[2]) ) {
			$this->oMail->setFrom( $aMatches[2], $aMatches[1] );
		} else {
			$this->oMail->setFrom( $aParams['from'] );
		}		
		
		// Set return path header
		if( !empty($aParams['returnPath']) ) {
			$this->oMail->setReturnPath( $aParams['returnPath'] );
		}
		
		// Set reply to header
		if( !empty($aParams['replyTo']) ) {
			preg_match( '!(.*?)\s+<\s*(.*?)\s*>!', $aParams['replyTo'], $aMatches );
			if( !empty($aMatches[1]) && !empty($aMatches[2]) ) {
				$this->oMail->setReplyTo( $aMatches[2], $aMatches[1] );
			} else {
				$this->oMail->setReplyTo( $aParams['replyTo'] );
			}
		}
		
		// Add recipients
		if( !is_array($aParams['to']) ) $aParams['to'] = (array) $aParams['to'];
		foreach( $aParams['to'] as $mAddress ) {
			preg_match( '!(.*?)\s+<\s*(.*?)\s*>!', $mAddress, $aMatches );
			if( !empty($aMatches[1]) && !empty($aMatches[2]) ) {
				$this->oMail->addTo( $aMatches[2], $aMatches[1] );
			} else {
				$this->oMail->addTo( $mAddress );
			}			
		}
		
		// CC recipients
		if( !empty($aParams['cc']) ) {
			// Add bcc recipients
			if( !is_array($aParams['cc']) ) $aParams['cc'] = (array) $aParams['cc'];
			foreach( $aParams['cc'] as $mAddress ) {
				preg_match( '!(.*?)\s+<\s*(.*?)\s*>!', $mAddress, $aMatches );
				if( !empty($aMatches[1]) && !empty($aMatches[2]) ) {
					$this->oMail->addCC( $aMatches[2], $aMatches[1] );
				} else {
					$this->oMail->addCC( $mAddress );
				}
			}
		}
		
		// BCC recipients
		if( !empty($aParams['bcc']) ) {
			// Add bcc recipients
			if( !is_array($aParams['bcc']) ) $aParams['bcc'] = (array) $aParams['bcc'];
			foreach( $aParams['bcc'] as $mAddress ) {
				preg_match( '!(.*?)\s+<\s*(.*?)\s*>!', $mAddress, $aMatches );
				if( !empty($aMatches[1]) && !empty($aMatches[2]) ) {
					$this->oMail->addBCC( $aMatches[2], $aMatches[1] );
				} else {
					$this->oMail->addBCC( $mAddress );
				}
			}
		}
		
		// Set subject
		$this->oMail->setSubject( $aParams['title'] );
		
		// Content with HTML & template
		$sHtmlContent = $this->createContent( $aParams['title'], $aParams['content'], $aParams['template'] );
		
		// Plain text content
		$sTextContent = strip_tags( preg_replace( "/<style\\b[^>]*>(.*?)<\\/style>/s", "", $sHtmlContent ) );
		
		// Set mail body
		$this->oMail->setBody( $sTextContent, $sHtmlContent );
		
		// Attachments
		if( !empty($aParams['attachments']) ) {			
			foreach( $aParams['attachments'] as $aAttachment ) {
				if( empty($aAttachment['name']) ) continue;
				$sType = !empty($aAttachment['type']) ? $aAttachment['type'] : 'application/octet-stream';
				
				$sAttachment = !empty($aAttachment['path']) ? $aAttachment['path'] : $aAttachment['content'];
				
				// Add attachment
				$this->oMail->addAttachment( $aAttachment['name'], $sType, $sAttachment );
			}
		}
		
		// Headers
		$this->oMail->addHeaders( $aHeaders );
		
		$this->bPrepared = true;		
		return $this->bPrepared;
	}
	
	/**
	 * Send out mail
	 */
	public function send() {
		// Do we have a mail to send out?
		if( !$this->bPrepared ) return false;
		
		// Custom CC of all mail to developer
		//$this->oMail->addCC( 'developer@domain.se' );
		
		// Send
		if( $this->bDebug === true ) echo '<div class="debug"><h2>Debug output:</h2>';
		$mResult = $this->oMail->send();
		if( $this->bDebug === true ) echo '</div>';
		
		// Unset mail modules from registry
		if( $this->sMailService == 'default' ) unset( clRegistry::$aEntries['clMail'] );
		if( $this->sMailService == 'smtp' ) unset( clRegistry::$aEntries['clMailSmtp'] );
		
		// Logging
		//clFactory::loadClassFile( 'clLogger' );
		//clLogger::log( $this->aPrepareParams, 'mailHandler.log' );
		//clLogger::log( 'Result: ' . ($mResult === true ? 'successful' : 'failed'), 'mailHandler.log' );
		//clLogger::log( '- - -', 'mailHandler.log' );
		//clLogger::logRotate( 'mailHandler.log', '6M' );
		
		return $mResult;
	}
	
	/**
	 * Expunge deleted mails
	 * (requires php-imap extension)
	 */
	public function expungeInbox( $aAccount = array(), $aParams = array() ) {
		if( !is_callable(imap_open) ) return false;
		
		/**
		 * Config settings
		 */
		$oConfig = clFactory::create( 'clConfig' );
		$aDefault = arrayToSingle( $oConfig->oDao->readData( array(
			'fields' => '*',
			'criterias' => 'configGroupKey = "Mail"'
		) ), 'configKey', 'configValue' );
		
		/**
		 * Default account
		 */
		$aAccount += array(
			'host' => $aDefault['imapHost'],
			'username' => $aDefault['smtpUsername'],
			'password' => $aDefault['smtpPassword']
		);
		
		/**
		 * Default settings
		 */
		$aParams += array(
			'deleteAll' => true
		);
		
		if( $rMailBox = imap_open( "{" . $aAccount['host'] . ":993/imap/ssl}INBOX", $aAccount['username'], $aAccount['password'] ) ) {
			$oBoxInfo = imap_mailboxmsginfo( $rMailBox );
			if( $oBoxInfo->Nmsgs >= 1 ) {
				$iTotal = $oBoxInfo->Nmsgs;
				
				for( $iNo = 1; $iNo <= $iTotal; $iNo++ ) {
					if( $aParams['deleteAll'] == true ) {
						// Mark mail: 'deleted'
						imap_delete( $rMailBox, $iNo );
					}
				}
				
				// Delete all mail marked 'deleted'
				imap_expunge( $rMailBox );
				
				// Re-check inbox
				$oBoxInfo = imap_mailboxmsginfo( $rMailBox );
				if( $oBoxInfo->Nmsgs == 0 ) {
					// Success
					imap_close( $rMailBox );
					return $iTotal;
				} else {
					// Error
					imap_close( $rMailBox );
					return false;	
				}
			}
			
			// Empty
			imap_close( $rMailBox );
			return 0;		
		}
		
		return false;		
	}
	
	/**
	 * This function creats html enriched content
	 */
	public function createContent( $sTitle, $sContent, $sTemplate ) {
		require_once PATH_CORE . '/clTemplateHtml.php';
		
		// Mail template
		if( empty($sTemplate) ) {
			// Default template
			$sTemplate = SITE_MAIL_TEMPLATE;
		}
		
		$oMailTemplate = new clTemplateHtml();
		$oMailTemplate->setTemplate( $sTemplate );
		$oMailTemplate->setTitle( $sTitle );
		$oMailTemplate->setContent( $sContent );
		
		return $oMailTemplate->render();
	}
	
}