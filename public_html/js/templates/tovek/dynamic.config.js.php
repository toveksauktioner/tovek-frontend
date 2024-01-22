<?php

require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/platform/core/bootstrap.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/platform/modules/auction/config/cfAuction.php';

?>

var bDebug = <?php echo ($GLOBALS['debug'] ? 'true' : 'false'); ?>;
var sPushProtocol = '<?php echo SERVICE_PROTOCOL; ?>';
var sPushDomain = "<?php echo SERVICE_DOMAIN; ?>";
var sPushPort = <?php echo SERVICE_PORT; ?>;
var sWebProtocol = "<?php echo (SITE_PROTOCOL == 'http' ? 'http' : 'https'); ?>";
var sWebDomain = "<?php echo SITE_DOMAIN; ?>";
var sHost = sWebProtocol + '://' + sWebDomain;

// Use Geo IP lookup from https://json.geoiplookup.io
var bGeoiplookup = false;

var aAuctionBidTariff = <?php echo json_encode( AUCTION_BID_TARIFF ); ?>;

var ajaxGlobalUrl = '/ajax/global';
