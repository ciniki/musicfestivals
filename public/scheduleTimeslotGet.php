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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

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
    // Return default for new Schedule Time Slot
    //
    if( $args['scheduletimeslot_id'] == 0 ) {
        $scheduletimeslot = array('id'=>0,
            'festival_id'=>'',
            'sdivision_id'=>(isset($args['sdivision_id']) ? $args['sdivision_id'] : 0),
            'slot_time'=>'',
            'name'=>'',
            'description'=>'',
            'runsheet_notes'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Time Slot
    //
    else {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.festival_id, "
            . "timeslots.sdivision_id, "
            . "TIME_FORMAT(timeslots.slot_time, '%h:%i %p') AS slot_time, "
            . "timeslots.flags, "
            . "timeslots.name, "
            . "timeslots.description, "
            . "timeslots.runsheet_notes "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduletimeslot', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'sdivision_id', 'slot_time', 
                    'flags', 'name', 'description', 'runsheet_notes', 
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

        //
        // Get the list of registrations
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.participation, "
            . "registrations.title1, "
            . "registrations.timeslot_sequence, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
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
            . "WHERE registrations.timeslot_id = '" . ciniki_core_dbQuote($ciniki, $scheduletimeslot['id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.timeslot_sequence, registrations.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'timeslot_sequence', 'title1',
                    'class_code', 'class_name', 'category_name', 'section_name', 'participation',
                    ),
                'maps'=>array('participation'=>$maps['registration']['participation']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.695', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $scheduletimeslot['registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    }

    $rsp = array('stat'=>'ok', 'scheduletimeslot'=>$scheduletimeslot);

    //
    // Get the list of divisions
    //
    $strsql = "SELECT divisions.id, CONCAT_WS(' - ', sections.name, divisions.name) AS name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections, ciniki_musicfestival_schedule_divisions AS divisions "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.id = divisions.ssection_id "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
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
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT categories.id, "
            . "CONCAT_WS('', categories.name, ' (', COUNT(registrations.id), ')') AS name, "
            . "COUNT(registrations.id) AS num_registrations "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.693', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();
        if( count($rsp['categories']) == 0 ) {
            $rsp['categories'] = array(
                array('id' => 0, 'name' => 'No Unscheduled Registrations'),
                );
        } else {
            array_unshift($rsp['categories'], array('id'=>0, 'name'=>'Select Category'));
        }
    } else {
        $rsp['categories'] = array(
            array('id' => 0, 'name' => 'Select Section'),
            );
    }

    //
    // Get the list of unscheduled registrations for a category
    //
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.title1, "
            . "registrations.participation, "
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
                . "AND registrations.timeslot_id = 0 "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE classes.category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'title1', 'class_code', 'class_name', 'category_name', 'section_name', 'participation'),
                'maps'=>array('participation'=>$maps['registration']['participation']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.694', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    } elseif( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.display_name, "
            . "registrations.title1, "
            . "registrations.participation, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.timeslot_id = 0 "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY categories.sequence, categories.name, class_code "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'title1', 'class_code', 'class_name', 'category_name', 'section_name', 'participation'),
                'maps'=>array('participation'=>$maps['registration']['participation']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.694', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['unscheduled_registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
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
