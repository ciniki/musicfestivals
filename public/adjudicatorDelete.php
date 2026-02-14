<?php
//
// Description
// -----------
// This method will delete an adjudicator.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the adjudicator is attached to.
// adjudicator_id:            The ID of the adjudicator to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_musicfestivals_adjudicatorDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'adjudicator_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Adjudicator'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicatorDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the adjudicator
    //
    $strsql = "SELECT id, uuid, festival_id "
        . "FROM ciniki_musicfestival_adjudicators "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['adjudicator']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.12', 'msg'=>'Adjudicator does not exist.'));
    }
    $adjudicator = $rc['adjudicator'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicator', $args['adjudicator_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.13', 'msg'=>'Unable to check if  is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.14', 'msg'=>'The Adjudicator is still in use. ' . $rc['msg']));
    }

    //
    // Add a check for any objects still referencing this adjudicator
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_musicfestival_adjudicatorrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $args['adjudicator_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1454', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' schedule section' . ($count==1?'':'s') . ' or division' . ($count==1?'':'s') . ' with this adjudicator.'));
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
    // Remove the adjudicator
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.adjudicator',
        $args['adjudicator_id'], $adjudicator['uuid'], 0x04);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    return array('stat'=>'ok');
}
?>
