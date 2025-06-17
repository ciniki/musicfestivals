<?php
//
// Description
// -----------
// This function will check for any description substitutions in the timeslot
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_scheduleTimeslotProcess(&$ciniki, $tnid, &$timeslot) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    $perf_time = '';
    $schedule_at_seconds = 0;
    $schedule_ata_seconds = 0;
    $num_reg = 0;

    //
    // Check if class is set, then use class name
    //
    if( isset($timeslot['class_id']) && $timeslot['class_id'] > 0 ) {
        if( $timeslot['name'] == '' && $timeslot['class_name'] != '' ) {
            $timeslot['name'] = $timeslot['class_name'];
        }
        $timeslot['description'] .= ($timeslot['description'] != '' ? "\n":'');

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
                $timeslot['description'] .= ($timeslot['description'] != '' ? "\n":'') . $individual_time_text . $reg['class_code'] . ' - ' . $reg['name'] . $ptime_text;
            }
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
        error_log('testing');
        $pref_time_str = '?';
        if( $slot_length != '' ) {
            $perf_time_str = $slot_length;
        }
    } elseif( $perf_time == '' && $slot_length != '' ) {
        $perf_time_str = $slot_length;
    }

    if( $perf_time_str != '' ) {
        $timeslot['perf_time_text'] = '[' . $perf_time_str . ']';
    } else {
        $timeslot['perf_time_text'] = '';
    }

    //
    // Run any subsctitutions on the description
    //
    if( isset($timeslot['slot_time']) ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $dt = new DateTime($dt->format('Y-m-d ') . $timeslot['slot_time'], new DateTimezone($intl_timezone));
        $timeslot['start_time'] = $dt;
        $timeslot['description'] = str_replace("{_start_time_}", $dt->format('g:i a'), $timeslot['description']);
        $end_dt = clone $dt;
        if( isset($timeslot['slot_seconds']) && $timeslot['slot_seconds'] > 0 ) {
            $end_dt->add(new DateInterval('PT' . $timeslot['slot_seconds'] . 'S'));
            $timeslot['description'] = str_replace("{_end_time_}", $end_dt->format('g:i a'), $timeslot['description']);
        }
    } elseif( isset($timeslot['slot_time_text']) ) {
        $timeslot['description'] = str_replace("{_start_time_}", $timeslot['slot_time_text'], $timeslot['description']);
    }


    return array('stat'=>'ok');
}
?>
