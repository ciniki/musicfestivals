<?php
//
// Description
// -----------
// This method will return the list of Schedule Divisions for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Schedule Division for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleDivisionList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'class_code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisionList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( preg_match("/,/", $args['class_code']) ) {
        $args['class_codes'] = explode(',', $args['class_code']);
    }

    //
    // Get the list of scheduledivisions
    //
    $strsql = "SELECT divisions.id, "
        . "divisions.festival_id, "
        . "divisions.ssection_id, "
        . "divisions.name, "
        . "divisions.division_date ";
    if( isset($args['class_code']) && $args['class_code'] != '' && isset($args['festival_id']) && $args['festival_id'] != '' ) {
        $strsql .= "FROM ciniki_musicfestival_schedule_divisions AS divisions "
            . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id ";
        if( isset($args['class_codes']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql .= "AND classes.code IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['class_codes']) . ") ";
        } else {
            $strsql .= "AND classes.code = '" . ciniki_core_dbQuote($ciniki, $args['class_code']) . "' ";
        }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "ORDER BY divisions.division_date, divisions.shortname, divisions.name, divisions.id  "
            . "";
    } else {
        $strsql .= "FROM ciniki_musicfestival_schedule_divisions AS divisions "
            . "WHERE divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'scheduledivisions', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'division_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['scheduledivisions']) ) {
        $scheduledivisions = $rc['scheduledivisions'];
        $scheduledivision_ids = array();
        foreach($scheduledivisions as $iid => $scheduledivision) {
            $scheduledivision_ids[] = $scheduledivision['id'];
        }
    } else {
        $scheduledivisions = array();
        $scheduledivision_ids = array();
    }

    return array('stat'=>'ok', 'scheduledivisions'=>$scheduledivisions, 'nplist'=>$scheduledivision_ids);
}
?>
