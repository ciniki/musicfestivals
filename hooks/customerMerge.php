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
function ciniki_musicfestivals_hooks_customerMerge($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list of adjudicators to update
    //
    $strsql = "SELECT id, customer_id "
        . "FROM ciniki_musicfestival_adjudicators "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.155', 'msg'=>'Unable to find adjudicators', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.adjudicator', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.156', 'msg'=>'Unable to find adjudicators.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of competitors to update
    //
    $strsql = "SELECT id, billing_customer_id "
        . "FROM ciniki_musicfestival_competitors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.157', 'msg'=>'Unable to find competitors', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.competitor', $row['id'], array(
            'billing_customer_id'=>$args['primary_customer_id'],
            'parent'=>$args['primary_display_name'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.158', 'msg'=>'Unable to find competitors.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of customers to update
    //
    $strsql = "SELECT id, customer_id "
        . "FROM ciniki_musicfestival_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.159', 'msg'=>'Unable to find music customers', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.customer', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.160', 'msg'=>'Unable to find music customers.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of registrations to update
    //
    $strsql = "SELECT id, teacher_customer_id, billing_customer_id, accompanist_customer_id "
        . "FROM ciniki_musicfestival_registrations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "teacher_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "OR billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "OR accompanist_customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . ") "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.161', 'msg'=>'Unable to find music registrations', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $update_args = array();
        if( $row['teacher_customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['teacher_customer_id'] = $args['primary_customer_id'];
        }
        if( $row['billing_customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['billing_customer_id'] = $args['primary_customer_id'];
        }
        if( $row['accompanist_customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['accompanist_customer_id'] = $args['primary_customer_id'];
        }
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $row['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.162', 'msg'=>'Unable to find music registrations.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of member admins to update
    //
    $strsql = "SELECT id, customer_id "
        . "FROM ciniki_musicfestivals_members "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.667', 'msg'=>'Unable to find adjudicators', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.member', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.668', 'msg'=>'Unable to find adjudicators.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    if( $updated > 0 ) {
        //
        // Update the last_change date in the tenant modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'musicfestivals');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
