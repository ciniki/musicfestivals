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
    // Load the message details
    //
    $strsql = "SELECT messages.id, "
        . "messages.uuid, "
        . "messages.festival_id, "
        . "messages.subject, "
        . "messages.status, "
        . "messages.mtype, "
        . "messages.flags, "
        . "messages.content, "
        . "messages.files, "
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
                'id', 'uuid', 'festival_id', 'subject', 'status', 'mtype', 'flags', 'content', 'files', 
                'dt_scheduled', 'dt_scheduled_text', 'dt_sent', 'dt_sent_text',
                ),
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
    if( $rsp['message']['files'] != '' ) {
        $rsp['message']['files'] = unserialize($rsp['message']['files']);
    }

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $rsp['message']['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.892', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $tnid, $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
        'students' => array(),
        'teachers' => array(),
        'accompanists' => array(),
        'tags' => array(),
        'statuses' => array(),
        'provincials_statuses' => array(),
        );
    $object_ids = array();
    $competitor_ids = array();
    $teacher_ids = array();
    $accompanist_ids = array();
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.464', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.467', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.512', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.515', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.517', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
        elseif( $ref['object'] == 'ciniki.musicfestivals.registrationtag' ) {
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 70,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Tag',
                'label' => $ref['object_id'],
            );
            $ref_ids['tags'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.registrationstatus' ) {
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 75,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Status',
                'label' => $maps['registration']['status'][$ref['object_id']],
            );
            $ref_ids['statuses'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.registrationprovincialsstatus' ) {
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 76,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Status',
                'label' => $maps['registration']['provincials_status'][$ref['object_id']],
            );
            $ref_ids['provincials_statuses'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.registration' ) {
            $strsql = "SELECT registrations.display_name AS name "
                . "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.534', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Registration');
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 79,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Registration',
                'label' => $label,
            );
            $ref_ids['registrations'][] = $ref['object_id'];
        }
        elseif( $ref['object'] == 'ciniki.musicfestivals.teacher' ) {
            $strsql = "SELECT customers.display_name AS name "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.519', 'msg'=>'Unable to load teacher', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Teacher');
            $teacher_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 70,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Teacher Only',
                'label' => $label,
            );
//            $ref_ids['teachers'][] = $ref['object_id'];
        } 
        elseif( $ref['object'] == 'ciniki.musicfestivals.accompanist' ) {
            $strsql = "SELECT customers.display_name AS name "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.890', 'msg'=>'Unable to load accompanist', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Accompanist');
            $accompanist_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 71,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Accompanist Only',
                'label' => $label,
            );
        } 
        elseif( $ref['object'] == 'ciniki.musicfestivals.students' ) {
            $strsql = "SELECT customers.display_name AS name "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.630', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Teacher');
//            $teacher_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 70,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Teacher & Students',
                'label' => $label,
            );
            $ref_ids['students'][] = $ref['object_id'];
        } 
