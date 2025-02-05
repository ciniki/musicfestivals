<?php
//
// Description
// ===========
// This method will return all the information about an schedule time slot.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule time slot is attached to.
// scheduletimeslot_id:          The ID of the schedule time slot to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotGet($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Category'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Class'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load sapos maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sapos_maps = $rc['maps'];

    //
    // Get the additional settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $festival = $rc['festival'];

    //
    // Load competitor ages if required
    //
    if( isset($festival['scheduling-age-show']) && $festival['scheduling-age-show'] == 'yes' ) {
        $strsql = "SELECT competitors.id, competitors.age "
            . "FROM ciniki_musicfestival_competitors AS competitors "
            . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'competitors');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        $ages = isset($rc['competitors']) ? $rc['competitors'] : array();
    }

    //
    // Return default for new Schedule Time Slot
    //
    if( $args['scheduletimeslot_id'] == 0 ) {
        $scheduletimeslot = array('id'=>0,
            'festival_id'=>'',
            'ssection_id'=>(isset($args['ssection_id']) ? $args['ssection_id'] : 0),
            'sdivision_id'=>(isset($args['sdivision_id']) ? $args['sdivision_id'] : 0),
            'slot_time'=>'',
            'slot_seconds' => '',
            'name'=>'',
            'groupname'=>'',
            'start_num' => '',
            'flags' => 0,
            'description'=>'',
            'runsheet_notes'=>'',
            'results_notes' => '',
            'results_video_url' => '',
        );
    }

    //
    // Get the details for an existing Schedule Time Slot
    //
    else {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.ssection_id, "
            . "timeslots.sdivision_id, ";
        if( isset($festival['scheduling-seconds-show']) && $festival['scheduling-seconds-show'] == 'yes' ) {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i:%s %p') AS slot_time, ";
        } else {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, ";
        }
//        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time, "
        $strsql .= "timeslots.slot_seconds, "
            . "timeslots.flags, "
            . "timeslots.name, "
            . "timeslots.groupname, "
            . "timeslots.start_num, "
            . "timeslots.description, "
            . "timeslots.runsheet_notes, "
            . "timeslots.results_notes, "
            . "timeslots.results_video_url "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduletimeslot', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'ssection_id', 'sdivision_id', 'slot_time', 'slot_seconds',
                    'flags', 'name', 'groupname', 'start_num',
                    'description', 'runsheet_notes', 'results_notes', 'results_video_url',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.98', 'msg'=>'Schedule Time Slot not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduletimeslot'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.99', 'msg'=>'Unable to find Schedule Time Slot'));
        }
        $scheduletimeslot = $rc['scheduletimeslot'][0];
        if( $scheduletimeslot['slot_seconds'] == 0 ) {
            $scheduletimeslot['slot_seconds'] = '';
        }
        if( $scheduletimeslot['start_num'] < 1 ) {
            $scheduletimeslot['start_num'] = '';
        }

        //
        // Get the list of registrations
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.flags, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.participation, "
            . "CONCAT_WS(' ', registrations.notes, registrations.runsheet_notes, registrations.internal_notes) AS notes, "
            . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id, "
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
            . "registrations.perf_time8, ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i') AS timeslot_time, ";
            $strsql .= "registrations.finals_timeslot_sequence AS timeslot_sequence, ";
        } else {
            $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i') AS timeslot_time, ";
            $strsql .= "registrations.timeslot_sequence, ";
        }
        $strsql .= "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS invoice_status_text, "
            . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
            . "IFNULL(teachers.display_name, '') AS teacher_name, "
            . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
            . "IFNULL(members.name, '') AS member_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "classes.flags AS class_flags, "
            . "classes.schedule_seconds, "
            . "classes.schedule_at_seconds, "
            . "classes.schedule_ata_seconds, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS accompanists ON ("
                . "registrations.accompanist_customer_id = accompanists.id "
                . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers ON ("
                . "registrations.teacher_customer_id = teachers.id "
                . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers2 ON ("
                . "registrations.teacher2_customer_id = teachers2.id "
                . "AND teachers2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "registrations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "(" 
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "WHERE registrations.finals_timeslot_id = '" . ciniki_core_dbQuote($ciniki, $scheduletimeslot['id']) . "' ";
        } else {
            $strsql .= "WHERE registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $scheduletimeslot['id']) . "' ";
        }
        $strsql .= "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY registrations.id ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "ORDER BY registrations.finals_timeslot_sequence, registrations.display_name ";
        } else {
            $strsql .= "ORDER BY registrations.timeslot_sequence, registrations.display_name ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'timeslot_time', 'timeslot_sequence', 
                    'flags', 'status', 'status_text', 'accompanist_name', 'member_name', 
                    'teacher_name', 'teacher2_name',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                    'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                    'class_code', 'class_name', 'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                    'category_name', 'section_name', 
                    'participation', 'invoice_status_text', 'notes', 'competitor_notes',
                    ),
                'maps'=>array(
                    'participation'=>$maps['registration']['participation'],
                    'status_text'=>$maps['registration']['status'],
                    'invoice_status_text'=>$sapos_maps['invoice']['typestatus'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.695', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $scheduletimeslot['registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
        $total_time = 0;
        $schedule_at_seconds = 0;
        $schedule_ata_seconds = 0;
        $num = 1;
        if( $scheduletimeslot['start_num'] > 1 ) {
            $num = $scheduletimeslot['start_num'];
        }
        foreach($scheduletimeslot['registrations'] as $rid => $reg) {
            $scheduletimeslot['registrations'][$rid]['timeslot_number'] = $num;
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, [
                'times' => 'startsum', 
                'numbers' => 'yes',
                ]);
            $scheduletimeslot['registrations'][$rid]['titles'] = $rc['titles'];
            $total_time += $rc['perf_time_seconds'];
            if( isset($reg['schedule_at_seconds']) > $schedule_at_seconds ) {
                $schedule_at_seconds = $reg['schedule_at_seconds'];
            }
            if( isset($reg['schedule_ata_seconds']) > $schedule_ata_seconds ) {
                $schedule_ata_seconds = $reg['schedule_ata_seconds'];
            }
            if( isset($ages) ) {
                $ra= '';
                for($i = 1; $i<=5; $i++) {
                    if( $reg["competitor{$i}_id"] > 0 && isset($ages[$reg["competitor{$i}_id"]]) ) {
                        $ra .= ($ra != '' ? ',' : '') . $ages[$reg["competitor{$i}_id"]];
                    }
                }
                if( $ra != '' ) {
                    $scheduletimeslot['registrations'][$rid]['display_name'] .= " [{$ra}]";
                    $scheduletimeslot['registrations'][$rid]['ages'] = $ra;
                }
            }
            if( $reg['competitor_notes'] != '' ) {
                $scheduletimeslot['registrations'][$rid]['notes'] .= ($reg['notes'] != '' ? ' ' : '') . $reg['competitor_notes'];
            }
            $num++;
        }

        if( $schedule_at_seconds > 0 ) {
            $total_time += $schedule_at_seconds;
        }
        if( $schedule_ata_seconds > 0 && count($scheduletimeslot['registrations']) > 1 ) {
            $total_time += ($schedule_ata_seconds * (count($scheduletimeslot['registrations'])-1));
        }
        if( $total_time > 0 ) {
            if( $total_time > 3600 ) {
                $scheduletimeslot['total_perf_time'] = intval($total_time/3600) . 'h ' . ceil(($total_time%3600)/60) . 'm';
            } else {
                $scheduletimeslot['total_perf_time'] =  intval($total_time/60) . ':' . str_pad(($total_time%60), 2, '0', STR_PAD_LEFT);
            }
        } else {
            $scheduletimeslot['total_perf_time'] = '';
        }
    }

    $rsp = array('stat'=>'ok', 'scheduletimeslot'=>$scheduletimeslot);

    //
    // Get the list of divisions
    //
    $strsql = "SELECT divisions.id, "
        . "CONCAT_WS(' - ', sections.name, divisions.name) AS name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sections.name, divisions.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.118', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['divisions']) ) {
        $rsp['scheduledivisions'] = $rc['divisions'];
    }

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.name "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.692', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
    array_unshift($rsp['sections'], array('id'=>0, 'name'=>'Select Section'));

    //
    // Get the list of divisions if section is specified
    //
