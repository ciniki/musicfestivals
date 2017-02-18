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
// business_id:         The ID of the business the schedule time slot is attached to.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduletimeslot_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Time Slot'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['business_id'], 'ciniki.musicfestivals.scheduleTimeslotGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
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
    // Return default for new Schedule Time Slot
    //
    if( $args['scheduletimeslot_id'] == 0 ) {
        $scheduletimeslot = array('id'=>0,
            'festival_id'=>'',
            'sdivision_id'=>(isset($args['sdivision_id']) ? $args['sdivision_id'] : 0),
            'slot_time'=>'',
            'class_id'=>(isset($args['class_id']) ? $args['class_id'] : 0),
            'name'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Time Slot
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_schedule_timeslots.id, "
            . "ciniki_musicfestival_schedule_timeslots.festival_id, "
            . "ciniki_musicfestival_schedule_timeslots.sdivision_id, "
            . "TIME_FORMAT(ciniki_musicfestival_schedule_timeslots.slot_time, '%h:%i %p') AS slot_time, "
            . "ciniki_musicfestival_schedule_timeslots.class_id, "
            . "ciniki_musicfestival_schedule_timeslots.name, "
            . "ciniki_musicfestival_schedule_timeslots.description "
            . "FROM ciniki_musicfestival_schedule_timeslots "
            . "WHERE ciniki_musicfestival_schedule_timeslots.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_musicfestival_schedule_timeslots.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduletimeslot_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduletimeslot', 'fname'=>'id', 
                'fields'=>array('festival_id', 'sdivision_id', 'slot_time', 'class_id', 'name', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.98', 'msg'=>'Schedule Time Slot not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduletimeslot'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.99', 'msg'=>'Unable to find Schedule Time Slot'));
        }
        $scheduletimeslot = $rc['scheduletimeslot'][0];
    }

    $rsp = array('stat'=>'ok', 'scheduletimeslot'=>$scheduletimeslot);

    //
    // Get the list of divisions
    //
    $strsql = "SELECT divisions.id, CONCAT_WS(' - ', sections.name, divisions.name) AS name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections, ciniki_musicfestival_schedule_divisions AS divisions "
        . "WHERE sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.id = divisions.ssection_id "
        . "AND divisions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sections.name, divisions.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.100', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['divisions']) ) {
        $rsp['scheduledivisions'] = $rc['divisions'];
    }

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name, "
        . "FORMAT(classes.fee, 2) AS fee, "
        . "COUNT(registrations.id) AS num_registrations "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND sections.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "GROUP BY classes.id "
        . "ORDER BY num_registrations DESC, sections.name, classes.code "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'fee', 'num_registrations')),
//        array('container'=>'registrations', 'fname'=>'registration_id', 'fields'=>array('id'=>'registration_id, 'name', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
        foreach($rsp['classes'] as $cid => $class) {
            if( $class['num_registrations'] > 0 ) {
                $rsp['classes'][$cid]['name'] .= ' (' . $class['num_registrations'] . ')';
            }
        }
    }

    return $rsp;
}
?>
