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
function ciniki_musicfestivals_wng_rulesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.104', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1066', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['syllabus-id']) || $s['syllabus-id'] == '' || $s['syllabus-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1067', 'msg'=>"No syllabus specified"));
    }

    //
    // Load the syllabus
    //
    $strsql = "SELECT syllabuses.id, "
        . "syllabuses.festival_id, "
        . "syllabuses.name, "
        . "syllabuses.rules "
        . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
        . "WHERE syllabuses.id = '" . ciniki_core_dbQuote($ciniki, $s['syllabus-id']) . "' "
        . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'syllabus');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1037', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
    }
    if( !isset($rc['syllabus']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1038', 'msg'=>'Unable to find requested syllabus'));
    }
    $syllabus = $rc['syllabus'];

    if( $syllabus['rules'] != '' && $syllabus['rules'] != '{}' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'rulesProcess');
        $rc = ciniki_musicfestivals_rulesProcess($ciniki, $tnid, $syllabus['rules']);    
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1078', 'msg'=>"No rules specified"));
        }
        $rules = $rc['rules'];
        if( isset($rules['intro']) && $rules['intro'] != '') {
            $blocks[] = [
                'type' => 'text',
                'level' => $section['sequence'] == 1 ? 1 : 2,
                'title' => isset($rules['title']) ? $rules['title'] : '',
                'content' => $rules['intro'],
                ];
        } elseif( isset($rules['title']) && $rules['title'] != '' ) {
            $blocks[] = [
                'type' => 'title',
                'level' => $section['sequence'] == 1 ? 1 : 2,
                'title' => $rules['title'],
                ];
        }
        foreach($rules['sections'] as $section) {
            if( isset($section['items']) && count($section['items']) > 0 ) {
                $blocks[] = [
                    'type' => 'list',
                    'title' => $section['title'],
                    'level' => 2,
                    'content' => $section['intro'],
                    'class' => 'section-' . ciniki_core_makePermalink($ciniki, $section['title']),
                    'list-start' => $section['start'],
                    'list-type' => isset($section['list-type']) ? $section['list-type'] : '1',
                    'items' => $section['items'],
                    ];
            } elseif( isset($section['intro']) && $section['intro'] ) {
                $blocks[] = [
                    'type' => 'text',
                    'level' => 2,
                    'title' => isset($section['title']) ? $section['title'] : '',
                    'content' => $section['intro'],
                    ];
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
