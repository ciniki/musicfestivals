<?php
//
// Description
// -----------
// This method will delete an list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the list is attached to.
// list_id:            The ID of the list to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_listDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'List'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the list
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_lists "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'list');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['list']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.262', 'msg'=>'List does not exist.'));
    }
    $list = $rc['list'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_musicfestival_list_sections "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND list_id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.musicfestivals', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.267', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' section' . ($count==1?'':'s') . ' in that list.'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.list', $args['list_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.263', 'msg'=>'Unable to check if the list is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.264', 'msg'=>'The list is still in use. ' . $rc['msg']));
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
    // Remove the list
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.list',
        $args['list_id'], $list['uuid'], 0x04);
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
