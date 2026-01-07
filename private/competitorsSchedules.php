<?php
//
// Description
// -----------
// Load all the competitors and their schedules, indexed by name
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_competitorsSchedules(&$ciniki, $tnid, $args) {

    $strsql = "SELECT competitors.id, "
        . "competitors.name, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, "
        . "DATE_FORMAT(divisions.division_date, '%b %e, %Y') AS division_date "
        . "FROM ciniki_musicfestival_competitors AS competitors "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "("
                . "competitors.id = registrations.competitor1_id "
                . "OR competitors.id = registrations.competitor2_id "
                . "OR competitors.id = registrations.competitor3_id "
                . "OR competitors.id = registrations.competitor4_id "
                . "OR competitors.id = registrations.competitor5_id "
                . ") "
            . "AND registrations.participation <> 1 "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY competitors.name, divisions.division_date, timeslots.slot_time "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'competitors', 'fname'=>'name', 'fields'=>array('name')),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('name'=>'timeslot_name', 'groupname', 'slot_time', 'division_date'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1272', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    return array('stat'=>'ok', 'competitors'=>$competitors);
}
?>
