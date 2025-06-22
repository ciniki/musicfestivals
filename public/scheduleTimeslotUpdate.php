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
function ciniki_musicfestivals_scheduleTimeslotUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'festival_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Festival'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Section'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Division'),
        'slot_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Time'),
        'pre_seconds'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Pre Length'),
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
        'linked_timeslot_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Linked Timeslot'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current timeslot to get the flags linked_timeslot_id
    //
    $strsql = "SELECT timeslots.id, "
        . "timeslots.name, "
        . "timeslots.festival_id, "
        . "timeslots.sdivision_id, "
        . "timeslots.flags, "
        . "timeslots.linked_timeslot_id "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'timeslot');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1005', 'msg'=>'Unable to load timeslot', 'err'=>$rc['err']));
    }
    if( !isset($rc['timeslot']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1006', 'msg'=>'Unable to find requested timeslot'));
    }
    $timeslot = $rc['timeslot'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $timeslot['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Create the shortname
    //
    if( isset($args['name']) && $args['name'] != $timeslot['name'] && !isset($args['shortname']) 
        && isset($festival['scheduling-timeslot-shortname'])
        && $festival['scheduling-timeslot-shortname'] != 'no'
        && $festival['scheduling-timeslot-shortname'] != 'manual'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'shortnameMake');
        $rc = ciniki_musicfestivals_shortnameMake($ciniki, $args['tnid'], [
            'type' => 'timeslot',
            'format' => $festival['scheduling-timeslot-shortname'],
            'text' => $args['name'],
            ]);
        if( isset($rc['shortname']) ) {
            $args['shortname'] = $rc['shortname'];
        }
    }

    //
    // Check for any timeslots linked to this one, or linked to the timeslot being linked to.
    //
    $linked_timeslot_id = $timeslot['linked_timeslot_id'];
    if( isset($args['linked_timeslot_id']) ) {
        $linked_timeslot_id = $args['linked_timeslot_id'];
    }
    $strsql = "SELECT timeslots.id, "
        . "timeslots.flags "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "WHERE ("
            . "timeslots.linked_timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
            . "OR timeslots.linked_timeslot_id = '" . ciniki_core_dbQuote($ciniki, $linked_timeslot_id) . "' "
            . ") "
        . "AND (timeslots.flags&0x04) = 0x04 "
        . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $timeslot['festival_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'id', 'fields'=>array('id', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1007', 'msg'=>'Unable to load timeslots', 'err'=>$rc['err']));
    }
    $linked_timeslots = isset($rc['timeslots']) ? $rc['timeslots'] : array();

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
    // Update the Schedule Time Slot in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $args['scheduletimeslot_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
        return $rc;
    }

    if( isset($festival['scheduling-timeslot-autoshift']) && $festival['scheduling-timeslot-autoshift'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleDivisionRecalc');
        $rc = ciniki_musicfestivals_scheduleDivisionRecalc($ciniki, $args['tnid'], [
            'division_id' => isset($args['sdivision_id']) ? $args['sdivision_id'] : $timeslot['sdivision_id'],
            ]);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Check if registration schedule times need to be updated
    // Note: Recalcs are auto run in scheduleDivisionRecal
    //
    elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotRecalc');
        $rc = ciniki_musicfestivals_scheduleTimeslotRecalc($ciniki, $args['tnid'], [
            'timeslot_id' => $args['scheduletimeslot_id']
            ]);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    if( isset($args['start_num']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotRenumber');
        $rc = ciniki_musicfestivals_scheduleTimeslotRenumber($ciniki, $args['tnid'], [
            'timeslot_id' => $args['scheduletimeslot_id']
            ]);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    if( isset($args['slot_time']) || isset($args['slot_seconds']) ) {
        $linked_args = [];
        if( isset($args['slot_time']) ) {
            $linked_args['slot_time'] = $args['slot_time'];
        }
        if( isset($args['slot_seconds']) ) {
            $linked_args['slot_seconds'] = $args['slot_seconds'];
        }
    }

    //
    // Check if linked timeslot needs to be updated
    //
    if( isset($timeslot['linked_timeslot_id']) 
        && ($timeslot['flags']&0x04) == 0x04 // Time Linked Timeslot
        && isset($linked_args)
        ) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $timeslot['linked_timeslot_id'], $linked_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Check if any timeslots linked to this one
    //
    if( isset($linked_timeslots) && count($linked_timeslots) > 0 && isset($linked_args) ) {
        foreach($linked_timeslots as $linked_timeslot) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduletimeslot', $linked_timeslot['id'], $linked_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
                return $rc;
            }
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'musicfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.musicfestivals.scheduleTimeslot', 'object_id'=>$args['scheduletimeslot_id']));

    return array('stat'=>'ok');
}
?>
