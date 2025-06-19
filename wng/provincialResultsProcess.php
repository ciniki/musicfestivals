<?php
//
// Description
// -----------
// This function will generate the blocks to display member festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_provincialResultsProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titlesMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.998', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) || !isset($section['settings']['festival-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.999', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    //
    // Load the member ID and provincials TNID
    //
    $strsql = "SELECT members.id, "
        . "members.tnid "
        . "FROM ciniki_musicfestival_members AS fmembers "
        . "INNER JOIN ciniki_musicfestivals_members AS members ON ("
            . "fmembers.member_id = members.id "
            . "AND members.member_tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'provincials');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1001', 'msg'=>'Unable to load provincials', 'err'=>$rc['err']));
    }
    if( !isset($rc['provincials']) ) {
        $content = isset($s['notreleased']) ? $s['notreleased'] : 'No provincials connected';
        if( (isset($s['title']) && $s['title'] != '') || $content != '' ) {
            $blocks[] = array(
                'type' => ($content == '' ? 'title' : 'text'),
                'title' => isset($s['title']) ? $s['title'] : '',
                'level' => isset($section['sequence']) && $section['sequence'] == 1 ? 1 : 2,
                'content' => $content,
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        return array('stat'=>'ok');
    }
    $member_id = $rc['provincials']['id'];
    $provincial_tnid = $rc['provincials']['tnid'];

    //
    // Load the provincial results
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "classes.ID AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "registrations.id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.placement, "
        . "registrations.finals_placement "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
            . ") "
        . "WHERE registrations.member_id = '" . ciniki_core_dbQuote($ciniki, $member_id) . "' "
        . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincial_tnid) . "' "
        . "AND registrations.status < 70 "
        . "AND registrations.status > 5 "
        . "AND (placement <> '' OR finals_placement <> '') "
        . "AND ((divisions.flags&0x20) = 0x20 OR (ssections.flags&0x20) = 0x20) "
        . "ORDER BY sections.sequence, sections.id, categories.sequence, categories.id, classes.sequence, classes.code, registrations.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name'),
            ),
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'public_name', 'placement', 'finals_placement',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1002', 'msg'=>'Unable to load results', 'err'=>$rc['err']));
    }
    $results = isset($rc['sections']) ? $rc['sections'] : array();

    if( count($results) == 0 ) {
        $content = isset($s['notreleased']) ? $s['notreleased'] : '';
        if( (isset($s['title']) && $s['title'] != '') || $content != '' ) {
            $blocks[] = array(
                'type' => ($content == '' ? 'title' : 'text'),
                'title' => isset($s['title']) ? $s['title'] : '',
                'level' => isset($section['sequence']) && $section['sequence'] == 1 ? 1 : 2,
                'content' => $content,
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
    }

    $sections = [];
    foreach($results as $section) {
        $name = preg_replace("/ \-.*/", '', $section['name']);
        $permalink = ciniki_core_makePermalink($ciniki, $name);
        if( !isset($sections[$permalink]) ) {
            $sections[$permalink] = [
                'title' => $name,
                'classes' => $section['classes'],
                'url' => $request['page']['path'] . '/provincials/' . $permalink, 
                ];
        } else {
            foreach($section['classes'] as $class) {
                $sections[$permalink]['classes'][] = $class;
            }
        }
    }

    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) 
        && $request['uri_split'][($request['cur_uri_pos']+1)] == 'provincials'
        && isset($sections[$request['uri_split'][($request['cur_uri_pos']+2)]]) 
        ) {
        $section = $sections[$request['uri_split'][($request['cur_uri_pos']+2)]];
        if( isset($s['title']) && $s['title'] != '' ) {
            $blocks[] = array(
                'type' => 'title',
                'title' => $s['title'] . ' - ' . $section['title'],
                );
        }
        foreach($section['classes'] as $class) {
            $registrations = $class['registrations'];
            foreach($registrations as $rid => $reg) {
                if( $reg['finals_placement'] != '' ) {
                    $registrations[$rid]['placement'] = $reg['finals_placement'];
                }
                $rc = ciniki_musicfestivals_titlesMerge($ciniki, $tnid, $reg, ['newline'=>'<br/>']);
                $registrations[$rid]['titles'] = $rc['titles'];
            }
            uasort($registrations, function($a, $b) {
                return strnatcmp($a['placement'], $b['placement']);    
                });
            $blocks[] = [
                'type' => 'table',
                'title' => $class['code'] . ' - ' . $class['name'],
                'headers' => 'no',
                'class' => 'fold-at-50',
                'columns' => [
                    ['label'=>'Placement', 'field'=>'placement'],
                    ['label'=>'Name', 'field'=>'display_name'],
                    ['label'=>'Titles', 'field'=>'titles'],
                    ],
                'rows' => $registrations,
                ];
        }
        return array('stat'=>'ok', 'blocks'=>$blocks, 'clear'=>'yes', 'stop'=>'yes');
    } else {
        if( isset($s['title']) && $s['title'] != '' ) {
            $content = isset($s['content']) ? $s['content'] : '';
            $blocks[] = array(
                'type' => ($content == '' ? 'title' : 'text'),
                'title' => $s['title'],
                'content' => $content,
                );
        }
        $blocks[] = [
            'type' => 'buttons',
            'section' => 'provincial-results',
            'class' => 'musicfestival-provincial-results',
            'level' => 2,
            'items' => $sections,
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
