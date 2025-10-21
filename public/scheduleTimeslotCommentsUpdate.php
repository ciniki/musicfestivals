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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

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
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
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
//            . "AND timeslots.class1_id > 0 "
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
                'mark', 'placement', 'level', 'comments', 'class_name', 
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
            if( isset($ciniki['request']['args']['level_' . $registration['id']])
                && $ciniki['request']['args']['level_' . $registration['id']] != $registration['level'] 
                ) {
                $update_args['level'] = $ciniki['request']['args']['level_' . $registration['id']];
            }
            if( count($update_args) > 0 ) {
                //
                // Update the comments for the registration
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
