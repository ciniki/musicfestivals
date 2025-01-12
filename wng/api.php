<?php
//
// Description
// -----------
// This function will process api requests for wng.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_api(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.526', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // saveSubmission - Save the form submission
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'adjudicationsSave' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'apiAdjudicationsSave');
        return ciniki_musicfestivals_wng_apiAdjudicationsSave($ciniki, $tnid, $request);
    }
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'classSearch' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'apiClassSearch');
        return ciniki_musicfestivals_wng_apiClassSearch($ciniki, $tnid, $request);
    }
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'scheduleSearch' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'apiScheduleSearch');
        return ciniki_musicfestivals_wng_apiScheduleSearch($ciniki, $tnid, $request);
    }

    return array('stat'=>'ok');
}
?>
