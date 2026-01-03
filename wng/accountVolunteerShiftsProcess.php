<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountVolunteerShiftsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteList');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/volunteer';
    $display = 'sections';
    $date_format = 'D, M j, Y';

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
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1297', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Parse and check the approved roles for this volunteer
    //
    $volunteer = $args['volunteer'];
    $roles = [];
    if( isset($volunteer['approved_roles']) ) {
        $roles = explode('::', $volunteer['approved_roles']);
    }

    if( count($roles) <= 0 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No shifts available',
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['url' => $base_url, 'text' => 'Back'],
                ],
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

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
    // Get the shifts for the approved roles
    //
    $strsql = "SELECT shifts.id, "
        . "shifts.uuid, "
        . "shifts.shift_date, "
        . "shifts.shift_date AS shift_date_text, "
        . "TIME_FORMAT(shifts.start_time, '%l:%i %p') as start_time, "
        . "TIME_FORMAT(shifts.end_time, '%l:%i %p') AS end_time, "
        . "TIME_FORMAT(shifts.start_time, '%H%i') as sort_start_time, "
        . "TIME_FORMAT(shifts.end_time, '%H%i') as sort_end_time, "
        . "shifts.object, "
        . "shifts.object_id, "
        . "shifts.role, "
        . "shifts.min_volunteers, "
        . "shifts.max_volunteers, "
        . "COUNT(assigned.volunteer_id) AS num_volunteers "
        . "FROM ciniki_musicfestival_volunteer_shifts AS shifts "
        . "LEFT JOIN ciniki_musicfestival_volunteer_assignments AS assigned ON ("
            . "shifts.id = assigned.shift_id "
            . "AND assigned.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
