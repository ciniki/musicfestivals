<?php
//
// Description
// -----------
// This function will recalculate the start time of each timeslot for a day.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_scheduleDivisionRecalc(&$ciniki, $tnid, $args) {

    if( !isset($args['division_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.803', 'msg'=>'No division specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotRecalc');

    //
    // Load the timeslots for the division
    //
    $strsql = "SELECT timeslots.id, "
        . "timeslots.festival_id, "
        . "timeslots.flags, "
        . "timeslots.slot_time, "
        . "timeslots.pre_seconds, "
        . "timeslots.slot_seconds, "
        . "COUNT(registrations.id) AS num_reg "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "("
                . "timeslots.id = registrations.timeslot_id "
                . "OR timeslots.id = registrations.finals_timeslot_id "
            . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' "
        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY timeslots.id "
        . "ORDER BY slot_time "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'flags', 'slot_time', 'pre_seconds', 'slot_seconds', 'num_reg'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.804', 'msg'=>'Unable to load timeslots', 'err'=>$rc['err']));
    }
    $timeslots = isset($rc['timeslots']) ? $rc['timeslots'] : array();

    if( count($timeslots) == 0 ) {
        return array('stat' => 'ok');
    }

    //
    // Update the timeslots
    //
    $start_time = $timeslots[0]['slot_time'];
    foreach($timeslots as $tid => $timeslot) {
        if( ($timeslot['flags']&0x04) == 0x04 ) {
            // Stop if hits auto linked timeslot
            break;
        }
        //
        // Check if timeslot needs buffer before start
        //
        if( $tid > 0 && $timeslot['pre_seconds'] > 0 ) {
            $dt = new DateTime('now');
            $m = explode(':', $start_time);
            $dt->setTime($m[0], $m[1], $m[2]);
            $dt->add(new DateInterval('PT' . $timeslot['pre_seconds'] . 'S'));
            $start_time = $dt->format('H:i:s');
        }

        //
        // Check if timeslot needs a new start time
        //
        if( $start_time != $timeslot['slot_time'] ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.scheduletimeslot', $timeslot['id'], [
                'slot_time' => $start_time,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1012', 'msg'=>'Unable to update the scheduletimeslot', 'err'=>$rc['err']));
            }
        }

        //
        // Recalculate how long this timeslot should be
        //
        if( $timeslot['slot_seconds'] > 0 ) {
            $dt = new DateTime('now');
            $m = explode(':', $start_time);
            $dt->setTime($m[0], $m[1], $m[2]);
            $dt->add(new DateInterval('PT' . $timeslot['slot_seconds'] . 'S'));
            $start_time = $dt->format('H:i:s');
        } elseif( $timeslot['num_reg'] > 0 ) {
            $rc = ciniki_musicfestivals_scheduleTimeslotRecalc($ciniki, $tnid, [
                'timeslot_id' => $timeslot['id'],
                'festival_id' => $timeslot['festival_id'],
                'slot_time' => $timeslot['slot_time'],
                ]);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['end_time']) ) {
                if( $start_time == $rc['end_time'] ) {
                    //
                    // Stop if we found a zero time slot
                    //
                    break;
                } else {
                    $start_time = $rc['end_time'];
                }
            } else {
                break;
            }
        } else {
            break;
        }
    }

    return array('stat'=>'ok');
}
?>
