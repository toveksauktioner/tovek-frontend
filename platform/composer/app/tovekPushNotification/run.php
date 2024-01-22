<?php

/**
 * Service is run by systemctl so should be restarted when server is restarted
 *
 * Check status: systemctl --user status websocket.service
 * Start: systemctl --user start websocket.service
 *
 * Read more: https://glesys.se/kb/artikel/anvandarmanual-systemforvaltning-linux
 * Systemd: https://wiki.archlinux.org/title/Systemd#Writing_unit_files
 *
 * Old way to start:
 * To run once: php -f /home/httpd/tovek/tovek.se/platform/composer/app/tovekPushNotification/run.php
 * To run infinite: nohup php -f /home/httpd/tovek/tovek.se/platform/composer/app/tovekPushNotification/run.php >&/dev/null &
 *
 * To stop:
 * 1) ps aux | grep php
 * 2) kill %pid%
 */

// Libraries
use Ratchet\Http\OriginCheck;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use tovekPushNotification\clNotifier;

// Composer config
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/cfComposer.php';

// Autoloader
require_once PATH_COMPOSER_VENDOR . '/autoload.php';

// App config
require_once PATH_COMPOSER_APP . '/tovekPushNotification/cfConfig.php';

if( strlen(WS_ORIGIN_HOST) > 0 ) {
	// Origin host is defined
	$oOriginCheck = new OriginCheck( new WsServer( new clNotifier() ), array('localhost') );
    $oOriginCheck->allowedOrigins[] = WS_ORIGIN_HOST;
	$oServerApp = $oOriginCheck;
} else {
	// Just localhost
	$oServerApp = new WsServer( new clNotifier() );
}

// Server object
$oServer = IoServer::factory(
	new HttpServer(
        $oServerApp
	),
	WS_PORT
);

// Run server
$oServer->run();