//        . "LEFT JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
//            . "assigned.volunteer_id = volunteers.id "
//            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . ") "
        . "WHERE shifts.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND shifts.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND shifts.role IN (" . ciniki_core_dbQuoteList($ciniki, $roles) . ") "
        . "AND (shifts.flags&0x01) = 0x01 " // Online signups
        . "AND shifts.shift_date >= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
        . "GROUP BY shifts.id "
        . "ORDER BY shifts.shift_date, shifts.role, shifts.object, shifts.object_id, assigned.volunteer_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'dates', 'fname'=>'shift_date', 
            'fields'=>array('shift_date', 'shift_date_text'),
            'utctotz'=>array('shift_date_text'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        array('container'=>'roles', 'fname'=>'role', 
            'fields'=>array('name'=>'role'),
            ),
        array('container'=>'shifts', 'fname'=>'uuid', 
            'fields'=>array('id', 'uuid', 'shift_date', 'start_time', 'end_time', 
                'sort_start_time', 'sort_end_time', 
                'object', 'object_id', 'role', 
                'min_volunteers', 'max_volunteers', 'num_volunteers'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1260', 'msg'=>'Unable to load shifts', 'err'=>$rc['err']));
    }
    $shift_dates = isset($rc['dates']) ? $rc['dates'] : array();

    if( count($shift_dates) == 0 ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error',
            'content' => "No upcoming volunteer shifts",
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['url' => $base_url, 'text' => 'Back'],
                ],
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Show the dates
    //
    foreach($shift_dates AS $did => $date) {
        $shift_dates[$did]['num_open'] = 0;
        if( isset($date['roles']) ) {
            foreach($date['roles'] as $rid => $role) {
                $shift_dates[$did]['roles'][$rid]['num_open'] = 0;
                $permalink = ciniki_core_makePermalink($ciniki, $role['name']);
                $shift_dates[$did]['roles'][$permalink] = $shift_dates[$did]['roles'][$rid];
                unset($shift_dates[$did]['roles'][$rid]);
                $rid = $permalink;
                $shift_dates[$did]['roles'][$rid]['permalink'] = $permalink;
                if( isset($role['shifts']) ) {
                    foreach($role['shifts'] as $sid => $shift) {
                        if( isset($locations["{$shift['object']}:{$shift['object_id']}"]) ) {
                            $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['location'] = $locations["{$shift['object']}:{$shift['object_id']}"]['name'];
                        }
                        $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['times'] = "{$shift['start_time']} - {$shift['end_time']}";
                        if( $shift['num_volunteers'] < $shift['max_volunteers'] ) {
                            $shift_dates[$did]['num_open']++;
                            $shift_dates[$did]['roles'][$rid]['num_open']++;
                        }
                        $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['buttons'] = "";
                        if( isset($args['volunteer']['shifts'][$shift['id']]) ) {
                            $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['assigned'] = 'yes';
                            if( $args['volunteer']['shifts'][$shift['id']]['assignment_status'] == 30 ) {
                                $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['buttons'] .= "<span class='text'>Your Shift</span>";
                            } else {
                                $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['buttons'] .= "<span class='text'>Request Pending</span>";
                            }
                        } elseif( $shift['num_volunteers'] >= $shift['max_volunteers'] ) {
                            $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['buttons'] .= "<span class='text'>Filled</span>";
                        } 
                        $shift_dates[$did]['roles'][$rid]['shifts'][$sid]['buttons'] .= "<a class='button' href='{$base_url}/shifts/{$date['shift_date']}/{$permalink}/{$shift['uuid']}'>Open</a>";
                    }
                    uasort($shift_dates[$did]['roles'][$rid]['shifts'], function($a, $b) {
                        if( $a['location'] == $b['location'] ) {
                            return $a['sort_start_time'] < $b['sort_start_time'] ? -1 : 1;
                        }
                        return strcasecmp($a['location'], $b['location']);
                        });
                }
                $shift_dates[$did]['roles'][$rid]['num_open_text'] = $shift_dates[$did]['roles'][$rid]['num_open'] > 0 ? $shift_dates[$did]['roles'][$rid]['num_open'] : 'Filled';
                $shift_dates[$did]['roles'][$rid]['buttons'] = "<a class='button' href='{$base_url}/shifts/{$date['shift_date']}/{$permalink}'>Open</a>";
            }
        }
        $shift_dates[$did]['num_open_text'] = $shift_dates[$did]['num_open'] > 0 ? $shift_dates[$did]['num_open'] : 'Filled';
        $shift_dates[$did]['buttons'] = "<a class='button' href='{$base_url}/shifts/{$date['shift_date']}'>Open</a>";
    }

    $selected_date = '';
    $selected_role = '';
    $selected_shift = '';
    $action = 'view';
    if( isset($request['uri_split'][($request['cur_uri_pos']+4)]) ) {
        $selected_date = $request['uri_split'][($request['cur_uri_pos']+4)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+5)]) ) {
        $selected_role = $request['uri_split'][($request['cur_uri_pos']+5)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+6)]) ) {
        $selected_shift = $request['uri_split'][($request['cur_uri_pos']+6)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+7)]) ) {
        $action = $request['uri_split'][($request['cur_uri_pos']+7)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+8)]) ) {
        $confirm = $request['uri_split'][($request['cur_uri_pos']+8)];
    }

    //
    // Decide what to display
    //
    if( $selected_date != '' && $selected_role != '' && $selected_shift != '' 
        && isset($shift_dates[$selected_date]['roles'][$selected_role]['shifts'][$selected_shift]) 
        ) {
        $date = $shift_dates[$selected_date];
        $shift = $shift_dates[$selected_date]['roles'][$selected_role]['shifts'][$selected_shift];
        $content = "<b>Date</b>: {$date['shift_date_text']}<br/>"
            . "<b>Times</b>: {$shift['start_time']} - {$shift['end_time']}<br/>"
            . "<b>Location</b>: {$shift['location']}<br/>"
            . "<b>Role</b>: {$shift['role']}<br/>"
            . "";
        $blocks[] = [
            'type' => 'text',
            'title' => 'Volunteer Shift',
            'content' => $content,
            ];
        $buttons = [];
        // Already signed up for this shift
        if( isset($args['volunteer']['shifts'][$shift['id']]) ) {
            if( $args['volunteer']['shifts'][$shift['id']]['assignment_status'] == 30 ) {
                $blocks[] = [
                    'type' => 'msg',
                    'level' => 'success',
                    'content' => "You are signed up for this shift.",
                    ];
            } else {
                $blocks[] = [
                    'type' => 'msg',
                    'level' => 'success',
                    'content' => "Thank you for requesting this shift, we will email when with confirmation.",
                    ];
            }
            $buttons = [
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}", 'text' => 'Back'],
                ];
        } 
        // Shift is full
        elseif( $shift['num_volunteers'] >= $shift['max_volunteers'] ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'warning',
                'content' => "This shift is full",
                ];
            $buttons = [
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}", 'text' => 'Back'],
                ];
        } 
        // Shift has open spots
        elseif( isset($action) && $action == 'signup' && isset($confirm) && $confirm == 'confirm' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.musicfestivals.volunteerassignment', [
                'festival_id' => $volunteer['festival_id'],
                'shift_id' => $shift['id'],
                'volunteer_id' => $volunteer['id'],
                'status' => 10,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                $blocks[] = [
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => "Unable to signup for shift, please contact us for help."
                    ];
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }
            $blocks[] = [
                'type' => 'msg',
                'level' => 'success',
                'content' => "Thank you for requesting this shift, we will email when with confirmation.",
                ];
            $buttons = [
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}", 'text' => 'Back'],
                ];
        }
        elseif( isset($action) && $action == 'signup' ) {
            $buttons = [
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}/{$selected_shift}", 'text' => 'Cancel'],
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}/{$selected_shift}/signup/confirm", 'text' => 'Confirm'],
                ];
        }
        else {
            $buttons = [
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}", 'text' => 'Back'],
                ['url' => "{$base_url}/shifts/{$selected_date}/{$selected_role}/{$selected_shift}/signup", 'text' => 'Request Shift'],
                ];
        }
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => $buttons,
            ];
    } 
    elseif( $selected_date != '' && $selected_role != '' && isset($shift_dates[$selected_date]['roles'][$selected_role]) ) {
        $date = $shift_dates[$selected_date];
        $role = $shift_dates[$selected_date]['roles'][$selected_role];
        $blocks[] = [
            'type' => 'table',
            'title' => $date['shift_date_text'] . ' - ' . $role['name'],
            'columns' => [
                ['label' => 'Location', 'field' => 'location'],
                ['label' => 'Times', 'field' => 'times'],
                ['label' => '', 'class' => 'alignright buttons', 'field' => 'buttons'],
                ],
            'rows' => $role['shifts'],
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['url' => $base_url . '/shifts/' . $selected_date, 'text' => 'Back'],
                ],
            ];
    } 
    elseif( $selected_date != '' && isset($shift_dates[$selected_date]) ) {
        $date = $shift_dates[$selected_date];
        $blocks[] = [
            'type' => 'table',
            'title' => $date['shift_date_text'],
            'columns' => [
                ['label' => 'Role', 'field' => 'name'],
                ['label' => 'Unfilled Shifts', 'field' => 'num_open_text'],
                ['label' => '', 'class' => 'alignright buttons', 'field' => 'buttons'],
                ],
            'rows' => $date['roles'],
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['url' => $base_url . '/shifts', 'text' => 'Back'],
                ],
            ];
    }
    //
    // Print the list of dates
    //
    else {
        $blocks[] = [
            'type' => 'table',
            'title' => 'Dates',
            'columns' => [
                ['label' => 'Date', 'field' => 'shift_date_text'],
                ['label' => 'Unfilled Shifts', 'field' => 'num_open_text'],
                ['label' => '', 'class' => 'alignright buttons', 'field' => 'buttons'],
                ],
            'rows' => $shift_dates,
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['url' => $base_url, 'text' => 'Back'],
                ],
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
