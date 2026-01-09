<?php
//
// Description
// -----------
// This method will add a new shift for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Shift to.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerShiftAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'shift_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'),
        'start_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'Start'),
        'end_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'End'),
        'location'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Location'),
        'role'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Role'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'min_volunteers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Volunteers'),
        'max_volunteers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum Volunteers'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerShiftAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Parse location into object:object_id
    //
    if( isset($args['location']) && preg_match("/^(.*):(.*)$/", $args['location'], $m) ) {
        $args['object'] = $m[1];
        $args['object_id'] = $m[2];
    } else {
        $args['object'] = '';
        $args['object_id'] = 0;
    }

    //
    // Check for volunteers added to shift
    //
    $assigned_ids = [];
    $assigned_id_status = [];
    foreach($ciniki['request']['args'] as $k => $v) {
        if( preg_match("/volunteer_([0-9]+)_status$/", $k, $m) ) {
            $assigned_id_status[$m[1]] = $v;
        }
        elseif( preg_match("/volunteer_([0-9]+)$/", $k, $m) ) {
            $assigned_ids[$m[1]] = $v;
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
    // Add the shift to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteershift', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }
    $shift_id = $rc['id'];

    //
    // Add the assigned volunteers
    //
    $email_shift_assigned_ids = [];  // The list of assignment ids that need an shift-assigned email
    foreach($assigned_ids as $volunteer_id) {
        if( $volunteer_id == 0 ) {
            continue;
        }
        $status_key = array_search($volunteer_id, $assigned_ids);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerassignment', [
            'festival_id' => $shift['festival_id'],
            'shift_id' => $args['shift_id'],
            'volunteer_id' => $volunteer_id,
            'status' => $status_key !== false ? $assigned_id_status[$status_key] : 30,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1251', 'msg'=>'Unable to add the volunteer', 'err'=>$rc['err']));
        }
        $assignment_id = $rc['id'];
        if( !isset($assigned_id_status[$status_key]) || $assigned_id_status[$status_key] == 30 ) {
            $email_shift_assigned_ids[] = $rc['id'];
        }

        //
        // Update the email queue for the volunteer
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerNotificationsUpdate');
        $rc = ciniki_musicfestivals_volunteerNotificationsUpdate($ciniki, $args['tnid'], [
            'volunteer_id' => $volunteer_id,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1302', 'msg'=>'Unable to update the notification queue', 'err'=>$rc['err']));
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.musicfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Once all changes commited, send out the emails
    //
    foreach($email_shift_assigned_ids as $assignment_id) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
        $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $args['tnid'], [
            'assignment_id' => $assignment_id,
            'template' => 'volunteers-email-shift-assigned',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1303', 'msg'=>'Unable to email volunteer', 'err'=>$rc['err']));
        }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.volunteerShift', 'object_id'=>$shift_id));

    return array('stat'=>'ok', 'id'=>$shift_id);
}
?>
