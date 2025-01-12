<?php
//
// Description
// ===========
// This function will lookup an invoice item and make sure it is still available for purchase.
// This function is called for any items previous to checkout.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_cartItemAdd($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.866', 'msg'=>'No registration specified.'));
    }

    //
    // Lookup the requested object (registration) and see if it is still available or if registrations have closed
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
        && $args['object'] == 'ciniki.musicfestivals.registration' 
        && isset($args['object_id']) && $args['object_id'] > 0 
        ) {
        // FIXME: change provincials member fees into registrationExtraFeesCheck
        return array('stat'=>'ok');
    }
    elseif( $args['object'] == 'ciniki.musicfestivals.registration' && isset($args['object_id']) && $args['object_id'] > 0 ) {

        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
        $rc = ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
            'invoice_id' => $invoice_id,
            ]);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'updated' ) {
            return $rc;
        }
        return array('stat'=>'ok');
    }
    
    return array('stat'=>'ok');
}
?>
