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
function ciniki_musicfestivals_wng_accountVolunteerProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/volunteer';
    $display = 'sections';

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Check if the customer is a volunteer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'volunteerLoad');
    $rc = ciniki_musicfestivals_volunteerLoad($ciniki, $tnid, [
        'festival_id' => $festival['id'],
        'customer_id' => $request['session']['customer']['id'],
        'assignments' => 'upcoming',
        'contact' => 'yes',
        ]);
    if( $rc['stat'] == 'noexist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountVolunteerProfileProcess');
        return ciniki_musicfestivals_wng_accountVolunteerProfileProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'editable' => 'yes',
            'signup' => 'yes',
            ]);
    } else if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks' => [[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Internal errors retrieving profile',
            ]]);
    } 
    $volunteer = $rc['volunteer'];

    //
    // Check for requrest
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)]) 
        && $request['uri_split'][($request['cur_uri_pos']+3)] == 'edit'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountVolunteerProfileProcess');
        return ciniki_musicfestivals_wng_accountVolunteerProfileProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'volunteer' => $volunteer,
            'editable' => 'yes',
            ]);
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+3)]) 
        && $request['uri_split'][($request['cur_uri_pos']+3)] == 'shifts'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountVolunteerShiftsProcess');
        return ciniki_musicfestivals_wng_accountVolunteerShiftsProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'volunteer' => $volunteer,
            ]);
    }

    //
    // Show the volunteer's assignments
    //
    if( $volunteer['status'] == 10 && (!isset($volunteer['shifts']) || count($volunteer['shifts']) == 0) ) {
        $blocks[] = [
            'type' => 'title',
            'title' => 'My Upcoming Shifts',
            'level' => 2,
            ];
        $blocks[] = [
            'type' => 'msg',
            'level' => 'success',
            'content' => (isset($festival['volunteers-applied-msg']) && $festival['volunteers-applied-msg'] != '' 
                ? $festival['volunteers-applied-msg'] 
                : 'Thank you for applying to be a volunteer, your application is being reviewed.'),
            ];
    } else {
        if( !isset($volunteer['shifts']) || count($volunteer['shifts']) == 0 ) {
            $blocks[] = [
                'type' => 'title',
                'title' => 'My Upcoming Shifts',
                'level' => 2,
                ];
            $blocks[] = [
                'type' => 'msg',
                'level' => 'warning',
                'content' => 'No upcoming shifts',
                ];
        } else {
            $blocks[] = [
                'type' => 'table',
                'title' => 'My Upcoming Shifts',
                'columns' => [
                    ['label' => 'Date', 'field' => 'shift_date'],
                    ['label' => 'Time', 'field' => 'shift_times'],
                    ['label' => 'Location', 'field' => 'location'],
                    ['label' => 'Role', 'field' => 'role'],
                    ['label' => 'Status', 'field' => 'assignment_status_text'],
                    ],
                'rows' => $volunteer['shifts'],
                ];
        }

        //
        // Check if approved roles specified
        //
        if( isset($volunteer['approved_roles']) && $volunteer['approved_roles'] != '' ) {
            $blocks[] = [
                'type' => 'buttons',
                'class' => 'aligncenter',
                'items' => [
                    ['url' => $base_url . '/shifts', 'text' => 'Volunteer for Shifts'],
                    ],
                ];
        }
    }

    //
    // Show the profile details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountVolunteerProfileProcess');
    $rc = ciniki_musicfestivals_wng_accountVolunteerProfileProcess($ciniki, $tnid, $request, [
        'festival' => $festival,
        'volunteer' => $volunteer,
        'editable' => 'no',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['blocks']) ) {
        foreach($rc['blocks'] as $block) {
            $blocks[] = $block;
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
