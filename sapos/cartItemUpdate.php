<?php
//
// Description
// ===========
// This function 
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_cartItemUpdate($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.869', 'msg'=>'No registration specified', 'err'=>$rc['err']));
    }

    //
    // Check to make sure the registration exists
    //
    if( $args['object'] == 'ciniki.musicfestivals.registration' ) {
        //
        // Update Extra Fees
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'registrationExtraFeesCheck');
        $rc = ciniki_musicfestivals_sapos_registrationExtraFeesCheck($ciniki, $tnid, [
            'invoice_id' => $invoice_id,
            'registration_id' => $args['object_id'],
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
