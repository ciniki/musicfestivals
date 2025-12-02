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
function ciniki_musicfestivals_wng_countdownProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.1206', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1207', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1208', 'msg'=>"No festival specified"));
    }

    //
    // Load the festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $s['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return ['stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No festival',
            ]]];
    }
    $festival = isset($rc['festival']) ? $rc['festival'] : array();

    
    if( isset($festival['live']) && $festival['live'] == 'yes' 
        && isset($festival['live_end_dt']) && is_object($festival['live_end_dt'])
        ) {
        $blocks[] = [
            'type' => 'countdown',
            'running-title' => isset($s['open-title']) ? $s['open-title'] : '',
            'end-dt' => $festival['live_end_dt'],
            'finished-title' => isset($s['closed-title']) ? $s['closed-title'] : '',
            'finished-content' => isset($s['closed-content']) ? $s['closed-content'] : '',
            ];
    } elseif( isset($s['closed-title']) && $s['closed-title'] != '' 
        && (!isset($s['closed-content']) || $s['closed-content'] == '')
        ) {
        $blocks[] = [
            'type' => 'title',
            'level' => 2,
            'class' => 'aligncenter',
            'title' => $s['closed-title'],
            ];
    } elseif( isset($s['closed-content']) && $s['closed-content'] != '' ) {
        $blocks[] = [
            'type' => 'text',
            'level' => 2,
            'class' => 'aligncenter',
            'title' => isset($s['closed-title']) ? $s['closed-title'] : '',
            'content' => $s['closed-content'],
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
