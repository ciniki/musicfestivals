<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_sapos_cartItemEditURL($ciniki, $tnid, $invoice_id, $args) {

    if( isset($args['object']) && $args['object'] == 'ciniki.musicfestivals.registration' && isset($args['object_id']) ) {
        //
        // Load the registration
        //
        $strsql = "SELECT id, uuid "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.333', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( isset($rc['registration']['uuid']) ) {
            return array('stat'=>'ok', 'url'=>'/account/musicfestivalregistrations?r=' . $rc['registration']['uuid']);
        }
    }

    return array('stat'=>'ok');
}
?>
