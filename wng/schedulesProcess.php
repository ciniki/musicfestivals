<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_schedulesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.671', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.672', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.673', 'msg'=>"No festival specified"));
    }

    if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        $section_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) ) {
        $division_permalink = $request['uri_split'][($request['cur_uri_pos']+2)];
    }

    //
    // Load the schedules
    //
    if( isset($s['layout']) && $s['layout'] == 'division-buttons' ) {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "ORDER BY sections.name, divisions.division_date "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name',
                )),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'text'=>'division_name', 'division_date_text',
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.674', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($sections as $sid => $sc) {
            $sections[$sid]['permalink'] = ciniki_core_makePermalink($ciniki, $sc['name']);
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'];
            if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] ) {
                $selected_section = $sections[$sid];
            }
            foreach($sc['divisions'] as $did => $division) {
                $sections[$sid]['divisions'][$did]['permalink'] = ciniki_core_makePermalink($ciniki, $division['name']);
                $sections[$sid]['divisions'][$did]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'] . '/' . $sections[$sid]['divisions'][$did]['permalink'];
                if( isset($s['division-dates']) && $s['division-dates'] == 'yes' ) {
                    $sections[$sid]['divisions'][$did]['text'] .= '<br/>' . $division['division_date_text'];
                }
                if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] 
                    && isset($division_permalink) && $division_permalink == $sections[$sid]['divisions'][$did]['permalink'] 
                    ) {
                    $selected_division = $sections[$sid]['divisions'][$did];
                }
            }
        }
        
    } else {
        $strsql = "SELECT sections.id, "
            . "sections.name "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.674', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($sections as $sid => $sc) {
            $sections[$sid]['permalink'] = ciniki_core_makePermalink($ciniki, $sc['name']);
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'];
            if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] ) {
                $selected_section = $sections[$sid];
            }
        }
    }

    //
    // Add the title block
    //
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Schedule',
            );
    }
    
    if( isset($selected_section) && isset($selected_division) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        $section['settings']['division-id'] = $selected_division['id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    elseif( isset($selected_section) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    //
    // Display the buttons for the list of schedules
    //
    elseif( isset($s['layout']) && $s['layout'] == 'division-buttons' ) {
        if( isset($division_permalink) && $division_permalink != '' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => 'Schedule not found',
                );
        }
        foreach($sections as $section) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'schedule-buttons',
                'title' => $section['name'],
                'level' => 2,
                'list' => $section['divisions'],
                );
        }

    }
    else {
        if( isset($section_permalink) && $section_permalink != '' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => 'Schedule not found',
                );
        }
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'schedule-buttons',
            'list' => $sections,
            );
    }
    

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
