<?php
//
// Description
// -----------
// This function will recalculate the timings for a timeslots when using "Individual Registration Schedule Times" (flag 0x080000)
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_timeslotScheduleTimesRecalc(&$ciniki, $tnid, $args) {

    if( !isset($args['timeslot_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.951', 'msg'=>'No timeslot specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Get the slot start time
    //
    if( !isset($args['slot_time']) || !isset($args['festival_id']) ) {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.slot_time "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'timeslot');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.952', 'msg'=>'Unable to load timeslot', 'err'=>$rc['err']));
        }
        if( !isset($rc['timeslot']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.953', 'msg'=>'Unable to find requested timeslot'));
        }
        $timeslot = $rc['timeslot'];
        $slot_time = $timeslot['slot_time'];
        $festival_id = $timeslot['festival_id'];
    } else {
        $slot_time = $args['slot_time'];
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
    // Setup the slot time
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    if( preg_match("/^\s*([0-9]+):([0-9]+)/", $slot_time, $m) ) {
        $dt->setTime($m[1], $m[2], 0);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.954', 'msg'=>'Unable to parse timeslot start time'));
    }

    //
    // Load the registrations
    //
    $strsql = "SELECT registrations.id, "
        . "registrations.timeslot_time, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.perf_time1, "
        . "registrations.perf_time2, "
        . "registrations.perf_time3, "
        . "registrations.perf_time4, "
        . "registrations.perf_time5, "
        . "registrations.perf_time6, "
        . "registrations.perf_time7, "
        . "registrations.perf_time8, "
        . "classes.flags AS class_flags, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'timeslot_time',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 
                'title8', 'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 
                'composer7', 'composer8', 'movements1', 'movements2', 'movements3', 'movements4', 
                'movements5', 'movements6', 'movements7', 'movements8', 'perf_time1', 'perf_time2', 
                'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8', 
                'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.954', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
   
    //
    // Check if anyting has changes with perf_times
    //
    foreach($registrations AS $reg) {
        $update_args = [];
        $new_time = $dt->format('H:i:00');
        if( $new_time != $reg['timeslot_time'] ) {
            $update_args['timeslot_time'] = $new_time;
        }

        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $reg['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, [
            'rounding' => isset($festival['scheduling-perftime-rounding']) ? $festival['scheduling-perftime-rounding'] : '',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $dt->add(new DateInterval('PT' . $rc['perf_time_seconds'] . 'S'));
    }

    return array('stat'=>'ok');
}
?>
