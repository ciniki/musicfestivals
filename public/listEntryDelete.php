<?php
//
// Description
// -----------
// This method will delete an list entry.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the list entry is attached to.
// listentry_id:            The ID of the list entry to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_listEntryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'listentry_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'List Entry'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listEntryDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the list entry
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_list_entries "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['listentry_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'listentry');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['listentry']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.273', 'msg'=>'List Entry does not exist.'));
    }
    $listentry = $rc['listentry'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.listentry', $args['listentry_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.274', 'msg'=>'Unable to check if the list entry is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.275', 'msg'=>'The list entry is still in use. ' . $rc['msg']));
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
    // Remove the listentry
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.listentry',
        $args['listentry_id'], $listentry['uuid'], 0x04);
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
