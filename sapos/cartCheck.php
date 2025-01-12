<?php
//
// Description
// ===========
// This function will check the musicfestival cart items to check if late fees or admin fees should be applied.
// The entire cart is checked in one pass to be more efficient than checking each item individually.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_cartCheck($ciniki, $tnid, $args) {

    if( !isset($args['cart']['id']) || $args['cart']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.907', 'msg'=>'No cart specified'));
    }

    //
    // Run the check on the entire invoice, for both closed festival and late/admin fees
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
    return ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
        'invoice_id' => $args['cart']['id'],
        ]);
}
?>
