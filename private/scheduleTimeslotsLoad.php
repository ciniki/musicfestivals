
<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_scheduleTimeslotsLoad(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotProcess');

    if( !isset($args['festival']) || !isset($args['division_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.982', 'msg'=>'Missing Arguments'));
    }

    $strsql = "SELECT timeslots.id, "
        . "timeslots.festival_id, "
        . "timeslots.sdivision_id, "
        . "timeslots.flags, ";
    if( isset($args['festival']['scheduling-seconds-show']) && $args['festival']['scheduling-seconds-show'] == 'yes' ) {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i:%s %p') AS slot_time_text, ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
    }
    $strsql .= "timeslots.slot_time, "
        . "timeslots.slot_seconds, "
        . "timeslots.name, "
        . "timeslots.groupname, "
        . "timeslots.start_num, "
        . "timeslots.description, "
        . "timeslots.results_notes, "
        . "timeslots.results_video_url, "
        . "timeslots.linked_timeslot_id, "
        . "registrations.id AS reg_id, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS reg_time_text, "
        . "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i %p') AS reg_finals_time_text, "
        . "TIME_FORMAT(registrations.finals_timeslot_time, '%H%i') AS reg_finals_sort_time, "
        . "registrations.fulltitle1, "
        . "registrations.fulltitle2, "
        . "registrations.fulltitle3, "
        . "registrations.fulltitle4, "
        . "registrations.fulltitle5, "
        . "registrations.fulltitle6, "
        . "registrations.fulltitle7, "
        . "registrations.fulltitle8, "
        . "registrations.perf_time1, "
        . "registrations.perf_time2, "
        . "registrations.perf_time3, "
        . "registrations.perf_time4, "
        . "registrations.perf_time5, "
        . "registrations.perf_time6, "
        . "registrations.perf_time7, "
        . "registrations.perf_time8, "
        . "IFNULL(classes.id, 0) AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.schedule_seconds, "
        . "classes.schedule_at_seconds, "
        . "classes.schedule_ata_seconds, "
        . "";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x80) ) {
        $strsql .= "registrations.pn_display_name AS display_name ";
    } else {
        $strsql .= "registrations.display_name ";
    }
    $strsql .= "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations USE INDEX (timeslots) ON ("
            . "registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival']['id']) . "' "
            . "AND ("
                . "((timeslots.flags&0x02) = 0 AND timeslots.id = registrations.timeslot_id) "
                . "OR ((timeslots.flags&0x02) = 0x02 AND timeslots.id = registrations.finals_timeslot_id) "
                . ") "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' "
        . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival']['id']) . "' "
        . "ORDER BY slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'sdivision_id', 'flags', 
                'slot_time_text', 'slot_time', 'slot_seconds', 'name', 'groupname', 'start_num', 'description', 
                'class_id', 'class_name', 'results_notes', 'results_video_url', 'linked_timeslot_id', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name',
            'fulltitle1', 'fulltitle2', 'fulltitle3', 'fulltitle4', 'fulltitle5', 'fulltitle6', 'fulltitle7', 'fulltitle8', 
            'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
            'reg_time_text', 'reg_finals_time_text', 'reg_finals_sort_time',
            'class_code', 'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $timeslots = isset($rc['timeslots']) ? $rc['timeslots'] : array();
    $nplist = [];
    foreach($timeslots as $tid => $timeslot) {
        $nplist[] = $timeslot['id'];

        $rc = ciniki_musicfestivals_scheduleTimeslotProcess($ciniki, $tnid, $timeslot, [
            'festival' => $args['festival'],
            ]);
        $timeslots[$tid] = $timeslot;

        if( isset($timeslot['registrations']) ) {
            unset($timeslot['registrations']);
        }
    }

    return array('stat'=>'ok', 'timeslots'=>$timeslots, 'nplist'=>$nplist);

}
?>
