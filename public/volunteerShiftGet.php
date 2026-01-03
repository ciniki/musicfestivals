<?php
//
// Description
// ===========
// This method will return all the information about an shift.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the shift is attached to.
// shift_id:          The ID of the shift to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerShiftGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'shift_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shift'),
        'division_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteerShiftGet');
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

    //
    // Return default for new Shift
    //
    if( $args['shift_id'] == 0 ) {
        $shift = array('id'=>0,
            'festival_id'=>'',
            'shift_date'=>'',
            'start_time'=>'',
            'end_time'=>'',
            'object'=>'',
            'object_id'=>'',
            'role'=>'',
            'flags'=>0x01,
            'min_volunteers'=>'1',
            'max_volunteers'=>'1',
        );

        // 
        // Check if division specified, and lookup specifics
        //
        if( isset($args['division_id']) && $args['division_id'] > 0 ) {
            $strsql = "SELECT DATE_FORMAT(divisions.division_date, '%a, %b %e, %Y') AS division_date, "
                . "divisions.location_id "
                . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                . "WHERE divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['division_id']) . "' "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'division');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1253', 'msg'=>'Unable to load division', 'err'=>$rc['err']));
            }
            if( !isset($rc['division']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1254', 'msg'=>'Unable to find requested division'));
            }
            $division = $rc['division'];
            $shift['shift_date'] = $division['division_date'];
            if( $division['location_id'] > 0 ) {
                $shift['location'] = 'ciniki.musicfestivals.location:' . $division['location_id'];
            }
        }
    }

    //
    // Get the details for an existing Shift
    //
    else {
        $strsql = "SELECT shifts.id, "
            . "shifts.festival_id, "
            . "shifts.shift_date, "
            . "TIME_FORMAT(shifts.start_time, '%l:%i %p') AS start_time,"
            . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time,"
            . "shifts.object, "
            . "shifts.object_id, "
            . "shifts.role, "
            . "shifts.flags, "
            . "shifts.min_volunteers, "
            . "shifts.max_volunteers "
            . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
            . "WHERE shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND shifts.id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'shifts', 'fname'=>'id', 
                'fields'=>array('festival_id', 'shift_date', 'start_time', 'end_time', 
                    'object', 'object_id', 'role', 'flags', 'min_volunteers', 'max_volunteers',
                    ),
                'utctotz'=>array('shift_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1238', 'msg'=>'Shift not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['shifts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1239', 'msg'=>'Unable to find Shift'));
        }
        $shift = $rc['shifts'][0];
        if( $shift['object'] != '' ) {
            $shift['location'] = $shift['object'] . ':' . $shift['object_id'];
        } else {
            $shift['location'] = '';
        }

        //
        // Load the existing assignments
        //
        $strsql = "SELECT assignments.id, "
            . "assignments.uuid, "
            . "assignments.volunteer_id, "
            . "assignments.status, "
            . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS name "
            . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
            . "INNER JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                . "assignments.volunteer_id = volunteers.id "
                . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE assignments.shift_id = '" . ciniki_core_dbQuote($ciniki, $args['shift_id']) . "' "
            . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'assignments', 'fname'=>'volunteer_id', 
                'fields'=>array('id', 'uuid', 'volunteer_id', 'status', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1250', 'msg'=>'Unable to load assignments', 'err'=>$rc['err']));
        }
        $shift['assignments'] = isset($rc['assignments']) ? $rc['assignments'] : array();
        $i = 1;
        foreach($shift['assignments'] as $assignment) {
            $shift['volunteer_' . $i] = $assignment['volunteer_id'];
            $shift['volunteer_' . $i . '_status'] = $assignment['status'];
            $i++;
        }
        for(;$i <= $shift['max_volunteers']; $i++) {
            $shift['volunteer_' . $i] = 0;
            $shift['volunteer_' . $i . '_status'] = 10;
        }
    }

    $rsp = array('stat'=>'ok', 'shift'=>$shift);


    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'locationsLoad');
    $rc = ciniki_musicfestivals_locationsLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['locations'] = $rc['locations'];
    array_unshift($rsp['locations'], ['id' => 0, 'name' => '']);

    //
    // Get the list of volunteers
    //
    $strsql = "SELECT volunteers.id, "
        . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS name "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "volunteers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1247', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
    }
    $rsp['volunteers'] = isset($rc['volunteers']) ? $rc['volunteers'] : array();

    return $rsp;
}
?>
