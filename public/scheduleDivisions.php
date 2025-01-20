<?php
//
// Description
// ===========
// This method will return the details for multislot UI scheduler allowing
// 1 class to be scheduled over multiple timeslots
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
function ciniki_musicfestivals_scheduleDivisions($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'division1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 1'),
        'division2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 2'),
        'division3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 3'),
        'division4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 4'),
        'division5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 5'),
        'division6_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 6'),
        'division7_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 7'),
        'division8_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 8'),
        'division9_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division 9'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Section'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Syllabus Class'),
        'participation'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Participation'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Setup maps for participation number
    //
    if( ($festival['flags']&0x12) > 0 ) {
        $participation_maps = $maps['registration']['participationinitials'];
    } else {
        $participation_maps = ['0' => '', '1' => '', '2' => '', '3' => ''];
    }

    //
    // Load sapos maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sapos_maps = $rc['maps'];

    $rsp = array('stat'=>'ok');

    //
    // Get the list of available divisions
    //
    $strsql = "SELECT divisions.id, "
        . "CONCAT_WS(' - ', sections.name, divisions.name, DATE_FORMAT(divisions.division_date, '%b %e')) AS name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, divisions.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.796', 'msg'=>'Unable to load divisions', 'err'=>$rc['err']));
    }
    $rsp['divisions'] = isset($rc['divisions']) ? $rc['divisions'] : array();
    
    //
    // Get the timeslots and registrations for each division
    //
    for($i = 1; $i <= 9; $i++) {
        $rsp["timeslots{$i}"] = array();
        if( isset($args["division{$i}_id"]) && $args["division{$i}_id"] > 0 ) {
            //
            // Get the list of registrations
            //
            $strsql = "SELECT timeslots.id, ";
            if( isset($festival['scheduling-seconds-show']) && $festival['scheduling-seconds-show'] == 'yes' ) {
                $strsql .= "TIME_FORMAT(slot_time, '%l:%i:%s %p') AS slot_time, ";
            } else {
                $strsql .= "TIME_FORMAT(slot_time, '%l:%i %p') AS slot_time, ";
            }
            $strsql .= "timeslots.name, "
                . "timeslots.groupname, "
                . "timeslots.slot_seconds, "
                . "timeslots.start_num, "
//                . "IF(timeslots.name='', TIME_FORMAT(slot_time, '%l:%i %p'), timeslots.name) AS name, "
                . "registrations.id AS reg_id, "
                . "registrations.display_name, "
                . "registrations.flags, "
                . "registrations.status, "
                . "registrations.participation, "
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
                . "registrations.timeslot_id, "
                . "TIME_FORMAT(registrations.timeslot_time, '%l:%i') AS timeslot_time, "
                . "registrations.timeslot_sequence, "
                . "registrations.notes, "
                . "IFNULL(competitors.id, 0) AS competitor_id, "
                . "IFNULL(competitors.notes, '') AS competitor_notes, "
//                . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
                . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
                . "IFNULL(teachers.display_name, '') AS teacher_name, "
                . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "classes.flags AS class_flags, "
                . "classes.schedule_seconds, "
                . "classes.schedule_at_seconds, "
                . "classes.schedule_ata_seconds, "
                . "categories.name AS category_name, "
                . "sections.name AS section_name "
                . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "timeslots.id = registrations.timeslot_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                    . "(" 
                        . "registrations.competitor1_id = competitors.id "
                        . "OR registrations.competitor2_id = competitors.id "
                        . "OR registrations.competitor3_id = competitors.id "
                        . "OR registrations.competitor4_id = competitors.id "
                        . ") "
                    . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
                . "WHERE timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args["division{$i}_id"]) . "' "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY timeslots.slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence, registrations.display_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'timeslots', 'fname'=>'id', 
                    'fields'=>array('id', 'slot_time', 'slot_seconds', 'name', 'groupname', 'start_num'),
                    ),
                array('container'=>'registrations', 'fname'=>'reg_id', 
                    'fields'=>array('id'=>'reg_id', 'display_name', 'timeslot_id', 'timeslot_time', 'timeslot_sequence', 
                        'flags', 'status', 'accompanist_name', 'teacher_name', 'teacher2_name',
                        'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                        'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                        'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                        'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                        'class_code', 'class_name', 'category_name', 'section_name', 
                        'class_flags', 'schedule_seconds', 'schedule_at_seconds', 'schedule_ata_seconds',
                        'participation', 'notes', 
                        ),
                    'maps'=>array(
                        'participation'=>$participation_maps,
                        ),
                    ),
                array('container'=>'competitors', 'fname'=>'competitor_id', 'fields'=>array('notes'=>'competitor_notes')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.798', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
            }
            $rsp["timeslots{$i}"] = isset($rc['timeslots']) ? $rc['timeslots'] : array();
            foreach($rsp["timeslots{$i}"] as $tid => $timeslot) {
                $rsp["timeslots{$i}"][$tid]['name'] = $timeslot['slot_time'] . ($timeslot['name'] != '' ? ' - ' . $timeslot['name'] : '');
                $perf_time = 0;
                $schedule_at_seconds = 0;
                $schedule_ata_seconds = 0;
                $num = ($timeslot['start_num'] > 1 ? $timeslot['start_num'] : 1);
                if( isset($timeslot['registrations']) ) {
                    foreach($timeslot["registrations"] as $rid => $reg) {
                        $rsp["timeslots{$i}"][$tid]["registrations"][$rid]['timeslot_number'] = $num;
                        $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, array('times'=>'startsum', 'numbers'=>'yes'));
                        $rsp["timeslots{$i}"][$tid]["registrations"][$rid]['titles'] = $rc['titles'];
                        $rsp["timeslots{$i}"][$tid]["registrations"][$rid]['perf_time'] = $rc['perf_time'];
                        $perf_time += $rc['perf_time_seconds'];
                        if( isset($reg['competitors']) ) {
                            foreach($reg['competitors'] as $competitor) {
                                if( $competitor['notes'] != '' ) {
                                    $rsp["timeslots{$i}"][$tid]["registrations"][$rid]['notes'] .= ($rsp["timeslots{$i}"][$tid]["registrations"][$rid]['notes'] != '' ? ' ' : '') . $competitor['notes'];
                                }
                            }
                            unset($rsp["timeslots{$i}"][$tid]["registrations"][$rid]['competitors']);
                        }
                        if( $reg['schedule_at_seconds'] > $schedule_at_seconds ) {
                            $reg['schedule_at_seconds'] = $schedule_at_seconds;
                        }
                        if( $reg['schedule_ata_seconds'] > $schedule_ata_seconds ) {
                            $reg['schedule_ata_seconds'] = $schedule_ata_seconds;
                        }
                        $num++;
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
                $rsp["timeslots{$i}"][$tid]['perf_time_text'] = '[' . $perf_time_str . ']';
            }
        }
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.896', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
    array_unshift($rsp['sections'], array('id'=>0, 'name'=>'Select Section'));

    //
    // Get the list of divisions if section is specified
    //
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
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 ";
        if( isset($args['participation']) && $args['participation'] == 'live' ) {
            $strsql .= "AND registrations.participation = 0 ";
        }
        elseif( isset($args['participation']) && $args['participation'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
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
        $strsql .= ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.897', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();
        if( count($rsp['classes']) == 0 ) {
            $rsp['classes'] = array(
                array('id' => 0, 'name' => 'No Unscheduled Registrations'),
                );
        } else {
            array_unshift($rsp['classes'], array('id'=>0, 'name'=>'Select Class'));
        }
    } else {
        $rsp['classes'] = array(
            array('id' => 0, 'name' => 'Select Section'),
            );
    }

    //
    // Get the list of unscheduled registrations for a category
    //
    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.flags, "
            . "registrations.status, "
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
            . "registrations.notes, "
            . "GROUP_CONCAT(' ', competitors.notes) AS competitor_notes, "
//            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "IFNULL(accompanists.display_name, '') AS accompanist_name, "
            . "IFNULL(teachers.display_name, '') AS teacher_name, "
            . "IFNULL(teachers2.display_name, '') AS teacher2_name, "
            . "IFNULL(teachers.display_name, '') AS teacher_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 ";
        if( isset($args['participation']) && $args['participation'] == 'live' ) {
            $strsql .= "AND registrations.participation = 0 ";
        }
        elseif( isset($args['participation']) && $args['participation'] == 'virtual' ) {
            $strsql .= "AND registrations.participation = 1 ";
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
        $strsql .= ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "(" 
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
/*            . "LEFT JOIN ciniki_sapos_invoices AS invoices ON ("
                . "registrations.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") " */
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
                . ") ";
        if( isset($args['class_id']) && $args['class_id'] > 0 ) {
            $strsql .= "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
        } elseif( isset($args['category_id']) && $args['category_id'] > 0 ) {
            $strsql .= "WHERE classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        $strsql .= "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY registrations.id "
            . "ORDER BY categories.sequence, categories.name, teacher_name, class_code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'flags', 'status', 'accompanist_name', 'teacher_name', 'teacher2_name',
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                    'perf_time1', 'perf_time2', 'perf_time3', 'perf_time4', 'perf_time5', 'perf_time6', 'perf_time7', 'perf_time8',
                    'class_code', 'class_name', 'category_name', 'section_name', 'participation', 'notes', 'competitor_notes',
                    ),
                'maps'=>array(
                    'participation'=>$participation_maps,
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.898', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
        foreach($rsp['unscheduled_registrations'] as $rid => $reg) {
            $rc = ciniki_musicfestivals_titlesMerge($ciniki, $args['tnid'], $reg, array('times'=>'startsum', 'numbers'=>'yes'));
            $rsp['unscheduled_registrations'][$rid]['titles'] = $rc['titles'];
            $rsp['unscheduled_registrations'][$rid]['perf_time'] = $rc['perf_time'];
            if( $reg['competitor_notes'] != '' ) {
                $rsp['unscheduled_registrations'][$rid]['notes'] .= ($reg['notes'] != '' ? ' ' : '') . $reg['competitor_notes'];
            }
        }
    } else {
        $rsp['unschedule_registrations'] = array();
    }

    return $rsp;
}
?>
