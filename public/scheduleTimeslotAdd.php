<?php
//
// Description
// -----------
// This method will add a new schedule time slot for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Schedule Time Slot to.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
        'sdivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Division'),
        'slot_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'Time'),
        'slot_seconds'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Length'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'shortname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Name'),
        'groupname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Group Name'),
        'start_num'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Starting Number'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'runsheet_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Runsheet Notes'),
        'results_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Results Notes'),
        'results_video_url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Results Video URL'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the schedule time slot to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $scheduletimeslot_id = $rc['id'];

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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.scheduleTimeslot', 'object_id'=>$scheduletimeslot_id));

    return array('stat'=>'ok', 'id'=>$scheduletimeslot_id);
}
?>
