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

    //
    // Load the schedules
    //
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
    
    if( isset($selected_section) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    //
    // Display the buttons for the list of schedules
    //
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
