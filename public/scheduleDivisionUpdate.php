<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleDivisionUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scheduledivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Division'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'adjudicator_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicator'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'shortname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Name'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'division_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'),
        'address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address'),
        'results_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Results Notes'),
        'results_video_url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Results Video URL'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisionUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current division
    //
    $strsql = "SELECT divisions.id, "
        . "divisions.name, "
        . "divisions.festival_id "
        . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
        . "WHERE divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'division');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1028', 'msg'=>'Unable to load division', 'err'=>$rc['err']));
    }
    if( !isset($rc['division']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1029', 'msg'=>'Unable to find requested division'));
    }
    $division = $rc['division'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $division['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Create the shortname
    //
    if( isset($args['name']) && $args['name'] != $division['name'] && !isset($args['shortname']) 
        && isset($festival['scheduling-division-shortname'])
        && $festival['scheduling-division-shortname'] != 'no'
        && $festival['scheduling-division-shortname'] != 'manual'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'shortnameMake');
        $rc = ciniki_musicfestivals_shortnameMake($ciniki, $args['tnid'], [
            'type' => 'division',
            'format' => $festival['scheduling-division-shortname'],
            'text' => $args['name'],
            ]);
        if( isset($rc['shortname']) ) {
            $args['shortname'] = $rc['shortname'];
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Schedule Division in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduledivision', $args['scheduledivision_id'], $args, 0x04);
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.scheduleDivision', 'object_id'=>$args['scheduledivision_id']));

    return array('stat'=>'ok');
}
?>
