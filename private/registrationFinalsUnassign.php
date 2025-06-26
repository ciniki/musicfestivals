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
function ciniki_musicfestivals_registrationFinalsUnassign(&$ciniki, $tnid, $args) {

    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1014', 'msg'=>'No registration specified'));
    }
    if( !isset($args['finals_timeslot_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1015', 'msg'=>'No timeslot specified'));
    }

    //
    // Update the registrations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['registration']['id'], [
        'finals_timeslot_id' => 0,
        'finals_timeslot_sequence' => 0,
        'finals_timeslot_time' => '',
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

    //
    // Renumber the timeslot
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotRenumber');
    $rc = ciniki_musicfestivals_scheduleTimeslotRenumber($ciniki, $tnid, [
        'timeslot_id' => $args['finals_timeslot_id'],
        'festival' => $args['festival'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
