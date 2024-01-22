<?php

/* * * *
 * Filename: clInvoiceMaintenance.php
 * Created: 21/03/2014 by Mikael
 * Reference:
 * Description: This is a maintenance class for invoices. It is a copy/modification of clAuctionMaintenance
 *
 * Benchmarks & tests:
 * ------------------------------------------------------------------------------------
 * 
 * Free notes:
 * ------------------------------------------------------------------------------------
 * * * */

class clInvoiceMaintenance {
	
	public function __construct() {
		$this->sModuleName = 'InvoiceMaintenance';
	}
	
	public function maintenanceTest() {
		return 'success..';
	}
	
	/* * *
	 * General checking function
	 * * */
	public function maintenanceCheck() {
		return true;
	}
	
}