<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_hooks_checkObjectUsed($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    if( $args['object'] == 'ciniki.customers.customer' ) {
        //
        // Check the invoice customers
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_musicfestival_adjudicators "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count music festival adjudicator" . ($count==1?'':'s') . " for this customer.";
        }
        //
        // Check the invoice customers
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_musicfestival_registrations "
            . "WHERE ("
                . "billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "OR teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "OR teacher2_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "OR parent_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "OR accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count music festival registration" . ($count==1?'':'s') . " for this customer.";
        }
    }

    return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
