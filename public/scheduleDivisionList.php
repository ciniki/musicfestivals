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

    //
    // Get the list of scheduledivisions
    //
    $strsql = "SELECT ciniki_musicfestival_schedule_divisions.id, "
        . "ciniki_musicfestival_schedule_divisions.festival_id, "
        . "ciniki_musicfestival_schedule_divisions.ssection_id, "
        . "ciniki_musicfestival_schedule_divisions.name, "
        . "ciniki_musicfestival_schedule_divisions.division_date, "
        . "ciniki_musicfestival_schedule_divisions.address "
        . "FROM ciniki_musicfestival_schedule_divisions "
        . "WHERE ciniki_musicfestival_schedule_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'scheduledivisions', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'division_date', 'address')),
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
