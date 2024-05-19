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
function ciniki_musicfestivals_scheduleTimeslotCommentsGet($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleTimeslotCommentsGet');
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
    $strsql = "SELECT adjudicators.id, adjudicators.customer_id, customers.display_name "
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
                . "OR divisions.adjudicator_id = adjudicators.id "
                . ") "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['timeslot_id']) . "' "
        . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.203', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
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
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
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
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.music_orgfilename4, "
        . "registrations.music_orgfilename5, "
        . "registrations.music_orgfilename6, "
        . "registrations.music_orgfilename7, "
        . "registrations.music_orgfilename8, "
//        . "IFNULL(ssections.adjudicator_id, 0) AS adjudicator_id, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.comments, "
/*        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, " */
        . "classes.flags AS reg_flags, "
        . "registrations.participation AS participation, "
        . "classes.min_titles AS min_titles, "
        . "classes.max_titles AS max_titles, "
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
//            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY slot_time, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 
                'reg_flags', 'participation', 'min_titles', 'max_titles',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8', 
                'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3', 'music_orgfilename4', 
                'music_orgfilename5', 'music_orgfilename6', 'music_orgfilename7', 'music_orgfilename8',
                'mark', 'placement', 'level', 'comments', 'class_name',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $timeslot = isset($rc['timeslots'][0]) ? $rc['timeslots'][0] : array();

    if( isset($timeslot['registrations']) ) {
        foreach($timeslot['registrations'] as $rid => $registration) {
            $timeslot['mark_' . $registration['id']] = $registration['mark'];
            $timeslot['placement_' . $registration['id']] = $registration['placement'];
            $timeslot['level_' . $registration['id']] = $registration['level'];
            $timeslot['comments_' . $registration['id']] = $registration['comments'];
            for($i = 1; $i <= 8; $i++) {
                $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $registration, $i);
                $timeslot['registrations'][$rid]["title{$i}"] = $rc['title'];
            }
        }
    }
/*    if( isset($timeslot['registrations']) ) {
            foreach($adjudicators as $aid => $adjudicator) {
                $timeslot['registrations'][$rid]['comments_' . $adjudicator['id']] = '';
                $timeslot['registrations'][$rid]['grade_' . $adjudicator['id']] = '';
                $timeslot['registrations'][$rid]['score_' . $adjudicator['id']] = '';
//                $timeslot['registrations'][$rid]['placement_' . $adjudicator['id']] = '';
                if( isset($registration['comments']) ) {
                    foreach($registration['comments'] as $comment) {
                        if( $comment['adjudicator_id'] == $adjudicator['id'] ) {
                            $timeslot['comments_' . $registration['id'] . '_' . $adjudicator['id']] = $comment['comments'];
                            $timeslot['grade_' . $registration['id'] . '_' . $adjudicator['id']] = $comment['grade'];
                            $timeslot['score_' . $registration['id'] . '_' . $adjudicator['id']] = $comment['score'];
//                            $timeslot['placement_' . $registration['id'] . '_' . $adjudicator['id']] = $registration['placement'];
                        }
                    }
                }
                
            }
        }
    }
*/
    return array('stat'=>'ok', 'timeslot'=>$timeslot, 'adjudicators'=>$adjudicators);
}
?>