/*        elseif( $ref['object'] == 'ciniki.musicfestivals.students' ) {
            $strsql = "SELECT customers.display_name AS name "
                . "FROM ciniki_customers AS customers "
                . "WHERE customers.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.631', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
            }
            $label = (isset($rc['item']['name']) ? $rc['item']['name'] : 'Unknown Teacher');
            $teacher_ids[] = $ref['object_id'];
            $rsp['message']['objects'][] = array(
                'id' => $ref['id'],
                'seq' => 75,
                'object' => $ref['object'],
                'object_id' => $ref['object_id'],
                'type' => 'Students',
                'label' => $label,
            );
            $ref_ids['students'][] = $ref['object_id'];
        }  */
        elseif( $ref['object'] == 'ciniki.musicfestivals.competitor' ) {
            $strsql = "SELECT competitors.name "
                . "FROM ciniki_musicfestival_competitors AS competitors "
                . "WHERE competitors.id = '" . ciniki_core_dbQuote($ciniki, $ref['object_id']) . "' " 
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.518', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
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
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
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
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
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
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
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
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE sections.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE divisions.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.scheduletimeslot' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE registrations.timeslot_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.students' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE (registrations.teacher_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                    . "OR registrations.teacher2_customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                    . ") "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.registrationtag' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registration_tags AS tags "
                . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                    . "tags.registration_id = registrations.id "
                    . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE (";
            $or = '';
            foreach($ids as $id) {
                $reg_strsql .= $or . "tags.tag_name = '" . ciniki_core_dbQuote($ciniki, $id) . "' ";
                $or = " OR ";
            }
            $reg_strsql .= ") "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.registrationstatus' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE (";
            $or = '';
            foreach($ids as $id) {
                $reg_strsql .= $or . "registrations.status = '" . ciniki_core_dbQuote($ciniki, $id) . "' ";
                $or = " OR ";
            }
            $reg_strsql .= ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.registrationprovincialsstatus' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE (";
            $or = '';
            foreach($ids as $id) {
                $reg_strsql .= $or . "registrations.provincials_status = '" . ciniki_core_dbQuote($ciniki, $id) . "' ";
                $or = " OR ";
            }
            $reg_strsql .= ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "";
        }
        elseif( $object == 'ciniki.musicfestivals.registration' ) {
            $reg_strsql .= "FROM ciniki_musicfestival_registrations AS registrations "
                . "WHERE (";
            $or = '';
            foreach($ids as $id) {
                $reg_strsql .= $or . "registrations.id = '" . ciniki_core_dbQuote($ciniki, $id) . "' ";
                $or = " OR ";
            }
            $reg_strsql .= ") "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "";
        }

        if( $reg_strsql != '' ) {
            $strsql = "SELECT registrations.id, "
                . "registrations.teacher_customer_id, "
                . "registrations.teacher2_customer_id, "
                . "registrations.parent_customer_id, "
                . "registrations.accompanist_customer_id, "
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
                if( ($rsp['message']['flags']&0x02) == 0x02 && $reg['teacher_customer_id'] > 0 ) {
                    $teacher_ids[] = $reg['teacher_customer_id'];
                }
                if( ($rsp['message']['flags']&0x02) == 0x02 && $reg['teacher2_customer_id'] > 0 ) {
                    $teacher_ids[] = $reg['teacher2_customer_id'];
                }
                if( ($rsp['message']['flags']&0x04) == 0x04 && $reg['accompanist_customer_id'] > 0 ) {
                    $accompanist_ids[] = $reg['accompanist_customer_id'];
                }
                if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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
    // Load the full syllabus, schedule, tags, competitor list and teachers and mark which objects
    // are added, or which classes/categories are auto included in a section
    // and which classes are auto included in each category or section.
    //
    if( isset($args['allrefs']) && $args['allrefs'] == 'yes' ) {
        //
        // Load the full list of competitors
        //
        $strsql = "SELECT DISTINCT competitors.id, "
            . "competitors.name "
            . "FROM ciniki_musicfestival_competitors AS competitors "
            . "WHERE competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
/*            . "AND competitors.id IN ("
                . "SELECT DISTINCT competitor1_id FROM ciniki_musicfestival_registrations "
                    . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "UNION SELECT DISTINCT competitor2_id FROM ciniki_musicfestival_registrations "
                    . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "UNION SELECT DISTINCT competitor3_id FROM ciniki_musicfestival_registrations "
                    . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "UNION SELECT DISTINCT competitor4_id FROM ciniki_musicfestival_registrations "
                    . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "UNION SELECT DISTINCT competitor5_id FROM ciniki_musicfestival_registrations "
                    . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "  */
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
            . "customers.display_name AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
//                . "("
                    . "registrations.teacher_customer_id = customers.id "
//                    . "OR registrations.teacher2_customer_id = customers.id "
//                    . ") "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name')),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.458', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        $rsp['teachers'] = isset($rc['teachers']) ? $rc['teachers'] : array();
        //
        // Load the teacher2 list and merge
        // Note: This is faster than doing an OR in mysql query
        //
        $strsql = "SELECT customers.id, "
            . "customers.display_name AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher2_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name')),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.514', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        // Merge teacher2 list
        if( isset($rc['teachers']) ) {
            foreach($rc['teachers'] as $tid => $teacher) {
                if( !isset($rsp['teachers'][$tid]) ) {
                    $rsp['teachers'][$tid] = $teacher;
                }
            }
        }

        //
        // Process teacher list
        foreach($rsp['teachers'] as $tid => $teacher) {
            $rsp['teachers'][$tid]['object'] = 'ciniki.musicfestivals.teacher';
            //
            // Check if category is automatically included in section
            //
            if( in_array($tid, $ref_ids['students']) ) {
                if( isset($teacher['registrations']) ) {
                    foreach($teacher['registrations'] AS $rid => $reg) {
                        if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                            if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher_customer_id']]['students'] = 'yes';
                            }
                            if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher2_customer_id']]['students'] = 'yes';
                            }
                        }
//                        if( ($rsp['message']['flags']&0x04) == 0x04
//                            && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
//                            ) {
//                            $rsp['accompanists'][$reg['accompanist_customer_id']]['students'] = 'yes';
//                        }
                        if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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

        //
        // Load the full list of accompanists
        //
        $strsql = "SELECT customers.id, "
            . "customers.display_name AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.accompanist_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accompanists', 'fname'=>'id', 'fields'=>array('id', 'name')),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'accompanist_customer_id', 
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.891', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
        }
        $rsp['accompanists'] = isset($rc['accompanists']) ? $rc['accompanists'] : array();
        foreach($rsp['accompanists'] as $aid => $accompanist) {
            $rsp['accompanists'][$aid]['object'] = 'ciniki.musicfestivals.accompanist';
            //
            // Check if category is automatically included in section
            //
            if( in_array($aid, $ref_ids['students']) ) {
                if( isset($accompanist['registrations']) ) {
                    foreach($accompanist['registrations'] AS $rid => $reg) {
//                        if( ($rsp['message']['flags']&0x02) == 0x02
//                            && isset($rsp['teachers'][$reg['teacher_customer_id']]) 
//                            ) {
//                            $rsp['teachers'][$reg['teacher_customer_id']]['students'] = 'yes';
//                        }
                        if( ($rsp['message']['flags']&0x04) == 0x04 
                            && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                            ) {
                            $rsp['accompanists'][$reg['accompanist_customer_id']]['students'] = 'yes';
                        }
                        if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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
            . "registrations.teacher2_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "classes.id = registrations.class_id "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
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
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id', 
                    'accompanist_customer_id', 'parent_customer_id',
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
                                    if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                                        if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                            $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                                        }
                                        if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                            $rsp['teachers'][$reg['teacher2_customer_id']]['included'] = 'yes';
                                        }
                                    }
                                    if( ($rsp['message']['flags']&0x04) == 0x04 
                                        && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                                        ) {
                                        $rsp['accompanists'][$reg['accompanist_customer_id']]['included'] = 'yes';
                                    }
                                    if( ($rsp['message']['flags']&0x01) == 0x01 ) {
                                        for($i = 1; $i <= 5; $i++) {
                                            if( isset($rsp['competitors'][$reg["competitor{$i}_id"]]) ) {
                                                $rsp['competitors'][$reg["competitor{$i}_id"]]['included'] = 'yes';
                                            }
                                        }
                                    }
                                }
                            }
                            //
                            // Update all competitors who are registered in a class
                            //
                            if( isset($class['registrations']) ) {
                                foreach($class['registrations'] AS $rid => $reg) {
                                    for($i = 1; $i <= 5; $i++) {
                                        if( isset($rsp['competitors'][$reg["competitor{$i}_id"]]) ) {
                                            $rsp['competitors'][$reg["competitor{$i}_id"]]['registered'] = 'yes';
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
        // Filter out competitors who have not registered
        //
        foreach($rsp['competitors'] as $cid => $competitor) {
            if( !isset($competitor['registered']) ) {
                unset($competitor[$cid]);
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
            . "registrations.teacher2_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sections.sequence, sections.name, "
                . "divisions.division_date, "
                . "divisions.name, "
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
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id', 
                    'parent_customer_id', 'accompanist_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.513', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
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
                                    if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                                        if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                            $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                                        }
                                        if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                            $rsp['teachers'][$reg['teacher2_customer_id']]['included'] = 'yes';
                                        }
                                    }
                                    if( ($rsp['message']['flags']&0x04) == 0x04
                                        && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                                        ) {
                                        $rsp['accompanists'][$reg['accompanist_customer_id']]['included'] = 'yes';
                                    }
                                    if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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
        // Load the tags and registrations
        //
        $strsql = "SELECT tags.tag_name AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registration_tags AS tags "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "tags.registration_id = registrations.id "
                . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY tags.tag_name, registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tags', 'fname'=>'name', 'fields'=>array('id'=>'name', 'name')),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id',
                    'parent_customer_id', 'accompanist_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.559', 'msg'=>'Unable to load registration tag list', 'err'=>$rc['err']));
        }
        $rsp['tags'] = isset($rc['tags']) ? $rc['tags'] : array();
        foreach($rsp['tags'] as $tid => $tag) {
            $rsp['tags'][$tid]['object'] = 'ciniki.musicfestivals.registrationtag';
            //
            // Check if category is automatically included in section
            //
            if( in_array($tid, $ref_ids['tags']) ) {
                $rsp['tags'][$tid]['added'] = 'yes';
                if( isset($tag['registrations']) ) {
                    foreach($tag['registrations'] AS $rid => $reg) {
                        if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                            if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                            }
                            if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher2_customer_id']]['included'] = 'yes';
                            }
                        }
                        if( ($rsp['message']['flags']&0x04) == 0x04
                            && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                            ) {
                            $rsp['accompanists'][$reg['accompanist_customer_id']]['included'] = 'yes';
                        }
                        if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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

        //
        // Load the statuses and registrations
        //
        $strsql = "SELECT registrations.status AS id, "
            . "registrations.status AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY registrations.status, registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'statuses', 'fname'=>'id', 
                'fields'=>array('id', 'name'),
                'maps'=>array('name'=>$maps['registration']['status']),
                ),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id', 
                    'parent_customer_id', 'accompanist_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.107', 'msg'=>'Unable to load registration status list', 'err'=>$rc['err']));
        }
        $rsp['statuses'] = isset($rc['statuses']) ? $rc['statuses'] : array();
        foreach($rsp['statuses'] as $sid => $status) {
            $rsp['statuses'][$sid]['object'] = 'ciniki.musicfestivals.registrationstatus';
            //
            // Check if category is automatically included in section
            //
            if( in_array($sid, $ref_ids['statuses']) ) {
                $rsp['statuses'][$sid]['added'] = 'yes';
                if( isset($status['registrations']) ) {
                    foreach($status['registrations'] AS $rid => $reg) {
                        if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                            if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                            }
                            if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher2_customer_id']]['included'] = 'yes';
                            }
                        }
                        if( ($rsp['message']['flags']&0x04) == 0x04
                            && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                            ) {
                            $rsp['accompanists'][$reg['accompanist_customer_id']]['included'] = 'yes';
                        }
                        if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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

        //
        // Load the provincials statuses and registrations
        //
        $strsql = "SELECT registrations.provincials_status AS id, "
            . "registrations.provincials_status AS name, "
            . "registrations.id AS reg_id, "
            . "registrations.teacher_customer_id, "
            . "registrations.teacher2_customer_id, "
            . "registrations.parent_customer_id, "
            . "registrations.accompanist_customer_id, "
            . "registrations.competitor1_id, "
            . "registrations.competitor2_id, "
            . "registrations.competitor3_id, "
            . "registrations.competitor4_id, "
            . "registrations.competitor5_id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND registrations.provincials_status > 0 "
            . "ORDER BY registrations.provincials_status, registrations.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'provincials_statuses', 'fname'=>'id', 
                'fields'=>array('id', 'name'),
                'maps'=>array('name'=>$maps['registration']['provincials_status']),
                ),
            array('container'=>'registrations', 'fname'=>'reg_id', 
                'fields'=>array('id'=>'reg_id', 'teacher_customer_id', 'teacher2_customer_id', 
                    'parent_customer_id', 'accompanist_customer_id',
                    'competitor1_id', 'competitor2_id', 'competitor3_id', 
                    'competitor4_id', 'competitor5_id',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.456', 'msg'=>'Unable to load registration status list', 'err'=>$rc['err']));
        }
        $rsp['provincials_statuses'] = isset($rc['provincials_statuses']) ? $rc['provincials_statuses'] : array();
        foreach($rsp['provincials_statuses'] as $sid => $status) {
            $rsp['provincials_statuses'][$sid]['object'] = 'ciniki.musicfestivals.registrationprovincialsstatus';
            //
            // Check if category is automatically included in section
            //
            if( in_array($sid, $ref_ids['provincials_statuses']) ) {
                $rsp['provincials_statuses'][$sid]['added'] = 'yes';
                if( isset($status['registrations']) ) {
                    foreach($status['registrations'] AS $rid => $reg) {
                        if( ($rsp['message']['flags']&0x02) == 0x02 ) {
                            if( $reg['teacher_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher_customer_id']]['included'] = 'yes';
                            }
                            if( $reg['teacher2_customer_id'] > 0 && isset($rsp['teachers'][$reg['teacher2_customer_id']]) ) {
                                $rsp['teachers'][$reg['teacher2_customer_id']]['included'] = 'yes';
                            }
                        }
                        if( ($rsp['message']['flags']&0x04) == 0x04
                            && isset($rsp['accompanists'][$reg['accompanist_customer_id']]) 
                            ) {
                            $rsp['accompanists'][$reg['accompanist_customer_id']]['included'] = 'yes';
                        }
                        if( ($rsp['message']['flags']&0x01) == 0x01 ) {
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


        //
        // Check the objects for teacher and competitor direct adds
        //
        foreach($rsp['message']['objects'] as $obj) {
            if( $obj['object'] == 'ciniki.musicfestivals.teacher' 
                && isset($rsp['teachers'][$obj['object_id']])
                ) {
                $rsp['teachers'][$obj['object_id']]['added'] = 'yes';
            }
            if( $obj['object'] == 'ciniki.musicfestivals.accompanist' 
                && isset($rsp['accompanists'][$obj['object_id']])
                ) {
                $rsp['accompanists'][$obj['object_id']]['added'] = 'yes';
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
        foreach($rsp['accompanists'] as $aid => $accompanist) {
            $rsp['accompanists'][$aid]['object'] = 'ciniki.musicfestivals.accompanist';
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
    $accompanist_ids = array_unique($accompanist_ids, SORT_NUMERIC);
    $rsp['message']['num_teachers'] = $rsp['message']['mtype'] == 10 ? count($teacher_ids) : 0;
    $rsp['message']['num_accompanists'] = $rsp['message']['mtype'] == 10 ? count($accompanist_ids) : 0;
    $rsp['message']['num_competitors'] = count($competitor_ids);

    if( $rsp['message']['status'] == 30 ) {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => 'Scheduled For', 'value' => $rsp['message']['dt_scheduled_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => $rsp['message']['num_teachers']),
            );
    } elseif( $rsp['message']['status'] == 50 ) {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => 'Sent At', 'value' => $rsp['message']['dt_sent_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => $rsp['message']['num_teachers']),
            );
    } else {
        $rsp['message']['details'] = array(
            array('label' => 'Status', 'value' => $rsp['message']['status_text']),
            array('label' => '# Competitors', 'value' => count($competitor_ids)),
            array('label' => '# Teachers', 'value' => $rsp['message']['num_teachers']),
            );
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x8000) ) {
        $rsp['message']['details'][] = array('label' => '# Accompanists', 'value' => $rsp['message']['num_accompanists']);
    }

    //
    // Load competitors and teachers and accompanists names and emails
    //
    if( (isset($args['emails']) && $args['emails'] == 'yes')
        || (isset($args['emaillist']) && $args['emaillist'] == 'yes') 
        ) {
        //
        // Load 100 competitors at a time so we don't overload SQL limits
        //
        $rsp['message']['emails'] = array();
        $rsp['message']['customers'] = array();
        $rsp['message']['competitors'] = array();
        if( count($competitor_ids) > 0 ) {
//            for($i = 0; $i < count($competitor_ids); $i+=100) {
            while(count($competitor_ids) > 0 ) {
                $ids = array_splice($competitor_ids, 0, 100);
                $strsql = "SELECT competitors.id, "
                    . "competitors.name, "
                    . "IF(competitors.ctype=50,competitors.name,competitors.first) AS first, "
                    . "competitors.email "
                    . "FROM ciniki_musicfestival_competitors AS competitors "
                    . "WHERE competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                    . "AND competitors.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                    . "ORDER BY competitors.name "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.516', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                }
                if( isset($rc['rows']) ) {
                    foreach($rc['rows'] as $row) {
                        $rsp['message']['competitors'][$row['id']] = $row;
                        if( isset($args['emaillist']) && $args['emaillist'] == 'yes' ) {
                            $idx = strtolower($row['email']);
                            if( $rsp['message']['mtype'] == 40 ) {
                                $idx = strtolower($row['name']) . '-' . strtolower($row['email']);
                            }
                            if( !isset($rsp['message']['emails'][$idx]) ) {
                                $rsp['message']['emails'][$idx] = array(
                                    'name' => $row['name'],
                                    'email' => $row['email'],
                                    );
                            }
                        }
                    }
                }
                //
                // Get the billing_customer_id 
                //
                if( $rsp['message']['mtype'] == 10 ) {
                    $strsql = "SELECT customers.id, "
                        . "customers.display_name AS name, "
                        . "customers.first, "
                        . "emails.email "
                        . "FROM ciniki_musicfestival_registrations AS registrations " 
                        . "INNER JOIN ciniki_customers AS customers ON ("
                            . "registrations.billing_customer_id = customers.id "
                            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . ") "
                        . "INNER JOIN ciniki_customer_emails AS emails ON ("
                            . "customers.id = emails.customer_id "
                            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . ") "
                        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                            . "AND registrations.billing_customer_id > 0 "
                            . "AND ("
                                . "registrations.competitor1_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor2_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor3_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor4_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor5_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                            . ") "
                        . "ORDER BY customers.display_name "
                        . "";
                    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.457', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                    }
                    if( isset($rc['rows']) ) {
                        foreach($rc['rows'] as $row) {
                            $rsp['message']['customers'][$row['id']] = $row;
                            if( isset($args['emaillist']) && $args['emaillist'] == 'yes' ) {
                                $row['email'] = strtolower($row['email']);
                                if( !isset($rsp['message']['emails'][$row['email']]) ) {
                                    $rsp['message']['emails'][$row['email']] = array(
                                        'name' => $row['name'],
                                        'email' => $row['email'],
                                        );
                                }
                            }
                        }
                    }
                }

                // 
                // Get any parent emails linked via registrations.parent_customer_id
                //
                if( $rsp['message']['mtype'] == 10 ) {
                    $strsql = "SELECT customers.id, "
                        . "customers.display_name AS name, "
                        . "customers.first, "
                        . "emails.email "
                        . "FROM ciniki_musicfestival_registrations AS registrations " 
                        . "INNER JOIN ciniki_customers AS customers ON ("
                            . "registrations.parent_customer_id = customers.id "
                            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . ") "
                        . "INNER JOIN ciniki_customer_emails AS emails ON ("
                            . "customers.id = emails.customer_id "
                            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . ") "
                        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $rsp['message']['festival_id']) . "' "
                            . "AND registrations.parent_customer_id > 0 "
                            . "AND ("
                                . "registrations.competitor1_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor2_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor3_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor4_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                                . "OR registrations.competitor5_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ids) . ") "
                            . ") "
                        . "ORDER BY customers.display_name "
                        . "";
                    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.137', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                    }
                    if( isset($rc['rows']) ) {
                        foreach($rc['rows'] as $row) {
                            if( !isset($rsp['message']['customers'][$row['id']]) ) {
                                $rsp['message']['customers'][$row['id']] = $row;
                            }
                            if( isset($args['emaillist']) && $args['emaillist'] == 'yes' ) {
                                $row['email'] = strtolower($row['email']);
                                if( !isset($rsp['message']['emails'][$row['email']]) ) {
                                    $rsp['message']['emails'][$row['email']] = array(
                                        'name' => $row['name'],
                                        'email' => $row['email'],
                                        );
                                }
                            }
                        }
                    }
                }
            }
        }

        //
        // Load the teachers, should always be smaller number than competitors
        //
        $rsp['message']['teachers'] = array();
        if( $rsp['message']['mtype'] == 10 && count($teacher_ids) > 0 ) {
            $strsql = "SELECT customers.id, "
                . "customers.first AS first, "
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
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.474', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $rsp['message']['teachers'][] = $row;
                    if( isset($args['emaillist']) && $args['emaillist'] == 'yes' ) {
                        if( !isset($rsp['message']['emails'][$row['email']]) ) {
                            $row['email'] = strtolower($row['email']);
                            $rsp['message']['emails'][$row['email']] = array(
                                'name' => $row['name'],
                                'email' => $row['email'],
                                );
                        }
                    }
                }
            }
        }

        //
        // Load the accompanists, should always be smaller number than competitors
        //
        $rsp['message']['accompanists'] = array();
        if( $rsp['message']['mtype'] == 10 && count($accompanist_ids) > 0 ) {
            $strsql = "SELECT customers.id, "
                . "customers.first AS first, "
                . "customers.display_name AS name, "
                . "emails.email "
                . "FROM ciniki_customers AS customers "
                . "INNER JOIN ciniki_customer_emails AS emails ON ("
                    . "customers.id = emails.customer_id "
                    . "AND (emails.flags&0x10) = 0 "
                    . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE customers.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $accompanist_ids) . ") "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY customers.display_name "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.886', 'msg'=>'Unable to load accompanists', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $rsp['message']['accompanists'][] = $row;
                    if( isset($args['emaillist']) && $args['emaillist'] == 'yes' ) {
                        if( !isset($rsp['message']['emails'][$row['email']]) ) {
                            $row['email'] = strtolower($row['email']);
                            $rsp['message']['emails'][$row['email']] = array(
                                'name' => $row['name'],
                                'email' => $row['email'],
                                );
                        }
                    }
                }
            }
        }

        if( isset($args['emaillist']) && $args['emaillist'] == 'yes' && $rsp['message']['mtype'] == 10 ) {
            uasort($rsp['message']['emails'], function($a, $b) {
                $x = explode('@', $a['email']);
                $adomain = isset($x[1]) ? $x[1] : '';
                $y = explode('@', $b['email']);
                $bdomain = isset($y[1]) ? $y[1] : '';
                
                return strcmp($adomain, $bdomain);
                });
        }
        elseif( isset($args['emaillist']) && $args['emaillist'] == 'yes' && $rsp['message']['mtype'] == 40 ) {
            uasort($rsp['message']['emails'], function($a, $b) {
                return strcmp($a['name'], $b['name']);
                });
        }
    }

    return $rsp;
}
?>
