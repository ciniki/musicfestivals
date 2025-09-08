<?php
//
// Description
// -----------
// Search the classes for the festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_apiScheduleSearch(&$ciniki, $tnid, $request) {
   
   
    if( !isset($request['args']['search_string']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.880', 'msg'=>'No search string specified'));
    }
    if( !isset($request['args']['festival-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.881', 'msg'=>'No festival specified'));
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $request['args']['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.882', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $tnid, [
        'keywords' => $request['args']['search_string'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['search_string']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    $limit = 50;

    //
    // Get the settings for the schedule section
    // FIXME: This just picks the first schedule section for this festival.
    //
    $strsql = "SELECT sections.id, "
        . "sections.settings "
        . "FROM ciniki_wng_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.ref = 'ciniki.musicfestivals.schedules' "
        . "AND settings like '%\"festival-id\":\"" . ciniki_core_dbQuote($ciniki, $request['args']['festival-id']) . "\"%' "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.323', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( isset($rc['section']['settings']) ) {
        $s = json_decode($rc['section']['settings'], true);
    }

    //
    // search the classes
    //
    if( $keywords != '' ) {
        $strsql = "SELECT registrations.id, " ;
        if( isset($s['names']) && $s['names'] == 'private' ) {
            $strsql .= "registrations.display_name, ";
        } else {
            $strsql .= "registrations.public_name AS display_name, ";
        }
        $strsql .= "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "timeslots.id AS timeslot_id, "
            . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time,"
            . "timeslots.name AS slot_name, "
            . "DATE_FORMAT(divisions.division_date, '%b %D, %Y') AS division_date, "
            . "divisions.name AS division_name, "
            . "sections.name AS section_name, "
            . "IFNULL(locations.name, '') AS location_name "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ( "
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ( "
                . "registrations.timeslot_id = timeslots.id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ( "
                . "timeslots.sdivision_id = divisions.id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ( "
                . "divisions.ssection_id = sections.id "
                . "AND (sections.flags&0x10) = 0x10 " // Schedule released
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ( "
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $request['args']['festival-id']) . "' "
                . "AND (";
        if( isset($s['names']) && $s['names'] == 'private' ) {
            $strsql .= "registrations.display_name like '" . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
                . "OR registrations.display_name like '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' ";
        } else {
            $strsql .= "registrations.public_name like '" . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
                . "OR registrations.public_name like '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' ";
        }
        $strsql .= "OR classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' "
                    . ") ";
        if( isset($request['args']['lv']) && $request['args']['lv'] == 'live' ) {
            $strsql .= "AND (classes.feeflags&0x03) > 0 ";
        } elseif( isset($request['args']['lv']) && $request['args']['lv'] == 'virtual' ) {
            $strsql .= "AND (classes.feeflags&0x08) = 0x08 ";
        }
        $strsql .= "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY registrations.display_name, divisions.division_date, divisions.name, timeslots.slot_time "
            . "LIMIT " . ($limit + 1)
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id',  
                'fields'=>array('id', 'display_name', 'class_code', 'class_name', 
                    'timeslot_id', 'slot_time', 'slot_name', 'division_name', 'division_date', 'section_name', 
                    'location_name',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.906', 'msg'=>'Unable to search classes', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
    } else {
        $registrations = [];
    }

    if( count($registrations) > 0 ) {
        //
        // Process the classes to determine which fee to show
        //
        $live_column = 'no';
        $virtual_column = 'no';
        $plus_live_column = 'no';
        $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird' : 'Fee';
        $count = 0;
        foreach($registrations as $rid => $reg) {
            $count++;
            if( $count > $limit ) {
                unset($registrations[$rid]);
                continue;
            }

            $registrations[$rid]['location'] = $reg['location_name'];

            //
            // Determine which link to use
            //
            if( substr($request['args']['baseurl'], -1) != '/' ) {
                $request['args']['baseurl'] .= '/';
            }
            if( isset($request['args']['layout']) && $request['args']['layout'] == 'groupbuttons' ) {
                $group_permalink = ciniki_core_makePermalink($ciniki, $reg['groupname']);
//                $registrations[$rid]['link'] = "<a class='button' href='{$request['args']['baseurl']}{$reg['section_permalink']}/{$group_permalink}#{$reg['category_permalink']}'>View</a>";
            } else {
//                $registrations[$rid]['link'] = "<a class='button' href='{$request['args']['baseurl']}{$reg['section_permalink']}#{$reg['category_permalink']}'>View</a>";
            }
        }
        //
        // Check if online registrations enabled, and online registrations enabled for this class
        //
        $block = array(
            'type' => 'table', 
            'section' => 'registrations', 
            'title' => 'Search Results',
            'headers' => 'yes',
            'class' => 'fold-at-40',
            'columns' => array(
                array('label'=>'Name', 'fold-label'=>'Name:', 'field'=>'display_name', 'class'=>''),
                array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'class_name', 'class'=>''),
                array('label'=>'Location', 'fold-label'=>'Location:', 'field'=>'location', 'class'=>''),
                array('label'=>'Date', 'fold-label'=>'Date:', 'field'=>'division_date', 'class'=>''),
                array('label'=>'Timeslot', 'fold-label'=>'Timeslot:', 'field'=>'slot_time', 'class'=>''),
                ),
            'rows' => $registrations,
            );
        if( isset($section['tableheader']) && $section['tableheader'] == 'multiprices' && count($block['columns']) < 3 ) {
            $block['headers'] = 'no';
        }
//        $block['columns'][] = array('label'=>'', 'field'=>'link', 'class'=>'alignright buttons'); 
        $blocks[] = $block;
        if( $count > $limit ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'warning',
                'content' => 'Too many results, please add more keywords to your search',
                ];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'blocksGenerate');
        return ciniki_wng_blocksGenerate($ciniki, $tnid, $request, $blocks);
    } elseif( $request['args']['search_string'] != '' && $keywords == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'warning', 
            'content' => 'Keep typing...',
            ]);
    } elseif( $request['args']['search_string'] == '' ) {
        return array('stat'=>'ok', 'content'=>'');
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'error', 
            'content' => 'No classes found',
            ]);
    }


    return array('stat'=>'ok', 'content'=>'');
}
?>
