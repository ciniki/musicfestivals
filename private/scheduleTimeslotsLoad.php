
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

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
    $strsql .= "timeslots.slot_seconds, "
        . "timeslots.name, "
        . "timeslots.groupname, "
        . "timeslots.start_num, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS reg_time_text, "
        . "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i %p') AS reg_finals_time_text, "
        . "TIME_FORMAT(registrations.finals_timeslot_time, '%H%i') AS reg_finals_sort_time, "
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
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "("
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
                'slot_time_text', 'slot_seconds', 'name', 'groupname', 'start_num', 'description', 
                'class_id', 'class_name', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name',
            'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
            'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
            'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
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
        $perf_time = '';
        $schedule_at_seconds = 0;
        $schedule_ata_seconds = 0;
        $num_reg = 0;

        //
        // Check if class is set, then use class name
        //
        if( $timeslot['class_id'] > 0 ) {
            if( $timeslot['name'] == '' && $timeslot['class_name'] != '' ) {
                $timeslots[$tid]['name'] = $timeslot['class_name'];
            }
            $timeslots[$tid]['description'] .= ($timeslots[$tid]['description'] != '' ? "\n":'');

            //
            // Add the registrations to the description
            //
            if( isset($timeslot['registrations']) ) {
                $perf_time = 0;
                // Sort registrations based on finals_time for this finals timeslot
                if( ($timeslot['flags']&0x02) == 0x02 ) {
                    usort($timeslot['registrations'], function($a, $b) {
                        if( $a['reg_finals_sort_time'] == $b['reg_finals_sort_time'] ) {
                            return 0;
                        }
                        return ($a['reg_finals_sort_time'] < $b['reg_finals_sort_time']) ? -1 : 1;
                        });
                }
                $num = ($timeslot['start_num'] && $timeslot['start_num'] > 1 ? $timeslot['start_num'] : 1);
                foreach($timeslot['registrations'] as $reg) {
                    $num_reg++;
                    $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, [
                        'rounding' => isset($args['festival']['scheduling-perftime-rounding']) ? $args['festival']['scheduling-perftime-rounding'] : '',
                        ]);
                    $perf_time += $rc['perf_time_seconds'];
                    $ptime_text = ' [' . $rc['perf_time'] . ']';
                    $individual_time_text = '';
                    if( isset($args['festival']['scheduling-timeslot-startnum']) 
                        && $args['festival']['scheduling-timeslot-startnum'] == 'yes' 
                        ) {
                        $individual_time_text = $num . '. ';
                        $num++;
                    }
                    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) 
                        && $reg['reg_time_text'] != ''
                        ) {
                        if( ($timeslot['flags']&0x02) == 0x02 ) {
                            $individual_time_text .= $reg['reg_finals_time_text'] . ' - ';
                        } else {
                            $individual_time_text .= $reg['reg_time_text'] . ' - ';
                        }
                    }
                    $timeslots[$tid]['description'] .= ($timeslots[$tid]['description'] != '' ? "\n":'') . $individual_time_text . $reg['class_code'] . ' - ' . $reg['name'] . $ptime_text;
                }
                unset($timeslots[$tid]['registrations']);
            }
        }
        if( $schedule_at_seconds > 0 ) {
            $perf_time += $schedule_at_seconds;
        }
        if( $schedule_ata_seconds > 0 && $num_reg > 1 ) {
            $perf_time += ($schedule_ata_seconds * ($num_reg-1));
        }
        $slot_length = '';
        if( $timeslot['slot_seconds'] > 0 ) {
            if( $timeslot['slot_seconds'] > 3600 ) {
                $slot_length = intval($timeslot['slot_seconds']/3600) . 'h ' . ceil(($timeslot['slot_seconds']%3600)/60) . 'm';
            } else {
                $slot_length = '' . intval($timeslot['slot_seconds']/60) . ':' . str_pad(($timeslot['slot_seconds']%60), 2, '0', STR_PAD_LEFT) . '';
            }
        }
        $perf_time_str = '';
        if( $perf_time != '' && $perf_time > 0 ) {
            if( $perf_time > 3600 ) {
                $perf_time_str = intval($perf_time/3600) . 'h ' . ceil(($perf_time%3600)/60) . 'm';
            } else {
                $perf_time_str = '' . intval($perf_time/60) . ':' . str_pad(($perf_time%60), 2, '0', STR_PAD_LEFT) . '';
            }
            if( $slot_length != '' ) {
                $perf_time_str = '<strike>' . $perf_time_str . '</strike> ' . $slot_length;
            }
        } elseif( $perf_time != '' && $perf_time == 0 ) {
            $pref_time_str = '?';
            if( $slot_length != '' ) {
                $perf_time_str = $slot_length;
            }
        }
        if( $perf_time_str != '' ) {
            $timeslots[$tid]['perf_time_text'] = '[' . $perf_time_str . ']';
        } else {
            $timeslots[$tid]['perf_time_text'] = '';
        }
    }

    return array('stat'=>'ok', 'timeslots'=>$timeslots, 'nplist'=>$nplist);

}
?>
