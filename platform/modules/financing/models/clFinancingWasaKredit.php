<?php

require_once PATH_MODULE . '/financing/config/cfFinancingWasaKredit.php';
require_once PATH_MODULE . '/financing/models/clFinancing.php';

// API code and namespace
require_once PATH_MODULE . '/financing/api/php-checkout-sdk-master/Wasa.php';
use Sdk as WasaKredit;

class clFinancingWasaKredit extends clFinancing {
  private $oClient;

	public function __construct() {
    parent::__construct();
		$this->initBase();
 	}

  public function connect() {
    $this->oClient = $this->apiClient( FINANCING_WASAKREDIT_ID, FINANCING_WASAKREDIT_SECRET, FINANCING_WASAKREDIT_TESTMODE );
  }

  public function createCheckout( $aData ) {
    $aCheckoutData = array();

    // Create financing object
    // if( empty($this->iFinancingId) ) $this->iFinancingId = $this->createFinancing( array(
    //   'financingService' => 'wasakredit'
    // ) );

    $iUserId = null;
    if( !empty($aData['user']) ) {
      $aCheckoutData += array(
        'customer_organization_number' => $aData['user']['userPin'],
        'purchaser_name' => $aData['user']['infoName'],
        'purchaser_email' => $aData['user']['userEmail'],
        'purchaser_phone' => ( !empty($aData['user']['infoCellPhone']) ? $aData['user']['infoCellPhone'] : $aData['user']['infoPhone'] ),
        'billing_address' => array(
          'company_name' => $aData['user']['infoName'],
          'street_address' => $aData['user']['infoAddress'],
          'postal_code' => $aData['user']['infoZipCode'],
          'city' => $aData['user']['infoCity'],
          'country' => 'Sweden'
        ),
        'recipient_name' => $aData['user']['infoName'],
        'recipient_phone' => ( !empty($aData['user']['infoCellPhone']) ? $aData['user']['infoCellPhone'] : $aData['user']['infoPhone'] ),
      );
      $iUserId = $aData['user']['userId'];

      // User id stored in local references
      $aCheckoutData['order_references'] = array(
        array(
          'key' => 'tovek_user_id',
          'value' => $iUserId
        ), array(
          'key' => 'tove_ref_string',
          'value' => $aData['user']['userCustomerNo'] . ( !empty($aData['items']) ? '-' . implode('|', arrayToSingle($aData['items'], null, 'itemId')) : '' )
        )
      );
    }

    $aTotalValue = 0;
    if( !empty($aData['items']) ) {
      // An array with basically item data straight from database with theese addendums
      // itemFinancingAmount = what amount that customer selects

      $aCheckoutData['cart_items'] = array();

      foreach( $aData['items'] as $aItem ) {
        $aCheckoutData['cart_items'][] = array(
          'product_id' => $aItem['itemId'],
          'product_name' => _( 'Rop' ) . ' ' . $aItem['itemSortNo'] . ': ' . $aItem['itemTitle'],
          'price_ex_vat' => array(
            'amount' => $aItem['itemFinancingAmount'],
            'currency' => 'SEK'
          ),
          'quantity' => 1,
          'vat_percentage' => $aItem['itemVatValue'],
          'vat_amount' => array(
            'amount' => round( ($aItem['itemFinancingAmount'] / 100 * $aItem['itemVatValue']), 2 ),
            'currency' => 'SEK'
          ),
        );
      }

      // $this->createFinancingToItem( $this->iFinancingId, $aItem['itemId'], $aItem['itemFinancingAmount'], $iUserId );
      $aTotalValue += $aItem['itemFinancingAmount'];
    }

    // Update financing object
    // $this->updateFinancing( $this->iFinancingId, array(
    //   'financingUserId' => $iUserId,
    //   'financingTotalValue' => $aTotalValue
    // ) );

    // Shipping cost is mandatory
    $aCheckoutData += array(
      'shipping_cost_ex_vat' => array(
        'amount' => 0,
        'currency' => 'SEK'
      ),
    );

    // Dump data
    // echo '<pre>';
    // print_r( $aCheckoutData );
    // echo '</pre>';

    return $this->apiCreateCheckout( $aCheckoutData );
  }

