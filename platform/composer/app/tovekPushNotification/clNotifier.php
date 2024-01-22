<?php

namespace tovekPushNotification;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class clNotifier implements MessageComponentInterface {

    protected $oClients;

    public function __construct() {
        $this->oClients = new \SplObjectStorage;
    }

    public function onOpen( ConnectionInterface $oConnection ) {
        // Store new connection
        $this->oClients->attach( $oConnection );

        if( WS_OUTPUT ) {
			$aHeaders = $oConnection->httpRequest->getHeaders();
			$this->output( 'New connection (' . $oConnection->resourceId . '), from: ' . ( (!empty($aHeaders['X-Forwarded-For']) && is_array($aHeaders['X-Forwarded-For'])) ? current($aHeaders['X-Forwarded-For']) : 'Unknown' ) . ', on: ' . ( (!empty($aHeaders['User-Agent']) && is_array($aHeaders['User-Agent'])) ? current($aHeaders['User-Agent']) : 'Unknown' ) );
        }
    }

    public function onMessage( ConnectionInterface $oFrom, $sMessage ) {
        $oData = json_decode( $sMessage );
        if( $oData !== null && !empty($oData->type) ) {
            // Debug stuff
			if( WS_DEBUG ) {
				if( !empty($oData->message) ) $this->output( $oData->message );
				if( !empty($oData->data) ) $this->output( $oData->data );
			}

			if( $oData->type == 'newConnection' ) {
				// Prepare data to be sent to client
				$aResponseData = array(
					'type' => 'newConnection',
					'message' => $oData->message,
					'data' => $oData->data
				);
				// Send data
				$this->sendNotification( $oFrom, $aResponseData );
			}

			if( $oData->type == 'auctionBid' ) {
				// Prepare data to be sent to client
				$aResponseData = array(
					'type' => 'update',
					'message' => 'New bid accepted',
					'data' => $oData->data
				);
				// Send data
				$this->sendNotification( $oFrom, $aResponseData );
			}
        } else {
            // Debug stuff
			if( WS_DEBUG ) {
				$this->output( $oData->data );
			}

            // Prepare json data
			$aResponse = array(
				'type' => 'system',
				'message' => 'Did not receive any data',
				'data' => 'something'
			);
			$this->sendNotification( $oFrom, $aResponse );
        }
    }

    public function onClose( ConnectionInterface $oConnection ) {
        // The connection is closed, remove it,
        // as we can no longer send it messages
        $this->oClients->detach( $oConnection );

        $this->output( 'Connection (' . $oConnection->resourceId . ') has disconnected' );
    }

    public function onError( ConnectionInterface $oConnection, \Exception $exception ) {
        $this->output( 'An error has occurred: ' . $exception->getMessage() );
        $oConnection->close();
    }

    public function sendNotification( $oFrom, $aResponse ) {
        foreach( $this->oClients as $oClient ) {
            if( !WS_SELF_SENDING && $oFrom == $oClient ) continue;
            $oClient->send( json_encode($aResponse) );
        }
    }

    public static function output( $sMessage ) {
        if( WS_OUTPUT ) {
            echo $sMessage . "\n";
        }
    }

}
