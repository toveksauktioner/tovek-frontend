<?php

/**
 *
 * variables.js.php
 * 
 */
 
require_once realpath( dirname(dirname(dirname(dirname(dirname(__FILE__))))) ) . '/platform/config/cfBase.php';

echo "
var sPushProtocol = '" . SERVICE_DEFAULT_PROTOCOL . "';
var sPushDomain = '" . SERVICE_DOMAIN . "';
var sPushPort = " . SERVICE_DEFAULT_PORT . ";
var sWebProtocol = '" . SITE_DEFAULT_PROTOCOL . "';
var sWebDomain = '" . SITE_DEFAULT_DOMAIN . "';";
