<?php
//
// Description
// -----------
// This function will renumber the registrations for a timeslot
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_scheduleTimeslotRenumber(&$ciniki, $tnid, $args) {

    if( !isset($args['timeslot_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.955', 'msg'=>'No timeslot specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Get the slot start time
    //
    if( !isset($args['start_num']) || !isset($args['festival_id']) ) {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.start_num "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'timeslot');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.956', 'msg'=>'Unable to load timeslot', 'err'=>$rc['err']));
        }
        if( !isset($rc['timeslot']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.957', 'msg'=>'Unable to find requested timeslot'));
        }
        $timeslot = $rc['timeslot'];
        $start_num = $timeslot['start_num'];
        $festival_id = $timeslot['festival_id'];
    } else {
        $start_num = $args['start_num'];
        $festival_id = $args['festival_id'];
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $festival_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load the registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.timeslot_sequence "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'timeslot_sequence'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.991', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
   
    //
    // Check if anyting has changes with perf_times
    //
    foreach($registrations AS $reg) {
        $update_args = [];
        if( $start_num != $reg['timeslot_sequence'] ) {
            $update_args['timeslot_sequence'] = $start_num;
        }

        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $reg['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        
        $start_num++;
    }

    return array('stat'=>'ok');
}
?>
