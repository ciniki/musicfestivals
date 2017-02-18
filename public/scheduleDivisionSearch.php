<?php
//
// Description
// -----------
// This method searchs for a Schedule Divisions for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Schedule Division for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleDivisionSearch($ciniki) {
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.scheduleDivisionSearch');
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
        . "WHERE ciniki_musicfestival_schedule_divisions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
