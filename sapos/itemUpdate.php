<?php
//
// Description
// ===========
// This function will be a callback when an item is updated in ciniki.sapos.invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_itemUpdate($ciniki, $tnid, $invoice_id, $item) {

    error_log('iteUpdate');
    if( isset($item['object']) && $item['object'] == 'ciniki.musicfestivals.registration' 
        && isset($item['object_id']) && $item['object_id'] > 0 
        ) {
        //
        // Check if extra fee needs to be added
        //
        error_log('check fees');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'registrationExtraFeesCheck');
        $rc = ciniki_musicfestivals_sapos_registrationExtraFeesCheck($ciniki, $tnid, [
            'registration_id' => $item['object_id'],
            'invoice_id' => $invoice_id,
            'closed' => 'ignore',
            ]);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'updated' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
