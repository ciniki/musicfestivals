<?php
//
// Description
// ===========
// This method will return the volunteer info for a festival
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_volunteers($ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'volunteers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Volunteers'),
        'volunteer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Volunteer'),
        'schedule'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule'),
        'ssection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Section'),
        'sdivision_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Schedule Division'),
        'shifts'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shifts'),
        'shift_dates'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shift Dates'),
        'shift_date'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shift Date'),
        'locations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Locations'),
        'location'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Location'),
        'roles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Roles'),
        'role'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Role'),
        'pending'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Pending'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $date_format = 'D, M j, Y';

    
    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1246', 'msg'=>'No festival specified'));
    }
    $festival = $rc['festival'];

    $rsp = array('stat'=>'ok', 'festival'=>$festival);

    //
    // Load the locations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'locationsLoad');
    $rc = ciniki_musicfestivals_locationsLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $locations = isset($rc['locations']) ? $rc['locations'] : array();

    //
    // Check if location specified
    //
    if( isset($args['location']) && $args['location'] != '' 
        && preg_match("/^(.*):(.*)$/", $args['location'], $m)
        ) {
        $args['object'] = $m[1];
        $args['object_id'] = $m[2];
    }

    //
    // Load volunteers
    //
    if( isset($args['volunteers']) && $args['volunteers'] == 'yes' ) {
        //
        // Get the list of volunteers
        //
        $strsql = "SELECT volunteers.id, "
            . "volunteers.status, "
            . "volunteers.status AS status_text, "
            . "volunteers.customer_id, "
            . "customers.display_name AS customer_name, "
            . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS display_name "
            . "FROM ciniki_musicfestival_volunteers AS volunteers "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['pending']) && $args['pending'] == 'yes' ) {
            $strsql .= "AND volunteers.status = 10 ";
        }
        $strsql .= "ORDER BY customer_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'volunteers', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'status_text', 'customer_id', 'display_name', 'customer_name'),
                'maps'=>array('status_text'=>$maps['volunteer']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1144', 'msg'=>'Unable to load volunteers', 'err'=>$rc['err']));
        }
        $rsp['volunteers'] = isset($rc['volunteers']) ? $rc['volunteers'] : array();
    
        //
        // Load the volunteer shifts
        //
        if( isset($args['volunteer_id']) && $args['volunteer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerLoad');
            $rc = ciniki_musicfestivals_volunteerLoad($ciniki, $args['tnid'], [ 
                'festival_id' => $args['festival_id'],
                'volunteer_id' => $args['volunteer_id'],
                'assignments' => 'yes',
                ]);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['volunteer_shifts'] = isset($rc['volunteer']['shifts']) ? array_values($rc['volunteer']['shifts']) : [];
        }
    }

    //
    // Load schedule
    //
    if( isset($args['schedule']) && $args['schedule'] == 'yes' ) {
        //
        // Get the list of schedule sections
        //
        $strsql = "SELECT sections.id, "
            . "sections.festival_id, "
            . "sections.name, "
            . "sections.sequence, "
            . "sections.flags, "
            . "sections.flags AS options "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        //
        // Only get live sections, no volunteers for virtual
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) ) {
            $strsql .= "AND ((sections.flags&0x0F00) = 0 OR (sections.flags&0x0100) = 0x0100) ";
        }
        $strsql .= "ORDER BY sections.sequence, sections.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'name', 'sequence', 'flags', 'options'),
                'flags' => array('options'=>$maps['schedulesection']['flags']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['schedule_sections'] = isset($rc['sections']) ? $rc['sections'] : array();

        //
        // Load the divisions
        //
        if( isset($args['ssection_id']) && $args['ssection_id'] > 0 ) {
            $strsql = "SELECT divisions.id, "
                . "divisions.festival_id, "
                . "divisions.ssection_id, "
                . "divisions.flags, "
                . "divisions.flags AS options, "
                . "divisions.name, "
                . "DATE_FORMAT(divisions.division_date, '%a, %b %e, %Y') AS division_date_text, "
                . "IFNULL(IF(locations.shortname <> '', locations.shortname, locations.name), '') AS location_name, "
                . "GROUP_CONCAT(DISTINCT customers.display_name ORDER BY customers.display_name SEPARATOR ', ') AS adjudicator_name, "
                . "MIN(timeslots.slot_time) AS first_timeslot "
                . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                . "LEFT JOIN ciniki_musicfestival_adjudicatorrefs AS arefs ON ("
                    . "divisions.id = arefs.object_id "
                    . "AND arefs.object = 'ciniki.musicfestivals.scheduledivision' "
                    . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_adjudicators AS adjudicators ON ("
                    . "arefs.adjudicator_id = adjudicators.id "
                    . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "adjudicators.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                    . "divisions.location_id = locations.id "
                    . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                    . "divisions.id = timeslots.sdivision_id "
                    . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $args['ssection_id']) . "' "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY divisions.id "
                . "ORDER BY divisions.division_date, divisions.name, first_timeslot "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'divisions', 'fname'=>'id', 
                    'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'flags', 'options', 
                        'division_date_text', 'location_name', 'adjudicator_name', 
                        ),
                    'flags' => array('options'=>$maps['schedulesection']['flags']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['divisions']) ) {
                $rsp['schedule_divisions'] = $rc['divisions'];
                $nplists['schedule_divisions'] = array();
                foreach($rsp['schedule_divisions'] as $iid => $scheduledivision) {
                    $nplists['schedule_divisions'][] = $scheduledivision['id'];
                }
            } else {
                $rsp['schedule_divisions'] = array();
                $nplists['schedule_divisions'] = array();
            }
        }

        //
        // Load the list of shifts and volunteers signed up for those shifts
        //
        if( isset($args['sdivision_id']) && $args['sdivision_id'] > 0 ) {
            $strsql = "SELECT divisions.id, "
                . "DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS division_date, "
                . "divisions.location_id, "
                . "IFNULL(locations.building_id, 0) AS building_id "
                . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
                . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                    . "divisions.location_id = locations.id "
                    . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE divisions.id = '" . ciniki_core_dbQuote($ciniki, $args['sdivision_id']) . "' "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'division');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1305', 'msg'=>'Unable to load division', 'err'=>$rc['err']));
            }
            if( !isset($rc['division']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1306', 'msg'=>'Unable to find requested division'));
            }
            $division = $rc['division'];

            //
            // Load the shifts
            //
            $strsql = "SELECT shifts.id, "
                . "DATE_FORMAT(shifts.shift_date, '%a, %b %e, %Y') AS shift_date, "
                . "DATE_FORMAT(shifts.shift_date, 'Ymd') AS sort_shift_date, "
                . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
                . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
                . "TIME_FORMAT(shifts.start_time, '%H%i') as sort_start_time, "
                . "TIME_FORMAT(shifts.end_time, '%H%i') as sort_end_time, "
                . "shifts.role, "
                . "shifts.min_volunteers, "
                . "shifts.max_volunteers, "
                . "volunteers.id AS volunteer_id, "
                . "assignments.status AS assignment_status, "
                . "assignments.status AS assignment_status_text, "
                . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
                . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
                . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                    . "shifts.id = assignments.shift_id "
                    . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                    . "assignments.volunteer_id = volunteers.id "
                    . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "volunteers.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND shifts.shift_date = '" . ciniki_core_dbQuote($ciniki, $division['division_date']) . "' "
                . "AND ("
                    . "(object = 'ciniki.musicfestivals.location' "
                    . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $division['location_id']) . "' "
                    . ") OR ("
                    . "object = 'ciniki.musicfestivals.building' "
                    . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $division['building_id']) . "' "
                    . "))"
                . "ORDER BY shifts.role, shifts.start_time, names "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'shifts', 'fname'=>'id', 
                    'fields'=>array('id', 'shift_date', 'sort_shift_date', 
                        'start_time', 'end_time', 'sort_start_time', 'sort_end_time', 
                        'role', 'min_volunteers', 'max_volunteers',
                        ),
                    ),
                array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                    'fields'=>array('id'=>'volunteer_id', 'name'=>'names', 'assignment_status', 'assignment_status_text'),
                    'maps'=>array('assignment_status_text'=>$maps['volunteerassignment']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1308', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
            }
            $rsp['schedule_vshifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();
            foreach($rsp['schedule_vshifts'] as $sid => $shift) {
                $rsp['schedule_vshifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
                $rsp['schedule_vshifts'][$sid]['names'] = '';
                if( isset($shift['volunteers']) ) {
                    foreach($shift['volunteers'] as $volunteer) {
                        $rsp['schedule_vshifts'][$sid]['names'] .= ($rsp['schedule_vshifts'][$sid]['names'] != '' ? '<br>' : '')
                            . $volunteer['name']
                            . ($volunteer['assignment_status'] != 30 ? ' [' . $volunteer['assignment_status_text'] . ']' : '');
                        
                    }
                }

            }
        }
    }

    //
    // Load the shifts
    //
    if( isset($args['shifts']) && $args['shifts'] == 'yes' ) {
        $strsql = "SELECT shifts.id, "
            . "DATE_FORMAT(shifts.shift_date, '%a, %b %e, %Y') AS shift_date, "
            . "DATE_FORMAT(shifts.shift_date, 'Ymd') AS sort_shift_date, "
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
            . "assignments.status AS assignment_status, "
            . "assignments.status AS assignment_status_text, "
            . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
            . "FROM ciniki_musicfestival_volunteer_shifts AS shifts ";
        if( isset($args['pending']) && $args['pending'] == 'yes' ) {
            $strsql .= "INNER JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                . "shifts.id = assignments.shift_id "
                . "AND assignments.status = 10 "
                . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        } else {
            $strsql .= "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                . "shifts.id = assignments.shift_id "
                . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .= "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                . "assignments.volunteer_id = volunteers.id "
                . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "volunteers.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        if( isset($args['shift_date']) && $args['shift_date'] != '' ) {
            $strsql .= "AND shifts.shift_date = '" . ciniki_core_dbQuote($ciniki, $args['shift_date']) . "' ";
        }
        if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
            $strsql .= "AND shifts.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
                . "AND shifts.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' ";
        }
        if( isset($args['role']) && $args['role'] != '' ) {
            $strsql .= "AND shifts.role = '" . ciniki_core_dbQuote($ciniki, $args['role']) . "' ";
        }
        // Sort done in php
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'shifts', 'fname'=>'id', 
                'fields'=>array('id', 'shift_date', 'sort_shift_date', 
                    'start_time', 'end_time', 'sort_start_time', 'sort_end_time', 
                    'object', 'object_id', 'role', 'min_volunteers', 'max_volunteers',
                    ),
                ),
            array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                'fields'=>array('id'=>'volunteer_id', 'name'=>'names', 'assignment_status', 'assignment_status_text'),
                'maps'=>array('assignment_status_text'=>$maps['volunteerassignment']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1309', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
        }
        $rsp['shifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();

        foreach($rsp['shifts'] as $sid => $shift) {
            if( isset($locations["{$shift['object']}:{$shift['object_id']}"]) ) {
                $rsp['shifts'][$sid]['location_shortname'] = $locations["{$shift['object']}:{$shift['object_id']}"]['shortname'];
                $rsp['shifts'][$sid]['location_name'] = $locations["{$shift['object']}:{$shift['object_id']}"]['name'];
            }
            $rsp['shifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
            $rsp['shifts'][$sid]['names'] = '';
            if( isset($shift['volunteers']) ) {
                foreach($shift['volunteers'] as $volunteer) {
                    $rsp['shifts'][$sid]['names'] .= ($rsp['shifts'][$sid]['names'] != '' ? '<br>' : '')
                        . $volunteer['name']
                        . ($volunteer['assignment_status'] != 30 ? ' [' . $volunteer['assignment_status_text'] . ']' : '');
                }
            }
        }
        uasort($rsp['shifts'], function($a, $b) {
            if( $a['sort_shift_date'] != $b['sort_shift_date'] ) {    
                return strnatcasecmp($a['sort_shift_date'], $b['sort_shift_date']);
            }
            if( $a['location_name'] != $b['location_name'] ) {    
                return strnatcasecmp($a['location_name'], $b['location_name']);
            }
            if( $a['role'] != $b['role'] ) {    
                return strnatcasecmp($a['role'], $b['role']);
            }
            if( $a['sort_start_time'] == $b['sort_start_time'] ) {
                return 0;
            }
            return $a['sort_start_time'] < $b['sort_start_time'] ? -1 : 1;
            });
        $rsp['shifts'] = array_values($rsp['shifts']);
    }

    //
    // Load the shift dates
    //
    if( isset($args['shift_dates']) && $args['shift_dates'] == 'yes' ) {
        $strsql = "SELECT DISTINCT shift_date, "
            . "shift_date AS shift_date_text "
            . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY shift_date "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'shift_dates', 'fname'=>'shift_date', 
                'fields'=>array('shift_date', 'shift_date_text'),
                'dtformat'=>array('shift_date_text'=>'D, M j, Y'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1226', 'msg'=>'Unable to load dates', 'err'=>$rc['err']));
        }
        $rsp['shift_dates'] = isset($rc['shift_dates']) ? $rc['shift_dates'] : array();

        if( isset($args['shift_date']) && $args['shift_date'] != '' ) {
            $strsql = "SELECT shifts.id, "
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
                . "assignments.status AS assignment_status, "
                . "assignments.status AS assignment_status_text, "
                . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
                . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
                . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                    . "shifts.id = assignments.shift_id "
                    . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                    . "assignments.volunteer_id = volunteers.id "
                    . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "volunteers.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND shifts.shift_date = '" . ciniki_core_dbQuote($ciniki, $args['shift_date']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'shifts', 'fname'=>'id', 
                    'fields'=>array('id', 'start_time', 'end_time', 
                        'sort_start_time', 'sort_end_time', 
                        'object', 'object_id', 'role', 
                        'min_volunteers', 'max_volunteers'),
                    ),
                array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                    'fields'=>array('id'=>'volunteer_id', 'name'=>'names', 'assignment_status', 'assignment_status_text'),
                    'maps'=>array('assignment_status_text'=>$maps['volunteerassignment']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1309', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
            }
            $rsp['date_shifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();

            foreach($rsp['date_shifts'] as $sid => $shift) {
                if( isset($locations["{$shift['object']}:{$shift['object_id']}"]) ) {
                    $rsp['date_shifts'][$sid]['location_shortname'] = $locations["{$shift['object']}:{$shift['object_id']}"]['shortname'];
                    $rsp['date_shifts'][$sid]['location_name'] = $locations["{$shift['object']}:{$shift['object_id']}"]['name'];
                }
                $rsp['date_shifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
                $rsp['date_shifts'][$sid]['names'] = '';
                if( isset($shift['volunteers']) ) {
                    foreach($shift['volunteers'] as $volunteer) {
                        $rsp['date_shifts'][$sid]['names'] .= ($rsp['date_shifts'][$sid]['names'] != '' ? '<br>' : '')
                            . $volunteer['name']
                            . ($volunteer['assignment_status'] != 30 ? ' [' . $volunteer['assignment_status_text'] . ']' : '');
                    }
                }
            }
            uasort($rsp['date_shifts'], function($a, $b) {
                if( $a['location_name'] != $b['location_name'] ) {    
                    return strcasecmp($a['location_name'], $b['location_name']);
                }
                if( $a['role'] != $b['role'] ) {    
                    return strcasecmp($a['role'], $b['role']);
                }
                if( $a['sort_start_time'] == $b['sort_start_time'] ) {
                    return 0;
                }
                return $a['sort_start_time'] < $b['sort_start_time'] ? -1 : 1;
                });
            $rsp['date_shifts'] = array_values($rsp['date_shifts']);
        }
    }

    if( isset($args['locations']) && $args['locations'] == 'yes' ) {
        $rsp['locations'] = $locations;
        if( isset($args['location']) && $args['location'] != '' 
            && preg_match("/^(.*):(.*)$/", $args['location'], $m)
            ) {
            $args['object'] = $m[1];
            $args['object_id'] = $m[2];
            $strsql = "SELECT shifts.id, "
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
                . "assignments.status AS assignment_status, "
                . "assignments.status AS assignment_status_text, "
                . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
                . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
                . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                    . "shifts.id = assignments.shift_id "
                    . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                    . "assignments.volunteer_id = volunteers.id "
                    . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "volunteers.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND shifts.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
                . "AND shifts.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "ORDER BY shifts.shift_date, shifts.role, shifts.start_time "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'shifts', 'fname'=>'id', 
                    'fields'=>array('id', 'shift_date', 'start_time', 'end_time', 
                        'object', 'object_id', 'role', 
                        'min_volunteers', 'max_volunteers', 'names'),
                    'utctotz'=>array('shift_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                    'dlists'=>array('names'=>'<br>'),
                    ),
                array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                    'fields'=>array('id'=>'volunteer_id', 'name'=>'names', 'assignment_status', 'assignment_status_text'),
                    'maps'=>array('assignment_status_text'=>$maps['volunteerassignment']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1315', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
            }
            $rsp['location_shifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();
            foreach($rsp['location_shifts'] as $sid => $shift) {
                $rsp['location_shifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
                $rsp['location_shifts'][$sid]['names'] = '';
                if( isset($shift['volunteers']) ) {
                    foreach($shift['volunteers'] as $volunteer) {
                        $rsp['location_shifts'][$sid]['names'] .= ($rsp['location_shifts'][$sid]['names'] != '' ? '<br>' : '')
                            . $volunteer['name']
                            . ($volunteer['assignment_status'] != 30 ? ' [' . $volunteer['assignment_status_text'] . ']' : '');
                    }
                }
            }
        }
    }

    if( isset($args['roles']) && $args['roles'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerRolesLoad');
        $rc = ciniki_musicfestivals_volunteerRolesLoad($ciniki, $args['tnid'], [
            'festival' => $festival,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['roles'] = [];
        foreach($rc['roles'] as $role) {
            $rsp['roles'][] = ['role' => $role];
        }

        //
        // Load the shifts for a role
        //
        if( isset($args['role']) && $args['role'] != '' ) {
            $strsql = "SELECT shifts.id, "
                . "shifts.shift_date, "
                . "DATE_FORMAT(shifts.shift_date, '%Y%m%d') AS sort_shift_date, "
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
                . "assignments.status AS assignment_status, "
                . "assignments.status AS assignment_status_text, "
                . "IF(volunteers.shortname <> '', volunteers.shortname, customers.display_name) AS names "
                . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
                . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assignments ON ("
                    . "shifts.id = assignments.shift_id "
                    . "AND assignments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
                    . "assignments.volunteer_id = volunteers.id "
                    . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS customers ON ("
                    . "volunteers.customer_id = customers.id "
                    . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND shifts.role = '" . ciniki_core_dbQuote($ciniki, $args['role']) . "' "
                . "ORDER BY shifts.shift_date, shifts.start_time, shifts.object_id, shifts.role, names "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'shifts', 'fname'=>'id', 
                    'fields'=>array('id', 'shift_date', 'start_time', 'end_time', 
                        'sort_shift_date', 'sort_start_time', 'sort_end_time', 
                        'object', 'object_id', 'role', 
                        'min_volunteers', 'max_volunteers', 'names'),
                    'utctotz'=>array('shift_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
                    ),
                array('container'=>'volunteers', 'fname'=>'volunteer_id', 
                    'fields'=>array('id'=>'volunteer_id', 'name'=>'names', 'assignment_status', 'assignment_status_text'),
                    'maps'=>array('assignment_status_text'=>$maps['volunteerassignment']['status']),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1310', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
            }
            $rsp['role_shifts'] = isset($rc['shifts']) ? $rc['shifts'] : array();

            foreach($rsp['role_shifts'] as $sid => $shift) {
                if( isset($locations["{$shift['object']}:{$shift['object_id']}"]) ) {
                    $rsp['role_shifts'][$sid]['location_shortname'] = $locations["{$shift['object']}:{$shift['object_id']}"]['shortname'];
                    $rsp['role_shifts'][$sid]['location_name'] = $locations["{$shift['object']}:{$shift['object_id']}"]['name'];
                }
                $rsp['role_shifts'][$sid]['num_volunteers'] = isset($shift['volunteers']) ? count($shift['volunteers']) : 0;
                $rsp['role_shifts'][$sid]['names'] = '';
                if( isset($shift['volunteers']) ) {
                    foreach($shift['volunteers'] as $volunteer) {
                        $rsp['role_shifts'][$sid]['names'] .= ($rsp['role_shifts'][$sid]['names'] != '' ? '<br>' : '')
                            . $volunteer['name']
                            . ($volunteer['assignment_status'] != 30 ? ' [' . $volunteer['assignment_status_text'] . ']' : '');
                    }
                }
            }
            uasort($rsp['role_shifts'], function($a, $b) {
                if( $a['location_name'] != $b['location_name'] ) {    
                    return strcasecmp($a['location_name'], $b['location_name']);
                }
                if( $a['sort_shift_date'] != $b['sort_shift_date'] ) {
                    return $a['sort_shift_date'] < $b['sort_shift_date'] ? -1 : 1;
                }
                if( $a['sort_start_time'] == $b['sort_start_time'] ) {
                    return 0;
                }
                return $a['sort_start_time'] < $b['sort_start_time'] ? -1 : 1;
                });
            $rsp['role_shifts'] = array_values($rsp['role_shifts']);
        }
    }

    return $rsp;
}
?>
