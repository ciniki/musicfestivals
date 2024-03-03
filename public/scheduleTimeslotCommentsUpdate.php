<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleTimeslotCommentsUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'timeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotCommentsUpdate');
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
    // Get the adjudicators for the timeslot
    //
    $strsql = "SELECT adjudicators.id, adjudicators.customer_id "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
            . "divisions.ssection_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
            . "(sections.adjudicator1_id = adjudicators.id "
                . "OR sections.adjudicator2_id = adjudicators.id "
                . "OR sections.adjudicator3_id = adjudicators.id "
                . ") "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.166', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    //
    // Get the registrations for the timeslot
    //
    $strsql = "SELECT "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.uuid AS timeslot_uuid, "
        . "IF(timeslots.name='', IFNULL(classes.name, ''), timeslots.name) AS timeslot_name, "
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
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
//        . "registrations.placement, "
/*        . "IFNULL(comments.adjudicator_id, 0) AS adjudicator_id, "
        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, " */
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.comments, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY slot_time, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'public_name', 'title1', 
                'video_url1', 'video_url2', 'video_url3', 
                'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3',
                'mark', 'placement', 'comments', 'class_name', 
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $timeslot = isset($rc['timeslots'][$args['timeslot_id']]) ? $rc['timeslots'][$args['timeslot_id']] : array();

    if( isset($timeslot['registrations']) ) {
        foreach($timeslot['registrations'] as $rid => $registration) {
            $update_args = array();
            if( isset($ciniki['request']['args']['comments_' . $registration['id']])
                && $ciniki['request']['args']['comments_' . $registration['id']] != $registration['comments'] 
                ) {
                $update_args['comments'] = $ciniki['request']['args']['comments_' . $registration['id']];
            }
            if( isset($ciniki['request']['args']['mark_' . $registration['id']])
                && $ciniki['request']['args']['mark_' . $registration['id']] != $registration['mark'] 
                ) {
                $update_args['mark'] = $ciniki['request']['args']['mark_' . $registration['id']];
            }
            if( isset($ciniki['request']['args']['placement_' . $registration['id']])
                && $ciniki['request']['args']['placement_' . $registration['id']] != $registration['placement'] 
                ) {
                $update_args['placement'] = $ciniki['request']['args']['placement_' . $registration['id']];
            }
            if( count($update_args) > 0 ) {
                //
                // Update the comments for the adjudicator
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.registration', $registration['id'], $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.167', 'msg'=>'Unable to update the comment'));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
