<?php
//
// Description
// -----------
// This method will return the list of Schedule Time Slots for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Schedule Time Slot for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of scheduletimeslot
    //
    $strsql = "SELECT ciniki_musicfestival_schedule_timeslots.id, "
        . "ciniki_musicfestival_schedule_timeslots.festival_id, "
        . "ciniki_musicfestival_schedule_timeslots.sdivision_id, "
        . "ciniki_musicfestival_schedule_timeslots.slot_time, "
        . "ciniki_musicfestival_schedule_timeslots.class1_id, "
        . "ciniki_musicfestival_schedule_timeslots.class2_id, "
        . "ciniki_musicfestival_schedule_timeslots.class3_id, "
        . "ciniki_musicfestival_schedule_timeslots.name, "
        . "ciniki_musicfestival_schedule_timeslots.description "
        . "FROM ciniki_musicfestival_schedule_timeslots "
        . "WHERE ciniki_musicfestival_schedule_timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'scheduletimeslot', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time', 'class1_id', 'class2_id', 'class3_id', 'name', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['scheduletimeslot']) ) {
        $scheduletimeslot = $rc['scheduletimeslot'];
        $scheduletimeslot_ids = array();
        foreach($scheduletimeslot as $iid => $scheduletimeslot) {
            $scheduletimeslot_ids[] = $scheduletimeslot['id'];
        }
    } else {
        $scheduletimeslot = array();
        $scheduletimeslot_ids = array();
    }

    return array('stat'=>'ok', 'scheduletimeslot'=>$scheduletimeslot, 'nplist'=>$scheduletimeslot_ids);
}
?>
