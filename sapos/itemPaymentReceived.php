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
function ciniki_musicfestivals_sapos_itemPaymentReceived($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.338', 'msg'=>'No registration specified.'));
    }
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.339', 'msg'=>'No invoice specified.'));
    }

    if( $args['object'] == 'ciniki.musicfestivals.registration' ) {
        //
        // Get the current details for the registration
        //
        $strsql = "SELECT status "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.142', 'msg'=>'Unable to find registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.143', 'msg'=>'Unable to find registration'));
        }
        $item = $rc['item'];

        //
        // Change the status to paid
        //
        if( $item['status'] < 10 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['object_id'], array(
                'status' => 10, // Registered Status
                ));
            if( $rc['stat'] != 'ok' ) {
                error_log("ERR: Unable to process payment for registration: {$item['id']}");
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
