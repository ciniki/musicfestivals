<?php
//
// Description
// -----------
// This method will delete an shift.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the shift is attached to.
// shift_id:            The ID of the shift to be removed.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerShiftDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'shift_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Shift'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerShiftDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the shift
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_musicfestival_volunteer_shifts "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'shift');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['shift']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1235', 'msg'=>'Shift does not exist.'));
    }
    $shift = $rc['shift'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Detach any messages from this shift
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessagesDetach');
    $rc = ciniki_mail_hooks_objectMessagesDetach($ciniki, $args['tnid'], array(
        'object' => 'ciniki.musicfestivals.volunteershift',
        'object_id' => $args['shift_id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.624', 'msg'=>'Unable to detach from mail messages.', 'err'=>$rc['err']));
    } 

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerShift', $args['shift_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1236', 'msg'=>'Unable to check if the shift is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1237', 'msg'=>'The shift is still in use. ' . $rc['msg']));
    }

    //
    // Get the list of assignments to this shift
    //
    $strsql = "SELECT assignments.id, "
        . "assignments.uuid, "
        . "notifications.id AS notification_id, "
        . "notifications.uuid AS notification_uuid "
        . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
        . "LEFT JOIN ciniki_musicfestival_volunteer_notifications AS notifications ON ("
            . "assignments.id = notifications.assignment_id "
            . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE assignments.shift_id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
        . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'assignments', 'fname'=>'id', 'fields'=>array('id', 'uuid')),
        array('container'=>'notifications', 'fname'=>'notification_id', 
            'fields'=>array('id'=>'notification_id', 'uuid'=>'notification_uuid'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1268', 'msg'=>'Unable to load assignments', 'err'=>$rc['err']));
    }
    $assignments = isset($rc['assignments']) ? $rc['assignments'] : array();

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


    foreach($assignments as $assignment) {
        if( isset($assignment['notifications']) ) {
            foreach($assignment['notifications'] as $notification) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteernotification',
                    $notification['id'], $notification['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return $rc;
                }
            }
        }
        //
        // Detach any messages from this assignment
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessagesDetach');
        $rc = ciniki_mail_hooks_objectMessagesDetach($ciniki, $args['tnid'], array(
            'object' => 'ciniki.musicfestivals.volunteerassignment',
            'object_id' => $assignment['id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.624', 'msg'=>'Unable to detach from mail messages.', 'err'=>$rc['err']));
        } 

        //
        // Delete the assignment
        //
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerassignment',
            $assignment['id'], $assignment['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Remove the shift
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteershift',
        $args['shift_id'], $shift['uuid'], 0x04);
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
