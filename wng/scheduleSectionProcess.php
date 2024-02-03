<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_scheduleSectionProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.675', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.676', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.677', 'msg'=>"No festival specified"));
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($s['section-id']) || $s['section-id'] == '' || $s['section-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.678', 'msg'=>"No schedule specified"));
    }

    //
    // Load the schedules
    //
    $strsql = "SELECT sections.id, "
        . "sections.name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (sections.flags&0x01) = 0x01 "
        . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.679', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'ok', 'blocks' => array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => 'Unable to find section requested',
            ));
    }
    $schedulesection = $rc['section'];

    //
    // Load the dividions, timeslots and registrations
    //
    $strsql = "SELECT divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
/*        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "timeslots.class4_id, "
        . "timeslots.class5_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
        . "IFNULL(class4.name, '') AS class4_name, "
        . "IFNULL(class5.name, '') AS class5_name, " */
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
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
        . "registrations.participation, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
        . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
/*        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
            . "timeslots.class2_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class4 ON ("
            . "timeslots.class4_id = class4.id " 
            . "AND class4.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class5 ON ("
            . "timeslots.class5_id = class5.id " 
            . "AND class5.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") " */
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($s['ipv']) && $s['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
    } elseif( isset($args['ipv']) && $args['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'description', 'class_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'display_name', 'public_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'participation', 'class_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();
    
    $s['title'] .= ($s['title'] != '' ? ' - ' : '') . $schedulesection['name'];

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'level' => $section['sequence'] == 1 ? 1 : 2,
        'title' => isset($s['title']) ? $s['title'] : '',
        );

    //
    // Show the divisions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    foreach($divisions as $division) {
        if( isset($division['timeslots']) ) {
            foreach($division['timeslots'] as $tid => $timeslot) {
                $division['timeslots'][$tid]['time'] = '<b>' . $timeslot['time'] . '</b>';
//                if( $timeslot['name'] == '' ) {
//                    $content = '<b>' . $timeslot['class_name'] . '</b>';
//                } else {
                    $content = '<b>' . $timeslot['name'] . '</b>';
//                }
                if( isset($timeslot['registrations']) ) {
                    foreach($timeslot['registrations'] as $registration) {
                        for($i = 1; $i <= 8; $i++) {
                            if( isset($registration["title{$i}"]) && $registration["title{$i}"] != '' ) {
                                $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                                if( $rc['stat'] != 'ok' ) {
                                    return $rc;
                                }
                                if( isset($s['full-names']) && $s['full-names'] == 'yes' ) {
                                    $content .= "<br/>" . $registration['display_name'] . ' - ' . $rc['title'];
                                } else {
                                    $content .= "<br/>" . $registration['public_name'] . ' - ' . $rc['title'];
                                }
                            }
                        }
                    }
                }
                $division['timeslots'][$tid]['content'] = $content;
            }
            $blocks[] = array(
                'type' => 'table',
                'title' => $division['name'] . ' - ' . $division['date'],
                'subtitle' => $division['address'],
                'class' => 'musicfestival-timeslots' . ($division['address'] != '' ? ' subtitle' : ''),
                'headers' => 'no',
                'columns' => array(
                    array('label'=>'Time', 'field'=>'time', 'class'=>'alignright aligntop'),
                    array('label'=>'Competitors', 'field'=>'content', 'class'=>''),
                    ),
                'rows' => $division['timeslots'],
                );
        }
//        $blocks[] = array(
//            'type' => 'table',
//            );
    }
    
    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
}
?>
