<?php

/* * * *
 * Filename: invoiceShow.php
 * Created: 24/04/2014 by Renfors
 * Reference:
 * Description: View file for show invoice. Invoice ID must be sent to the script. An option is to present as PDF.
 * * * */

if( !isset($_GET['invoiceId']) ) return;


// Get invoice data
$oInvoice = clRegistry::get( 'clInvoice', PATH_MODULE . '/invoice/models' );
$aInvoiceData = current( $oInvoice->read(null, $_GET['invoiceId']) );

// Allow administrators and the user who ownes the invoice to view it
if( array_key_exists('superNova', $oUser->aGroups) ||
    array_key_exists('admin', $oUser->aGroups) ||
   (array_key_exists('user', $oUser->aGroups) && ($aInvoiceData['invoiceUserId'] == $oUser->iId))
) {
    $aInvoiceParams = array();

    // Special params for invoice
    if( isset($_GET['stamp']) ) $aInvoiceParams += array( 'stamp' => $_GET['stamp'] );

    if( isset($_GET['pdf']) && ($_GET['pdf'] == 1) ) {
        // Generate PDF output

        $aInvoiceParams['pdf'] = true;

        clFactory::loadClassFile( 'clTemplateHtml' );
        $oInvoiceTemplate = new clTemplateHtml();
        $oInvoiceTemplate->setTemplate( 'pdfInvoice.php' );
        $oInvoiceTemplate->setTitle( _( 'Invoice' ) . ' ' . $aInvoiceData['invoiceNo'] );
        $oInvoiceTemplate->setContent( $oInvoice->generateInvoiceHtml($_GET['invoiceId'], true, $aInvoiceParams) );


        $oMPdf = clRegistry::get( 'clMPdf', PATH_CORE . '/mPdf' );
				$oMPdf->setHtmlFooter( $oInvoice->getInvoiceFooter(), _( 'Invoice' ) . '-' . $aInvoiceData['invoiceNo'] );
				$oMPdf->setHtmlHeader( $oInvoice->getInvoiceHeader($aInvoiceParams, $_GET['invoiceId']) );
        $oMPdf->loadHtml( $oInvoiceTemplate->render() );

        $sFileName = _( 'Invoice' ) . '-' . $aInvoiceData['invoiceNo'] . '-' . date( 'YmdHis' ) . '.pdf';
        $oMPdf->output( $sFileName, 'I' ); // I = sent to browser
        exit;
    } else {
        // Generate normal output

        echo '
            <div class="invoice show view adminControlsContent">
            ' . $oInvoice->getInvoiceHeader( [], $_GET['invoiceId'] ) . '
            ' . $oInvoice->generateInvoiceHtml( $_GET['invoiceId'], false, $aInvoiceParams ) . '
            </div>';
    }
}
