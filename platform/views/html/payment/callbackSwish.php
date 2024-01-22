<?php

/**
 *
 * - - Swish API callback - -
 *
 */

/**
 * Respons data
 *
 * @var array Fetching Swish respons
 * 		errorCode 				# May contain an error code
 * 		errorMessage 			# May contain an error message
 * 		id 						# Transaction ID
 * 		payeePaymentReference 	# Given referens of created payment
 * 		paymentReference 		# Sort of "transaction number"
 * 		callbackUrl 			# {site}/callback/swish
 * 		payerAlias 				# Phone no to payer, ex. 467123345678
 * 		payeeAlias 				# Swish ID of payee
 * 		amount 					# Amount for crated payment
 * 		currency 				# Selected currency of crated payment
 * 		message 				# Entered message of crated payment
 * 		status 					# ex. PAID
 * 		dateCreated 			# ex. 2018-03-22T13:01:43.497Z
 * 		datePaid 				# ex. 2018-03-22T13:01:43.497Z
 */
$aRespons = (array) json_decode( file_get_contents("php://input") );

clFactory::loadClassFile( 'clLogger' );

if( !empty($aRespons) ) {
	if( SWISH_LOGGING === true ) {		
		clLogger::log( $aRespons, 'callbackSwish.log' );
	}
	
	$oSwish = clRegistry::get( 'clPaymentSwish', PATH_MODULE . '/payment/models' );
	
	if( !empty($aRespons['status']) && $aRespons['status'] == 'PAID' ) {
		/**
		 * Successful
		 */
		
		$aReference = explode( ':', $aRespons['payeePaymentReference'] );
		
		if(
			/**
			 * Order
			 */
			!empty($aReference[0]) && $aReference[0] == 'orderID' &&
			!empty($aReference[1]) && ctype_digit($aReference[1])
		) {
			// Order payment
			$oSwish->finalizeOrder( $aReference[1] );
			
		} else {
			// Log manual successful payment
			clLogger::log( $aRespons, 'swishSuccessful.log' );
			
		}
		
	} elseif( !empty($aRespons) ) {
		/**
		 * Unsuccessful, but with data
		 */
		
		$oSwish->logBadPayment( $aRespons );
		
	}
	
} else {
	// No respons data
	clLogger::log( 'Error: unkown error with no data', 'callbackSwish.log' );
	
}