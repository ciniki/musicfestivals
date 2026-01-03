<?php
//
// Description
// ===========
// This function will return the volunteer and all their details
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the volunteer is attached to.
// volunteer_id:          The ID of the volunteer to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteerLoad($ciniki, $tnid, $args) {

    $date_format = 'D, M j, Y';

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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    $now = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Load the volunteer
    //
    $strsql = "SELECT volunteers.id, "
        . "volunteers.festival_id, "
        . "volunteers.customer_id, "
        . "volunteers.status, "
        . "volunteers.shortname, "
        . "volunteers.local_festival_id, "
        . "volunteers.notes, "
        . "volunteers.internal_notes "
        . "FROM ciniki_musicfestival_volunteers AS volunteers "
        . "WHERE volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['volunteer_id']) && is_numeric($args['volunteer_id']) ) {
        $strsql .= "AND volunteers.id = '" . ciniki_core_dbQuote($ciniki, $args['volunteer_id']) . "' ";
    } elseif( isset($args['customer_id']) && is_numeric($args['customer_id']) ) {
        $strsql .= "AND volunteers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1257', 'msg'=>'No volunteer specified'));
    }
    if( isset($args['festival_id']) && is_numeric($args['festival_id']) ) {
        $strsql .= "AND volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' ";
    } elseif( !isset($args['volunteer_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1258', 'msg'=>'No festival specified'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'volunteers', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'customer_id', 'status', 'shortname', 'local_festival_id', 'notes', 'internal_notes'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1224', 'msg'=>'Volunteer not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['volunteers'][0]) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.musicfestivals.1225', 'msg'=>'Unable to find Volunteer'));
    }
    $volunteer = $rc['volunteers'][0];

    //
    // Get the tags
    //
    $strsql = "SELECT tag_type, tag_name AS lists "
        . "FROM ciniki_musicfestival_volunteer_tags "
        . "WHERE volunteer_id = '" . ciniki_core_dbQuote($ciniki, $volunteer['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY tag_type, tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'tags', 'fname'=>'tag_type', 
            'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $tags) {
            if( $tags['tag_type'] == 10 ) {
                $volunteer['available_days'] = $tags['lists'];
            } elseif( $tags['tag_type'] == 20 ) {
                $volunteer['available_times'] = $tags['lists'];
            } elseif( $tags['tag_type'] == 30 ) {
                $volunteer['skills'] = $tags['lists'];
            } elseif( $tags['tag_type'] == 50 ) {
                $volunteer['approved_roles'] = $tags['lists'];
            }
        }
    }

    //
    // Load the shifts
    //
    if( isset($args['assignments']) && ($args['assignments'] == 'yes' || $args['assignments'] == 'upcoming') ) {
        //
        // Load the locations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'locationsLoad');
        $rc = ciniki_musicfestivals_locationsLoad($ciniki, $tnid, $volunteer['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $locations = isset($rc['locations']) ? $rc['locations'] : array();

        //
        // Load the shifts and get the other volunteers
        //
        $strsql = "SELECT shifts.id, "
            . "assignments.id AS assignment_id, "
            . "assignments.status AS assignment_status, "
            . "assignments.status AS assignment_status_text, "
            . "shifts.shift_date, "
            . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
            . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
            . "TIME_FORMAT(shifts.start_time, '%H%i') as sort_start_time, "
            . "TIME_FORMAT(shifts.end_time, '%H%i') as sort_end_time, "
            . "shifts.object, "
            . "shifts.object_id, "
            . "shifts.role, "
            . "shifts.min_volunteers, "
            . "shifts.max_volunteers, "
            . "volunteers.id AS volunteer_id, "
            . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
            . "FROM ciniki_musicfestival_volunteer_assignments AS assignments "
            . "INNER JOIN ciniki_musicfestival_volunteer_shifts AS shifts ON ("
                . "assignments.shift_id = shifts.id "
                . "AND shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assigned ON ("
                . "shifts.id = assigned.shift_id "
                . "AND assigned.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                . "assigned.volunteer_id = volunteers.id "
                . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE assignments.festival_id = '" . ciniki_core_dbQuote($ciniki, $volunteer['festival_id']) . "' "
            . "AND assignments.volunteer_id = '" . ciniki_core_dbQuote($ciniki, $volunteer['id']) . "' "
            . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        if( $args['assignments'] == 'upcoming' ) {
            $strsql .= "AND shifts.shift_date >= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' ";
        }
        $strsql .= "ORDER BY shifts.shift_date, shifts.start_time, object, object_id, role "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'shifts', 'fname'=>'id', 
                'fields'=>array('id', 'assignment_id', 'assignment_status', 'assignment_status_text',
                    'shift_date', 'start_time', 'end_time', 'sort_start_time', 'sort_end_time', 
                    'object', 'object_id', 'role', 
                    'min_volunteers', 'max_volunteers', 'names'),
                'utctotz'=>array('shift_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                'dlists'=>array('names'=>'<br>'),
                'maps'=>array('assignment_status_text' => $maps['volunteerassignment']['status']),
                ),
            array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                'fields'=>array('id'=>'volunteer_id', 'name'=>'names'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1271', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
        }
        $volunteer['shifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();
        foreach($volunteer['shifts'] as $sid => $shift) {
            if( isset($locations["{$shift['object']}:{$shift['object_id']}"]) ) {
                $volunteer['shifts'][$sid]['location_shortname'] = $locations["{$shift['object']}:{$shift['object_id']}"]['shortname'];
                $volunteer['shifts'][$sid]['location'] = $locations["{$shift['object']}:{$shift['object_id']}"]['name'];
            }
            $volunteer['shifts'][$sid]['shift_times'] = $shift['start_time'] . ' - ' . $shift['end_time'];
            $volunteer['shifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
        }
    }

    //
    // Check if contact info should be loaded
    //
    if( isset($args['contact']) && $args['contact'] == 'yes' ) {
/*        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
            'customer_id' => $volunteer['customer_id'],
            'addresses' => 'yes',
            'phones' => 'yes',
            'emails' => 'yes',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $volunteer['customer'] = $rc['customer'];
        if( !isset($volunteer['customer']['phones']) ) {
            $volunteer['customer']['phones'] = array();
        }
        if( !isset($volunteer['customer']['emails']) ) {
            $volunteer['customer']['emails'] = array();
        }
        if( !isset($volunteer['customer']['addresses']) ) {
            $volunteer['customer']['addresses'] = array();
        } */
    }

    return array('stat'=>'ok', 'volunteer'=>$volunteer);
}
?>