//    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
//        && isset($args['section_id']) && $args['section_id'] > 0 
//        ) {
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT classes.id, "
            . "CONCAT_WS('', classes.code, ' - ', classes.name, ' (', COUNT(registrations.id), ')') AS name, "
            . "COUNT(registrations.id) AS num_registrations "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "AND registrations.finals_timeslot_id = 0 ";
        } else {
            $strsql .= "AND registrations.timeslot_id = 0 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        $strsql .= "AND ("
                    . "(registrations.status > 5 AND registrations.status < 70) ";
        if( isset($festival['scheduling-draft-show']) && $festival['scheduling-draft-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 5 ";
        }
        if( isset($festival['scheduling-disqualified-show']) && $festival['scheduling-disqualified-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 70 ";
        }
        if( isset($festival['scheduling-withdrawn-show']) && $festival['scheduling-withdrawn-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 75 ";
        }
        if( isset($festival['scheduling-cancelled-show']) && $festival['scheduling-cancelled-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 80 ";
        }
        $strsql .= ") "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY classes.id "
            . "ORDER BY classes.code, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.693', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();
        if( count($rsp['classes']) == 0 ) {
            $rsp['classes'] = array(
                array('id' => 0, 'name' => 'No Unscheduled Registrations'),
                );
        } else {
            array_unshift($rsp['classes'], array('id'=>0, 'name'=>'Select Class'));
        }

/*    } elseif( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) 
        && isset($args['section_id']) && $args['section_id'] > 0 
        ) {
        $strsql = "SELECT categories.id, "
            . "CONCAT_WS('', categories.name, ' (', COUNT(registrations.id), ')') AS name, "
            . "COUNT(registrations.id) AS num_registrations "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "AND registrations.finals_timeslot_id = 0 ";
        } else {
            $strsql .= "AND registrations.timeslot_id = 0 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY categories.id "
            . "ORDER BY categories.sequence, categories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.193', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();
        if( count($rsp['categories']) == 0 ) {
            $rsp['categories'] = array(
                array('id' => 0, 'name' => 'No Unscheduled Registrations'),
                );
        } else {
            array_unshift($rsp['categories'], array('id'=>0, 'name'=>'Select Category'));
        } */
    } else {
        $rsp['categories'] = array(
            array('id' => 0, 'name' => 'Select Section'),
            );
        $rsp['classes'] = array(
            array('id' => 0, 'name' => 'Select Section'),
            );
    }

    //
    // Get the list of unscheduled registrations for a category
    //
    if( (isset($args['category_id']) && $args['category_id'] > 0)
        || (isset($args['class_id']) && $args['class_id'] > 0)
        ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.flags, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id, "
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
            . "registrations.participation, "
            . "CONCAT_WS(' ', registrations.notes, registrations.runsheet_notes, registrations.internal_notes) AS notes, "
            . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS invoice_status_text, "
            . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
            . "IFNULL(teachers.display_name, '') AS teacher_name, "
            . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
            . "IFNULL(members.name, '') AS member_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "classes.flags AS class_flags, "
            . "classes.schedule_seconds, "
            . "classes.schedule_at_seconds, "
            . "classes.schedule_ata_seconds, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "AND registrations.finals_timeslot_id = 0 ";
        } else {
            $strsql .= "AND registrations.timeslot_id = 0 ";
        }
        // Defaults to only load those registrations which are not draft or disqualified or withdrawn or cancelled
        $strsql .= "AND ("
                    . "(registrations.status > 5 AND registrations.status < 70) ";
        if( isset($festival['scheduling-draft-show']) && $festival['scheduling-draft-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 5 ";
        }
        if( isset($festival['scheduling-disqualified-show']) && $festival['scheduling-disqualified-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 70 ";
        }
        if( isset($festival['scheduling-withdrawn-show']) && $festival['scheduling-withdrawn-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 75 ";
        }
        if( isset($festival['scheduling-cancelled-show']) && $festival['scheduling-cancelled-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 80 ";
        }
        $strsql .= ") ";
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "(" 
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS accompanists ON ("
                . "registrations.accompanist_customer_id = accompanists.id "
                . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers ON ("
                . "registrations.teacher_customer_id = teachers.id "
                . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers2 ON ("
                . "registrations.teacher2_customer_id = teachers2.id "
                . "AND teachers2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "registrations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        if( isset($args['class_id']) && $args['class_id'] > 0 ) {
            $strsql .= "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
        } elseif( isset($args['category_id']) && $args['category_id'] > 0 ) {
            $strsql .= "WHERE classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        $strsql .= "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY registrations.id "
            . "ORDER BY categories.sequence, categories.name, class_code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'flags', 'status', 'status_text', 'accompanist_name', 'member_name', 
                    'teacher_name', 'teacher2_name',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                    'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                    'class_code', 'class_name', 'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                    'category_name', 'section_name', 'participation', 'invoice_status_text',
                    ),
                'maps'=>array(
                    'participation'=>$maps['registration']['participation'],
                    'status_text'=>$maps['registration']['status'],
                    'invoice_status_text'=>$sapos_maps['invoice']['typestatus'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.196', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    } elseif( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.flags, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id, "
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
            . "registrations.participation, "
            . "CONCAT_WS(' ', registrations.notes, registrations.runsheet_notes, registrations.internal_notes) AS notes, "
            . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS invoice_status_text, "
            . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
            . "IFNULL(teachers.display_name, '') AS teacher_name, "
            . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
            . "IFNULL(members.name, '') AS member_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "classes.flags AS class_flags, "
            . "classes.schedule_seconds, "
            . "classes.schedule_at_seconds, "
            . "classes.schedule_ata_seconds, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id ";
        if( ($scheduletimeslot['flags']&0x02) == 0x02 ) {
            $strsql .= "AND registrations.finals_timeslot_id = 0 ";
        } else {
            $strsql .= "AND registrations.timeslot_id = 0 ";
        }
        // Defaults to only load those registrations which are not draft or disqualified or withdrawn or cancelled
        $strsql .= "AND ("
                    . "(registrations.status > 5 AND registrations.status < 70) ";
        if( isset($festival['scheduling-draft-show']) && $festival['scheduling-draft-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 5 ";
        }
        if( isset($festival['scheduling-disqualified-show']) && $festival['scheduling-disqualified-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 70 ";
        }
        if( isset($festival['scheduling-withdrawn-show']) && $festival['scheduling-withdrawn-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 75 ";
        }
        if( isset($festival['scheduling-cancelled-show']) && $festival['scheduling-cancelled-show'] == 'yes' ) {
            $strsql .= "OR registrations.status = 80 ";
        }
        $strsql .= ") ";
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "(" 
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS accompanists ON ("
                . "registrations.accompanist_customer_id = accompanists.id "
                . "AND accompanists.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers ON ("
                . "registrations.teacher_customer_id = teachers.id "
                . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS teachers2 ON ("
                . "registrations.teacher2_customer_id = teachers2.id "
                . "AND teachers2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
                . "registrations.member_id = members.id "
                . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY registrations.id "
            . "ORDER BY categories.sequence, categories.name, class_code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'flags', 'status', 'status_text', 'accompanist_name', 'member_name', 
                    'teacher_name', 'teacher2_name',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                    'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                    'class_code', 'class_name', 'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                    'category_name', 'section_name', 'participation', 'invoice_status_text', 'notes', 'competitor_notes',
                    ),
                'maps'=>array(
                    'participation'=>$maps['registration']['participation'],
                    'status_text'=>$maps['registration']['status'],
                    'invoice_status_text'=>$sapos_maps['invoice']['typestatus'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.694', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    }
    if( isset($rsp['unscheduled_registrations']) ) {
        foreach($rsp['unscheduled_registrations'] as $rid => $reg) {
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, [
                'times' => 'startsum', 
                'numbers' => 'yes',
//                'schedule_time' => isset($festival['syllabus-schedule-time']) ? $festival['syllabus-schedule-time'] : '',
//                'schedule_seconds' => $reg['schedule_seconds'],
                ]);
            $rsp['unscheduled_registrations'][$rid]['titles'] = $rc['titles'];
            if( isset($ages) ) {
                $ra= '';
                for($i = 1; $i<=5; $i++) {
                    if( $reg["competitor{$i}_id"] > 0 && isset($ages[$reg["competitor{$i}_id"]]) ) {
                        $ra .= ($ra != '' ? ',' : '') . $ages[$reg["competitor{$i}_id"]];
                    }
                }
                if( $ra != '' ) {
                    $rsp['unscheduled_registrations'][$rid]['display_name'] .= " [{$ra}]";
                    $rsp['unscheduled_registrations'][$rid]['ages'] = $ra;
                }
            }
            if( $reg['competitor_notes'] != '' ) {
                $rsp['unscheduled_registrations'][$rid]['notes'] .= ($reg['notes'] != '' ? ' ' : '') . $reg['competitor_notes'];
            }
        }
    }
/*
    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name, "
        . "FORMAT(classes.fee, 2) AS fee, "
        . "registrations.id AS registration_id, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.timeslot_sequence, "
        . "IFNULL(TIME_FORMAT(rtimeslots.slot_time, '%h:%i %p'), '') AS regtime "
//        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS rtimeslots ON ("
            . "registrations.timeslot_id = rtimeslots.id "
            . "AND rtimeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//        . "GROUP BY classes.id "
//        . "ORDER BY num_registrations DESC, sections.name, classes.code "
        . "ORDER BY classes.id, registrations.timeslot_sequence, sections.name, classes.code "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'fee')),
        array('container'=>'registrations', 'fname'=>'registration_id', 
            'fields'=>array('id'=>'registration_id', 'name'=>'display_name', 'time'=>'regtime', 'title1', 'timeslot_sequence'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
        foreach($rsp['classes'] as $cid => $class) {
            $rsp['classes'][$cid]['num_registrations'] = (isset($class['registrations']) ? count($class['registrations']) : 0);
            if( $rsp['classes'][$cid]['num_registrations'] > 0 ) {
                $rsp['classes'][$cid]['name'] .= ' (' . $rsp['classes'][$cid]['num_registrations'] . ')';
            } else {
                unset($rsp['classes'][$cid]);
                continue;
            }
            if( isset($class['registrations']) ) {
                foreach($class['registrations'] as $rid => $reg) {
                    if( $reg['time'] != '' ) {
                        $rsp['classes'][$cid]['registrations'][$rid]['name'] .= ' (' . $reg['time'] . ')';
                    }
                }
            }
        }
        usort($rsp['classes'], function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
//            if( $a['num_registrations'] == $b['num_registrations'] ) {
//                return strcasecmp($a['name'], $b['name']);
//            }
//            return ($a['num_registrations'] > $b['num_registrations'] ? -1 : 1);
        });
    }
*/
    return $rsp;
}
?>
