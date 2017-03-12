<?php
//
// Description
// -----------
// This method searchs for a Schedule Time Slots for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Schedule Time Slot for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.scheduleTimeslotSearch');
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
        . "WHERE ciniki_musicfestival_schedule_timeslots.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
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