  public function initFinancing( $aData ) {
    $aData += array(
      'financingService' => FINANCING_WASAKREDIT_SERVICE
    );

    $this->iFinancingId = $this->createFinancing( $aData );

    return $this->iFinancingId;
  }

  //
  // API functions
  //
  public function apiClient( $sId, $sSecret, $bTestMode ) {
    return new WasaKredit\Client( $sId, $sSecret, $bTestMode );
  }

  public function apiCalculateMonthlyCosts( $aData = array() ) {}

  public function apiCreateCheckout( $aData = array() ) {
    if( empty($this->oClient) ) $this->connect();

    $aData += array(
      'payment_types' => 'leasing',
      'request_domain' => FINANCING_WASAKREDIT_OURDOMAIN,
      'confirmation_callback_url' => FINANCING_WASAKREDIT_URL_CALLBACK,
      'ping_url' => FINANCING_WASAKREDIT_URL_PING
    );

    // Create service record
    $iRequestId = $this->createServiceRequest( array(
      'requestFunction' => 'create_checkout',
      'requestQuery' => json_encode( $aData )
    ) );

    // Make the request
    $oApiResponse = $this->oClient->create_checkout( $aData );

    // Update service record and analyse result for error and such
    // Functions is located in the sdk/Response.php code
    $this->updateServiceRequest( $iRequestId, array(
      'requestResponse' => json_encode( $oApiResponse->data ),
      'requestResponseCode' => $oApiResponse->statusCode
    ) );

    return ( isset($oApiResponse->error) ? false : $oApiResponse->data );
  }

  public function apiValidateFinancedAmount( $aData = array() ) {}

  public function apiGetMonthlyCostWidget( $fAmount ) {
    if( empty($this->oClient) ) $this->connect();

    // This service is not logged in database since it is made so often.

    $oApiResponse = $this->oClient->get_monthly_cost_widget( $fAmount );

    // Analyse result for error and such
    // Functions is located in the sdk/Response.php code

    return $oApiResponse->data;
  }

  public function apiGetOrder( $sOrderId ) {
    if( empty($this->oClient) ) $this->connect();

    // Create service record
    $iRequestId = $this->createServiceRequest( array(
      'requestFunction' => 'get_order',
      'requestQuery' => $sOrderId
    ) );

    $oApiResponse = $this->oClient->get_order( $sOrderId );

    // Analyse result for error and such
    // Functions is located in the sdk/Response.php code
    $this->updateServiceRequest( $iRequestId, array(
      'requestResponse' => json_encode( $oApiResponse->data ),
      'requestResponseCode' => $oApiResponse->statusCode
    ) );

    return $oApiResponse->data;
  }

  public function apiGetOrderStatus( $iOrderId, $aData = array() ) {}
  public function apiUpdateOrderStatus( $iOrderId, $aData = array() ) {}

  public function apiAddOrderReference( $iOrderId, $aData = array() ) {
    if( empty($this->oClient) ) $this->connect();

    // Create service record
    $iRequestId = $this->createServiceRequest( array(
      'requestFunction' => 'add_order_reference',
      'requestQuery' => json_encode( array(
        'orderId' => $iOrderId,
        'data' => $aData
      ) )
    ) );

    $oApiResponse = $this->oClient->add_order_reference( $sOrderId, $aData );

    // Analyse result for error and such
    // Functions is located in the sdk/Response.php code
    $this->updateServiceRequest( $iRequestId, array(
      'requestResponse' => json_encode( $oApiResponse->data ),
      'requestResponseCode' => $oApiResponse->statusCode
    ) );

    return $oApiResponse->data;
  }

  public function apiGetPaymentMethods( $aData = array() ) {}
}
