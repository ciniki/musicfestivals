<?php
//
// Description
// -----------
// This function will load a message and all the objects for it.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_messageLoad(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['message_id']) || $args['message_id'] < 1 || $args['message_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.472', 'msg'=>'No message specified'));
    }
        
    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
        
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
    // Load the message details
    //
    $strsql = "SELECT messages.id, "
        . "messages.festival_id, "
        . "messages.subject, "
        . "messages.status, "
        . "messages.flags, "
        . "messages.content, "
        . "messages.dt_scheduled, "
        . "messages.dt_scheduled AS dt_scheduled_text, "
//        . "DATE_FORMAT(messages.dt_scheduled, '%b %e, %Y %l:%i %p') AS dt_scheduled_text, "
        . "messages.dt_sent, "
        . "messages.dt_sent AS dt_sent_text "
//        . "DATE_FORMAT(messages.dt_sent, '%b %e, %Y %l:%i %p') AS dt_sent_text "
        . "FROM ciniki_musicfestival_messages AS messages "
        . "WHERE messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array(
                'id', 'festival_id', 'subject', 'status', 'flags', 'content', 'dt_scheduled',
                'dt_scheduled_text', 'dt_sent', 'dt_sent_text'),
            'utctotz'=>array(
                'dt_scheduled_text'=>array('format'=>'M j, Y g:i A', 'timezone'=>$intl_timezone),
                'dt_sent_text'=>array('format'=>'M j, Y g:i A', 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.496', 'msg'=>'Unable to load messages', 'err'=>$rc['err']));
    }
    if( !isset($rc['messages'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.479', 'msg'=>'Unable to find requested message'));
    }
    $rsp = array('stat' => 'ok', 'message' => $rc['messages'][0]);
    $rsp['message']['objects'] = array();

    if( isset($maps['message']['status'][$rsp['message']['status']]) ) {
        $rsp['message']['status_text'] = $maps['message']['status'][$rsp['message']['status']];
    }

    //
    // Load festival flags
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.name, "
        . "festivals.flags "
        . "FROM ciniki_musicfestivals AS festivals "
        . "WHERE festivals.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festivals.id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.482', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.483', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];

    //
    // Load the objects
    //
    $strsql = "SELECT refs.id, "
        . "refs.object, "
        . "refs.object_id "
        . "FROM ciniki_musicfestival_messagerefs AS refs "
        . "WHERE refs.message_id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "AND refs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'refs', 'fname'=>'id', 'fields'=>array('id', 'object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.480', 'msg'=>'Unable to load refs', 'err'=>$rc['err']));
    }
    $rsp['message']['refs'] = isset($rc['refs']) ? $rc['refs'] : array();

    //
    // Process the objects
    //
    $ref_ids = array(
        'sections' => array(),
        'categories' => array(),
        'classes' => array(),
        'schedule' => array(),
        'divisions' => array(),
        'timeslots' => array(),
        );
    $object_ids = array();
    $competitor_ids = array();
    $teacher_ids = array();
    foreach($rsp['message']['refs'] as $rid => $ref) {
        if( !isset($object_ids[$ref['object']]) ) {
            $object_ids[$ref['object']] = array();
        }
        $object_ids[$ref['object']][] = $ref['object_id'];

        //
        // Load labels
        //
        if( $ref['object'] == 'ciniki.musicfestivals.section' ) {
            $strsql = "SELECT sections.name "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "WHERE sections.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Section');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 10,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Syllabus',
                'label' => $label,
            );
            $ref_ids['sections'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.category' ) {
            $strsql = "SELECT sections.name AS section_name, "
                . "categories.name "
                . "FROM ciniki_musicfestival_categories AS categories "
                . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE categories.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['section_name'] . ' - ' . $rc['item']['name'] : 'Unknown Category');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 20,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Category',
                'label' => $label,
            );
            $ref_ids['categories'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.class' ) {
            $strsql = "SELECT sections.name AS section_name, "
                . "categories.name AS category_name, "
                . "classes.name "
                . "FROM ciniki_musicfestival_classes AS classes "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE classes.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            if( ($festival['flags']&0x0100) == 0x0100 ) {
                $label = (isset($rc['item']['name']) ? $rc['item']['section_name'] . ' - ' . $rc['item']['category_name']  . ' - ' . $rc['item']['name'] : 'Unknown Class');
            } else {
                $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Class');
            }
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 30,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Class',
                'label' => $label,
            );
            $ref_ids['classes'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.schedulesection' ) {
            $strsql = "SELECT sections.name "
                . "FROM ciniki_musicfestival_schedule_sections AS sections "
                . "WHERE sections.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Schedule');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 40,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Schedule', 
                'label' => $label,
            );
            $ref_ids['schedule'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.scheduledivision' ) {
            $strsql = "SELECT sections.name AS section_name, "
                . "divisions.name "
                . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                    . "divisions.ssection_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE divisions.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['section_name'] . ' - ' . $rc['item']['name'] : 'Unknown Schedule Division');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 50,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Division',
                'label' => $label,
            );
            $ref_ids['divisions'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.scheduletimeslot' ) {
            $strsql = "SELECT sections.name AS section_name, "
                . "divisions.name AS division_name, "
                . "timeslots.name "
                . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
                . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "timeslots.sdivision_id = divisions.id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                    . "divisions.ssection_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['section_name'] . ' - ' . $rc['item']['division_name']  . ' - ' . $rc['item']['name'] : 'Unknown Timeslot');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 60,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => "Timeslot",
                'label' => $label,
            );
            $ref_ids['timeslots'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.competitor' ) {
            $strsql = "SELECT competitors.name "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE competitors.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Competitor');
            $competitor_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 80,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Competitor',
                'label' => $label,
            );
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.teacher' ) {
            $strsql = "SELECT customers.display_name AS name "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.481', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Teacher');
            $teacher_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 70,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Teacher',
                'label' => $label,
            );
        } 
    }

    //
    // Load all the registrations from sections and/or schedule
    //
    foreach($object_ids as $object => $ids) {
        $reg_strsql = '';
        if( $object == 'ciniki.musicfestivals.section' ) {
            $reg_strsql = "FROM ciniki_musicfestival_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "sections.id = categories.section_id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE sections.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.category' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_categories AS categories "
                . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "categories.id = classes.category_id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE categories.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.class' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_classes AS classes "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "classes.id = registrations.class_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE classes.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.schedulesection' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_schedule_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                    . "sections.id = divisions.ssection_id "
                    . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "divisions.id = timeslots.sdivision_id "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "timeslots.id = registrations.timeslot_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE divisions.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.scheduledivision' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "divisions.id = timeslots.sdivision_id "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "timeslots.id = registrations.timeslot_id "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE divisions.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.scheduletimeslot' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.timeslot_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }

        if( $reg_strsql != '' ) {
            $strsql = "SELECT registrations.id, "
                . "registrations.teacher_customer_id, "
                . "registrations.competitor1_id, "
                . "registrations.competitor2_id, "
                . "registrations.competitor3_id, "
                . "registrations.competitor4_id, "
                . "registrations.competitor5_id "
                . $reg_strsql
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.473', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
            }
            $registrations = isset($rc['rows']) ? $rc['rows'] : array();
            foreach($registrations as $reg) {
                if( ($rsp['message']['flags']&0x02) == 0 && $reg['teacher_customer_id'] > 0 ) {
                    $teacher_ids[] = $reg['teacher_customer_id'];
                }
                if( ($rsp['message']['flags']&0x01) == 0 ) {
                    if( $reg['competitor1_id'] > 0 ) {
                        $competitor_ids[] = $reg['competitor1_id'];
                    }
                    if( $reg['competitor2_id'] > 0 ) {
                        $competitor_ids[] = $reg['competitor2_id'];
                    }
                    if( $reg['competitor3_id'] > 0 ) {
                        $competitor_ids[] = $reg['competitor3_id'];
                    }
                    if( $reg['competitor4_id'] > 0 ) {
                        $competitor_ids[] = $reg['competitor4_id'];
                    }
                    if( $reg['competitor5_id'] > 0 ) {
                        $competitor_ids[] = $reg['competitor5_id'];
                    }
                }
            }
        }
    }

    //
    // Load the full syllabus, schedule, competitor list and teachers and mark which objects
    // are added, or which classes/categories are auto included in a section
    // and which classes are auto included in each category or section.
    //
    if( isset($args['allrefs']) && $args['allrefs'] == 'yes' ) {
        //
        // Load the full list of competitors
        //
        $strsql = "SELECT competitors.id, "
            . "competitors.name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY competitors.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.488', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
        }
        $rsp['competitors'] = isset($rc['competitors']) ? $rc['competitors'] : array();

        //
        // Load the full list of teachers
        //
        $strsql = "SELECT customers.id, "
            . "customers.display_name AS name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.488', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        $rsp['teachers'] = isset($rc['teachers']) ? $rc['teachers'] : array();

        //
        // Load the syllabus and registrations
        //
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "categories.id AS category_id, "
            . "categories.name AS category_name, "
            . "classes.id AS class_id, "
            . "classes.name AS class_name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sections.sequence, sections.name, "
                . "categories.sequence, categories.name, "
                . "classes.sequence, classes.name, "
                . "registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 
                'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
                ),
            array('container'=>'categories', 'fname'=>'category_id', 
                'fields'=>array('id'=>'category_id', 'name'=>'category_name'),
                ),
            array('container'=>'classes', 'fname'=>'class_id', 
                'fields'=>array('id'=>'class_id', 'name'=>'class_name'),
                ),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.487', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
        }
        $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($rsp['sections'] as $sid => $section) {
            $rsp['sections'][$sid]['object'] = 'ciniki.musicfestivals.section';
            //
            // Check if category is automatically included in section
            //
            if( in_array($sid, $ref_ids['sections']) ) {
                $rsp['sections'][$sid]['added'] = 'yes';
            }
            if( isset($section['categories']) ) { 
                foreach($section['categories'] as $cid => $category) {
                    $rsp['sections'][$sid]['categories'][$cid]['object'] = 'ciniki.musicfestivals.category';
                    //
                    // Check if category has been added to messagerefs
                    //
                    if( in_array($cid, $ref_ids['categories']) ) {
                        $rsp['sections'][$sid]['partial'] = 'yes';
                        $rsp['sections'][$sid]['categories'][$cid]['added'] = 'yes';
                    }
                    //
                    // Check if category is automatically included in section
                    //
                    elseif( in_array($sid, $ref_ids['sections']) ) {
                        $rsp['sections'][$sid]['categories'][$cid]['included'] = 'yes';
                    }
                    if( isset($category['classes']) ) { 
                        foreach($category['classes'] as $clid => $class) {
                            $rsp['sections'][$sid]['categories'][$cid]['classes'][$clid]['object'] = 'ciniki.musicfestivals.class';
                            //
                            // Check if category has been added to messagerefs
                            //
                            $include_reg = 'no';
                            if( in_array($clid, $ref_ids['classes']) ) {
                                $rsp['sections'][$sid]['partial'] = 'yes';
                                $rsp['sections'][$sid]['categories'][$cid]['partial'] = 'yes';
                                $rsp['sections'][$sid]['categories'][$cid]['classes'][$clid]['added'] = 'yes';
                                $include_reg = 'yes';
                            }
                            //
                            // Check if category is automatically included in category
                            //
                            elseif( in_array($cid, $ref_ids['categories']) ) {
                                $rsp['sections'][$sid]['categories'][$cid]['classes'][$clid]['included'] = 'yes';
                                $include_reg = 'yes';
                            }
                            //
                            // Check if category is automatically included in section
                            //
                            elseif( in_array($sid, $ref_ids['sections']) ) {
                                $rsp['sections'][$sid]['categories'][$cid]['classes'][$clid]['included'] = 'yes';
                                $include_reg = 'yes';
                            }
                            if( $include_reg == 'yes' && isset($class['registrations']) ) {
                                foreach($class['registrations'] AS $rid => $reg) {
                                    if( ($rsp['message']['flags']&0x02) == 0 
                                        && isset($rsp['teachers'][$reg['teacher_customer_id']]) 
                                        ) {
                                        $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                                    }
                                    if( ($rsp['message']['flags']&0x01) == 0 ) {
                                        for($i = 1; $i <= 5; $i++) {
                                            if( isset($rsp['competitors'][$reg["competitor{$i}_id"]]) ) {
                                                $rsp['competitors'][$reg["competitor{$i}_id"]]['included'] = 'yes';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        //
        // Load the schedule and registrations
        //
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "divisions.division_date AS division_date, "
            . "timeslots.id AS timeslot_id, "
            . "timeslots.name AS timeslot_name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sections.name, "
                . "divisions.division_date, "
                . "timeslots.slot_time, "
                . "registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 
                'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
                ),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'name'=>'division_name'),
                ),
            array('container'=>'timeslots', 'fname'=>'timeslot_id', 
                'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name'),
                ),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.487', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
        }
        $rsp['schedule'] = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($rsp['schedule'] as $sid => $section) {
            $rsp['schedule'][$sid]['object'] = 'ciniki.musicfestivals.schedulesection';
            //
            // Check if category is automatically included in section
            //
            if( in_array($sid, $ref_ids['schedule']) ) {
                $rsp['schedule'][$sid]['added'] = 'yes';
            }
            if( isset($section['divisions']) ) { 
                foreach($section['divisions'] as $did => $division) {
                    $rsp['schedule'][$sid]['divisions'][$did]['object'] = 'ciniki.musicfestivals.scheduledivision';
                    //
                    // Check if division has been added to messagerefs
                    //
                    if( in_array($did, $ref_ids['divisions']) ) {
                        $rsp['schedule'][$sid]['partial'] = 'yes';
                        $rsp['schedule'][$sid]['divisions'][$did]['added'] = 'yes';
                    }
                    //
                    // Check if division is automatically included in section
                    //
                    elseif( in_array($sid, $ref_ids['schedule']) ) {
                        $rsp['schedule'][$sid]['divisions'][$did]['included'] = 'yes';
                    }
                    if( isset($division['timeslots']) ) { 
                        foreach($division['timeslots'] as $tid => $timeslot) {
                            $rsp['schedule'][$sid]['divisions'][$did]['timeslots'][$tid]['object'] = 'ciniki.musicfestivals.scheduletimeslot';
                            //
                            // Check if division has been added to messagerefs
                            //
                            $include_reg = 'no';
                            if( in_array($tid, $ref_ids['timeslots']) ) {
                                $rsp['schedule'][$sid]['divisions'][$did]['partial'] = 'yes';
                                $rsp['schedule'][$sid]['divisions'][$did]['timeslots'][$tid]['added'] = 'yes';
                                $include_reg = 'yes';
                            }
                            //
                            // Check if division is automatically included in section
                            //
                            elseif( in_array($did, $ref_ids['divisions']) ) {
                                $rsp['schedule'][$sid]['divisions'][$did]['timeslots'][$tid]['included'] = 'yes';
                                $include_reg = 'yes';
                            }
                            //
                            // Check if timeslot is automatically included in section
                            //
                            elseif( in_array($sid, $ref_ids['schedule']) ) {
                                $rsp['schedule'][$sid]['divisions'][$did]['timeslots'][$tid]['included'] = 'yes';
                                $include_reg = 'yes';
                            }
                            if( $include_reg == 'yes' && isset($timeslot['registrations']) ) {
                                foreach($timeslot['registrations'] AS $rid => $reg) {
                                    if( ($rsp['message']['flags']&0x02) == 0 
                                        && isset($rsp['teachers'][$reg['teacher_customer_id']]) 
                                        ) {
                                        $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                                    }
                                    if( ($rsp['message']['flags']&0x01) == 0 ) {
                                        for($i = 1; $i <= 5; $i++) {
                                            if( isset($rsp['competitors'][$reg["competitor{$i}_id"]]) ) {
                                                $rsp['competitors'][$reg["competitor{$i}_id"]]['included'] = 'yes';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //
        // Check the objects for teacher and competitor direct adds
        //
        foreach($rsp['message']['objects'] as $obj) {
            if( $obj['object'] == 'ciniki.musicfestivals.teacher' 
                && isset($rsp['teachers'][$obj['object_id']])
                ) {
                $rsp['teachers'][$obj['object_id']]['added'] = 'yes';
            }
            if( $obj['object'] == 'ciniki.musicfestivals.competitor' 
                && isset($rsp['competitors'][$obj['object_id']])
                ) {
                $rsp['competitors'][$obj['object_id']]['added'] = 'yes';
            }
        }
        //
        // Setup the object field for each teacher and competitor
        //
        foreach($rsp['teachers'] as $tid => $teacher) {
            $rsp['teachers'][$tid]['object'] = 'ciniki.musicfestivals.teacher';
        }
        foreach($rsp['competitors'] as $cid => $competitor) {
            $rsp['competitors'][$cid]['object'] = 'ciniki.musicfestivals.competitor';
        }
    }

    //
    // Sort and unique competitor and teacher ids
    //
    $competitor_ids = array_unique($competitor_ids, SORT_NUMERIC);
    $teacher_ids = array_unique($teacher_ids, SORT_NUMERIC);
    $rsp['message']['num_teachers'] = count($teacher_ids);
    $rsp['message']['num_competitors'] = count($competitor_ids);

    if( $rsp['message']['status'] == 30 ) {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => 'Scheduled For', 'value' => $rsp['message']['dt_scheduled_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => count($teacher_ids)),
            );
    } elseif( $rsp['message']['status'] == 50 ) {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => 'Sent At', 'value' => $rsp['message']['dt_sent_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => count($teacher_ids)),
            );
    } else {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => count($teacher_ids)),
            );
    }

    //
    // Load competitors and teachers names and emails
    //
    if( isset($args['emails']) && $args['emails'] == 'yes' ) {
        //
        // Load 100 competitors at a time so we don't overload SQL limits
        //
        $rsp['message']['competitors'] = array();
        if( count($competitor_ids) > 0 ) {
            for($i = 0; $i < count($competitor_ids); $i+=100) {
                $strsql = "SELECT competitors.id, "
                    . "competitors.name, "
                    . "competitors.email "
                    . "FROM ciniki_musicfestival_competitors AS competitors "
                    . "WHERE competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND competitors.id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_splice($competitor_ids, $i, 100)) . ") "
                    . "ORDER BY competitors.name "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.474', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                if( isset($rc['rows']) ) {
                    foreach($rc['rows'] as $row) {
                        $rsp['message']['competitors'][$row['id']] = $row;
                    }
                }
            }
        }

        //
        // Load the teachers, should always be smaller number than competitors
        //
        $rsp['message']['teachers'] = array();
        if( count($teacher_ids) > 0 ) {
            $strsql = "SELECT customers.id, "
                . "customers.display_name AS name, "
                . "emails.email "
                . "FROM ciniki_customers AS customers "
                . "INNER JOIN ciniki_customer_emails AS emails ON ("
                    . "customers.id = emails.customer_id "
                    . "AND (emails.flags&0x10) = 0 "
                    . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE customers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $teacher_ids) . ") "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY customers.display_name "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.474', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $rsp['message']['teachers'][] = $row;
                }
            }
        }
    }

    return $rsp;
}
?>
