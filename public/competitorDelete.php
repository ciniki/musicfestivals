<?php
//
// Description
// -----------
// This method will delete an competitor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the competitor is attached to.
// competitor_id:            The ID of the competitor to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_competitorDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Competitor'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.competitorDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the competitor
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_competitors "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'competitor');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['competitor']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.65', 'msg'=>'Competitor does not exist.'));
    }
    $competitor = $rc['competitor'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT COUNT(id) AS num_registrations "
        . "FROM ciniki_musicfestivals_registrations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "(ciniki_musicfestivals_registrations.competitor1_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "OR ciniki_musicfestivals_registrations.competitor2_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "OR ciniki_musicfestivals_registrations.competitor3_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "OR ciniki_musicfestivals_registrations.competitor4_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "OR ciniki_musicfestivals_registrations.competitor5_id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.75', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' registration' . ($count==1?'':'s') . ' for that competitor.'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['business_id'], 'ciniki.musicfestivals.competitor', $args['competitor_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.66', 'msg'=>'Unable to check if the competitor is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.67', 'msg'=>'The competitor is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the competitor
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.musicfestivals.competitor',
        $args['competitor_id'], $competitor['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
