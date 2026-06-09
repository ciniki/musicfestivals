<?php
//
// Description
// -----------
// This function will display the backtracks for sound techs to download
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountBacktracksProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/backtracks';
    $display = 'sections';

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1610', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $tnid, $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
   
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
    // Load the all the backtracks for the festival, in date order
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "locations.name AS location_name, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.groupname AS timeslot_groupname, ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS slot_time_text, ";
    } else {
        $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, ";
    }
    $strsql .= "registrations.id AS reg_id, "
        . "registrations.uuid, "
        . "registrations.display_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.backtrack1, "
        . "registrations.backtrack2, "
        . "registrations.backtrack3, "
        . "registrations.backtrack4, "
        . "registrations.backtrack5, "
        . "registrations.backtrack6, "
        . "registrations.backtrack7, "
        . "registrations.backtrack8 "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
            . "divisions.ssection_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
            . "divisions.location_id = locations.id "
            . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
        $strsql .= "ORDER BY sections.sequence, sections.name, divisions.name, registrations.timeslot_time, registrations.timeslot_sequence, registrations.display_name ";
    } else {
        $strsql .= "ORDER BY sections.sequence, sections.name, divisions.name, timeslots.slot_time, registrations.timeslot_sequence, registrations.display_name ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'division_name', 'location_name'),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'uuid', 'section_name', 'division_id', 'division_name', 'slot_time_text', 'display_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8', 
                'backtrack1', 'backtrack2', 'backtrack3', 'backtrack4', 'backtrack5', 'backtrack6', 'backtrack7', 'backtrack8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.762', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Default to showing list of sections
    //
    $blocks[] = [
        'type' => 'title',
        'title' => 'Backtracks',
        ];
    foreach($sections as $sid => $section) {
        if( !isset($section['divisions']) ) {   
            continue;
        }
        $section_permalink = ciniki_core_makePermalink($ciniki, $section['name']);
        $items = [];
        foreach($section['divisions'] as $division) {
            $division_permalink = ciniki_core_makePermalink($ciniki, $division['name']);
            //
            // Check for backtracks
            //
            $files = [];
            $division_files_found = 'no';
            $reg_num = 1;
            foreach($division['registrations'] as $rid => $reg) {
                $reg_files_found = 'no';
                for($i = 1; $i <= 8; $i++) {
                    if( isset($reg["title{$i}"]) && isset($reg["backtrack{$i}"]) && $reg["backtrack{$i}"] != '' ) {
                        $division_files_found = 'yes';
                        $reg_files_found = 'yes';
                        $backtrack_permalink = ciniki_core_makePermalink($ciniki, $reg["backtrack{$i}"]);
                        //
                        // Check if requested file
                        //
                        if( isset($request['uri_split'][5]) 
                            && $request['uri_split'][3] == $section_permalink
                            && $request['uri_split'][4] == $division_permalink
                            && $request['uri_split'][5] == $backtrack_permalink
                            ) {
                            $storage_filename = $tenant_storage_dir . '/ciniki.musicfestivals/files/'
                                . $reg['uuid'][0] . '/' . $reg['uuid'] . '_backtrack' . $i;
                            // Download file
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
                            $filename = "{$reg_num} - {$reg['slot_time_text']} - {$reg['display_name']} - {$reg["title{$i}"]} - {$reg["backtrack{$i}"]}";
                            header('Content-Disposition: attachment; filename="' . $filename . '"');
                            header('Content-Length: ' . filesize($storage_filename));
                            header('Cache-Control: max-age=0');

                            $fp = fopen($storage_filename, 'rb');
                            fpassthru($fp);

                            return array('stat'=>'exit');
                        }
                        $files[] = [
                            'name' => "{$reg_num} - {$reg['slot_time_text']} - {$reg['display_name']} - {$reg["title{$i}"]} - {$reg["backtrack{$i}"]}",
                            'url' => "{$base_url}/{$section_permalink}/{$division_permalink}/{$backtrack_permalink}",
                            ];
                    }
                }
                if( $reg_files_found == 'yes' ) {
                    $reg_num++;
                }
            }
            //
            // Check if division selected
            //
            if( isset($request['uri_split'][4]) 
                && $request['uri_split'][3] == $section_permalink
                && $request['uri_split'][4] == $division_permalink
                ) {
                $blocks = [];
                $html = '';
                foreach($files as $file) {
                    $html .= ($html != '' ? '<br/>' : '')
                        . "<a target='_blank' href='{$file['url']}'>{$file['name']}</a>";
                }
                $blocks[] = [
                    'type' => 'title',
                    'title' => 'Backtracks',
                    ];
                $blocks[] = [
                    'type' => 'text',
                    'title' => $section['name'] . '<br/>' . $division['location_name'] . '<br/>' . $division['name'],
                    'level' => 3,
                    'content' => $html,
                    ];
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }

            if( $division_files_found == 'yes' ) {
                $items[] = [
                    'url' => $base_url . '/' . $section_permalink . '/' . $division_permalink,
                    'text' => $division['location_name'] . ' - ' . $division['name'],
                    ];
            }
        }

        $blocks[] = [
            'type' => 'buttons',
            'title' => $section['name'],
            'level' => 2,
            'items' => $items,
            ];

    }
    
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
