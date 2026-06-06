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
        && isset($festival['volunteers-account-shift-selector']) && $festival['volunteers-account-shift-selector'] == 'yes'
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountVolunteerShiftsProcess2');
        return ciniki_musicfestivals_wng_accountVolunteerShiftsProcess2($ciniki, $tnid, $request, [
            'festival' => $festival,
            'volunteer' => $volunteer,
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
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+4)]) 
        && $request['uri_split'][($request['cur_uri_pos']+3)] == 'resource'
        ) {
        $filename = urldecode($request['uri_split'][($request['cur_uri_pos']+4)]);
        $strsql = "SELECT id, uuid, org_filename "
            . "FROM ciniki_musicfestival_volunteer_resources "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND org_filename = '" . ciniki_core_dbQuote($ciniki, $filename) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'resource');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1606', 'msg'=>'Unable to load resource', 'err'=>$rc['err']));
        }
        if( !isset($rc['resource']) ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => 'File not found',
                );
        } else {
            $resource = $rc['resource'];

            //
            // Get the tenant storage directory
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
            $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $tenant_storage_dir = $rc['storage_dir'];

            //
            // Build the storage filename
            //
            $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/volunteerfiles/' . $resource['uuid'][0] . '/' . $resource['uuid'];
            if( file_exists($storage_filename) ) {
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
                header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');

                $finfo = finfo_open(FILEINFO_MIME);
                if( $finfo ) {
                    $content_type = finfo_file($finfo, $storage_filename);
                    if( $content_type != '' ) {
                        header('Content-Type: ' . $content_type);
                    }
                }

                // Specify Filename
                header('Content-Disposition: filename="' . $resource['org_filename'] . '"');
                header('Content-Length: ' . filesize($storage_filename));
                header('Cache-Control: max-age=0');

                $fp = fopen($storage_filename, 'rb');
                fpassthru($fp);

                return array('stat'=>'exit');
            } else {
                $blocks[] = array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => 'File not found',
                    );
            }
        }
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
            foreach($volunteer['shifts'] as $sid => $shift) {
                $permalink = ciniki_core_makePermalink($ciniki, $shift['role']);
                $volunteer['shifts'][$sid]['buttons'] = "<a class='button' href='{$base_url}/shifts/{$shift['shift_date_ymd']}/{$permalink}/{$shift['uuid']}?back=profile'>Open</a>";
            }
            $blocks[] = [
                'type' => 'table',
                'title' => 'My Upcoming Shifts',
                'columns' => [
                    ['label' => 'Date', 'field' => 'shift_date'],
                    ['label' => 'Time', 'field' => 'shift_times'],
                    ['label' => 'Location', 'field' => 'location'],
                    ['label' => 'Role', 'field' => 'role'],
                    ['label' => 'Status', 'field' => 'assignment_status_text'],
                    ['label' => '', 'field' => 'buttons'],
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
    // Show any files for the volunteer
    //
    $strsql = "SELECT resources.id, "
        . "resources.name, "
        . "resources.resourcetype, "
        . "resources.resourcetype AS resourcetype_text, "
        . "resources.category, "
        . "resources.synopsis, "
        . "resources.url, "
        . "resources.org_filename, "
        . "resources.extension "
        . "FROM ciniki_musicfestival_volunteer_resources AS resources "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY category, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.volunteers', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category'),
            ),
        array('container'=>'resources', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'resourcetype', 'category', 'synopsis', 'url', 'org_filename', 'extension'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    if( count($categories) > 0 ) {
        $blocks[] = array(
            'type' => 'title', 
            'title' => 'Volunteer Resources',
            'level' => 2,
            );
        foreach($categories as $category) {
            if( isset($category['resources']) ) {
                $html = '';
                foreach($category['resources'] as $resource) {
                    if( $resource['resourcetype'] == 10 ) {
                        $html .= ($html != '' ? '<br/>' : '')
                            . "<a target='_blank' href='" . $resource['url'] . "'>" . $resource['name'] . "</a>";
                    } elseif( $resource['resourcetype'] == 30 ) {
                        $html .= ($html != '' ? '<br/>' : '')
                            . "<a target='_blank' href='" . $resource['url'] . "'>" . $resource['name'] . "</a>";
                    } elseif( $resource['resourcetype'] == 50 ) {
                        $html .= ($html != '' ? '<br/>' : '')
                            . "<a target='_blank' href='{$base_url}/resource/" . urlencode($resource['org_filename']) . "'>" . $resource['name'] . "</a>";
                    }
                }
                $blocks[] = array(
                    'type' => 'text',
                    'title' => $category['name'],
                    'level' => 3,
                    'content' => $html,
                    );
            }
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
