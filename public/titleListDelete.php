<?php
//
// Description
// -----------
// This method will delete an approved title list.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the approved title list is attached to.
// list_id:            The ID of the approved title list to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_titleListDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Approved Title List'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleListDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the approved title list
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestivals_titlelists "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'titlelist');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['titlelist']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1146', 'msg'=>'Approved Title List does not exist.'));
    }
    $titlelist = $rc['titlelist'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.titleList', $args['list_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1147', 'msg'=>'Unable to check if the approved title list is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1148', 'msg'=>'The approved title list is still in use. ' . $rc['msg']));
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
    // Remove the titles
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestivals_titles "
        . "WHERE list_id = '" . ciniki_core_dbQuote($ciniki, $args['list_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'list');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1152', 'msg'=>'Unable to load list', 'err'=>$rc['err']));
    }
    $titles = isset($rc['rows']) ? $rc['rows'] : array();
   
    foreach($titles as $title) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.title', $title['id'], $title['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Remove the titlelist
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.titlelist', $args['list_id'], $titlelist['uuid'], 0x04);
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
