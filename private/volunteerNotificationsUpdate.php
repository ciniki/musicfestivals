<?php
//
// Description
// -----------
// This function will update the queue of reminder emails for the volunteer
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_volunteerNotificationsUpdate(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the volunteer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerLoad');
    $rc = ciniki_musicfestivals_volunteerLoad($ciniki, $tnid, [
        'volunteer_id' => $args['volunteer_id'],
        'assignments' => 'upcoming',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $volunteer = $rc['volunteer'];

    //
    // Load the festival if not passed
    //
    if( !isset($args['festival']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $volunteer['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1263', 'msg'=>'', 'err'=>$rc['err']));
        }
        $festival = $rc['festival'];
    } else {
        $festival = $args['festival'];
    }

    $sevenday = 'no';
    $oneday = 'no';
    if( isset($festival['volunteers-email-reminder-7day-subject'])
        && $festival['volunteers-email-reminder-7day-subject'] != ''
        && isset($festival['volunteers-email-reminder-7day-message'])
        && $festival['volunteers-email-reminder-7day-message'] != ''
        ) {
        $sevenday = 'yes';
    }
    if( isset($festival['volunteers-email-reminder-24hour-subject'])
        && $festival['volunteers-email-reminder-24hour-subject'] != ''
        && isset($festival['volunteers-email-reminder-24hour-message'])
        && $festival['volunteers-email-reminder-24hour-message'] != ''
        ) {
        $oneday = 'yes';
    }

    //
    // Load the current email queue for the volunteer
    //
    $strsql = "SELECT notifications.id, "
        . "notifications.uuid, "
        . "notifications.scheduled_dt, "
        . "notifications.assignment_id, "
        . "notifications.template "
        . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
        . "INNER JOIN ciniki_musicfestival_volunteer_notifications AS notifications ON ("
            . "assignments.id = notifications.assignment_id "
            . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE assignments.volunteer_id = '" . ciniki_core_dbQuote($ciniki, $args['volunteer_id']) . "' "
        . "AND assignments.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY notifications.assignment_id, notifications.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'assignments', 'fname'=>'assignment_id', 
            'fields'=>array('id'=>'assignment_id'),
            ),
        array('container'=>'notifications', 'fname'=>'template', 
            'fields'=>array('id', 'uuid', 'scheduled_dt', 'assignment_id', 'template'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1265', 'msg'=>'Unable to load notifications', 'err'=>$rc['err']));
    }
    $notifications = isset($rc['assignments']) ? $rc['assignments'] : array();

    //
    // Go through the current assignments and add to message queue
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $volunteer_assignments = [];    // Keep track of assignment-templates already setup
    if( isset($volunteer['shifts']) ) {
        foreach($volunteer['shifts'] as $shift) {
            
            // Skip pending (non-assigned) shifts
            if( $shift['assignment_status'] != 30 ) {
                continue;
            }
            $shift_dt = new DateTime($shift['shift_date'] . ' ' . $shift['start_time'], new DateTimezone($intl_timezone));
            $shift_dt->setTimezone(new DateTimezone('UTC'));
            $sevenday_dt = clone $shift_dt;
            $sevenday_dt->sub(new DateInterval('P7D'));
            $oneday_dt = clone $shift_dt;
            $oneday_dt->sub(new DateInterval('P1D'));
            if( $sevenday == 'yes' && $sevenday_dt > $now 
                && !isset($notifications[$shift['assignment_id']]['notifications']['volunteers-email-reminder-7day'])
                ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.volunteernotification', [
                    'scheduled_dt' => $sevenday_dt->format('Y-m-d H:i:s'),
                    'assignment_id' => $shift['assignment_id'],
                    'template' => 'volunteers-email-reminder-7day',
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1270', 'msg'=>'Unable to add the volunteernotification', 'err'=>$rc['err']));
                }
            }
            if( $oneday == 'yes' && $oneday_dt > $now 
                && !isset($notifications[$shift['assignment_id']]['notifications']['volunteers-email-reminder-24hour'])
                ) {
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.volunteernotification', [
                    'scheduled_dt' => $oneday_dt->format('Y-m-d H:i:s'),
                    'assignment_id' => $shift['assignment_id'],
                    'template' => 'volunteers-email-reminder-24hour',
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1269', 'msg'=>'Unable to add the volunteernotification', 'err'=>$rc['err']));
                }
            }
            $volunteer_assignments[$shift['assignment_id']] = 'yes';
        }
    }

    //
    // Check for any notifications that need to be removed
    //
    foreach($notifications as $assignment) {
        if( !isset($volunteer_assignments[$assignment['id']]) 
            && isset($assignment['notifications']) 
            ) {   
            foreach($assignment['notifications'] as $notification) {
                // 
                // Remove the notification
                //
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.musicfestivals.volunteernotification',
                    $notification['id'], $notification['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
