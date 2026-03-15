<?php
//
// Description
// -----------
// Check volunteer for conflict with a shift
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_volunteerConflictCheck(&$ciniki, $tnid, $args) {

    //
    // Check for conflict
    //
    $strsql = "SELECT conflictshifts.id "
        . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
        . "INNER JOIN ciniki_musicfestival_volunteer_shifts AS conflictshifts ON ("
            . "shifts.shift_date = conflictshifts.shift_date "
            . "AND ("
                . "(shifts.start_time >= conflictshifts.start_time AND shifts.start_time <= conflictshifts.end_time) "
                . "OR (shifts.end_time >= conflictshifts.start_time AND shifts.end_time <= conflictshifts.end_time) "
                . ") "
            . "AND conflictshifts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
            . "conflictshifts.id = assignments.shift_id "
            . "AND assignments.volunteer_id = '" . ciniki_core_dbQuote($ciniki, $args['volunteer_id']) . "' "
            . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE shifts.id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
        . "AND shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1511', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.musicfestivals.1511', 'msg'=>'Conflict with another timeslot'));
    }

    return array('stat'=>'ok');
}
?>
