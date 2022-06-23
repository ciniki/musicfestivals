<?php
//
// Description
// -----------
// This method will delete an list section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the list section is attached to.
// listsection_id:            The ID of the list section to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_listSectionDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'listsection_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'List Section'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.listSectionDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the list section
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_list_sections "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['listsection_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'listsection');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['listsection']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.268', 'msg'=>'List Section does not exist.'));
    }
    $listsection = $rc['listsection'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.listsection', $args['listsection_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.269', 'msg'=>'Unable to check if the list section is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.270', 'msg'=>'The list section is still in use. ' . $rc['msg']));
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
    // Remove the listsection
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.listsection',
        $args['listsection_id'], $listsection['uuid'], 0x04);
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
