<?php
//
// Description
// -----------
// This function will assign a finals_timeslot_id to a registration
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_registrationFinalsAssign(&$ciniki, $tnid, $args) {

    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.146', 'msg'=>'No registration specified'));
    }
    if( !isset($args['finals_timeslot_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.149', 'msg'=>'No timeslot specified'));
    }

    error_log("Assign Finals: " . $args['finals_timeslot_id']);

    //
    // Get the next sequence and time for the new
    //
    $strsql = "SELECT MAX(registrations.finals_timeslot_sequence) AS max_num "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.finals_timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['finals_timeslot_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sequence = 1;
    if( isset($rc['max']) && $rc['max'] > 1 ) {
        $sequence = $rc['max'] + 1;
    }

    //
    // Update the registrations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['registration']['id'], [
        'finals_timeslot_id' => $args['finals_timeslot_id'],
        'finals_timeslot_sequence' => $sequence,
        ], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1013', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
    }

    //
    // Recalc the timeslot
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotRecalc');
    $rc = ciniki_musicfestivals_scheduleTimeslotRecalc($ciniki, $tnid, [
        'timeslot_id' => $args['finals_timeslot_id'],
        'festival' => $args['festival'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
