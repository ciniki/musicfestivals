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
function ciniki_musicfestivals_sapos_cartItemDelete($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.131', 'msg'=>'No registration specified', 'err'=>$rc['err']));
    }

    //
    // Check to make sure the registration exists
    //
    if( $args['object'] == 'ciniki.musicfestivals.registration' ) {
        //
        // Get the current details for the registration
        //
        $strsql = "SELECT id, uuid, status "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.135', 'msg'=>'Unable to find registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.136', 'msg'=>'Unable to find registration'));
        }
        $item = $rc['item'];

        if( $item['status'] != 5 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.137', 'msg'=>'This registration cannot be removed.'));
        }

        //
        // Delete the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationDelete');
        $rc = ciniki_musicfestivals_registrationDelete($ciniki, $tnid, $item['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.138', 'msg'=>'Error trying to remove registration.', 'err'=>$rc['err']));
        }

        //
        // Update Extra Fees
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'sapos', 'invoiceExtraFeesCheck');
        $rc = ciniki_musicfestivals_sapos_invoiceExtraFeesCheck($ciniki, $tnid, [
            'invoice_id' => $invoice_id,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
