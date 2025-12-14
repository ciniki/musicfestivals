<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_invoiceUpdate($ciniki, $tnid, $invoice_id, $item) {

    //
    // If an invoice was updated, check if we need to change any of the registrations
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.musicfestivals.registration' && isset($item['object_id']) ) {
        //
        // Check the offering registration exists
        //
        $strsql = "SELECT id, billing_customer_id "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1227', 'msg'=>'Unable to find registration'));
        }
        $registration = $rc['registration'];

        //
        // Pull the customer id from the invoice, see if it's different
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1228', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        
        //
        // If the customer is different, update the registration
        //
        if( $registration['billing_customer_id'] != $invoice['customer_id'] ) {
            //
            // NOTE: Don't change the student_id if originally the same as customer_id because the use 
            //       might be changing from a self pay registration, to a company pay.
            //
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $registration['id'], [
                'billing_customer_id' => $invoice['customer_id'],
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
