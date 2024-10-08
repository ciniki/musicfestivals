<?php
//
// Description
// ===========
// This function will mark the registration as paid when the payment is received online.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_cartItemETransferCheckout($ciniki, $tnid, $request, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.334', 'msg'=>'No registration specified.'));
    }
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.335', 'msg'=>'No invoice specified.'));
    }

    if( $args['object'] == 'ciniki.musicfestivals.registration' ) {
        //
        // Get the current details for the registration
        //
        $strsql = "SELECT status, invoice_id "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.336', 'msg'=>'Unable to find registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.337', 'msg'=>'Unable to find registration'));
        }
        $item = $rc['item'];

        //
        // Change the status to paid
        //
        $update_args = array();
        if( $item['invoice_id'] != $args['invoice_id'] ) {
            $update_args['invoice_id'] = $args['invoice_id'];
        }
        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['object_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                error_log("ERR: Unable to process e-transfer checkout for registration: {$item['id']}");
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
