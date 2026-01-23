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
function ciniki_musicfestivals_sapos_itemDelete($ciniki, $tnid, $invoice_id, $item) {

    //
    // An music festival was added to an invoice item, get the details and see if we need to 
    // create a registration for this music festival
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.musicfestivals.registration' && isset($item['object_id']) ) {
        //
        // Check the music festival registration exists
        //
        $strsql = "SELECT id, uuid, festival_id, invoice_id, billing_customer_id "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Remove the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationDelete');
        $rc = ciniki_musicfestivals__registrationDelete($ciniki, $tnid, $registration['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update Extra Fees
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
        $rc = ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
            'invoice_id' => $invoice_id,
            'ignore_registration_id' => $item['object_id'],
            ]);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'updated' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
