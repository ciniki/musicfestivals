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
function ciniki_musicfestivals_volunteerShiftUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'shift_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shift'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'shift_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'),
        'start_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Start'),
        'end_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'End'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location Type'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'location'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'role'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Role'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'min_volunteers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Volunteers'),
        'max_volunteers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum Volunteers'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerShiftUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the existing shift
    //
    $strsql = "SELECT shifts.id, "
        . "shifts.festival_id, "
        . "DATE_FORMAT(shifts.shift_date, '%a, %b %e, %Y') AS shift_date, "
        . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
        . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
        . "shifts.object, "
        . "shifts.object_id, "
        . "shifts.role "
        . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'shift');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1242', 'msg'=>'Unable to load shift', 'err'=>$rc['err']));
    }
    if( !isset($rc['shift']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1243', 'msg'=>'Unable to find requested shift'));
    }
    $shift = $rc['shift'];

    //
    // Parse location into object:object_id
    //
    if( isset($args['location']) && preg_match("/^(.*):(.*)$/", $args['location'], $m) ) {
        $args['object'] = $m[1];
        $args['object_id'] = $m[2];
    } elseif( isset($args['location']) && $args['location'] == 0 ) {
        $args['object'] = '';
        $args['object_id'] = 0;
    }

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $shift['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

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
    // Load the existing volunteers
    //
    $strsql = "SELECT assignments.id, "
        . "assignments.uuid, "
        . "assignments.volunteer_id, "
        . "assignments.status, "
        . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS name "
        . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
        . "INNER JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
            . "assignments.volunteer_id = volunteers.id "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "volunteers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE assignments.shift_id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
        . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'assignments', 'fname'=>'volunteer_id', 'fields'=>array('id', 'uuid', 'volunteer_id', 'status')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1312', 'msg'=>'Unable to load assignments', 'err'=>$rc['err']));
    }
    $assignments = isset($rc['assignments']) ? $rc['assignments'] : array();

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
    // Update the Shift in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteershift', $args['shift_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    //
    // Remove unassigned volunteers
    //
    foreach($assignments as $volunteer_id => $assignment) {
        if( !in_array($volunteer_id, $assigned_ids) ) {
            $assignment = $assignments[$volunteer_id];
            //
            // Delete any assignment notifications
            //
            $strsql = "SELECT notifications.id, "
                . "notifications.uuid "
                . "FROM ciniki_musicfestival_volunteer_notifications AS notifications "
                . "WHERE notifications.assignment_id = '" . ciniki_core_dbQuote($ciniki, $assignment['id']) . "' "
                . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'notification');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1474', 'msg'=>'Unable to load notification', 'err'=>$rc['err']));
            }
            $notifications = isset($rc['rows']) ? $rc['rows'] : array();
            foreach($notifications as $notification) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteernotification', $notification['id'], $notification['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1532', 'msg'=>'Unable to remove notification', 'err'=>$rc['err']));
                }
            }
            //
            // Check if they need cancelled email
            //
            if( $assignment['status'] == 30 ) {
                //
                // Email cancelled email
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
                $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $args['tnid'], [
                    'assignment_id' => $assignment['id'],
                    'template' => 'volunteers-email-shift-cancelled',
                    ]);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1530', 'msg'=>'Unable to email volunteer', 'err'=>$rc['err']));
                }
            }

            //
            // Delete the assignment
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerassignment', $assignment['id'], $assignment['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1252', 'msg'=>'Unable to remove volunteer', 'err'=>$rc['err']));
            }
            //
            // Update the email queue for the volunteer
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerNotificationsUpdate');
            $rc = ciniki_musicfestivals_volunteerNotificationsUpdate($ciniki, $args['tnid'], [
                'volunteer_id' => $volunteer_id,
                ]);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1531', 'msg'=>'Unable to update the notification queue', 'err'=>$rc['err']));
            }
        }
    }

    //
    // NOTE: The status for each volunteer is passed as the volunteer_id.
    // It is assigned into assigned_id_status as the index of the volunteer_id (NOT THE volunteer_id)
    //

    //
    // Add assigned volunteers
    //
    $email_shift_assigned_ids = [];  // The list of assignment ids that need an shift-assigned email
    $email_shift_changed_ids = [];  // The list of assignment ids that need an shift-changed email
    foreach($assigned_ids as $volunteer_id) {
        if( $volunteer_id == 0 ) {
            continue;
        }
        $assignment = $assignments[$volunteer_id];
        $status_key = array_search($volunteer_id, $assigned_ids);
        //
        // Check for new volunteer assignment
        //
        if( !isset($assignments[$volunteer_id]) ) {
            //
            // Check for volunteer scheduling conflict
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerConflictCheck');
            $rc = ciniki_musicfestivals_volunteerConflictCheck($ciniki, $args['tnid'], [
                'volunteer_id' => $volunteer_id,
                'shift_id' => $args['shift_id'],
                'festival' => $festival,
                ]);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerassignment', [
                'festival_id' => $shift['festival_id'],
                'shift_id' => $args['shift_id'],
                'volunteer_id' => $volunteer_id,
                'status' => $status_key !== false ? $assigned_id_status[$status_key] : 30,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1311', 'msg'=>'Unable to add the volunteer', 'err'=>$rc['err']));
            }
            $assignment_id = $rc['id'];
            if( !isset($assigned_id_status[$status_key]) || $assigned_id_status[$status_key] == 30 ) {
                $email_shift_assigned_ids[] = $rc['id'];
            }
        } 
        //
        // Check for existing assignmen changed to approved
        //
        elseif( $status_key !== false && $assignments[$volunteer_id]['status'] != $assigned_id_status[$status_key] ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerassignment', $assignments[$volunteer_id]['id'], ['status' => $assigned_id_status[$status_key]], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1261', 'msg'=>'Unable to change status', 'err'=>$rc['err']));
            }
            if( $assigned_id_status[$status_key] == 30 ) {
                $email_shift_assigned_ids[] = $assignments[$volunteer_id]['id'];
            }
        } 
        //
        // Existing assignments need update email
        //
        elseif( $assignment['status'] == 30 && (
            (isset($args['shift_date']) && $args['shift_date'] != $shift['shift_date'])
            || (isset($args['start_time']) && $args['start_time'] != $shift['start_time'])
            || (isset($args['end_time']) && $args['end_time'] != $shift['end_time'])
            || (isset($args['object']) && $args['object'] != $shift['object'])
            || (isset($args['object_id']) && $args['object_id'] != $shift['object_id'])
            || (isset($args['role']) && $args['role'] != $shift['role'])
            )) {
            $email_shift_changed_ids[] = $assignments[$volunteer_id]['id'];
        }
        //
        // Update the email queue for the volunteer
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerNotificationsUpdate');
        $rc = ciniki_musicfestivals_volunteerNotificationsUpdate($ciniki, $args['tnid'], [
            'volunteer_id' => $volunteer_id,
            ]);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1266', 'msg'=>'Unable to update the notification queue', 'err'=>$rc['err']));
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
    // Once all changes commited, send out the emails about shift changed
    //
    foreach($email_shift_changed_ids as $assignment_id) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
        $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $args['tnid'], [
            'assignment_id' => $assignment_id,
            'old_shift' => $shift,
            'template' => 'volunteers-email-shift-changed',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1527', 'msg'=>'Unable to email volunteer', 'err'=>$rc['err']));
        }
    }

    //
    // Emails to new assigments
    //
    foreach($email_shift_assigned_ids as $assignment_id) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerEmail');
        $rc = ciniki_musicfestivals_volunteerEmail($ciniki, $args['tnid'], [
            'assignment_id' => $assignment_id,
            'template' => 'volunteers-email-shift-assigned',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1528', 'msg'=>'Unable to email volunteer', 'err'=>$rc['err']));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.volunteerShift', 'object_id'=>$args['shift_id']));

    return array('stat'=>'ok');
}
?>
