<?php
//
// Description
// ===========
// This method will return all the information about an schedule division.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule division is attached to.
// scheduledivision_id:          The ID of the schedule division to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleDivisionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'scheduledivision_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Division'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleDivisionGet');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

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
    // Return default for new Schedule Division
    //
    if( $args['scheduledivision_id'] == 0 ) {
        $scheduledivision = array('id'=>0,
            'festival_id'=>'',
            'ssection_id'=>(isset($args['ssection_id']) ? $args['ssection_id'] : 0),
            'location_id'=>0,
            'adjudicator_id'=>0,
            'name'=>'',
            'flags' => 0,
            'division_date'=>'',
            'address'=>'',
            'results_notes'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Division
    //
    else {
        $strsql = "SELECT divisions.id, "
            . "divisions.festival_id, "
            . "divisions.ssection_id, "
            . "divisions.location_id, "
            . "divisions.adjudicator_id, "
            . "divisions.name, "
            . "divisions.shortname, "
            . "divisions.flags, "
            . "divisions.division_date, "
            . "divisions.address, "
            . "divisions.results_notes, "
            . "divisions.results_video_url "
            . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
            . "WHERE divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'scheduledivisions', 'fname'=>'id', 
                'fields'=>array('festival_id', 'ssection_id', 'name', 'shortname', 'flags', 'division_date', 
                    'address', 'adjudicator_id', 'location_id', 'results_notes', 'results_video_url',
                    ),
                'utctotz'=>array('division_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.93', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['scheduledivisions'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.94', 'msg'=>'Unable to find Schedule Division'));
        }
        $scheduledivision = $rc['scheduledivisions'][0];

        //
        // Load the timeslots
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'scheduleTimeslotsLoad');
        $rc = ciniki_musicfestivals_scheduleTimeslotsLoad($ciniki, $args['tnid'], [
            'festival' => $festival,
            'division_id' => $args['scheduledivision_id'],
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $scheduledivision['timeslots'] = $rc['timeslots'];

        //
        // Get the list of results
        //
        $strsql = "SELECT timeslots.id AS timeslot_id, "
            . "timeslots.groupname, "
            . "timeslots.start_num, ";
            
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
            $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
        } else {
            $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
        }
        $strsql .= "registrations.id, "
            . "registrations.status, "
            . "registrations.display_name, "
            . "registrations.timeslot_sequence, "
            . "registrations.flags, "
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
            . "IF((timeslots.flags&0x02)=0x02, registrations.finals_mark, registrations.mark) AS mark, "
            . "IF((timeslots.flags&0x02)=0x02, registrations.finals_placement, registrations.placement) AS placement, "
            . "IF((timeslots.flags&0x02)=0x02, registrations.finals_level, registrations.level) AS level, "
            . "registrations.provincials_status, "
            . "registrations.provincials_position, "
            . "classes.flags AS class_flags, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "("
                    . "((timeslots.flags&0x02) = 0 && timeslots.id = registrations.timeslot_id) "
                    . "OR ((timeslots.flags&0x02) = 0x02 && timeslots.id = registrations.finals_timeslot_id) "
                    . ") "
            . "AND registrations.status <> 70 " // Disqualified
            . "AND registrations.status <> 75 " // Withdrawn
            . "AND registrations.status <> 80 " // Cancelled
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            . "WHERE timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND timeslots.sdivision_id = '" . ciniki_core_dbQuote($ciniki, $args['scheduledivision_id']) . "' "
            . "AND timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "ORDER BY timeslots.slot_time, timeslots.name, timeslots.id, registrations.timeslot_sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'results', 'fname'=>'id', 
                'fields'=>array('id', 'timeslot_id', 'groupname', 'start_num', 
                    'status', 'display_name', 'slot_time_text', 'timeslot_sequence', 'flags',
                    'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                    'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8', 
                    'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8', 
                    'mark', 'placement', 'level', 'provincials_status', 'provincials_position',
                    'class_code', 'class_name', 'class_flags', 'category_name', 'section_name',
                    ),
                'maps'=>array(
                    'provincials_status' => $maps['registration']['provincials_status'],
                    'provincials_position' => $maps['registration']['provincials_position'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.63', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
        }
        $scheduledivision['division_results'] = isset($rc['results']) ? $rc['results'] : array();
        foreach($scheduledivision['division_results'] as $sid => $result) {
            $scheduledivision['division_results'][$sid]['timeslot_number'] = $result['timeslot_sequence'];
            if( $result['start_num'] > 1 ) {
                $scheduledivision['division_results'][$sid]['timeslot_number'] += ($result['start_num'] - 1);
            }
            if( $result['status'] == 77 ) {
                $scheduledivision['division_results'][$sid]['mark'] .= ($result['mark'] != '' ? ' - ' : '') . 'No Show';
            }
            $titles = '';
            for($i = 1; $i <= 8; $i++) {
                if( $result["title{$i}"] != '' ) {
                    $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $result, $i);
                    if( isset($rc['title']) ) {
                        $titles .= ($titles != '' ? '<br/>' : '') . $rc['title'];
                    }
                }
            }
            $scheduledivision['division_results'][$sid]['titles'] = $titles;
        }
    }

    $rsp = array('stat'=>'ok', 'scheduledivision'=>$scheduledivision);

    //
    // Get the list of sections
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_musicfestival_schedule_sections "
        . "WHERE ciniki_musicfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_musicfestival_schedule_sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY sequence, name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.100', 'msg'=>'Schedule Division not found', 'err'=>$rc['err']));
    }
    if( isset($rc['sections']) ) {
        $rsp['schedulesections'] = $rc['sections'];
    }

    //
    // Get the list of locations
    //
    $strsql = "SELECT locations.id, "
        . "IF(locations.shortname <> '', locations.shortname, locations.name) AS name "
        . "FROM ciniki_musicfestival_locations AS locations "
        . "WHERE locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND locations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY locations.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'locations', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['locations'] = isset($rc['locations']) ? $rc['locations'] : array();

    //
    // Get the list of adjudicators
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "customers.display_name "
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "ORDER BY customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['adjudicators'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    return $rsp;
}
?>
